<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * The two halves of linking a Telegram account to a Platform user:
 * `generateCode()` runs inside an authenticated Platform request (real
 * SupabaseUserService, the caller's own token — an ordinary RLS-respecting
 * update of their own `users` row, no elevated access involved).
 * `completeLink()` runs inside the Telegram webhook, which has no Platform
 * session at all, so it necessarily uses the service-role client — `users`
 * is one of the Core Platform Rule's documented exemptions, and this is a
 * narrow, single-purpose write (set this specific already-identified row's
 * telegram_* columns), not a general tenant-data bypass.
 *
 * The code is short-lived and single-use by construction: `completeLink()`
 * always clears it (on both success and expiry-driven failure paths it
 * leaves the row alone only when no matching code was found at all), so a
 * captured code can't be replayed even before its TTL elapses.
 */
class PlatformTelegramLinkService
{
    private const CODE_TTL_MINUTES = 10;

    /**
     * @return array{code: string, expires_at: string}
     */
    public function generateCode(SupabaseUserService $ownScope, string $ownUserId): array
    {
        $code = strtoupper(Str::random(8));
        $expiresAt = now()->addMinutes(self::CODE_TTL_MINUTES)->toIso8601String();

        $ownScope->update('users', ['id' => 'eq.' . $ownUserId], [
            'telegram_link_code' => $code,
            'telegram_link_code_expires_at' => $expiresAt,
        ]);

        return ['code' => $code, 'expires_at' => $expiresAt];
    }

    public function disconnect(SupabaseUserService $ownScope, string $ownUserId): void
    {
        $ownScope->update('users', ['id' => 'eq.' . $ownUserId], [
            'telegram_user_id' => null,
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_linked_at' => null,
        ]);
    }

    /**
     * @return string|null the linked user's `public.users.id`, or null if no
     *                      matching, unexpired code was found. The id (rather
     *                      than a plain bool) is what lets the webhook
     *                      controller attribute an audit log entry to a real
     *                      `target_user_id` instead of just "something was
     *                      linked."
     */
    public function completeLink(
        SupabaseService $supabase,
        string $code,
        int $telegramUserId,
        int $chatId,
        ?string $username
    ): ?string {
        $user = $supabase->first('users', [
            'telegram_link_code' => 'eq.' . $code,
            'telegram_link_code_expires_at' => 'gt.' . now()->toIso8601String(),
            'select' => 'id',
        ]);

        if (empty($user)) {
            return null;
        }

        $supabase->patch('users', ['id' => 'eq.' . $user['id']], [
            'telegram_user_id' => $telegramUserId,
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_linked_at' => now()->toIso8601String(),
            'telegram_link_code' => null,
            'telegram_link_code_expires_at' => null,
        ]);

        return $user['id'];
    }
}
