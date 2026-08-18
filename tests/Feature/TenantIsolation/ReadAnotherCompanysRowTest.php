<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Attack #1: "Read another company's row." A Company Admin of Company A
 * requests a page scoped to Company B by putting Company B's id in the URL.
 * `PlatformAuthorization::ensureCompanyAdmin()`/`ensureCompanyMember()` must
 * reject this before the controller issues a single read against Company
 * B's data — proven here by both the 403 and the absence of any request to
 * the table that page would otherwise have read.
 */
class ReadAnotherCompanysRowTest extends TenantIsolationTestCase
{
    public static function readOnlyEndpoints(): array
    {
        return [
            'departments index' => ['/platform/companies/company-b/departments', 'departments'],
            'kpis index' => ['/platform/companies/company-b/kpis', 'kpis'],
            'audit log' => ['/platform/companies/company-b/audit-log', 'admin_action_logs'],
        ];
    }

    #[DataProvider('readOnlyEndpoints')]
    public function test_reading_another_companys_page_is_denied(string $url, string $tableItWouldHaveRead): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->get($url);

        $response->assertStatus(403);

        // Not just a 403 status: proves the controller bailed out via
        // ensureCompanyAdmin/ensureCompanyMember BEFORE issuing the actual
        // data read, rather than fetching Company B's row and then merely
        // declining to render it.
        $this->assertNoRequestReached($tableItWouldHaveRead);
    }

    public function test_reading_another_companys_department_submissions_is_denied(): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->get('/platform/companies/company-b/departments/dept-b/submissions');

        $response->assertStatus(403);
        $this->assertNoRequestReached('kpi_submissions');
    }
}
