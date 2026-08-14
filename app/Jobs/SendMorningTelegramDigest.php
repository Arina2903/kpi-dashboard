<?php

namespace App\Jobs;

use App\Services\TelegramDigestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendMorningTelegramDigest implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(TelegramDigestService $digest): void
    {
        $sent = $digest->sendMorning();

        Log::info("Morning Telegram digest sent to {$sent} users.");
    }
}
