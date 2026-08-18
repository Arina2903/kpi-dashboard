<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Real, previously-undocumented gap found while actually running
 * `tenant_isolation.sql` against a real disposable Postgres instance for the
 * first time (Requirement #10 follow-up, "Production-Grade Tenant
 * Isolation") and then deliberately probing individual-user suspension —
 * not something surfaced by re-reading the policy SQL.
 *
 * `auth_company_ids()` and `auth_role_in_company()` both correctly check
 * `company_users.status = 'active'` (added when Phase 5's suspension
 * enforcement first shipped) — a Company Admin or Employee whose OWN
 * `company_users` row is suspended (`DepartmentController::suspendUser()`,
 * the comprehensive-audit-system pass) loses company-wide access on their
 * very next request, even if the company itself stays active.
 *
 * `auth_department_ids()` was never given the same check. It only ever
 * excluded a suspended/archived COMPANY, never a suspended INDIVIDUAL
 * membership row. Every policy that ORs in
 * `department_id in (select auth_department_ids())` inherits that gap:
 * `department_users_select`, `kpi_access_grants_select`, `reports_select`,
 * `reports_insert`, and — via `auth_can_view_kpi()`'s department-grant
 * branch, which also calls `auth_department_ids()` directly rather than
 * `auth_company_ids()` — `kpis_select` and `kpi_submissions_insert` too.
 * Concretely: a suspended Employee whose department has an explicit
 * `kpi_access_grants.department_id` grant could still read that KPI and
 * submit new `kpi_submissions` against it after being suspended — not a
 * cross-tenant leak, but exactly the "suspended user retains operational
 * access" failure mode the tenant-isolation work is meant to close.
 *
 * Fixed once, in the single shared helper every one of those policies
 * already routes through — not by patching each policy individually, which
 * is exactly how this kind of gap re-opens later.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_department_ids()
            returns setof uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select du.department_id from department_users du
                join users u on u.id = du.user_id
                join departments d on d.id = du.department_id
                join companies c on c.id = d.company_id
                join company_users cu on cu.user_id = du.user_id and cu.company_id = d.company_id
                where u.auth_user_id = auth.uid()
                  and cu.status = 'active'
                  and c.status not in ('suspended', 'archived');
            $$
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_department_ids()
            returns setof uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select du.department_id from department_users du
                join users u on u.id = du.user_id
                join departments d on d.id = du.department_id
                join companies c on c.id = d.company_id
                where u.auth_user_id = auth.uid()
                  and c.status not in ('suspended', 'archived');
            $$
        SQL);
    }
};
