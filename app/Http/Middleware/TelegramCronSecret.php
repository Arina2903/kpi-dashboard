<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramCronSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = env('TELEGRAM_CRON_SECRET', '');
        $provided = $request->header('X-Cron-Secret', '');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            abort(403, 'Invalid cron secret.');
        }

        return $next($request);
    }
}
