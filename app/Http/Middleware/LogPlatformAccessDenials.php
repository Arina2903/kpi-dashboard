<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "API activity" (requirement #8's own category), scoped deliberately narrow:
 * this logs 401/403 responses on `/platform/*` routes, not every request.
 *
 * Logging every successful GET would be pure noise at Platform scale (every
 * page load, every sidebar re-fetch) with no investigative value — every
 * meaningful WRITE already gets its own specific, richer log entry via
 * `LogsAdminActions` (create_kpi, suspend_company, etc.), so duplicating
 * those here as generic "PATCH /platform/..." rows would just be the same
 * fact recorded twice, once with detail and once without. What's missing
 * without this middleware is the one signal neither of those capture: someone
 * *tried* to reach something they weren't allowed to — a stale link, a
 * probing request, a permission edge case worth investigating — which never
 * reaches a controller's own logging call because the controller (or RLS)
 * rejected it first.
 *
 * Best-effort by construction (`recordBestEffort`) — a logging hiccup must
 * never turn an already-correct 403 into a 500.
 */
class LogPlatformAccessDenials
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($response->getStatusCode(), [401, 403], true)) {
            $platformUser = $request->attributes->get('platformUser');

            app(AuditLogService::class)->recordBestEffort([
                'actor_user_id' => $platformUser['id'] ?? null,
                'actor_email' => $platformUser['email'] ?? null,
                'action' => 'access_denied',
                'target_type' => 'route',
                'metadata' => [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'status' => $response->getStatusCode(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }
}
