<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Platform-side linking endpoints — reachable only by an authenticated
 * Platform user, same guard shape as AniraControllerTest.
 */
class TelegramLinkControllerTest extends TestCase
{
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

    public function test_generate_code_without_a_platform_session_redirects_to_login(): void
    {
        $response = $this->postJson('/platform/telegram/link-code');

        $response->assertRedirect(route('platform.login'));
    }

    public function test_generate_code_writes_a_code_to_the_authenticated_users_own_row(): void
    {
        $this->fakeAuthenticatedSession();
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-under-test', 'name' => 'Jamie Test', 'email' => 'jamie@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
        ]);

        $response = $this->withSession(['platform_access_token' => $this->fakeUserToken()])
            ->postJson('/platform/telegram/link-code');

        $response->assertOk();
        $response->assertJsonStructure(['code', 'expires_at', 'bot_deep_link']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/users')
                && $request->method() === 'PATCH'
                && str_contains($request->url(), 'id=eq.user-under-test');
        });
    }
}
