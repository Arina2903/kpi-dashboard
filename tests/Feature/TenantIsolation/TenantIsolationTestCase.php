<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Requirement #10: "Don't just manually test 'Andalusia can't see VFive' —
 * create automated tests that continuously try [specific attacks] and expect
 * DENIED." This directory is one half of that — every test here plays the
 * same character, a Company Admin who legitimately administers ONE company
 * and tries to reach another, and proves the application layer refuses
 * before any cross-tenant Supabase REST call is even attempted.
 *
 * WHAT THIS SUITE DOES AND DOESN'T PROVE. Every request here goes through
 * `Http::fake()`, never a real Postgres connection — nothing in this
 * directory can prove Postgres RLS itself refuses a forged request, only
 * that this application never sends one in the first place (see the
 * `real-walkthrough-over-mocks` lesson: mocked tests miss real RLS/PostgREST
 * bugs). The complementary, REAL-RLS half of requirement #10 is
 * `database/rls-tests/tenant_isolation.sql`, run against an actual Postgres
 * engine — evaluating this project's own policies for real — by the
 * `tenant-isolation` job in `.github/workflows/ci.yml` on every push.
 * Together the two prove both halves of "Company A cannot reach Company B":
 * the app never tries, and the database would refuse it even if the app
 * ever got that wrong. Neither one alone is the whole story; treat a green
 * run of only this directory as "the app-layer guard holds," not as proof
 * the tenant boundary itself is intact.
 */
abstract class TenantIsolationTestCase extends TestCase
{
    protected function fakeToken(string $authUserId): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => $authUserId, 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    /**
     * A Company Admin of exactly ONE company (`$companyId`), with no
     * membership anywhere else — the base fixture every "reach across the
     * boundary" test in this suite starts from. `$userId` doubles as the
     * fake auth subject and the `public.users.id` for simplicity; nothing
     * here depends on them being different.
     */
    protected function fakeCompanyAdminSessionFakes(string $userId, string $companyId): array
    {
        return [
            '*/rest/v1/users*' => Http::response([[
                'id' => $userId, 'name' => 'Admin', 'email' => $userId . '@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([[
                'company_id' => $companyId, 'role' => 'company_admin', 'status' => 'active',
                'companies' => ['name' => 'Company', 'code' => 'CO'],
            ]], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
            // KpiSubmissionController::ensureDepartmentAccess() calls this
            // RPC unconditionally, even for a caller about to be rejected —
            // it decides the UI's "canSubmit" flag, not the authorization
            // outcome itself, so leaving it unfaked would send a real
            // network request under Http::fake() (a non-wildcard fake array
            // only stubs the patterns you give it; anything else is NOT
            // given a default response and goes out for real).
            '*/rest/v1/rpc/auth_department_ids*' => Http::response([], 200),
        ];
    }

    protected function fakeSuperAdminSessionFakes(string $userId): array
    {
        return [
            '*/rest/v1/users*' => Http::response([[
                'id' => $userId, 'name' => 'Center', 'email' => $userId . '@example.com',
                'role' => 'richworks_super_admin', 'status' => 'active',
            ]], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
        ];
    }

    /**
     * Asserts nothing ever READ the given Supabase table — the strongest
     * form of "never even attempted." Deliberately GET-only: `admin_action_logs`
     * specifically is expected to see a WRITE on a denied request (the
     * `platform.audit` middleware logging the very `access_denied` event
     * this test is causing) — that's the security system working correctly,
     * not a leak, and would make a method-agnostic assertion here fail on
     * the one endpoint it's most important to get right.
     */
    protected function assertNoRequestReached(string $table): void
    {
        Http::assertNotSent(fn ($request) => str_contains($request->url(), "/rest/v1/{$table}") && $request->method() === 'GET');
    }
}
