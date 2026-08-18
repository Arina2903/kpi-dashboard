<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Makes tenant isolation a structural rule rather than a convention.
 *
 * The rule: Platform -> Company -> Users -> KPI -> Targets/Results. Every
 * company-owned table carries its own `company_id`, and Postgres RLS -- not
 * application code -- is the final authority on who may read or write it.
 * Application-level `where company_id = ?` filters stay, but only as a
 * performance optimisation; removing one must never widen what a caller can
 * see.
 *
 * Two tables broke that rule. `department_users` and `roles` are unambiguously
 * company-owned data, but neither carried a `company_id` -- their policies
 * reached the tenant boundary by joining through `departments` on every check.
 * That worked, but it made the boundary an emergent property of a join rather
 * than a column anyone can see, audit, or index, and it meant those two tables
 * were the only ones in the schema where "is this row tenant-scoped?" couldn't
 * be answered by looking at the row.
 *
 * This migration adds the column, backfills it, and rewrites both tables'
 * policies to scope on it directly through `auth_company_ids()` -- the same
 * helper every other table already routes through, which also means both
 * tables now pick up the company-suspension enforcement added in
 * 2026_08_14_060000 that the hand-rolled joins partially sidestepped.
 *
 * `company_id` is *derived*, never trusted. A BEFORE INSERT/UPDATE trigger
 * overwrites whatever the client sent with the parent department's real
 * company, so a mismatched value isn't rejected -- it's impossible. A second
 * trigger freezes it afterwards, matching the pattern established in
 * 2026_08_14_080000 for the tables that already had the column.
 *
 * Deliberately NOT in scope here:
 *   - `users`, `kpi_templates`, `kpi_template_items` -- genuinely not
 *     company-owned (a global identity table and a shared, Center-curated
 *     library with no tenant data in it). Giving these a `company_id` would
 *     be cargo-culting the rule, not applying it.
 *   - `admin_action_logs` -- Center-only cross-company audit trail; scoping
 *     it to one company would defeat its purpose. It already has
 *     `target_company_id`, which is a different thing.
 *   - DELETE policies -- still absent platform-wide, so deletion is denied by
 *     default even to the owning Company Admin. That's a safe default but an
 *     open product decision (soft-delete via `status` vs. real policies), not
 *     something to settle inside an isolation migration.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addCompanyIdToDepartmentUsers();
        $this->addCompanyIdToRoles();
        $this->deriveAndFreezeCompanyId();
        $this->rewriteDepartmentUsersPolicies();
        $this->rewriteRolesPolicies();
        $this->scopeNotificationsToCompany();
    }

    /**
     * Nullable -> backfill -> NOT NULL, so this is safe on a table that
     * already has rows in production.
     */
    private function addCompanyIdToDepartmentUsers(): void
    {
        DB::connection('pgsql')->statement('alter table department_users add column if not exists company_id uuid');

        DB::connection('pgsql')->statement(<<<'SQL'
            update department_users du
              set company_id = d.company_id
              from departments d
              where d.id = du.department_id
                and du.company_id is distinct from d.company_id
        SQL);

        DB::connection('pgsql')->statement('alter table department_users alter column company_id set not null');

        DB::connection('pgsql')->statement(<<<'SQL'
            alter table department_users
              drop constraint if exists department_users_company_id_fkey
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table department_users
              add constraint department_users_company_id_fkey
              foreign key (company_id) references companies(id) on delete cascade
        SQL);

        DB::connection('pgsql')->statement(
            'create index if not exists department_users_company_id_index on department_users (company_id)'
        );
    }

    private function addCompanyIdToRoles(): void
    {
        DB::connection('pgsql')->statement('alter table roles add column if not exists company_id uuid');

        DB::connection('pgsql')->statement(<<<'SQL'
            update roles r
              set company_id = d.company_id
              from departments d
              where d.id = r.department_id
                and r.company_id is distinct from d.company_id
        SQL);

        DB::connection('pgsql')->statement('alter table roles alter column company_id set not null');

        DB::connection('pgsql')->statement('alter table roles drop constraint if exists roles_company_id_fkey');
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table roles
              add constraint roles_company_id_fkey
              foreign key (company_id) references companies(id) on delete cascade
        SQL);

        DB::connection('pgsql')->statement(
            'create index if not exists roles_company_id_index on roles (company_id)'
        );
    }

    /**
     * Derive-then-freeze. The derive trigger runs BEFORE INSERT OR UPDATE and
     * ignores whatever `company_id` the client supplied, replacing it with the
     * parent department's own. Because it is a BEFORE trigger it also fires
     * ahead of the policy's WITH CHECK, so a caller who tries to smuggle in a
     * foreign `company_id` fails the check on the *corrected* value -- and on
     * the uncorrected one too, if the ordering ever changed. There is no
     * ordering in which a mismatched row survives.
     *
     * `prevent_company_id_change()` already exists from 2026_08_14_080000 and
     * is reused verbatim rather than redefined.
     */
    private function deriveAndFreezeCompanyId(): void
    {
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function derive_company_id_from_department()
            returns trigger language plpgsql security definer
            set search_path to 'public' as $$
            declare
              parent_company uuid;
            begin
              select d.company_id into parent_company
                from departments d where d.id = NEW.department_id;

              if parent_company is null then
                raise exception 'department % does not exist; cannot derive company_id.', NEW.department_id;
              end if;

              NEW.company_id := parent_company;
              return NEW;
            end;
            $$
        SQL);

        foreach (['department_users', 'roles'] as $table) {
            DB::connection('pgsql')->statement("drop trigger if exists trg_derive_company_id on {$table}");
            DB::connection('pgsql')->statement(<<<SQL
                create trigger trg_derive_company_id
                  before insert or update on {$table}
                  for each row execute function derive_company_id_from_department()
            SQL);
        }

        // `department_users.department_id` is already frozen by
        // trg_prevent_department_users_reassignment (2026_08_14_080000).
        // `roles` had no equivalent, so a role could previously be moved to
        // another department -- and now, transitively, to another company.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_roles_reassignment()
            returns trigger language plpgsql as $$
            begin
              if NEW.department_id <> OLD.department_id then
                raise exception 'department_id cannot be changed on an existing role — delete and recreate instead.';
              end if;
              return NEW;
            end;
            $$
        SQL);

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_roles_reassignment on roles');
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_roles_reassignment
              before update on roles
              for each row execute function prevent_roles_reassignment()
        SQL);

        // Trigger name order matters: Postgres fires BEFORE triggers
        // alphabetically, so trg_derive_company_id runs before
        // trg_prevent_company_id_change and the freeze compares the derived
        // value, not the client's.
        foreach (['department_users', 'roles'] as $table) {
            DB::connection('pgsql')->statement("drop trigger if exists trg_prevent_company_id_change on {$table}");
            DB::connection('pgsql')->statement(<<<SQL
                create trigger trg_prevent_company_id_change
                  before update on {$table}
                  for each row execute function prevent_company_id_change()
            SQL);
        }
    }

    /**
     * Same access semantics as before -- Super Admin, the member themselves,
     * anyone in the same department, or a company/department admin of the
     * owning company -- expressed against the row's own `company_id` instead
     * of a correlated subquery into `departments`.
     */
    private function rewriteDepartmentUsersPolicies(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists department_users_select on department_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_select on department_users for select
              using (
                auth_is_richworks_super_admin()
                or user_id = auth_current_user_id()
                or department_id in (select auth_department_ids())
                or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists department_users_insert on department_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_insert on department_users for insert
              with check (
                (
                  auth_is_richworks_super_admin()
                  or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
                )
                and company_id = (
                  select d.company_id from departments d where d.id = department_users.department_id
                )
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists department_users_update on department_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_update on department_users for update
              using (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
              )
              with check (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) in ('company_admin', 'department_admin')
              )
        SQL);
    }

    private function rewriteRolesPolicies(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists roles_select on roles');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy roles_select on roles for select
              using (
                auth_is_richworks_super_admin()
                or company_id in (select auth_company_ids())
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists roles_write on roles');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy roles_write on roles for all
              using (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) = 'company_admin'
              )
              with check (
                auth_is_richworks_super_admin()
                or auth_role_in_company(company_id) = 'company_admin'
              )
        SQL);
    }

    /**
     * `notifications` already carried `company_id`, but neither of its
     * policies ever referenced it -- both scoped on `user_id` alone. That is
     * strictly narrower than the tenant boundary so it never leaked, but it
     * meant the one table where `company_id` existed and went unused was also
     * the one table that never picked up suspension enforcement. Now a
     * suspended company's notifications go dark with the rest of its data.
     */
    private function scopeNotificationsToCompany(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists notifications_select on notifications');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy notifications_select on notifications for select
              using (
                user_id = auth_current_user_id()
                and company_id in (select auth_company_ids())
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists notifications_update on notifications');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy notifications_update on notifications for update
              using (
                user_id = auth_current_user_id()
                and company_id in (select auth_company_ids())
              )
              with check (
                user_id = auth_current_user_id()
                and company_id in (select auth_company_ids())
              )
        SQL);
    }

    public function down(): void
    {
        // Restore the join-based policies from the foundational schema.
        DB::connection('pgsql')->statement('drop policy if exists notifications_update on notifications');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy notifications_update on notifications for update
              using (user_id = auth_current_user_id())
              with check (user_id = auth_current_user_id())
        SQL);
        DB::connection('pgsql')->statement('drop policy if exists notifications_select on notifications');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy notifications_select on notifications for select
              using (user_id = auth_current_user_id())
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists roles_write on roles');
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
        DB::connection('pgsql')->statement('drop policy if exists roles_select on roles');
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
        SQL);
        DB::connection('pgsql')->statement('drop policy if exists department_users_insert on department_users');
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
        DB::connection('pgsql')->statement('drop policy if exists department_users_select on department_users');
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

        foreach (['department_users', 'roles'] as $table) {
            DB::connection('pgsql')->statement("drop trigger if exists trg_derive_company_id on {$table}");
        }
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_company_id_change on roles');
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_roles_reassignment on roles');
        DB::connection('pgsql')->statement('drop function if exists prevent_roles_reassignment()');
        DB::connection('pgsql')->statement('drop function if exists derive_company_id_from_department()');

        // department_users' own trg_prevent_company_id_change is dropped only
        // because this migration created it -- 2026_08_14_080000 applied that
        // trigger to kpis/kpi_categories/departments only.
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_company_id_change on department_users');

        DB::connection('pgsql')->statement('drop index if exists roles_company_id_index');
        DB::connection('pgsql')->statement('alter table roles drop constraint if exists roles_company_id_fkey');
        DB::connection('pgsql')->statement('alter table roles drop column if exists company_id');

        DB::connection('pgsql')->statement('drop index if exists department_users_company_id_index');
        DB::connection('pgsql')->statement('alter table department_users drop constraint if exists department_users_company_id_fkey');
        DB::connection('pgsql')->statement('alter table department_users drop column if exists company_id');
    }
};
