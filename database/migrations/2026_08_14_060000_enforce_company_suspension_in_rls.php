<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Performix Platform Blueprint, Phase 5: "activate/deactivate" (a company's
 * `status` transitioning to `suspended`) previously had no enforcement at
 * all -- `companies.status` existed only as a display value. Every RLS
 * policy scopes through one of `auth_company_ids()`, `auth_role_in_company()`,
 * or `auth_department_ids()`, none of which looked at the company's own
 * `status` -- so a suspended company's own users could still read and write
 * every row they always could, via the same JWT they already held, entirely
 * independent of whatever CompanyController::suspend() flips in the app.
 *
 * Fixing this at the helper-function level (rather than touching every
 * individual policy) means every policy that already calls these three
 * functions is tightened automatically -- the same pattern this project
 * already used for the recursive-policy fix in 2026_08_14_020000.
 *
 * Deliberately `status not in ('suspended', 'inactive')` rather than
 * `status = 'active'`: a company mid-onboarding (`prospect`/`onboarding`/
 * `ready`) already has an invited Company Admin who needs to sign in and set
 * up departments/KPIs before Center formally activates them (see
 * CompanyController::storeAdmin(), which never checked company status
 * either) -- only `suspended`/`inactive` are meant to be a hard lockout.
 *
 * Known remaining gap, not fixed here: `PlatformAuth`'s `company_memberships`
 * list is populated via a plain `company_users` SELECT, gated by
 * `company_users_select`'s `user_id = auth_current_user_id()` branch, which
 * doesn't route through any of these three functions -- so a suspended
 * company's own admin still sees a nav link to it, which then hits an empty
 * RLS-filtered result rather than disappearing outright. The actual data
 * boundary holds either way; only the link's continued visibility is
 * cosmetic. Revisit if that dead-link UX turns out to matter in practice.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
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
};
