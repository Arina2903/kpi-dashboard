<?php

namespace Tests\Feature\TenantIsolation;

use App\Services\PlatformTelegramDigestService;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use App\Services\TelegramService;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Http;

/**
 * Attack #10: "Query through Telegram." The existing
 * `PlatformTelegramDigestServiceTest` proves isolation one linked user at a
 * time; this test strengthens that by running TWO linked users — one per
 * company — through the SAME `broadcast()` call, the way a real cron tick
 * actually does it. That's a materially different, stronger claim: it rules
 * out any bug where state leaks or gets reused ACROSS iterations of the
 * broadcast loop (a cached scope, a stale token, an off-by-one on which
 * user's data was just fetched) — the kind of bug that a "one user, one
 * test" setup can't expose no matter how many times it's run.
 */
class QueryThroughTelegramTest extends TenantIsolationTestCase
{
    public function test_two_linked_users_in_two_companies_never_cross_contaminate_within_one_broadcast_run(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            // The broadcast loop's own "who is linked at all" query.
            if (str_contains($url, '/rest/v1/users') && str_contains($url, 'telegram_user_id=not.is.null')) {
                return Http::response([
                    ['id' => 'user-a', 'telegram_user_id' => 111, 'telegram_chat_id' => 111],
                    ['id' => 'user-b', 'telegram_user_id' => 222, 'telegram_chat_id' => 222],
                ], 200);
            }

            // TelegramAuthorizedScope's per-user identity+status lookup.
            if (str_contains($url, '/rest/v1/users') && str_contains($url, 'telegram_user_id=eq.111')) {
                return Http::response([['id' => 'user-a', 'email' => 'user-a@example.com', 'status' => 'active']], 200);
            }
            if (str_contains($url, '/rest/v1/users') && str_contains($url, 'telegram_user_id=eq.222')) {
                return Http::response([['id' => 'user-b', 'email' => 'user-b@example.com', 'status' => 'active']], 200);
            }

            // AuthorizedDataScope::me(), called from assistantContext() —
            // keyed by which minted session's sub claim is asking.
            if (str_contains($url, '/rest/v1/users') && str_contains($url, 'auth_user_id=eq.user-a-auth-id')) {
                return Http::response([['id' => 'user-a', 'name' => 'A', 'email' => 'user-a@example.com', 'role' => 'member', 'status' => 'active']], 200);
            }
            if (str_contains($url, '/rest/v1/users') && str_contains($url, 'auth_user_id=eq.user-b-auth-id')) {
                return Http::response([['id' => 'user-b', 'name' => 'B', 'email' => 'user-b@example.com', 'role' => 'member', 'status' => 'active']], 200);
            }

            // Mint a session: generate_link keyed by email, verify keyed by
            // the hashed_token that generate_link just handed back — this is
            // what lets two DIFFERENT, DISTINGUISHABLE sessions exist within
            // one broadcast() call instead of accidentally sharing one.
            if (str_contains($url, '/auth/v1/admin/generate_link')) {
                $email = $request['email'] ?? '';

                return Http::response(['hashed_token' => 'th_' . $email], 200);
            }

            if (str_contains($url, '/auth/v1/verify')) {
                $tokenHash = $request['token_hash'] ?? '';
                $sub = str_contains($tokenHash, 'user-a') ? 'user-a-auth-id' : 'user-b-auth-id';

                return Http::response(['access_token' => $this->fakeToken($sub)], 200);
            }

            // Company/KPI reads — RLS-scoped by whichever minted token is
            // asking, simulated here by inspecting the Authorization header
            // the same way real PostgREST would use it to evaluate RLS.
            $bearer = $request->header('Authorization')[0] ?? '';
            $isUserA = str_contains($bearer, $this->fakeToken('user-a-auth-id'));

            if (str_contains($url, '/rest/v1/companies')) {
                return $isUserA
                    ? Http::response([['id' => 'company-a', 'name' => 'Andalusia', 'code' => 'AND', 'status' => 'active']], 200)
                    : Http::response([['id' => 'company-b', 'name' => 'VFive', 'code' => 'VF', 'status' => 'active']], 200);
            }

            if (str_contains($url, '/rest/v1/kpis')) {
                return $isUserA
                    ? Http::response([['id' => 'kpi-a', 'name' => 'Andalusia Revenue']], 200)
                    : Http::response([['id' => 'kpi-b', 'name' => 'VFive Revenue']], 200);
            }

            if (str_contains($url, '/rest/v1/departments') || str_contains($url, '/rest/v1/kpi_submissions')) {
                return Http::response([], 200);
            }

            if (str_contains($url, 'api.telegram.org')) {
                return Http::response(['ok' => true], 200);
            }

            return Http::response([], 200);
        });

        $service = new PlatformTelegramDigestService(
            new SupabaseService(),
            new SupabaseAuthService(),
            new TelegramService(),
            new AuditLogService(new SupabaseService()),
        );

        $result = $service->sendMorning();

        $this->assertSame(2, $result['sent']);

        // The actual proof: whichever message went to chat 111 (user A)
        // must mention Andalusia and NEVER VFive, and vice versa for chat
        // 222 — checked against the REAL outgoing Telegram API calls, not
        // against intermediate data, so a leak anywhere in the pipeline
        // (not just the final text-building step) would show up here.
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'api.telegram.org') || ($request['chat_id'] ?? null) != 111) {
                return false;
            }

            return str_contains($request['text'], 'Andalusia') && !str_contains($request['text'], 'VFive');
        });

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'api.telegram.org') || ($request['chat_id'] ?? null) != 222) {
                return false;
            }

            return str_contains($request['text'], 'VFive') && !str_contains($request['text'], 'Andalusia');
        });
    }
}
