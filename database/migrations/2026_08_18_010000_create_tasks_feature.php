<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a Tasks feature to the Performix Platform: a task can optionally be
 * linked to one or more KPIs for visibility/alignment (never touches a KPI's
 * actual value — mirrors the read-only "alignment" concept the legacy
 * Telegram Mini App already has via `telegram_project_task_kpi_links`, but
 * this is a brand-new, independent implementation built for the Platform's
 * company_id + RLS tenancy model, not a port of that table).
 *
 * `tasks` has no parent row to derive `company_id` from at creation (same
 * situation as `kpis`/`departments`) — the controller sets it from the route
 * parameter, and `tasks_insert`'s `with check` is the real guarantee, not
 * client trust. `task_kpi_links` *can* derive `company_id` from its parent
 * task, so it follows the `kpi_access_grants` pattern exactly: a
 * derive-then-freeze trigger pair plus an AFTER INSERT/UPDATE tenancy guard
 * rejecting a link to a KPI in a different company.
 *
 * Both tables are granted to `authenticated` in this same migration —
 * `2026_08_17_150000_grant_authenticated_role_on_missing_tables.php` fixed a
 * real bug where six tables had correct RLS but no table-level GRANT
 * (Postgres checks GRANTs before RLS ever runs), leaving them silently
 * non-functional. Not repeating that here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('tasks', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->text('title');
            $table->text('description')->nullable();
            $table->text('status')->default('open');
            $table->text('priority')->default('medium');
            $table->date('due_date')->nullable();
            $table->uuid('assignee_user_id')->nullable();
            $table->uuid('created_by');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('assignee_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index('company_id');
            $table->index('assignee_user_id');
            $table->index('status');
        });

        DB::connection('pgsql')->statement(<<<'SQL'
            alter table tasks add constraint tasks_status_check
              check (status in ('open', 'in_progress', 'done', 'cancelled'))
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table tasks add constraint tasks_priority_check
              check (priority in ('low', 'medium', 'high'))
        SQL);

        DB::connection('pgsql')->statement('grant select, insert, update, delete on public.tasks to authenticated');

        // Reuses the shared prevent_company_id_change() (defined once in
        // 2026_08_14_080000_restore_immutability_after_recursion_fix.php) —
        // not redefined here.
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_company_id_change
              before update on tasks
              for each row execute function prevent_company_id_change()
        SQL);

        DB::connection('pgsql')->statement('alter table tasks enable row level security');

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy tasks_select on tasks for select
              using (
                auth_is_richworks_super_admin()
                or auth_can_view_company_wide(company_id)
                or created_by = auth_current_user_id()
                or assignee_user_id = auth_current_user_id()
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy tasks_insert on tasks for insert
              with check (
                auth_can_administer_company(company_id)
                or auth_role_in_company(company_id) in ('slt', 'executive', 'employee')
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy tasks_update on tasks for update
              using (
                auth_can_administer_company(company_id)
                or created_by = auth_current_user_id()
                or assignee_user_id = auth_current_user_id()
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy tasks_delete on tasks for delete
              using (
                auth_can_administer_company(company_id)
                or created_by = auth_current_user_id()
              )
        SQL);

        // --- task_kpi_links --------------------------------------------
        Schema::connection('pgsql')->create('task_kpi_links', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->uuid('task_id');
            $table->uuid('kpi_id');
            $table->uuid('linked_by');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('kpi_id')->references('id')->on('kpis')->onDelete('cascade');
            $table->foreign('linked_by')->references('id')->on('users');
            $table->unique(['task_id', 'kpi_id']);
            $table->index('company_id');
            $table->index('task_id');
            $table->index('kpi_id');
        });

        DB::connection('pgsql')->statement('grant select, insert, update, delete on public.task_kpi_links to authenticated');

        // Core Platform Rule: company_id is derived from the parent task,
        // never trusted from the client, then frozen. Mirrors
        // derive_company_id_from_kpi() in
        // 2026_08_17_110000_separate_platform_and_company_roles.php.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function derive_company_id_from_task()
            returns trigger language plpgsql security definer
            set search_path to 'public' as $$
            declare
              parent_company uuid;
            begin
              select t.company_id into parent_company from tasks t where t.id = NEW.task_id;

              if parent_company is null then
                raise exception 'task % does not exist; cannot derive company_id.', NEW.task_id;
              end if;

              NEW.company_id := parent_company;
              return NEW;
            end;
            $$
        SQL);

        // Alphabetical firing order: trg_derive_* runs before trg_prevent_*.
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_derive_company_id
              before insert or update on task_kpi_links
              for each row execute function derive_company_id_from_task()
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_company_id_change
              before update on task_kpi_links
              for each row execute function prevent_company_id_change()
        SQL);

        // A link must not cross a tenant boundary: linking a task to a KPI
        // owned by a different company is exactly the leak the derive
        // trigger above doesn't by itself prevent (it trusts task_id, not
        // kpi_id). Not expressible as a column constraint since it spans
        // two tables.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function validate_task_kpi_link_tenancy()
            returns trigger language plpgsql security definer
            set search_path to 'public' as $$
            begin
              if not exists (
                select 1 from kpis k where k.id = NEW.kpi_id and k.company_id = NEW.company_id
              ) then
                raise exception 'cannot link a task to a KPI in another company.';
              end if;

              return NEW;
            end;
            $$
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_validate_task_kpi_link_tenancy
              after insert or update on task_kpi_links
              for each row execute function validate_task_kpi_link_tenancy()
        SQL);

        DB::connection('pgsql')->statement('alter table task_kpi_links enable row level security');

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy task_kpi_links_select on task_kpi_links for select
              using (
                auth_is_richworks_super_admin()
                or exists (
                  select 1 from tasks t
                  where t.id = task_kpi_links.task_id
                    and (
                      auth_can_view_company_wide(t.company_id)
                      or t.created_by = auth_current_user_id()
                      or t.assignee_user_id = auth_current_user_id()
                    )
                )
              )
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy task_kpi_links_write on task_kpi_links for all
              using (
                auth_can_administer_company(company_id)
                or exists (
                  select 1 from tasks t
                  where t.id = task_kpi_links.task_id
                    and (t.created_by = auth_current_user_id() or t.assignee_user_id = auth_current_user_id())
                )
              )
              with check (
                auth_can_administer_company(company_id)
                or exists (
                  select 1 from tasks t
                  where t.id = task_kpi_links.task_id
                    and (t.created_by = auth_current_user_id() or t.assignee_user_id = auth_current_user_id())
                )
              )
        SQL);
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('task_kpi_links');
        DB::connection('pgsql')->statement('drop function if exists validate_task_kpi_link_tenancy()');
        DB::connection('pgsql')->statement('drop function if exists derive_company_id_from_task()');

        Schema::connection('pgsql')->dropIfExists('tasks');
    }
};
