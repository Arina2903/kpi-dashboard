<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The core gap requirement #8 ("a proper audit system") was built to close:
 * before this change, `LogsAdminActions::logIfSuperAdmin()` only wrote a row
 * when a Super Admin was bypassing into a company they don't belong to —
 * which meant an ordinary Company Admin creating a KPI, a role, or a
 * department (the overwhelming majority of real Platform usage) generated
 * ZERO audit trail at all. `logIfSuperAdmin()` is gone; every write now goes
 * through `logCompanyAction()`, which logs unconditionally and tags
 * `acting_as_super_admin_bypass` in the metadata instead of gating on it.
 *
 * This test proves the fix at the one call site most representative of the
 * gap (`KpiController::store()`, a Company Admin's own company) — a
 * Company Admin creating a KPI in THEIR OWN company must still produce a row
 * in `admin_action_logs`, with `acting_as_super_admin_bypass: false`.
 */
class CompanyAdminActionsAreAlwaysLoggedTest extends TestCase
{
    private function fakeCompanyAdminToken(): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'company-admin-auth-id', 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    private function fakeAuthenticatedCompanyAdminSession(): void
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
            '*/rest/v1/kpis*' => Http::response([], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
        ]);
    }

    public function test_a_company_admin_creating_a_kpi_in_their_own_company_is_logged(): void
    {
        $this->fakeAuthenticatedCompanyAdminSession();

        $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->post('/platform/companies/company-1/kpis', [
                'name' => 'QA KPI',
                'frequency' => 'quarterly',
            ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/admin_action_logs') || $request->method() !== 'POST') {
                return false;
            }

            return $request['action'] === 'create_kpi'
                && $request['target_company_id'] === 'company-1'
                && $request['metadata']['acting_as_super_admin_bypass'] === false;
        });
    }

    public function test_a_role_change_by_a_company_admin_logs_the_before_and_after_role(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 'company-admin-id', 'name' => 'Admin', 'email' => 'admin@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::sequence()
                ->push([[
                    'company_id' => 'company-1', 'role' => 'company_admin', 'status' => 'active',
                    'companies' => ['name' => 'QA Co', 'code' => 'QA'],
                ]], 200)
                ->push([], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            '*/rest/v1/roles*' => Http::response([['id' => '22222222-2222-2222-2222-222222222222']], 200),
            '*/rest/v1/department_users*' => Http::response([['role' => 'employee', 'role_id' => '11111111-1111-1111-1111-111111111111']], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
        ]);

        $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->patch('/platform/companies/company-1/departments/department-1/users/user-2/role', [
                'role' => 'executive',
                'role_id' => '22222222-2222-2222-2222-222222222222',
            ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/admin_action_logs') || $request->method() !== 'POST') {
                return false;
            }

            return $request['action'] === 'update_user_role'
                && $request['before']['role'] === 'employee'
                && $request['after']['role'] === 'executive';
        });
    }
}
