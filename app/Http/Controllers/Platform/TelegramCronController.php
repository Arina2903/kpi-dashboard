<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\PlatformTelegramDigestService;
use Illuminate\Http\Request;

/**
 * Cron-triggered entry points for the tenant-aware Telegram digests — the
 * Platform equivalent of Telegram\TelegramCronController, guarded by the
 * same shared-secret middleware (a secret check has no tenant dimension of
 * its own to duplicate). Kept as a distinct controller/route prefix rather
 * than extending the legacy one so nothing here shares a code path with the
 * dead legacy broadcast logic.
 */
class TelegramCronController extends Controller
{
    public function morning(Request $request, PlatformTelegramDigestService $digest)
    {
        return response()->json($digest->sendMorning());
    }

    public function evening(Request $request, PlatformTelegramDigestService $digest)
    {
        return response()->json($digest->sendEvening());
    }
}
