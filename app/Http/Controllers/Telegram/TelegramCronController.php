<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramDigestService;

class TelegramCronController extends Controller
{
    public function morning(TelegramDigestService $digest)
    {
        return response()->json(['ok' => true, 'sent' => $digest->sendMorning()]);
    }

    public function evening(TelegramDigestService $digest)
    {
        return response()->json(['ok' => true, 'sent' => $digest->sendEvening()]);
    }
}
