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

        // ── Tenure calculation ────────────────────────────────────────────────
        $joinDate = $user['join_date'] ?? null;
        $tenure   = '—';
        if ($joinDate) {
            $diff = \Carbon\Carbon::parse($joinDate)->diff(now());
            $parts = [];
            if ($diff->y > 0) $parts[] = $diff->y . ' year' . ($diff->y !== 1 ? 's' : '');
            if ($diff->m > 0) $parts[] = $diff->m . ' month' . ($diff->m !== 1 ? 's' : '');
            $tenure = $parts ? implode(' ', $parts) : 'Less than 1 month';
        }

        // Reporting-to (approver)
        $reportsTo = null;
        if (!empty($user['reports_to_id'])) {
            $managers  = $supabase->get('employees', [
                'id'     => 'eq.' . $user['reports_to_id'],
                'select' => 'id,short_name,full_name,role,position',
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
            'reportsToName'        => $reportsTo ? ($reportsTo['full_name'] ?? $reportsTo['short_name'] ?? '-') : '-',
            'reportsToPosition'    => $reportsTo['position'] ?? $reportsTo['role'] ?? '-',
            'joinDate'             => $joinDate ? \Carbon\Carbon::parse($joinDate)->format('d M Y') : '—',
            'tenure'               => $tenure,
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

    public function attitude(SupabaseService $supabase)
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

        $reportsTo = null;
        if (!empty($user['reports_to_id'])) {
            $managers  = $supabase->get('employees', [
                'id'     => 'eq.' . $user['reports_to_id'],
                'select' => 'id,short_name,full_name,role,position',
            ]);
            $reportsTo = $managers[0] ?? null;
        }

        $department = null;
        if (!empty($user['department_code'])) {
            $depts      = $supabase->get('departments', [
                'code'   => 'eq.' . $user['department_code'],
                'select' => '*',
            ]);
            $department = $depts[0] ?? null;
        }

        $now   = now();
        $month = (int) $now->format('n');
        $year  = (int) $now->format('Y');

        $quarterOfMonth = match(true) {
            $month <= 3 => 1,
            $month <= 6 => 2,
            $month <= 9 => 3,
            default     => 4,
        };

        $windows = [
            1 => ['start' => "{$year}-03-24", 'end' => "{$year}-04-07"],
            2 => ['start' => "{$year}-06-23", 'end' => "{$year}-07-07"],
            3 => ['start' => "{$year}-09-22", 'end' => "{$year}-10-06"],
            4 => ['start' => "{$year}-12-23", 'end' => ($year + 1) . "-01-06"],
        ];

        $displayQuarter = $quarterOfMonth;
        $isWindowOpen   = false;
        foreach ($windows as $q => $win) {
            if ($now->toDateString() >= $win['start'] && $now->toDateString() <= $win['end']) {
                $displayQuarter = $q;
                $isWindowOpen   = true;
                break;
            }
        }

        $window      = $windows[$displayQuarter];
        $windowStart = \Carbon\Carbon::parse($window['start'])->format('d M Y');
        $windowEnd   = \Carbon\Carbon::parse($window['end'])->format('d M Y');
        $qLabel      = 'Q' . $displayQuarter;

        $assessmentAreas = [
            [
                'no'          => 1,
                'title'       => 'Knowledge of Job Requirements',
                'description' => 'Knowledge of job requirements, methods, techniques and skills involved in doing the job, and in applying these to perform efficiently.',
            ],
            [
                'no'          => 2,
                'title'       => 'Quality of Work Done',
                'description' => 'To what degree did the appraisee fulfil the quality expectations of the job? Was the work completed accurate and reliable? What is the degree of excellence of end results?',
            ],
            [
                'no'          => 3,
                'title'       => 'Planning and Organising Skills',
                'description' => 'To what degree did the appraisee anticipate needs, forecast conditions, set goals and standards, plan and schedule work?',
            ],
            [
                'no'          => 4,
                'title'       => 'Decision Making',
                'description' => 'Was the appraisee able to analyse problems effectively and make sound decisions and commit to those decisions to achieve an acceptable result?',
            ],
            [
                'no'          => 5,
                'title'       => 'Communication Skills',
                'description' => 'Did the appraisee communicate effectively verbal and written, with superiors and peers?',
            ],
            [
                'no'          => 6,
                'title'       => 'Teamwork',
                'description' => 'Was the appraisee able to adopt and adapt in work conditions/situations and work with others toward a common objective?',
            ],
            [
                'no'          => 7,
                'title'       => 'Interpersonal Relationships',
                'description' => 'How well did the appraisee relate to associates, superiors, and external contacts to get the desired cooperation and assistance?',
            ],
            [
                'no'          => 8,
                'title'       => 'Attitude Towards Work',
                'description' => 'Was the appraisee able to work independently without need for direct supervision? How well did the appraisee adapt to new tasks and to changes in the work environment? Did the appraisee show commitment in discharge of his/her duties?',
            ],
            [
                'no'          => 9,
                'title'       => 'Time Management / Tardiness',
                'description' => 'Is the appraisee able to plan, execute and complete assigned tasks within the deadline given? Did the appraisee conform to Company\'s rules and regulations at all times? Was the appraisee punctual in attendance and timekeeping?',
            ],
            [
                'no'          => 10,
                'title'       => 'Appearance',
                'description' => 'Was the appraisee well groomed? Did the appraisee make an excellent impression?',
            ],
            [
                'no'          => 11,
                'title'       => 'Dependability / Accountability',
                'description' => 'Able to carry out work with limited or minimum supervision and able to follow work instructions. Demonstrates high level of commitment to complete tasks assigned and shows initiative in ensuring job is completed efficiently and effectively.',
            ],
            [
                'no'          => 12,
                'title'       => 'Values',
                'description' => 'Does the appraisee understand and demonstrate organisation values all the time?',
            ],
        ];

        return view('performance.attitude', [
            'user'                 => $user,
            'currentUserName'      => $user['full_name'] ?? $user['short_name'] ?? 'User',
            'userPosition'         => $user['position'] ?? $user['role'] ?? '-',
            'departmentName'       => $department['name'] ?? $user['department_code'] ?? '-',
            'reportsToName'        => $reportsTo
                                        ? ($reportsTo['full_name'] ?? $reportsTo['short_name'] ?? '-')
                                        : '-',
            'reportsToPosition'    => $reportsTo['position'] ?? $reportsTo['role'] ?? '-',
            'currentFinancialYear' => $this->currentFinancialYear,
            'displayQuarter'       => $displayQuarter,
            'qLabel'               => $qLabel,
            'isWindowOpen'         => $isWindowOpen,
            'windowStart'          => $windowStart,
            'windowEnd'            => $windowEnd,
            'assessmentAreas'      => $assessmentAreas,
        ]);
    }

    public function report(SupabaseService $supabase)
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

        // ── Tenure ─────────────────────────────────────────────────────────────
        $joinDate = $user['join_date'] ?? null;
        $tenure   = '—';
        if ($joinDate) {
            $diff  = \Carbon\Carbon::parse($joinDate)->diff(now());
            $parts = [];
            if ($diff->y > 0) $parts[] = $diff->y . ' year' . ($diff->y !== 1 ? 's' : '');
            if ($diff->m > 0) $parts[] = $diff->m . ' month' . ($diff->m !== 1 ? 's' : '');
            $tenure = $parts ? implode(' ', $parts) : 'Less than 1 month';
        }

        // ── Reports-to ─────────────────────────────────────────────────────────
        $reportsTo = null;
        if (!empty($user['reports_to_id'])) {
            $managers  = $supabase->get('employees', [
                'id'     => 'eq.' . $user['reports_to_id'],
                'select' => 'id,short_name,full_name,role,position',
            ]);
            $reportsTo = $managers[0] ?? null;
        }

        // ── Department ─────────────────────────────────────────────────────────
        $department = null;
        if (!empty($user['department_code'])) {
            $depts      = $supabase->get('departments', [
                'code'   => 'eq.' . $user['department_code'],
                'select' => '*',
            ]);
            $department = $depts[0] ?? null;
        }

        // ── Quarter logic ──────────────────────────────────────────────────────
        $now   = now();
        $month = (int) $now->format('n');
        $year  = (int) $now->format('Y');

        $quarterOfMonth = match(true) {
            $month <= 3 => 1,
            $month <= 6 => 2,
            $month <= 9 => 3,
            default     => 4,
        };

        $windows = [
            1 => ['start' => "{$year}-03-24", 'end' => "{$year}-04-07"],
            2 => ['start' => "{$year}-06-23", 'end' => "{$year}-07-07"],
            3 => ['start' => "{$year}-09-22", 'end' => "{$year}-10-06"],
            4 => ['start' => "{$year}-12-23", 'end' => ($year + 1) . "-01-06"],
        ];

        $displayQuarter = $quarterOfMonth;
        $isWindowOpen   = false;
        foreach ($windows as $q => $win) {
            if ($now->toDateString() >= $win['start'] && $now->toDateString() <= $win['end']) {
                $displayQuarter = $q;
                $isWindowOpen   = true;
                break;
            }
        }

        $window      = $windows[$displayQuarter];
        $windowStart = \Carbon\Carbon::parse($window['start'])->format('d M Y');
        $windowEnd   = \Carbon\Carbon::parse($window['end'])->format('d M Y');
        $qLabel      = 'Q' . $displayQuarter;

        // ── KPIs ───────────────────────────────────────────────────────────────
        $kpis = $supabase->get('kpis', [
            'employee_id'    => 'eq.' . $user['id'],
            'financial_year' => 'eq.' . $this->currentFinancialYear,
            'select'         => 'id,kpi_title,category,sub_category,unit,base_target,actual_value,status,weightage',
        ]) ?? [];

        $quarterScores = [];
        $allQuarters   = [];
        foreach ($kpis as $kpi) {
            $qRows = $supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $kpi['id'],
                'select' => 'quarter,quarter_title,quarter_target,quarter_actual,status',
            ]);
            foreach ($qRows as $row) {
                $allQuarters[$kpi['id']][$row['quarter']] = $row;
            }
            $quarterScores[$kpi['id']] = $allQuarters[$kpi['id']][$qLabel] ?? null;
        }

        // ── Attendance: aggregate quarter months from attendance_summary ────────
        $quarterMonths = match($displayQuarter) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12],
        };

        $allAttendance = $supabase->get('attendance_summary', [
            'employee_id' => 'eq.' . $user['id'],
            'year'        => 'eq.' . $year,
            'select'      => 'month,working_days,present_days,absent_days,late_count,total_late_minutes,mc_days,al_days,other_leave_days',
        ]) ?? [];

        $qAttendance = array_filter($allAttendance, fn($r) => in_array((int)$r['month'], $quarterMonths));

        $attendanceSummary = [
            'has_data'           => !empty($qAttendance),
            'working_days'       => 0,
            'present_days'       => 0,
            'absent_days'        => 0,
            'late_count'         => 0,
            'total_late_minutes' => 0,
            'mc_days'            => 0,
            'al_days'            => 0,
            'other_leave_days'   => 0,
            'months'             => [],
        ];
        foreach ($qAttendance as $ar) {
            $attendanceSummary['working_days']       += (int)($ar['working_days'] ?? 0);
            $attendanceSummary['present_days']       += (int)($ar['present_days'] ?? 0);
            $attendanceSummary['absent_days']        += (int)($ar['absent_days'] ?? 0);
            $attendanceSummary['late_count']         += (int)($ar['late_count'] ?? 0);
            $attendanceSummary['total_late_minutes'] += (int)($ar['total_late_minutes'] ?? 0);
            $attendanceSummary['mc_days']            += (int)($ar['mc_days'] ?? 0);
            $attendanceSummary['al_days']            += (int)($ar['al_days'] ?? 0);
            $attendanceSummary['other_leave_days']   += (int)($ar['other_leave_days'] ?? 0);
            $attendanceSummary['months'][]           = \Carbon\Carbon::create($year, (int)$ar['month'], 1)->format('M Y');
        }

        // ── YTD totals (all uploaded months this year) for Part A ─────────────
        $attendanceYTD = ['has_data' => !empty($allAttendance), 'mc_days' => 0, 'other_leave_days' => 0, 'late_count' => 0];
        foreach ($allAttendance as $ar) {
            $attendanceYTD['mc_days']          += (int)($ar['mc_days'] ?? 0);
            $attendanceYTD['other_leave_days'] += (int)($ar['other_leave_days'] ?? 0);
            $attendanceYTD['late_count']       += (int)($ar['late_count'] ?? 0);
        }

        // ── Assessment areas (attitude) ────────────────────────────────────────
        $isExecutive = strtolower($user['role'] ?? '') === 'executive';

        $assessmentAreas = $isExecutive ? [
            ['no' =>  1, 'title' => 'Knowledge of Job Requirements',  'description' => 'Knowledge of job requirements, methods, techniques and skills involved in doing the job, and applying these to perform efficiently.'],
            ['no' =>  2, 'title' => 'Quality of Work Done',           'description' => 'Degree to which quality expectations of the job were fulfilled — accuracy, reliability, and excellence of end results.'],
            ['no' =>  3, 'title' => 'Planning & Organising Skills',   'description' => 'Degree to which the appraisee anticipated needs, forecast conditions, set goals and standards, planned and scheduled work.'],
            ['no' =>  4, 'title' => 'Decision Making',                'description' => 'Able to analyse problems effectively, make sound decisions, and commit to those decisions to achieve an acceptable result.'],
            ['no' =>  5, 'title' => 'Communication Skills',           'description' => 'Communicated effectively — verbal and written — with superiors and peers.'],
            ['no' =>  6, 'title' => 'Teamwork',                       'description' => 'Able to adopt and adapt in work conditions/situations and work with others toward a common objective.'],
            ['no' =>  7, 'title' => 'Interpersonal Relationships',    'description' => 'How well the appraisee related to associates, superiors, and external contacts to get the desired cooperation and assistance.'],
            ['no' =>  8, 'title' => 'Attitude Towards Work',          'description' => 'Able to work independently without direct supervision; adapted well to new tasks/changes; showed commitment in discharge of duties.'],
            ['no' =>  9, 'title' => 'Time Management / Tardiness',    'description' => 'Able to plan, execute and complete assigned tasks within deadline; conformed to company rules; punctual in attendance and timekeeping.'],
            ['no' => 10, 'title' => 'Appearance',                     'description' => 'Well-groomed; made an excellent impression.'],
            ['no' => 11, 'title' => 'Dependability / Accountability', 'description' => 'Carries out work with limited/minimum supervision, follows instructions; shows initiative to complete tasks efficiently and effectively.'],
            ['no' => 12, 'title' => 'Values',                         'description' => 'Understands and demonstrates organisation values at all times.'],
        ] : [
            ['no' =>  1, 'title' => 'Knowledge of Job Requirements',  'description' => 'Knowledge of job requirements, methods, techniques and skills involved in doing the job, and in applying these to perform efficiently.'],
            ['no' =>  2, 'title' => 'Quality of Work Done',           'description' => 'To what degree did the appraisee fulfil the quality expectations of the job? Was the work completed accurate and reliable? What is the degree of excellence of end results?'],
            ['no' =>  3, 'title' => 'Planning and Organising Skills', 'description' => 'To what degree did the appraisee anticipate needs, forecast conditions, set goals and standards, plan and schedule work?'],
            ['no' =>  4, 'title' => 'Decision Making',                'description' => 'Was the appraisee able to analyse problems effectively and make sound decisions and commit to those decisions to achieve an acceptable result?'],
            ['no' =>  5, 'title' => 'Communication Skills',           'description' => 'Did the appraisee communicate effectively verbal and written, with superiors and peers?'],
            ['no' =>  6, 'title' => 'Teamwork',                       'description' => 'Was the appraisee able to adopt and adapt in work conditions/situations and work with others toward a common objective?'],
            ['no' =>  7, 'title' => 'Interpersonal Relationships',    'description' => 'How well did the appraisee relate to associates, superiors, and external contacts to get the desired cooperation and assistance?'],
            ['no' =>  8, 'title' => 'Attitude Towards Work',          'description' => 'Was the appraisee able to work independently without need for direct supervision? How well did the appraisee adapt to new tasks and to changes in the work environment? Did the appraisee show commitment in discharge of his/her duties?'],
            ['no' =>  9, 'title' => 'Time Management / Tardiness',    'description' => "Is the appraisee able to plan, execute and complete assigned tasks within the deadline given? Did the appraisee conform to Company's rules and regulations at all times? Was the appraisee punctual in attendance and timekeeping?"],
            ['no' => 10, 'title' => 'Appearance',                     'description' => 'Was the appraisee well groomed? Did the appraisee make an excellent impression?'],
            ['no' => 11, 'title' => 'Dependability / Accountability', 'description' => 'Able to carry out work with limited or minimum supervision and able to follow work instructions. Demonstrates high level of commitment to complete tasks assigned and shows initiative in ensuring job is completed efficiently and effectively.'],
            ['no' => 12, 'title' => 'Values',                         'description' => 'Does the appraisee understand and demonstrate organisation values all the time?'],
        ];

        return view('performance.report', [
            'user'                 => $user,
            'currentUserName'      => $user['full_name'] ?? $user['short_name'] ?? 'User',
            'userPosition'         => $user['position'] ?? $user['role'] ?? '-',
            'departmentName'       => $department['name'] ?? $user['department_code'] ?? '-',
            'reportsToName'        => $reportsTo ? ($reportsTo['full_name'] ?? $reportsTo['short_name'] ?? '-') : '-',
            'reportsToPosition'    => $reportsTo['position'] ?? $reportsTo['role'] ?? '-',
            'joinDate'             => $joinDate ? \Carbon\Carbon::parse($joinDate)->format('d M Y') : '—',
            'tenure'               => $tenure,
            'currentFinancialYear' => $this->currentFinancialYear,
            'displayQuarter'       => $displayQuarter,
            'qLabel'               => $qLabel,
            'isWindowOpen'         => $isWindowOpen,
            'windowStart'          => $windowStart,
            'windowEnd'            => $windowEnd,
            'kpis'                 => $kpis,
            'quarterScores'        => $quarterScores,
            'allQuarters'          => $allQuarters,
            'assessmentAreas'      => $assessmentAreas,
            'attendanceSummary'    => $attendanceSummary,
            'attendanceYTD'        => $attendanceYTD,
        ]);
    }
}
