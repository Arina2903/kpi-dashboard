-- Run this once in the Supabase SQL editor (project: mlggobjdsicuokblbsww) to
-- schedule AI performance review generation from Supabase itself, same
-- pattern as telegram_cron.sql. Requires pg_cron and pg_net (already enabled
-- if telegram_cron.sql was applied).
--
-- Replace <CRON_SECRET> with TELEGRAM_CRON_SECRET from .env, and <APP_URL>
-- with the deployed app URL (e.g. https://kpi.richworks.com).

CREATE EXTENSION IF NOT EXISTS pg_cron;
CREATE EXTENSION IF NOT EXISTS pg_net;

-- Weekly review — every Sunday 18:00 Asia/Kuala_Lumpur (UTC+8) = 10:00 UTC.
-- Covers the 7 days ending that Sunday.
SELECT cron.schedule(
    'telegram-weekly-review',
    '0 10 * * 0',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/review/weekly',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- Monthly review — 1st of each month, 08:00 Asia/Kuala_Lumpur (UTC+8) =
-- 00:00 UTC. Covers the calendar month that just ended.
SELECT cron.schedule(
    'telegram-monthly-review',
    '0 0 1 * *',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/review/monthly',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- Quarterly review — runs daily at 07:10 Asia/Kuala_Lumpur = 23:10 UTC
-- (previous day) and internally only generates a review for employees whose
-- KPI quarter actually ended the day before, since each KPI's quarter dates
-- are set individually rather than on one fixed company-wide calendar.
SELECT cron.schedule(
    'telegram-quarterly-review',
    '10 23 * * *',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/review/quarterly',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- To inspect scheduled jobs:      SELECT * FROM cron.job;
-- To inspect run history:        SELECT * FROM cron.job_run_details ORDER BY start_time DESC LIMIT 20;
-- To remove a job:                SELECT cron.unschedule('telegram-weekly-review');
