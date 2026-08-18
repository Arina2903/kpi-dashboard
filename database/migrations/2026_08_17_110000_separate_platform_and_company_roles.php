<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the single "are you the Center or not?" boolean into two independent
 * axes -- a PLATFORM tier and a COMPANY tier -- and adds per-KPI visibility so
 * "permitted KPI data" means something enforceable rather than aspirational.
 *
 *   PLATFORM TIER (users.role)          scope
 *   ----------------------------------  --------------------------------------
 *   richworks_super_admin               every company, unconditionally
 *   platform_admin                      ONLY companies explicitly assigned via
 *                                       platform_admin_assignments
 *   member                              no platform-wide powers at all
 *
 *   COMPANY TIER (company_users.role)   scope within that one company
 *   ----------------------------------  --------------------------------------
 *   company_admin                       full administration of own company
 *   slt                                 company-wide KPI visibility, read-only,
 *                                       minus anything marked `restricted`
 *   executive                           own department's permitted KPI data
 *   employee                            own submissions + own department's
 *                                       permitted KPI data
 *
 * Why `richworks_super_admin` keeps its name: it is already the stored value
 * in production and is referenced by `auth_is_richworks_super_admin()`, which
 * roughly thirty policies call. Renaming it to `platform_super_admin` would
 * mean rewriting every one of those for a purely cosmetic gain, and a
 * half-finished rename of an authorisation predicate is exactly the kind of
 * change that fails open. It IS the Platform Super Admin tier; only the string
 * differs.
 *
 * `department_admin`/`department_user` ARE renamed (to `executive`/`employee`)
 * because leaving two naming systems in one role model is how authorisation
 * bugs get written -- and unlike the super-admin predicate, these are matched
 * as plain strings in a countable number of places, all updated alongside this
 * migration. Existing rows are migrated in place; no row changes meaning.
 *
 * PER-KPI VISIBILITY. `kpis.visibility` is the default reach of a KPI:
 *   'company'    -- any member of the company may read it (the default, and
 *                   what every existing KPI is backfilled to, so this
 *                   migration does not silently narrow live access)
 *   'department' -- only the departments that submit against it, plus admins
 *   'restricted' -- nobody by default; requires an explicit grant
 * `kpi_access_grants` then widens that for a specific user or department.
 * SLT sees 'company' and 'department' KPIs across the whole company but needs
 * a grant for 'restricted' ones -- which is precisely "own company + permitted
 * KPI visibility".
 *
 * Both new tables follow the Core Platform Rule from 2026_08_17_100000:
 * `company_id NOT NULL`, FK'd, indexed, and frozen by trigger.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Order matters and is circular if you get it wrong:
        // `auth_platform_company_ids()` reads platform_admin_assignments and
        // `auth_can_view_kpi()` reads kpi_access_grants, so both tables must
        // exist before the helpers are defined -- while every new policy needs
        // the helpers to already exist. Tables first, then helpers, then all
        // policies last.
        $this->splitPlatformTier();
        $this->createPlatformAdminAssignments();
        $this->renameCompanyTierRoles();
        $this->addKpiVisibility();
        $this->createKpiAccessGrants();
        $this->replaceHelperFunctions();
        $this->rewritePolicies();
    }

    // ------------------------------------------------------------------
    // Platform tier
    // ------------------------------------------------------------------

    private function splitPlatformTier(): void
    {
        // The old constraint must go BEFORE the rewrite below -- production
        // already has a `users_role_check` restricting role to whatever the
        // foundational migration defined (`richworks_super_admin`,
        // `company_user`), and 'member' isn't a member of that set yet. Doing
        // the UPDATE first (as an earlier version of this migration did) trips
        // that still-live constraint on its very first statement.
        DB::connection('pgsql')->statement('alter table users drop constraint if exists users_role_check');

        // `company_user` was the schema default and nothing ever checked for
        // it -- documented in CLAUDE.md's "known dormant issues". Renaming it
        // to `member` makes the absence of platform powers explicit instead of
        // implied by a value that reads like it grants something.
        DB::connection('pgsql')->statement(<<<'SQL'
            update users set role = 'member'
              where role is null or role not in ('richworks_super_admin', 'platform_admin')
        SQL);

        DB::connection('pgsql')->statement('alter table users alter column role set default \'member\'');

        DB::connection('pgsql')->statement(<<<'SQL'
            alter table users add constraint users_role_check
              check (role in ('richworks_super_admin', 'platform_admin', 'member'))
        SQL);
    }

    /**
     * A Platform Admin has no reach of their own -- every company they can
     * touch is an explicit row here. That is the entire difference between
     * them and a Super Admin, so it is modelled as data rather than as a
     * second boolean.
     */
    private function createPlatformAdminAssignments(): void
    {
        Schema::connection('pgsql')->create('platform_admin_assignments', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('user_id');
            $table->uuid('company_id');
            $table->uuid('granted_by');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users');
            $table->unique(['user_id', 'company_id']);
            $table->index('user_id');
            $table->index('company_id');
        });

        DB::connection('pgsql')->statement('alter table platform_admin_assignments enable row level security');

        // Reassigning an existing grant to another company would move someone
        // across the boundary without an audit row; revoke and re-grant.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_assignment_reassignment()
            returns trigger language plpgsql as $$
            begin
              if NEW.company_id <> OLD.company_id or NEW.user_id <> OLD.user_id then
                raise exception 'an assignment cannot be repointed — revoke it and grant a new one.';
              end if;
              return NEW;
            end;
            $$
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_company_id_change
              before update on platform_admin_assignments
              for each row execute function prevent_assignment_reassignment()
        SQL);

        // Only the Center grants platform access; a Platform Admin may read
        // their own assignments (they need to know their own scope) but can
        // never widen it.
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy platform_admin_assignments_select on platform_admin_assignments for select
              using (auth_is_richworks_super_admin() or user_id = auth_current_user_id())
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy platform_admin_assignments_write on platform_admin_assignments for all
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin() and granted_by = auth_current_user_id())
        SQL);
    }

    // ------------------------------------------------------------------
    // Company tier
    // ------------------------------------------------------------------

    private function renameCompanyTierRoles(): void
    {
        // Same ordering fix as splitPlatformTier(): drop both live
        // constraints before touching any rows, or the rename UPDATEs below
        // trip the still-live 'department_admin'/'department_user' check.
        DB::connection('pgsql')->statement('alter table company_users drop constraint if exists company_users_role_check');
        DB::connection('pgsql')->statement('alter table department_users drop constraint if exists department_users_role_check');

        foreach (['company_users', 'department_users'] as $table) {
            DB::connection('pgsql')->statement("update {$table} set role = 'executive' where role = 'department_admin'");
            DB::connection('pgsql')->statement("update {$table} set role = 'employee' where role = 'department_user'");
        }

        DB::connection('pgsql')->statement('alter table department_users alter column role set default \'employee\'');

        DB::connection('pgsql')->statement(<<<'SQL'
            alter table company_users add constraint company_users_role_check
              check (role in ('company_admin', 'slt', 'executive', 'employee'))
        SQL);

        // `slt` is a company-wide tier and meaningless at department level --
        // someone with company-wide visibility does not also need a per-
        // department row to express it.
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table department_users add constraint department_users_role_check
              check (role in ('executive', 'employee'))
        SQL);
    }

    // ------------------------------------------------------------------
    // Per-KPI visibility
    // ------------------------------------------------------------------

    private function addKpiVisibility(): void
    {
        DB::connection('pgsql')->statement(
            "alter table kpis add column if not exists visibility text not null default 'company'"
        );

        // Everything that exists today is company-wide readable already, so
        // backfilling to 'company' preserves current behaviour exactly. This
        // migration widens nobody's access and narrows nobody's.
        DB::connection('pgsql')->statement("update kpis set visibility = 'company' where visibility is null");

        DB::connection('pgsql')->statement('alter table kpis drop constraint if exists kpis_visibility_check');
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table kpis add constraint kpis_visibility_check
              check (visibility in ('company', 'department', 'restricted'))
        SQL);
    }

    private function createKpiAccessGrants(): void
    {
        Schema::connection('pgsql')->create('kpi_access_grants', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->uuid('kpi_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('granted_by');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('kpi_id')->references('id')->on('kpis')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users');
            $table->index('company_id');
            $table->index('kpi_id');
            $table->index('user_id');
            $table->index('department_id');
        });

        // Exactly one grantee. A row with both set would be ambiguous; a row
        // with neither would grant to nobody while looking like it grants to
        // someone, which is worse.
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table kpi_access_grants add constraint kpi_access_grants_one_grantee_check
              check (num_nonnulls(user_id, department_id) = 1)
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create unique index kpi_access_grants_user_unique
              on kpi_access_grants (kpi_id, user_id) where user_id is not null
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create unique index kpi_access_grants_department_unique
              on kpi_access_grants (kpi_id, department_id) where department_id is not null
        SQL);

        // Core Platform Rule: company_id is derived from the parent KPI, never
        // trusted from the client, then frozen. Mirrors
        // derive_company_id_from_department() in 2026_08_17_100000.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function derive_company_id_from_kpi()
            returns trigger language plpgsql security definer
            set search_path to 'public' as $$
            declare
              parent_company uuid;
            begin
              select k.company_id into parent_company from kpis k where k.id = NEW.kpi_id;

              if parent_company is null then
                raise exception 'kpi % does not exist; cannot derive company_id.', NEW.kpi_id;
              end if;

              NEW.company_id := parent_company;
              return NEW;
            end;
            $$
        SQL);

        // Alphabetical firing order: trg_derive_* runs before trg_prevent_*.
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_derive_company_id
              before insert or update on kpi_access_grants
              for each row execute function derive_company_id_from_kpi()
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_company_id_change
              before update on kpi_access_grants
              for each row execute function prevent_company_id_change()
        SQL);

        // A grant must not cross a tenant boundary: granting a user from
        // company A visibility of company B's KPI is the exact leak this whole
        // model exists to prevent, and it is not expressible as a column
        // constraint because it spans three tables.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function validate_kpi_grant_tenancy()
            returns trigger language plpgsql security definer
            set search_path to 'public' as $$
            begin
              if NEW.department_id is not null
                 and not exists (
                   select 1 from departments d
                   where d.id = NEW.department_id and d.company_id = NEW.company_id
                 )
              then
                raise exception 'cannot grant KPI access to a department in another company.';
              end if;

              if NEW.user_id is not null
                 and not exists (
                   select 1 from company_users cu
                   where cu.user_id = NEW.user_id
                     and cu.company_id = NEW.company_id
                     and cu.status = 'active'
                 )
              then
                raise exception 'cannot grant KPI access to a user who is not an active member of this company.';
              end if;

              return NEW;
            end;
            $$
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_validate_kpi_grant_tenancy
              after insert or update on kpi_access_grants
              for each row execute function validate_kpi_grant_tenancy()
        SQL);

        // Policies for this table are created in rewritePolicies(), not here
        // -- they call auth_can_administer_company(), which cannot be defined
        // until this table exists (auth_can_view_kpi() reads it).
        DB::connection('pgsql')->statement('alter table kpi_access_grants enable row level security');
    }

    // ------------------------------------------------------------------
    // Helper functions
    // ------------------------------------------------------------------

    private function replaceHelperFunctions(): void
    {
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_platform_role()
            returns text
            language sql stable security definer
            set search_path to 'public'
            as $$
                select u.role from users u
                where u.auth_user_id = auth.uid() and u.status = 'active'
                limit 1;
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_is_platform_admin()
            returns boolean
            language sql stable security definer
            set search_path to 'public'
            as $$
                select coalesce(auth_platform_role() = 'platform_admin', false);
            $$
        SQL);

        // Companies a Platform Admin has been explicitly assigned. Suspension-
        // aware for the same reason every other helper is: a suspended company
        // goes dark for platform operators too, not just its own staff.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_platform_company_ids()
            returns setof uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select paa.company_id
                from platform_admin_assignments paa
                join users u on u.id = paa.user_id
                join companies c on c.id = paa.company_id
                where u.auth_user_id = auth.uid()
                  and u.status = 'active'
                  and u.role = 'platform_admin'
                  and c.status not in ('suspended', 'inactive');
            $$
        SQL);

        // Extends the Phase 5 definition: a company is "yours" either through
        // a company_users membership or through a platform assignment. Every
        // policy that already scopes on this picks up Platform Admins for free.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_company_ids()
            returns setof uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select cu.company_id from company_users cu
                join users u on u.id = cu.user_id
                join companies c on c.id = cu.company_id
                where u.auth_user_id = auth.uid() and cu.status = 'active'
                  and c.status not in ('suspended', 'inactive')
                union
                select auth_platform_company_ids();
            $$
        SQL);

        // The single predicate for "may administer this company". Replaces the
        // `auth_role_in_company(x) = 'company_admin'` string comparison that
        // was repeated across a dozen policies and which, by construction,
        // could never account for a platform tier.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_can_administer_company(c_id uuid)
            returns boolean
            language sql stable security definer
            set search_path to 'public'
            as $$
                select auth_is_richworks_super_admin()
                    or c_id in (select auth_platform_company_ids())
                    or auth_role_in_company(c_id) = 'company_admin';
            $$
        SQL);

        // Company-wide read without write: SLT, plus everyone who can already
        // administer.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_can_view_company_wide(c_id uuid)
            returns boolean
            language sql stable security definer
            set search_path to 'public'
            as $$
                select auth_can_administer_company(c_id)
                    or auth_role_in_company(c_id) = 'slt';
            $$
        SQL);

        /**
         * The one place "permitted KPI data" is decided. Every KPI-touching
         * policy delegates here so the rules cannot drift apart between
         * `kpis` and `kpi_submissions`.
         */
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_can_view_kpi(k_id uuid)
            returns boolean
            language sql stable security definer
            set search_path to 'public'
            as $$
                select exists (
                  select 1 from kpis k
                  where k.id = k_id
                    and (
                      -- Administrators of the owning company see everything in it.
                      auth_can_administer_company(k.company_id)

                      -- An explicit grant always wins, whatever the visibility.
                      or exists (
                        select 1 from kpi_access_grants g
                        where g.kpi_id = k.id
                          and (
                            g.user_id = auth_current_user_id()
                            or g.department_id in (select auth_department_ids())
                          )
                      )

                      -- SLT: company-wide, but 'restricted' still needs a grant.
                      or (
                        auth_role_in_company(k.company_id) = 'slt'
                        and k.visibility in ('company', 'department')
                      )

                      -- Executive / employee: company-visible KPIs, or
                      -- department-visible ones their own department submits
                      -- against.
                      or (
                        k.company_id in (select auth_company_ids())
                        and (
                          k.visibility = 'company'
                          or (
                            k.visibility = 'department'
                            and exists (
                              select 1 from kpi_submissions s
                              where s.kpi_id = k.id
                                and s.department_id in (select auth_department_ids())
                            )
                          )
                        )
                      )
                    )
                );
            $$
        SQL);
    }

    // ------------------------------------------------------------------
    // Policies
    // ------------------------------------------------------------------

    private function rewritePolicies(): void
    {
        // --- companies -------------------------------------------------
        // Creating and deleting companies stays Center-only; a Platform Admin
        // operates companies, it does not conjure them.
        DB::connection('pgsql')->statement('drop policy if exists companies_write on companies');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy companies_insert on companies for insert
              with check (auth_is_richworks_super_admin())
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy companies_update on companies for update
              using (auth_is_richworks_super_admin() or id in (select auth_platform_company_ids()))
              with check (auth_is_richworks_super_admin() or id in (select auth_platform_company_ids()))
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy companies_delete on companies for delete
              using (auth_is_richworks_super_admin())
        SQL);

        // --- users -----------------------------------------------------
        // Replaces the raw self-join from 2026_08_14_090000 with the shared
        // predicate, so Platform Admins see their assigned companies' users
        // and the suspension fix keeps holding.
        DB::connection('pgsql')->statement('drop policy if exists users_select on users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_select on users for select
              using (
                auth_is_richworks_super_admin()
                or auth_user_id = auth.uid()
                or exists (
                  select 1 from company_users cu
                  where cu.user_id = users.id
                    and auth_can_administer_company(cu.company_id)
                )
              )
        SQL);

        // A Platform Admin must not be able to promote themselves (or anyone
        // else) to Super Admin, so platform-tier writes stay Center-only.
        DB::connection('pgsql')->statement('drop policy if exists users_update_admin on users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_update_admin on users for update
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin())
        SQL);

        // --- departments / kpi_categories ------------------------------
        foreach (['departments', 'kpi_categories'] as $table) {
            DB::connection('pgsql')->statement("drop policy if exists {$table}_insert on {$table}");
            DB::connection('pgsql')->statement(<<<SQL
                create policy {$table}_insert on {$table} for insert
                  with check (auth_can_administer_company(company_id))
            SQL);

            DB::connection('pgsql')->statement("drop policy if exists {$table}_update on {$table}");
            DB::connection('pgsql')->statement(<<<SQL
                create policy {$table}_update on {$table} for update
                  using (auth_can_administer_company(company_id))
                  with check (auth_can_administer_company(company_id))
            SQL);
        }

        // --- company_users ---------------------------------------------
        // A company_admin may hand out slt/executive/employee but never
        // another company_admin -- that stays a Center/Platform-Admin action,
        // so a compromised company admin cannot mint peers.
        DB::connection('pgsql')->statement('drop policy if exists company_users_insert on company_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy company_users_insert on company_users for insert
              with check (
                auth_is_richworks_super_admin()
                or company_id in (select auth_platform_company_ids())
                or (
                  auth_role_in_company(company_id) = 'company_admin'
                  and role in ('slt', 'executive', 'employee')
                )
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists company_users_update on company_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy company_users_update on company_users for update
              using (auth_can_administer_company(company_id))
              with check (
                auth_is_richworks_super_admin()
                or company_id in (select auth_platform_company_ids())
                or (
                  auth_role_in_company(company_id) = 'company_admin'
                  and role in ('slt', 'executive', 'employee')
                )
              )
        SQL);

        // --- department_users ------------------------------------------
        // Narrower than before, deliberately. The old policy let any
        // `department_admin` read and write EVERY department membership in
        // the company, because it only checked the company-level role and
        // never which department the caller actually belongs to. Under the
        // new model an Executive is scoped to their own departments, which is
        // what "own permitted data" means; company-wide reach now requires
        // SLT (read) or Company Admin (write).
        DB::connection('pgsql')->statement('drop policy if exists department_users_select on department_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_select on department_users for select
              using (
                auth_is_richworks_super_admin()
                or user_id = auth_current_user_id()
                or department_id in (select auth_department_ids())
                or auth_can_view_company_wide(company_id)
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists department_users_insert on department_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy department_users_insert on department_users for insert
              with check (
                (
                  auth_can_administer_company(company_id)
                  or (
                    auth_role_in_company(company_id) = 'executive'
                    and department_id in (select auth_department_ids())
                  )
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
                auth_can_administer_company(company_id)
                or (
                  auth_role_in_company(company_id) = 'executive'
                  and department_id in (select auth_department_ids())
                )
              )
              with check (
                auth_can_administer_company(company_id)
                or (
                  auth_role_in_company(company_id) = 'executive'
                  and department_id in (select auth_department_ids())
                )
              )
        SQL);

        // --- kpi_access_grants -------------------------------------------
        DB::connection('pgsql')->statement('drop policy if exists kpi_access_grants_select on kpi_access_grants');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_access_grants_select on kpi_access_grants for select
              using (
                auth_is_richworks_super_admin()
                or user_id = auth_current_user_id()
                or department_id in (select auth_department_ids())
                or auth_can_administer_company(company_id)
              )
        SQL);
        DB::connection('pgsql')->statement('drop policy if exists kpi_access_grants_write on kpi_access_grants');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_access_grants_write on kpi_access_grants for all
              using (auth_can_administer_company(company_id))
              with check (auth_can_administer_company(company_id) and granted_by = auth_current_user_id())
        SQL);

        // --- roles -------------------------------------------------------
        DB::connection('pgsql')->statement('drop policy if exists roles_write on roles');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy roles_write on roles for all
              using (auth_can_administer_company(company_id))
              with check (auth_can_administer_company(company_id))
        SQL);

        // --- kpis --------------------------------------------------------
        // SELECT now routes entirely through auth_can_view_kpi(), which is
        // where the SLT / executive / employee distinction actually lives.
        DB::connection('pgsql')->statement('drop policy if exists kpis_select on kpis');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpis_select on kpis for select
              using (auth_is_richworks_super_admin() or auth_can_view_kpi(id))
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists kpis_insert on kpis');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpis_insert on kpis for insert
              with check (auth_can_administer_company(company_id))
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists kpis_update on kpis');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpis_update on kpis for update
              using (auth_can_administer_company(company_id))
              with check (auth_can_administer_company(company_id))
        SQL);

        // --- kpi_submissions ---------------------------------------------
        // A submission is readable only if the KPI behind it is. Without that
        // join, a 'restricted' KPI would leak its numbers through the
        // submissions table while the KPI row itself stayed hidden.
        DB::connection('pgsql')->statement('drop policy if exists kpi_submissions_select on kpi_submissions');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_submissions_select on kpi_submissions for select
              using (
                auth_is_richworks_super_admin()
                or (
                  auth_can_view_kpi(kpi_id)
                  and (
                    auth_can_view_company_wide(company_id)
                    or department_id in (select auth_department_ids())
                  )
                )
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists kpi_submissions_insert on kpi_submissions');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_submissions_insert on kpi_submissions for insert
              with check (
                department_id in (select auth_department_ids())
                and submitted_by = auth_current_user_id()
                and auth_can_view_kpi(kpi_id)
                and company_id = (select d.company_id from departments d where d.id = kpi_submissions.department_id)
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists kpi_submissions_update on kpi_submissions');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_submissions_update on kpi_submissions for update
              using (
                auth_is_richworks_super_admin()
                or auth_can_administer_company(company_id)
                or (
                  auth_role_in_company(company_id) = 'executive'
                  and department_id in (select auth_department_ids())
                )
                or (department_id in (select auth_department_ids()) and submitted_by = auth_current_user_id())
              )
              with check (
                auth_is_richworks_super_admin()
                or auth_can_administer_company(company_id)
                or (
                  auth_role_in_company(company_id) = 'executive'
                  and department_id in (select auth_department_ids())
                )
                or (department_id in (select auth_department_ids()) and submitted_by = auth_current_user_id())
              )
        SQL);

        // --- import_batches / audit_logs / reports -----------------------
        DB::connection('pgsql')->statement('drop policy if exists import_batches_select on import_batches');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy import_batches_select on import_batches for select
              using (auth_can_administer_company(company_id))
        SQL);
        DB::connection('pgsql')->statement('drop policy if exists import_batches_insert on import_batches');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy import_batches_insert on import_batches for insert
              with check (auth_can_administer_company(company_id) and uploaded_by = auth_current_user_id())
        SQL);
        DB::connection('pgsql')->statement('drop policy if exists import_batches_update on import_batches');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy import_batches_update on import_batches for update
              using (auth_can_administer_company(company_id))
              with check (auth_can_administer_company(company_id))
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists audit_logs_select on audit_logs');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy audit_logs_select on audit_logs for select
              using (
                auth_is_richworks_super_admin()
                or (company_id is not null and auth_can_administer_company(company_id))
              )
        SQL);

        DB::connection('pgsql')->statement('drop policy if exists reports_select on reports');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy reports_select on reports for select
              using (
                auth_can_view_company_wide(company_id)
                or (department_id is not null and department_id in (select auth_department_ids()))
              )
        SQL);
        DB::connection('pgsql')->statement('drop policy if exists reports_insert on reports');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy reports_insert on reports for insert
              with check (
                generated_by = auth_current_user_id()
                and (
                  auth_can_view_company_wide(company_id)
                  or (department_id is not null and department_id in (select auth_department_ids()))
                )
              )
        SQL);
    }

    // ------------------------------------------------------------------

    public function down(): void
    {
        // Policies that referenced the new helpers must go before the helpers
        // themselves can be dropped or reverted.
        foreach ([
            'companies_insert' => 'companies',
            'companies_update' => 'companies',
            'companies_delete' => 'companies',
            'kpi_access_grants_select' => 'kpi_access_grants',
            'kpi_access_grants_write' => 'kpi_access_grants',
        ] as $policy => $table) {
            DB::connection('pgsql')->statement("drop policy if exists {$policy} on {$table}");
        }

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy companies_write on companies for all
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin())
        SQL);

        Schema::connection('pgsql')->dropIfExists('kpi_access_grants');
        DB::connection('pgsql')->statement('drop function if exists validate_kpi_grant_tenancy()');
        DB::connection('pgsql')->statement('drop function if exists derive_company_id_from_kpi()');

        Schema::connection('pgsql')->dropIfExists('platform_admin_assignments');
        DB::connection('pgsql')->statement('drop function if exists prevent_assignment_reassignment()');

        DB::connection('pgsql')->statement('alter table kpis drop constraint if exists kpis_visibility_check');
        DB::connection('pgsql')->statement('alter table kpis drop column if exists visibility');

        // Roll the tier names back before re-adding the old constraints.
        foreach (['company_users', 'department_users'] as $table) {
            DB::connection('pgsql')->statement("update {$table} set role = 'department_admin' where role = 'executive'");
            DB::connection('pgsql')->statement("update {$table} set role = 'department_user' where role in ('employee', 'slt')");
            DB::connection('pgsql')->statement("alter table {$table} drop constraint if exists {$table}_role_check");
        }
        DB::connection('pgsql')->statement('alter table department_users alter column role set default \'department_user\'');

        DB::connection('pgsql')->statement("update users set role = 'company_user' where role <> 'richworks_super_admin'");
        DB::connection('pgsql')->statement('alter table users drop constraint if exists users_role_check');
        DB::connection('pgsql')->statement('alter table users alter column role set default \'company_user\'');

        DB::connection('pgsql')->statement('drop function if exists auth_can_view_kpi(uuid)');
        DB::connection('pgsql')->statement('drop function if exists auth_can_view_company_wide(uuid)');
        DB::connection('pgsql')->statement('drop function if exists auth_can_administer_company(uuid)');
        DB::connection('pgsql')->statement('drop function if exists auth_platform_company_ids()');
        DB::connection('pgsql')->statement('drop function if exists auth_is_platform_admin()');
        DB::connection('pgsql')->statement('drop function if exists auth_platform_role()');

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_company_ids()
            returns setof uuid
            language sql stable security definer
            set search_path to 'public'
            as $$
                select cu.company_id from company_users cu
                join users u on u.id = cu.user_id
                join companies c on c.id = cu.company_id
                where u.auth_user_id = auth.uid() and cu.status = 'active'
                  and c.status not in ('suspended', 'inactive');
            $$
        SQL);

        // NOTE: the remaining policies rewritten by up() (users, departments,
        // kpi_categories, company_users, department_users, roles, kpis,
        // kpi_submissions, import_batches, audit_logs, reports) are left
        // pointing at the pre-split predicates by re-running the prior
        // migrations' definitions -- see 2026_08_12_000000, 2026_08_14_090000
        // and 2026_08_17_100000. Rolling this migration back therefore
        // requires re-running those three, in that order.
    }
};
