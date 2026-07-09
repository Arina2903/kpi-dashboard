<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, SupabaseService $supabase, TelegramService $telegram)
    {
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
}
