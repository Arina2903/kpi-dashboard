<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Performix Platform Blueprint, Phase 2: the onboarding wizard (Create ->
 * Import -> Users -> KPI -> Review -> Activate) has nowhere to persist its
 * position today -- `companies.status` only ever gets set to its default,
 * `active`, and nothing tracks how far through setup a company has gotten.
 * This adds that state machine plus the branding columns the Review/Live
 * screens need, without introducing a separate settings table for what is,
 * for v1, three columns 1:1 with a company (decision recorded during Phase 1
 * review: branding-only config surface, no feature-flag table yet).
 *
 * Every company already live in production was created before onboarding
 * existed as a concept and its `status` is `active` in every observed case
 * (confirmed: no other status-setting code exists anywhere in the app yet)
 * -- so those rows backfill to `onboarding_status = 'completed'` and
 * `activated_at = created_at`. Anything not already active backfills to
 * `company_created`, the state CompanyController::store() actually leaves a
 * brand new company in.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 *
 * Written idempotently (IF NOT EXISTS / DROP...IF EXISTS + recreate) after
 * discovering, while finally getting this migration to run against
 * production, that most of its own effects (all five columns, the
 * onboarding_status backfill and constraint) had already silently applied
 * in an earlier partial run that never got recorded in the migrations
 * table (interrupted mid-batch by the SUPABASE_DB_URL DNS issue documented
 * in CLAUDE.md) -- re-running the original non-idempotent version would
 * have failed immediately on "column already exists".
 *
 * That same investigation also surfaced a real, separate discovery: a
 * `companies_status_check` constraint already existed live in production
 * *before this migration was ever written* -- restricting `status` to
 * `('active', 'suspended', 'archived')` -- which the retroactive
 * foundational-schema migration's own column-by-column verification
 * missed (it must have been added to production after that verification
 * pass). This migration's original assumption that "`status` was never
 * constrained" was simply wrong. The fix keeps `archived` (real,
 * pre-existing, possibly load-bearing elsewhere) and adds this migration's
 * own onboarding-lifecycle values on top, rather than silently narrowing
 * production's existing constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('alter table companies add column if not exists onboarding_status text');
        DB::connection('pgsql')->statement('alter table companies add column if not exists activated_at timestamptz');
        DB::connection('pgsql')->statement('alter table companies add column if not exists display_name text');
        DB::connection('pgsql')->statement('alter table companies add column if not exists primary_color text');
        DB::connection('pgsql')->statement('alter table companies add column if not exists secondary_color text');

        DB::connection('pgsql')->statement(<<<'SQL'
            update companies
            set onboarding_status = case when status = 'active' then 'completed' else 'company_created' end,
                activated_at = case when status = 'active' then created_at else null end
            where onboarding_status is null
        SQL);

        DB::connection('pgsql')->statement("alter table companies alter column onboarding_status set default 'company_created'");
        DB::connection('pgsql')->statement('alter table companies alter column onboarding_status set not null');

        DB::connection('pgsql')->statement('alter table companies drop constraint if exists companies_onboarding_status_check');
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table companies add constraint companies_onboarding_status_check
              check (onboarding_status in (
                'not_started', 'company_created', 'data_imported', 'users_created',
                'kpi_configured', 'review', 'ready', 'completed'
              ))
        SQL);

        // Superset of the pre-existing ('active', 'suspended', 'archived')
        // and this migration's own onboarding-lifecycle values -- see the
        // class docblock. Not a fresh constraint on a previously-open column.
        DB::connection('pgsql')->statement('alter table companies drop constraint if exists companies_status_check');
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table companies add constraint companies_status_check
              check (status in ('prospect', 'onboarding', 'ready', 'active', 'suspended', 'inactive', 'archived'))
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('alter table companies drop constraint if exists companies_status_check');
        DB::connection('pgsql')->statement('alter table companies drop constraint if exists companies_onboarding_status_check');
        DB::connection('pgsql')->statement('alter table companies drop column if exists secondary_color');
        DB::connection('pgsql')->statement('alter table companies drop column if exists primary_color');
        DB::connection('pgsql')->statement('alter table companies drop column if exists display_name');
        DB::connection('pgsql')->statement('alter table companies drop column if exists activated_at');
        DB::connection('pgsql')->statement('alter table companies drop column if exists onboarding_status');
    }
};
