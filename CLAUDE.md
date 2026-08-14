# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install

# Generate app key (first time)
php artisan key:generate

# Run database migrations (sessions table)
php artisan migrate

# Start dev server
php artisan serve --port=8000

# Clear config/cache after .env changes
php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Test a service directly
php artisan tinker --execute="(new App\Services\AiService())->chat([['role'=>'user','content'=>'hello']], [])"
```

PHP must be on PATH. On macOS with Homebrew: `eval "$(/usr/local/bin/brew shellenv)"` before running `php`.

**Production:** `php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan serve --host=0.0.0.0 --port=$PORT`

## Environment Variables

```
SUPABASE_URL                — Supabase project URL
SUPABASE_SERVICE_ROLE_KEY   — Service role key (full DB access, SupabaseService only)
SUPABASE_ANON_KEY           — Anon key (Platform: SupabaseAuthService/SupabaseUserService, RLS-scoped)
SUPABASE_DB_URL             — Direct Postgres connection string (Platform migrations only, `pgsql` connection — see config/database.php)
OPENAI_API_KEY              — OpenAI key for ANIRA AI features
DEFAULT_LOGIN_PASSWORD      — Default password for new employees (legacy app only)
TELEGRAM_BOT_TOKEN          — Telegram bot token
TELEGRAM_WEBHOOK_SECRET     — Validates incoming Telegram webhook requests
TELEGRAM_BOT_USERNAME       — Bot username shown in Telegram Mini App
TELEGRAM_CRON_SECRET        — Authorises cron-triggered Telegram digest endpoints
MAIL_MAILER / MAIL_*        — For password reset emails and Platform invite emails
```

`SUPABASE_DB_URL`'s default host is IPv6-only — if migrating from an environment without IPv6 routing, use the Session Pooler connection string instead (Supabase dashboard → Project Settings → Database → Connection string → Session pooler), which is IPv4-compatible.

For local dev: `SESSION_DRIVER=file`, `SESSION_DOMAIN=localhost`, `SESSION_SECURE_COOKIE=false`.

## Architecture

**Stack:** Laravel 11 (PHP 8.5) · Blade templates (legacy) + Inertia/React (multi-company Platform, in progress) · Supabase (PostgreSQL via REST, plus a direct `pgsql` Postgres connection for Platform migrations/RLS) · OpenAI API · Telegram Bot API

There are now two parallel systems in this codebase, both live in the same production Supabase project (`eavmrurxxdxbufkkzlup`):

- **The legacy single-tenant app** (this whole file, historically) — Blade views, session-based auth against an `employees` table, `EXECUTIVE/MANAGER/VP/SLT` roles.
- **The multi-company Platform** (see below) — Inertia+React, Supabase Auth, Postgres RLS. This is the active development target; the legacy app's `employees` table doesn't exist in production anymore (confirmed live), so its controllers/routes are effectively dead code until/unless a real migration path is built. `/` and `/login` already redirect to `/platform/login` for this reason.

### Database — Supabase REST only, never Eloquent

All database access goes through `app/Services/SupabaseService.php` using Laravel's `Http::` facade. There are no Eloquent models used in practice — every DB call uses:

```php
$supabase->get('table', ['column' => 'eq.value', 'select' => '*']);
$supabase->insert('table', $data);
$supabase->patch('table', $filters, $data);
$supabase->delete('table', $filters);
```

The one exception is the `sessions` table which is a standard Laravel SQLite-backed session (migrations exist for it).

### Session Structure

After login and company selection, the session contains:

```
employee_uuid            — primary identifier used in all DB queries
employee                 — array: id, role, short_name, department, department_code, company_code, manager_id, vp_id, reports_to_id
selected_department_code — set when SLT switches department view
available_dashboards     — list of company/employee combos this user can access
```

The `kpi.auth` middleware (`app/Http/Middleware/KpiAuth.php`) guards all protected routes by checking `employee_uuid`.

### Role Hierarchy

`EXECUTIVE → MANAGER → VP → SLT`

All approval routing and permission checks are based on this chain. `ApprovalHierarchyService` resolves the approver for any given employee. Sensitive KPI actions (edit, target change, delete) go through `ApprovalController` / `ApprovalActionService`.

### Controllers

| Controller | Responsibility |
|---|---|
| `KpiController` | Core KPI CRUD, quarter management, approval requests (~3,600 lines) |
| `DashboardController` | Weighted score calculation, department rankings, SLT staff drill-down |
| `PerformanceController` | End-of-year appraisal — KPI scoring, attitude scoring, performance report |
| `ApprovalController` | Approval center — routes requests by type to `ApprovalActionService` |
| `ActivityLogController` | Read-only audit trail |
| `AttendanceController` | Attendance tracking and management |
| `LinkageController` | Cascades targets from manager → subordinate via `kpi_linkages` table |
| `AiController` | OpenAI endpoints: `chat`, `scoreDescription`, `suggestDescription`, `suggestTargets` |
| `AdminController` | SLT-only view-as feature to impersonate any employee |
| `ProfileController` | Employee profile management |
| `JobDescriptionController` | Job description CRUD |
| `KpiTemplateController` | KPI template library |
| `TitanKpiController` | Titan-specific KPI view |
| `AuthController` | Login, forgot/reset password, company selection, session setup |
| `Telegram/TelegramWebhookController` | Receives and dispatches Telegram bot messages |
| `Telegram/TelegramMiniAppController` | Serves the Telegram Mini App (runs inside Telegram's WebView) |
| `Telegram/TelegramProjectTaskController` | Project task management via Telegram |
| `Telegram/TelegramLinkController` | Links Telegram accounts to employee records |
| `Telegram/TelegramCronController` | Cron-triggered morning/evening digest endpoints |

### Services

| Service | Responsibility |
|---|---|
| `SupabaseService` | All DB access via Supabase REST API |
| `AiService` | All OpenAI calls (chat, KPI scoring, description suggestions) |
| `ApprovalActionService` | Executes approved/rejected actions by type |
| `ApprovalHierarchyService` | Resolves approver chain for any employee |
| `KpiQuarterUpdateService` | Quarter actual update logic |
| `TelegramService` | Sends messages via Telegram Bot API |
| `TelegramReviewService` | AI-generated performance review digests for Telegram |
| `TelegramDigestService` | Morning/evening digest content assembly |

### AI — ANIRA

All OpenAI calls live in `app/Services/AiService.php` using `Http::` (same pattern as Supabase). Model set in `protected string $model`.

- `chat(messages, employee)` — powers ANIRA floating chat widget; acts as KPI coach, not a generator; system prompt includes full KPI system knowledge, role hierarchy, linkages, and activity log context
- `scoreKpiDescription(title, description, baseTarget, stretchTarget, unit, weightage, category, subCategory, quarterTargets)` — scores a complete KPI out of 10 using a calibrated 5-dimension rubric (title, description, target ambition, quarterly distribution, overall coherence)
- `suggestKpiDescription(title, department, role)` — generates a KPI description
- `suggestQuarterlyTargets(title, annualTarget, unit)` — suggests Q1–Q4 split

The ANIRA chat widget (`resources/views/partials/ai-chat-widget.blade.php`) is included via `partials/sidebar.blade.php` and appears on every authenticated page. The ANIRA score card sits in the KPI create form's summary sidebar above the Submit button.

### KPI Scoring Formula

```
Overall Score = Σ(achievement% × weightage%) / Σ(weightage%)
```

Per-KPI achievement (`KpiController::calculateAchievement()`):
- `actual < base` → `(actual / base) × 100`
- `actual > base` with stretch → `100 + ((actual - base) / (stretch - base)) × 100`, capped at 200%
- No stretch set → capped at 100%

### Telegram Integration

The Telegram Mini App runs at `/telegram/app` (outside `kpi.auth` — auth happens per-request via Telegram `initData` validated in `TelegramWebAppAuth` middleware). The webhook receives updates at `/telegram/webhook` (guarded by `TelegramWebhookSecret`). Cron digests are triggered via `/telegram/cron/*` routes guarded by `TelegramCronSecret`. Console commands in `app/Console/Commands/` handle scheduled digest sends and webhook registration.

### Views

All authenticated pages include `@include('partials.sidebar')`. Pages are standalone Blade files (no shared layout `@extends`) — each page sets up its own `<html>`, `<head>`, and Tailwind via CDN.

The KPI create form (`kpi/create.blade.php`) is the most complex view (~3,200+ lines) with a live JS summary sidebar (`updateSummary()`), ANIRA score card, and KPI linkage UI.

Legacy controllers now return `Inertia::render()` rather than `view()` (ported so the same routes work whether the eventual frontend is Blade or React), but the corresponding `.blade.php` files above are still what actually renders today — the `.tsx` pages under `resources/js/Pages/` are the target, not yet wired as the default render path.

## Multi-Company Platform (new architecture, in progress)

A parallel rebuild adding real multi-tenancy: one Richworks "Center" that can see every company, each company fully isolated from every other. Confirmed live in production as of 2026-08-14 — schema, RLS, and helper functions all verified directly against the database, not just inferred from code.

### Why this exists, and how it differs from the legacy app above

The legacy app has no real tenant isolation mechanism beyond `company_code` filters an engineer has to remember to add in every controller. The Platform replaces that with **Supabase Auth + Postgres Row-Level Security**: every non-privileged request carries the caller's own Supabase Auth access token (never `service_role`), so `auth.uid()` resolves to a real person inside Postgres and RLS policies — not application code — decide what's visible. A bug in a controller, a raw SQL script, or someone editing rows directly in the Supabase table editor still can't cross a company boundary.

### Role hierarchy

`richworks_super_admin` (Center Admin, sees every company) → `company_admin` (runs one company) → `department_admin` (runs one department) → `department_user` (submits KPIs). The first two are `users.role` / `company_users.role`; the last two are `department_users.role`. Each department can also define its own configurable job-level labels via the `roles` table (e.g. "Staff", "Lead") — cosmetic ordering only, not a permission tier.

### Schema (all RLS-protected, all confirmed live)

`companies`, `users` (Supabase-Auth-linked via `auth_user_id`, distinct from `auth.users`), `departments`, `company_users`, `department_users`, `roles`, `kpi_categories`, `kpis`, `kpi_submissions`, `admin_action_logs` (Center-only cross-company audit trail, append-only), `audit_logs` and `reports` (schema + RLS exist, but **no application code writes to either yet** — built ahead of features that don't exist), `notifications` (see Known issues below), and the `company_kpi_summary` view (`security_invoker`, powers the Platform dashboard's per-company stats).

Migrations live in `database/migrations/2026_08_12_000000_create_platform_foundation_schema.php` (the foundational schema + RLS + `auth_*()` helper functions, written retroactively — **do not run its `up()` against production**, everything in it already exists there; see its docblock), `2026_08_13_120000_add_configurable_department_roles.php`, `2026_08_13_130000_add_self_service_guardrail_triggers.php`, `2026_08_13_140000_add_admin_action_logs.php`, and `2026_08_14_010000_create_company_kpi_summary_view.php`.

RLS is enforced through five `SECURITY DEFINER` helper functions callable from any policy: `auth_current_user_id()`, `auth_is_richworks_super_admin()`, `auth_company_ids()`, `auth_role_in_company(company_id)`, `auth_department_ids()`. Nearly every policy is shaped `auth_is_richworks_super_admin() OR <scoped condition>`.

### Code layout

- `app/Http/Controllers/Platform/*` — Company/Department/Role/Kpi/KpiSubmission/Dashboard/Auth/Invite controllers, routed under `/platform/*`
- `app/Http/Middleware/PlatformAuth.php` — re-resolves the caller's role from Supabase on every request (unlike `KpiAuth`, which trusts the session)
- `app/Services/SupabaseAuthService.php` — wraps Supabase Auth's REST API (sign-in, invite links, password setting)
- `app/Services/SupabaseUserService.php` — the RLS-respecting client (anon key + caller's own token); use this, not `SupabaseService`, for any new Platform code
- `app/Models/Company.php`, `Department.php`, `Role.php`, `KpiPermission.php` — Eloquent models against the `pgsql` connection; **currently unused by any controller** (Platform code queries Supabase directly via `SupabaseUserService`)
- `php artisan platform:bootstrap-super-admin {email} {password}` — one-time creation of the first Richworks Super Admin
- `php artisan platform:plan-migration {company_code?}` — read-only dry run reporting how legacy `employees` rows would map onto this model; writes nothing

### Known dormant issues

- `NotificationService::notify()` and its 6 legacy callers (`ApprovalController`, `KpiController`, `PerformanceController`, `JobDescriptionController`, `TelegramReviewService`, `MiniAppTaskController`) write columns that don't exist on the live `notifications` table and are built around `employees.id`, which doesn't exist either. Every call fails silently (caught, logged). Left dormant deliberately — fixing it for real means deciding whether to rebuild the legacy KPI/approval flows on the Platform's `user_id`/`company_id` model, not a column patch.
- `users.role` defaults to `'company_user'`, a value nothing actually checks for (only `'richworks_super_admin'` is meaningful app-side).
