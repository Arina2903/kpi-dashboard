<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 of the multi-company platform: lets each company define its own
 * per-department job-level roles (e.g. "Staff", "Lead") instead of relying on
 * one hardcoded hierarchy — see the "Performix Platform Blueprint" design
 * doc, section 3, for the full reasoning.
 *
 * `roles` is separate from `company_users.role`/`department_users.role`
 * (the fixed company_admin/department_admin/department_user access tier
 * that gates what someone can do in the admin console): this table is the
 * company's own configurable org-structure label instead. Verified against
 * the live schema before writing — this project has no `kpi_permissions`
 * table at all (that's a legacy-app-only concept; the platform's own
 * KpiController/KpiSubmissionController enforce access via RLS + inline
 * role checks, not a permissions matrix table), so unlike an earlier draft
 * of this migration, nothing here touches it.
 *
 * RLS policies reuse the project's existing `auth_is_richworks_super_admin()`,
 * `auth_company_ids()`, and `auth_role_in_company()` helpers (confirmed via
 * `pg_proc`/`pg_policies` against the live database) rather than re-deriving
 * `auth.uid()` resolution by hand — `users.id` and `users.auth_user_id` are
 * two different columns, and every existing policy on this project resolves
 * `auth.uid()` through `auth_user_id`, never `id`, via those helpers.
 *
 * Runs against the `pgsql` connection, same as the prior FK-index migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('roles', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('department_id');
            $table->string('label');
            $table->smallInteger('rank')->default(0);
            $table->boolean('is_department_admin')->default(false);
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->unique(['department_id', 'label']);
            $table->index('department_id');
        });

        Schema::connection('pgsql')->table('department_users', function (Blueprint $table) {
            $table->uuid('role_id')->nullable()->after('role');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
            $table->index('role_id');
        });

        DB::connection('pgsql')->statement('alter table roles enable row level security');

        // Mirrors departments_select: visible to a Super Admin, or to anyone
        // who belongs to the role's company (resolved by joining through
        // departments, since roles has no company_id column of its own).
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy roles_select on roles for select
              using (
                auth_is_richworks_super_admin()
                or exists (
                  select 1 from departments d
                  where d.id = roles.department_id
                    and d.company_id in (select auth_company_ids())
                )
              )
        SQL);

        // Mirrors departments_insert: only a Super Admin or that company's
        // own Company Admin may shape its role structure — matching
        // RoleController::ensureCompanyAccess(), which checks the same tier.
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy roles_write on roles for all
              using (
                auth_is_richworks_super_admin()
                or exists (
                  select 1 from departments d
                  where d.id = roles.department_id
                    and auth_role_in_company(d.company_id) = 'company_admin'
                )
              )
              with check (
                auth_is_richworks_super_admin()
                or exists (
                  select 1 from departments d
                  where d.id = roles.department_id
                    and auth_role_in_company(d.company_id) = 'company_admin'
                )
              )
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists roles_write on roles');
        DB::connection('pgsql')->statement('drop policy if exists roles_select on roles');

        Schema::connection('pgsql')->table('department_users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::connection('pgsql')->dropIfExists('roles');
    }
};
