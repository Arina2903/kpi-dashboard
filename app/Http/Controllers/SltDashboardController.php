<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SltDashboardController extends Controller
{
    private string $currentFinancialYear = 'FY2026';

    private function currentUser(SupabaseService $supabase): array
    {
        $employees = $supabase->get('employees', [
            'id'        => 'eq.' . session('employee_uuid'),
            'is_active' => 'eq.true',
            'select'    => '*',
        ]);

        if (empty($employees)) {
            session()->flush();
            abort(403, 'Employee not found.');
        }

        return $employees[0];
    }

    // Same department gate used for the SLT-Office staff KPI drill-down —
    // this page is restricted the same way.
    private function requireSltAccess(array $user): void
    {
        $dept = strtoupper(trim($user['department_code'] ?? ''));
        if (!in_array($dept, ['SLT OFFICE', 'BTS'])) {
            abort(403, 'This page is only accessible to SLT Office.');
        }
    }

    private function defaultQuarter(): string
    {
        $now  = now()->timezone('Asia/Kuala_Lumpur');
        $year = (int) $now->year;
        $mon  = (int) $now->month;

        $windows = [
            'Q1' => ['start' => "{$year}-03-24", 'end' => "2026-07-17"],
            'Q2' => ['start' => "{$year}-06-23", 'end' => "{$year}-07-17"],
            'Q3' => ['start' => "{$year}-09-22", 'end' => "{$year}-10-06"],
            'Q4' => ['start' => "{$year}-12-23", 'end' => ($year + 1) . "-01-06"],
        ];

        $today = $now->toDateString();
        foreach ($windows as $q => $win) {
            if ($today >= $win['start'] && $today <= $win['end']) {
                return $q;
            }
        }

        return match (true) {
            $mon <= 3 => 'Q1',
            $mon <= 6 => 'Q2',
            $mon <= 9 => 'Q3',
            default   => 'Q4',
        };
    }

    // Same band thresholds/labels as the Performance Distribution chart on
    // performance/report.blade.php — kept in sync so a score means the same
    // thing everywhere in the app.
    private function bandFor(float $score): array
    {
        if ($score >= 90) return ['key' => 'outstanding',        'label' => 'Outstanding',         'color' => '#00B050'];
        if ($score >= 70) return ['key' => 'meets_expectations',  'label' => 'Meets Expectations',  'color' => '#FFD700'];
        if ($score >= 50) return ['key' => 'below_average',       'label' => 'Below Average',       'color' => '#FF8C00'];
        return                   ['key' => 'unsatisfactory',      'label' => 'Unsatisfactory',      'color' => '#ED1C24'];
    }

    // Mirrors the appraiser total computed client-side in report.blade.php's
    // updateS6()/updateGauge(): sum of s6_s2_app (KPI, /70) + s6_s3_app
    // (Attitude, /25) + s6_s4_app (Attendance, /5) + s6_s5_app (Culture, /5,
    // Q4 only) — all already scaled to their weight when saved.
    private function scoreFromFormData(?array $formData, string $quarter): ?float
    {
        if (!$formData) return null;

        $keys = ['s6_s2_app', 's6_s3_app', 's6_s4_app'];
        if ($quarter === 'Q4') $keys[] = 's6_s5_app';

        $sum = 0;
        $found = false;
        foreach ($keys as $key) {
            if (isset($formData[$key]) && is_numeric($formData[$key])) {
                $sum += (float) $formData[$key];
                $found = true;
            }
        }

        return $found ? round($sum, 1) : null;
    }

    public function index(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login')->with('error', 'Sila login terlebih dahulu.');
        }

        $user = $this->currentUser($supabase);
        $this->requireSltAccess($user);

        $companyCode = session('company_code');

        $quarter = strtoupper($request->query('quarter', $this->defaultQuarter()));
        if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
            $quarter = $this->defaultQuarter();
        }

        $deptFilter = $request->query('department', 'ALL');

        $departments = $supabase->get('departments', [
            'company_code' => 'eq.' . $companyCode,
            'select'       => 'code,name',
            'order'        => 'name.asc',
        ]) ?? [];

        $employeeFilters = [
            'company_code' => 'eq.' . $companyCode,
            'is_active'    => 'eq.true',
            'select'       => 'id,employee_id,short_name,full_name,department_code,role,position,reports_to_id',
            'order'        => 'short_name.asc',
        ];
        if ($deptFilter !== 'ALL') {
            $employeeFilters['department_code'] = 'eq.' . $deptFilter;
        }

        $employees = $supabase->get('employees', $employeeFilters) ?? [];

        // Manager-name lookup needs the full company roster, not just the
        // filtered department, so a manager outside the filter still resolves.
        $allEmployees = $deptFilter === 'ALL'
            ? $employees
            : ($supabase->get('employees', [
                'company_code' => 'eq.' . $companyCode,
                'is_active'    => 'eq.true',
                'select'       => 'id,short_name,full_name',
              ]) ?? []);
        $nameById = collect($allEmployees)->keyBy('id');

        $empIds = collect($employees)->pluck('id')->filter()->values()->toArray();

        $reports = [];
        if (!empty($empIds)) {
            $reports = $supabase->get('performance_reports', [
                'employee_id'    => 'in.(' . implode(',', $empIds) . ')',
                'financial_year' => 'eq.' . $this->currentFinancialYear,
                'quarter'        => 'eq.' . $quarter,
                'select'         => 'employee_id,status,form_data,updated_at',
            ]) ?? [];
        }
        $reportByEmp = collect($reports)->keyBy('employee_id');

        $bandKeys = ['unsatisfactory', 'below_average', 'meets_expectations', 'outstanding'];
        $bandCounts = array_fill_keys($bandKeys, 0);
        $bandStaff  = array_fill_keys($bandKeys, []);

        $notSubmitted = [];
        $submittedPending = [];
        $completedCount = 0;
        $submittedOrFurtherCount = 0;
        $scoreSum = 0;
        $scoreCount = 0;

        foreach ($employees as $emp) {
            $report = $reportByEmp->get($emp['id']);
            $status = $report['status'] ?? 'draft';

            $manager = $nameById->get($emp['reports_to_id'] ?? null);
            $row = [
                'employee_id' => $emp['employee_id'] ?? '-',
                'name'        => $emp['short_name'] ?? $emp['full_name'] ?? 'Unknown',
                'department'  => $emp['department_code'] ?? '-',
                'manager'     => $manager ? ($manager['short_name'] ?? $manager['full_name'] ?? '-') : '-',
            ];

            if (in_array($status, ['submitted', 'appraised', 'completed'])) {
                $submittedOrFurtherCount++;
            } else {
                $notSubmitted[] = $row;
                continue;
            }

            if (!in_array($status, ['appraised', 'completed'])) {
                $submittedPending[] = $row;
                continue;
            }

            $score = $this->scoreFromFormData($report['form_data'] ?? null, $quarter);
            if ($score === null) {
                $submittedPending[] = $row;
                continue;
            }

            $completedCount++;
            $scoreSum += $score;
            $scoreCount++;

            $band = $this->bandFor($score);
            $row['score'] = $score;
            $bandCounts[$band['key']]++;
            $bandStaff[$band['key']][] = $row;
        }

        $totalStaff = count($employees);
        $participationRate = $totalStaff > 0 ? round($submittedOrFurtherCount / $totalStaff * 100) : 0;
        $averageScore = $scoreCount > 0 ? round($scoreSum / $scoreCount, 1) : 0;

        return view('slt-dashboard', [
            'user'                 => $user,
            'currentFinancialYear' => $this->currentFinancialYear,
            'quarter'              => $quarter,
            'departments'          => $departments,
            'deptFilter'           => $deptFilter,
            'totalStaff'           => $totalStaff,
            'participationRate'    => $participationRate,
            'completedCount'       => $completedCount,
            'notCompleteCount'     => $totalStaff - $completedCount,
            'averageScore'         => $averageScore,
            'averageBand'          => $this->bandFor($averageScore),
            'bandCounts'           => $bandCounts,
            'bandStaff'            => $bandStaff,
            'notSubmitted'         => $notSubmitted,
            'submittedPending'     => $submittedPending,
        ]);
    }
}
