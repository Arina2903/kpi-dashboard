<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = env('TELEGRAM_WEBHOOK_SECRET', '');
        $provided = $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            abort(403, 'Invalid webhook secret.');
        }

        return $next($request);
    }
}
