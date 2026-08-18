-- Run this once in the Supabase SQL Editor (dashboard -> SQL Editor -> New query).
--
-- Backs the BTS-only "Quarter Control" feature: lets BTS force a specific
-- quarter (Q1-Q4) open for both KPI actual submission and the appraisal
-- self-review/appraiser flow, until a chosen deadline, regardless of that
-- quarter's own normal date range. One row per (financial_year, quarter) --
-- setting a new override for the same quarter replaces the old deadline.
--
-- Consistent with every other legacy table in this project: plain Supabase
-- Postgres table, no RLS (this project has none anywhere), accessed only via
-- SupabaseService (service_role) from app/Services/QuarterOverrideService.php.

create table if not exists quarter_overrides (
    id uuid primary key default gen_random_uuid(),
    financial_year text not null,
    quarter text not null check (quarter in ('Q1', 'Q2', 'Q3', 'Q4')),
    open_until timestamptz not null,
    created_by uuid null,
    created_by_name text null,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    unique (financial_year, quarter)
);
