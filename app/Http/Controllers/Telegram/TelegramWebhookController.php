<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\PlatformTelegramLinkService;
use App\Services\SupabaseService;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(
        Request $request,
        SupabaseService $supabase,
        TelegramService $telegram,
        PlatformTelegramLinkService $platformLinks,
        AuditLogService $auditLog
    ) {
        $message = $request->input('message');

        if (empty($message)) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');

        if (!$chatId) {
            return response()->json(['ok' => true]);
        }

        if (str_starts_with($text, '/start')) {
            $code = trim(substr($text, strlen('/start')));

            // `pf_`-prefixed codes are the Platform's own linking flow
            // (PlatformTelegramLinkService/TelegramLinkController) — a
            // distinct namespace so it can never collide with, or fall
            // through into, the legacy code path below.
            if (str_starts_with($code, 'pf_')) {
                $this->handlePlatformLinkCode($supabase, $platformLinks, $telegram, $auditLog, $message, substr($code, strlen('pf_')));

                return response()->json(['ok' => true]);
            }

            if ($code !== '') {
                $this->handleLinkCode($supabase, $telegram, $message, $code);

                return response()->json(['ok' => true]);
            }
        }

        $telegram->sendMessage(
            $chatId,
            "👋 Hi! Use the button below to open your KPI mini app.",
            $telegram->webAppButton('📊 Open KPI Mini App', rtrim(env('APP_URL'), '/') . '/telegram/app')
        );

        return response()->json(['ok' => true]);
    }

    private function handleLinkCode(SupabaseService $supabase, TelegramService $telegram, array $message, string $code): void
    {
        $chatId = $message['chat']['id'];

        $user = $supabase->first('users', [
            'telegram_link_code' => 'eq.' . $code,
            'telegram_link_code_expires_at' => 'gt.' . now()->toIso8601String(),
            'select' => '*',
        ]);

        if (empty($user)) {
            $telegram->sendMessage(
                $chatId,
                "⚠️ This link code has expired. Please go back to the KPI Dashboard's My Profile page and tap \"Connect Telegram\" again."
            );

            return;
        }

        $supabase->safePatch('users', ['id' => 'eq.' . $user['id']], [
            'telegram_user_id' => $message['from']['id'],
            'telegram_chat_id' => $chatId,
            'telegram_username' => $message['from']['username'] ?? null,
            'telegram_linked_at' => now()->toIso8601String(),
            'telegram_link_code' => null,
            'telegram_link_code_expires_at' => null,
        ]);

        $telegram->sendMessage(
            $chatId,
            "✅ <b>Telegram connected!</b>\nYou'll now get daily reminders here. Tap below anytime to open your KPI mini app.",
            $telegram->webAppButton('📊 Open KPI Mini App', rtrim(env('APP_URL'), '/') . '/telegram/app')
        );
    }

    /**
     * Completes the Platform's own linking flow — see
     * PlatformTelegramLinkService's docblock for why this, unlike everything
     * else in this controller, is safe to point at real Platform data: the
     * code was minted by an authenticated Platform user acting on their own
     * row, is single-use, and expires in minutes.
     */
    private function handlePlatformLinkCode(
        SupabaseService $supabase,
        PlatformTelegramLinkService $platformLinks,
        TelegramService $telegram,
        AuditLogService $auditLog,
        array $message,
        string $code
    ): void {
        $chatId = $message['chat']['id'];

        $linkedUserId = $platformLinks->completeLink(
            $supabase,
            $code,
            (int) $message['from']['id'],
            (int) $chatId,
            $message['from']['username'] ?? null
        );

        if (!$linkedUserId) {
            // No `actor_user_id` to attribute this to — an invalid/expired
            // code carries no identity of its own, which is exactly the
            // security-relevant fact worth keeping (someone tried a Telegram
            // code that didn't resolve to anyone).
            $auditLog->recordBestEffort([
                'action' => 'telegram_link_failed',
                'target_type' => 'telegram_link_code',
                'metadata' => ['telegram_user_id' => $message['from']['id'] ?? null],
            ]);

            $telegram->sendMessage(
                $chatId,
                "⚠️ This link code has expired or is invalid. Please go back to your Platform profile page and tap \"Connect Telegram\" again."
            );

            return;
        }

        $auditLog->recordBestEffort([
            'actor_user_id' => $linkedUserId,
            'action' => 'telegram_link_completed',
            'target_user_id' => $linkedUserId,
            'target_type' => 'user',
            'target_id' => $linkedUserId,
        ]);

        $telegram->sendMessage(
            $chatId,
            "✅ <b>Telegram connected to your Performix Platform account!</b>\nYou'll now get KPI reminders here, scoped to exactly what you're authorized to see."
        );
    }
}
