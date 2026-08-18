<?php

namespace App\Services;

/**
 * Resolves "which Telegram account belongs to which Platform user, and what
 * are THEY authorized to see" — the mapping CLAUDE.md's Telegram security
 * model calls for: Telegram User → Performix User → Company → Role →
 * Permissions.
 *
 * This exists because a bot webhook or scheduled digest job has no Platform
 * session and therefore no user access token of its own to hand
 * `AuthorizedDataScope`. The naive fix — read everything with `SupabaseService`
 * (service_role) and filter in PHP — is exactly the failure mode already
 * documented on that class and on `SupabaseService::TENANT_OWNED_TABLES`: a
 * bug in the manual filter is a cross-tenant leak with nothing else standing
 * in the way. Instead, this class mints the Telegram-linked user a genuine,
 * short-lived Supabase Auth session (`SupabaseAuthService::mintSessionAccessToken()`)
 * and hands THAT to `AuthorizedDataScope`, so every read that follows is
 * filtered by real Postgres RLS as that specific user — identical to what
 * would happen if they'd made the request themselves.
 *
 * Returns null — never a scope — the moment identity or authorization can't
 * be established, and treats all of the following as the same outcome:
 * unlinked Telegram account, unknown user, and a SUSPENDED OR DEACTIVATED
 * account (`status !== 'active'`). That last check is what makes suspension
 * "handled immediately": nothing here caches a linked user's status, so the
 * very next digest or bot interaction after an account is suspended sees
 * `null` and sends nothing — there is no separate "check if disabled" step
 * to forget, because there is no path to data that skips this method.
 */
class TelegramAuthorizedScope
{
    public static function forTelegramUserId(
        SupabaseService $supabase,
        SupabaseAuthService $authService,
        int|string $telegramUserId
    ): ?AuthorizedDataScope {
        // `users` is one of the Core Platform Rule's own documented
        // service-role exemptions (see SupabaseService::TENANT_OWNED_TABLES)
        // — this is an identity lookup, not a read of tenant-owned data.
        $user = $supabase->first('users', [
            'telegram_user_id' => 'eq.' . $telegramUserId,
            'select' => 'id,email,status',
        ]);

        if (empty($user)) {
            return null;
        }

        if (($user['status'] ?? null) !== 'active') {
            return null;
        }

        $accessToken = $authService->mintSessionAccessToken($user['email']);

        if (!$accessToken) {
            return null;
        }

        return new AuthorizedDataScope($accessToken);
    }
}
