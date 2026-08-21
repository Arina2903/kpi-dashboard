-- Run this once in the Supabase SQL Editor (dashboard -> SQL Editor -> New query).
--
-- Backs the BTS-only "Appraiser Delegation" feature (Quarter Control page):
-- when a Manager is on long leave, BTS can stand their own VP in as the
-- appraiser for that Manager's Executives, without touching anyone's real
-- reports_to_id/manager_id/vp_id. One row per manager_id -- setting a new
-- delegate for the same manager replaces the old one; deleting the row
-- reverts to the normal role-based appraiser chain.
--
-- Deliberately NOT a generic "delegate anyone to anyone" table: the app only
-- ever writes delegate_to_id as that manager's own resolved VP (see
-- AppraiserDelegationController::store()), so there's no path to delegate a
-- VP's own appraiser duty onward if the VP is also unavailable -- by design,
-- per the business rule this was built for (Manager -> VP substitution only,
-- one hop, no further chaining).
--
-- Consistent with every other legacy table in this project: plain Supabase
-- Postgres table, no RLS (this project has none anywhere), accessed only via
-- SupabaseService (service_role) from app/Services/AppraiserDelegationService.php.

create table if not exists appraiser_delegations (
    id uuid primary key default gen_random_uuid(),
    manager_id uuid not null unique,
    delegate_to_id uuid not null,
    reason text null,
    created_by uuid null,
    created_by_name text null,
    created_at timestamptz not null default now()
);
