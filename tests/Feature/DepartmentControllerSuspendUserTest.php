<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `suspendUser()`/`reactivateUser()` are new — "User suspension"
 * (requirement #8) had no endpoint at all before this. Two things matter:
 * the last active Company Admin can't be suspended (the DB's own
 * `prevent_zero_company_admins` trigger only fires on DELETE/role-change, not
 * a status-only UPDATE, so this app-level check is the only thing that would
 * catch it), and a successful suspend/reactivate logs before/after status.
 */
class DepartmentControllerSuspendUserTest extends TestCase
{
    private function fakeCompanyAdminToken(): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'company-admin-auth-id', 'role' => 'authenticated'])), '+/', '-_'), '=');

        return "{$header}.{$payload}.fake-signature";
    }

    private function fakeSessionHeaders(): array
    {
        return [
            '*/rest/v1/users*' => Http::response([[
                'id' => 'company-admin-id', 'name' => 'Admin', 'email' => 'admin@example.com',
                'role' => 'member', 'status' => 'active',
            ]], 200),
            '*/rest/v1/company_users*' => Http::response([[
                'company_id' => 'company-1', 'role' => 'company_admin', 'status' => 'active',
                'companies' => ['name' => 'QA Co', 'code' => 'QA'],
            ]], 200),
            '*/rest/v1/platform_admin_assignments*' => Http::response([], 200),
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
        ];
    }

    public function test_cannot_suspend_the_last_active_company_admin(): void
    {
        Http::fake(array_merge($this->fakeSessionHeaders(), [
            '*/rest/v1/company_users*' => Http::sequence()
                // PlatformAuth's own membership lookup
                ->push([[
                    'company_id' => 'company-1', 'role' => 'company_admin', 'status' => 'active',
                    'companies' => ['name' => 'QA Co', 'code' => 'QA'],
                ]], 200)
                // suspendUser()'s "before" lookup for the target
                ->push([['role' => 'company_admin', 'status' => 'active']], 200)
                // suspendUser()'s "any other active admin" check — none
                ->push([], 200),
        ]));

        $response = $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->post('/platform/companies/company-1/users/user-2/suspend');

        $response->assertSessionHas('error', 'Cannot suspend the last active Company Admin for this company.');

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/company_users')
                && $request->method() === 'PATCH';
        });
    }

    public function test_suspending_an_ordinary_member_logs_before_and_after_status(): void
    {
        Http::fake(array_merge($this->fakeSessionHeaders(), [
            '*/rest/v1/company_users*' => Http::sequence()
                ->push([[
                    'company_id' => 'company-1', 'role' => 'company_admin', 'status' => 'active',
                    'companies' => ['name' => 'QA Co', 'code' => 'QA'],
                ]], 200)
                ->push([['role' => 'employee', 'status' => 'active']], 200)
                ->push([], 200), // PATCH response
        ]));

        $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->post('/platform/companies/company-1/users/user-2/suspend');

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/admin_action_logs') || $request->method() !== 'POST') {
                return false;
            }

            return $request['action'] === 'suspend_user'
                && $request['before']['status'] === 'active'
                && $request['after']['status'] === 'suspended';
        });
    }
}
