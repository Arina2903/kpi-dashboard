<?php

namespace Tests\Unit;

use App\Services\AppraiserDelegationService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Manager -> VP substitution feature (see AppraiserDelegationService's
 * own docblock): when BTS activates a delegation for a Manager on long
 * leave, nextParentId() must hand back the delegate instead of the normal
 * manager_id/vp_id/reports_to_id chain -- for every role, and unaffected
 * when no delegation exists for that particular parent id.
 */
class AppraiserDelegationServiceTest extends TestCase
{
    private function service(): AppraiserDelegationService
    {
        return new AppraiserDelegationService(app(SupabaseService::class));
    }

    public function test_next_parent_id_for_executive_uses_manager_id_when_no_delegation_exists(): void
    {
        Http::fake([
            '*/rest/v1/appraiser_delegations*' => Http::response([], 200),
        ]);

        $executive = ['role' => 'EXECUTIVE', 'manager_id' => 'manager-1', 'vp_id' => 'vp-1', 'reports_to_id' => null];

        $this->assertSame('manager-1', $this->service()->nextParentId($executive));
    }

    public function test_next_parent_id_for_executive_substitutes_the_delegate_when_their_manager_is_delegated(): void
    {
        Http::fake([
            '*/rest/v1/appraiser_delegations*' => Http::response([
                ['manager_id' => 'manager-1', 'delegate_to_id' => 'vp-2'],
            ], 200),
        ]);

        $executive = ['role' => 'EXECUTIVE', 'manager_id' => 'manager-1', 'vp_id' => 'vp-1', 'reports_to_id' => null];

        $this->assertSame('vp-2', $this->service()->nextParentId($executive));
    }

    public function test_next_parent_id_for_manager_falls_back_to_vp_id_and_is_unaffected_by_an_unrelated_delegation(): void
    {
        // Http::fake matches by URL pattern, not by the actual querystring
        // filter -- a bare wildcard would return this row regardless of
        // which manager_id was really being asked about, which would prove
        // nothing. Scoping the fake to the exact filtered URL forces the
        // lookup to genuinely ask for "vp-1" and get an empty result, the
        // same as a real "some-other-manager"-only row would produce.
        Http::fake([
            '*manager_id=eq.some-other-manager*' => Http::response([
                ['manager_id' => 'some-other-manager', 'delegate_to_id' => 'vp-2'],
            ], 200),
            '*/rest/v1/appraiser_delegations*' => Http::response([], 200),
        ]);

        $manager = ['role' => 'MANAGER', 'manager_id' => null, 'vp_id' => 'vp-1', 'reports_to_id' => 'slt-1'];

        $this->assertSame('vp-1', $this->service()->nextParentId($manager));
    }

    public function test_next_parent_id_for_vp_uses_reports_to_id_when_no_delegation_matches_it(): void
    {
        // A VP's own appraiser (their reports_to_id, typically SLT) is still
        // run through the same delegation lookup as every other hop -- it's
        // just that no row can ever match it in practice, because
        // AppraiserDelegationController::store() only ever writes rows keyed
        // by a MANAGER's id. That guarantee lives on the write side, not by
        // skipping the read here.
        Http::fake(['*/rest/v1/appraiser_delegations*' => Http::response([], 200)]);

        $vp = ['role' => 'VP', 'manager_id' => null, 'vp_id' => null, 'reports_to_id' => 'slt-1'];

        $this->assertSame('slt-1', $this->service()->nextParentId($vp));
    }

    public function test_next_parent_id_returns_null_when_role_has_no_parent(): void
    {
        Http::fake(['*/rest/v1/appraiser_delegations*' => Http::response([], 200)]);

        $slt = ['role' => 'SLT', 'manager_id' => null, 'vp_id' => null, 'reports_to_id' => null];

        $this->assertNull($this->service()->nextParentId($slt));
    }

    public function test_active_delegate_fails_open_to_null_when_the_table_does_not_exist_yet(): void
    {
        Http::fake([
            '*/rest/v1/appraiser_delegations*' => Http::response([
                'code' => 'PGRST205', 'message' => "Could not find the table 'public.appraiser_delegations'",
            ], 404),
        ]);

        $this->assertNull($this->service()->activeDelegate('manager-1'));
    }

    public function test_all_fails_open_to_empty_array_when_the_table_does_not_exist_yet(): void
    {
        Http::fake([
            '*/rest/v1/appraiser_delegations*' => Http::response([
                'code' => 'PGRST205', 'message' => "Could not find the table 'public.appraiser_delegations'",
            ], 404),
        ]);

        $this->assertSame([], $this->service()->all());
    }
}
