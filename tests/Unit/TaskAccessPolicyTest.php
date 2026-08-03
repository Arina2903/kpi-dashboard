<?php

namespace Tests\Unit;

use App\Services\SupabaseService;
use App\Services\TaskAccessPolicy;
use PHPUnit\Framework\TestCase;

/**
 * An in-memory stand-in for SupabaseService — no network calls, no
 * container, just canned rows filtered by the same eq./in. query shape
 * TaskAccessPolicy actually sends, so these tests exercise the real
 * filtering logic without hitting Supabase.
 */
class FakeSupabaseForPolicy extends SupabaseService
{
    public array $tables = [];

    public function __construct()
    {
        // Skip the parent constructor entirely — it reads SUPABASE_URL /
        // SUPABASE_SERVICE_ROLE_KEY from env, which this offline fake never
        // needs since get()/getMany() are fully overridden below.
    }

    public function get(string $table, array $query = [])
    {
        $rows = $this->tables[$table] ?? [];

        return array_values(array_filter($rows, function ($row) use ($query) {
            foreach ($query as $key => $value) {
                if (in_array($key, ['select', 'order', 'limit'], true)) {
                    continue;
                }
                if (!$this->matches($row[$key] ?? null, $value)) {
                    return false;
                }
            }
            return true;
        }));
    }

    public function getMany(array $requests): array
    {
        $results = [];
        foreach ($requests as $key => $req) {
            $results[$key] = $this->get($req['table'], $req['query']);
        }
        return $results;
    }

    private function matches($actual, string $filter): bool
    {
        if (is_bool($actual)) {
            $actual = $actual ? 'true' : 'false';
        }

        if (str_starts_with($filter, 'eq.')) {
            return (string) $actual === substr($filter, 3);
        }

        if (str_starts_with($filter, 'in.(')) {
            $values = explode(',', trim(substr($filter, 3), '()'));
            return in_array((string) $actual, $values, true);
        }

        return true;
    }
}

class TaskAccessPolicyTest extends TestCase
{
    private FakeSupabaseForPolicy $supabase;
    private TaskAccessPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supabase = new FakeSupabaseForPolicy();
        $this->policy = new TaskAccessPolicy($this->supabase);
    }

    private function employee(array $overrides = []): array
    {
        return array_merge([
            'id' => 'emp-1',
            'role' => 'EXECUTIVE',
            'company_code' => 'ACME',
            'department_code' => 'SALES',
            'is_active' => true,
        ], $overrides);
    }

    public function test_executive_sees_only_self(): void
    {
        $actor = $this->employee(['id' => 'exec-1', 'role' => 'EXECUTIVE']);

        $this->assertSame(['exec-1'], $this->policy->visibleEmployeeIds($actor));
    }

    public function test_executive_cannot_view_another_executive(): void
    {
        $actor = $this->employee(['id' => 'exec-1', 'role' => 'EXECUTIVE']);

        $this->assertFalse($this->policy->canView($actor, 'exec-2'));
        $this->assertTrue($this->policy->canView($actor, 'exec-1'));
    }

    public function test_executive_can_only_assign_to_self(): void
    {
        $actor = $this->employee(['id' => 'exec-1', 'role' => 'EXECUTIVE']);

        $this->assertTrue($this->policy->canAssign($actor, 'exec-1'));
        $this->assertFalse($this->policy->canAssign($actor, 'exec-2'));
    }

    public function test_executive_has_no_team(): void
    {
        $actor = $this->employee(['role' => 'EXECUTIVE']);

        $this->assertFalse($this->policy->hasTeam($actor));
    }

    public function test_manager_sees_self_and_active_department_peers_only(): void
    {
        $actor = $this->employee(['id' => 'mgr-1', 'role' => 'MANAGER', 'department_code' => 'SALES']);

        $this->supabase->tables['employees'] = [
            ['id' => 'sales-1', 'company_code' => 'ACME', 'department_code' => 'SALES', 'is_active' => true],
            ['id' => 'sales-2', 'company_code' => 'ACME', 'department_code' => 'SALES', 'is_active' => true],
            ['id' => 'sales-inactive', 'company_code' => 'ACME', 'department_code' => 'SALES', 'is_active' => false],
            ['id' => 'ops-1', 'company_code' => 'ACME', 'department_code' => 'OPS', 'is_active' => true],
            ['id' => 'other-company', 'company_code' => 'OTHER', 'department_code' => 'SALES', 'is_active' => true],
        ];

        $visible = $this->policy->visibleEmployeeIds($actor);

        sort($visible);
        $this->assertSame(['mgr-1', 'sales-1', 'sales-2'], $visible);
    }

    public function test_manager_has_team(): void
    {
        $actor = $this->employee(['role' => 'MANAGER']);

        $this->assertTrue($this->policy->hasTeam($actor));
    }

    public function test_manager_can_assign_within_department_but_not_outside(): void
    {
        $actor = $this->employee(['id' => 'mgr-1', 'role' => 'MANAGER', 'department_code' => 'SALES']);

        $this->supabase->tables['employees'] = [
            ['id' => 'sales-1', 'company_code' => 'ACME', 'department_code' => 'SALES', 'is_active' => true],
            ['id' => 'ops-1', 'company_code' => 'ACME', 'department_code' => 'OPS', 'is_active' => true],
        ];

        $this->assertTrue($this->policy->canAssign($actor, 'sales-1'));
        $this->assertFalse($this->policy->canAssign($actor, 'ops-1'));
    }

    public function test_vp_sees_direct_reports_and_their_reports_but_not_unrelated_employees(): void
    {
        $actor = $this->employee(['id' => 'vp-1', 'role' => 'VP']);

        $this->supabase->tables['employees'] = [
            // direct report via vp_id
            ['id' => 'mgr-1', 'company_code' => 'ACME', 'vp_id' => 'vp-1', 'manager_id' => null, 'reports_to_id' => null, 'is_active' => true],
            // direct report via reports_to_id (no manager layer)
            ['id' => 'exec-2', 'company_code' => 'ACME', 'vp_id' => null, 'manager_id' => null, 'reports_to_id' => 'vp-1', 'is_active' => true],
            // grand-report: reports to mgr-1, who reports to the VP
            ['id' => 'exec-1', 'company_code' => 'ACME', 'vp_id' => null, 'manager_id' => 'mgr-1', 'reports_to_id' => null, 'is_active' => true],
            // unrelated employee in the same company, no relation to this VP
            ['id' => 'exec-3', 'company_code' => 'ACME', 'vp_id' => null, 'manager_id' => null, 'reports_to_id' => null, 'is_active' => true],
        ];

        $visible = $this->policy->visibleEmployeeIds($actor);
        sort($visible);

        $this->assertSame(['exec-1', 'exec-2', 'mgr-1', 'vp-1'], $visible);
    }

    public function test_vp_with_no_reports_sees_only_self(): void
    {
        $actor = $this->employee(['id' => 'vp-1', 'role' => 'VP']);
        $this->supabase->tables['employees'] = [];

        $this->assertSame(['vp-1'], $this->policy->visibleEmployeeIds($actor));
    }

    public function test_slt_sees_entire_active_company_excluding_other_companies(): void
    {
        $actor = $this->employee(['id' => 'slt-1', 'role' => 'SLT']);

        $this->supabase->tables['employees'] = [
            ['id' => 'a', 'company_code' => 'ACME', 'is_active' => true],
            ['id' => 'b', 'company_code' => 'ACME', 'is_active' => true],
            ['id' => 'inactive', 'company_code' => 'ACME', 'is_active' => false],
            ['id' => 'other-co', 'company_code' => 'OTHER', 'is_active' => true],
        ];

        $visible = $this->policy->visibleEmployeeIds($actor);
        sort($visible);

        $this->assertSame(['a', 'b', 'slt-1'], $visible);
    }

    public function test_slt_has_team(): void
    {
        $actor = $this->employee(['role' => 'SLT']);

        $this->assertTrue($this->policy->hasTeam($actor));
    }

    public function test_missing_actor_id_or_company_returns_empty_visibility(): void
    {
        $this->assertSame([], $this->policy->visibleEmployeeIds(['role' => 'SLT', 'company_code' => 'ACME']));
        $this->assertSame([], $this->policy->visibleEmployeeIds(['id' => 'x', 'role' => 'SLT']));
    }

    public function test_unknown_role_defaults_to_self_only(): void
    {
        $actor = $this->employee(['id' => 'ghost-1', 'role' => 'CONTRACTOR']);

        $this->assertSame(['ghost-1'], $this->policy->visibleEmployeeIds($actor));
        $this->assertFalse($this->policy->hasTeam($actor));
    }

    public function test_canview_uses_visible_employee_ids_consistently_for_manager(): void
    {
        $actor = $this->employee(['id' => 'mgr-1', 'role' => 'MANAGER', 'department_code' => 'SALES']);

        $this->supabase->tables['employees'] = [
            ['id' => 'sales-1', 'company_code' => 'ACME', 'department_code' => 'SALES', 'is_active' => true],
        ];

        $this->assertTrue($this->policy->canView($actor, 'sales-1'));
        $this->assertFalse($this->policy->canView($actor, 'random-employee'));
    }
}
