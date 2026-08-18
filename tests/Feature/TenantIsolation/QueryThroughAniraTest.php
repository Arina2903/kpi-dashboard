<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;

/**
 * Attack #9: "Query through ANIRA." The chat interface must not become a
 * side door around the tenant boundary every other Platform endpoint
 * respects — see "ANIRA tenant-aware permission resolution" in CLAUDE.md.
 * A caller legitimately authorized for Company A asks specifically about
 * Company B (a real company they simply aren't a member of, not a garbage
 * id) — `AniraController::chat()` must refuse before `AiService::chatForPlatform()`
 * is ever invoked, not just decline to mention Company B in the reply.
 */
class QueryThroughAniraTest extends TenantIsolationTestCase
{
    // AniraController validates company_id as `nullable|uuid` — these must
    // be real UUID-shaped strings, not the plain "company-a"/"company-b"
    // labels used elsewhere in this suite where the target routes don't
    // validate the segment's format at all.
    private const COMPANY_A = '11111111-1111-1111-1111-111111111111';
    private const COMPANY_B = '22222222-2222-2222-2222-222222222222';

    public function test_asking_anira_about_another_company_is_denied_before_the_model_is_ever_asked(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'company-a-admin', 'name' => 'Admin', 'email' => 'admin@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([[
                'company_id' => self::COMPANY_A, 'role' => 'company_admin', 'status' => 'active',
                'companies' => ['name' => 'Company A', 'code' => 'A'],
            ]], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            // The caller's OWN authorized company list — deliberately
            // non-empty and specifically Company A, so this proves
            // "authorized for A, denied for B", not just "denied for
            // everything because nothing was authorized at all."
            '*/rest/v1/companies*' => Http::response([
                ['id' => self::COMPANY_A, 'name' => 'Company A', 'code' => 'A', 'status' => 'active'],
            ], 200),
        ]);

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->postJson('/platform/ai/chat', [
                'message' => "What is Company B's revenue KPI?",
                'company_id' => self::COMPANY_B,
            ]);

        $response->assertStatus(403);

        // The strongest available proof: the model itself was never asked
        // anything. Every unauthorized row was already excluded before this
        // point (AuthorizedDataScope/RLS) — this confirms the request
        // doesn't even reach the point of building a prompt that could leak
        // the boundary through, however carefully worded the system prompt.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }
}
