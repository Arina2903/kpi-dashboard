-- Run this once in the Supabase SQL editor (project: mlggobjdsicuokblbsww) to
-- create the table backing AI-generated weekly/monthly/quarterly performance
-- reviews shown in the Telegram Mini App. Manual-apply, like
-- telegram_projects.sql / telegram_project_task_updates.sql — not an
-- auto-run Laravel migration.

CREATE TABLE IF NOT EXISTS telegram_ai_reviews (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    employee_id uuid NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    company_code text NOT NULL,
    period_type text NOT NULL CHECK (period_type IN ('weekly', 'monthly', 'quarterly')),
    period_label text NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    score numeric NOT NULL,
    narrative text NOT NULL,
    stats jsonb,
    generated_at timestamptz NOT NULL DEFAULT now(),
    notified_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (employee_id, period_type, period_start)
);

CREATE INDEX IF NOT EXISTS telegram_ai_reviews_employee_period_idx
    ON telegram_ai_reviews (employee_id, period_type, period_start DESC);
