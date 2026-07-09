<?php

namespace App\Console\Commands;

use App\Services\SupabaseService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSendEveningDigest extends Command
{
    protected $signature = 'telegram:send-evening-digest';

    protected $description = 'Sends the 5:30pm "update your progress" reminder to all linked Telegram users';

    public function handle(SupabaseService $supabase, TelegramService $telegram): int
    {
        $users = $supabase->get('users', [
            'telegram_chat_id' => 'not.is.null',
            'select' => 'id,telegram_chat_id',
        ]) ?? [];

        $this->info('Sending evening digest to ' . count($users) . ' linked users…');

        $appUrl = rtrim(env('APP_URL'), '/');
        $sent = 0;

        foreach ($users as $user) {
            try {
                $telegram->sendMessage(
                    (int) $user['telegram_chat_id'],
                    "📈 <b>How did today go?</b>\nUpdate your progress on today's KPI tasks — this will automatically update your KPI actuals.",
                    $telegram->webAppButton('📈 Update My Progress', $appUrl . '/telegram/app?screen=evening')
                );
                $sent++;
            } catch (\Throwable $e) {
                $this->warn("Failed to notify user {$user['id']}: {$e->getMessage()}");
            }

            usleep(50000);
        }

        $this->info("Sent {$sent}/" . count($users) . ' messages.');

        return self::SUCCESS;
    }
}
