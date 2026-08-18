<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * "Login/logout" (requirement #8) — the one category with no authenticated
 * Postgres session to piggyback on: a failed login has no Supabase session
 * at all (that's the whole point of it failing), so `actor_email` is the
 * only identity to record. Everything here is best-effort by construction
 * (`AuditLogService::recordBestEffort()`) — the two things that matter are
 * that a logging failure never turns a wrong-password 302 into a 500, and
 * that a successful login is still attributed to the real resolved user, not
 * just the email the form was submitted with.
 */
class AuthControllerAuditLoggingTest extends TestCase
{
    public function test_a_failed_login_logs_the_attempted_email_without_breaking_the_response(): void
    {
        Http::fake([
            '*/auth/v1/token*' => Http::response(['error' => 'invalid_grant'], 400),
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
        ]);

        $response = $this->post('/platform/login', [
            'email' => 'nope@example.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHas('error', 'Invalid email or password.');

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/admin_action_logs') || $request->method() !== 'POST') {
                return false;
            }

            return $request['action'] === 'login_failed' && $request['actor_email'] === 'nope@example.com';
        });
    }

    public function test_a_failed_login_still_returns_the_normal_error_even_if_logging_itself_fails(): void
    {
        Http::fake([
            '*/auth/v1/token*' => Http::response(['error' => 'invalid_grant'], 400),
            '*/rest/v1/admin_action_logs*' => Http::response(['message' => 'db is down'], 500),
        ]);

        $response = $this->post('/platform/login', [
            'email' => 'nope@example.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHas('error', 'Invalid email or password.');
        $response->assertStatus(302);
    }

    public function test_a_successful_login_logs_the_resolved_user(): void
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'auth-id-1', 'role' => 'authenticated'])), '+/', '-_'), '=');
        $accessToken = "{$header}.{$payload}.fake-signature";

        Http::fake([
            '*/auth/v1/token*' => Http::response([
                'access_token' => $accessToken,
                'refresh_token' => 'refresh-1',
                'user' => ['id' => 'auth-id-1', 'email' => 'admin@example.com'],
            ], 200),
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-1', 'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
        ]);

        $this->post('/platform/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/admin_action_logs') || $request->method() !== 'POST') {
                return false;
            }

            return $request['action'] === 'login'
                && $request['actor_user_id'] === 'user-1'
                && $request['actor_email'] === 'admin@example.com';
        });
    }
}
