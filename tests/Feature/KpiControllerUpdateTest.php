<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `KpiController::update()` is new — before this, a KPI could be created but
 * never edited, so "KPI configuration changes" and "Target changes"
 * (requirement #8) had nothing to log in the first place. Two things matter:
 * the update request itself uses `Prefer: return=minimal` (the same fix
 * `store()` already needed — `kpis_select` self-references `kpis` via
 * `auth_can_view_kpi()`, which breaks the implicit RETURNING for a
 * non-Super-Admin caller on write, same failure mode whether it's an INSERT
 * or an UPDATE), and a target change emits its own separately-filterable
 * `change_kpi_target` action in addition to the general `update_kpi` entry.
 */
class KpiControllerUpdateTest extends TestCase
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
            '*/rest/v1/admin_action_logs*' => Http::response([], 201),
        ]);
    }

    public function test_update_sends_return_minimal_and_logs_the_target_change(): void
    {
        $this->fakeSession();

        Http::fake(array_merge($this->currentFakes(), [
            '*/rest/v1/kpis*' => Http::sequence()
                // before-state lookup
                ->push([[
                    'id' => 'kpi-1', 'name' => 'Old Name', 'description' => null,
                    'target' => 50, 'unit' => '%', 'frequency' => 'monthly',
                    'visibility' => 'company', 'category_id' => null,
                ]], 200)
                // the PATCH itself
                ->push([], 200),
        ]));

        $this->withSession(['platform_access_token' => $this->fakeCompanyAdminToken()])
            ->patch('/platform/companies/company-1/kpis/kpi-1', [
                'name' => 'New Name',
                'target' => 75,
                'frequency' => 'monthly',
            ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/rest/v1/kpis?id=eq.kpi-1')
                && $request->method() === 'PATCH'
                && $request->header('Prefer') === ['return=minimal'];
        });

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/rest/v1/admin_action_logs') || $request->method() !== 'POST') {
                return false;
            }

            return $request['action'] === 'change_kpi_target'
                && $request['before']['target'] == 50
                && $request['after']['target'] == 75;
        });
    }

    private function currentFakes(): array
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
}
