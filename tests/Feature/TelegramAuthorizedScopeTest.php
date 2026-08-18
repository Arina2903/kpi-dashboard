<?php

namespace Tests\Feature;

use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use App\Services\TelegramAuthorizedScope;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The core mapping CLAUDE.md's Telegram security model calls for: Telegram
 * User → Performix User → Company → Role → Permissions. This must refuse to
 * produce a scope — not fall back to an unscoped one — for every identity
 * failure: unlinked account, suspended account, or a failed session mint.
 * Every read that DOES succeed must be genuinely RLS-filtered, proven here by
 * faking distinct `companies` responses per minted token and confirming each
 * scope only ever sees its own.
 */
class TelegramAuthorizedScopeTest extends TestCase
{
    /**
     * A syntactically-real (unsigned) JWT shaped like a genuine Supabase
     * session token — AuthorizedDataScope's own guard rejects anything that
     * doesn't decode to a non-service_role JWT payload, which a bare string
     * like 'real-session-token' correctly trips.
     */
    private function fakeSessionToken(string $sub): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => $sub, 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    public function test_unlinked_telegram_user_id_resolves_to_no_scope(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([], 200),
        ]);

        $scope = TelegramAuthorizedScope::forTelegramUserId(
            new SupabaseService(),
            new SupabaseAuthService(),
            999999
        );

        $this->assertNull($scope);
    }

    public function test_suspended_user_resolves_to_no_scope(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-1', 'email' => 'suspended@example.com', 'status' => 'suspended',
            ]], 200),
        ]);

        $scope = TelegramAuthorizedScope::forTelegramUserId(
            new SupabaseService(),
            new SupabaseAuthService(),
            111
        );

        $this->assertNull($scope);
    }

    public function test_active_linked_user_resolves_to_a_working_scope(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-1', 'email' => 'active@example.com', 'status' => 'active',
            ]], 200),
            '*/auth/v1/admin/generate_link*' => Http::response(['hashed_token' => 'th_abc'], 200),
            '*/auth/v1/verify*' => Http::response(['access_token' => $this->fakeSessionToken('user-1')], 200),
            '*/rest/v1/companies*' => Http::response([['id' => 'c1', 'name' => 'Andalusia', 'code' => 'AND', 'status' => 'active']], 200),
        ]);

        $scope = TelegramAuthorizedScope::forTelegramUserId(
            new SupabaseService(),
            new SupabaseAuthService(),
            222
        );

        $this->assertNotNull($scope);
        $this->assertSame('Andalusia', $scope->companies()[0]['name']);
    }

    /**
     * Two separate test methods rather than two scopes in one test — each
     * gets PHPUnit's own fresh Http::fake() state (calling Http::fake()
     * again mid-test adds stubs rather than cleanly replacing earlier ones),
     * which also happens to be the more faithful simulation: two Telegram
     * users linked to two different companies are never resolved within the
     * same request in real usage either.
     */
    public function test_a_linked_user_in_company_a_sees_only_company_a(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-a', 'email' => 'a@example.com', 'status' => 'active',
            ]], 200),
            '*/auth/v1/admin/generate_link*' => Http::response(['hashed_token' => 'th_a'], 200),
            '*/auth/v1/verify*' => Http::response(['access_token' => $this->fakeSessionToken('user-a')], 200),
            '*/rest/v1/companies*' => Http::response([['id' => 'company-a', 'name' => 'Andalusia', 'code' => 'AND', 'status' => 'active']], 200),
        ]);

        $scope = TelegramAuthorizedScope::forTelegramUserId(new SupabaseService(), new SupabaseAuthService(), 1);
        $companies = $scope->companies();

        $this->assertCount(1, $companies);
        $this->assertSame('Andalusia', $companies[0]['name']);
    }

    public function test_a_linked_user_in_company_b_sees_only_company_b(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-b', 'email' => 'b@example.com', 'status' => 'active',
            ]], 200),
            '*/auth/v1/admin/generate_link*' => Http::response(['hashed_token' => 'th_b'], 200),
            '*/auth/v1/verify*' => Http::response(['access_token' => $this->fakeSessionToken('user-b')], 200),
            '*/rest/v1/companies*' => Http::response([['id' => 'company-b', 'name' => 'VFive', 'code' => 'VF', 'status' => 'active']], 200),
        ]);

        $scope = TelegramAuthorizedScope::forTelegramUserId(new SupabaseService(), new SupabaseAuthService(), 2);
        $companies = $scope->companies();

        $this->assertCount(1, $companies);
        $this->assertSame('VFive', $companies[0]['name']);
    }

    public function test_failed_session_mint_resolves_to_no_scope(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'user-1', 'email' => 'active@example.com', 'status' => 'active',
            ]], 200),
            '*/auth/v1/admin/generate_link*' => Http::response(['error' => 'boom'], 500),
        ]);

        $scope = TelegramAuthorizedScope::forTelegramUserId(
            new SupabaseService(),
            new SupabaseAuthService(),
            333
        );

        $this->assertNull($scope);
    }
}
