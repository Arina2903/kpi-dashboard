-- Applied directly to Supabase project mlggobjdsicuokblbsww on 2026-07-10
-- (kept here for reference/reproducibility, same pattern as telegram_projects.sql).
--
-- Records each individual task-progress update as a timestamped history
-- entry, so a KPI's linked tasks can show "what was updated, and when" —
-- see [[project_kpi_dashboard_telegram]]. This replaced an earlier design
-- where updating a task's actual also silently cascaded the same delta into
-- every linked KPI's currently-open quarter; that direct write into the
-- KPI's official actual was judged too aggressive, so task updates now only
-- ever touch telegram_project_tasks.actual, with this table as the audit
-- trail behind the "Tasks & History" view per KPI.

CREATE TABLE telegram_project_task_updates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task_id UUID NOT NULL REFERENCES telegram_project_tasks(id) ON DELETE CASCADE,
    delta NUMERIC NOT NULL,
    new_actual NUMERIC NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_tg_ptu_task ON telegram_project_task_updates (task_id);
CREATE INDEX idx_tg_ptu_created ON telegram_project_task_updates (created_at);
