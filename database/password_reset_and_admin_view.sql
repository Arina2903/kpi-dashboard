-- Password reset (forgot password via email) + BTS admin "View As" audit log.
-- Manual-apply file (like database/telegram_feature.sql) — run in the Supabase SQL editor.

alter table public.users
  add column if not exists password_reset_token text,
  add column if not exists password_reset_expires_at timestamptz;

create table if not exists public.admin_view_as_logs (
  id uuid primary key default gen_random_uuid(),
  admin_employee_id uuid not null references public.employees(id),
  admin_name text not null,
  target_employee_id uuid not null references public.employees(id),
  target_name text not null,
  started_at timestamptz not null default now(),
  ended_at timestamptz
);
