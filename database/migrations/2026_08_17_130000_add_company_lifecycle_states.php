<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Formalizes `companies.status` into a real lifecycle instead of the loose
 * 7-value grab-bag `2026_08_14_030000` left behind (`prospect`, `onboarding`,
 * `ready`, `active`, `suspended`, `inactive`, `archived`) — most of which
 * nothing in the app ever wrote (confirmed against production: both existing
 * companies are `active`; `prospect`/`ready`/`inactive`/`archived` have zero
 * rows). Reconciled down to six, matching what the app actually does:
 *
 *   draft -> onboarding -> configuring -> active -> suspended -> archived
 *
 * `draft`/`onboarding`/`configuring` are the pre-activation progression —
 * auto-advanced by `CompanyLifecycleService` as real setup work happens
 * (first admin invited, KPIs configured), the same "computed, monotonic,
 * never regresses" pattern `onboarding_status` already uses. `active`,
 * `suspended`, and `archived` are NOT part of that auto-advance: they are
 * deliberate, admin-triggered actions
 * (`CompanyController::activate()/suspend()/reactivate()/archive()/unarchive()`).
 *
 * Backfill mapping for the old values (defensive — no production row
 * currently needs it, but written correctly in case that changes before this
 * runs): `prospect` -> `draft`, `ready` -> `configuring`, `inactive` ->
 * `suspended` (RLS already treated them identically; `inactive` was never a
 * meaningfully distinct state from `suspended`).
 *
 * RLS enforcement update: `auth_company_ids()`, `auth_role_in_company()`,
 * `auth_department_ids()`, and `auth_platform_company_ids()` (2026_08_14_060000,
 * 2026_08_17_110000) excluded `('suspended', 'inactive')` from access. Now
 * that `inactive` no longer exists and `archived` is a real, reachable
 * terminal state, the exclusion becomes `('suspended', 'archived')` — closing
 * a real gap: nothing previously stopped an archived company's own members
 * from reading their data, because nothing had ever set `archived` before.
 * `draft`/`onboarding`/`configuring` are deliberately NOT excluded — a company
 * mid-setup still needs its own Company Admin to be able to configure it,
 * exactly as 2026_08_14_060000's own docblock already established for the
 * pre-active states that existed at the time.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement("update companies set status = 'draft' where status = 'prospect'");
        DB::connection('pgsql')->statement("update companies set status = 'configuring' where status = 'ready'");
        DB::connection('pgsql')->statement("update companies set status = 'suspended' where status = 'inactive'");

        DB::connection('pgsql')->statement('alter table companies drop constraint if exists companies_status_check');
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table companies add constraint companies_status_check
              check (status in ('draft', 'onboarding', 'configuring', 'active', 'suspended', 'archived'))
        SQL);

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
                  and c.status not in ('suspended', 'archived');
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
                join companies c on c.id = cu.company_id
                where u.auth_user_id = auth.uid() and cu.status = 'active'
                  and c.status not in ('suspended', 'archived')
                union
                select auth_platform_company_ids();
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
                join companies c on c.id = cu.company_id
                where u.auth_user_id = auth.uid() and cu.company_id = c_id and cu.status = 'active'
                  and c.status not in ('suspended', 'archived')
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
                join departments d on d.id = du.department_id
                join companies c on c.id = d.company_id
                where u.auth_user_id = auth.uid()
                  and c.status not in ('suspended', 'archived');
            $$
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement("update companies set status = 'inactive' where status = 'archived'");
        DB::connection('pgsql')->statement("update companies set status = 'ready' where status = 'configuring'");
        DB::connection('pgsql')->statement("update companies set status = 'prospect' where status = 'draft'");

        DB::connection('pgsql')->statement('alter table companies drop constraint if exists companies_status_check');
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table companies add constraint companies_status_check
              check (status in ('prospect', 'onboarding', 'ready', 'active', 'suspended', 'inactive', 'archived'))
        SQL);

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

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function auth_role_in_company(c_id uuid)
            returns text
            language sql stable security definer
            set search_path to 'public'
            as $$
                select cu.role from company_users cu
                join users u on u.id = cu.user_id
                join companies c on c.id = cu.company_id
                where u.auth_user_id = auth.uid() and cu.company_id = c_id and cu.status = 'active'
                  and c.status not in ('suspended', 'inactive')
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
                join departments d on d.id = du.department_id
                join companies c on c.id = d.company_id
                where u.auth_user_id = auth.uid()
                  and c.status not in ('suspended', 'inactive');
            $$
        SQL);
    }
};
