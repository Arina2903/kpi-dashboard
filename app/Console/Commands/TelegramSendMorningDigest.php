<?php

namespace App\Console\Commands;

use App\Services\TelegramDigestService;
use Illuminate\Console\Command;

class TelegramSendMorningDigest extends Command
{
    protected $signature = 'telegram:send-morning-digest';

    protected $description = 'Sends the 8:30am "set your daily to-do" reminder to all linked Telegram users';

    public function handle(TelegramDigestService $digest): int
    {
        $this->info('Sending morning digest…');
        $sent = $digest->sendMorning();
        $this->info("Sent {$sent} messages.");

        return self::SUCCESS;
    }
}
