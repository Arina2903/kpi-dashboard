<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\LogsAdminActions;
use App\Http\Controllers\Platform\Concerns\PlatformAuthorization;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Performix Platform Blueprint, Phase 11 — expanded under requirement #8 from
 * a Super-Admin-only viewer of the narrow "cross-company bypass" trail into
 * the comprehensive audit system's read side: `index()`/`export()` remain
 * Center-only (the cross-company view), and `companyIndex()`/`companyExport()`
 * are new — a Company Admin can now see (and export) their OWN company's
 * slice of the same table, which matters now that most of what's logged is
 * their own routine activity (KPI/target edits, role changes, suspensions),
 * not just Richworks support access.
 *
 * `companyIndex()` goes through the caller's own `SupabaseUserService` (RLS),
 * relying on `admin_action_logs_select_company` (2026_08_17_170000) rather
 * than an app-level filter — the same "RLS is the real boundary" pattern
 * every other Platform controller uses. `index()`/`export()` also use the
 * caller's own token now that a widened policy exists; a Super Admin's own
 * `admin_action_logs_select` policy still returns every row regardless.
 *
 * `admin_action_logs` has two separate foreign keys into `users`
 * (`actor_user_id`, `target_user_id`) — rather than relying on PostgREST's
 * FK-constraint-name embed-disambiguation syntax for embedding both from a
 * single query, this fetches the log rows plain and resolves the referenced
 * users/companies with two follow-up `in.()` queries, joined in PHP. Slightly
 * more code, nothing subtle to get wrong.
 */
class AuditLogController extends Controller
{
    use LogsAdminActions;
    use PlatformAuthorization;

    private const SELECT = 'id,action,actor_user_id,actor_email,target_company_id,target_user_id,target_type,target_id,before,after,metadata,ip_address,occurred_at';

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $logs = $supabase->get('admin_action_logs', [
            'select' => self::SELECT,
            'order' => 'occurred_at.desc',
            'limit' => 200,
        ]);

        return Inertia::render('Platform/AuditLog/Index', [
            'logs' => $this->enrich($supabase, $logs)->values()->all(),
            'company' => null,
        ]);
    }

    public function export(Request $request)
    {
        $this->ensureSuperAdmin($request);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $logs = $supabase->get('admin_action_logs', [
            'select' => self::SELECT,
            'order' => 'occurred_at.desc',
            'limit' => 1000,
        ]);

        try {
            $this->logAdminAction($request, 'export_audit_log', null, null, ['scope' => 'platform', 'row_count' => count($logs)], 'audit_log');
        } catch (\Throwable) {
            // Not fatal to the export itself — an operator downloading a CSV
            // shouldn't be blocked by a logging hiccup, but this one write
            // failing silently would be exactly the kind of gap the rest of
            // this trait exists to avoid, so it's still attempted, just not
            // allowed to break the response.
        }

        return $this->toCsvResponse($logs, 'audit-log.csv');
    }

    public function companyIndex(Request $request, string $company)
    {
        $this->ensureCompanyAdmin($request, $company);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $companyRow = $supabase->first('companies', [
            'id' => 'eq.' . $company,
            'select' => 'id,name,code',
        ]);

        $logs = $supabase->get('admin_action_logs', [
            'target_company_id' => 'eq.' . $company,
            'select' => self::SELECT,
            'order' => 'occurred_at.desc',
            'limit' => 200,
        ]);

        return Inertia::render('Platform/AuditLog/Index', [
            'logs' => $this->enrich($supabase, $logs)->values()->all(),
            'company' => $companyRow,
        ]);
    }

    public function companyExport(Request $request, string $company)
    {
        $this->ensureCompanyAdmin($request, $company);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $logs = $supabase->get('admin_action_logs', [
            'target_company_id' => 'eq.' . $company,
            'select' => self::SELECT,
            'order' => 'occurred_at.desc',
            'limit' => 1000,
        ]);

        try {
            $this->logCompanyAction($request, 'export_audit_log', $company, null, ['scope' => 'company', 'row_count' => count($logs)], 'audit_log');
        } catch (\Throwable) {
            // See export()'s note — attempted, not allowed to block the file.
        }

        return $this->toCsvResponse($logs, 'audit-log-' . $company . '.csv');
    }

    private function enrich(SupabaseUserService $supabase, array $logs): \Illuminate\Support\Collection
    {
        $userIds = collect($logs)
            ->flatMap(fn ($l) => [$l['actor_user_id'] ?? null, $l['target_user_id'] ?? null])
            ->filter()
            ->unique()
            ->values();

        $companyIds = collect($logs)->pluck('target_company_id')->filter()->unique()->values();

        $users = $userIds->isEmpty() ? collect() : collect($supabase->get('users', [
            'id' => 'in.(' . $userIds->implode(',') . ')',
            'select' => 'id,name,email',
        ]))->keyBy('id');

        $companies = $companyIds->isEmpty() ? collect() : collect($supabase->get('companies', [
            'id' => 'in.(' . $companyIds->implode(',') . ')',
            'select' => 'id,name,code',
        ]))->keyBy('id');

        return collect($logs)->map(fn ($log) => [
            ...$log,
            'actor' => $log['actor_user_id'] ? $users->get($log['actor_user_id']) : null,
            'target_user' => $log['target_user_id'] ? $users->get($log['target_user_id']) : null,
            'target_company' => $log['target_company_id'] ? $companies->get($log['target_company_id']) : null,
        ]);
    }

    private function toCsvResponse(array $logs, string $filename): StreamedResponse
    {
        $columns = ['occurred_at', 'action', 'actor_email', 'actor_user_id', 'target_type', 'target_id', 'target_company_id', 'target_user_id', 'before', 'after', 'metadata', 'ip_address'];

        $response = new StreamedResponse(function () use ($logs, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($logs as $log) {
                fputcsv($handle, array_map(
                    fn ($col) => is_array($log[$col] ?? null) ? json_encode($log[$col]) : ($log[$col] ?? ''),
                    $columns
                ));
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
