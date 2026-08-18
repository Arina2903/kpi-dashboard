<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * RETIRED — found live and unsafe during the tenant-isolation audit
 * (Requirement #10 follow-up, 2026-08-18), not previously caught because it
 * was reasoned about as "dead" before the Telegram-linking migration
 * existed. `broadcast()` used to query `users.telegram_chat_id` on the
 * assumption that column could never be populated (the legacy `employees`/
 * `user_company_roles` tables it depended on for identity don't exist in
 * production). That assumption broke the moment
 * `2026_08_17_140000_add_telegram_linking_to_platform_users.php` added
 * `telegram_chat_id` to this SAME `users` table for the new, tenant-aware
 * Platform Telegram feature — `users` is one of the Core Platform Rule's own
 * documented exemptions from `SupabaseService::TENANT_OWNED_TABLES`, so
 * nothing blocked this read. Every real Platform user who links Telegram via
 * `/platform/profile` was then reachable by this broadcast with zero
 * company or suspension check at all: a suspended user, or a member of a
 * suspended company, kept receiving these messages indefinitely, and
 * `TelegramCronController::morning()/evening()` (`POST /telegram/cron
 * /morning|evening`, guarded only by a shared secret with no tenant
 * dimension) reaches it directly, alongside the console commands and job
 * that also call it.
 *
 * `PlatformTelegramDigestService` (via `TelegramAuthorizedScope`) already
 * replaces this correctly — real per-user tenant/suspension-aware sends
 * against the identical `telegram_chat_id` column, wired at
 * `/platform/telegram/cron/{morning,evening}`. There is no remaining
 * legitimate audience for this class: the legacy `employees` table it was
 * originally built around doesn't exist in production, so every row this
 * query could ever return today is really a Platform user who should be
 * going through the replacement instead. Left in place (not deleted) so the
 * still-wired routes/commands/jobs keep resolving without a hard error, but
 * inert — the same "known dormant issue" treatment already used elsewhere
 * in this codebase (see CLAUDE.md), except this one was actively
 * exploitable rather than already-dead, so it's a behavior change, not just
 * documentation.
 */
class TelegramDigestService
{
    public function __construct(
        private SupabaseService $supabase,
        private TelegramService $telegram,
    ) {
    }

    public function sendMorning(): int
    {
        return $this->broadcast(
            "📝 <b>Good morning!</b>\nTime to set your to-do list for today. Pick the KPIs you'll work on and set today's target.",
            "📝 Set Today's To-Do",
            '/telegram/app?screen=morning'
        );
    }

    public function sendEvening(): int
    {
        return $this->broadcast(
            "📈 <b>How did today go?</b>\nUpdate your progress on today's KPI tasks — this will automatically update your KPI actuals.",
            '📈 Update My Progress',
            '/telegram/app?screen=evening'
        );
    }

    private function broadcast(string $text, string $buttonLabel, string $path): int
    {
        Log::warning(
            'TelegramDigestService::broadcast() was called but is retired (unscoped by company/suspension — '
            . 'see its class docblock). Use PlatformTelegramDigestService via /platform/telegram/cron instead.'
        );

        return 0;
    }
}
