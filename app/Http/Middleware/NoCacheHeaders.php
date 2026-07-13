<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Prevents the browser from ever serving a cached copy of a response —
 * used on pages whose editability depends on live, frequently-changing
 * server state (e.g. appraisal status), where a stale cached page can
 * look identical to a real bug.
 */
class NoCacheHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
