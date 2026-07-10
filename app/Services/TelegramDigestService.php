<?php

namespace App\Services;

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
        $users = $this->supabase->get('users', [
            'telegram_chat_id' => 'not.is.null',
            'select' => 'id,telegram_chat_id',
        ]) ?? [];

        $appUrl = rtrim(env('APP_URL'), '/');
        $sent = 0;

        foreach ($users as $user) {
            try {
                $this->telegram->sendMessage(
                    (int) $user['telegram_chat_id'],
                    $text,
                    $this->telegram->webAppButton($buttonLabel, $appUrl . $path)
                );
                $sent++;
            } catch (\Throwable $e) {
                // individual send failures are logged inside TelegramService; keep broadcasting
            }

            usleep(50000);
        }

        return $sent;
    }
}
