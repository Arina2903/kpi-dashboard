<?php

namespace Tests\Feature;

use App\Services\AuditLogService;
use App\Services\PlatformTelegramDigestService;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The concrete replacement for the legacy TelegramDigestService::broadcast(),
 * which sends the identical message to every linked chat_id with no company,
 * role, or status check at all. This proves the two properties CLAUDE.md's
 * Telegram security model asks for: a suspended user is skipped (not sent a
 * generic reminder), and a message actually sent to Telegram's API never
 * contains another company's name — only what that specific linked user's
 * own RLS-filtered scope returned.
 */
class PlatformTelegramDigestServiceTest extends TestCase
{
    private function fakeSessionToken(): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'user-1', 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    public function test_suspended_linked_user_is_skipped_not_sent_a_reminder(): void
    {
        // First `users` call lists linked users for the broadcast loop;
        // second is TelegramAuthorizedScope's fresh identity re-lookup for
        // that same user, returning it as suspended.
        Http::fake([
            '*/rest/v1/users*' => Http::sequence()
                ->push([[
                    'id' => 'user-1', 'telegram_user_id' => 111, 'telegram_chat_id' => 111,
                ]], 200)
                ->push([[
                    'id' => 'user-1', 'email' => 'suspended@example.com', 'status' => 'suspended',
                ]], 200),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $service = new PlatformTelegramDigestService(new SupabaseService(), new SupabaseAuthService(), new TelegramService(), new AuditLogService(new SupabaseService()));

        $result = $service->sendMorning();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, $result['skipped']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_active_linked_user_with_authorized_data_is_sent_a_scoped_message(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::sequence()
                ->push([[
                    'id' => 'user-1', 'telegram_user_id' => 222, 'telegram_chat_id' => 222,
                ]], 200)
                // TelegramAuthorizedScope's own identity+status check
                ->push([[
                    'id' => 'user-1', 'email' => 'active@example.com', 'status' => 'active',
                ]], 200)
                // AuthorizedDataScope::me(), called from assistantContext()
                ->push([[
                    'id' => 'user-1', 'name' => 'Jamie', 'email' => 'active@example.com',
                    'role' => 'member', 'status' => 'active',
                ]], 200),
            '*/auth/v1/admin/generate_link*' => Http::response(['hashed_token' => 'th_x'], 200),
            '*/auth/v1/verify*' => Http::response(['access_token' => $this->fakeSessionToken()], 200),
            '*/rest/v1/companies*' => Http::response([['id' => 'company-a', 'name' => 'Andalusia', 'code' => 'AND', 'status' => 'active']], 200),
            '*/rest/v1/departments*' => Http::response([], 200),
            '*/rest/v1/kpis*' => Http::response([['id' => 'kpi-1', 'name' => 'Revenue']], 200),
            '*/rest/v1/kpi_submissions*' => Http::response([], 200),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $service = new PlatformTelegramDigestService(new SupabaseService(), new SupabaseAuthService(), new TelegramService(), new AuditLogService(new SupabaseService()));

        $result = $service->sendMorning();

        $this->assertSame(1, $result['sent']);
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'api.telegram.org')) {
                return false;
            }

            $text = $request['text'] ?? '';

            return str_contains($text, 'Andalusia') && !str_contains($text, 'VFive');
        });
    }
}
