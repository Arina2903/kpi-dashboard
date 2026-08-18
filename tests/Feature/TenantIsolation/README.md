# Tenant isolation — automated, continuous (requirement #10)

Two complementary layers prove "Company A cannot reach Company B," run on
every push:

| Layer | Where | What it proves |
|---|---|---|
| Application | This directory (`php artisan test`, CI job `test`) | The app never even *attempts* a cross-tenant read/write/delete — `Http::fake()`-based, no real Postgres involved. |
| Database | `database/rls-tests/tenant_isolation.sql` (CI job `tenant-isolation`) | Postgres RLS itself refuses a forged request, evaluated for real against a disposable `postgres:16` container bootstrapped with a minimal Supabase-compatible `auth` schema (`database/rls-tests/ci_auth_schema_bootstrap.sql`). |

Neither layer alone is the whole story. A green run of only this directory
proves the app-layer guard holds, not that the tenant boundary itself is
intact — that's what the real-Postgres job is for. See
`TenantIsolationTestCase`'s own docblock for the longer version of why this
split exists (the `real-walkthrough-over-mocks` lesson: mocked tests can't
catch a real RLS/PostgREST bug).

## The 10 attack vectors, and where each is covered

| # | Attack | This directory | Real Postgres |
|---|---|---|---|
| 1 | Read another company's row | `ReadAnotherCompanysRowTest` | `tenant_isolation.sql` scenarios 1–3 |
| 2 | Update another company's row | `UpdateAnotherCompanysRowTest` | `tenant_isolation.sql` scenario 5 |
| 3 | Delete another company's row | `DeleteAnotherCompanysRowTest` | `tenant_isolation.sql` scenario 6 (documents the confirmed gap: no DELETE policy exists on `kpis` at all, so RLS denies by default — not a failure to fix here) |
| 4 | Change `company_id` | `ChangeCompanyIdTest` (app never reads a client-supplied `company_id`) | The `BEFORE UPDATE` immutability triggers (`2026_08_14_080000`, `2026_08_17_100000`) — not directly exercised by `tenant_isolation.sql` today; `tenant_isolation.sql` scenario 4 covers the sibling case (a forged `company_id` on INSERT) |
| 5 | Import another company's employee | `ImportAnotherCompanysEmployeeTest` | n/a — import has no RLS-level test today; the app-layer token/company binding is the only defense (Super-Admin-only feature, so RLS bypass is expected by design) |
| 6 | Access another company's API endpoint | `AccessAnotherCompanysApiEndpointTest` (broad sweep — see its own docblock for maintenance expectations) | every scenario above, collectively |
| 7 | Access suspended company data | `AccessSuspendedCompanyDataTest` | `tenant_isolation.sql` scenarios 7–9 (the actual authoritative proof — suspension/archival is enforced entirely by RLS) |
| 8 | Manipulate URL/company IDs | `ManipulateUrlCompanyIdsTest` | n/a — this is specifically an app-layer question (does a malformed segment crash or leak) |
| 9 | Query through ANIRA | `QueryThroughAniraTest` (+ existing `AniraControllerTest`/`AiServicePlatformChatTest`) | n/a — ANIRA has no direct SQL surface; its safety is that every row it can discuss already passed through RLS via `AuthorizedDataScope` |
| 10 | Query through Telegram | `QueryThroughTelegramTest` (+ existing `TelegramAuthorizedScopeTest`/`PlatformTelegramDigestServiceTest`) | n/a — same reasoning as ANIRA; `TelegramAuthorizedScope` mints a real per-user RLS-scoped session rather than reading with service_role |

## Running it yourself

```bash
# Application layer — fast, no external dependencies
php artisan test --filter=TenantIsolation

# Database layer — needs a disposable Postgres (see .github/workflows/ci.yml
# for the exact bootstrap sequence; never point this at production)
psql "$DISPOSABLE_DSN" -f database/rls-tests/ci_auth_schema_bootstrap.sql
php artisan migrate --force
php artisan platform:rls-test --dsn="$DISPOSABLE_DSN"
```
