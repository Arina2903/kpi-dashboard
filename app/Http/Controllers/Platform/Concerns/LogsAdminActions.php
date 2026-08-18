<?php

namespace App\Http\Controllers\Platform\Concerns;

use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Writes to `admin_action_logs` via `AuditLogService`. Requirement #8's
 * "proper audit system" needs every admin-shaped action captured — role
 * changes, KPI/target edits, suspensions, grants — not just the narrow
 * "Super Admin acting outside their own company" trail this trait used to
 * gate everything behind (the old `logIfSuperAdmin()`, since removed: it
 * meant an ordinary Company Admin creating KPIs, inviting staff, or changing
 * someone's role — the overwhelming majority of real usage — generated zero
 * audit trail at all).
 *
 * Two logging modes, matching how disruptive a lost write is:
 *   - `logAdminAction()`/`logCompanyAction()` — NOT best-effort. A failed
 *     write propagates to the caller, which is expected to catch it and tell
 *     the operator their action already happened but wasn't logged (see
 *     CompanyController::store() for the pattern). For deliberate,
 *     infrequent, admin-driven mutations, a silent gap is worse than a
 *     visible error.
 *   - `logBestEffort()` — never throws. For high-frequency or system-
 *     triggered events (page views, chat messages) where blocking the
 *     primary action on a logging hiccup would be worse than a rare gap.
 */
trait LogsAdminActions
{
    protected function logAdminAction(
        Request $request,
        string $action,
        ?string $targetCompanyId = null,
        ?string $targetUserId = null,
        array $metadata = [],
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        $platformUser = $request->attributes->get('platformUser');

        app(AuditLogService::class)->record([
            'actor_user_id' => $platformUser['id'] ?? null,
            'actor_email' => $platformUser['email'] ?? null,
            'action' => $action,
            'target_company_id' => $targetCompanyId,
            'target_user_id' => $targetUserId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * True when the caller is only able to touch this company via their
     * Super Admin (or assigned Platform Admin) bypass — i.e. they have no
     * `company_users` membership of their own here. This is the "Richworks
     * support access" moment CLAUDE.md's Core Platform Rule discussion calls
     * out; it's recorded as metadata on every write now (not just gated
     * behind it, the way the old `logIfSuperAdmin()` did) so the trail can
     * always answer "was this the company's own admin, or Center support?"
     */
    protected function isSuperAdminBypass(Request $request, string $targetCompanyId): bool
    {
        $platformUser = $request->attributes->get('platformUser');

        if (!($platformUser['is_super_admin'] ?? false) && !($platformUser['is_platform_admin'] ?? false)) {
            return false;
        }

        return !collect($platformUser['company_memberships'] ?? [])
            ->contains(fn ($m) => $m['company_id'] === $targetCompanyId);
    }

    /**
     * Logs a company-scoped write unconditionally, regardless of the actor's
     * tier — a Company Admin managing their own company is logged exactly
     * like a Super Admin bypassing in, with `acting_as_super_admin_bypass`
     * in the metadata distinguishing the two after the fact.
     */
    protected function logCompanyAction(
        Request $request,
        string $action,
        string $targetCompanyId,
        ?string $targetUserId = null,
        array $metadata = [],
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        $metadata['acting_as_super_admin_bypass'] = $this->isSuperAdminBypass($request, $targetCompanyId);

        $this->logAdminAction($request, $action, $targetCompanyId, $targetUserId, $metadata, $targetType, $targetId, $before, $after);
    }

    protected function logBestEffort(
        Request $request,
        string $action,
        ?string $targetCompanyId = null,
        ?string $targetUserId = null,
        array $metadata = [],
        ?string $targetType = null,
        ?string $targetId = null,
    ): void {
        $platformUser = $request->attributes->get('platformUser');

        app(AuditLogService::class)->recordBestEffort([
            'actor_user_id' => $platformUser['id'] ?? null,
            'actor_email' => $platformUser['email'] ?? null,
            'action' => $action,
            'target_company_id' => $targetCompanyId,
            'target_user_id' => $targetUserId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * "Admin access" auditing (requirement #8's own category): logs a Super
     * Admin or assigned Platform Admin merely VIEWING a company's data they
     * don't themselves belong to. An ordinary Company Admin/SLT/Executive
     * loading their own company's pages generates zero rows here — this is
     * specifically the Richworks-support-access moment, not general page-view
     * telemetry (which would be pure noise at Platform scale). Best-effort:
     * a logging hiccup must never block a page load.
     */
    protected function logAdminAccessIfCrossCompany(Request $request, string $action, string $targetCompanyId, array $metadata = []): void
    {
        if (!$this->isSuperAdminBypass($request, $targetCompanyId)) {
            return;
        }

        $this->logBestEffort($request, $action, $targetCompanyId, null, $metadata, 'company', $targetCompanyId);
    }
}
