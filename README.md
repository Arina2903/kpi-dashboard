# KPI Dashboard

A full-featured KPI management system built for organisations to set, track, and approve employee KPIs across a structured role hierarchy — with an AI assistant, Telegram bot integration, and end-of-year appraisal workflow.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 (PHP 8.5) |
| Frontend | Blade templates · Tailwind CSS (CDN) |
| Database | Supabase (PostgreSQL via REST API) |
| AI | OpenAI API (GPT) |
| Messaging | Telegram Bot API |
| Session | File-based (local) · Laravel default (production) |
| Deployment | Railway |

> No Eloquent ORM. All database access goes through `SupabaseService` using Laravel's `Http::` facade.

---

## Features

### KPI Management
- Create, edit, and delete KPIs with base and stretch targets
- Quarterly target distribution (Q1–Q4)
- Category and sub-category classification
- Weightage-based overall score calculation
- Quarter actual updates with achievement tracking
- KPI linkage — cascade targets from manager to subordinate

### Approval Workflow
- Role hierarchy: `EXECUTIVE → MANAGER → VP → SLT`
- All sensitive KPI actions (edit, target change, delete) route through an approval chain
- Approval centre with accept/reject actions
- Activity log — full read-only audit trail of all actions

### ANIRA — AI KPI Assistant
- Floating chat widget available on every authenticated page
- Guided 9-step KPI consultation flow (one question at a time)
- Platform guidance: answers navigation, approval, and system questions directly
- KPI Quality Score (6 dimensions: Relevance, Measurability, Controllability, Outcome Orientation, Clarity, Data Availability)
- Draft my KPI button unlocks only after the AI finalises the KPI (Quality Score ≥ 75)
- Auto-fills the KPI create form — works from any page via cross-page redirect
- Suggests KPI descriptions and quarterly targets inline in the create form

### Dashboard & Performance
- Weighted KPI score per employee
- Department rankings
- SLT drill-down view across all staff
- End-of-year appraisal: KPI scoring + attitude scoring + performance report
- SLT admin impersonation (view-as any employee)

### Telegram Integration
- Telegram Mini App (`/telegram/app`) — runs inside Telegram's WebView
- Morning and evening digest — cron-triggered summaries sent to employees
- Project task management via Telegram bot
- Account linking — connects a Telegram account to an employee record
- Webhook at `/telegram/webhook`

### Other
- Attendance tracking and management
- KPI template library
- Job description management (used as context for ANIRA)
- Employee profile management
- Password reset via email

---

## Local Setup

### Prerequisites
- PHP 8.5+ on PATH (macOS with Homebrew: `eval "$(/usr/local/bin/brew shellenv)"`)
- Composer

### Install

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate        # creates the sessions table
```

### Environment Variables

Create a `.env` file with the following:

```env
APP_URL=http://localhost:8000

# Database
SUPABASE_URL=
SUPABASE_SERVICE_ROLE_KEY=

# AI
OPENAI_API_KEY=

# Auth
DEFAULT_LOGIN_PASSWORD=

# Telegram
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_BOT_USERNAME=
TELEGRAM_CRON_SECRET=

# Mail (password reset)
MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=

# Session (local dev)
SESSION_DRIVER=file
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false
```

### Run

```bash
php artisan serve --port=8000
```

After any `.env` changes:

```bash
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

---

## Architecture

### Database

All DB calls use `SupabaseService`:

```php
$supabase->get('table', ['column' => 'eq.value', 'select' => '*']);
$supabase->insert('table', $data);
$supabase->patch('table', $filters, $data);
$supabase->delete('table', $filters);
```

The only exception is the `sessions` table, backed by SQLite via a standard Laravel migration.

### Session

After login and company selection:

```
employee_uuid              — primary identifier for all DB queries
employee                   — array: id, role, short_name, department, department_code,
                             company_code, manager_id, vp_id, reports_to_id
selected_department_code   — set when SLT switches department view
available_dashboards       — list of company/employee combos this user can access
```

The `kpi.auth` middleware (`KpiAuth.php`) guards all protected routes via `employee_uuid`.

### KPI Scoring Formula

```
Overall Score = Σ(achievement% × weightage%) / Σ(weightage%)
```

Per-KPI achievement:
- `actual < base` → `(actual / base) × 100`
- `actual ≥ base` with stretch → `100 + ((actual − base) / (stretch − base)) × 100`, capped at 200%
- No stretch set → capped at 100%

### ANIRA — AI Quality Score Dimensions

| Dimension | Weight |
|---|---|
| Relevance | 20% |
| Measurability | 20% |
| Controllability | 20% |
| Outcome Orientation | 15% |
| Clarity | 15% |
| Data Availability | 10% |

Threshold to finalise: **≥ 75 / 100**

---

## Production (Railway)

```bash
php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan serve --host=0.0.0.0 --port=$PORT
```

---

## Key Files

| Path | Purpose |
|---|---|
| `app/Services/SupabaseService.php` | All database access |
| `app/Services/AiService.php` | All OpenAI calls (chat, scoring, suggestions) |
| `app/Services/ApprovalHierarchyService.php` | Resolves approver chain |
| `app/Services/TelegramService.php` | Sends Telegram messages |
| `app/Http/Controllers/KpiController.php` | Core KPI CRUD (~3,600 lines) |
| `app/Http/Controllers/DashboardController.php` | Scores and rankings |
| `app/Http/Controllers/PerformanceController.php` | End-of-year appraisal |
| `app/Http/Controllers/ApprovalController.php` | Approval centre |
| `app/Http/Middleware/KpiAuth.php` | Session-based auth guard |
| `resources/views/kpi/create.blade.php` | KPI create form (~3,200 lines) |
| `resources/views/partials/ai-chat-widget.blade.php` | ANIRA floating chat widget |
