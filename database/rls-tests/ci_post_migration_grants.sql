-- Run this AFTER `php artisan migrate --force` and BEFORE
-- `platform:rls-test` / `tenant_isolation.sql`, against the same disposable
-- Postgres target `ci_auth_schema_bootstrap.sql` was applied to.
--
-- WHY THIS EXISTS: on a real Supabase project, every table in the `public`
-- schema automatically carries baseline privileges for `anon`/`authenticated`
-- /`service_role` — Supabase's own project template applies this once, and
-- `ALTER DEFAULT PRIVILEGES` extends it to every table created afterwards.
-- None of this codebase's migrations grant it themselves for the ORIGINAL
-- foundational tables (`companies`, `users`, `departments`, `company_users`,
-- `department_users`, `kpi_categories`, `kpis`, `kpi_submissions`,
-- `notifications`, `audit_logs`, `reports`) — only
-- `2026_08_17_150000_grant_authenticated_role_on_missing_tables` grants it,
-- and only for six tables that were *missing* it relative to what Supabase's
-- own template had already silently applied to everything else in
-- production (see CLAUDE.md's "six tables were missing basic Postgres
-- GRANTs" finding). A disposable `postgres:16` container has none of that
-- implicit history, so without this step every `authenticated`-scoped query
-- in `tenant_isolation.sql` fails at the GRANT check before RLS is ever
-- reached — found by actually running the suite against a real container,
-- not by re-reading the migrations.
--
-- This does not weaken what's being tested: Postgres checks table-level
-- GRANTs before RLS policies, so this step only clears the precondition for
-- RLS to be the thing that decides access — it grants the same broad
-- baseline Supabase itself grants, and RLS is exactly as restrictive
-- underneath it as it is in production.

grant all on all tables in schema public to anon, authenticated, service_role;
grant all on all sequences in schema public to anon, authenticated, service_role;
grant all on all routines in schema public to anon, authenticated, service_role;

alter default privileges for role postgres in schema public
    grant all on tables to anon, authenticated, service_role;
alter default privileges for role postgres in schema public
    grant all on sequences to anon, authenticated, service_role;
alter default privileges for role postgres in schema public
    grant all on routines to anon, authenticated, service_role;
