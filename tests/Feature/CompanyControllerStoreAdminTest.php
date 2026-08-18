<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regression test for two real, compounding bugs found while walking through
 * the app as an actual Super Admin:
 *
 * 1. `CompanyController::storeAdmin()` read the new account's id from
 *    `$invite['user']['id']`, but Supabase's real `admin/generate_link`
 *    response has `id` at the TOP level — there is no `user` key at all.
 *    That path was always null, so every admin invite failed with "the new
 *    account id was missing" AFTER already creating a real Supabase Auth
 *    user, leaving an orphaned account with no `company_users` row.
 * 2. Even after fixing #1, the subsequent poll for the matching
 *    `public.users` row filtered on `id = eq.<authUserId>` — but that id is
 *    auth.users.id, stored on `public.users` as `auth_user_id`, a totally
 *    separate independently-generated column from `public.users.id`. The
 *    poll always found nothing and the invite still failed, just with a
 *    different error message.
 *
 * The same two bugs existed in DepartmentController::storeUser() and
 * UserCreationController::store(). No test had ever exercised this path with
 * a realistic response shape — exactly the kind of gap only a real,
 * non-mocked walkthrough surfaces.
 */
class CompanyControllerStoreAdminTest extends TestCase
{
    private function fakeSuperAdminToken(): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'super-admin-auth-id', 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    public function test_store_admin_links_the_new_user_using_the_real_flat_response_shape(): void
    {
        Mail::fake();

        Http::fake([
            '*/rest/v1/users*' => function ($request) {
                $url = $request->url();

                // storeAdmin() polls for the freshly-created user by
                // auth_user_id (NOT id — see class docblock) via the
                // privileged client — must resolve to the NEW user, not the
                // caller, or the test can't tell the two apart.
                if (str_contains($url, 'auth_user_id=eq.new-admin-user-id')) {
                    return Http::response([['id' => 'new-admin-public-id']], 200);
                }

                return Http::response([[
                    'id' => 'super-admin-id', 'name' => 'Super Admin', 'email' => 'super@example.com',
                    'role' => 'richworks_super_admin', 'status' => 'active',
                ]], 200);
            },
            '*/rest/v1/company_users*' => Http::response([['id' => 'cu-1']], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            // The real Supabase admin/generate_link shape: `id` at the top
            // level, no `user` wrapper.
            '*/auth/v1/admin/generate_link*' => Http::response([
                'id' => 'new-admin-user-id',
                'email' => 'newadmin@example.com',
                'hashed_token' => 'th_abc123',
            ], 200),
            '*/rest/v1/companies*' => Http::response([['id' => 'company-1', 'name' => 'QA Co']], 200),
            '*/admin_action_logs*' => Http::response([['id' => 'log-1']], 200),
        ]);

        $response = $this->withSession(['platform_access_token' => $this->fakeSuperAdminToken()])
            ->post('/platform/companies/company-1/admins', [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('error');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/company_users')
                && $request->method() === 'POST'
                && ($request['user_id'] ?? null) === 'new-admin-public-id';
        });
    }
}
