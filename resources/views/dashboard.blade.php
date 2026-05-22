<!DOCTYPE html>
<html>
<head>
    <title>RCG KPI Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .brand-panel {
            background:
                radial-gradient(circle at top left, rgba(59,130,246,.16), transparent 30%),
                radial-gradient(circle at bottom right, rgba(20,184,166,.13), transparent 34%),
                linear-gradient(135deg, #06142f 0%, #0b1f45 52%, #020617 100%);
        }

             .soft-card { box-shadow: 0 18px 45px rgba(15, 23, 42, .08); }
        .thin-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .thin-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>

<body class="bg-[#f4f7fb] min-h-screen text-slate-900">

    @include('partials.sidebar')

    @php
        $role = strtoupper(trim($user['role'] ?? ''));
        $currentUserId = (string) ($user['id'] ?? $user['employee_id'] ?? '');
        $currentUserName = $user['short_name'] ?? $user['full_name'] ?? $user['name'] ?? 'User';
        $currentDepartment = $user['department_code'] ?? '-';
        $currentFinancialYear = $currentFinancialYear ?? ('FY' . now()->year);

        $companyRoles = ['SLT', 'CCO', 'CCMO', 'ADMIN'];
        $vpRoles = ['VP'];
        $deptRoles = ['MANAGER', 'EXECUTIVE'];

        $canViewCompanyDashboard = in_array($role, $companyRoles);
        $canViewAllDepartmentPerformance = in_array($role, array_merge($companyRoles, $vpRoles));
        $canViewStaffAction = in_array($role, $companyRoles);
        $isDepartmentUser = in_array($role, $deptRoles);

        $kpiCollection = collect($kpis ?? []);

        $scoreStyle = function ($score) {
            $score = (float) $score;
            if ($score <= 25) {
                return [
                    'bar' => 'bg-red-600',
                    'text' => 'text-red-700',
                    'badge' => 'bg-red-50 text-red-700 border-red-100',
                    'label' => 'Critical',
                ];
            }
            if ($score <= 50) {
                return [
                    'bar' => 'bg-gradient-to-r from-red-600 to-orange-500',
                    'text' => 'text-orange-700',
                    'badge' => 'bg-orange-50 text-orange-700 border-orange-100',
                    'label' => 'Risk',
                ];
            }
            if ($score <= 75) {
                return [
                    'bar' => 'bg-gradient-to-r from-orange-500 to-yellow-400',
                    'text' => 'text-amber-700',
                    'badge' => 'bg-amber-50 text-amber-700 border-amber-100',
                    'label' => 'Watch',
                ];
            }
            if ($score <= 100) {
                return [
                    'bar' => 'bg-gradient-to-r from-yellow-400 to-emerald-600',
                    'text' => 'text-emerald-700',
                    'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                    'label' => 'Good',
                ];
            }
            return [
                'bar' => 'bg-emerald-700',
                'text' => 'text-emerald-800',
                'badge' => 'bg-emerald-50 text-emerald-800 border-emerald-100',
                'label' => 'Exceeded',
            ];
        };

        $calculateKpiScore = function ($kpi) {
            $quarters = collect($kpi['quarters'] ?? []);
            $quarterBaseTotal = 0;
            $quarterActualTotal = 0;

            foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarterName) {
                $quarter = $quarters->firstWhere('quarter', $quarterName) ?? [];
                $quarterBaseTotal += max(0, (float) ($quarter['quarter_target'] ?? 0));
                $quarterActualTotal += max(0, (float) ($quarter['quarter_actual'] ?? 0));
            }

            if ($quarterBaseTotal > 0) {
                return round(($quarterActualTotal / $quarterBaseTotal) * 100, 2);
            }

            $base = max(0, (float) ($kpi['base_target'] ?? 0));
            $actual = max(0, (float) ($kpi['actual_value'] ?? 0));

            return $base > 0 ? round(($actual / $base) * 100, 2) : 0;
        };

        $calculateWeightedScore = function ($kpi) use ($calculateKpiScore) {
            $score = (float) $calculateKpiScore($kpi);
            $weightage = max(0, (float) ($kpi['weightage'] ?? 0));
            return round(($score * $weightage) / 100, 2);
        };

        $riskStatuses = ['at_risk', 'risk', 'in_trouble', 'critical'];

        $kpiRows = $kpiCollection->map(function ($kpi) use ($calculateKpiScore, $calculateWeightedScore, $riskStatuses) {
            $score = $calculateKpiScore($kpi);
            $weightage = max(0, (float) ($kpi['weightage'] ?? 0));
            $weightedScore = $calculateWeightedScore($kpi);
            $status = strtolower($kpi['status'] ?? 'not_started');

            return array_merge($kpi, [
                '_score' => $score,
                '_weightage' => $weightage,
                '_weighted_score' => $weightedScore,
                '_is_risk' => in_array($status, $riskStatuses),
                '_employee_key' => (string) ($kpi['employee_id'] ?? 'unassigned'),
                '_employee_name' => $kpi['employee_name'] ?? $kpi['owner_name'] ?? 'Unassigned',
                '_department_code' => $kpi['department_code'] ?? '-',
            ]);
        });

        $individualKpis = $kpiRows->filter(function ($kpi) use ($currentUserId, $currentUserName) {
            $employeeId = (string) ($kpi['employee_id'] ?? '');
            $employeeName = strtolower(trim($kpi['_employee_name'] ?? ''));
            $userName = strtolower(trim($currentUserName));

            return ($currentUserId && $employeeId === $currentUserId)
                || ($userName && $employeeName === $userName);
        });

        $individualPerformance = round($individualKpis->sum('_weighted_score'), 2);
        $individualWeightage = round($individualKpis->sum('_weightage'), 2);
        $individualKpiCount = $individualKpis->count();
        $individualRiskCount = $individualKpis->where('_is_risk', true)->count();

        $staffPerformanceRows = $kpiRows
            ->groupBy('_employee_key')
            ->map(function ($items, $employeeKey) {
                return [
                    'employee_id' => $employeeKey,
                    'name' => $items->first()['_employee_name'] ?? 'Unassigned',
                    'department_code' => $items->first()['_department_code'] ?? '-',
                    'kpi_count' => $items->count(),
                    'weightage_total' => round($items->sum('_weightage'), 2),
                    'performance' => round($items->sum('_weighted_score'), 2),
                    'risk_count' => $items->where('_is_risk', true)->count(),
                ];
            })
            ->values();

        $companyPerformance = $staffPerformanceRows->count() > 0
            ? round($staffPerformanceRows->avg('performance'), 2)
            : 0;

        $departmentPerformanceRows = $staffPerformanceRows
            ->groupBy('department_code')
            ->map(function ($items, $departmentCode) {
                return [
                    'department_code' => $departmentCode ?: '-',
                    'staff_count' => $items->count(),
                    'kpi_count' => $items->sum('kpi_count'),
                    'performance' => round($items->avg('performance'), 2),
                    'risk_count' => $items->sum('risk_count'),
                    'weightage_average' => round($items->avg('weightage_total'), 2),
                ];
            })
            ->values()
            ->sortByDesc('performance');

        $currentDepartmentPerformance = $departmentPerformanceRows->firstWhere('department_code', $currentDepartment) ?? [
            'department_code' => $currentDepartment,
            'staff_count' => 0,
            'kpi_count' => 0,
            'performance' => 0,
            'risk_count' => 0,
            'weightage_average' => 0,
        ];

        $totalKpis = $kpiRows->count();
        $riskCriticalTotal = $kpiRows->where('_is_risk', true)->count();
        $monitoringTotal = $kpiRows->whereIn('status', ['on_track', 'monitoring', 'completed'])->count();

        $topStaffRisk = $staffPerformanceRows
            ->filter(fn($staff) => ($staff['risk_count'] ?? 0) > 0)
            ->sortByDesc('risk_count')
            ->take(5);

        $dashboardTitle = $canViewCompanyDashboard
            ? 'Company KPI Score'
            : ($canViewAllDepartmentPerformance ? 'Department Performance View' : 'Department Performance');

        $dashboardScore = $canViewCompanyDashboard
            ? $companyPerformance
            : ($currentDepartmentPerformance['performance'] ?? 0);

        $dashboardNote = $canViewCompanyDashboard
            ? 'Company performance is based on average staff weighted KPI score.'
            : 'Department performance is based on visible weighted KPI score.';

        $mainScoreStyle = $scoreStyle($dashboardScore);
        $individualScoreStyle = $scoreStyle($individualPerformance);
        $deptScoreStyle = $scoreStyle($currentDepartmentPerformance['performance'] ?? 0);
    @endphp

    <main
        id="mainContent"
        class="ml-[230px] min-h-screen transition-all duration-300"
    >

        <!-- TOP BAR -->
        <div class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800">
                    Good morning, {{ $user['short_name'] ?? 'User' }} 👋
                </h2>

                <p class="text-xs text-slate-500">
                    Here's your KPI summary for {{ $currentFinancialYear ?? 'FY' . now()->year }}
                </p>
            </div>

            <div class="flex items-center gap-5">
                <div class="bg-slate-100 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 flex items-center gap-2">
                    <span>📅</span>
                    <span>{{ $currentFinancialYear ?? 'FY' . now()->year }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-slate-300 overflow-hidden">
                        <img
                            src="https://ui-avatars.com/api/?name={{ urlencode($user['short_name'] ?? 'User') }}&background=0f172a&color=fff"
                            class="w-full h-full object-cover"
                        />
                    </div>

                    <div class="leading-tight">
                        <p class="text-xs font-semibold text-slate-800">
                            {{ $user['short_name'] ?? 'User' }}
                        </p>

                        <p class="text-[10px] text-slate-500">
                            {{ $user['role'] ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="p-6">

            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded-lg text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- SUMMARY CARDS -->
            @php
                $riskCriticalTotal = ($risk ?? $atRisk ?? 0) + ($critical ?? $offTrack ?? $overdue ?? 0);
                $overallScoreValue = min(max((float) ($overallScore ?? 0), 0), 100);

                $overallScoreBar = $overallScoreValue <= 25
                    ? 'bg-red-600'
                    : ($overallScoreValue <= 50
                        ? 'bg-gradient-to-r from-red-600 to-orange-500'
                        : ($overallScoreValue <= 75
                            ? 'bg-gradient-to-r from-orange-500 to-yellow-400'
                            : 'bg-gradient-to-r from-yellow-400 to-emerald-600'));

                $summaryCards = [
                    [
                        'label' => 'Overall KPI Score',
                        'value' => number_format($overallScore ?? 0, 2) . '%',
                        'note' => 'Company KPI progress based on visible KPI.',
                        'accent' => 'from-[#06142f] to-[#0b1f45]',
                        'text' => 'text-[#06142f]',
                        'bar' => $overallScoreBar,
                        'width' => $overallScoreValue,
                    ],
                    [
                        'label' => 'Total KPI',
                        'value' => $totalKpis ?? 0,
                        'note' => 'Total KPI currently visible in this view.',
                        'accent' => 'from-indigo-600 to-blue-500',
                        'text' => 'text-indigo-700',
                        'bar' => 'bg-gradient-to-r from-indigo-600 to-blue-500',
                        'width' => min(($totalKpis ?? 0) * 10, 100),
                    ],
                    [
                        'label' => 'Monitoring',
                        'value' => $monitoring ?? $onTrack ?? 0,
                        'note' => 'KPI moving normally and still being tracked.',
                        'accent' => 'from-blue-600 to-cyan-500',
                        'text' => 'text-blue-700',
                        'bar' => 'bg-gradient-to-r from-blue-600 to-cyan-500',
                        'width' => min(($monitoring ?? $onTrack ?? 0) * 10, 100),
                    ],
                    [
                        'label' => 'Risk / Critical',
                        'value' => $riskCriticalTotal,
                        'note' => 'Needs action now, not decorative concern.',
                        'accent' => 'from-red-600 to-orange-500',
                        'text' => 'text-red-700',
                        'bar' => 'bg-gradient-to-r from-red-600 to-orange-500',
                        'width' => min($riskCriticalTotal * 10, 100),
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                @foreach($summaryCards as $card)
                    <div class="relative overflow-hidden bg-white rounded-3xl border border-slate-200 soft-card p-5">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r {{ $card['accent'] }}"></div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-slate-500 text-xs font-black uppercase tracking-wide">
                                    {{ $card['label'] }}
                                </p>

                                <p class="mt-3 text-3xl font-black {{ $card['text'] }} tracking-tight truncate">
                                    {{ $card['value'] }}
                                </p>
                            </div>

                            <div class="shrink-0 w-11 h-11 rounded-2xl bg-gradient-to-br {{ $card['accent'] }} text-white flex items-center justify-center text-[10px] font-black shadow-lg shadow-slate-300/70">
                                KPI
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 mt-4 min-h-[32px]">
                            {{ $card['note'] }}
                        </p>

                        <div class="mt-4 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-2.5 rounded-full {{ $card['bar'] }}" style="width: {{ $card['width'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- EXECUTIVE KPI COMMAND CENTER -->
            @php
                $totalStaff = count($kpiCountByUser ?? []);
                $totalDept = count($kpiCountByDepartment ?? []);

                $staffWithRisk = collect($kpiCountByUser ?? [])->filter(fn($d) => ($d['at_risk'] ?? 0) > 0)->count();
                $staffNoKpi = collect($kpiCountByUser ?? [])->filter(fn($d) => ($d['total'] ?? 0) == 0)->count();

                $topStaff = collect($kpiCountByUser ?? [])
                    ->sortByDesc(fn($d) => ($d['at_risk'] ?? 0) * 100 + ($d['total'] ?? 0))
                    ->take(5);

                $topDept = collect($kpiCountByDepartment ?? [])
                    ->sortByDesc(fn($d) => ($d['at_risk'] ?? 0) * 100 + ($d['total'] ?? 0))
                    ->take(4);

                $companyFocusTotal = collect($subCategoryByCompany ?? [])->sum();

                $topCompanyFocus = collect($subCategoryByCompany ?? [])
                    ->sortDesc()
                    ->take(5);

                $mainAction = $staffWithRisk > 0
                    ? 'Review Risk'
                    : ($staffNoKpi > 0 ? 'Assign KPI' : 'Monitor');

                $mainActionClass = $staffWithRisk > 0
                    ? 'bg-red-50 text-red-700 border-red-100'
                    : ($staffNoKpi > 0 ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100');
            @endphp

            <div class="bg-white rounded-3xl border border-slate-200 soft-card mb-8 overflow-hidden">

                <!-- TOP STRIP -->
                <div class="brand-panel px-5 py-4 flex items-center justify-between gap-4 text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 text-white flex items-center justify-center text-sm font-black">
                            KPI
                        </div>

                        <div>
                            <h3 class="text-sm font-black text-white">
                                Executive KPI Command Center
                            </h3>
                            <p class="text-[11px] text-slate-300">
                                Clear action flow: review risk, assign missing KPI, monitor progress.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-black px-3 py-1.5 rounded-xl border {{ $mainActionClass }}">
                            {{ $mainAction }}
                        </span>

                        <a href="/kpi" class="text-[11px] font-bold bg-white text-[#06142f] px-3 py-1.5 rounded-xl hover:bg-slate-100">
                            Manage KPI
                        </a>
                    </div>
                </div>

                <!-- ACTION FLOW -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 p-4 border-b border-slate-200 bg-slate-50/70">

                    <div class="group relative overflow-hidden rounded-2xl bg-red-50/50 border border-red-100 p-4 shadow-sm hover:shadow-md transition">
                        <div class="absolute left-0 top-0 h-full w-1.5 bg-red-500"></div>

                        <div class="flex items-start justify-between gap-3 pl-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-red-500">Action 1</p>
                                <h4 class="text-sm font-black text-slate-900 mt-1">Review Risk Owner</h4>
                                <p class="text-xs text-slate-500 mt-1">Check staff with KPI at risk or critical.</p>
                            </div>

                            <div class="w-11 h-11 rounded-2xl bg-white border border-red-100 text-red-700 flex items-center justify-center text-lg font-black shadow-sm">
                                {{ $staffWithRisk }}
                            </div>
                        </div>

                        <a href="#staff-action" class="mt-4 ml-3 inline-flex items-center justify-center gap-2 rounded-xl bg-white border border-red-200 text-red-700 text-xs font-black px-4 py-2 hover:bg-red-100 transition">
                            Review Staff Risk
                            <span class="text-red-500">→</span>
                        </a>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl bg-amber-50/50 border border-amber-100 p-4 shadow-sm hover:shadow-md transition">
                        <div class="absolute left-0 top-0 h-full w-1.5 bg-amber-500"></div>

                        <div class="flex items-start justify-between gap-3 pl-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-amber-500">Action 2</p>
                                <h4 class="text-sm font-black text-slate-900 mt-1">Assign Missing KPI</h4>
                                <p class="text-xs text-slate-500 mt-1">Find staff with no KPI and create proper ownership.</p>
                            </div>

                            <div class="w-11 h-11 rounded-2xl bg-white border border-amber-100 text-amber-700 flex items-center justify-center text-lg font-black shadow-sm">
                                {{ $staffNoKpi }}
                            </div>
                        </div>

                        <a href="/kpi" class="mt-4 ml-3 inline-flex items-center justify-center gap-2 rounded-xl bg-white border border-amber-200 text-amber-700 text-xs font-black px-4 py-2 hover:bg-amber-100 transition">
                            Manage KPI Owner
                            <span class="text-amber-500">→</span>
                        </a>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl bg-emerald-50/50 border border-emerald-100 p-4 shadow-sm hover:shadow-md transition">
                        <div class="absolute left-0 top-0 h-full w-1.5 bg-emerald-500"></div>

                        <div class="flex items-start justify-between gap-3 pl-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-emerald-500">Action 3</p>
                                <h4 class="text-sm font-black text-slate-900 mt-1">Monitor Progress</h4>
                                <p class="text-xs text-slate-500 mt-1">Review quarter progress, timeline and update gaps.</p>
                            </div>

                            <div class="w-11 h-11 rounded-2xl bg-white border border-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-black shadow-sm">
                                {{ $monitoring ?? $onTrack ?? 0 }}
                            </div>
                        </div>

                        <a href="#kpi-quarter-plan" class="mt-4 ml-3 inline-flex items-center justify-center gap-2 rounded-xl bg-white border border-emerald-200 text-emerald-700 text-xs font-black px-4 py-2 hover:bg-emerald-100 transition">
                            Monitor Quarter Plan
                            <span class="text-emerald-500">→</span>
                        </a>
                    </div>

                </div>

                <!-- ACTION SNAPSHOT -->
                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 border-b border-slate-200 bg-white">

                    <div class="p-4 border-r border-slate-100">
                        <p class="text-[10px] text-slate-400 uppercase">Review Staff</p>
                        <p class="text-2xl font-black text-red-700 mt-1">{{ $staffWithRisk }}</p>
                        <p class="text-[10px] text-slate-500">risk owner</p>
                    </div>

                    <div class="p-4 border-r border-slate-100">
                        <p class="text-[10px] text-slate-400 uppercase">Assign KPI</p>
                        <p class="text-2xl font-black text-amber-700 mt-1">{{ $staffNoKpi }}</p>
                        <p class="text-[10px] text-slate-500">no KPI</p>
                    </div>

                    <div class="p-4 border-r border-slate-100">
                        <p class="text-[10px] text-slate-400 uppercase">Staff</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalStaff }}</p>
                        <p class="text-[10px] text-slate-500">visible</p>
                    </div>

                    <div class="p-4 border-r border-slate-100">
                        <p class="text-[10px] text-slate-400 uppercase">Dept</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalDept }}</p>
                        <p class="text-[10px] text-slate-500">visible</p>
                    </div>

                    <div class="p-4 border-r border-slate-100">
                        <p class="text-[10px] text-slate-400 uppercase">KPI Area</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ $companyFocusTotal }}</p>
                        <p class="text-[10px] text-slate-500">focus count</p>
                    </div>

                    <div class="p-4 border-r border-slate-100">
                        <p class="text-[10px] text-slate-400 uppercase">Next</p>
                        <p class="text-sm font-black mt-2 {{ $staffWithRisk > 0 ? 'text-red-700' : ($staffNoKpi > 0 ? 'text-amber-700' : 'text-emerald-700') }}">
                            {{ $staffWithRisk > 0 ? 'Call / Review' : ($staffNoKpi > 0 ? 'Assign Owner' : 'Weekly Check') }}
                        </p>
                        <p class="text-[10px] text-slate-500">action</p>
                    </div>

                    <div class="p-4 border-r border-slate-100">
                        <p class="text-[10px] text-slate-400 uppercase">Risk Signal</p>
                        <p class="text-sm font-black mt-2 {{ $staffWithRisk > 0 ? 'text-red-700' : 'text-emerald-700' }}">
                            {{ $staffWithRisk > 0 ? 'Attention' : 'Clear' }}
                        </p>
                        <p class="text-[10px] text-slate-500">status</p>
                    </div>

                    <div class="p-4">
                        <p class="text-[10px] text-slate-400 uppercase">Flow</p>
                        <p class="text-sm font-black text-slate-900 mt-2">
                            3 Steps
                        </p>
                        <p class="text-[10px] text-slate-500">action</p>
                    </div>
                </div>

                <!-- DETAIL GRID -->
                <div class="grid grid-cols-12">

                    <!-- STAFF ACTION -->
                    <div id="staff-action" class="col-span-12 xl:col-span-5 p-4 border-r border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                Staff Action
                            </h4>

                            <span class="text-[10px] font-bold bg-red-50 text-red-700 px-2 py-1 rounded-lg">
                                Top 5
                            </span>
                        </div>

                        <div class="space-y-1.5 max-h-[190px] overflow-y-auto pr-1">
                            @forelse($topStaff as $staffName => $data)
                                @php
                                    $total = max(1, $data['total'] ?? 0);
                                    $done = $data['completed'] ?? 0;
                                    $risk = $data['at_risk'] ?? 0;
                                    $percent = round(($done / $total) * 100);

                                    $rowClass = $risk > 0
                                        ? 'bg-red-50 border-red-100'
                                        : 'bg-slate-50 border-slate-200';

                                    $riskText = $risk > 0 ? 'Review' : 'OK';
                                @endphp

                                <div class="grid grid-cols-12 items-center gap-2 rounded-xl border {{ $rowClass }} px-3 py-2">
                                    <div class="col-span-5 min-w-0">
                                        <p class="text-[12px] font-black text-slate-900 truncate">
                                            {{ $staffName ?: 'Unassigned' }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 truncate">
                                            {{ $data['department'] ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="col-span-2 text-center">
                                        <p class="text-[10px] text-slate-400">KPI</p>
                                        <p class="text-[12px] font-black text-slate-900">{{ $total }}</p>
                                    </div>

                                    <div class="col-span-2 text-center">
                                        <p class="text-[10px] text-slate-400">Risk</p>
                                        <p class="text-[12px] font-black {{ $risk > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $risk }}</p>
                                    </div>

                                    <div class="col-span-2">
                                        <div class="h-1.5 bg-white rounded-full overflow-hidden">
                                            <div class="h-1.5 rounded-full {{ $risk > 0 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min($percent, 100) }}%"></div>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-1">{{ $percent }}%</p>
                                    </div>

                                    <div class="col-span-1 text-right">
                                        <span class="text-[10px] font-black {{ $risk > 0 ? 'text-red-700' : 'text-emerald-700' }}">
                                            {{ $riskText }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400">No staff data.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- DEPT + FOCUS -->
                    <div class="col-span-12 xl:col-span-7 p-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                            <!-- DEPARTMENT ACTION -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                        Dept Action
                                    </h4>

                                    <span class="text-[10px] font-bold bg-indigo-50 text-indigo-700 px-2 py-1 rounded-lg">
                                        Top 4
                                    </span>
                                </div>

                                <div class="space-y-1.5 max-h-[190px] overflow-y-auto pr-1">
                                    @forelse($topDept as $departmentCode => $data)
                                        @php
                                            $total = $data['total'] ?? 0;
                                            $risk = $data['at_risk'] ?? 0;
                                            $maxDeptKpi = max(1, collect($kpiCountByDepartment ?? [])->pluck('total')->max() ?? 1);
                                            $barWidth = min(100, round(($total / $maxDeptKpi) * 100));
                                        @endphp

                                        <div class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="text-[12px] font-black text-slate-900 truncate">
                                                        {{ $departmentCode ?: 'No Dept' }}
                                                    </p>
                                                    <p class="text-[10px] text-slate-500">
                                                        {{ $data['staff_count'] ?? 0 }} staff
                                                    </p>
                                                </div>

                                                <div class="text-right shrink-0">
                                                    <p class="text-[12px] font-black text-slate-900">{{ $total }} KPI</p>
                                                    <p class="text-[10px] {{ $risk > 0 ? 'text-red-600 font-bold' : 'text-slate-400' }}">
                                                        {{ $risk }} risk
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-2 h-1.5 bg-white rounded-full overflow-hidden">
                                                <div class="h-1.5 rounded-full {{ $risk > 0 ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ $barWidth }}%"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-400">No department data.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- FOCUS ACTION -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                        Focus Area
                                    </h4>

                                    <span class="text-[10px] font-bold bg-slate-100 text-slate-700 px-2 py-1 rounded-lg">
                                        Top 5
                                    </span>
                                </div>

                                <div class="space-y-2 max-h-[190px] overflow-y-auto pr-1">
                                    @forelse($topCompanyFocus as $subCategory => $count)
                                        @php
                                            $width = $companyFocusTotal > 0 ? min(100, round(($count / $companyFocusTotal) * 100)) : 0;
                                        @endphp

                                        <div>
                                            <div class="flex items-center justify-between text-[12px] mb-1">
                                                <span class="font-black text-slate-800 truncate pr-2">
                                                    {{ $subCategory ?: 'No Sub Category' }}
                                                </span>
                                                <span class="font-black text-slate-900">
                                                    {{ $count }}
                                                </span>
                                            </div>

                                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-1.5 bg-slate-900 rounded-full" style="width: {{ $width }}%"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-400">No focus data.</p>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- KPI LIST -->
            <div id="kpi-quarter-plan" class="bg-white rounded-3xl border border-slate-200 soft-card p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-xl font-bold">KPI & Quarter Plan</h3>
                        <p class="text-xs text-slate-500">
                            Showing KPI for {{ $selectedDepartmentCode ?? $user['department_code'] ?? '-' }}
                            ·
                            {{ $currentFinancialYear ?? 'FY' . now()->year }}
                        </p>
                    </div>

                    <a href="/kpi" class="bg-[#06142f] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#0b1f45] shadow-lg shadow-slate-300/60">
                        Manage KPI
                    </a>
                </div>

                <div class="w-full overflow-x-auto rounded-2xl border border-slate-200">

                    <table class="w-full min-w-[1450px] text-left border-collapse text-[12px]">
                        <thead>
                            <tr class="border-b bg-[#06142f] text-white">
                                <th class="p-3 w-[8%]">Category</th>
                                <th class="p-3 w-[8%]">Sub</th>
                                <th class="p-3 w-[36%]">KPI & Quarter Plan</th>
                                <th class="p-3 w-[8%]">Unit</th>
                                <th class="p-3 w-[10%]">Target</th>
                                <th class="p-3 w-[10%]">Actual</th>
                                <th class="p-3 w-[11%]">Progress</th>
                                <th class="p-3 w-[7%]">Status</th>
                                <th class="p-3 w-[2%] text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $categoryStyles = [
                                    'Financial' => [
                                        'bg' => 'bg-emerald-700 text-white',
                                        'sub' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
                                    ],
                                    'Growth & Customer' => [
                                        'bg' => 'bg-indigo-700 text-white',
                                        'sub' => 'bg-indigo-50 text-indigo-800 border border-indigo-200',
                                    ],
                                    'Initiatives' => [
                                        'bg' => 'bg-amber-600 text-white',
                                        'sub' => 'bg-amber-50 text-amber-800 border border-amber-200',
                                    ],
                                    'People' => [
                                        'bg' => 'bg-pink-700 text-white',
                                        'sub' => 'bg-pink-50 text-pink-800 border border-pink-200',
                                    ],
                                    'Default' => [
                                        'bg' => 'bg-slate-700 text-white',
                                        'sub' => 'bg-slate-50 text-slate-800 border border-slate-200',
                                    ],
                                ];
                            @endphp

                            @forelse($kpis ?? [] as $kpi)
                                @php
                                    $quarters = collect($kpi['quarters'] ?? []);

                                    $totalTarget = (float) ($kpi['quarter_total_target'] ?? $kpi['base_target'] ?? 0);
                                    $totalActual = (float) ($kpi['quarter_total_actual'] ?? $kpi['actual_value'] ?? 0);

                                    $overallProgress = $totalTarget > 0
                                        ? round(($totalActual / $totalTarget) * 100, 1)
                                        : 0;

                                    $status = $kpi['status'] ?? 'not_started';

                                    $statusLabel = match($status) {
                                        'not_started' => 'Not Started',
                                        'on_track' => 'On Track',
                                        'monitoring' => 'Monitoring',
                                        'at_risk' => 'At Risk',
                                        'risk' => 'Risk',
                                        'in_trouble' => 'In Trouble',
                                        'critical' => 'Critical',
                                        'completed' => 'Completed',
                                        default => 'Not Started',
                                    };

                                    $statusClass = match($status) {
                                        'not_started' => 'bg-slate-100 text-slate-700',
                                        'on_track' => 'bg-blue-100 text-blue-700',
                                        'monitoring' => 'bg-blue-100 text-blue-700',
                                        'at_risk' => 'bg-amber-100 text-amber-700',
                                        'risk' => 'bg-amber-100 text-amber-700',
                                        'in_trouble' => 'bg-red-100 text-red-700',
                                        'critical' => 'bg-red-100 text-red-700',
                                        'completed' => 'bg-emerald-100 text-emerald-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };

                                    if ($overallProgress <= 25) {
                                        $overallBar = 'bg-red-600';
                                        $overallProgressText = 'Critical';
                                        $overallProgressTextClass = 'text-red-700';
                                    } elseif ($overallProgress <= 50) {
                                        $overallBar = 'bg-gradient-to-r from-red-600 to-orange-500';
                                        $overallProgressText = 'Risk';
                                        $overallProgressTextClass = 'text-orange-700';
                                    } elseif ($overallProgress <= 75) {
                                        $overallBar = 'bg-gradient-to-r from-orange-500 to-yellow-400';
                                        $overallProgressText = 'Watch';
                                        $overallProgressTextClass = 'text-amber-700';
                                    } elseif ($overallProgress <= 100) {
                                        $overallBar = 'bg-gradient-to-r from-yellow-400 to-emerald-600';
                                        $overallProgressText = 'Healthy';
                                        $overallProgressTextClass = 'text-emerald-700';
                                    } else {
                                        $overallBar = 'bg-emerald-700';
                                        $overallProgressText = 'Exceeded';
                                        $overallProgressTextClass = 'text-emerald-800';
                                    }
                                @endphp

                                <!-- PARENT KPI ROW -->
                                <tr
                                    class="bg-white border-t-2 border-slate-200 hover:bg-slate-50 cursor-pointer"
                                    onclick="openKpiDetail('{{ $kpi['id'] }}')"
                                >
                                    <td class="p-3 align-top">
                                        @php
                                            $style = $categoryStyles[$kpi['category']] ?? $categoryStyles['Default'];
                                        @endphp

                                        <span class="px-2 py-1 text-[10px] font-bold rounded-lg {{ $style['bg'] }}">
                                                {{ $kpi['category'] ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="p-3 align-top">
                                        <span class="px-2 py-1 text-[10px] rounded-lg {{ $style['sub'] }}">
                                            {{ $kpi['sub_category'] ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="p-3 align-top">
                                        <div class="flex items-start gap-2">
                                            <div class="w-6 h-6 rounded-lg bg-slate-900 text-white flex items-center justify-center text-[10px] font-black shrink-0">
                                                KPI
                                            </div>

                                            <div class="min-w-0">
                                                <p class="font-black text-slate-900 leading-snug">
                                                    {{ $kpi['kpi_title'] }}
                                                </p>

                                                <p class="text-[10px] text-slate-500 mt-1 line-clamp-1">
                                                    {{ $kpi['kpi_description'] ?? 'No description.' }}
                                                </p>

                                                @php
                                                    $lastUpdatedAt = $kpi['last_activity'] ?? $kpi['updated_at'] ?? $kpi['created_at'] ?? null;
                                                    $lastUpdated = $lastUpdatedAt ? \Carbon\Carbon::parse($lastUpdatedAt)->timezone('Asia/Kuala_Lumpur') : null;
                                                @endphp

                                                <p class="text-[10px] text-slate-400 mt-1">
                                                    Last Active:
                                                    <span class="font-semibold text-slate-600">
                                                        {{ $lastUpdated ? $lastUpdated->format('d M Y, h:i A') : 'No activity yet' }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-3 align-top">
                                        <span class="inline-flex px-2 py-1 rounded-lg bg-slate-100 text-slate-700 text-[10px] font-black uppercase">
                                            {{ $kpi['unit'] ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="p-3 align-top">
                                        <div class="max-w-[130px] truncate text-sm font-black text-slate-900" title="{{ number_format($totalTarget, 2) }}">
                                            {{ number_format($totalTarget, 2) }}
                                        </div>
                                    </td>

                                    <td class="p-3 align-top">
                                        <div class="max-w-[130px] truncate text-sm font-black text-slate-900" title="{{ number_format($totalActual, 2) }}">
                                            {{ number_format($totalActual, 2) }}
                                        </div>
                                    </td>

                                    <td class="p-3 align-top">
                                        <div class="h-2.5 bg-slate-200 rounded-full overflow-hidden">
                                            <div class="h-2.5 rounded-full {{ $overallBar }}" style="width: {{ min($overallProgress, 100) }}%"></div>
                                        </div>

                                        <div class="mt-1 flex items-center justify-between gap-2">
                                            <p class="text-[10px] text-slate-500">
                                                {{ number_format($overallProgress, 1) }}%
                                            </p>
                                            <p class="text-[10px] font-black {{ $overallProgressTextClass }}">
                                                {{ $overallProgressText }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="p-3 align-top">
                                        <span class="inline-flex px-2 py-1 rounded-lg text-[10px] font-bold {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="p-3 align-top text-right">
                                        <button
                                            onclick="event.stopPropagation(); openKpiDetail('{{ $kpi['id'] }}')"
                                            class="text-[10px] font-bold text-blue-600"
                                        >
                                            View
                                        </button>
                                    </td>
                                </tr>

                                <!-- CHILD QUARTER ROWS -->
                                @foreach(['Q1','Q2','Q3','Q4'] as $qLabel)
                                    @php
                                        $quarter = $quarters->firstWhere('quarter', $qLabel);

                                        $qTarget = (float) ($quarter['quarter_target'] ?? 0);
                                        $qActual = (float) ($quarter['quarter_actual'] ?? 0);

                                        $qProgress = $qTarget > 0
                                            ? round(($qActual / $qTarget) * 100, 1)
                                            : 0;

                                        $qStart = $quarter['start_date'] ?? null;
                                        $qEnd = $quarter['end_date'] ?? null;
                                        $qRemark = $quarter['remark'] ?? '-';

                                        $qTitle = $quarter['quarter_title'] ?? ($qLabel . ' Plan');
                                        $qDescription = $quarter['quarter_description'] ?? 'No description added.';

                                        $qStartFormatted = $qStart
                                            ? \Carbon\Carbon::parse($qStart)->format('d M Y')
                                            : 'No start date';

                                        $qEndFormatted = $qEnd
                                            ? \Carbon\Carbon::parse($qEnd)->format('d M Y')
                                            : 'No end date';

                                        $today = \Carbon\Carbon::now('Asia/Kuala_Lumpur')->startOfDay();

                                        $endDate = $qEnd
                                            ? \Carbon\Carbon::parse($qEnd)->startOfDay()
                                            : null;

                                        $daysLeft = null;

                                        if ($endDate) {
                                            $secondsDiff = $endDate->timestamp - $today->timestamp;
                                            $daysLeft = (int) floor($secondsDiff / 86400);
                                        }

                                        if (!$quarter) {
                                            $qCountdown = 'Not set';
                                            $qStatusLabel = 'No Plan';
                                            $qRowClass = 'bg-slate-50';
                                            $qStatusClass = 'bg-slate-100 text-slate-500';
                                        } elseif (!$qEnd) {
                                            $qCountdown = 'No end date';
                                            $qStatusLabel = 'No Date';
                                            $qRowClass = 'bg-slate-50';
                                            $qStatusClass = 'bg-slate-100 text-slate-500';
                                        } elseif ($qProgress >= 100) {
                                            $qCountdown = 'Completed';
                                            $qStatusLabel = 'Achieved';
                                            $qRowClass = 'bg-emerald-50/40';
                                            $qStatusClass = 'bg-emerald-100 text-emerald-700';
                                        } elseif ($daysLeft < 0) {
                                            $qCountdown = abs($daysLeft) . ' days overdue';
                                            $qStatusLabel = 'Overdue';
                                            $qRowClass = 'bg-red-50/50';
                                            $qStatusClass = 'bg-red-100 text-red-700';
                                        } elseif ($daysLeft === 0) {
                                            $qCountdown = 'Due today';
                                            $qStatusLabel = 'Today';
                                            $qRowClass = 'bg-red-50/50';
                                            $qStatusClass = 'bg-red-100 text-red-700';
                                        } elseif ($daysLeft <= 7) {
                                            $qCountdown = $daysLeft . ' days left';
                                            $qStatusLabel = 'Urgent';
                                            $qRowClass = 'bg-red-50/40';
                                            $qStatusClass = 'bg-red-100 text-red-700';
                                        } elseif ($daysLeft <= 30) {
                                            $qCountdown = $daysLeft . ' days left';
                                            $qStatusLabel = 'Watch';
                                            $qRowClass = 'bg-amber-50/40';
                                            $qStatusClass = 'bg-amber-100 text-amber-700';
                                        } else {
                                            $qCountdown = $daysLeft . ' days left';
                                            $qStatusLabel = 'Active';
                                            $qRowClass = 'bg-blue-50/30';
                                            $qStatusClass = 'bg-blue-100 text-blue-700';
                                        }

                                        if ($qProgress <= 25) {
                                            $qBar = 'bg-red-600';
                                            $qProgressText = 'Critical';
                                            $qProgressTextClass = 'text-red-700';
                                        } elseif ($qProgress <= 50) {
                                            $qBar = 'bg-gradient-to-r from-red-600 to-orange-500';
                                            $qProgressText = 'Risk';
                                            $qProgressTextClass = 'text-orange-700';
                                        } elseif ($qProgress <= 75) {
                                            $qBar = 'bg-gradient-to-r from-orange-500 to-yellow-400';
                                            $qProgressText = 'Watch';
                                            $qProgressTextClass = 'text-amber-700';
                                        } elseif ($qProgress <= 100) {
                                            $qBar = 'bg-gradient-to-r from-yellow-400 to-emerald-600';
                                            $qProgressText = 'Healthy';
                                            $qProgressTextClass = 'text-emerald-700';
                                        } else {
                                            $qBar = 'bg-emerald-700';
                                            $qProgressText = 'Exceeded';
                                            $qProgressTextClass = 'text-emerald-800';
                                        }
                                    @endphp

                                    <tr class="border-b border-slate-100 {{ $qRowClass }}">
                                        <td class="p-3"></td>
                                        <td class="p-3"></td>

                                        <td class="p-3">
                                            <div class="ml-8 border-l-2 border-slate-300 pl-3">
                                                <div class="grid grid-cols-12 gap-3 items-center">

                                                    <!-- LEFT: DATE + COUNTDOWN -->
                                                    <div class="col-span-5">
                                                        <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                                                            <p class="text-[9px] uppercase text-slate-400 font-bold">
                                                                Timeline
                                                            </p>

                                                            <p class="text-[11px] font-black text-slate-800 mt-1">
                                                                {{ $qStartFormatted }}
                                                            </p>

                                                            <p class="text-[10px] text-slate-500">
                                                                to {{ $qEndFormatted }}
                                                            </p>

                                                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-[10px] font-black {{ $qStatusClass }}">
                                                                {{ $qCountdown }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- RIGHT: QUARTER PLAN -->
                                                    <div class="col-span-7">
                                                        <div class="flex items-start gap-2">
                                                            <span class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-[10px] font-black text-slate-700 shrink-0">
                                                                {{ $qLabel }}
                                                            </span>

                                                            <div class="min-w-0">
                                                                <p class="text-xs font-black text-slate-800 line-clamp-1">
                                                                    {{ $qTitle }}
                                                                </p>

                                                                <p class="text-[10px] text-slate-500 mt-1 line-clamp-2">
                                                                    {{ $qDescription }}
                                                                </p>

                                                                @php
                                                                    $qLastUpdatedAt = $quarter['updated_at']
                                                                        ?? $quarter['created_at']
                                                                        ?? null;

                                                                    $qLastUpdated = $qLastUpdatedAt
                                                                        ? \Carbon\Carbon::parse($qLastUpdatedAt)->timezone('Asia/Kuala_Lumpur')
                                                                        : null;
                                                                @endphp

                                                                <p class="text-[10px] text-slate-400 mt-1">
                                                                    Last update:
                                                                    <span class="font-semibold text-slate-600">
                                                                        {{ $qLastUpdated ? $qLastUpdated->format('d M Y, h:i A') : 'No activity' }}
                                                                    </span>
                                                                </p>

                                                                @if($qRemark !== '-')
                                                                    <p class="text-[10px] text-amber-600 mt-1 line-clamp-1">
                                                                        Remark: {{ $qRemark }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="p-3">
                                            <span class="inline-flex px-2 py-1 rounded-lg bg-white border border-slate-200 text-[10px] font-black text-slate-700 uppercase">
                                                {{ $kpi['unit'] ?? '-' }}
                                            </span>
                                        </td>

                                        <td class="p-3">
                                            <div class="max-w-[130px] truncate text-xs font-bold text-slate-700" title="{{ number_format($qTarget, 2) }}">
                                                {{ number_format($qTarget, 2) }}
                                            </div>
                                        </td>

                                        <td class="p-3">
                                            <div class="max-w-[130px] truncate text-xs font-bold text-slate-700" title="{{ number_format($qActual, 2) }}">
                                                {{ number_format($qActual, 2) }}
                                            </div>
                                        </td>

                                        <td class="p-3">
                                            <div class="h-2.5 bg-white rounded-full overflow-hidden">
                                                <div class="h-2.5 rounded-full {{ $qBar }}" style="width: {{ min($qProgress, 100) }}%"></div>
                                            </div>

                                            <div class="mt-1 flex items-center justify-between gap-2">
                                                <p class="text-[10px] text-slate-500">
                                                    {{ number_format($qProgress, 1) }}%
                                                </p>
                                                <p class="text-[10px] font-black {{ $qProgressTextClass }}">
                                                    {{ $qProgressText }}
                                                </p>
                                            </div>
                                        </td>

                                        <td class="p-3">
                                            <span class="inline-flex px-2 py-1 rounded-lg text-[10px] font-bold {{ $qStatusClass }}">
                                                {{ $qStatusLabel }}
                                            </span>
                                        </td>

                                        <td class="p-3 text-right">
                                            <div class="flex flex-col items-end gap-1">
                                                <button
                                                    onclick="event.stopPropagation(); openQuarterModal('{{ $kpi['id'] }}-{{ $qLabel }}')"
                                                    class="text-[10px] font-bold text-blue-600 hover:text-blue-800"
                                                >
                                                    Edit
                                                </button>

                                                <button
                                                    onclick="event.stopPropagation(); openKpiDetail('{{ $kpi['id'] }}')"
                                                    class="text-[10px] font-bold text-slate-500 hover:text-slate-800"
                                                >
                                                    View
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div
                                        id="quarter-modal-{{ $kpi['id'] }}-{{ $qLabel }}"
                                        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4"
                                        onclick="closeQuarterModal('{{ $kpi['id'] }}-{{ $qLabel }}')"
                                    >
                                        <div
                                            class="w-full max-w-xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
                                            onclick="event.stopPropagation()"
                                        >
                                            <div class="px-4 py-3 brand-panel text-white flex items-start justify-between">
                                                <div>
                                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">
                                                        Edit {{ $qLabel }} Quarter Plan
                                                    </p>
                                                    <h3 class="text-sm font-black mt-1">
                                                        {{ $kpi['kpi_title'] }}
                                                    </h3>
                                                </div>

                                                <button
                                                    type="button"
                                                    onclick="closeQuarterModal('{{ $kpi['id'] }}-{{ $qLabel }}')"
                                                    class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-xs"
                                                >
                                                    ✕
                                                </button>
                                            </div>

                                            <form
                                                method="POST"
                                                action="{{ route('kpi.quarter.save') }}"
                                                class="p-4 space-y-4"
                                            >
                                                @csrf

                                                <input type="hidden" name="kpi_id" value="{{ $kpi['id'] }}">
                                                <input type="hidden" name="quarter" value="{{ $qLabel }}">
                                                <input type="hidden" name="quarter_id" value="{{ $quarter['id'] ?? '' }}">

                                                <div>
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">
                                                        Quarter KPI Title
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="quarter_title"
                                                        value="{{ $qTitle }}"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                        placeholder="Example: Complete sales pipeline cleanup"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">
                                                        Quarter Description
                                                    </label>
                                                    <textarea
                                                        name="quarter_description"
                                                        rows="3"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                        placeholder="Explain what this quarter needs to achieve."
                                                    >{{ $qDescription !== 'No description added.' ? $qDescription : '' }}</textarea>
                                                </div>

                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">Target</label>
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            name="quarter_target"
                                                            value="{{ $qTarget }}"
                                                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">Actual</label>
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            name="quarter_actual"
                                                            value="{{ $qActual }}"
                                                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                        >
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">Start Date</label>
                                                        <input
                                                            type="date"
                                                            name="start_date"
                                                            value="{{ $qStart }}"
                                                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">End Date</label>
                                                        <input
                                                            type="date"
                                                            name="end_date"
                                                            value="{{ $qEnd }}"
                                                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                        >
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">Status</label>
                                                    <select
                                                        name="status"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                    >
                                                        @foreach(['not_started', 'on_track', 'at_risk', 'in_trouble', 'completed'] as $statusOption)
                                                            <option value="{{ $statusOption }}" @selected(($quarter['status'] ?? 'not_started') === $statusOption)>
                                                                {{ ucwords(str_replace('_', ' ', $statusOption)) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">Remark</label>
                                                    <textarea
                                                        name="remark"
                                                        rows="2"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                    >{{ $qRemark !== '-' ? $qRemark : '' }}</textarea>
                                                </div>

                                                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                                                    <button
                                                        type="button"
                                                        onclick="closeQuarterModal('{{ $kpi['id'] }}-{{ $qLabel }}')"
                                                        class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-bold"
                                                    >
                                                        Cancel
                                                    </button>

                                                    <button
                                                        type="submit"
                                                        class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold"
                                                    >
                                                        Save Quarter
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="9" class="p-6 text-center text-gray-500">
                                        No KPI found.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                    </table>

                    @foreach($kpis ?? [] as $kpi)
                        @php
                            $modalStatus = $kpi['status'] ?? 'not_started';

                            $modalStatusLabel = match($modalStatus) {
                                'not_started' => 'Not Started',
                                'on_track' => 'On Track',
                                'monitoring' => 'Monitoring',
                                'at_risk' => 'At Risk',
                                'risk' => 'Risk',
                                'in_trouble' => 'In Trouble',
                                'critical' => 'Critical',
                                'completed' => 'Completed',
                                default => 'Not Started',
                            };

                            $modalStatusClass = match($modalStatus) {
                                'not_started' => 'bg-slate-100 text-slate-700',
                                'on_track' => 'bg-blue-100 text-blue-700',
                                'monitoring' => 'bg-blue-100 text-blue-700',
                                'at_risk' => 'bg-amber-100 text-amber-700',
                                'risk' => 'bg-amber-100 text-amber-700',
                                'in_trouble' => 'bg-red-100 text-red-700',
                                'critical' => 'bg-red-100 text-red-700',
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                default => 'bg-slate-100 text-slate-700',
                            };

                            $modalCategoryStyles = [
                                'Financial' => [
                                    'category' => 'bg-emerald-700 text-white',
                                    'subs' => ['bg-emerald-50 text-emerald-800 border border-emerald-200'],
                                ],
                                'Growth & Customer' => [
                                    'category' => 'bg-indigo-700 text-white',
                                    'subs' => ['bg-indigo-50 text-indigo-800 border border-indigo-200'],
                                ],
                                'Initiatives' => [
                                    'category' => 'bg-amber-600 text-white',
                                    'subs' => ['bg-amber-50 text-amber-800 border border-amber-200'],
                                ],
                                'People' => [
                                    'category' => 'bg-pink-700 text-white',
                                    'subs' => ['bg-pink-50 text-pink-800 border border-pink-200'],
                                ],
                                'Default' => [
                                    'category' => 'bg-slate-700 text-white',
                                    'subs' => ['bg-slate-50 text-slate-800 border border-slate-200'],
                                ],
                            ];

                            $modalCategory = $kpi['category'] ?? 'Default';
                            $modalStyleSet = $modalCategoryStyles[$modalCategory] ?? $modalCategoryStyles['Default'];

                            $modalCategoryClass = $modalStyleSet['category'];
                            $modalSubCategoryClass = $modalStyleSet['subs'][0];
                            $modalBaseTarget = (float) ($kpi['base_target'] ?? 0);
                            $modalActualValue = (float) ($kpi['actual_value'] ?? 0);

                            $modalAchievement = $modalBaseTarget > 0
                                ? round(($modalActualValue / $modalBaseTarget) * 100, 2)
                                : 0;

                            $modalAchievement = max(0, $modalAchievement);

                            if ($modalAchievement <= 25) {
                                $modalProgressColor = 'bg-red-600';
                            } elseif ($modalAchievement <= 50) {
                                $modalProgressColor = 'bg-gradient-to-r from-red-600 to-orange-500';
                            } elseif ($modalAchievement <= 75) {
                                $modalProgressColor = 'bg-gradient-to-r from-orange-500 to-yellow-400';
                            } elseif ($modalAchievement <= 100) {
                                $modalProgressColor = 'bg-gradient-to-r from-yellow-400 to-green-500';
                            } else {
                                $modalProgressColor = 'bg-emerald-700';
                            }
                        @endphp

                        <div
                            id="kpi-modal-{{ $kpi['id'] }}"
                            class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4"
                            onclick="closeKpiDetail('{{ $kpi['id'] }}')"
                        >
                            <div
                                class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
                                onclick="event.stopPropagation()"
                            >
                                <!-- HEADER -->
                                <div class="px-4 py-3 brand-panel text-white flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">
                                            KPI Detail
                                        </p>
                                        <h3 class="text-sm font-black mt-1 leading-snug line-clamp-2">
                                            {{ $kpi['kpi_title'] ?? '-' }}
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        onclick="closeKpiDetail('{{ $kpi['id'] }}')"
                                        class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-xs shrink-0"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- BODY -->
                                <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">

                                    <!-- QUICK SNAPSHOT -->
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-2">
                                            <p class="text-[9px] text-slate-400 uppercase">Base</p>
                                            <p class="text-xs font-black text-slate-900 truncate">
                                                {{ number_format((float) ($kpi['base_target'] ?? 0), 2) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-2">
                                            <p class="text-[9px] text-slate-400 uppercase">Stretch</p>
                                            <p class="text-xs font-black text-slate-900 truncate">
                                                {{ number_format((float) ($kpi['stretch_target'] ?? 0), 2) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-blue-50 border border-blue-100 p-2">
                                            <p class="text-[9px] text-blue-400 uppercase">Actual</p>
                                            <p class="text-xs font-black text-blue-800 truncate">
                                                {{ number_format((float) ($kpi['actual_value'] ?? 0), 2) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- PROGRESS -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <div>
                                                <p class="text-[10px] text-slate-400 uppercase">Progress</p>
                                                <p class="text-sm font-black text-slate-900">
                                                    {{ number_format($modalAchievement, 2) }}%
                                                </p>
                                            </div>

                                            <span class="text-[10px] font-bold px-2 py-1 rounded-lg {{ $modalStatusClass }}">
                                                {{ $modalStatusLabel }}
                                            </span>
                                        </div>

                                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div
                                                class="{{ $modalProgressColor }} h-2 rounded-full"
                                                style="width: {{ min($modalAchievement, 100) }}%"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- CATEGORY -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="rounded-xl border border-slate-200 p-3">
                                            <p class="text-[10px] text-slate-400 uppercase mb-1">Category</p>
                                            <span class="inline-block px-2 py-1 rounded-lg text-[10px] font-bold {{ $modalCategoryClass }}">
                                                {{ $kpi['category'] ?? '-' }}
                                            </span>
                                        </div>

                                        <div class="rounded-xl border border-slate-200 p-3">
                                            <p class="text-[10px] text-slate-400 uppercase mb-1">Sub Category</p>
                                            <span class="inline-block px-2 py-1 rounded-lg text-[10px] font-semibold {{ $modalSubCategoryClass }}">
                                                {{ $kpi['sub_category'] ?? '-' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- DESCRIPTION -->
                                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                        <p class="text-[10px] text-slate-400 uppercase mb-1">Description</p>
                                        <p class="text-xs text-slate-700 leading-relaxed">
                                            {{ $kpi['kpi_description'] ?? 'No description.' }}
                                        </p>
                                    </div>

                                    <!-- REMARK -->
                                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
                                        <p class="text-[10px] text-amber-500 uppercase mb-1">Remark</p>
                                        <p class="text-xs text-amber-800 leading-relaxed">
                                            {{ $kpi['remark'] ?? 'No remark.' }}
                                        </p>
                                    </div>

                                    <!-- META -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="rounded-xl bg-white border border-slate-100 p-3">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wide">Unit</p>

                                            @php
                                                $unit = strtolower(trim($kpi['unit'] ?? ''));

                                                $unitDisplay = match($unit) {
                                                    'currency' => 'RM',
                                                    'percentage' => '%',
                                                    'number' => '—',
                                                    default => '—',
                                                };
                                            @endphp

                                            <p class="font-bold text-slate-800 mt-1">
                                                {{ $unitDisplay }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                            <p class="text-[10px] text-slate-400 uppercase">Last Check-In</p>
                                            <p class="font-bold text-slate-800 mt-1">
                                                @php
                                                    $lastActiveAt = $kpi['last_activity'] ?? null;
                                                    $lastActive = $lastActiveAt ? \Carbon\Carbon::parse($lastActiveAt) : null;
                                                @endphp

                                                {{ $lastActive ? $lastActive->format('d M Y, h:i A') : '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- FOOTER -->
                                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                                    <button
                                        type="button"
                                        onclick="closeKpiDetail('{{ $kpi['id'] }}')"
                                        class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100"
                                    >
                                        Close
                                    </button>

                                    <a
                                        href="{{ route('kpi.edit', $kpi['id']) }}"
                                        class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold hover:bg-slate-800"
                                    >
                                        Edit KPI
                                    </a>
                                </div>
                            </div>
                        </div>

                    @endforeach
                </div>
            </div>
        </div>
    </main>

<script>
    let sidebarOpen = true;

    function enableEdit(id) {
        const row = document.getElementById('row-' + id);
        if (!row) return;

        row.querySelectorAll('.view-mode').forEach(el => {
            el.classList.add('hidden');
        });

        row.querySelectorAll('.edit-mode').forEach(el => {
            el.classList.remove('hidden');
        });

        const historyRow = document.getElementById('history-' + id);
        if (historyRow) {
            historyRow.classList.add('hidden');
        }
    }

    function cancelEdit(id) {
        window.location.reload();
    }

    function openKpiDetail(id) {
        const modal = document.getElementById('kpi-modal-' + id);
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeKpiDetail(id) {
        const modal = document.getElementById('kpi-modal-' + id);
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function openHistoryModal(id) {
        const modal = document.getElementById('history-modal-' + id);
        const row = document.getElementById('row-' + id);

        const isEditing = row && row.querySelector('.edit-mode:not(.hidden)');
        if (isEditing || !modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function openTargetModal(kpiId, fieldName, currentValue) {
        const modal = document.getElementById('target-modal-' + kpiId);
        const fieldInput = document.getElementById('target-field-' + kpiId);
        const currentInput = document.getElementById('target-current-' + kpiId);

        if (!modal || !fieldInput || !currentInput) return;

        fieldInput.value = fieldName;
        currentInput.value = currentValue;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeTargetModal(kpiId) {
        const modal = document.getElementById('target-modal-' + kpiId);
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function closeHistoryModal(id) {
        const modal = document.getElementById('history-modal-' + id);
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function openSubCategoryModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeSubCategoryModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openQuarterModal(id) {
        const modal = document.getElementById('quarter-modal-' + id);
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeQuarterModal(id) {
        const modal = document.getElementById('quarter-modal-' + id);
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

</script>

</body>
</html>
