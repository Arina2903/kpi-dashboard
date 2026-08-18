-- ============================================================================
-- Performix core platform rule — conformance check
-- ============================================================================
--
-- The rule:
--
--     Platform → Company → Users → KPI → Targets/Results
--
--     Every company-owned table carries `company_id`, and PostgreSQL RLS is
--     the final authority on access. Application-level `where company_id = ?`
--     filters are a performance optimisation only — deleting one must never
--     widen what a caller can see.
--
-- This script does not test behaviour (that's `tenant_isolation.sql`, which
-- simulates real users and asserts on what they can read). It audits the
-- *shape* of the schema: it finds any table that is supposed to be tenant-
-- scoped but isn't wired up for it. A drifting schema shows up here long
-- before it shows up as a leak.
--
-- READ-ONLY. Safe to run against any environment, production included.
--
--   psql "$SUPABASE_DB_URL" -f database/rls-tests/tenant_isolation_rule_check.sql
--
-- Every section should return zero rows. Anything returned is a violation.
-- ============================================================================

\echo ''
\echo '=== Tables deliberately exempt from the company_id rule ==================='
\echo 'companies          — is the tenant'
\echo 'users              — global identity; scoped via company_users'
\echo 'kpi_templates      — shared Center-curated library, copy-on-apply'
\echo 'kpi_template_items — ditto'
\echo 'admin_action_logs  — Center-only cross-company audit trail'
\echo ''

create temporary table _exempt (table_name text) on commit drop;
insert into _exempt values
  ('companies'), ('users'), ('kpi_templates'), ('kpi_template_items'), ('admin_action_logs');

-- ---------------------------------------------------------------------------
-- 1. Every non-exempt public table must have a company_id column.
-- ---------------------------------------------------------------------------
\echo '=== [1] Tables missing company_id (expect 0 rows) ========================='

select t.tablename as violation
from pg_tables t
where t.schemaname = 'public'
  and t.tablename not in (select table_name from _exempt)
  and t.tablename not like 'pg_%'
  and not exists (
    select 1 from information_schema.columns c
    where c.table_schema = 'public'
      and c.table_name = t.tablename
      and c.column_name = 'company_id'
  )
order by 1;

-- ---------------------------------------------------------------------------
-- 2. company_id must be NOT NULL and foreign-keyed to companies. A nullable
--    tenant key is an unscoped row waiting to happen; a key with no FK can
--    point at a company that no longer exists.
--
--    `audit_logs.company_id` is intentionally nullable — a platform-level
--    event may have no company — so it is excluded from the NOT NULL half.
-- ---------------------------------------------------------------------------
\echo '=== [2] company_id nullable, or not FK-constrained (expect 0 rows) ========'

select
  c.table_name as violation,
  case when c.is_nullable = 'YES' then 'nullable' else '' end
    || case when not exists (
         select 1
         from information_schema.table_constraints tc
         join information_schema.key_column_usage kcu
           on kcu.constraint_name = tc.constraint_name
          and kcu.table_schema = tc.table_schema
         join information_schema.constraint_column_usage ccu
           on ccu.constraint_name = tc.constraint_name
          and ccu.table_schema = tc.table_schema
         where tc.constraint_type = 'FOREIGN KEY'
           and tc.table_schema = 'public'
           and tc.table_name = c.table_name
           and kcu.column_name = 'company_id'
           and ccu.table_name = 'companies'
       ) then ' no-fk-to-companies' else '' end as problem
from information_schema.columns c
where c.table_schema = 'public'
  and c.column_name = 'company_id'
  and c.table_name not in ('audit_logs')
  -- Views (e.g. `company_kpi_summary`) have no FK/NOT NULL machinery of
  -- their own to check — the rule applies to base tables, which is what
  -- section 1 already scoped to via pg_tables. Section 2 uses
  -- information_schema.columns instead (it needs constraint metadata
  -- pg_tables doesn't carry), so it has to exclude views explicitly here.
  and exists (
    select 1 from pg_class cl
    join pg_namespace n on n.oid = cl.relnamespace
    where n.nspname = c.table_schema and cl.relname = c.table_name and cl.relkind = 'r'
  )
  and (
    c.is_nullable = 'YES'
    or not exists (
      select 1
      from information_schema.table_constraints tc
      join information_schema.key_column_usage kcu
        on kcu.constraint_name = tc.constraint_name
       and kcu.table_schema = tc.table_schema
      join information_schema.constraint_column_usage ccu
        on ccu.constraint_name = tc.constraint_name
       and ccu.table_schema = tc.table_schema
      where tc.constraint_type = 'FOREIGN KEY'
        and tc.table_schema = 'public'
        and tc.table_name = c.table_name
        and kcu.column_name = 'company_id'
        and ccu.table_name = 'companies'
    )
  )
order by 1;

-- ---------------------------------------------------------------------------
-- 3. RLS must be enabled on every public table — exempt ones included. Being
--    exempt from `company_id` does not mean being exempt from RLS.
-- ---------------------------------------------------------------------------
\echo '=== [3] Tables without RLS enabled (expect 0 rows) ========================'

select c.relname as violation
from pg_class c
join pg_namespace n on n.oid = c.relnamespace
where n.nspname = 'public'
  and c.relkind = 'r'
  and not c.relrowsecurity
order by 1;

-- ---------------------------------------------------------------------------
-- 4. RLS enabled with no policies at all denies everything, which is safe but
--    almost always means a table was wired up and then forgotten.
-- ---------------------------------------------------------------------------
\echo '=== [4] RLS enabled but zero policies (expect 0 rows) ====================='

select c.relname as violation
from pg_class c
join pg_namespace n on n.oid = c.relnamespace
where n.nspname = 'public'
  and c.relkind = 'r'
  and c.relrowsecurity
  and not exists (select 1 from pg_policy p where p.polrelid = c.oid)
order by 1;

-- ---------------------------------------------------------------------------
-- 5. Every SELECT policy on a company-scoped table must mention either
--    `company_id` or one of the auth_* helpers. A policy that references
--    neither is not enforcing the tenant boundary, whatever else it does.
--
--    Textual, so it is a smoke test rather than a proof — but it reliably
--    catches the "scoped on user_id only, tenant boundary never checked"
--    shape, which is exactly how `notifications` drifted.
-- ---------------------------------------------------------------------------
\echo '=== [5] SELECT policies that never reference the tenant (expect 0 rows) ==='

select
  p.tablename || '.' || p.policyname as violation,
  p.qual as policy_body
from pg_policies p
where p.schemaname = 'public'
  and p.cmd in ('SELECT', 'ALL')
  and p.tablename not in (select table_name from _exempt)
  and coalesce(p.qual, '') !~ 'company_id|auth_company_ids|auth_role_in_company|auth_is_richworks_super_admin'
order by 1;

-- ---------------------------------------------------------------------------
-- 6. Tables with company_id must freeze it. A tenant key that can be UPDATEd
--    is a tenant boundary that can be walked across — this is the exact bug
--    2026_08_14_080000 was written to fix, and this check is what stops it
--    from silently regressing again.
--
--    Four tables are excluded, each because nothing non-privileged can move
--    their company_id in the first place: `reports` has no UPDATE policy at
--    all (denied by default), `import_batches` and `audit_logs` are
--    Super-Admin-only writes, and `notifications` is scoped to the recipient.
--    A Super Admin already sees every company, so them reassigning one of
--    these rows is a data-quality problem, not a boundary crossing. If any of
--    those four ever gains a broader UPDATE policy, delete it from this list
--    and give it a trigger.
-- ---------------------------------------------------------------------------
\echo '=== [6] company_id tables without an immutability trigger (expect 0 rows) ='

select c.table_name as violation
from information_schema.columns c
where c.table_schema = 'public'
  and c.column_name = 'company_id'
  and c.table_name not in ('audit_logs', 'import_batches', 'notifications', 'reports')
  -- Views can't carry triggers at all -- see the matching note on section 2.
  and exists (
    select 1 from pg_class cl
    join pg_namespace n on n.oid = cl.relnamespace
    where n.nspname = c.table_schema and cl.relname = c.table_name and cl.relkind = 'r'
  )
  and not exists (
    select 1
    from pg_trigger tg
    join pg_class cl on cl.oid = tg.tgrelid
    join pg_namespace n on n.oid = cl.relnamespace
    where n.nspname = 'public'
      and cl.relname = c.table_name
      and not tg.tgisinternal
      and tg.tgname in ('trg_prevent_company_id_change', 'trg_prevent_kpi_submission_reassignment')
  )
order by 1;

-- ---------------------------------------------------------------------------
-- 7. Known open items — reported, not asserted. These are live product
--    decisions rather than drift, so they print for visibility and do not
--    count as violations.
-- ---------------------------------------------------------------------------
\echo ''
\echo '=== [7] FYI: tables with no DELETE policy (deletion denied by default) ===='

select c.relname as table_name
from pg_class c
join pg_namespace n on n.oid = c.relnamespace
where n.nspname = 'public'
  and c.relkind = 'r'
  and c.relrowsecurity
  and not exists (
    select 1 from pg_policy p
    where p.polrelid = c.oid and p.polcmd in ('d', '*')
  )
order by 1;

\echo ''
\echo '=== [7] FYI: tables without FORCE ROW LEVEL SECURITY ======================'
\echo 'ENABLE does not apply to the table owner; FORCE does. Do not turn this on'
\echo 'without checking whether the SECURITY DEFINER auth_*() helpers (owned by'
\echo 'postgres, and reading from `users`) would then become subject to the very'
\echo 'policies that call them — that is a recursion risk, not a free win.'

select c.relname as table_name
from pg_class c
join pg_namespace n on n.oid = c.relnamespace
where n.nspname = 'public'
  and c.relkind = 'r'
  and c.relrowsecurity
  and not c.relforcerowsecurity
order by 1;

\echo ''
\echo '=== Rule check complete. Sections 1-6 must be empty. ======================'
