<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Attack #8: "Manipulate URL/company IDs." Every Platform authorization
 * check (`ensureCompanyAdmin()` et al.) is a plain string comparison against
 * the caller's own `company_memberships` — never a raw SQL fragment built
 * from the URL — so there's no injection surface here to begin with. What
 * matters is that a malformed, unexpected, or maliciously-shaped `{company}`
 * segment produces a clean 4xx either way: never a 200 (access granted) and
 * never a 500 (an unhandled exception, which is its own kind of information
 * leak about the server's internals).
 */
class ManipulateUrlCompanyIdsTest extends TenantIsolationTestCase
{
    public static function manipulatedCompanyIds(): array
    {
        return [
            'another real company id' => ['company-b'],
            'sql-injection-shaped string' => ["company-a' OR '1'='1"],
            'path-traversal-shaped string' => ['....company-a'],
            'null-byte-shaped string' => ["company-a\0admin"],
            'very long garbage string' => [str_repeat('a', 500)],
        ];
    }

    #[DataProvider('manipulatedCompanyIds')]
    public function test_a_manipulated_url_company_id_never_grants_access_or_crashes(string $companyId): void
    {
        Http::fake($this->fakeCompanyAdminSessionFakes('company-a-admin', 'company-a'));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('company-a-admin-auth-id')])
            ->get('/platform/companies/' . rawurlencode($companyId) . '/departments');

        $this->assertGreaterThanOrEqual(400, $response->status());
        $this->assertLessThan(500, $response->status(), 'expected a clean 4xx, got a server error (' . $response->status() . ')');
    }
}
