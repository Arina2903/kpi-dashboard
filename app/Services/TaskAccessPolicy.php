<?php

namespace App\Services;

/**
 * Central place that answers "which employee_ids can this actor see task,
 * score, and summary data for" — every Performix controller/endpoint goes
 * through this instead of hand-rolling its own role branch, per
 * docs/performix-design.md §2 / §6-R1. This is the one thing standing
 * between a department's task data and a cross-department leak, so changes
 * here should be reviewed carefully.
 *
 * Not a Postgres RLS policy — this app queries Supabase via REST with the
 * service-role key (no direct Postgres access from PHP), so authorization
 * is entirely a PHP-layer responsibility here, consistent with the rest of
 * this codebase (see docs/performix-design.md §6-R6).
 */
class TaskAccessPolicy
{
    protected SupabaseService $supabase;

    public function __construct(
        SupabaseService $supabase
    ) {
        $this->supabase = $supabase;
    }

    /**
     * Every employee_id the given actor is allowed to see task/score/summary
     * data for, including themselves. Mirrors docs/performix-design.md §2:
     *
     *   EXECUTIVE -> self only
     *   MANAGER   -> self + entire department (company_code + department_code)
     *   VP        -> self + direct reports (vp_id/reports_to_id) + those
     *                reports' own reports (manager_id) — a two-hop proxy for
     *                "division", since this schema has no division field
     *   SLT       -> self + entire company (company_code)
     */
    public function visibleEmployeeIds(
        array $actor
    ): array {

        $role        = strtoupper(trim($actor['role'] ?? ''));
        $actorId     = $actor['id'] ?? $actor['employee_id'] ?? null;
        $companyCode = $actor['company_code'] ?? null;

        if (!$actorId || !$companyCode) {
            return [];
        }

        switch ($role) {

            case 'MANAGER':
                return $this->departmentEmployeeIds($companyCode, $actor['department_code'] ?? null, $actorId);

            case 'VP':
                return $this->vpScopedEmployeeIds($companyCode, $actorId);

            case 'SLT':
                return $this->companyEmployeeIds($companyCode, $actorId);

            case 'EXECUTIVE':
            default:
                return [$actorId];
        }
    }

    /**
     * Whether $actor is allowed to see task/score/summary data belonging to
     * $targetEmployeeId. Thin wrapper over visibleEmployeeIds() for the
     * common single-target check (e.g. "can I open this task's detail?").
     */
    public function canView(
        array $actor,
        string $targetEmployeeId
    ): bool {

        return in_array($targetEmployeeId, $this->visibleEmployeeIds($actor), true);
    }

    /**
     * Whether $actor is allowed to assign a task to $targetEmployeeId.
     * EXECUTIVEs can only ever assign to themselves; everyone else follows
     * the same visibility set as viewing (docs/performix-design.md §2 —
     * "Assign task to someone else" row).
     */
    public function canAssign(
        array $actor,
        string $targetEmployeeId
    ): bool {

        $role    = strtoupper(trim($actor['role'] ?? ''));
        $actorId = $actor['id'] ?? $actor['employee_id'] ?? null;

        if ($role === 'EXECUTIVE') {
            return $targetEmployeeId === $actorId;
        }

        return $this->canView($actor, $targetEmployeeId);
    }

    /**
     * Whether $actor manages at least one other employee — drives whether
     * the "My Team" screen / team-scope AI summaries are shown at all.
     */
    public function hasTeam(
        array $actor
    ): bool {

        $role = strtoupper(trim($actor['role'] ?? ''));

        return in_array($role, ['MANAGER', 'VP', 'SLT'], true);
    }

    protected function departmentEmployeeIds(
        string $companyCode,
        ?string $departmentCode,
        string $actorId
    ): array {

        if (!$departmentCode) {
            return [$actorId];
        }

        $employees = $this->supabase->get('employees', [
            'company_code'    => 'eq.' . $companyCode,
            'department_code' => 'eq.' . $departmentCode,
            'is_active'       => 'eq.true',
            'select'          => 'id',
        ]) ?? [];

        $ids = collect($employees)->pluck('id')->all();

        return array_values(array_unique(array_merge($ids, [$actorId])));
    }

    protected function vpScopedEmployeeIds(
        string $companyCode,
        string $actorId
    ): array {

        // byVpId and byReportsTo don't depend on each other — sent
        // concurrently instead of one after another.
        $batch = $this->supabase->getMany([
            'byVpId'      => ['table' => 'employees', 'query' => [
                'company_code' => 'eq.' . $companyCode,
                'vp_id'        => 'eq.' . $actorId,
                'is_active'    => 'eq.true',
                'select'       => 'id',
            ]],
            'byReportsTo' => ['table' => 'employees', 'query' => [
                'company_code'  => 'eq.' . $companyCode,
                'reports_to_id' => 'eq.' . $actorId,
                'is_active'     => 'eq.true',
                'select'        => 'id',
            ]],
        ]);

        $directIds = collect(array_merge($batch['byVpId'] ?? [], $batch['byReportsTo'] ?? []))
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if (empty($directIds)) {
            return [$actorId];
        }

        $grandReports = $this->supabase->get('employees', [
            'company_code' => 'eq.' . $companyCode,
            'manager_id'   => 'in.(' . implode(',', $directIds) . ')',
            'is_active'    => 'eq.true',
            'select'       => 'id',
        ]) ?? [];

        $grandIds = collect($grandReports)->pluck('id')->all();

        return array_values(array_unique(array_merge([$actorId], $directIds, $grandIds)));
    }

    protected function companyEmployeeIds(
        string $companyCode,
        string $actorId
    ): array {

        $employees = $this->supabase->get('employees', [
            'company_code' => 'eq.' . $companyCode,
            'is_active'    => 'eq.true',
            'select'       => 'id',
        ]) ?? [];

        $ids = collect($employees)->pluck('id')->all();

        return array_values(array_unique(array_merge($ids, [$actorId])));
    }
}
