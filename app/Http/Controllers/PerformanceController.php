<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;

class PerformanceController extends Controller
{
    private string $currentFinancialYear = 'FY2026';

    public function kpiAppraisal(SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login');
        }

        $employees = $supabase->get('employees', [
            'id'        => 'eq.' . session('employee_uuid'),
            'is_active' => 'eq.true',
            'select'    => '*',
        ]);
        $user = $employees[0] ?? null;

        if (!$user) {
            session()->flush();
            return redirect()->route('login');
        }

        // Reporting-to (approver)
        $reportsTo = null;
        if (!empty($user['reports_to_id'])) {
            $managers  = $supabase->get('employees', [
                'id'     => 'eq.' . $user['reports_to_id'],
                'select' => 'id,short_name,full_name,role',
            ]);
            $reportsTo = $managers[0] ?? null;
        }

        // Department
        $department = null;
        if (!empty($user['department_code'])) {
            $depts      = $supabase->get('departments', [
                'code'   => 'eq.' . $user['department_code'],
                'select' => '*',
            ]);
            $department = $depts[0] ?? null;
        }

        // ── Quarter logic ─────────────────────────────────────────────────────
        $now   = now();
        $month = (int) $now->format('n');
        $year  = (int) $now->format('Y');

        // Calendar-year quarters
        $quarterOfMonth = match(true) {
            $month <= 3 => 1,
            $month <= 6 => 2,
            $month <= 9 => 3,
            default     => 4,
        };

        // Submission windows: last ~week of quarter + first ~week of next quarter
        $windows = [
            1 => ['start' => "{$year}-03-24", 'end' => "{$year}-04-07"],
            2 => ['start' => "{$year}-06-23", 'end' => "{$year}-07-07"],
            3 => ['start' => "{$year}-09-22", 'end' => "{$year}-10-06"],
            4 => ['start' => "{$year}-12-23", 'end' => ($year + 1) . "-01-06"],
        ];

        // Check if we are currently inside any submission window
        $displayQuarter = $quarterOfMonth;
        $isWindowOpen   = false;
        foreach ($windows as $q => $win) {
            if ($now->toDateString() >= $win['start'] && $now->toDateString() <= $win['end']) {
                $displayQuarter = $q;
                $isWindowOpen   = true;
                break;
            }
        }

        $window     = $windows[$displayQuarter];
        $windowStart = \Carbon\Carbon::parse($window['start'])->format('d M Y');
        $windowEnd   = \Carbon\Carbon::parse($window['end'])->format('d M Y');
        $qLabel      = 'Q' . $displayQuarter;

        // ── KPIs for this user ────────────────────────────────────────────────
        $kpis = $supabase->get('kpis', [
            'employee_id'    => 'eq.' . $user['id'],
            'financial_year' => 'eq.' . $this->currentFinancialYear,
            'select'         => 'id,kpi_title,category,sub_category,unit,base_target,actual_value,status,weightage',
        ]) ?? [];

        $quarterScores = [];
        foreach ($kpis as $kpi) {
            $qRows = $supabase->get('kpi_quarters', [
                'kpi_id'  => 'eq.' . $kpi['id'],
                'quarter' => 'eq.' . $qLabel,
                'select'  => 'quarter,quarter_target,quarter_actual,status',
            ]);
            $quarterScores[$kpi['id']] = $qRows[0] ?? null;
        }

        return view('performance.kpi', [
            'user'                 => $user,
            'currentUserName'      => $user['full_name'] ?? $user['short_name'] ?? 'User',
            'userPosition'         => $user['position'] ?? $user['role'] ?? '-',
            'departmentName'       => $department['name'] ?? $user['department_code'] ?? '-',
            'reportsToName'        => $reportsTo
                                        ? ($reportsTo['full_name'] ?? $reportsTo['short_name'] ?? '-')
                                        : '-',
            'currentFinancialYear' => $this->currentFinancialYear,
            'displayQuarter'       => $displayQuarter,
            'qLabel'               => $qLabel,
            'isWindowOpen'         => $isWindowOpen,
            'windowStart'          => $windowStart,
            'windowEnd'            => $windowEnd,
            'kpis'                 => $kpis,
            'quarterScores'        => $quarterScores,
        ]);
    }
}
