-- NOT YET APPLIED to Supabase project mlggobjdsicuokblbsww.
-- (kept here for reference/reproducibility, same pattern as telegram_projects.sql
-- and telegram_project_task_updates.sql — there is no direct Postgres/DDL access
-- from the app; this file is applied manually via the Supabase SQL editor or MCP).
--
-- Module 1 of the Performix design (see docs/performix-design.md §1, §7).
-- Extends the existing telegram_projects / telegram_project_tasks /
-- telegram_project_task_kpi_links / telegram_project_task_updates tables in
-- place rather than creating a parallel schema, and adds four new tables
-- (task_attachments, task_score_snapshots, ai_summaries, task_reminders_log).
--
-- Does NOT touch the unrelated, unused `tasks` table or TaskController — see
-- docs/performix-design.md §0.3 / §6-R7.

-- ---------------------------------------------------------------------------
-- telegram_projects: department scoping + visibility
-- ---------------------------------------------------------------------------
ALTER TABLE telegram_projects
    ADD COLUMN department_code TEXT,
    ADD COLUMN visibility TEXT NOT NULL DEFAULT 'private'
        CHECK (visibility IN ('private', 'team', 'department'));

-- ---------------------------------------------------------------------------
-- telegram_project_tasks: full task fields per spec (assignee, dates,
-- priority, type, effort, recurrence, visibility, richer status)
-- ---------------------------------------------------------------------------
ALTER TABLE telegram_project_tasks
    ADD COLUMN assignee_employee_id UUID REFERENCES employees(id),
    ADD COLUMN description TEXT,
    ADD COLUMN progress_percentage NUMERIC NOT NULL DEFAULT 0
        CHECK (progress_percentage >= 0 AND progress_percentage <= 100),
    ADD COLUMN priority TEXT NOT NULL DEFAULT 'medium'
        CHECK (priority IN ('low', 'medium', 'high', 'critical')),
    ADD COLUMN task_type TEXT,
    ADD COLUMN estimated_effort_hours NUMERIC,
    ADD COLUMN start_date DATE,
    ADD COLUMN due_date DATE,
    ADD COLUMN reminder_at TIMESTAMPTZ,
    ADD COLUMN visibility TEXT NOT NULL DEFAULT 'private'
        CHECK (visibility IN ('private', 'team', 'department')),
    ADD COLUMN recurrence_rule TEXT NOT NULL DEFAULT 'none'
        CHECK (recurrence_rule IN ('none', 'daily', 'weekdays', 'weekly', 'monthly')),
    ADD COLUMN is_unplanned BOOLEAN NOT NULL DEFAULT false;

-- Backfill assignee from creator for all existing rows, then enforce NOT NULL
-- going forward (new inserts must always set it explicitly).
UPDATE telegram_project_tasks
    SET assignee_employee_id = employee_id
    WHERE assignee_employee_id IS NULL;

ALTER TABLE telegram_project_tasks
    ALTER COLUMN assignee_employee_id SET NOT NULL;

-- Widen status from (in_progress, done) to the full spec'd lifecycle.
ALTER TABLE telegram_project_tasks
    DROP CONSTRAINT telegram_project_tasks_status_check;

ALTER TABLE telegram_project_tasks
    ADD CONSTRAINT telegram_project_tasks_status_check
    CHECK (status IN ('not_started', 'in_progress', 'done', 'blocked', 'cancelled'));

CREATE INDEX idx_tg_ptasks_assignee ON telegram_project_tasks (assignee_employee_id);
CREATE INDEX idx_tg_ptasks_due_date ON telegram_project_tasks (due_date);
CREATE INDEX idx_tg_ptasks_status ON telegram_project_tasks (status);

-- ---------------------------------------------------------------------------
-- telegram_project_task_kpi_links: AI suggestion metadata
-- ---------------------------------------------------------------------------
ALTER TABLE telegram_project_task_kpi_links
    ADD COLUMN ai_suggested BOOLEAN NOT NULL DEFAULT false,
    ADD COLUMN ai_confidence NUMERIC CHECK (ai_confidence >= 0 AND ai_confidence <= 100),
    ADD COLUMN ai_reason TEXT,
    ADD COLUMN confirmed_by_user BOOLEAN NOT NULL DEFAULT true;

-- ---------------------------------------------------------------------------
-- telegram_project_task_updates: daily-update fields (who, status/progress
-- at that moment, blocked/reschedule notes, channel)
-- ---------------------------------------------------------------------------
ALTER TABLE telegram_project_task_updates
    ADD COLUMN updated_by_employee_id UUID REFERENCES employees(id),
    ADD COLUMN status_at_update TEXT,
    ADD COLUMN progress_at_update NUMERIC,
    ADD COLUMN note TEXT,
    ADD COLUMN reschedule_reason TEXT,
    ADD COLUMN channel TEXT NOT NULL DEFAULT 'telegram'
        CHECK (channel IN ('telegram', 'web'));

-- Backfill updated_by_employee_id from the parent task's creator for
-- existing rows (best available signal — no per-update actor was recorded
-- before this column existed).
UPDATE telegram_project_task_updates u
    SET updated_by_employee_id = t.employee_id
    FROM telegram_project_tasks t
    WHERE u.task_id = t.id AND u.updated_by_employee_id IS NULL;

-- ---------------------------------------------------------------------------
-- task_attachments (new)
-- ---------------------------------------------------------------------------
CREATE TABLE task_attachments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task_id UUID NOT NULL REFERENCES telegram_project_tasks(id) ON DELETE CASCADE,
    file_url TEXT NOT NULL,
    file_name TEXT NOT NULL,
    uploaded_by_employee_id UUID NOT NULL REFERENCES employees(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_task_attachments_task ON task_attachments (task_id);

-- ---------------------------------------------------------------------------
-- task_score_snapshots (new) — precomputed, audited Task Score per period.
-- Populated by the scoring service (docs/performix-design.md §6-R4, §6-R5),
-- never calculated live on dashboard load.
-- ---------------------------------------------------------------------------
CREATE TABLE task_score_snapshots (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    employee_id UUID NOT NULL REFERENCES employees(id),
    period_type TEXT NOT NULL CHECK (period_type IN ('daily', 'weekly', 'monthly')),
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    score NUMERIC NOT NULL CHECK (score >= 0 AND score <= 100),
    breakdown JSONB NOT NULL,
    calculated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (employee_id, period_type, period_start)
);

CREATE INDEX idx_tss_employee_period ON task_score_snapshots (employee_id, period_type, period_start);

-- ---------------------------------------------------------------------------
-- ai_summaries (new) — daily/weekly/monthly AI summaries at employee/team/
-- department/company scope, with a self-referencing audit chain for
-- regeneration (docs/performix-design.md AI REQUIREMENTS section).
-- ---------------------------------------------------------------------------
CREATE TABLE ai_summaries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    employee_id UUID REFERENCES employees(id),
    scope TEXT NOT NULL CHECK (scope IN ('employee', 'team', 'department', 'company')),
    period_type TEXT NOT NULL CHECK (period_type IN ('daily', 'weekly', 'monthly')),
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    summary_text TEXT NOT NULL,
    facts JSONB NOT NULL,
    model_version TEXT NOT NULL,
    regenerated_from_id UUID REFERENCES ai_summaries(id),
    generated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_ai_summaries_lookup ON ai_summaries (employee_id, scope, period_type, period_start);

-- ---------------------------------------------------------------------------
-- task_reminders_log (new) — idempotency guard for the 8:30/17:30 scheduler
-- (docs/performix-design.md §6-R3). The UNIQUE constraint, not app logic, is
-- what actually prevents a double-send on a retried cron invocation.
-- ---------------------------------------------------------------------------
CREATE TABLE task_reminders_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    employee_id UUID NOT NULL REFERENCES employees(id),
    reminder_type TEXT NOT NULL CHECK (reminder_type IN ('morning', 'evening', 'weekly', 'monthly')),
    task_date DATE NOT NULL,
    sent_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (employee_id, reminder_type, task_date)
);
