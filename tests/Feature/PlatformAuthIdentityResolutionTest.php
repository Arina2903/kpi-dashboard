<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression test for a real bug found while walking through the app as an
 * actual authenticated Super Admin: `PlatformAuth` used to resolve "who is
 * logged in" via `$supabase->first('users', ['select' => '...'])` with NO
 * filter, relying entirely on RLS to narrow the result to "just me." That
 * assumption is false for a Super Admin (RLS exposes every row in `users` to
 * them) and for a Company Admin (RLS exposes every user in their own
 * company) — `first()` with no filter and no explicit order just returns
 * whatever row PostgREST happens to return first out of that visible set,
 * which is only "yourself" by coincidence. In production this manifested as
 * one Super Admin's session resolving to a COMPLETELY DIFFERENT Super
 * Admin's identity.
 *
 * This is exactly the kind of bug mocked single-row HTTP fakes can never
 * catch — every other test in this suite fakes the users REST endpoint with
 * exactly one row, which trivially "resolves correctly" regardless of
 * whether the filter is applied. This test deliberately fakes MULTIPLE
 * visible rows, with the WRONG one listed first, to prove the fix filters on
 * the token's own identity rather than trusting result order.
 */
class PlatformAuthIdentityResolutionTest extends TestCase
{
    private function fakeSessionToken(string $sub): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => $sub, 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    public function test_platform_auth_resolves_the_callers_own_row_not_an_arbitrary_visible_one(): void
    {
        // Simulates two Super Admins, both visible to each other under RLS.
        // The caller's token belongs to 'user-a-auth-id'; 'user-b' is listed
        // FIRST in the unfiltered case to catch a regression to the old
        // "just take whatever comes back" behavior.
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'auth_user_id=eq.user-a-auth-id')) {
                return Http::response([[
                    'id' => 'user-a', 'name' => 'Correct Caller', 'email' => 'caller@example.com',
                    'role' => 'richworks_super_admin', 'status' => 'active',
                ]], 200);
            }

            if (str_contains($url, '/rest/v1/users') && !str_contains($url, 'auth_user_id')) {
                // The old, buggy shape of the request: no identity filter at
                // all. Returns the WRONG user first, exactly like RLS's
                // unordered visible set would for a Super Admin.
                return Http::response([
                    ['id' => 'user-b', 'name' => 'Wrong Other Admin', 'email' => 'other-admin@example.com',
                        'role' => 'richworks_super_admin', 'status' => 'active'],
                    ['id' => 'user-a', 'name' => 'Correct Caller', 'email' => 'caller@example.com',
                        'role' => 'richworks_super_admin', 'status' => 'active'],
                ], 200);
            }

            if (str_contains($url, '/rest/v1/platform_admin_assignments')) {
                return Http::response([], 200);
            }

            return Http::response([], 200);
        });

        $response = $this->withSession(['platform_access_token' => $this->fakeSessionToken('user-a-auth-id')])
            ->get('/platform/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('me.id', 'user-a')
            ->where('me.email', 'caller@example.com')
        );
    }
}
