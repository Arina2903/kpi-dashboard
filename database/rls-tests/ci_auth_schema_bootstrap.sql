-- Bootstraps just enough of Supabase's own `auth` schema for a plain
-- `postgres:16` container (e.g. a GitHub Actions service container) to run
-- this project's migrations and `tenant_isolation.sql` against, with zero
-- dependency on the full Supabase Docker stack (GoTrue/PostgREST/Studio).
--
-- WHY THIS IS FAITHFUL, NOT A SHORTCUT: every RLS policy and helper function
-- in this codebase reaches identity through exactly one thing —
-- `auth.uid()`, a plain SQL function that reads a session-local GUC
-- (`request.jwt.claims`) and casts its `sub` claim to uuid. That's the same
-- function body Supabase itself ships (verified against Supabase's own
-- public Postgres image). Postgres evaluates a `USING`/`WITH CHECK` clause
-- identically whether `auth.uid()` is answered by this stub or by the real
-- GoTrue-backed one — RLS doesn't know or care which. The one other piece
-- referenced anywhere in the migrations is `auth.users` itself (via a
-- foreign key on `public.users.auth_user_id`), which `tenant_isolation.sql`
-- already inserts into directly rather than through Supabase's signup API
-- (see that script's own header) — so a plain table with the columns the
-- foundational migration's insert touches is all that's needed here too.
--
-- Run this BEFORE `php artisan migrate` — the very first migration
-- (`2026_08_12_000000_create_platform_foundation_schema`) has a foreign key
-- into `auth.users` and would fail immediately without it.
--
-- Also creates the three Postgres ROLES Supabase provisions on every project
-- (`anon`, `authenticated`, `service_role`) — a plain postgres:16 container
-- has none of them. `2026_08_17_150000_grant_authenticated_role_on_missing_tables`
-- runs `grant ... to authenticated`, which errors with "role does not exist"
-- on a container that only has the `auth` schema/function stubbed above and
-- nothing granting actual Postgres roles — found by actually running
-- migrations against a real disposable container, not by re-reading the SQL.

create extension if not exists pgcrypto;

do $$
begin
    if not exists (select from pg_roles where rolname = 'anon') then
        create role anon nologin noinherit;
    end if;
    if not exists (select from pg_roles where rolname = 'authenticated') then
        create role authenticated nologin noinherit;
    end if;
    if not exists (select from pg_roles where rolname = 'service_role') then
        create role service_role nologin noinherit bypassrls;
    end if;
end
$$;

grant usage on schema public to anon, authenticated, service_role;

create schema if not exists auth;

grant usage on schema auth to anon, authenticated, service_role;

create table if not exists auth.users (
    id uuid primary key default gen_random_uuid(),
    instance_id uuid,
    aud varchar(255),
    role varchar(255),
    email varchar(255),
    encrypted_password varchar(255),
    email_confirmed_at timestamptz,
    created_at timestamptz default now(),
    updated_at timestamptz default now(),
    raw_app_meta_data jsonb default '{}'::jsonb,
    raw_user_meta_data jsonb default '{}'::jsonb
);

-- Verbatim match of Supabase's own `auth.uid()` — reads the JWT `sub` claim
-- out of `request.jwt.claims`, exactly what a real PostgREST request
-- populates (and exactly what `tenant_isolation.sql` fakes via
-- `set_config('request.jwt.claims', ..., true)`).
create or replace function auth.uid() returns uuid
    language sql stable
    as $$
  select
    coalesce(
        nullif(current_setting('request.jwt.claim.sub', true), ''),
        (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'sub')
    )::uuid
$$;
