<?php

namespace Tests\Feature;

use App\Services\PlatformTelegramLinkService;
use App\Services\SupabaseService;
use App\Services\SupabaseUserService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `generateCode()` runs entirely through the caller's own token (an
 * ordinary RLS-respecting self-update, no elevated access). `completeLink()`
 * necessarily uses the service-role client (the Telegram webhook has no
 * session), so what matters here is that it's narrowly scoped — a code
 * resolves to exactly the one user it was minted for, and an
 * expired/unknown code links nobody.
 */
class PlatformTelegramLinkServiceTest extends TestCase
{
    public function test_generate_code_writes_to_the_callers_own_row_via_their_own_token(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([['id' => 'user-1']], 200),
        ]);

        $service = new PlatformTelegramLinkService();
        $result = $service->generateCode(new SupabaseUserService('caller-own-token'), 'user-1');

        $this->assertNotEmpty($result['code']);
        $this->assertNotEmpty($result['expires_at']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/users')
                && $request->method() === 'PATCH'
                && str_contains($request->url(), 'id=eq.user-1')
                && ($request['telegram_link_code'] ?? null) !== null;
        });
    }

    public function test_complete_link_with_a_valid_code_links_the_matching_user(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([['id' => 'user-1']], 200),
        ]);

        $service = new PlatformTelegramLinkService();
        $linked = $service->completeLink(new SupabaseService(), 'ABC12345', 999, 999, 'someuser');

        $this->assertSame('user-1', $linked);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/users')
                && $request->method() === 'PATCH'
                && ($request['telegram_user_id'] ?? null) === 999;
        });
    }

    public function test_complete_link_with_an_unknown_or_expired_code_links_nobody(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([], 200),
        ]);

        $service = new PlatformTelegramLinkService();
        $linked = $service->completeLink(new SupabaseService(), 'DOES-NOT-EXIST', 999, 999, null);

        $this->assertNull($linked);

        Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');
    }
}
