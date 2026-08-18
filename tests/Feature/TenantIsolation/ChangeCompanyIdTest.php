<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;

/**
 * Attack #4: "Change company_id." The database-level half of this guarantee
 * — `company_id` is immutable once set, enforced by BEFORE UPDATE triggers,
 * and derived server-side rather than trusted from the client on tables
 * like `department_users`/`roles` — is documented in CLAUDE.md's Core
 * Platform Rule and can only be proven for real against Postgres (see
 * `tenant_isolation.sql` and the migrations under "Tenant isolation as a
 * structural rule"). This test proves the APPLICATION-layer half: even when
 * a request body includes a spoofed `company_id` pointing at a company the
 * caller doesn't administer, the controller never reads it — every insert's
 * `company_id` comes from the URL's own route parameter, which
 * `ensureCompanyAdmin()` has already authorized.
 */
class ChangeCompanyIdTest extends TenantIsolationTestCase
{
    public function test_kpi_creation_ignores_a_spoofed_company_id_in_the_request_body(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->post('/platform/companies/company-a/kpis', [
                'name' => 'Legit-looking KPI',
                'frequency' => 'monthly',
                'company_id' => 'company-b',
            ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/kpis') || $request->method() !== 'POST') {
                return false;
            }

            return $request['company_id'] === 'company-a';
        });
    }

    public function test_department_creation_ignores_a_spoofed_company_id_in_the_request_body(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->post('/platform/companies/company-a/departments', [
                'name' => 'Legit-looking Department',
                'code' => 'LGT',
                'company_id' => 'company-b',
            ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/departments') || $request->method() !== 'POST') {
                return false;
            }

            return $request['company_id'] === 'company-a';
        });
    }
}
