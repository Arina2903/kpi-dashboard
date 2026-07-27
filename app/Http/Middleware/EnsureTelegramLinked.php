<?php

namespace App\Http\Middleware;

use App\Services\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the Mini App's API routes — the page itself already gates on this
 * (see MiniAppController::index rendering the connect-gate view instead of
 * the app), but these direct JSON endpoints need their own check in case
 * they're ever called without going through the page first.
 */
class EnsureTelegramLinked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = app(SupabaseService::class)->first('users', [
            'id' => 'eq.' . session('user_uuid'),
            'select' => 'telegram_linked_at',
        ]);

        if (empty($user['telegram_linked_at'])) {
            return response()->json([
                'success' => false,
                'message' => 'Connect your Telegram account first.',
            ], 403);
        }

        return $next($request);
    }
}
