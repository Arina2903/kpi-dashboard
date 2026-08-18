<?php

namespace Tests\Unit;

use App\Services\AuditLogService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * `AuditLogService` is the single write path for the comprehensive audit
 * system (requirement #8) — it deliberately uses the service-role client
 * (`SupabaseService`) rather than a caller's own token, since several real
 * capture points (a failed login, the Telegram webhook, cron digests) have
 * no authenticated Postgres session to write through at all. What matters
 * here: every field defaults sensibly to null/empty so callers only need to
 * pass what they actually know, `record()` propagates a write failure
 * (callers must handle it), and `recordBestEffort()` never does.
 */
class AuditLogServiceTest extends TestCase
{
    public function test_record_merges_given_fields_over_defaults_and_posts_to_admin_action_logs(): void
    {
        Http::fake(['*/rest/v1/admin_action_logs*' => Http::response([], 201)]);

        (new AuditLogService(new SupabaseService()))->record([
            'action' => 'create_company',
            'target_company_id' => 'company-1',
        ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/admin_action_logs') || $request->method() !== 'POST') {
                return false;
            }

            return $request['action'] === 'create_company'
                && $request['target_company_id'] === 'company-1'
                && array_key_exists('actor_user_id', $request->data())
                && $request['actor_user_id'] === null
                && $request['metadata'] === [];
        });
    }

    public function test_record_throws_when_the_write_fails(): void
    {
        Http::fake(['*/rest/v1/admin_action_logs*' => Http::response(['message' => 'boom'], 500)]);

        $this->expectException(\Throwable::class);

        (new AuditLogService(new SupabaseService()))->record(['action' => 'create_company']);
    }

    public function test_record_best_effort_swallows_a_failed_write_and_logs_it(): void
    {
        Http::fake(['*/rest/v1/admin_action_logs*' => Http::response(['message' => 'boom'], 500)]);

        Log::shouldReceive('error')->once()->with('Audit log write failed', \Mockery::type('array'));

        (new AuditLogService(new SupabaseService()))->recordBestEffort(['action' => 'login_failed']);

        $this->addToAssertionCount(1); // reaching here without throwing is the assertion
    }
}
