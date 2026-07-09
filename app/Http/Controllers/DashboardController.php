<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private string $currentFinancialYear = 'FY2026';

    public function index(SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login')->with('error', 'Sila login terlebih dahulu.');
        }

        $user = $this->getCurrentUser($supabase);

        $company = $supabase->get('companies', [
            'code' => 'eq.' . session('company_code'),
            'select' => '*'
        ])[0] ?? null;

        if ($company) {
            session([
                'company_logo' => $company['logo_path'] ?: null,
                'company_display_name' => $company['display_name'] ?: $company['name'],
            ]);
        }

        if (!$user) {
            session()->flush();
            return redirect()->route('login')->with('error', 'Session tidak sah. Sila login semula.');
        }

        $companyCode = session('company_code');

        $department = $this->getUserDepartment($supabase, $user);
        $canSwitchDepartment = $this->canSwitchDepartment($user);

        $departments = $canSwitchDepartment
            ? $this->getAllDepartments($supabase, $companyCode)
            : [];

        $selectedDepartmentCode = $this->getSelectedDepartmentCode($user, $canSwitchDepartment);

        $visibleEmployeeIds = $this->getVisibleEmployeeIds(
            $supabase,
            $user,
            $selectedDepartmentCode,
            $companyCode
        );

        $kpis = $this->getKpis($supabase, $visibleEmployeeIds, $companyCode);

        $kpis = $this->attachQuartersToKpis($supabase, $kpis);
        $kpis = $this->attachEmployeeDataToKpis($supabase, $kpis, $user, $companyCode);
        $kpis = $this->attachHistoryToKpis($supabase, $kpis);

        $showOwnerColumn = $this->shouldShowOwnerColumn($kpis);

        $subCategoryByCompany = collect($kpis)
            ->groupBy(fn ($kpi) => $kpi['sub_category'] ?: 'Uncategorized')
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $subCategoryByDepartment = collect($kpis)
            ->groupBy(fn ($kpi) => $kpi['department_code'] ?? 'Unknown Department')
            ->map(function ($departmentItems) {
                return $departmentItems
                    ->groupBy(fn ($kpi) => $kpi['sub_category'] ?: 'Uncategorized')
                    ->map(fn ($items) => $items->count())
                    ->sortDesc();
            });

        $subCategoryByUser = collect($kpis)
            ->groupBy(fn ($kpi) => $kpi['owner_display_name'] ?? 'Unknown User')
            ->map(function ($userItems) {
                return $userItems
                    ->groupBy(fn ($kpi) => $kpi['sub_category'] ?: 'Uncategorized')
                    ->map(fn ($items) => $items->count())
                    ->sortDesc();
            });

        $kpiCountByUser = collect($kpis)
            ->groupBy(fn ($kpi) => $kpi['owner_display_name'] ?? 'Unknown User')
            ->map(function ($items) {
                return [
                    'total' => $items->count(),
                    'department' => $items->first()['owner_department_code']
                        ?? $items->first()['department_code']
                        ?? '-',
                    'completed' => $items->where('status', 'completed')->count(),
                    'at_risk' => $items->whereIn('status', ['at_risk', 'in_trouble'])->count(),
                ];
            })
            ->sortByDesc('total');

        $kpiCountByDepartment = collect($kpis)
            ->groupBy(fn ($kpi) => $kpi['department_code'] ?? 'Unknown Department')
            ->map(function ($items) {
                return [
                    'total' => $items->count(),
                    'staff_count' => $items->pluck('owner_display_name')->unique()->count(),
                    'completed' => $items->where('status', 'completed')->count(),
                    'at_risk' => $items->whereIn('status', ['at_risk', 'in_trouble'])->count(),
                ];
            })
            ->sortByDesc('total');

        $summary = $this->calculateSummary($kpis);
        $weightageSummary = $this->calculateWeightageSummary($kpis);

        $rankingResult       = $this->getCompanyDeptPerformance($supabase, $companyCode);
        $companyDeptRanking  = $rankingResult['depts'] ?? [];
        $companyTotalStaff   = $rankingResult['total_staff'] ?? 0;
        $companyTotalDepts   = $rankingResult['total_depts'] ?? 0;
        $allCompanyEmployees = $rankingResult['employees'] ?? [];

        // ── KPI LINKAGES (cascading targets) ────────────────────────────────
        $fy       = $this->currentFinancialYear;
        $userId   = $user['id'];
        $userRole = strtoupper(trim($user['role'] ?? ''));

        $incomingLinkages = $supabase->get('kpi_linkages', [
            'assignee_id'    => 'eq.' . $userId,
            'financial_year' => 'eq.' . $fy,
            'company_code'   => 'eq.' . $companyCode,
            'select'         => '*',
        ]) ?? [];

        $outgoingLinkages = $supabase->get('kpi_linkages', [
            'assigner_id'    => 'eq.' . $userId,
            'financial_year' => 'eq.' . $fy,
            'company_code'   => 'eq.' . $companyCode,
            'select'         => '*',
        ]) ?? [];

        $directReports = [];
        if ($userRole === 'SLT') {
            $directReports = $supabase->get('employees', [
                'company_code' => 'eq.' . $companyCode,
                'role'         => 'eq.VP',
                'is_active'    => 'eq.true',
                'select'       => 'id,short_name,role',
                'order'        => 'short_name.asc',
            ]) ?? [];
        } elseif ($userRole === 'VP') {
            $byVpId = $supabase->get('employees', [
                'company_code' => 'eq.' . $companyCode,
                'vp_id'        => 'eq.' . $userId,
                'is_active'    => 'eq.true',
                'select'       => 'id,short_name,role',
            ]) ?? [];
            $byReportsTo = $supabase->get('employees', [
                'company_code'  => 'eq.' . $companyCode,
                'reports_to_id' => 'eq.' . $userId,
                'is_active'     => 'eq.true',
                'select'        => 'id,short_name,role',
            ]) ?? [];
            $drIds = collect($byVpId)->pluck('id')->toArray();
            foreach ($byReportsTo as $r) {
                if (!in_array($r['id'], $drIds)) { $byVpId[] = $r; $drIds[] = $r['id']; }
            }
            $directReports = $byVpId;
        } elseif ($userRole === 'MANAGER') {
            $directReports = $supabase->get('employees', [
                'company_code' => 'eq.' . $companyCode,
                'manager_id'   => 'eq.' . $userId,
                'is_active'    => 'eq.true',
                'select'       => 'id,short_name,role',
                'order'        => 'short_name.asc',
            ]) ?? [];
        }

        return view('dashboard', [
            'user' => $user,
            'department' => $department,
            'departments' => $departments,
            'canSwitchDepartment' => $canSwitchDepartment,
            'selectedDepartmentCode' => $selectedDepartmentCode,
            'currentFinancialYear' => $this->currentFinancialYear,
            'showOwnerColumn' => $showOwnerColumn,
            'kpis' => $kpis,

            'overallScore' => $summary['overallScore'],
            'totalKpis' => $summary['totalKpis'],
            'completed' => $summary['completed'],
            'monitoring' => $summary['onTrack'],
            'risk' => $summary['atRisk'],
            'critical' => $summary['inTrouble'],

            'onTrack' => $summary['onTrack'],
            'atRisk' => $summary['atRisk'],
            'offTrack' => $summary['inTrouble'],
            'overdue' => $summary['inTrouble'],

            'totalWeightage' => $weightageSummary['totalWeightage'],
            'isWeightageExceeded' => $weightageSummary['isWeightageExceeded'],
            'isWeightageComplete' => $weightageSummary['isWeightageComplete'],

            'companyCode' => $companyCode,
            'companyName' => session('company_name'),

            'subCategoryByCompany' => $subCategoryByCompany,
            'subCategoryByDepartment' => $subCategoryByDepartment,
            'subCategoryByUser' => $subCategoryByUser,

            'kpiCountByUser' => $kpiCountByUser,
            'kpiCountByDepartment' => $kpiCountByDepartment,

            'companyDeptRanking'  => $companyDeptRanking,
            'companyTotalStaff'   => $companyTotalStaff,
            'companyTotalDepts'   => $companyTotalDepts,
            'allEmployees'        => $allCompanyEmployees,

            'incomingLinkages' => $incomingLinkages,
            'outgoingLinkages' => $outgoingLinkages,
            'directReports'    => $directReports,
        ]);
    }

    public function switchDepartment(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login')->with('error', 'Sila login terlebih dahulu.');
        }

        $user = $this->getCurrentUser($supabase);

        if (!$user || !$this->canSwitchDepartment($user)) {
            abort(403, 'Anda tiada akses untuk tukar department.');
        }

        $request->validate([
            'department_code' => 'required|string',
        ]);

        session([
            'selected_department_code' => $request->department_code,
        ]);

        return back();
    }

    private function getCurrentUser(SupabaseService $supabase): ?array
    {
        $employees = $supabase->get('employees', [
            'id' => 'eq.' . session('employee_uuid'),
            'is_active' => 'eq.true',
            'select' => '*',
        ]);

        return $employees[0] ?? null;
    }

    private function getUserDepartment(SupabaseService $supabase, array $user): ?array
    {
        $departments = $supabase->get('departments', [
            'code' => 'eq.' . $user['department_code'],
            'select' => '*',
        ]);

        return $departments[0] ?? null;
    }

    private function canSwitchDepartment(array $user): bool
    {
        return ($user['role'] ?? '') === 'SLT';
    }

    private function getAllDepartments(SupabaseService $supabase, string $companyCode): array
    {
        return $supabase->get('departments', [
            'company_code' => 'eq.' . $companyCode,
            'select' => '*',
            'order' => 'name.asc',
        ]);
    }

    private function getSelectedDepartmentCode(array $user, bool $canSwitchDepartment): string
    {
        if ($canSwitchDepartment) {
            // Dept switcher UI removed — always show all departments, clear any stale session value
            session()->forget('selected_department_code');
            return 'ALL';
        }

        session()->forget('selected_department_code');

        return $user['department_code'];
    }

    private function getVisibleEmployeeIds(
        SupabaseService $supabase,
        array $user,
        string $selectedDepartmentCode,
        string $companyCode
    ): array {
        $role = $user['role'] ?? '';

        if ($role === 'SLT') {
            $filters = [
                'company_code' => 'eq.' . $companyCode,
                'is_active' => 'eq.true',
                'select' => 'id',
            ];

            if ($selectedDepartmentCode !== 'ALL') {
                $filters['department_code'] = 'eq.' . $selectedDepartmentCode;
            }

            $employees = $supabase->get('employees', $filters);

            return collect($employees)->pluck('id')->toArray();
        }

        if (in_array($role, ['VP', 'MANAGER'])) {
            $employees = $supabase->get('employees', [
                'company_code' => 'eq.' . $companyCode,
                'is_active'    => 'eq.true',
                'select'       => 'id',
            ]);

            return collect($employees)->pluck('id')->toArray();
        }

        return [$user['id']];
    }

    private function getKpis(
        SupabaseService $supabase,
        array $visibleEmployeeIds,
        string $companyCode
    ): array {
        if (empty($visibleEmployeeIds)) {
            return [];
        }

        return $supabase->get('kpis', [
            'company_code' => 'eq.' . $companyCode,
            'employee_id' => 'in.(' . implode(',', $visibleEmployeeIds) . ')',
            'financial_year' => 'eq.' . $this->currentFinancialYear,
            'select' => '*',
            'order' => 'created_at.desc',
        ]);
    }

    private function attachQuartersToKpis(SupabaseService $supabase, array $kpis): array
    {
        if (empty($kpis)) {
            return [];
        }

        $kpiIds = collect($kpis)->pluck('id')->filter()->values()->toArray();

        if (empty($kpiIds)) {
            return $kpis;
        }

        $quarters = $supabase->get('kpi_quarters', [
            'kpi_id' => 'in.(' . implode(',', $kpiIds) . ')',
            'select' => '*',
            'order' => 'quarter.asc',
        ]);

        $quarterMap = collect($quarters)->groupBy('kpi_id');

        return collect($kpis)->map(function ($kpi) use ($quarterMap) {
            $kpiQuarters = $quarterMap
                ->get($kpi['id'], collect())
                ->sortBy('quarter')
                ->values();

            $kpi['quarters'] = $kpiQuarters->toArray();

            $kpi['quarter_total_target'] = $kpiQuarters->sum(function ($quarter) {
                return (float) ($quarter['quarter_target'] ?? 0);
            });

            $kpi['quarter_total_actual'] = $kpiQuarters->sum(function ($quarter) {
                return (float) ($quarter['quarter_actual'] ?? 0);
            });

            return $kpi;
        })->values()->toArray();
    }

    private function attachEmployeeDataToKpis(
        SupabaseService $supabase,
        array $kpis,
        array $user,
        string $companyCode
    ): array {
        if (empty($kpis)) {
            return [];
        }

        $employees = $supabase->get('employees', [
            'company_code' => 'eq.' . $companyCode,
            'is_active' => 'eq.true',
            'select' => 'id,employee_id,short_name,full_name,email,role,department_code',
        ]);

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
                'select' => 'id,employee_id,short_name,role,department_code'
            ]);
        }

        $creatorMap = collect($creators)->keyBy('id');

        return collect($kpis)->map(function ($kpi) use ($employeeMap, $user) {
            $owner = $employeeMap->get($kpi['employee_id']);

            $base = (float) ($kpi['base_target'] ?? 0);
            $stretch = (float) ($kpi['stretch_target'] ?? 0);

            $actual = isset($kpi['quarter_total_actual'])
                ? (float) $kpi['quarter_total_actual']
                : (float) ($kpi['actual_value'] ?? 0);

            $targetForAchievement = isset($kpi['quarter_total_target']) && (float) $kpi['quarter_total_target'] > 0
                ? (float) $kpi['quarter_total_target']
                : $base;

            if ($targetForAchievement <= 0) {
                $achievement = 0;
            } else {
                $achievement = round(($actual / $targetForAchievement) * 100, 2);
            }

            $achievement = min(max($achievement, 0), 200);

            $isSelf = ($kpi['employee_id'] ?? null) === ($user['id'] ?? null);

            $ownerName = $owner['short_name']
                ?? $owner['full_name']
                ?? 'Unknown';

            $kpi['is_self'] = $isSelf;
            $kpi['owner_name'] = $isSelf ? null : $ownerName;
            $kpi['owner_role'] = $owner['role'] ?? '-';

            $kpi['owner_display_name'] = $ownerName;
            $kpi['owner_department_code'] = $owner['department_code'] ?? $kpi['department_code'] ?? null;

            $kpi['base_target'] = $base;
            $kpi['stretch_target'] = $stretch;
            $kpi['actual_value'] = $actual;
            $kpi['achievement_percentage'] = $achievement;

            $kpi['status'] = $this->normalizeStatus($kpi['status'] ?? null);
            $kpi['unit'] = $kpi['unit'] ?? 'number';
            $kpi['remark'] = $kpi['remark'] ?? '-';

            return $kpi;
        })->values()->toArray();
    }

    private function attachHistoryToKpis(SupabaseService $supabase, array $kpis): array
    {
        if (empty($kpis)) {
            return [];
        }

        $kpiIds = collect($kpis)->pluck('id')->toArray();

        $histories = $supabase->get('kpi_histories', [
            'kpi_id' => 'in.(' . implode(',', $kpiIds) . ')',
            'select' => '*',
            'order' => 'created_at.desc',
        ]);

        $historyMap = collect($histories)->groupBy('kpi_id');

        return collect($kpis)->map(function ($kpi) use ($historyMap) {
            $histories = $historyMap->get($kpi['id'], collect())->values()->toArray();

            $kpi['histories'] = $histories;

            $latestHistory = $histories[0] ?? null;

            $kpi['last_edited_at'] = $latestHistory['created_at'] ?? $kpi['updated_at'] ?? null;
            $kpi['last_edited_by'] = $latestHistory['edited_by_name'] ?? null;

            return $kpi;
        })->values()->toArray();
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

    private function shouldShowOwnerColumn(array $kpis): bool
    {
        return collect($kpis)->where('is_self', false)->count() > 0;
    }

    private function calculateWeightageSummary(array $kpis): array
    {
        $totalWeightage = collect($kpis)->sum(function ($kpi) {
            return (float) ($kpi['weightage'] ?? 0);
        });

        return [
            'totalWeightage' => round($totalWeightage, 2),
            'isWeightageExceeded' => $totalWeightage > 100,
            'isWeightageComplete' => round($totalWeightage, 2) == 100,
        ];
    }

    private function getCompanyDeptPerformance(SupabaseService $supabase, string $companyCode): array
    {
        $employees = $supabase->get('employees', [
            'company_code' => 'eq.' . $companyCode,
            'is_active'    => 'eq.true',
            'select'       => 'id,short_name,full_name,role,department_code',
        ]);

        if (empty($employees)) return ['depts' => [], 'total_staff' => 0, 'total_depts' => 0];

        $empIds     = collect($employees)->pluck('id')->filter()->values()->toArray();
        $empDeptMap = collect($employees)->pluck('department_code', 'id');

        // All depts with actual employee counts (includes depts with no KPIs)
        $allDeptStaff = [];
        foreach ($employees as $emp) {
            $d = $emp['department_code'] ?? '-';
            $allDeptStaff[$d] = ($allDeptStaff[$d] ?? 0) + 1;
        }

        $kpis = $supabase->get('kpis', [
            'company_code'   => 'eq.' . $companyCode,
            'employee_id'    => 'in.(' . implode(',', $empIds) . ')',
            'financial_year' => 'eq.' . $this->currentFinancialYear,
            'select'         => 'id,employee_id,weightage,base_target,actual_value',
        ]);

        // Score accumulator per dept (employees with KPIs only)
        $deptScores = [];
        if (!empty($kpis)) {
            $kpiIds     = collect($kpis)->pluck('id')->filter()->values()->toArray();
            $quarters   = $supabase->get('kpi_quarters', [
                'kpi_id' => 'in.(' . implode(',', $kpiIds) . ')',
                'select' => 'kpi_id,quarter_target,quarter_actual',
            ]);
            $quarterMap = collect($quarters ?? [])->groupBy('kpi_id');

            $empScores = [];
            foreach ($kpis as $kpi) {
                $empId  = $kpi['employee_id'];
                $weight = (float)($kpi['weightage'] ?? 0);
                if ($weight <= 0) continue;

                $kpiQuarters = $quarterMap->get($kpi['id'], collect());
                $qTarget = $kpiQuarters->sum(fn($q) => max(0, (float)($q['quarter_target'] ?? 0)));
                $qActual = $kpiQuarters->sum(fn($q) => max(0, (float)($q['quarter_actual'] ?? 0)));

                if ($qTarget > 0) {
                    $pct = ($qActual / $qTarget) * 100;
                } else {
                    $base   = max(0, (float)($kpi['base_target']   ?? 0));
                    $actual = max(0, (float)($kpi['actual_value']   ?? 0));
                    $pct    = $base > 0 ? ($actual / $base) * 100 : 0;
                }
                $empScores[$empId] = ($empScores[$empId] ?? 0) + ($pct * $weight / 100);
            }

            foreach ($empScores as $empId => $score) {
                $d = $empDeptMap->get($empId, '-');
                $deptScores[$d]['total'] = ($deptScores[$d]['total'] ?? 0) + $score;
                $deptScores[$d]['count'] = ($deptScores[$d]['count'] ?? 0) + 1;
            }
        }

        // Build result for ALL departments — divide by total staff (including those with 0 KPIs)
        $result = [];
        foreach ($allDeptStaff as $deptCode => $staffCount) {
            $s = $deptScores[$deptCode] ?? null;
            $result[] = [
                'code'  => $deptCode,
                'score' => $s && $staffCount > 0 ? round($s['total'] / $staffCount, 2) : 0,
                'staff' => $staffCount,
            ];
        }

        usort($result, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'depts'       => $result,
            'total_staff' => count($employees),
            'total_depts' => count($allDeptStaff),
            'employees'   => $employees,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | STAFF KPI DRILL-DOWN — SLT Office only
    |--------------------------------------------------------------------------
    */

    private function requireSltOffice(array $user): void
    {
        if (strtoupper(trim($user['department_code'] ?? '')) !== 'SLT OFFICE') {
            abort(403, 'This page is only accessible to SLT Office.');
        }
    }

    public function staffKpis(string $employeeId, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login')->with('error', 'Sila login terlebih dahulu.');
        }

        $user = $this->getCurrentUser($supabase);
        if (!$user) {
            session()->flush();
            return redirect()->route('login')->with('error', 'Session tidak sah. Sila login semula.');
        }

        $this->requireSltOffice($user);

        $companyCode = session('company_code');

        $staff = $supabase->get('employees', [
            'id'            => 'eq.' . $employeeId,
            'company_code'  => 'eq.' . $companyCode,
            'is_active'     => 'eq.true',
            'select'        => '*',
        ])[0] ?? null;

        if (!$staff) {
            abort(404, 'Staff not found.');
        }

        $kpis = $supabase->get('kpis', [
            'employee_id'    => 'eq.' . $employeeId,
            'company_code'   => 'eq.' . $companyCode,
            'financial_year' => 'eq.' . $this->currentFinancialYear,
            'select'         => '*',
            'order'          => 'created_at.desc',
        ]) ?? [];

        $kpiIds = collect($kpis)->pluck('id')->filter()->values()->toArray();

        $quarterMap = collect();
        if (!empty($kpiIds)) {
            $quarters = $supabase->get('kpi_quarters', [
                'kpi_id' => 'in.(' . implode(',', $kpiIds) . ')',
                'select' => '*',
            ]) ?? [];
            $quarterMap = collect($quarters)->groupBy('kpi_id');
        }

        $kpis = collect($kpis)->map(function ($kpi) use ($quarterMap) {
            $qs      = $quarterMap->get($kpi['id'], collect());
            $qTarget = $qs->sum(fn ($q) => max(0, (float) ($q['quarter_target'] ?? 0)));
            $qActual = $qs->sum(fn ($q) => max(0, (float) ($q['quarter_actual'] ?? 0)));

            $base   = max(0, (float) ($kpi['base_target']   ?? 0));
            $actual = max(0, (float) ($kpi['actual_value']  ?? 0));

            $target = $qTarget > 0 ? $qTarget : $base;
            $act    = $qTarget > 0 ? $qActual : $actual;

            $kpi['display_target'] = $target;
            $kpi['display_actual'] = $act;
            $kpi['progress_pct']   = $target > 0 ? round(($act / $target) * 100, 1) : 0;
            $kpi['quarters_filled'] = $qs->filter(fn ($q) => (float) ($q['quarter_actual'] ?? 0) > 0)->count();

            // Per-quarter detail (target, actual, progress, quarter title) for the compact quarter strip
            $kpi['quarters'] = collect(['Q1', 'Q2', 'Q3', 'Q4'])->map(function ($label) use ($qs) {
                $row    = $qs->firstWhere('quarter', $label);
                $target = max(0, (float) ($row['quarter_target'] ?? 0));
                $actual = max(0, (float) ($row['quarter_actual'] ?? 0));

                return [
                    'label'        => $label,
                    'quarter_title' => $row['quarter_title'] ?? null,
                    'target'       => $target,
                    'actual'       => $actual,
                    'progress_pct' => $target > 0 ? round(($actual / $target) * 100, 1) : 0,
                    'has_data'     => $row !== null,
                ];
            })->values()->all();

            return $kpi;
        })->values()->all();

        $staffDepartment = $supabase->get('departments', [
            'code'   => 'eq.' . ($staff['department_code'] ?? ''),
            'select' => '*',
        ])[0] ?? null;

        $totalWeight   = collect($kpis)->sum(fn ($k) => (float) ($k['weightage'] ?? 0));
        $weightedScore = collect($kpis)->sum(fn ($k) => (float) ($k['weightage'] ?? 0) > 0
            ? ($k['progress_pct'] * (float) ($k['weightage'] ?? 0) / 100)
            : 0);

        return view('dashboard.staff-kpis', [
            'user'                 => $user,
            'department'           => $this->getUserDepartment($supabase, $user),
            'staff'                => $staff,
            'kpis'                 => $kpis,
            'departmentName'       => $staffDepartment['name'] ?? $staff['department_code'] ?? '-',
            'currentFinancialYear' => $this->currentFinancialYear,
            'totalWeight'          => round($totalWeight, 2),
            'weightedScore'        => round($weightedScore, 2),
        ]);
    }

    public function staffKpiDetail(string $employeeId, string $kpiId, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login')->with('error', 'Sila login terlebih dahulu.');
        }

        $user = $this->getCurrentUser($supabase);
        if (!$user) {
            session()->flush();
            return redirect()->route('login')->with('error', 'Session tidak sah. Sila login semula.');
        }

        $this->requireSltOffice($user);

        $companyCode = session('company_code');

        $staff = $supabase->get('employees', [
            'id'           => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'is_active'    => 'eq.true',
            'select'       => '*',
        ])[0] ?? null;

        if (!$staff) {
            abort(404, 'Staff not found.');
        }

        $kpi = $supabase->get('kpis', [
            'id'           => 'eq.' . $kpiId,
            'employee_id'  => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'select'       => '*',
        ])[0] ?? null;

        if (!$kpi) {
            abort(404, 'KPI not found.');
        }

        $quarters = $supabase->get('kpi_quarters', [
            'kpi_id' => 'eq.' . $kpiId,
            'select' => '*',
        ]) ?? [];

        $quarters = collect($quarters)->map(function ($q) {
            $target = max(0, (float) ($q['quarter_target'] ?? 0));
            $actual = max(0, (float) ($q['quarter_actual'] ?? 0));
            $q['progress_pct'] = $target > 0 ? round(($actual / $target) * 100, 1) : 0;
            return $q;
        })->sortBy('quarter')->values()->all();

        $filledQuarters = collect($quarters)->filter(
            fn ($q) => (float) ($q['quarter_actual'] ?? 0) > 0
        );

        $average = $filledQuarters->count() > 0
            ? round($filledQuarters->avg('progress_pct'), 1)
            : 0;

        return view('dashboard.staff-kpi-detail', [
            'user'                 => $user,
            'department'           => $this->getUserDepartment($supabase, $user),
            'staff'                => $staff,
            'kpi'                  => $kpi,
            'quarters'             => $quarters,
            'average'              => $average,
            'currentFinancialYear' => $this->currentFinancialYear,
        ]);
    }

    private function calculateSummary(array $kpis): array
    {
        $collection = collect($kpis);

        $totalWeighted = 0;
        $totalWeight = 0;

        foreach ($collection as $kpi) {
            $weight = (float) ($kpi['weightage'] ?? 0);
            $achievement = (float) ($kpi['achievement_percentage'] ?? 0);

            if ($weight <= 0) {
                continue;
            }

            $totalWeighted += $achievement * $weight;
            $totalWeight += $weight;
        }

        $overallScore = $totalWeight > 0
            ? round($totalWeighted / $totalWeight, 2)
            : 0;

        return [
            'overallScore' => $overallScore,
            'totalKpis' => $collection->count(),
            'completed' => $collection->where('status', 'completed')->count(),
            'onTrack' => $collection->where('status', 'on_track')->count(),
            'atRisk' => $collection->where('status', 'at_risk')->count(),
            'inTrouble' => $collection->where('status', 'in_trouble')->count(),
        ];
    }
}
