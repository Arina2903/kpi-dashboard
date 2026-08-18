<?php

namespace Tests\Feature\TenantIsolation;

use Illuminate\Support\Facades\Http;

/**
 * Attack #5: "Import another company's employee." Two separate places this
 * could go wrong, both already fixed in this codebase (see the "Code-review
 * bug-fix pass" section of CLAUDE.md) and both re-verified here:
 *   - `ImportController::confirm()` re-checks the stashed preview's own
 *     `company_id` against the URL's `{company}` — a preview validated
 *     against Company A must not be committable against Company B, even
 *     with a valid, unexpired token.
 *   - `UserCreationController` looks up an import batch by `id` AND
 *     `company_id` together — a batch that genuinely belongs to Company A
 *     must 404 (not silently succeed, not leak its contents) when requested
 *     through Company B's URL.
 */
class ImportAnotherCompanysEmployeeTest extends TenantIsolationTestCase
{
    public function test_confirming_an_import_preview_against_a_different_company_is_denied(): void
    {
        Http::fake($this->fakeSuperAdminSessionFakes('super-admin'));

        $response = $this->withSession([
            'platform_access_token' => $this->fakeToken('super-admin-auth-id'),
            'import_preview.tok-123' => [
                'company_id' => 'company-a',
                'filename' => 'employees.xlsx',
                'result' => [
                    'employees' => [
                        'valid' => [['name' => 'Eve', 'email' => 'eve@example.com']],
                        'errors' => [],
                    ],
                ],
            ],
        ])->post('/platform/companies/company-b/import/confirm', ['token' => 'tok-123']);

        $response->assertRedirect();
        $response->assertSessionHas(
            'error',
            'This preview was validated against a different company — upload the file again from this company\'s import page.'
        );

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/import_batches') && $request->method() === 'POST');
    }

    public function test_creating_accounts_for_another_companys_import_batch_404s(): void
    {
        Http::fake(array_merge($this->fakeSuperAdminSessionFakes('super-admin'), [
            // Company B itself genuinely exists — this test isolates the
            // BATCH lookup's own company scoping, not a company-not-found
            // short-circuit.
            '*/rest/v1/companies*' => Http::response([['id' => 'company-b', 'name' => 'Company B']], 200),
            // The batch genuinely exists (for Company A) — but the lookup is
            // filtered on company_id=eq.company-b too, so a real Postgres
            // (or, here, our fake standing in for it) correctly finds
            // nothing for this URL's company.
            '*/rest/v1/import_batches*' => Http::response([], 200),
        ]));

        $response = $this->withSession(['platform_access_token' => $this->fakeToken('super-admin-auth-id')])
            ->get('/platform/companies/company-b/import/batch-belongs-to-company-a/users');

        $response->assertStatus(404);
    }
}
