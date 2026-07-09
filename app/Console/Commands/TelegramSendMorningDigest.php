<?php

namespace App\Console\Commands;

use App\Services\SupabaseService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSendMorningDigest extends Command
{
    protected $signature = 'telegram:send-morning-digest';

    protected $description = 'Sends the 8:30am "set your daily to-do" reminder to all linked Telegram users';

    public function handle(SupabaseService $supabase, TelegramService $telegram): int
    {
        $users = $supabase->get('users', [
            'telegram_chat_id' => 'not.is.null',
            'select' => 'id,telegram_chat_id',
        ]) ?? [];

        $this->info('Sending morning digest to ' . count($users) . ' linked users…');

        $appUrl = rtrim(env('APP_URL'), '/');
        $sent = 0;

        foreach ($users as $user) {
            try {
                $telegram->sendMessage(
                    (int) $user['telegram_chat_id'],
                    "📝 <b>Good morning!</b>\nTime to set your to-do list for today. Pick the KPIs you'll work on and set today's target.",
                    $telegram->webAppButton("📝 Set Today's To-Do", $appUrl . '/telegram/app?screen=morning')
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
