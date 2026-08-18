<?php

namespace Tests\Feature;

use App\Services\TelegramDigestService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression test for the tenant-isolation audit finding (2026-08-18):
 * TelegramDigestService::broadcast() used to query `users.telegram_chat_id`
 * with no company or suspension scoping at all. That column is now shared
 * with the tenant-aware Platform Telegram-linking feature, so this legacy
 * path became live and unsafe the moment anyone linked Telegram through
 * `/platform/profile`. It's retired to a no-op (see the class docblock) in
 * favor of PlatformTelegramDigestService — this test guards against it
 * quietly being "fixed" back into an active broadcast without also giving
 * it the tenant/suspension scoping that would make that safe.
 */
class TelegramDigestServiceRetiredTest extends TestCase
{
    public function test_send_morning_never_queries_supabase_or_sends_telegram_messages(): void
    {
        Http::fake();

        $sent = app(TelegramDigestService::class)->sendMorning();

        $this->assertSame(0, $sent);
        Http::assertNothingSent();
    }

    public function test_send_evening_never_queries_supabase_or_sends_telegram_messages(): void
    {
        Http::fake();

        $sent = app(TelegramDigestService::class)->sendEvening();

        $this->assertSame(0, $sent);
        Http::assertNothingSent();
    }
}
