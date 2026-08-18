<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;

/**
 * Attack #3: "Delete another company's row." A Company Admin of Company A
 * sends a DELETE against a Company B resource. Rejected the same way as
 * every other cross-company write — `ensureCompanyAdmin()` before the
 * controller ever calls `$supabase->delete(...)`.
 */
class DeleteAnotherCompanysRowTest extends TenantIsolationTestCase
{
    public function test_deleting_another_companys_role_is_denied(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->delete('/platform/companies/company-b/departments/dept-b/roles/role-b');

        $response->assertStatus(403);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/roles') && $request->method() === 'DELETE');
    }

    public function test_revoking_another_companys_kpi_grant_is_denied(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->delete('/platform/companies/company-b/kpis/kpi-b/grants/grant-b');

        $response->assertStatus(403);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/kpi_access_grants') && $request->method() === 'DELETE');
    }
}
