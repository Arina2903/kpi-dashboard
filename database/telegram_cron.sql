-- Run this once in the Supabase SQL editor (project: mlggobjdsicuokblbsww) to
-- schedule the daily Telegram reminder digests from Supabase itself instead of
-- a separate Railway cron service. Requires the pg_cron and pg_net extensions
-- (Database > Extensions in the Supabase dashboard, or the CREATE EXTENSION
-- statements below if your plan allows enabling them via SQL).
--
-- Replace <CRON_SECRET> below with the TELEGRAM_CRON_SECRET value from .env,
-- and <APP_URL> with the deployed app URL (e.g. https://kpi.richworks.com).

CREATE EXTENSION IF NOT EXISTS pg_cron;
CREATE EXTENSION IF NOT EXISTS pg_net;

-- 08:30 Asia/Kuala_Lumpur (UTC+8) = 00:30 UTC
SELECT cron.schedule(
    'telegram-morning-digest',
    '30 0 * * *',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/morning',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- 17:30 Asia/Kuala_Lumpur (UTC+8) = 09:30 UTC
SELECT cron.schedule(
    'telegram-evening-digest',
    '30 9 * * *',
    $$
    SELECT net.http_post(
        url := '<APP_URL>/api/telegram/cron/evening',
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Cron-Secret', '<CRON_SECRET>'),
        body := '{}'::jsonb
    );
    $$
);

-- To inspect scheduled jobs:      SELECT * FROM cron.job;
-- To inspect run history:        SELECT * FROM cron.job_run_details ORDER BY start_time DESC LIMIT 20;
-- To remove a job:                SELECT cron.unschedule('telegram-morning-digest');
