-- Applied directly to Supabase project mlggobjdsicuokblbsww on 2026-07-10
-- (kept here for reference/reproducibility, same pattern as telegram_feature.sql
-- and telegram_cron.sql — there is no direct Postgres/DDL access from the app).
--
-- Adds "Projects" and "Tasks" as first-class, reusable entities for the
-- Telegram Mini App's daily to-do flow, distinct from the one-off, KPI-scoped
-- rows in telegram_daily_tasks. A task can be linked to multiple KPIs purely
-- for tracking/visibility (see telegram_project_task_updates.sql) — updating
-- a task's actual does NOT write into any KPI's quarter_actual. The KPI's
-- own actual is still only ever changed via the existing quick inline
-- Update box on "My KPIs" / the adjustQuarter endpoint.
--
-- Note: there is an existing unrelated `tasks` table (tied to `initiatives`,
-- no target/actual/unit columns) — these are separate, prefixed tables so as
-- not to collide with or repurpose that unused feature.

CREATE TABLE telegram_projects (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    employee_id UUID NOT NULL REFERENCES employees(id),
    company_code TEXT NOT NULL,
    name TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE telegram_project_tasks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES telegram_projects(id) ON DELETE CASCADE,
    employee_id UUID NOT NULL REFERENCES employees(id),
    company_code TEXT NOT NULL,
    title TEXT NOT NULL,
    unit TEXT NOT NULL CHECK (unit IN ('number','currency','percentage')),
    target NUMERIC NOT NULL DEFAULT 0,
    actual NUMERIC NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'in_progress' CHECK (status IN ('in_progress','done')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE telegram_project_task_kpi_links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task_id UUID NOT NULL REFERENCES telegram_project_tasks(id) ON DELETE CASCADE,
    kpi_id UUID NOT NULL REFERENCES kpis(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (task_id, kpi_id)
);

CREATE INDEX idx_tg_projects_employee ON telegram_projects (employee_id);
CREATE INDEX idx_tg_ptasks_project ON telegram_project_tasks (project_id);
CREATE INDEX idx_tg_ptasks_employee ON telegram_project_tasks (employee_id);
CREATE INDEX idx_tg_ptkl_task ON telegram_project_task_kpi_links (task_id);
CREATE INDEX idx_tg_ptkl_kpi ON telegram_project_task_kpi_links (kpi_id);
