<?php

namespace App\Console\Commands;

use App\Services\TelegramDigestService;
use Illuminate\Console\Command;

class TelegramSendEveningDigest extends Command
{
    protected $signature = 'telegram:send-evening-digest';

    protected $description = 'Sends the 5:30pm "update your progress" reminder to all linked Telegram users';

    public function handle(TelegramDigestService $digest): int
    {
        $this->info('Sending evening digest…');
        $sent = $digest->sendEvening();
        $this->info("Sent {$sent} messages.");

        return self::SUCCESS;
    }
}
