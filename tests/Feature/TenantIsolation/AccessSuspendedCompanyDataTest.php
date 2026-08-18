<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;

/**
 * Attack #7: "Access suspended company data." Suspension is enforced
 * entirely by RLS excluding the company from `auth_company_ids()`/
 * `auth_role_in_company()` (`2026_08_14_060000_enforce_company_suspension_in_rls`)
 * — there is deliberately no app-layer redundant status check anywhere.
 * What this test proves is the app-layer half of what makes that
 * "immediate": `PlatformAuth` re-resolves `company_memberships` fresh on
 * EVERY request (never from a cached/stale session), so the moment RLS
 * starts excluding a suspended company from that query, the very next
 * request treats its former admin as having no membership there at all —
 * simulated here by a membership query that simply returns nothing, exactly
 * what RLS produces once a company is suspended. The actual database-level
 * enforcement is proven for real by `tenant_isolation.sql` scenarios 7-9,
 * run against real Postgres in `.github/workflows/ci.yml`'s
 * `tenant-isolation` job — not by this mocked test.
 */
class AccessSuspendedCompanyDataTest extends TenantIsolationTestCase
{
    public function test_a_suspended_companys_former_admin_is_treated_as_having_no_membership(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'company-a-admin', 'name' => 'Admin', 'email' => 'admin@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            // Simulates exactly what RLS produces once auth_company_ids()
            // excludes a suspended company: the membership row this admin
            // held a moment ago simply isn't returned any more.
            '*/rest/v1/company_users*' => Http::response([], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
        ]);

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->get('/platform/companies/company-a/departments');

        $response->assertStatus(403);
    }

    public function test_a_suspended_companys_former_admin_cannot_write_either(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'company-a-admin', 'name' => 'Admin', 'email' => 'admin@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
        ]);

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->post('/platform/companies/company-a/kpis', ['name' => 'New KPI', 'frequency' => 'monthly']);

        $response->assertStatus(403);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/kpis') && $request->method() === 'POST');
    }
}
