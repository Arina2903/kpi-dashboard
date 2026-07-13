<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramDigestService;
use App\Services\TelegramReviewService;
use Illuminate\Http\Request;

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

    public function review(Request $request, TelegramReviewService $reviews, string $period)
    {
        if (!in_array($period, ['weekly', 'monthly', 'quarterly'])) {
            return response()->json(['ok' => false, 'message' => 'Invalid period.'], 422);
        }

        return response()->json(['ok' => true, 'generated' => $reviews->generate($period)]);
    }
}
