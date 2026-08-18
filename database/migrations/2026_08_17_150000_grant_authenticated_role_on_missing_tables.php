<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes a real, systemic bug found while walking through the app as an
 * actual authenticated user: six tables — `roles`, `kpi_access_grants`,
 * `platform_admin_assignments`, `admin_action_logs`, `kpi_templates`, and
 * `kpi_template_items` — were created (across four separate migrations, in
 * four separate phases of this project) without ever granting the
 * `authenticated` Postgres role any table-level privileges on them. Every
 * sibling table from the original foundational schema (`companies`, `users`,
 * `kpis`, etc.) has `SELECT,INSERT,UPDATE,DELETE` granted to `authenticated`;
 * these six only ever had `REFERENCES,TRIGGER,TRUNCATE` — confirmed directly
 * against `information_schema.role_table_grants`, not inferred.
 *
 * This matters because Postgres checks table-level GRANTs BEFORE Row-Level
 * Security policies ever run. RLS decides which ROWS a query may touch;
 * GRANTs decide whether the role may touch the table AT ALL. Without the
 * grant, every request via `SupabaseUserService` (anon key + a real user's
 * token — always resolves to the `authenticated` Postgres role) failed with
 * a flat `permission denied for table ...`, regardless of how correct the
 * RLS policy was. Concretely, this meant every one of these had been
 * completely non-functional in production the entire time they existed:
 *
 * - `admin_action_logs` — the whole audit trail: every `logAdminAction()`
 *   write (company create/activate/suspend/archive, admin invites, etc.) AND
 *   the AuditLogController viewer built specifically to read it.
 * - `platform_admin_assignments` — PlatformAdminController could create the
 *   `users.role = 'platform_admin'` promotion but the assignment-row half
 *   always failed, so no Platform Admin could ever actually be scoped to a
 *   company.
 * - `kpi_access_grants` — KpiController::storeGrant()/destroyGrant(), the
 *   entire per-KPI visibility-widening feature.
 * - `kpi_templates`/`kpi_template_items` — the whole KPI template library
 *   and KpiController::applyTemplate().
 * - `roles` — configurable per-department job-level labels.
 *
 * RLS itself was never the problem on any of these — each already has
 * correct, tenant-scoped policies (confirmed by the Core Platform Rule
 * conformance check, which audits policies, not grants, and is exactly why
 * this gap went undetected by that check specifically). Granted uniformly
 * with what every other Platform table already has, so RLS remains the only
 * thing actually deciding what's allowed.
 */
return new class extends Migration
{
    private const TABLES = [
        'roles',
        'kpi_access_grants',
        'platform_admin_assignments',
        'admin_action_logs',
        'kpi_templates',
        'kpi_template_items',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::connection('pgsql')->statement("grant select, insert, update, delete on public.{$table} to authenticated");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::connection('pgsql')->statement("revoke select, insert, update, delete on public.{$table} from authenticated");
        }
    }
};
