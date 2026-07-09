-- Run this once in the Supabase SQL editor (project: mlggobjdsicuokblbsww) before
-- using the Telegram bot / mini app feature. There is no direct Postgres/DDL
-- access from the app (it only talks to Supabase via the PostgREST REST API),
-- so this file is not an auto-run migration — copy/paste it into the SQL editor.

ALTER TABLE users
    ADD COLUMN telegram_user_id BIGINT UNIQUE,
    ADD COLUMN telegram_chat_id BIGINT,
    ADD COLUMN telegram_username TEXT,
    ADD COLUMN telegram_linked_at TIMESTAMPTZ,
    ADD COLUMN telegram_link_code TEXT,
    ADD COLUMN telegram_link_code_expires_at TIMESTAMPTZ;

CREATE INDEX idx_users_telegram_link_code ON users (telegram_link_code) WHERE telegram_link_code IS NOT NULL;
CREATE INDEX idx_users_telegram_chat_id ON users (telegram_chat_id) WHERE telegram_chat_id IS NOT NULL;

CREATE TABLE telegram_daily_tasks (
    id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    employee_id      UUID NOT NULL REFERENCES employees(id),
    kpi_id           UUID NOT NULL REFERENCES kpis(id),
    kpi_quarter_id   UUID NOT NULL REFERENCES kpi_quarters(id),
    task_date        DATE NOT NULL,
    unit             TEXT NOT NULL CHECK (unit IN ('number','currency','percentage')),
    planned_target   NUMERIC NOT NULL,
    planned_note     TEXT,
    baseline_actual  NUMERIC NOT NULL DEFAULT 0,
    progress_value   NUMERIC,
    status           TEXT NOT NULL DEFAULT 'planned' CHECK (status IN ('planned','done','skipped')),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (employee_id, kpi_quarter_id, task_date)
);

CREATE INDEX idx_tdt_employee_date ON telegram_daily_tasks (employee_id, task_date);
CREATE INDEX idx_tdt_kpi_quarter   ON telegram_daily_tasks (kpi_quarter_id);

ALTER TABLE telegram_daily_tasks ENABLE ROW LEVEL SECURITY;
