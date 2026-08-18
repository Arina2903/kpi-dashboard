<?php

namespace App\Services;

use RuntimeException;

/**
 * The only sanctioned way for ANIRA (and any other assistant, bot, digest, or
 * webhook) to read Platform data on a user's behalf.
 *
 * WHY THIS EXISTS
 * ---------------
 * An AI assistant is a data-exfiltration path wearing a friendly face. Ask it
 * "how is the sales team doing?" and whatever context the application stuffed
 * into the prompt is what it will happily summarise — including rows the
 * asking user was never entitled to see. The model cannot enforce a boundary
 * it was handed the wrong side of; by the time text reaches the prompt, the
 * leak has already happened. The same is true of Telegram: a digest job that
 * assembles content with a privileged client and then sends it to a chat is a
 * leak whether or not anyone notices.
 *
 * The legacy `AiController` demonstrates the failure mode exactly — it injects
 * `SupabaseService` (service_role, which bypasses RLS entirely) and builds
 * ANIRA's context from it, with the session's `employee_uuid` as the only
 * thing standing between one employee's prompt and another's data. Every
 * Telegram controller and both Telegram services do the same. Those paths are
 * legacy and currently dead (their `employees`/`user_company_roles` tables no
 * longer exist in production), which is the only reason this is a latent bug
 * rather than a live one — and precisely why the replacement should exist
 * before anything reconnects them to the Platform's real data.
 *
 * THE GUARANTEE
 * -------------
 * This class can only be constructed from a caller's own Supabase Auth access
 * token, and every read it performs goes through `SupabaseUserService`. There
 * is no code path here that reaches a `service_role` client, so "ANIRA sees
 * only what the requesting user is authorised to access" is not a rule someone
 * has to remember — it is the only thing the type permits. Postgres RLS
 * filters the rows; this class just makes sure nothing hands it a key that
 * skips the filter.
 *
 * Everything below returns already-RLS-filtered rows. An empty result means
 * "not authorised or not present", and those two cases are deliberately
 * indistinguishable — telling a caller that a KPI exists but is invisible to
 * them is itself a small leak.
 */
class AuthorizedDataScope
{
    private SupabaseUserService $supabase;

    /**
     * @param  string  $accessToken  the requesting user's OWN Supabase Auth
     *                               access token. Never a service-role key,
     *                               never another user's token.
     */
    public function __construct(string $accessToken)
    {
        if (trim($accessToken) === '') {
            throw new RuntimeException(
                'AuthorizedDataScope requires the requesting user\'s own access token. '
                . 'Refusing to build an assistant context with no identity attached.'
            );
        }

        // A service-role JWT carries `"role":"service_role"` in its payload.
        // Anyone passing one here has misunderstood the entire point of this
        // class, so fail loudly at construction rather than silently returning
        // every company's data.
        if ($this->looksLikeServiceRoleToken($accessToken)) {
            throw new RuntimeException(
                'A service_role key was passed to AuthorizedDataScope. That key bypasses RLS, '
                . 'which would let the assistant read every company\'s data regardless of who asked.'
            );
        }

        $this->supabase = new SupabaseUserService($accessToken);
    }

    /**
     * Build one from the Platform session. Returns null when there is no
     * signed-in Platform user, so callers must handle "no context" explicitly
     * instead of quietly falling back to a privileged read.
     */
    public static function fromSession(): ?self
    {
        $token = session('platform_access_token');

        return $token ? new self($token) : null;
    }

    // ------------------------------------------------------------------
    // Reads — every one of these is RLS-filtered by construction
    // ------------------------------------------------------------------

    /**
     * The asking user's own identity row — filtered explicitly on the
     * token's own `sub` claim, not left to RLS to narrow down on its own.
     * See SupabaseUserService::currentAuthUserId()'s docblock for why an
     * unfiltered lookup can silently return a DIFFERENT authorized user's
     * row for a Super Admin or Company Admin (both see other people's rows
     * under RLS, so a bare `first()` isn't necessarily "yourself").
     */
    public function me(): ?array
    {
        return $this->supabase->first('users', [
            'auth_user_id' => 'eq.' . $this->supabase->currentAuthUserId(),
            'select' => 'id,name,email,role,status',
        ]);
    }

    /** Companies the asking user may see — one row for a normal member. */
    public function companies(): array
    {
        return $this->supabase->get('companies', [
            'select' => 'id,name,code,status',
            'order' => 'name.asc',
            'limit' => 50,
        ]) ?? [];
    }

    /** Departments visible to the asking user, optionally within one company. */
    public function departments(?string $companyId = null): array
    {
        $query = ['select' => 'id,company_id,name,code', 'order' => 'name.asc', 'limit' => 200];

        if ($companyId) {
            $query['company_id'] = 'eq.' . $companyId;
        }

        return $this->supabase->get('departments', $query) ?? [];
    }

    /**
     * KPIs the asking user is permitted to see. `kpis_select` routes through
     * `auth_can_view_kpi()`, so a 'restricted' KPI without a grant simply is
     * not in this list — SLT, Executive and Employee each get a different
     * answer from the identical call.
     */
    public function kpis(?string $companyId = null, int $limit = 100): array
    {
        $query = [
            'select' => 'id,company_id,category_id,name,description,target,unit,frequency,visibility,status',
            'order' => 'name.asc',
            'limit' => $limit,
        ];

        if ($companyId) {
            $query['company_id'] = 'eq.' . $companyId;
        }

        return $this->supabase->get('kpis', $query) ?? [];
    }

    /**
     * Submissions the asking user may read. `kpi_submissions_select` requires
     * `auth_can_view_kpi()` on the parent KPI as well as company/department
     * reach, so a restricted KPI cannot leak its numbers here even though the
     * submissions table itself is company-scoped.
     */
    public function submissions(?string $companyId = null, ?string $departmentId = null, int $limit = 200): array
    {
        $query = [
            'select' => 'id,company_id,department_id,kpi_id,value,submission_date,notes',
            'order' => 'submission_date.desc',
            'limit' => $limit,
        ];

        if ($companyId) {
            $query['company_id'] = 'eq.' . $companyId;
        }

        if ($departmentId) {
            $query['department_id'] = 'eq.' . $departmentId;
        }

        return $this->supabase->get('kpi_submissions', $query) ?? [];
    }

    /**
     * Assembles the context blob an assistant prompt is built from. Nothing in
     * here was fetched with elevated privileges, so whatever the model then
     * says about it is, by construction, something the asking user could have
     * read themselves.
     *
     * @return array{user: ?array, companies: array, departments: array, kpis: array, submissions: array}
     */
    public function assistantContext(?string $companyId = null): array
    {
        return [
            'user' => $this->me(),
            'companies' => $this->companies(),
            'departments' => $this->departments($companyId),
            'kpis' => $this->kpis($companyId),
            'submissions' => $this->submissions($companyId),
        ];
    }

    /**
     * Guardrail for the other half of the problem. RLS decides what the
     * assistant may READ; this decides what it may say back. An assistant
     * asked to summarise "the company" can only cite KPIs that were in its
     * authorised context, so a hallucinated or model-recalled KPI name never
     * reaches the user looking like real data.
     *
     * @param  array  $authorizedKpis  the exact list handed to the prompt
     * @return bool   whether every id the model cited was one it was given
     */
    public function citationsAreAuthorized(array $citedKpiIds, array $authorizedKpis): bool
    {
        $permitted = array_column($authorizedKpis, 'id');

        foreach ($citedKpiIds as $id) {
            if (!in_array($id, $permitted, true)) {
                return false;
            }
        }

        return true;
    }

    private function looksLikeServiceRoleToken(string $token): bool
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            // Not a JWT at all — most likely a raw API key, which a user
            // access token never is.
            return true;
        }

        $payload = json_decode(
            base64_decode(strtr($parts[1], '-_', '+/')) ?: '',
            true
        );

        return is_array($payload) && ($payload['role'] ?? null) === 'service_role';
    }
}
