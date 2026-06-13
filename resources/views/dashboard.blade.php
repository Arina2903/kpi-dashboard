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
        // ── CORE ─────────────────────────────────────────────────────────────
        $role              = strtoupper(trim($user['role'] ?? ''));
        $currentUserId     = (string) ($user['id'] ?? $user['employee_id'] ?? '');
        $currentUserName   = $user['short_name'] ?? $user['full_name'] ?? $user['name'] ?? 'User';
        $currentDepartment = $user['department_code'] ?? '-';
        $currentFinancialYear = $currentFinancialYear ?? ('FY' . now()->year);
        $userPosition      = $user['position'] ?? $user['role'] ?? '-';

        $canViewCompanyDashboard = $role === 'SLT';
        $isManager               = in_array($role, ['SLT', 'VP', 'MANAGER']);
        $isDepartmentUser        = in_array($role, ['VP', 'MANAGER', 'EXECUTIVE']);

        $kpiCollection = collect($kpis ?? []);

        // ── SCORE STYLE ───────────────────────────────────────────────────────
        $scoreStyle = function ($score) {
            $score = (float) $score;
            if ($score <= 25)  return ['bar' => 'bg-red-600',                                     'text' => 'text-red-700',     'badge' => 'bg-red-50 text-red-700 border-red-100',       'label' => 'Critical'];
            if ($score <= 50)  return ['bar' => 'bg-gradient-to-r from-red-600 to-orange-500',    'text' => 'text-orange-700',  'badge' => 'bg-orange-50 text-orange-700 border-orange-100','label' => 'Risk'];
            if ($score <= 75)  return ['bar' => 'bg-gradient-to-r from-orange-500 to-yellow-400', 'text' => 'text-amber-700',   'badge' => 'bg-amber-50 text-amber-700 border-amber-100',  'label' => 'Watch'];
            if ($score <= 100) return ['bar' => 'bg-gradient-to-r from-yellow-400 to-emerald-600','text' => 'text-emerald-700', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-100','label' => 'Good'];
            return                     ['bar' => 'bg-emerald-700',                                'text' => 'text-emerald-800', 'badge' => 'bg-emerald-50 text-emerald-800 border-emerald-100','label' => 'Exceeded'];
        };

        // ── KPI SCORE CALCULATION ─────────────────────────────────────────────
        $calculateKpiScore = function ($kpi) {
            $quarters = collect($kpi['quarters'] ?? []);
            $qBase = 0; $qActual = 0;
            foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q) {
                $row = $quarters->firstWhere('quarter', $q) ?? [];
                $qBase   += max(0, (float) ($row['quarter_target'] ?? 0));
                $qActual += max(0, (float) ($row['quarter_actual'] ?? 0));
            }
            if ($qBase > 0) return round(($qActual / $qBase) * 100, 2);
            $base   = max(0, (float) ($kpi['base_target'] ?? 0));
            $actual = max(0, (float) ($kpi['actual_value'] ?? 0));
            return $base > 0 ? round(($actual / $base) * 100, 2) : 0;
        };

        $calculateWeightedScore = function ($kpi) use ($calculateKpiScore) {
            return round(((float) $calculateKpiScore($kpi) * max(0, (float) ($kpi['weightage'] ?? 0))) / 100, 2);
        };

        // ── KPI ROWS ──────────────────────────────────────────────────────────
        $riskStatuses = ['at_risk', 'risk', 'in_trouble', 'critical'];

        $kpiRows = $kpiCollection->map(function ($kpi) use ($calculateKpiScore, $calculateWeightedScore, $riskStatuses) {
            $score     = $calculateKpiScore($kpi);
            $weightage = max(0, (float) ($kpi['weightage'] ?? 0));
            $status    = strtolower($kpi['status'] ?? 'not_started');
            return array_merge($kpi, [
                '_score'           => $score,
                '_weightage'       => $weightage,
                '_weighted_score'  => $calculateWeightedScore($kpi),
                '_is_risk'         => in_array($status, $riskStatuses),
                '_employee_key'    => (string) ($kpi['employee_id'] ?? 'unassigned'),
                '_employee_name'   => $kpi['owner_display_name'] ?? $kpi['employee_name'] ?? $kpi['owner_name'] ?? 'Unassigned',
                '_department_code' => $kpi['department_code'] ?? '-',
            ]);
        });

        // ── MY KPIs ───────────────────────────────────────────────────────────
        $individualKpis = $kpiRows->filter(function ($kpi) use ($currentUserId, $currentUserName) {
            $employeeId   = (string) ($kpi['employee_id'] ?? '');
            $employeeName = strtolower(trim($kpi['_employee_name'] ?? ''));
            $userName     = strtolower(trim($currentUserName));
            return ($currentUserId && $employeeId === $currentUserId)
                || ($userName && $employeeName === $userName);
        });

        $individualPerformance = round($individualKpis->sum('_weighted_score'), 2);
        $individualWeightage   = round($individualKpis->sum('_weightage'), 2);
        $individualKpiCount    = $individualKpis->count();

        $myOnTrack    = $individualKpis->whereIn('status', ['on_track', 'monitoring'])->count();
        $myAtRisk     = $individualKpis->whereIn('status', ['at_risk', 'risk', 'in_trouble', 'critical'])->count();
        $myCompleted  = $individualKpis->where('status', 'completed')->count();
        $myNotStarted = $individualKpis->where('status', 'not_started')->count();

        $individualScoreStyle = $scoreStyle($individualPerformance);

        // ── CATEGORY GROUPS ───────────────────────────────────────────────────
        $categoryOrder = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];

        $categoryStyles = [
            'Financial'         => ['bg' => 'bg-emerald-700 text-white', 'sub' => 'bg-emerald-50 text-emerald-800 border border-emerald-200', 'left' => 'border-l-emerald-400'],
            'Growth & Customer' => ['bg' => 'bg-indigo-700 text-white',  'sub' => 'bg-indigo-50 text-indigo-800 border border-indigo-200',   'left' => 'border-l-indigo-400'],
            'Initiatives'       => ['bg' => 'bg-amber-600 text-white',   'sub' => 'bg-amber-50 text-amber-800 border border-amber-200',      'left' => 'border-l-amber-400'],
            'People'            => ['bg' => 'bg-pink-700 text-white',    'sub' => 'bg-pink-50 text-pink-800 border border-pink-200',         'left' => 'border-l-pink-400'],
            'Default'           => ['bg' => 'bg-slate-700 text-white',   'sub' => 'bg-slate-50 text-slate-800 border border-slate-200',      'left' => 'border-l-slate-300'],
        ];

        $myKpisByCategory      = $individualKpis->groupBy('category');
        $orderedCategoryGroups = collect();
        foreach ($categoryOrder as $cat) {
            if ($myKpisByCategory->has($cat)) $orderedCategoryGroups[$cat] = $myKpisByCategory->get($cat);
        }
        foreach ($myKpisByCategory as $cat => $items) {
            if (!in_array($cat, $categoryOrder)) $orderedCategoryGroups[$cat] = $items;
        }

        // ── STAFF & DEPARTMENT PERFORMANCE ────────────────────────────────────
        $staffPerformanceRows = $kpiRows
            ->groupBy('_employee_key')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'employee_id'     => $first['employee_id'] ?? '',
                    'name'            => $first['_employee_name'] ?? 'Unknown',
                    'department_code' => $first['_department_code'] ?? '-',
                    'role'            => $first['owner_role'] ?? '-',
                    'kpi_count'       => $items->count(),
                    'weightage_total' => round($items->sum('_weightage'), 2),
                    'performance'     => round($items->sum('_weighted_score'), 2),
                    'risk_count'      => $items->where('_is_risk', true)->count(),
                    'completed_count' => $items->where('status', 'completed')->count(),
                    'on_track_count'  => $items->whereIn('status', ['on_track', 'monitoring'])->count(),
                ];
            })
            ->values()
            ->sortByDesc('performance');

        $companyPerformance = $staffPerformanceRows->count() > 0
            ? round($staffPerformanceRows->avg('performance'), 2) : 0;

        $departmentPerformanceRows = $staffPerformanceRows
            ->groupBy('department_code')
            ->map(function ($items, $deptCode) {
                return [
                    'department_code' => $deptCode ?: '-',
                    'staff_count'     => $items->count(),
                    'kpi_count'       => $items->sum('kpi_count'),
                    'performance'     => round($items->avg('performance'), 2),
                    'risk_count'      => $items->sum('risk_count'),
                ];
            })
            ->values()
            ->sortByDesc('performance');

        // ── QUARTER STATUS HELPERS ────────────────────────────────────────────
        $qDotColor = function ($status) {
            return match(strtolower($status ?? '')) {
                'on_track', 'monitoring' => 'bg-emerald-500',
                'at_risk', 'risk'        => 'bg-amber-400',
                'in_trouble', 'critical' => 'bg-red-500',
                'completed'              => 'bg-blue-500',
                default                  => 'bg-slate-300',
            };
        };

        $statusBadge = function ($status) {
            return match(strtolower($status ?? '')) {
                'on_track', 'monitoring' => ['class' => 'bg-emerald-100 text-emerald-700', 'label' => 'On Track'],
                'at_risk', 'risk'        => ['class' => 'bg-amber-100 text-amber-700',     'label' => 'At Risk'],
                'in_trouble', 'critical' => ['class' => 'bg-red-100 text-red-700',         'label' => 'In Trouble'],
                'completed'              => ['class' => 'bg-blue-100 text-blue-700',        'label' => 'Completed'],
                default                  => ['class' => 'bg-slate-100 text-slate-600',      'label' => 'Not Started'],
            };
        };

        $cardBorderColor = function ($status) {
            return match(strtolower($status ?? '')) {
                'on_track', 'monitoring' => 'border-l-emerald-400',
                'at_risk', 'risk'        => 'border-l-amber-400',
                'in_trouble', 'critical' => 'border-l-red-400',
                'completed'              => 'border-l-blue-400',
                default                  => 'border-l-slate-200',
            };
        };
    @endphp

    <main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300 bg-[#f4f7fb]">

        <div class="p-6 space-y-6">

        {{-- ── PAGE HEADER ─────────────────────────────────────────────────── --}}
        <div class="rounded-[18px] bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white p-6 shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold mt-1">Dashboard</h1>
                <p class="text-blue-100 text-xs mt-1">
                    {{ $currentUserName }} · {{ $user['role'] ?? '-' }} · {{ $currentDepartment }} · {{ $currentFinancialYear }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Department switcher for SLT --}}
                @if($canViewCompanyDashboard && !empty($departments))
                    <form method="POST" action="{{ route('switch.department') }}" class="flex items-center gap-2">
                        @csrf
                        <select name="department_code" onchange="this.form.submit()"
                            class="text-xs border border-white/20 rounded-xl px-3 py-2 bg-white/10 text-white focus:outline-none focus:ring-2 focus:ring-white/30 backdrop-blur">
                            <option value="ALL" class="text-slate-900" {{ ($selectedDepartmentCode ?? 'ALL') === 'ALL' ? 'selected' : '' }}>All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept['code'] }}" class="text-slate-900" {{ ($selectedDepartmentCode ?? '') === $dept['code'] ? 'selected' : '' }}>
                                    {{ $dept['name'] ?? $dept['code'] }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <a href="{{ route('kpi.index') }}"
                   class="bg-white text-blue-900 hover:bg-blue-50 px-5 py-2.5 rounded-2xl shadow font-bold text-sm transition">
                    My KPIs
                </a>
                <a href="{{ route('weightage') }}"
                   class="bg-white/10 hover:bg-white/20 text-white px-5 py-2.5 rounded-2xl font-bold text-sm transition border border-white/20">
                    Weightage
                </a>
            </div>
        </div>

        <div class="space-y-8">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-xl text-sm border border-emerald-200 font-medium">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 text-red-700 px-4 py-3 rounded-xl text-sm border border-red-200 font-medium">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 text-red-700 px-4 py-3 rounded-xl text-sm border border-red-200 font-medium">{{ $errors->first() }}</div>
            @endif

            {{-- ════════════════════════════════════════════════════════════════
                 SECTION 1 — MY PERFORMANCE
            ═══════════════════════════════════════════════════════════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

                {{-- Score hero card --}}
                <div class="lg:col-span-2 brand-panel rounded-3xl p-6 text-white relative overflow-hidden soft-card">
                    <div class="absolute top-0 right-0 w-36 h-36 rounded-full bg-white/5 -translate-y-10 translate-x-10 pointer-events-none"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 rounded-full bg-white/5 translate-y-8 -translate-x-8 pointer-events-none"></div>

                    <p class="text-[10px] uppercase tracking-widest font-black text-blue-300">My Performance Score</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $currentFinancialYear }}</p>

                    @if($individualWeightage > 0)
                        <div class="mt-4 flex items-end gap-2">
                            <span class="text-6xl font-black leading-none {{ $individualScoreStyle['text'] }}">
                                {{ number_format($individualPerformance, 1) }}
                            </span>
                            <span class="text-2xl font-black text-white/50 mb-1">%</span>
                        </div>
                        <span class="inline-block mt-2 px-3 py-1 rounded-xl text-xs font-black border {{ $individualScoreStyle['badge'] }}">
                            {{ $individualScoreStyle['label'] }}
                        </span>
                        <div class="mt-4 h-2 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-2 rounded-full transition-all {{ $individualScoreStyle['bar'] }}" style="width: {{ min($individualPerformance, 100) }}%"></div>
                        </div>
                        <p class="text-[10px] text-blue-300/80 mt-2">
                            Based on {{ $individualKpiCount }} KPI · {{ number_format($individualWeightage, 0) }}% weightage set
                        </p>
                    @else
                        <div class="mt-4 text-2xl font-black text-white/50">— %</div>
                        <p class="text-xs text-blue-200 mt-2">
                            Weightage not set yet.
                            <a href="{{ route('weightage') }}" class="underline font-bold text-white">Set now →</a>
                        </p>
                        <p class="text-[10px] text-blue-300/80 mt-2">{{ $individualKpiCount }} KPI created</p>
                    @endif

                    <div class="mt-5 pt-4 border-t border-white/10 flex gap-2">
                        <a href="{{ route('kpi.index') }}" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-black transition">My KPIs</a>
                        <a href="{{ route('weightage') }}"  class="px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-black transition">Weightage</a>
                        @if($isManager)
                            <a href="{{ route('kpi.my-department-kpi') }}" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-black transition">Team KPIs</a>
                        @endif
                    </div>
                </div>

                {{-- Quick stats --}}
                <div class="lg:col-span-3 grid grid-cols-2 sm:grid-cols-4 gap-4 content-start">

                    <div class="bg-white rounded-2xl border border-slate-200 p-4 soft-card col-span-1">
                        <p class="text-[10px] uppercase font-black text-slate-400">Total KPIs</p>
                        <p class="text-4xl font-black text-slate-900 mt-2">{{ $individualKpiCount }}</p>
                        <p class="text-[10px] text-slate-400 mt-1">This year</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-emerald-100 p-4 soft-card col-span-1">
                        <p class="text-[10px] uppercase font-black text-emerald-600">On Track</p>
                        <p class="text-4xl font-black text-emerald-700 mt-2">{{ $myOnTrack }}</p>
                        <div class="mt-2 h-1.5 bg-emerald-50 rounded-full overflow-hidden">
                            <div class="h-1.5 bg-emerald-400 rounded-full"
                                 style="width: {{ $individualKpiCount > 0 ? round(($myOnTrack / $individualKpiCount) * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border {{ $myAtRisk > 0 ? 'border-red-100' : 'border-slate-200' }} p-4 soft-card col-span-1">
                        <p class="text-[10px] uppercase font-black {{ $myAtRisk > 0 ? 'text-red-500' : 'text-slate-400' }}">Needs Attention</p>
                        <p class="text-4xl font-black {{ $myAtRisk > 0 ? 'text-red-700' : 'text-slate-400' }} mt-2">{{ $myAtRisk }}</p>
                        <p class="text-[10px] mt-1 {{ $myAtRisk > 0 ? 'text-red-400 font-bold' : 'text-slate-400' }}">
                            {{ $myAtRisk > 0 ? 'Review required' : 'All clear' }}
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-blue-100 p-4 soft-card col-span-1">
                        <p class="text-[10px] uppercase font-black text-blue-400">Completed</p>
                        <p class="text-4xl font-black text-blue-700 mt-2">{{ $myCompleted }}</p>
                        <div class="mt-2 h-1.5 bg-blue-50 rounded-full overflow-hidden">
                            <div class="h-1.5 bg-blue-400 rounded-full"
                                 style="width: {{ $individualKpiCount > 0 ? round(($myCompleted / $individualKpiCount) * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Overview banner for managers / SLT --}}
                    @if($isManager && $kpiCollection->count() > $individualKpiCount)
                        <div class="col-span-2 sm:col-span-4 bg-white rounded-2xl border border-indigo-100 p-4 soft-card">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] uppercase font-black text-indigo-500 mb-2">
                                        {{ $canViewCompanyDashboard ? 'Company Overview' : 'Department Overview' }}
                                    </p>
                                    <div class="flex flex-wrap gap-6">
                                        <div>
                                            <p class="text-[10px] text-slate-500">Total Visible KPIs</p>
                                            <p class="text-xl font-black text-slate-900">{{ $kpiCollection->count() }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-slate-500">Staff</p>
                                            <p class="text-xl font-black text-slate-900">{{ $staffPerformanceRows->count() }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-slate-500">At Risk</p>
                                            <p class="text-xl font-black {{ $kpiRows->where('_is_risk', true)->count() > 0 ? 'text-red-700' : 'text-emerald-700' }}">
                                                {{ $kpiRows->where('_is_risk', true)->count() }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-slate-500">Completed</p>
                                            <p class="text-xl font-black text-blue-700">{{ $kpiRows->where('status', 'completed')->count() }}</p>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('kpi.my-department-kpi') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-black hover:bg-indigo-700 transition shrink-0">
                                    View Team KPIs →
                                </a>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════════════
                 SECTION 2 — MY KPIs
            ═══════════════════════════════════════════════════════════════════ --}}
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">My KPIs</h2>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $individualKpiCount }} KPI · {{ $currentFinancialYear }}</p>
                    </div>
                    <a href="{{ route('kpi.index') }}" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black hover:bg-slate-800 transition">
                        Manage KPIs →
                    </a>
                </div>

                @if($orderedCategoryGroups->isEmpty())
                    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-10 text-center">
                        <p class="text-slate-400 text-sm">No KPIs created yet for {{ $currentFinancialYear }}.</p>
                        <a href="{{ route('kpi.create') }}" class="mt-3 inline-block px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-black hover:bg-indigo-700">
                            Create First KPI
                        </a>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($orderedCategoryGroups as $category => $categoryKpis)
                            @php $catStyle = $categoryStyles[$category] ?? $categoryStyles['Default']; @endphp
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-3 py-1 rounded-xl text-xs font-black {{ $catStyle['bg'] }}">
                                        {{ $category ?: 'General' }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium">{{ $categoryKpis->count() }} KPI</span>
                                </div>

                                <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                                    @foreach($categoryKpis as $kpi)
                                        @php
                                            $kpiScore     = $kpi['_score'] ?? 0;
                                            $kpiWeightage = $kpi['_weightage'] ?? 0;
                                            $kpiStatus    = $kpi['status'] ?? 'not_started';
                                            $scoreSt      = $scoreStyle($kpiScore);
                                            $badgeSt      = $statusBadge($kpiStatus);
                                            $leftBorder   = $cardBorderColor($kpiStatus);
                                            $quarters     = collect($kpi['quarters'] ?? []);
                                        @endphp

                                        <div onclick="openKpiDetail('{{ $kpi['id'] }}')"
                                             class="bg-white rounded-2xl border border-l-4 border-slate-200 {{ $leftBorder }} p-4 cursor-pointer hover:shadow-lg transition-shadow soft-card group">

                                            {{-- Header: sub-category + weight + status badge --}}
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <div class="flex flex-wrap gap-1.5 min-w-0">
                                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-black {{ $catStyle['sub'] }}">
                                                        {{ $kpi['sub_category'] ?? '-' }}
                                                    </span>
                                                    @if($kpiWeightage > 0)
                                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-slate-100 text-slate-600">
                                                            {{ number_format($kpiWeightage, 0) }}% weight
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-amber-50 text-amber-600 border border-amber-200">
                                                            No weight
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="shrink-0 px-2 py-0.5 rounded-lg text-[10px] font-black {{ $badgeSt['class'] }}">
                                                    {{ $badgeSt['label'] }}
                                                </span>
                                            </div>

                                            {{-- KPI title + description --}}
                                            <h3 class="text-sm font-black text-slate-900 leading-snug mb-1 line-clamp-2">{{ $kpi['kpi_title'] }}</h3>
                                            <p class="text-[11px] text-slate-400 line-clamp-1 mb-3">{{ $kpi['kpi_description'] ?? 'No description.' }}</p>

                                            {{-- Achievement progress --}}
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="flex-1">
                                                    <div class="flex justify-between text-[10px] mb-1">
                                                        <span class="text-slate-400">Achievement</span>
                                                        <span class="font-black {{ $scoreSt['text'] }}">{{ number_format($kpiScore, 1) }}%</span>
                                                    </div>
                                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                                        <div class="h-2 rounded-full {{ $scoreSt['bar'] }}" style="width: {{ min($kpiScore, 100) }}%"></div>
                                                    </div>
                                                </div>
                                                <span class="px-2 py-1 rounded-lg text-[10px] font-black border {{ $scoreSt['badge'] }} shrink-0">
                                                    {{ $scoreSt['label'] }}
                                                </span>
                                            </div>

                                            {{-- Quarter dots --}}
                                            <div class="flex items-center gap-3">
                                                @foreach(['Q1', 'Q2', 'Q3', 'Q4'] as $qLabel)
                                                    @php
                                                        $qRow    = $quarters->firstWhere('quarter', $qLabel);
                                                        $qStatus = $qRow ? ($qRow['status'] ?? 'not_started') : null;
                                                    @endphp
                                                    <div class="flex items-center gap-1 text-[10px]"
                                                         title="{{ $qLabel }}: {{ $qStatus ? ucwords(str_replace('_', ' ', $qStatus)) : 'Not Planned' }}">
                                                        <div class="w-2.5 h-2.5 rounded-full {{ $qRow ? $qDotColor($qStatus) : 'bg-slate-200 border border-dashed border-slate-300' }}"></div>
                                                        <span class="font-bold {{ $qRow ? 'text-slate-700' : 'text-slate-400' }}">{{ $qLabel }}</span>
                                                    </div>
                                                @endforeach
                                                <span class="ml-auto text-[10px] text-slate-300 group-hover:text-indigo-500 transition font-black">View →</span>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ════════════════════════════════════════════════════════════════
                 SECTION 3 — TEAM OVERVIEW  (Manager / VP / SLT only)
            ═══════════════════════════════════════════════════════════════════ --}}
            @if($isManager && $staffPerformanceRows->count() > 0)
                <div>
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Team KPI Summary</h2>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $staffPerformanceRows->count() }} staff · sorted by score</p>
                        </div>
                        <a href="{{ route('kpi.my-department-kpi') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-black hover:bg-slate-200 transition">
                            Full View →
                        </a>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden soft-card">
                        <div class="overflow-x-auto thin-scroll">
                            <table class="w-full text-sm min-w-[640px]">
                                <thead>
                                    <tr class="brand-panel text-white text-[10px] uppercase tracking-wider">
                                        <th class="px-4 py-3 text-left font-black">#</th>
                                        <th class="px-4 py-3 text-left font-black">Name</th>
                                        <th class="px-4 py-3 text-left font-black">Department</th>
                                        <th class="px-4 py-3 text-center font-black">KPIs</th>
                                        <th class="px-4 py-3 text-center font-black">On Track</th>
                                        <th class="px-4 py-3 text-center font-black">At Risk</th>
                                        <th class="px-4 py-3 text-center font-black">Done</th>
                                        <th class="px-4 py-3 text-left font-black">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($staffPerformanceRows as $i => $staff)
                                        @php
                                            $staffStyle    = $scoreStyle($staff['performance']);
                                            $isCurrentUser = strtolower(trim($staff['name'] ?? '')) === strtolower(trim($currentUserName));
                                        @endphp
                                        <tr class="{{ $isCurrentUser ? 'bg-indigo-50/60' : 'hover:bg-slate-50' }} transition">
                                            <td class="px-4 py-3 text-xs text-slate-400 font-bold">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($staff['name'] ?? 'U') }}&background=0f172a&color=fff&size=28"
                                                             class="w-full h-full" />
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-black text-slate-900">
                                                            {{ $staff['name'] ?? 'Unknown' }}
                                                            @if($isCurrentUser) <span class="text-indigo-400 font-bold">(you)</span> @endif
                                                        </p>
                                                        <p class="text-[10px] text-slate-400">{{ ucfirst(strtolower($staff['role'] ?? '-')) }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-500">{{ $staff['department_code'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-center text-xs font-black text-slate-900">{{ $staff['kpi_count'] }}</td>
                                            <td class="px-4 py-3 text-center text-xs font-black text-emerald-700">{{ $staff['on_track_count'] }}</td>
                                            <td class="px-4 py-3 text-center text-xs font-black {{ ($staff['risk_count'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-300' }}">
                                                {{ $staff['risk_count'] ?? 0 }}
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs font-black text-blue-600">{{ $staff['completed_count'] ?? 0 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2 min-w-[100px]">
                                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                                        <div class="h-2 rounded-full {{ $staffStyle['bar'] }}"
                                                             style="width: {{ min($staff['performance'], 100) }}%"></div>
                                                    </div>
                                                    <span class="text-[10px] font-black {{ $staffStyle['text'] }} shrink-0 w-10 text-right">
                                                        {{ number_format($staff['performance'], 1) }}%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ════════════════════════════════════════════════════════════════
                 SECTION 4 — DEPARTMENT RANKING  (SLT only)
            ═══════════════════════════════════════════════════════════════════ --}}
            @if($canViewCompanyDashboard && $departmentPerformanceRows->count() > 1)
                <div>
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Department Performance</h2>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $departmentPerformanceRows->count() }} departments · company-wide</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($departmentPerformanceRows as $i => $dept)
                            @php $deptStyle = $scoreStyle($dept['performance']); @endphp
                            <div class="bg-white rounded-2xl border border-slate-200 p-5 soft-card">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            @if($i === 0)
                                                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-amber-100 text-amber-700 font-black">Top</span>
                                            @elseif($i === $departmentPerformanceRows->count() - 1)
                                                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-red-50 text-red-500 font-black">Needs Focus</span>
                                            @endif
                                            <p class="text-sm font-black text-slate-900">{{ $dept['department_code'] }}</p>
                                        </div>
                                        <p class="text-[10px] text-slate-500">{{ $dept['staff_count'] }} staff · {{ $dept['kpi_count'] }} KPIs</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black {{ $deptStyle['text'] }}">{{ number_format($dept['performance'], 1) }}%</p>
                                        <span class="text-[10px] px-2 py-0.5 rounded-lg border font-black {{ $deptStyle['badge'] }}">{{ $deptStyle['label'] }}</span>
                                    </div>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-2 rounded-full {{ $deptStyle['bar'] }}" style="width: {{ min($dept['performance'], 100) }}%"></div>
                                </div>
                                @if(($dept['risk_count'] ?? 0) > 0)
                                    <p class="text-[10px] text-red-500 mt-2 font-bold">{{ $dept['risk_count'] }} KPI needs attention</p>
                                @else
                                    <p class="text-[10px] text-emerald-600 mt-2 font-bold">All KPIs on track</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>{{-- /.space-y-8 --}}
        </div>{{-- /.p-6 --}}
    </main>

    {{-- ════════════════════════════════════════════════════════════════════════
         KPI DETAIL MODALS  (one per visible KPI)
    ═══════════════════════════════════════════════════════════════════════════ --}}
    @foreach($kpis ?? [] as $kpi)
        @php
            $modalStatus = $kpi['status'] ?? 'not_started';

            $modalStatusLabel = match($modalStatus) {
                'not_started' => 'Not Started',
                'on_track'    => 'On Track',
                'monitoring'  => 'Monitoring',
                'at_risk'     => 'At Risk',
                'risk'        => 'Risk',
                'in_trouble'  => 'In Trouble',
                'critical'    => 'Critical',
                'completed'   => 'Completed',
                default       => 'Not Started',
            };

            $modalStatusClass = match($modalStatus) {
                'on_track', 'monitoring' => 'bg-blue-100 text-blue-700',
                'at_risk', 'risk'        => 'bg-amber-100 text-amber-700',
                'in_trouble', 'critical' => 'bg-red-100 text-red-700',
                'completed'              => 'bg-emerald-100 text-emerald-700',
                default                  => 'bg-slate-100 text-slate-700',
            };

            $modalCategoryStyles = [
                'Financial'         => ['category' => 'bg-emerald-700 text-white', 'subs' => ['bg-emerald-50 text-emerald-800 border border-emerald-200']],
                'Growth & Customer' => ['category' => 'bg-indigo-700 text-white',  'subs' => ['bg-indigo-50 text-indigo-800 border border-indigo-200']],
                'Initiatives'       => ['category' => 'bg-amber-600 text-white',   'subs' => ['bg-amber-50 text-amber-800 border border-amber-200']],
                'People'            => ['category' => 'bg-pink-700 text-white',    'subs' => ['bg-pink-50 text-pink-800 border border-pink-200']],
                'Default'           => ['category' => 'bg-slate-700 text-white',   'subs' => ['bg-slate-50 text-slate-800 border border-slate-200']],
            ];

            $modalCategory       = $kpi['category'] ?? 'Default';
            $modalStyleSet       = $modalCategoryStyles[$modalCategory] ?? $modalCategoryStyles['Default'];
            $modalCategoryClass  = $modalStyleSet['category'];
            $modalSubCategoryClass = $modalStyleSet['subs'][0];
            $modalBaseTarget     = (float) ($kpi['base_target'] ?? 0);
            $modalActualValue    = (float) ($kpi['actual_value'] ?? 0);
            $modalAchievement    = $modalBaseTarget > 0 ? max(0, round(($modalActualValue / $modalBaseTarget) * 100, 2)) : 0;

            if      ($modalAchievement <= 25)  $modalProgressColor = 'bg-red-600';
            elseif  ($modalAchievement <= 50)  $modalProgressColor = 'bg-gradient-to-r from-red-600 to-orange-500';
            elseif  ($modalAchievement <= 75)  $modalProgressColor = 'bg-gradient-to-r from-orange-500 to-yellow-400';
            elseif  ($modalAchievement <= 100) $modalProgressColor = 'bg-gradient-to-r from-yellow-400 to-green-500';
            else                               $modalProgressColor = 'bg-emerald-700';
        @endphp

        <div id="kpi-modal-{{ $kpi['id'] }}"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4"
             onclick="closeKpiDetail('{{ $kpi['id'] }}')">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
                 onclick="event.stopPropagation()">

                {{-- Modal header --}}
                <div class="px-4 py-3 brand-panel text-white flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">KPI Detail</p>
                        <h3 class="text-sm font-black mt-1 leading-snug line-clamp-2">{{ $kpi['kpi_title'] ?? '-' }}</h3>
                    </div>
                    <button type="button" onclick="closeKpiDetail('{{ $kpi['id'] }}')"
                            class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-xs shrink-0 font-bold">✕</button>
                </div>

                {{-- Modal body --}}
                <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto thin-scroll">

                    <div class="grid grid-cols-3 gap-2">
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-2">
                            <p class="text-[9px] text-slate-400 uppercase">Base Target</p>
                            <p class="text-xs font-black text-slate-900 truncate">{{ number_format($modalBaseTarget, 2) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-2">
                            <p class="text-[9px] text-slate-400 uppercase">Stretch Target</p>
                            <p class="text-xs font-black text-slate-900 truncate">{{ number_format((float) ($kpi['stretch_target'] ?? 0), 2) }}</p>
                        </div>
                        <div class="rounded-xl bg-blue-50 border border-blue-100 p-2">
                            <p class="text-[9px] text-blue-400 uppercase">Actual</p>
                            <p class="text-xs font-black text-blue-800 truncate">{{ number_format($modalActualValue, 2) }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase">Progress</p>
                                <p class="text-sm font-black text-slate-900">{{ number_format($modalAchievement, 2) }}%</p>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-lg {{ $modalStatusClass }}">{{ $modalStatusLabel }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="{{ $modalProgressColor }} h-2 rounded-full" style="width: {{ min($modalAchievement, 100) }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-[10px] text-slate-400 uppercase mb-1">Category</p>
                            <span class="inline-block px-2 py-1 rounded-lg text-[10px] font-bold {{ $modalCategoryClass }}">{{ $kpi['category'] ?? '-' }}</span>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-[10px] text-slate-400 uppercase mb-1">Sub Category</p>
                            <span class="inline-block px-2 py-1 rounded-lg text-[10px] font-semibold {{ $modalSubCategoryClass }}">{{ $kpi['sub_category'] ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                        <p class="text-[10px] text-slate-400 uppercase mb-1">Description</p>
                        <p class="text-xs text-slate-700 leading-relaxed">{{ $kpi['kpi_description'] ?? 'No description.' }}</p>
                    </div>

                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
                        <p class="text-[10px] text-amber-500 uppercase mb-1">Remark</p>
                        <p class="text-xs text-amber-800 leading-relaxed">{{ $kpi['remark'] ?? 'No remark.' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-xl bg-white border border-slate-100 p-3">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wide">Unit</p>
                            @php
                                $unitDisplay = match(strtolower(trim($kpi['unit'] ?? ''))) {
                                    'currency'   => 'RM',
                                    'percentage' => '%',
                                    default      => '—',
                                };
                            @endphp
                            <p class="font-bold text-slate-800 mt-1">{{ $unitDisplay }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                            <p class="text-[10px] text-slate-400 uppercase">Last Check-In</p>
                            @php
                                $lastActive = isset($kpi['last_activity']) ? \Carbon\Carbon::parse($kpi['last_activity']) : null;
                            @endphp
                            <p class="font-bold text-slate-800 mt-1 text-[11px]">{{ $lastActive ? $lastActive->format('d M Y, h:i A') : '-' }}</p>
                        </div>
                    </div>

                </div>

                {{-- Modal footer --}}
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                    <button type="button" onclick="closeKpiDetail('{{ $kpi['id'] }}')"
                            class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100">
                        Close
                    </button>
                    <a href="{{ route('kpi.edit', $kpi['id']) }}"
                       class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold hover:bg-slate-800">
                        Edit KPI
                    </a>
                </div>
            </div>
        </div>
    @endforeach

    <script>
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

        // Keep existing quarter/history modal helpers so sidebar/other pages still work
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

        function openHistoryModal(id) {
            const modal = document.getElementById('history-modal-' + id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeHistoryModal(id) {
            const modal = document.getElementById('history-modal-' + id);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    </script>

</body>
</html>
