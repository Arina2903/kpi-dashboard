<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The property this covers: an unauthorized `company_id` must be REJECTED,
 * never silently dropped to "no filter." Falling back silently would still
 * leak "this id exists and is a company" through the absence of an error,
 * and would widen the response to every company the caller can see instead
 * of the one they explicitly (and wrongly) asked about.
 *
 * `PlatformAuth` re-resolves the caller's identity via a live Supabase call
 * on every request, so getting *past* it in a test means faking that call —
 * `fakeAuthenticatedSession()` does the minimum needed (a `users` row and an
 * empty `company_users` list) so `AniraController`'s own logic is what's
 * actually under test, not the middleware.
 */
class AniraControllerTest extends TestCase
{
    /**
     * A syntactically-real (but unsigned, never verified locally) JWT shaped
     * like a genuine Supabase Auth access token — `role: authenticated`, not
     * `service_role`. AuthorizedDataScope's own guard rejects anything that
     * doesn't decode to a JWT payload without that role, which a bare string
     * like 'fake-token' correctly trips (confirmed while writing this test)
     * — this exists so these tests exercise AniraController's logic, not
     * that unrelated guard.
     */
    private function fakeUserToken(): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'user-under-test', 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    private function fakeAuthenticatedSession(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-under-test', 'name' => 'Jamie Test', 'email' => 'jamie@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
        ]);
    }

    public function test_chat_without_a_platform_session_redirects_to_login(): void
    {
        $response = $this->postJson('/platform/ai/chat', ['message' => 'How is my KPI doing?']);

        // PlatformAuth middleware itself is what stops this, before the
        // controller ever runs — the endpoint requires a genuine session,
        // full stop.
        $response->assertRedirect(route('platform.login'));
    }

    public function test_index_without_a_platform_session_redirects_to_login(): void
    {
        $response = $this->get('/platform/anira');

        $response->assertRedirect(route('platform.login'));
    }

    public function test_chat_validates_the_message_field(): void
    {
        $this->fakeAuthenticatedSession();

        $response = $this->withSession(['platform_access_token' => $this->fakeUserToken()])
            ->postJson('/platform/ai/chat', []);

        $response->assertStatus(422);
    }

    public function test_chat_validates_company_id_is_a_uuid(): void
    {
        $this->fakeAuthenticatedSession();

        $response = $this->withSession(['platform_access_token' => $this->fakeUserToken()])
            ->postJson('/platform/ai/chat', [
                'message' => 'hello',
                'company_id' => 'not-a-uuid',
            ]);

        $response->assertStatus(422);
    }

    public function test_chat_rejects_a_company_id_the_caller_is_not_authorized_for(): void
    {
        $this->fakeAuthenticatedSession();

        // The caller's own authorized company list (via AuthorizedDataScope
        // -> SupabaseUserService -> the "companies" endpoint) is empty, so
        // ANY company_id they supply must be refused.
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-under-test', 'name' => 'Jamie Test', 'email' => 'jamie@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            '*/rest/v1/companies*' => Http::response([], 200),
        ]);

        $response = $this->withSession(['platform_access_token' => $this->fakeUserToken()])
            ->postJson('/platform/ai/chat', [
                'message' => 'How is Andalusia doing?',
                'company_id' => '11111111-1111-1111-1111-111111111111',
            ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }
}
