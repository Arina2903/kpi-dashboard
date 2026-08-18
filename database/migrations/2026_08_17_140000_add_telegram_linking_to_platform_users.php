<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds real Telegram-linking columns to the PLATFORM's own `users` table.
 * These are distinct from the identically-named columns the legacy Telegram
 * integration already expects on a *different*, legacy `users` table (see
 * app/Http/Controllers/Telegram/TelegramWebhookController.php and
 * TelegramLinkController.php) — that integration remains untouched and still
 * dead (its `employees`/`user_company_roles` tables don't exist in
 * production). This migration is what lets a Telegram account be linked to a
 * real Platform user for the first time, so `TelegramAuthorizedScope` can
 * resolve "which Platform user, in which company, with what role" instead of
 * a bot handler reaching for the service-role client — see CLAUDE.md's
 * "Telegram bot needs the same security model".
 *
 * `telegram_link_code`/`telegram_link_code_expires_at` are a short-lived,
 * single-use bridge: a signed-in Platform user requests a code (written to
 * their OWN row via their own token, under `users_update_self` RLS — no
 * elevated access needed for that half). The Telegram webhook, which has no
 * user session of its own, resolves the code back to that user via the
 * service-role client — `users` is one of the Core Platform Rule's own
 * documented service-role exemptions (see SupabaseService::TENANT_OWNED_TABLES),
 * and this is a narrow, single-purpose read/write against it, not a general
 * bypass.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('alter table users add column if not exists telegram_user_id bigint');
        DB::connection('pgsql')->statement('alter table users add column if not exists telegram_chat_id bigint');
        DB::connection('pgsql')->statement('alter table users add column if not exists telegram_username text');
        DB::connection('pgsql')->statement('alter table users add column if not exists telegram_linked_at timestamptz');
        DB::connection('pgsql')->statement('alter table users add column if not exists telegram_link_code text');
        DB::connection('pgsql')->statement('alter table users add column if not exists telegram_link_code_expires_at timestamptz');

        // Partial unique indexes: many rows will have NULL in these columns
        // (never linked / no code pending), and NULLs never collide under a
        // unique index, so this only actually constrains linked/pending rows.
        DB::connection('pgsql')->statement(
            'create unique index if not exists users_telegram_user_id_key on users (telegram_user_id) where telegram_user_id is not null'
        );
        DB::connection('pgsql')->statement(
            'create unique index if not exists users_telegram_link_code_key on users (telegram_link_code) where telegram_link_code is not null'
        );
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop index if exists users_telegram_link_code_key');
        DB::connection('pgsql')->statement('drop index if exists users_telegram_user_id_key');
        DB::connection('pgsql')->statement('alter table users drop column if exists telegram_link_code_expires_at');
        DB::connection('pgsql')->statement('alter table users drop column if exists telegram_link_code');
        DB::connection('pgsql')->statement('alter table users drop column if exists telegram_linked_at');
        DB::connection('pgsql')->statement('alter table users drop column if exists telegram_username');
        DB::connection('pgsql')->statement('alter table users drop column if exists telegram_chat_id');
        DB::connection('pgsql')->statement('alter table users drop column if exists telegram_user_id');
    }
};
