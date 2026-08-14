<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phases 1-7 of the multi-company platform, written retroactively. Every
 * table/function/policy below was already live in production (project
 * eavmrurxxdxbufkkzlup) when this file was written -- confirmed column-by-
 * column, policy-by-policy against the live database via the session pooler,
 * since Supabase MCP access in this environment covers a different account's
 * projects and this one wasn't reachable that way. This migration exists so
 * the schema is reproducible from git, not because it still needs to be run
 * against production.
 *
 * Phases 8-10 (roles, self-service guardrail triggers, admin_action_logs)
 * already have their own migration files and are NOT repeated here -- this
 * file is dated before them intentionally, matching the real build order.
 *
 * DO NOT run `up()` against the existing production database -- every table
 * here already exists there. Migration tracking lives in the app's *default*
 * connection (sqlite locally, per DB_CONNECTION), not in Postgres itself --
 * so on any environment that already has this schema (i.e. this repo's own
 * local dev setup, already pointed at the real project), mark it as applied
 * by inserting straight into that local tracking table:
 *
 *   insert into migrations (migration, batch)
 *   values ('2026_08_12_000000_create_platform_foundation_schema', 2);
 *
 * (Batch 2 to match the already-recorded roles/guardrails/admin_action_logs
 * migrations, since all four were live together.) This file's `up()` is for
 * provisioning a genuinely fresh environment instead -- a new client deploy,
 * a disaster-recovery restore, or a new Supabase project seeded from scratch
 * -- where none of this exists yet and `up()` is what actually creates it.
 *
 * Runs against the `pgsql` connection, same as the Phase 8-10 migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('companies', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->text('name');
            $table->text('code')->unique();
            $table->text('logo_url')->nullable();
            $table->text('status')->default('active');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
        });

        Schema::connection('pgsql')->create('users', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('auth_user_id')->unique();
            $table->text('name');
            $table->text('email')->unique();
            $table->text('role')->default('company_user');
            $table->text('status')->default('active');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
        });

        // auth_user_id references auth.users(id), a schema Laravel's Schema
        // builder can't target directly -- added as raw SQL.
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table users
              add constraint users_auth_user_id_fkey
              foreign key (auth_user_id) references auth.users(id) on delete cascade
        SQL);

        Schema::connection('pgsql')->create('departments', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->text('name');
            $table->text('code');
            $table->text('description')->nullable();
            $table->text('status')->default('active');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'code']);
            $table->index('company_id');
        });

        Schema::connection('pgsql')->create('company_users', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->uuid('user_id');
            $table->text('role');
            $table->text('status')->default('active');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['company_id', 'user_id']);
            $table->index('company_id');
            $table->index('user_id');
        });

        Schema::connection('pgsql')->create('department_users', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('department_id');
            $table->uuid('user_id');
            $table->text('role')->default('department_user');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['department_id', 'user_id']);
            $table->index('department_id');
            $table->index('user_id');
        });

        Schema::connection('pgsql')->create('kpi_categories', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->text('name');
            $table->text('description')->nullable();
            $table->text('status')->default('active');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'name']);
            $table->index('company_id');
        });

        Schema::connection('pgsql')->create('kpis', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->uuid('category_id')->nullable();
            $table->text('name');
            $table->text('description')->nullable();
            $table->decimal('target')->nullable();
            $table->text('unit')->nullable();
            $table->text('frequency')->default('monthly');
            $table->text('status')->default('active');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('kpi_categories')->onDelete('set null');
            $table->index('company_id');
            $table->index('category_id');
        });

        Schema::connection('pgsql')->create('kpi_submissions', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->uuid('department_id');
            $table->uuid('kpi_id');
            $table->decimal('value');
            $table->date('submission_date')->default(DB::raw('CURRENT_DATE'));
            $table->uuid('submitted_by');
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('kpi_id')->references('id')->on('kpis')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users');
            $table->index('company_id');
            $table->index('department_id');
            $table->index('kpi_id');
            $table->index('submission_date');
        });

        // Per-company audit trail (distinct from admin_action_logs, which is
        // Richworks-Super-Admin cross-company support actions only). No
        // application code writes to this yet -- see the migration's sibling
        // note in the platform schema reference doc.
        Schema::connection('pgsql')->create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->text('action');
            $table->text('entity_type');
            $table->uuid('entity_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['company_id', 'created_at']);
            $table->index('user_id');
        });

        Schema::connection('pgsql')->create('notifications', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->uuid('user_id');
            $table->text('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
        });

        // Not yet written to by any controller -- schema was built ahead of
        // a reporting feature that doesn't exist yet.
        Schema::connection('pgsql')->create('reports', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->uuid('department_id')->nullable();
            $table->text('report_type');
            $table->text('period');
            $table->uuid('generated_by');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users');
            $table->index('company_id');
            $table->index('department_id');
        });

        $this->createHelperFunctions();
        $this->enableRlsAndPolicies();
    }

    private function createHelperFunctions(): void
    {
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_current_user_id()
            returns uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select id from users where auth_user_id = auth.uid() limit 1;
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_is_richworks_super_admin()
            returns boolean
            language sql stable security definer
            set search_path to 'public'
            as $$
                select exists (
                    select 1 from users
                    where auth_user_id = auth.uid() and role = 'richworks_super_admin' and status = 'active'
                );
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_company_ids()
            returns setof uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select cu.company_id from company_users cu
                join users u on u.id = cu.user_id
                where u.auth_user_id = auth.uid() and cu.status = 'active';
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_role_in_company(c_id uuid)
            returns text
            language sql stable security definer
            set search_path to 'public'
            as $$
                select cu.role from company_users cu
                join users u on u.id = cu.user_id
                where u.auth_user_id = auth.uid() and cu.company_id = c_id and cu.status = 'active'
                limit 1;
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_department_ids()
            returns setof uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select du.department_id from department_users du
                join users u on u.id = du.user_id
                where u.auth_user_id = auth.uid();
            $$
        SQL);
    }

    private function enableRlsAndPolicies(): void
    {
        $tables = ['companies', 'users', 'departments', 'company_users', 'department_users', 'kpi_categories', 'kpis', 'kpi_submissions', 'audit_logs', 'notifications', 'reports'];

        foreach ($tables as $table) {
            DB::connection('pgsql')->statement("alter table {$table} enable row level security");
        }

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy companies_select on companies for select
              using (auth_is_richworks_super_admin() or id in (select auth_company_ids()))
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy companies_write on companies for all
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin())
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_select on users for select
              using (
                auth_is_richworks_super_admin()
                or auth_user_id = auth.uid()
                or exists (
                  select 1 from company_users cu_self
                  join company_users cu_target on cu_target.company_id = cu_self.company_id
                  where cu_self.user_id = auth_current_user_id()
                    and cu_target.user_id = users.id
                    and cu_self.role = 'company_admin'
                )
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_update_admin on users for update
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin())
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_update_self on users for update
              using (auth_user_id = auth.uid())
              with check (auth_user_id = auth.uid() and role = (select u2.role from users u2 where u2.id = users.id))
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy departments_select on departments for select
              using (auth_is_richworks_super_admin() or company_id in (select auth_company_ids()))
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy departments_insert on departments for insert
              with check (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy departments_update on departments for update
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
              with check (company_id = (select h.company_id from departments h where h.id = departments.id))
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy company_users_select on company_users for select
              using (
                auth_is_richworks_super_admin()
                or user_id = auth_current_user_id()
                or company_id in (select auth_company_ids())
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy company_users_insert on company_users for insert
              with check (
                auth_is_richworks_super_admin()
                or (auth_role_in_company(company_id) = 'company_admin' and role in ('department_admin', 'department_user'))
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy company_users_update on company_users for update
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
              with check (
                auth_is_richworks_super_admin()
                or (auth_role_in_company(company_id) = 'company_admin' and role in ('department_admin', 'department_user'))
              )
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_select on department_users for select
              using (
                auth_is_richworks_super_admin()
                or user_id = auth_current_user_id()
                or department_id in (select auth_department_ids())
                or exists (
                  select 1 from departments d
                  where d.id = department_users.department_id
                    and auth_role_in_company(d.company_id) in ('company_admin', 'department_admin')
                )
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_insert on department_users for insert
              with check (
                exists (
                  select 1 from departments d
                  where d.id = department_users.department_id
                    and (auth_is_richworks_super_admin() or auth_role_in_company(d.company_id) in ('company_admin', 'department_admin'))
                )
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_update on department_users for update
              using (
                exists (
                  select 1 from departments d
                  where d.id = department_users.department_id
                    and (auth_is_richworks_super_admin() or auth_role_in_company(d.company_id) in ('company_admin', 'department_admin'))
                )
              )
              with check (department_id = (select du2.department_id from department_users du2 where du2.id = department_users.id))
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_categories_select on kpi_categories for select
              using (auth_is_richworks_super_admin() or company_id in (select auth_company_ids()))
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_categories_insert on kpi_categories for insert
              with check (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_categories_update on kpi_categories for update
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
              with check (company_id = (select kc2.company_id from kpi_categories kc2 where kc2.id = kpi_categories.id))
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpis_select on kpis for select
              using (auth_is_richworks_super_admin() or company_id in (select auth_company_ids()))
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpis_insert on kpis for insert
              with check (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpis_update on kpis for update
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
              with check (company_id = (select k2.company_id from kpis k2 where k2.id = kpis.id))
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_submissions_select on kpi_submissions for select
              using (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
                or department_id in (select auth_department_ids())
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_submissions_insert on kpi_submissions for insert
              with check (
                department_id in (select auth_department_ids())
                and submitted_by = auth_current_user_id()
                and company_id = (select departments.company_id from departments where departments.id = kpi_submissions.department_id)
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_submissions_update on kpi_submissions for update
              using (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
                or (department_id in (select auth_department_ids()) and submitted_by = auth_current_user_id())
              )
              with check (
                company_id = (select ks2.company_id from kpi_submissions ks2 where ks2.id = kpi_submissions.id)
                and department_id = (select ks2.department_id from kpi_submissions ks2 where ks2.id = kpi_submissions.id)
                and kpi_id = (select ks2.kpi_id from kpi_submissions ks2 where ks2.id = kpi_submissions.id)
              )
        SQL);

        // Select-only by design -- no insert policy exists yet since nothing
        // writes to this table today (see the schema reference doc).
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy audit_logs_select on audit_logs for select
              using (auth_is_richworks_super_admin() or (company_id is not null and auth_role_in_company(company_id) = 'company_admin'))
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy notifications_select on notifications for select
              using (user_id = auth_current_user_id())
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy notifications_update on notifications for update
              using (user_id = auth_current_user_id())
              with check (user_id = auth_current_user_id())
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy reports_select on reports for select
              using (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
                or (department_id is not null and department_id in (select auth_department_ids()))
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy reports_insert on reports for insert
              with check (
                generated_by = auth_current_user_id()
                and (
                  auth_is_richworks_super_admin()
                  or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
                  or (department_id is not null and department_id in (select auth_department_ids()))
                )
              )
        SQL);
    }

    public function down(): void
    {
        foreach (['reports', 'notifications', 'audit_logs', 'kpi_submissions', 'kpis', 'kpi_categories', 'department_users', 'company_users', 'departments'] as $table) {
            Schema::connection('pgsql')->dropIfExists($table);
        }

        DB::connection('pgsql')->statement('drop function if exists auth_department_ids()');
        DB::connection('pgsql')->statement('drop function if exists auth_role_in_company(uuid)');
        DB::connection('pgsql')->statement('drop function if exists auth_company_ids()');
        DB::connection('pgsql')->statement('drop function if exists auth_is_richworks_super_admin()');
        DB::connection('pgsql')->statement('drop function if exists auth_current_user_id()');

        Schema::connection('pgsql')->dropIfExists('users');
        Schema::connection('pgsql')->dropIfExists('companies');
    }
};
