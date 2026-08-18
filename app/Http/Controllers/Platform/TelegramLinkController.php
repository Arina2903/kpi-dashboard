<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\LogsAdminActions;
use App\Services\PlatformTelegramLinkService;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;

/**
 * The Platform-side half of Telegram linking — reachable only by an
 * authenticated Platform user, acting only on their own `users` row via
 * their own token (`platformSupabase`, set by PlatformAuth). Never touches
 * the service-role client; that half only exists in the Telegram webhook,
 * which has no session of its own (see PlatformTelegramLinkService).
 */
class TelegramLinkController extends Controller
{
    use LogsAdminActions;

    public function generateCode(Request $request, PlatformTelegramLinkService $links)
    {
        $me = $request->attributes->get('platformUser');
        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $result = $links->generateCode($supabase, $me['id']);

        $this->logBestEffort($request, 'telegram_link_code_generated', null, $me['id'], [], 'user', $me['id']);

        return response()->json([
            'code' => $result['code'],
            'expires_at' => $result['expires_at'],
            'bot_deep_link' => 'https://t.me/' . env('TELEGRAM_BOT_USERNAME') . '?start=pf_' . $result['code'],
        ]);
    }

    public function disconnect(Request $request, PlatformTelegramLinkService $links)
    {
        $me = $request->attributes->get('platformUser');
        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $links->disconnect($supabase, $me['id']);

        $this->logBestEffort($request, 'telegram_disconnected', null, $me['id'], [], 'user', $me['id']);

        return back()->with('success', 'Telegram disconnected.');
    }
}
