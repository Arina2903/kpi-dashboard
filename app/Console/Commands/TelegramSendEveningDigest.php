<?php

namespace App\Console\Commands;

use App\Jobs\SendEveningTelegramDigest as SendEveningTelegramDigestJob;
use Illuminate\Console\Command;

class TelegramSendEveningDigest extends Command
{
    protected $signature = 'telegram:send-evening-digest';

    protected $description = 'Sends the 5:30pm "update your progress" reminder to all linked Telegram users';

    public function handle(): int
    {
        $this->info('Queuing evening digest…');
        SendEveningTelegramDigestJob::dispatch();
        $this->info('Queued.');

        return self::SUCCESS;
    }
}
