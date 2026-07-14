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

**Production (Railway):** `php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan serve --host=0.0.0.0 --port=$PORT`

## Environment Variables

```
SUPABASE_URL                — Supabase project URL
SUPABASE_SERVICE_ROLE_KEY   — Service role key (full DB access)
OPENAI_API_KEY              — OpenAI key for ANIRA AI features
DEFAULT_LOGIN_PASSWORD      — Default password for new employees
TELEGRAM_BOT_TOKEN          — Telegram bot token
TELEGRAM_WEBHOOK_SECRET     — Validates incoming Telegram webhook requests
TELEGRAM_BOT_USERNAME       — Bot username shown in Telegram Mini App
TELEGRAM_CRON_SECRET        — Authorises cron-triggered Telegram digest endpoints
MAIL_MAILER / MAIL_*        — For password reset emails
```

For local dev: `SESSION_DRIVER=file`, `SESSION_DOMAIN=localhost`, `SESSION_SECURE_COOKIE=false`.

## Architecture

**Stack:** Laravel 11 (PHP 8.5) · Blade templates · Supabase (PostgreSQL via REST) · OpenAI API · Telegram Bot API · No Eloquent ORM

### Database — Supabase REST only, never Eloquent

All database access goes through `app/Services/SupabaseService.php` using Laravel's `Http::` facade. There are no Eloquent models — every DB call uses:

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
