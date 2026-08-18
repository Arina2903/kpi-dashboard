<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes a live production bug found while running isolation tests: the
 * UPDATE policy's WITH CHECK on `kpis`, `kpi_categories`, `departments`,
 * `kpi_submissions`, and `department_users` each queried the SAME table the
 * policy is attached to (e.g. `company_id = (select k2.company_id from kpis
 * k2 where k2.id = kpis.id)`), which Postgres rejects outright with
 * "infinite recursion detected in policy for relation ...". Confirmed live:
 * this blocked EVERY update to these tables for EVERYONE, including a
 * company_admin editing their own company's own KPI -- not a tenant-isolation
 * leak (it fails closed, nothing was ever exposed), but a real functional
 * bug that made the write path unusable.
 *
 * The fix: for kpis/kpi_categories/departments, WITH CHECK just needs the
 * NEW row to still satisfy the same "company_admin of this company_id"
 * condition already used in USING -- no self-reference needed at all, since
 * that alone already prevents moving the row to a company the caller
 * doesn't admin. For kpi_submissions, WITH CHECK additionally confirms
 * company_id still matches department_id's actual company, via a join to
 * `departments` (a DIFFERENT table, so non-recursive) -- the same pattern
 * kpi_submissions_insert already used correctly. department_users_update's
 * WITH CHECK already joined `departments` rather than itself, so it wasn't
 * broken -- recreated here only for symmetry with its own USING clause.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists kpis_update on kpis');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpis_update on kpis for update
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
              with check (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists kpi_categories_update on kpi_categories');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_categories_update on kpi_categories for update
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
              with check (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists departments_update on departments');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy departments_update on departments for update
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
              with check (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists department_users_update on department_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_update on department_users for update
              using (
                exists (
                  select 1 from departments d
                  where d.id = department_users.department_id
                    and (auth_is_richworks_super_admin() or auth_role_in_company(d.company_id) in ('company_admin', 'department_admin'))
                )
              )
              with check (
                exists (
                  select 1 from departments d
                  where d.id = department_users.department_id
                    and (auth_is_richworks_super_admin() or auth_role_in_company(d.company_id) in ('company_admin', 'department_admin'))
                )
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists kpi_submissions_update on kpi_submissions');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_submissions_update on kpi_submissions for update
              using (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
                or (department_id in (select auth_department_ids()) and submitted_by = auth_current_user_id())
              )
              with check (
                (
                  auth_is_richworks_super_admin()
                  or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
                  or (department_id in (select auth_department_ids()) and submitted_by = auth_current_user_id())
                )
                and company_id = (select departments.company_id from departments where departments.id = kpi_submissions.department_id)
              )
        SQL);
    }

    public function down(): void
    {
        // Deliberately not restoring the recursive versions -- there is no
        // scenario where the broken behavior is preferable. Down just drops
        // back to no UPDATE policy at all (deny-by-default), matching the
        // state immediately before either version existed.
        foreach (['kpis_update', 'kpi_categories_update', 'departments_update', 'department_users_update', 'kpi_submissions_update'] as $policy) {
            $table = str_replace('_update', '', $policy);
            DB::connection('pgsql')->statement("drop policy if exists {$policy} on {$table}");
        }
    }
};
