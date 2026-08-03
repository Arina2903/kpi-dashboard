-- NOT YET APPLIED. Run this in the Supabase SQL editor (project: mlggobjdsicuokblbsww)
-- once you're ready for the Performix task reminders to start actually
-- firing in production — same pg_cron + pg_net pattern as telegram_cron.sql,
-- a separate schedule because these are a distinct concern (task reminders,
-- never touching KPI actuals) from the existing morning/evening KPI digest.
--
-- Replace <CRON_SECRET> with the TELEGRAM_CRON_SECRET value from .env, and
-- <APP_URL> with the deployed app URL (e.g. https://kpi.richworks.com).

-- 08:30 Asia/Kuala_Lumpur (UTC+8) = 00:30 UTC
SELECT cron.schedule(
    'performix-tasks-morning',
    '30 0 * * *',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/tasks-morning',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- 17:30 Asia/Kuala_Lumpur (UTC+8) = 09:30 UTC
SELECT cron.schedule(
    'performix-tasks-evening',
    '30 9 * * *',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/tasks-evening',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- To inspect scheduled jobs:      SELECT * FROM cron.job;
-- To inspect run history:        SELECT * FROM cron.job_run_details ORDER BY start_time DESC LIMIT 20;
-- To remove a job:                SELECT cron.unschedule('performix-tasks-morning');
