<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `companyIndex()`/`companyExport()` are new — before requirement #8, only a
 * Super Admin could read `admin_action_logs` at all (via `index()`), which
 * made sense when the table only ever recorded Super Admin bypass actions.
 * Now that a Company Admin's own routine activity is logged too, they need a
 * way to see (and export) their own company's slice — scoped by
 * `target_company_id`, relying on the new `admin_action_logs_select_company`
 * RLS policy the same way every other Platform read relies on RLS rather
 * than an app-level filter being the real boundary.
 */
class AuditLogControllerCompanyScopeTest extends TestCase
{
    private function fakeCompanyAdminToken(): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'company-admin-auth-id', 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    private function fakeSession(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'company-admin-id', 'name' => 'Admin', 'email' => 'admin@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([[
                'company_id' => 'company-1', 'role' => 'company_admin', 'status' => 'active',
                'companies' => ['name' => 'QA Co', 'code' => 'QA'],
            ]], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            '*/rest/v1/companies*' => Http::response([['id' => 'company-1', 'name' => 'QA Co', 'code' => 'QA']], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([], 200),
        ]);
    }

    public function test_company_index_filters_by_target_company_id(): void
    {
        $this->fakeSession();

        $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->get('/platform/companies/company-1/audit-log');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/admin_action_logs')
                && $request->method() === 'GET'
                && str_contains($request->url(), 'target_company_id=eq.company-1');
        });
    }

    public function test_a_company_admin_of_a_different_company_is_rejected(): void
    {
        $this->fakeSession();

        $response = $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->get('/platform/companies/some-other-company/audit-log');

        $response->assertStatus(403);
    }

    public function test_export_returns_a_csv_and_logs_the_export(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'company-admin-id', 'name' => 'Admin', 'email' => 'admin@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([[
                'company_id' => 'company-1', 'role' => 'company_admin', 'status' => 'active',
                'companies' => ['name' => 'QA Co', 'code' => 'QA'],
            ]], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([[
                'id' => 'log-1', 'action' => 'create_kpi', 'occurred_at' => '2026-08-17T00:00:00Z',
            ]], 200),
        ]);

        $response = $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->get('/platform/companies/company-1/audit-log/export');

        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/admin_action_logs')
                && $request->method() === 'POST'
                && ($request['action'] ?? null) === 'export_audit_log';
        });
    }
}
