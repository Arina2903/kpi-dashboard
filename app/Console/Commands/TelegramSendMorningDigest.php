<?php

namespace App\Console\Commands;

use App\Jobs\SendMorningTelegramDigest as SendMorningTelegramDigestJob;
use Illuminate\Console\Command;

class TelegramSendMorningDigest extends Command
{
    protected $signature = 'telegram:send-morning-digest';

    protected $description = 'Sends the 8:30am "set your daily to-do" reminder to all linked Telegram users';

    public function handle(): int
    {
        $this->info('Queuing morning digest…');
        SendMorningTelegramDigestJob::dispatch();
        $this->info('Queued.');

        return self::SUCCESS;
    }
}
