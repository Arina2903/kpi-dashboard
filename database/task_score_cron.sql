-- NOT YET APPLIED. Run this in the Supabase SQL editor (project: mlggobjdsicuokblbsww)
-- once you're ready for weekly/monthly Task Score + AI summary generation to
-- start running automatically — same pg_cron + pg_net pattern as
-- telegram_cron.sql / task_reminders_cron.sql.
--
-- Replace <CRON_SECRET> with the TELEGRAM_CRON_SECRET value from .env, and
-- <APP_URL> with the deployed app URL (e.g. https://kpi.richworks.com).
--
-- "Last business weekday of the month" has no clean crontab expression, so
-- the monthly job is scheduled to fire every day from the 28th onward —
-- TaskScoreSchedulerService::runMonthly() itself checks whether today is
-- actually the last business day and no-ops otherwise (see the service's
-- doc comment). Every other invocation that month is a harmless no-op.

-- Friday 17:30 Asia/Kuala_Lumpur (UTC+8) = Friday 09:30 UTC
SELECT cron.schedule(
    'performix-tasks-weekly',
    '30 9 * * 5',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/tasks-weekly',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- Days 28-31 of every month, 17:30 Asia/Kuala_Lumpur (UTC+8) = 09:30 UTC —
-- the app itself decides which of these is the real "last business day".
SELECT cron.schedule(
    'performix-tasks-monthly',
    '30 9 28-31 * *',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/tasks-monthly',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- To inspect scheduled jobs:      SELECT * FROM cron.job;
-- To inspect run history:        SELECT * FROM cron.job_run_details ORDER BY start_time DESC LIMIT 20;
-- To remove a job:                SELECT cron.unschedule('performix-tasks-weekly');
