<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;

class KpiController extends Controller
{
    private function currentFY(): string
    {
        return 'FY' . now()->year;
    }

    private function nowMy(): string
    {
        return now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString();
    }

    private function currentUser(SupabaseService $supabase): array
    {
        $employeeUuid = session('employee_uuid');

        if (!$employeeUuid) {
            abort(403, 'Employee not logged in.');
        }

        $employees = $supabase->get('employees', [
            'id' => 'eq.' . $employeeUuid,
            'is_active' => 'eq.true',
            'select' => '*',
        ]);

        if (empty($employees)) {
            session()->flush();
            abort(403, 'Employee not found.');
        }

        return $employees[0];
    }

    private function permissionForRoleFromDb(SupabaseService $supabase, string $role): array
    {
        $permissions = $supabase->get('kpi_permissions', [
            'role' => 'eq.' . $role,
            'select' => '*',
        ]);

        if (empty($permissions)) {
            abort(403, 'KPI permission not configured for role: ' . $role);
        }

        return $permissions[0];
    }

    private function sidebarData(SupabaseService $supabase, array $user): array
    {
        $departmentFilters = [
            'select' => '*',
            'order' => 'name.asc',
        ];

        if (!empty($user['company_code'])) {
            $departmentFilters['company_code'] = 'eq.' . $user['company_code'];
        }

        $departments = $supabase->get('departments', $departmentFilters) ?? [];

        $role = strtoupper(trim($user['role'] ?? ''));

        $canSwitchDepartment = in_array($role, [
            'ADMIN', 'SLT', 'CCO', 'CCMO', 'VP',
        ]);

        $selectedDepartmentCode = session('selected_department_code')
            ?? $user['department_code']
            ?? null;

        $department = null;

        if ($selectedDepartmentCode) {
            $departmentResult = $supabase->get('departments', [
                'code' => 'eq.' . $selectedDepartmentCode,
                'select' => '*',
            ]);

            $department = $departmentResult[0] ?? null;
        }

        return [
            'departments' => $departments,
            'department' => $department,
            'canSwitchDepartment' => $canSwitchDepartment,
            'selectedDepartmentCode' => $selectedDepartmentCode,
        ];
    }

    public function index(SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $permission = $this->permissionForRoleFromDb($supabase, $user['role']);
        $fy = $this->currentFY();

        $role = strtoupper(trim($user['role'] ?? ''));

        $employees = [];
        $kpis = [];

        if (in_array($role, ['ADMIN', 'SLT', 'CCO', 'CCMO', 'VP'])) {
            $selectedDepartmentCode = session('selected_department_code')
                ?? $user['department_code'];

            $kpis = $supabase->get('kpis', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $selectedDepartmentCode,
                'financial_year' => 'eq.' . $fy,
                'select' => '*',
                'order' => 'created_at.desc',
            ]) ?? [];

            $employees = $supabase->get('employees', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $selectedDepartmentCode,
                'is_active' => 'eq.true',
                'select' => 'id,employee_id,short_name,role,department_code',
            ]) ?? [];
        } elseif ($role === 'MANAGER') {
            $kpis = $supabase->get('kpis', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $user['department_code'],
                'financial_year' => 'eq.' . $fy,
                'select' => '*',
                'order' => 'created_at.desc',
            ]) ?? [];

            $employees = $supabase->get('employees', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $user['department_code'],
                'is_active' => 'eq.true',
                'select' => 'id,employee_id,short_name,role,department_code',
            ]) ?? [];
        } else {
            $kpis = $supabase->get('kpis', [
                'employee_id' => 'eq.' . $user['id'],
                'financial_year' => 'eq.' . $fy,
                'select' => '*',
                'order' => 'created_at.desc',
            ]) ?? [];

            $employees = $supabase->get('employees', [
                'id' => 'eq.' . $user['id'],
                'select' => 'id,employee_id,short_name,role,department_code',
            ]) ?? [];
        }

        $employeeMap = collect($employees)->keyBy('id');

        $creatorIds = collect($kpis)
            ->pluck('created_by')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $creators = [];

        if (!empty($creatorIds)) {
            $creators = $supabase->get('employees', [
                'id' => 'in.(' . implode(',', $creatorIds) . ')',
                'select' => 'id,employee_id,short_name,role,department_code',
            ]) ?? [];
        }

        $creatorMap = collect($creators)->keyBy('id');

        $kpis = collect($kpis)->map(function ($kpi) use ($employeeMap, $creatorMap, $supabase) {
            $employee = $employeeMap->get($kpi['employee_id']);
            $creator = $creatorMap->get($kpi['created_by'] ?? null);

            $kpi['employee_name'] = $employee['short_name'] ?? 'Unassigned';
            $kpi['employee_role'] = $employee['role'] ?? '-';
            $kpi['employee_code'] = $employee['employee_id'] ?? '-';
            $kpi['department_code'] = $kpi['department_code'] ?? ($employee['department_code'] ?? '-');

            $kpi['created_by_name'] = $creator['short_name'] ?? '-';
            $kpi['created_by_role'] = $creator['role'] ?? '-';

            $quarters = $supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $kpi['id'],
                'select' => '*',
                'order' => 'quarter.asc',
            ]) ?? [];

            $kpi['quarters'] = $quarters;
            $kpi['quarter_total_target'] = collect($quarters)->sum(fn ($q) => (float) ($q['quarter_target'] ?? 0));
            $kpi['quarter_total_actual'] = collect($quarters)->sum(fn ($q) => (float) ($q['quarter_actual'] ?? 0));

            $kpi['quarter_overall_progress'] = $kpi['quarter_total_target'] > 0
                ? round(($kpi['quarter_total_actual'] / $kpi['quarter_total_target']) * 100, 2)
                : 0;

            $today = now('Asia/Kuala_Lumpur')->toDateString();

            $currentQuarter = collect($quarters)->first(function ($q) use ($today) {
                return !empty($q['start_date'])
                    && !empty($q['end_date'])
                    && $q['start_date'] <= $today
                    && $q['end_date'] >= $today;
            });

            $kpi['current_quarter'] = $currentQuarter['quarter'] ?? '-';
            $kpi['current_quarter_end_date'] = $currentQuarter['end_date'] ?? null;

            return $kpi;
        })->toArray();

        $kpiCountByUser = collect($kpis)
            ->groupBy('employee_name')
            ->map(fn ($items) => [
                'total' => $items->count(),
                'department' => $items->first()['department_code'] ?? '-',
                'completed' => $items->where('status', 'completed')->count(),
                'at_risk' => $items->whereIn('status', ['at_risk', 'in_trouble'])->count(),
            ])
            ->sortByDesc('total');

        $kpiCountByDepartment = collect($kpis)
            ->groupBy('department_code')
            ->map(fn ($items) => [
                'total' => $items->count(),
                'staff_count' => $items->pluck('employee_name')->unique()->count(),
                'completed' => $items->where('status', 'completed')->count(),
                'at_risk' => $items->whereIn('status', ['at_risk', 'in_trouble'])->count(),
            ])
            ->sortByDesc('total');

        return view('kpi.index', array_merge([
            'user' => $user,
            'permission' => $permission,
            'employees' => $employees,
            'kpis' => $kpis,
            'fy' => $fy,
            'kpiCountByUser' => $kpiCountByUser,
            'kpiCountByDepartment' => $kpiCountByDepartment,
        ], $this->sidebarData($supabase, $user)));
    }

    public function create(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $fy = $request->get('financial_year', $this->currentFY());

        return view('kpi.create', array_merge([
            'user' => $user,
            'fy' => $fy,
        ], $this->sidebarData($supabase, $user)));
    }

    public function store(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $permission = $this->permissionForRoleFromDb($supabase, $user['role']);

        if (!$permission['can_create']) {
            abort(403, 'You cannot create KPI.');
        }

        $validated = $request->validate([
            'financial_year' => 'nullable|string',
            'category' => 'required|string',
            'sub_category' => 'required|string',
            'kpi_title' => 'required|string|max:255',
            'kpi_description' => 'nullable|string',
            'unit' => 'required|string|in:number,currency,percentage',
            'base_target' => 'required|numeric|min:0',
            'stretch_target' => 'required|numeric|min:0',
            'actual_value' => 'required|numeric|min:0',
            'status' => 'required|string|in:not_started,on_track,at_risk,in_trouble,completed',
            'remark' => 'nullable|string',

            'quarters' => 'nullable|array',
            'quarters.*.quarter' => 'nullable|string|in:Q1,Q2,Q3,Q4',
            'quarters.*.quarter_target' => 'nullable|numeric|min:0',
            'quarters.*.quarter_actual' => 'nullable|numeric|min:0',
            'quarters.*.start_date' => 'nullable|date',
            'quarters.*.end_date' => 'nullable|date',
            'quarters.*.remark' => 'nullable|string',
            'quarters.*.quarter_title' => 'nullable|string|max:255',
            'quarters.*.quarter_description' => 'nullable|string',
            'quarters.*.status' => 'nullable|string|in:not_started,on_track,at_risk,in_trouble,completed',
        ]);

        if ((float) $validated['stretch_target'] < (float) $validated['base_target']) {
            return back()
                ->withErrors(['stretch_target' => 'Stretch must be greater than or equal to Base.'])
                ->withInput();
        }

        $quarterDateError = $this->validateQuarterDates($validated['quarters'] ?? []);

        if ($quarterDateError) {
            return back()
                ->withErrors($quarterDateError)
                ->withInput();
        }

        $fy = $validated['financial_year'] ?? $this->currentFY();

        $achievement = $this->calculateAchievement(
            $validated['base_target'],
            $validated['stretch_target'],
            $validated['actual_value']
        );

        $payload = [
            'employee_id' => $user['id'],
            'department_code' => $user['department_code'],
            'company_code' => $user['company_code'],
            'created_by' => $user['id'],

            'financial_year' => $fy,
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'],
            'kpi_title' => $validated['kpi_title'],
            'kpi_description' => $validated['kpi_description'] ?? null,
            'unit' => $validated['unit'],
            'base_target' => $validated['base_target'],
            'stretch_target' => $validated['stretch_target'],
            'actual_value' => $validated['actual_value'],
            'achievement_percentage' => $achievement,
            'status' => $this->normalizeStatus($validated['status']),
            'remark' => $validated['remark'] ?? null,

            'created_at' => $this->nowMy(),
            'updated_at' => $this->nowMy(),
        ];

        $createdKpi = $supabase->insert('kpis', $payload);

        $kpiId = $createdKpi[0]['id'] ?? null;

        if (!$kpiId) {
            $latestKpi = $supabase->get('kpis', [
                'employee_id' => 'eq.' . $user['id'],
                'department_code' => 'eq.' . $user['department_code'],
                'financial_year' => 'eq.' . $fy,
                'kpi_title' => 'eq.' . $validated['kpi_title'],
                'select' => '*',
                'order' => 'created_at.desc',
                'limit' => '1',
            ]);

            $kpiId = $latestKpi[0]['id'] ?? null;
        }

        if (!$kpiId) {
            return back()
                ->withErrors(['kpi' => 'KPI created, but system failed to get KPI ID for quarter creation.'])
                ->withInput();
        }

        $this->upsertQuarters($supabase, $kpiId, $validated['quarters'] ?? []);

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI created successfully.');
    }

    public function update(Request $request, $id, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $permission = $this->permissionForRoleFromDb($supabase, $user['role']);

        if (!$permission['can_update']) {
            abort(403, 'You cannot update KPI.');
        }

        $oldKpi = $this->findKpiOrFail($supabase, $id);

        if (!$this->canEditKpi($user, $oldKpi)) {
            abort(403, 'You cannot update this KPI.');
        }

        $validated = $request->validate([
            'category' => 'required|string',
            'sub_category' => 'required|string',
            'kpi_title' => 'required|string|max:255',
            'kpi_description' => 'nullable|string',
            'unit' => 'required|string|in:number,currency,percentage',
            'base_target' => 'required|numeric|min:0',
            'stretch_target' => 'required|numeric|min:0',
            'actual_value' => 'required|numeric|min:0',
            'status' => 'required|string|in:not_started,on_track,at_risk,in_trouble,completed',
            'remark' => 'nullable|string',

            'quarters' => 'nullable|array',
            'quarters.*.quarter' => 'nullable|string|in:Q1,Q2,Q3,Q4',
            'quarters.*.quarter_target' => 'nullable|numeric|min:0',
            'quarters.*.quarter_actual' => 'nullable|numeric|min:0',
            'quarters.*.start_date' => 'nullable|date',
            'quarters.*.end_date' => 'nullable|date',
            'quarters.*.remark' => 'nullable|string',
            'quarters.*.quarter_title' => 'nullable|string|max:255',
            'quarters.*.quarter_description' => 'nullable|string',
            'quarters.*.status' => 'nullable|string|in:not_started,on_track,at_risk,in_trouble,completed',
        ]);

        if ((float) $validated['stretch_target'] < (float) $validated['base_target']) {
            return back()
                ->withErrors(['stretch_target' => 'Stretch must be greater than or equal to Base.'])
                ->withInput();
        }

        $quarterDateError = $this->validateQuarterDates($validated['quarters'] ?? []);

        if ($quarterDateError) {
            return back()
                ->withErrors($quarterDateError)
                ->withInput();
        }

        $achievement = $this->calculateAchievement(
            $validated['base_target'],
            $validated['stretch_target'],
            $validated['actual_value']
        );

        $updateData = [
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'],
            'kpi_title' => $validated['kpi_title'],
            'kpi_description' => $validated['kpi_description'] ?? null,
            'base_target' => $validated['base_target'],
            'stretch_target' => $validated['stretch_target'],
            'unit' => $validated['unit'],
            'actual_value' => $validated['actual_value'],
            'achievement_percentage' => $achievement,
            'status' => $this->normalizeStatus($validated['status']),
            'remark' => $validated['remark'] ?? null,
            'updated_at' => $this->nowMy(),
        ];

        $supabase->update('kpis', [
            'id' => 'eq.' . $id,
        ], $updateData);

        $this->upsertQuarters($supabase, $id, $validated['quarters'] ?? []);
        $this->recordKpiHistory($supabase, $oldKpi, $updateData, $id, $user);

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI updated successfully.');
    }

    public function destroy(string $id, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $kpis = $supabase->get('kpis', [
            'id' => 'eq.' . $id,
            'company_code' => 'eq.' . $user['company_code'],
            'select' => '*',
        ]);

        if (empty($kpis)) {
            return back()->with('error', 'KPI tidak dijumpai.');
        }

        $kpi = $kpis[0];

        $canDelete =
            in_array(strtoupper($user['role']), ['ADMIN', 'SLT', 'CCO', 'CCMO', 'VP'])
            || (($kpi['employee_id'] ?? null) === ($user['id'] ?? null))
            || (($kpi['created_by'] ?? null) === ($user['id'] ?? null));

        if (!$canDelete) {
            return back()->with('error', 'Anda tiada akses untuk padam KPI ini.');
        }

        $supabase->delete('kpi_quarters', [
            'kpi_id' => 'eq.' . $id,
        ]);

        $supabase->delete('kpi_histories', [
            'kpi_id' => 'eq.' . $id,
        ]);

        $supabase->delete('kpi_target_change_requests', [
            'kpi_id' => 'eq.' . $id,
        ]);

        $supabase->delete('kpis', [
            'id' => 'eq.' . $id,
            'company_code' => 'eq.' . $user['company_code'],
        ]);

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI berjaya dipadam.');
    }

    public function switchDepartment(Request $request)
    {
        $request->validate([
            'department_code' => 'required|string',
        ]);

        session([
            'selected_department_code' => $request->department_code,
        ]);

        return back();
    }

    private function canEditKpi(array $user, array $kpi): bool
    {
        $role = strtoupper(trim($user['role'] ?? ''));

        if (in_array($role, ['ADMIN', 'SLT', 'CCO', 'CCMO'])) {
            return ($user['company_code'] ?? null) === ($kpi['company_code'] ?? null);
        }

        if ($role === 'VP') {
            return ($user['company_code'] ?? null) === ($kpi['company_code'] ?? null)
                && ($user['department_code'] ?? null) === ($kpi['department_code'] ?? null);
        }

        if ($role === 'MANAGER') {
            return ($user['company_code'] ?? null) === ($kpi['company_code'] ?? null)
                && ($user['department_code'] ?? null) === ($kpi['department_code'] ?? null);
        }

        if ($role === 'EXECUTIVE') {
            return ($kpi['employee_id'] ?? null) === ($user['id'] ?? null);
        }

        return false;
    }

    private function upsertQuarters(SupabaseService $supabase, string $kpiId, array $quarters): void
    {
        $year = (int) now('Asia/Kuala_Lumpur')->year;

        $defaultDates = [
            'Q1' => ['start' => $year . '-01-01', 'end' => $year . '-03-31'],
            'Q2' => ['start' => $year . '-04-01', 'end' => $year . '-06-30'],
            'Q3' => ['start' => $year . '-07-01', 'end' => $year . '-09-30'],
            'Q4' => ['start' => $year . '-10-01', 'end' => $year . '-12-31'],
        ];

        foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarterLabel) {
            $quarter = $quarters[$quarterLabel] ?? [];

            $payload = [
                'kpi_id' => $kpiId,
                'quarter' => $quarterLabel,
                'quarter_title' => $quarter['quarter_title'] ?? ($quarterLabel . ' Plan'),
                'quarter_description' => $quarter['quarter_description'] ?? null,
                'quarter_target' => isset($quarter['quarter_target']) && $quarter['quarter_target'] !== ''
                    ? (float) $quarter['quarter_target']
                    : 0,
                'quarter_actual' => isset($quarter['quarter_actual']) && $quarter['quarter_actual'] !== ''
                    ? (float) $quarter['quarter_actual']
                    : 0,
                'start_date' => !empty($quarter['start_date'])
                    ? $quarter['start_date']
                    : $defaultDates[$quarterLabel]['start'],
                'end_date' => !empty($quarter['end_date'])
                    ? $quarter['end_date']
                    : $defaultDates[$quarterLabel]['end'],
                'status' => $this->normalizeStatus($quarter['status'] ?? 'not_started'),
                'remark' => $quarter['remark'] ?? null,
                'updated_at' => $this->nowMy(),
            ];

            $existingQuarter = $supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $kpiId,
                'quarter' => 'eq.' . $quarterLabel,
                'select' => '*',
                'limit' => 1,
            ]);

            if (!empty($existingQuarter)) {
                $supabase->update('kpi_quarters', [
                    'id' => 'eq.' . $existingQuarter[0]['id'],
                ], $payload);
            } else {
                $payload['created_at'] = $this->nowMy();
                $supabase->insert('kpi_quarters', $payload);
            }
        }
    }

    private function validateQuarterDates(array $quarters): ?array
    {
        foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarterLabel) {
            $quarter = $quarters[$quarterLabel] ?? [];

            $startDate = $quarter['start_date'] ?? null;
            $endDate = $quarter['end_date'] ?? null;

            if (empty($startDate) || empty($endDate)) {
                continue;
            }

            if ($endDate < $startDate) {
                return [
                    "quarters.$quarterLabel.end_date" => "{$quarterLabel}: End date mesti selepas atau sama dengan start date.",
                ];
            }
        }

        return null;
    }

    private function findKpiOrFail(SupabaseService $supabase, string $id): array
    {
        $kpi = $supabase->get('kpis', [
            'id' => 'eq.' . $id,
            'select' => '*',
        ])[0] ?? null;

        if (!$kpi) {
            abort(404, 'KPI not found.');
        }

        return $kpi;
    }

    private function calculateAchievement($baseTarget, $stretchTarget, $actualValue): float
    {
        $base = max(0, (float) ($baseTarget ?? 0));
        $stretch = max($base, (float) ($stretchTarget ?? 0));
        $actual = max(0, (float) ($actualValue ?? 0));

        if ($base <= 0) {
            return 0;
        }

        if ($actual <= $base) {
            return round(($actual / $base) * 100, 2);
        }

        if ($stretch > $base) {
            return round(min(200, 100 + (($actual - $base) / ($stretch - $base)) * 100), 2);
        }

        return 100;
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'not_started' => 'not_started',
            'on_track', 'monitoring' => 'on_track',
            'at_risk', 'risk' => 'at_risk',
            'in_trouble', 'critical', 'off_track', 'overdue' => 'in_trouble',
            'completed' => 'completed',
            default => 'not_started',
        };
    }

    private function recordKpiHistory(
        SupabaseService $supabase,
        array $oldKpi,
        array $newData,
        string $kpiId,
        array $user
    ): void {
        $fieldsToTrack = [
            'category',
            'sub_category',
            'kpi_title',
            'kpi_description',
            'base_target',
            'stretch_target',
            'actual_value',
            'unit',
            'status',
            'remark',
        ];

        foreach ($fieldsToTrack as $field) {
            $oldValue = (string) ($oldKpi[$field] ?? '');
            $newValue = (string) ($newData[$field] ?? '');

            if ($oldValue !== $newValue) {
                $supabase->insert('kpi_histories', [
                    'kpi_id' => $kpiId,
                    'edited_by' => $user['id'] ?? null,
                    'edited_by_name' => $user['short_name'] ?? 'Unknown',
                    'field_name' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'created_at' => $this->nowMy(),
                ]);
            }
        }
    }
}
