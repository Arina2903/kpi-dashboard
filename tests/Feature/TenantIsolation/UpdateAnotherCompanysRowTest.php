<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;

/**
 * Attack #2: "Update another company's row." A Company Admin of Company A
 * sends a write against a Company B resource by putting Company B's id in
 * the URL, with an otherwise well-formed, valid payload. Every one of these
 * must be rejected by `ensureCompanyAdmin()` before any PATCH is issued.
 */
class UpdateAnotherCompanysRowTest extends TenantIsolationTestCase
{
    public function test_updating_another_companys_kpi_is_denied(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->patch('/platform/companies/company-b/kpis/kpi-b', [
                'name' => 'Hijacked KPI',
                'target' => 999999,
                'frequency' => 'monthly',
            ]);

        $response->assertStatus(403);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/kpis') && $request->method() === 'PATCH');
    }

    public function test_changing_another_companys_members_role_is_denied(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->patch('/platform/companies/company-b/departments/dept-b/users/user-b/role', [
                'role' => 'executive',
                'role_id' => '11111111-1111-1111-1111-111111111111',
            ]);

        $response->assertStatus(403);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/department_users') && $request->method() === 'PATCH');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/company_users') && $request->method() === 'PATCH');
    }

    public function test_suspending_another_companys_member_is_denied(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->post('/platform/companies/company-b/users/user-b/suspend');

        $response->assertStatus(403);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/company_users') && $request->method() === 'PATCH');
    }
}
