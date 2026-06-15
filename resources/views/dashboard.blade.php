<!DOCTYPE html>
<html>
<head>
    <title>RCG KPI Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .brand-panel { background: radial-gradient(circle at top left,rgba(59,130,246,.16),transparent 30%), radial-gradient(circle at bottom right,rgba(20,184,166,.13),transparent 34%), linear-gradient(135deg,#06142f 0%,#0b1f45 52%,#020617 100%); }
        .soft-card   { box-shadow: 0 8px 30px rgba(15,23,42,.07); }
        .thin-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .thin-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .line-clamp-1 { display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden; }
        .line-clamp-2 { display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
        .dept-body { overflow:hidden; transition: max-height .4s ease, opacity .3s ease; }
        .dept-body.open   { max-height: 9999px; opacity:1; }
        .dept-body.closed { max-height: 0;      opacity:0; }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

@include('partials.sidebar')

@php
    // ── CORE ────────────────────────────────────────────────────────────────
    $role              = strtoupper(trim($user['role'] ?? ''));
    $currentUserId     = (string)($user['id'] ?? $user['employee_id'] ?? '');
    $currentUserName   = $user['short_name'] ?? $user['full_name'] ?? $user['name'] ?? 'User';
    $currentDepartment = $user['department_code'] ?? '-';
    $currentFinancialYear = $currentFinancialYear ?? ('FY'.now()->year);
    $userPosition      = $user['position'] ?? $user['role'] ?? '-';

    $canViewCompanyDashboard = $role === 'SLT';
    $isManager = in_array($role, ['SLT','VP','MANAGER']);

    $kpiCollection = collect($kpis ?? []);

    // ── SCORE STYLE ─────────────────────────────────────────────────────────
    $scoreStyle = function($s) {
        $s = (float)$s;
        if ($s <= 25)  return ['bar'=>'bg-red-600',                                     'text'=>'text-red-700',     'badge'=>'bg-red-50 text-red-700 border-red-100',        'label'=>'Critical','hex'=>'#ef4444'];
        if ($s <= 50)  return ['bar'=>'bg-gradient-to-r from-red-600 to-orange-500',    'text'=>'text-orange-700',  'badge'=>'bg-orange-50 text-orange-700 border-orange-100','label'=>'Risk',    'hex'=>'#f97316'];
        if ($s <= 75)  return ['bar'=>'bg-gradient-to-r from-orange-500 to-yellow-400', 'text'=>'text-amber-700',   'badge'=>'bg-amber-50 text-amber-700 border-amber-100',  'label'=>'Watch',   'hex'=>'#f59e0b'];
        if ($s <= 100) return ['bar'=>'bg-gradient-to-r from-yellow-400 to-emerald-600','text'=>'text-emerald-700', 'badge'=>'bg-emerald-50 text-emerald-700 border-emerald-100','label'=>'Good', 'hex'=>'#10b981'];
        return                 ['bar'=>'bg-emerald-700',                                'text'=>'text-emerald-800', 'badge'=>'bg-emerald-50 text-emerald-800 border-emerald-100','label'=>'Exceeded','hex'=>'#059669'];
    };

    // ── KPI SCORE ───────────────────────────────────────────────────────────
    $calculateKpiScore = function($kpi) {
        $quarters = collect($kpi['quarters'] ?? []);
        $qBase = 0; $qActual = 0;
        foreach (['Q1','Q2','Q3','Q4'] as $q) {
            $row = $quarters->firstWhere('quarter',$q) ?? [];
            $qBase   += max(0,(float)($row['quarter_target'] ?? 0));
            $qActual += max(0,(float)($row['quarter_actual'] ?? 0));
        }
        if ($qBase > 0) return round(($qActual/$qBase)*100,2);
        $base   = max(0,(float)($kpi['base_target'] ?? 0));
        $actual = max(0,(float)($kpi['actual_value'] ?? 0));
        return $base > 0 ? round(($actual/$base)*100,2) : 0;
    };

    $calculateWeightedScore = function($kpi) use($calculateKpiScore) {
        return round(((float)$calculateKpiScore($kpi) * max(0,(float)($kpi['weightage']??0)))/100,2);
    };

    // ── KPI ROWS ────────────────────────────────────────────────────────────
    $riskStatuses = ['at_risk','risk','in_trouble','critical'];
    $kpiRows = $kpiCollection->map(function($kpi) use($calculateKpiScore,$calculateWeightedScore,$riskStatuses) {
        $score     = $calculateKpiScore($kpi);
        $weightage = max(0,(float)($kpi['weightage'] ?? 0));
        $status    = strtolower($kpi['status'] ?? 'not_started');
        return array_merge($kpi,[
            '_score'           => $score,
            '_weightage'       => $weightage,
            '_weighted_score'  => $calculateWeightedScore($kpi),
            '_is_risk'         => in_array($status,$riskStatuses),
            '_employee_key'    => (string)($kpi['employee_id'] ?? 'unassigned'),
            '_employee_name'   => $kpi['owner_display_name'] ?? $kpi['employee_name'] ?? $kpi['owner_name'] ?? 'Unassigned',
            '_department_code' => $kpi['owner_department_code'] ?? $kpi['department_code'] ?? '-',
        ]);
    });

    // ── MY KPIs ─────────────────────────────────────────────────────────────
    $individualKpis = $kpiRows->filter(fn($k) => (string)($k['employee_id']??'') === $currentUserId);
    $individualPerformance = round($individualKpis->sum('_weighted_score'),2);
    $individualWeightage   = round($individualKpis->sum('_weightage'),2);
    $individualKpiCount    = $individualKpis->count();
    $myOnTrack    = $individualKpis->whereIn('status',['on_track','monitoring'])->count();
    $myAtRisk     = $individualKpis->whereIn('status',['at_risk','risk','in_trouble','critical'])->count();
    $myCompleted  = $individualKpis->sum(fn($k) => collect($k['quarters'] ?? [])->filter(fn($q) => ($q['status'] ?? '') === 'completed' && !empty($q['completion_submitted_at']))->count());
    $myTotalQuarters = $individualKpis->sum(fn($k) => count($k['quarters'] ?? []));
    $individualScoreStyle = $scoreStyle($individualPerformance);

    // ── CATEGORY GROUPS ─────────────────────────────────────────────────────
    $categoryOrder  = ['Financial','Growth & Customer','Initiatives','People'];
    $categoryStyles = [
        'Financial'         => ['bg'=>'bg-emerald-700 text-white','sub'=>'bg-emerald-50 text-emerald-800 border border-emerald-200','left'=>'border-l-emerald-400'],
        'Growth & Customer' => ['bg'=>'bg-indigo-700 text-white', 'sub'=>'bg-indigo-50 text-indigo-800 border border-indigo-200',  'left'=>'border-l-indigo-400'],
        'Initiatives'       => ['bg'=>'bg-amber-600 text-white',  'sub'=>'bg-amber-50 text-amber-800 border border-amber-200',    'left'=>'border-l-amber-400'],
        'People'            => ['bg'=>'bg-pink-700 text-white',   'sub'=>'bg-pink-50 text-pink-800 border border-pink-200',       'left'=>'border-l-pink-400'],
        'Default'           => ['bg'=>'bg-slate-700 text-white',  'sub'=>'bg-slate-50 text-slate-800 border border-slate-200',    'left'=>'border-l-slate-300'],
    ];
    $myKpisByCategory = $individualKpis->groupBy('category');
    $orderedCategoryGroups = collect();
    foreach ($categoryOrder as $cat) { if ($myKpisByCategory->has($cat)) $orderedCategoryGroups[$cat] = $myKpisByCategory->get($cat); }
    foreach ($myKpisByCategory as $cat => $items) { if (!in_array($cat,$categoryOrder)) $orderedCategoryGroups[$cat] = $items; }

    // ── STATUS HELPERS ───────────────────────────────────────────────────────
    $qDotColor   = fn($s) => match(strtolower($s??'')) { 'on_track','monitoring'=>'bg-emerald-500','at_risk','risk'=>'bg-amber-400','in_trouble','critical'=>'bg-red-500','completed'=>'bg-blue-500',default=>'bg-slate-300' };
    $statusBadge = fn($s) => match(strtolower($s??'')) { 'on_track','monitoring'=>['class'=>'bg-emerald-100 text-emerald-700','label'=>'On Track'],'at_risk','risk'=>['class'=>'bg-amber-100 text-amber-700','label'=>'At Risk'],'in_trouble','critical'=>['class'=>'bg-red-100 text-red-700','label'=>'In Trouble'],'completed'=>['class'=>'bg-blue-100 text-blue-700','label'=>'Completed'],default=>['class'=>'bg-slate-100 text-slate-600','label'=>'Not Started'] };
    $cardBorder  = fn($s) => match(strtolower($s??'')) { 'on_track','monitoring'=>'border-l-emerald-400','at_risk','risk'=>'border-l-amber-400','in_trouble','critical'=>'border-l-red-400','completed'=>'border-l-blue-400',default=>'border-l-slate-200' };

    // ── STAFF BASE ROWS ──────────────────────────────────────────────────────
    // Use name as fallback key so people with null employee_id don't merge
    $kpiRowsKeyed = $kpiRows->map(function($kpi) {
        $empId   = (string)($kpi['employee_id'] ?? '');
        $empName = $kpi['_employee_name'] ?? '';
        $safeKey = $empId ?: ($empName ?: 'unassigned');
        return array_merge($kpi, ['_safe_key' => $safeKey]);
    });

    $staffBaseRows = $kpiRowsKeyed->groupBy('_safe_key')->map(function($items) {
        $first = $items->first();
        return [
            'employee_id'     => $first['employee_id'] ?? '',
            'name'            => $first['_employee_name'] ?? 'Unknown',
            'department_code' => $first['_department_code'] ?? '-',
            'role'            => $first['owner_role'] ?? $first['employee_role'] ?? $first['position'] ?? '-',
            'kpi_count'       => $items->count(),
            'weightage_total' => round($items->sum('_weightage'),2),
            'performance'     => round($items->sum('_weighted_score'),2),
            'risk_count'      => $items->where('_is_risk',true)->count(),
            'completed_count' => $items->sum(fn($k) => collect($k['quarters'] ?? [])->filter(fn($q) => ($q['status'] ?? '') === 'completed' && !empty($q['completion_submitted_at']))->count()),
            'on_track_count'  => $items->whereIn('status',['on_track','monitoring'])->count(),
        ];
    })->values();

    // ── PER-EMPLOYEE QUARTERLY SCORES ────────────────────────────────────────
    $empQuarterMap = [];
    foreach ($kpiRows as $kpi) {
        $empKey = $kpi['_employee_key'];
        $quarters = collect($kpi['quarters'] ?? []);
        $weight   = (float)($kpi['_weightage'] ?? 0);
        foreach (['Q1','Q2','Q3','Q4'] as $q) {
            $qRow    = $quarters->firstWhere('quarter',$q);
            if (!$qRow) continue;
            $qTarget = max(0,(float)($qRow['quarter_target'] ?? 0));
            $qActual = max(0,(float)($qRow['quarter_actual'] ?? 0));
            $qPct    = $qTarget > 0 ? ($qActual/$qTarget)*100 : 0;
            $empQuarterMap[$empKey][$q] = ($empQuarterMap[$empKey][$q] ?? 0) + round($qPct * $weight / 100, 3);
        }
    }

    // ── STAFF WITH QUARTERLY DATA ────────────────────────────────────────────
    $staffPerformanceRows = $staffBaseRows->map(function($staff) use($empQuarterMap) {
        $key = (string)($staff['employee_id'] ?? '');
        $q = $empQuarterMap[$key] ?? [];
        return array_merge($staff,[
            'q1' => round($q['Q1'] ?? 0,2),
            'q2' => round($q['Q2'] ?? 0,2),
            'q3' => round($q['Q3'] ?? 0,2),
            'q4' => round($q['Q4'] ?? 0,2),
        ]);
    })->values()->sortByDesc('performance');

    // ── ROLE HIERARCHY SORT ──────────────────────────────────────────────────
    $rolePriority = function($role) {
        return match(strtoupper(trim($role ?? ''))) {
            'SLT'       => 1,
            'VP'        => 2,
            'MANAGER'   => 3,
            'EXECUTIVE' => 4,
            default     => 5,
        };
    };

    // ── DEPARTMENT ROWS — all employees, KPI data merged in where available ──
    $kpiByEmpId = $kpiRows->groupBy(fn($k) => (string)($k['employee_id'] ?? ''));

    $deptRows = collect($allEmployees ?? [])->map(function($emp) use($kpiByEmpId, $empQuarterMap) {
        $empId   = (string)($emp['id'] ?? '');
        $empKpis = $kpiByEmpId->get($empId, collect());
        $q       = $empQuarterMap[$empId] ?? [];
        return [
            'employee_id'     => $empId,
            'name'            => $emp['short_name'] ?? $emp['full_name'] ?? 'Unknown',
            'department_code' => $emp['department_code'] ?? '-',
            'role'            => $emp['role'] ?? '-',
            'kpi_count'       => $empKpis->count(),
            'performance'     => round($empKpis->sum('_weighted_score'), 2),
            'risk_count'      => $empKpis->where('_is_risk', true)->count(),
            'q1'              => round($q['Q1'] ?? 0, 2),
            'q2'              => round($q['Q2'] ?? 0, 2),
            'q3'              => round($q['Q3'] ?? 0, 2),
            'q4'              => round($q['Q4'] ?? 0, 2),
        ];
    })->groupBy('department_code')->map(function($staff, $deptCode) use($rolePriority) {
        $cnt   = $staff->count();
        $bands = [0,0,0,0];
        foreach ($staff as $s) {
            $p = (float)$s['performance'];
            if ($p >= 90) $bands[0]++;
            elseif ($p >= 75) $bands[1]++;
            elseif ($p >= 50) $bands[2]++;
            else $bands[3]++;
        }
        $sortedStaff = $staff->sortBy(
            fn($s) => sprintf('%d_%s', $rolePriority($s['role']), strtolower($s['name'] ?? ''))
        )->values();
        return [
            'department_code' => $deptCode ?: '-',
            'staff_count'     => $cnt,
            'kpi_count'       => $staff->sum('kpi_count'),
            'performance'     => round($cnt > 0 ? $staff->avg('performance') : 0, 2),
            'risk_count'      => $staff->sum('risk_count'),
            'q1'              => round($cnt > 0 ? $staff->avg('q1') : 0, 2),
            'q2'              => round($cnt > 0 ? $staff->avg('q2') : 0, 2),
            'q3'              => round($cnt > 0 ? $staff->avg('q3') : 0, 2),
            'q4'              => round($cnt > 0 ? $staff->avg('q4') : 0, 2),
            'band_counts'     => $bands,
            'staff_list'      => $sortedStaff->toArray(),
        ];
    })->values()->sortByDesc('performance');

    // ── COMPANY TOTALS ───────────────────────────────────────────────────────
    $companyPerformance = $staffPerformanceRows->count() > 0 ? round($staffPerformanceRows->avg('performance'),2) : 0;
    $companyScoreStyle  = $scoreStyle($companyPerformance);
    $totalStaffCount    = $staffPerformanceRows->count();
    $totalKpisVisible   = $kpiCollection->count();
    $totalAtRisk        = $kpiRows->where('_is_risk',true)->count();
    $totalCompleted     = $kpiRows->sum(fn($k) => collect($k['quarters'] ?? [])->filter(fn($q) => ($q['status'] ?? '') === 'completed' && !empty($q['completion_submitted_at']))->count());
    $totalQuarters      = $kpiRows->sum(fn($k) => count($k['quarters'] ?? []));
    $companyDeptCount = $companyTotalDepts ?? count($companyDeptRanking ?? []);

    // ── COMPANY BAND COUNTS ──────────────────────────────────────────────────
    $compBands = [0,0,0,0];
    foreach ($staffPerformanceRows as $s) {
        $p = (float)$s['performance'];
        if ($p >= 90) $compBands[0]++;
        elseif ($p >= 75) $compBands[1]++;
        elseif ($p >= 50) $compBands[2]++;
        else $compBands[3]++;
    }

    // ── MY DEPARTMENT SCORE (for the donut panel) ───────────────────────────
    $myDeptRow        = $deptRows->firstWhere('department_code', $currentDepartment);
    $myDeptPerformance = $myDeptRow ? (float)$myDeptRow['performance'] : 0;
    $myDeptBands      = $myDeptRow ? ($myDeptRow['band_counts'] ?? [0,0,0,0]) : [0,0,0,0];
    $myDeptScoreStyle = $scoreStyle($myDeptPerformance);

    // ── DATA FOR JS CHARTS ───────────────────────────────────────────────────
    $deptChartData = $deptRows->map(fn($d) => [
        'code'    => $d['department_code'],
        'annual'  => $d['performance'],
        'q1'      => $d['q1'],
        'q2'      => $d['q2'],
        'q3'      => $d['q3'],
        'q4'      => $d['q4'],
        'bands'   => $d['band_counts'],
        'staff'   => $d['staff_count'],
        'at_risk' => $d['risk_count'],
    ])->values()->all();
@endphp

<main id="mainContent" class="ml-[230px] min-h-screen">
<div class="p-4 space-y-3">

{{-- ═══════ HEADER ═══════════════════════════════════════════════════════ --}}
<div class="rounded-[18px] bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white px-6 py-4 shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h1 class="text-xl font-black">Dashboard</h1>
        <p class="text-blue-100 text-[11px] mt-0.5">{{ $currentUserName }} · {{ $user['role'] ?? '-' }} · {{ $currentDepartment }} · {{ $currentFinancialYear }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('kpi.index') }}"   class="bg-white text-blue-900 hover:bg-blue-50 px-4 py-2 rounded-xl shadow font-bold text-xs transition">My KPIs</a>
        <a href="{{ route('weightage') }}"   class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl font-bold text-xs transition border border-white/20">Weightage</a>
    </div>
</div>

@if(session('success'))<div class="bg-emerald-50 text-emerald-700 px-3 py-2 rounded-xl text-xs border border-emerald-200">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-red-50 text-red-700 px-3 py-2 rounded-xl text-xs border border-red-200">{{ session('error') }}</div>@endif
@if($errors->any())<div class="bg-red-50 text-red-700 px-3 py-2 rounded-xl text-xs border border-red-200">{{ $errors->first() }}</div>@endif

{{-- ═══════ TIER 1: DEPT RANKING — visible to ALL roles ═══════════════════ --}}
@php $rankingCount = count($companyDeptRanking ?? []); @endphp
@if($rankingCount > 0 || $deptRows->count() > 0)
<div class="grid grid-cols-1 xl:grid-cols-5 gap-3">

    {{-- Department Annual Ranking (always visible, all company depts) --}}
    <div class="{{ $isManager ? 'xl:col-span-3' : 'xl:col-span-5' }} bg-white rounded-2xl p-4 soft-card border border-slate-100">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-xs font-black text-slate-900">Department Annual Ranking</h3>
            <span class="text-[10px] text-slate-400">{{ $currentFinancialYear }}</span>
        </div>
        <p class="text-[10px] text-slate-400 mb-3">All {{ $rankingCount }} departments · sorted by highest achievement</p>
        <div style="height:{{ max(100, $rankingCount * 34) }}px; position:relative;">
            <canvas id="chartDeptRanking"></canvas>
        </div>
    </div>

    {{-- Right panel: donut + stat tiles (managers only) --}}
    @if($isManager)
    <div class="xl:col-span-2 flex flex-col gap-3">

        {{-- My Department donut --}}
        <div class="bg-white rounded-2xl p-3 soft-card border border-slate-100 flex items-center gap-3">
            <div class="relative shrink-0" style="width:88px;height:88px;">
                <canvas id="chartCompanyDonut" width="88" height="88"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <p class="text-sm font-black {{ $myDeptScoreStyle['text'] }}">{{ number_format($myDeptPerformance,1) }}%</p>
                    <p class="text-[8px] text-slate-400">{{ $currentDepartment }}</p>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[9px] font-black text-slate-500 uppercase mb-0.5">{{ $currentDepartment }} Achievement</p>
                <p class="text-[8px] text-slate-400 mb-1.5">Staff score distribution</p>
                @php $blInfo = [['bg-emerald-500','text-emerald-700','Excellent','≥90%'],['bg-indigo-500','text-indigo-700','Good','75–89%'],['bg-amber-500','text-amber-700','Watch','50–74%'],['bg-red-500','text-red-700','Critical','<50%']]; @endphp
                <div class="grid grid-cols-2 gap-x-2 gap-y-1">
                    @foreach($blInfo as $bi => $bl)
                    <div class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full {{ $bl[0] }} shrink-0"></div>
                        <span class="text-[9px] {{ $bl[1] }} font-bold">{{ $myDeptBands[$bi] }} staff</span>
                        <span class="text-[9px] text-slate-400">· {{ $bl[2] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 2 stat tiles: Total Staff (all company) + Completed KPIs --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded-xl p-3 soft-card border border-slate-100">
                <p class="text-[9px] uppercase font-black text-slate-400">Total Staff</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $companyTotalStaff ?: $totalStaffCount }}</p>
                <p class="text-[9px] text-slate-400">{{ $companyDeptCount ?: $deptRows->count() }} depts</p>
            </div>
            <div class="bg-white rounded-xl p-3 soft-card border border-blue-100">
                <p class="text-[9px] uppercase font-black text-blue-400">Completed Quarters</p>
                <p class="text-2xl font-black text-blue-700 mt-1">{{ $totalCompleted }}</p>
                <p class="text-[9px] text-blue-400">of {{ $totalQuarters }} quarters</p>
            </div>
        </div>
    </div>
    @endif

</div>
@endif

{{-- ═══════ TIER 2: DEPT ANALYTICS — managers+ only ══════════════════════ --}}
@if($isManager && $deptRows->count() > 0)

    {{-- Quarterly trend --}}
    <div class="bg-white rounded-2xl p-4 soft-card border border-slate-100">
        <h3 class="text-xs font-black text-slate-900">Quarterly Performance — All Departments</h3>
        <p class="text-[10px] text-slate-400 mt-0.5 mb-3">Q1 → Q4 avg score per dept · {{ $currentFinancialYear }}</p>
        <div style="height:160px; position:relative;">
            <canvas id="chartQuarterTrend"></canvas>
        </div>
    </div>

    {{-- Department Staff Breakdown accordion (starts collapsed) --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-black text-slate-900">Department Staff Breakdown</h2>
                <p class="text-[10px] text-slate-400 mt-0.5">All staff · quarterly scores · sorted by annual achievement</p>
            </div>
            <button onclick="toggleAllDepts()" id="toggleAllBtn"
                    class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-black hover:bg-slate-200 transition">
                Expand All
            </button>
        </div>

        @foreach($deptRows as $dept)
            @php
                $dstyle   = $scoreStyle($dept['performance']);
                $safeCode = preg_replace('/[^A-Za-z0-9]/', '_', $dept['department_code']);
            @endphp

            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden soft-card">

                {{-- Dept accordion header --}}
                <div class="flex items-center justify-between px-4 py-3 cursor-pointer select-none hover:bg-slate-50/60 transition"
                     onclick="toggleDept('{{ $safeCode }}')">
                    <div class="flex items-center gap-3">
                        <div class="relative w-10 h-10 shrink-0">
                            <canvas id="ring-{{ $safeCode }}" width="40" height="40"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-[8px] font-black {{ $dstyle['text'] }} leading-tight text-center">{{ number_format($dept['performance'],1) }}%</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-slate-900">{{ $dept['department_code'] }}</h3>
                            <p class="text-[9px] text-slate-400">{{ $dept['staff_count'] }} staff · {{ $dept['kpi_count'] }} KPIs</p>
                        </div>
                        <div class="hidden md:flex items-center gap-3 ml-1">
                            @foreach(['q1'=>'Q1','q2'=>'Q2','q3'=>'Q3','q4'=>'Q4'] as $qk => $ql)
                                @php $qst = $scoreStyle($dept[$qk]); @endphp
                                <div class="text-center">
                                    <p class="text-[8px] text-slate-400">{{ $ql }}</p>
                                    <p class="text-[9px] font-black {{ $qst['text'] }}">{{ $dept[$qk] > 0 ? number_format($dept[$qk],1).'%' : '—' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[9px] px-2 py-0.5 rounded-lg border font-black {{ $dstyle['badge'] }}">{{ $dstyle['label'] }}</span>
                        @if($dept['risk_count'] > 0)
                            <span class="text-[9px] px-2 py-0.5 rounded-lg bg-red-50 text-red-600 font-black border border-red-100">{{ $dept['risk_count'] }} risk</span>
                        @endif
                        <svg id="chev-{{ $safeCode }}" class="w-4 h-4 text-slate-400 transition-transform duration-300" style="transform:rotate(-90deg)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                {{-- Dept body — starts CLOSED --}}
                <div id="dept-body-{{ $safeCode }}" class="dept-body closed border-t border-slate-100">
                    <div class="p-4">

                        {{-- Staff table --}}
                        <div>
                            <div class="overflow-x-auto thin-scroll">
                                <table class="w-full min-w-[540px]">
                                    <thead>
                                        <tr class="bg-slate-50 text-[9px] uppercase tracking-wider text-slate-500 font-black border-b border-slate-100">
                                            <th class="px-2 py-1.5 text-left">#</th>
                                            <th class="px-2 py-1.5 text-left">Name</th>
                                            <th class="px-2 py-1.5 text-left">Role</th>
                                            <th class="px-2 py-1.5 text-center">KPIs</th>
                                            <th class="px-2 py-1.5 text-center">Q1</th>
                                            <th class="px-2 py-1.5 text-center">Q2</th>
                                            <th class="px-2 py-1.5 text-center">Q3</th>
                                            <th class="px-2 py-1.5 text-center">Q4</th>
                                            <th class="px-2 py-1.5 text-left">Annual</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        @foreach($dept['staff_list'] as $si => $staff)
                                            @php
                                                $sstyle   = $scoreStyle($staff['performance']);
                                                $isMe     = strtolower(trim($staff['name']??'')) === strtolower(trim($currentUserName));
                                                $roleUpper = strtoupper(trim($staff['role'] ?? '-'));
                                                $roleColor = match($roleUpper) {
                                                    'SLT'       => 'bg-purple-100 text-purple-700',
                                                    'VP'        => 'bg-blue-100 text-blue-700',
                                                    'MANAGER'   => 'bg-indigo-100 text-indigo-700',
                                                    'EXECUTIVE' => 'bg-slate-100 text-slate-600',
                                                    default     => 'bg-slate-100 text-slate-500',
                                                };
                                            @endphp
                                            <tr class="{{ $isMe ? 'bg-indigo-50/70' : 'hover:bg-slate-50' }} transition">
                                                <td class="px-2 py-2 text-[9px] text-slate-400 font-bold">{{ $si+1 }}</td>
                                                <td class="px-2 py-2">
                                                    <div class="flex items-center gap-1.5">
                                                        <div class="w-5 h-5 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($staff['name']??'U') }}&background=0f172a&color=fff&size=20" class="w-full h-full"/>
                                                        </div>
                                                        <span class="text-[10px] font-black text-slate-900">{{ $staff['name'] ?? 'Unknown' }}@if($isMe)<span class="text-indigo-400 font-normal"> (you)</span>@endif</span>
                                                    </div>
                                                </td>
                                                <td class="px-2 py-2">
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black {{ $roleColor }}">{{ $roleUpper !== '-' ? $roleUpper : '—' }}</span>
                                                </td>
                                                <td class="px-2 py-2 text-center text-[9px] font-bold text-slate-600">{{ $staff['kpi_count'] }}</td>
                                                @foreach(['q1','q2','q3','q4'] as $qk)
                                                    @php $qst2 = $scoreStyle($staff[$qk]); @endphp
                                                    <td class="px-2 py-2 text-center">
                                                        <span class="text-[9px] font-black {{ $qst2['text'] }}">{{ $staff[$qk] > 0 ? number_format($staff[$qk],1).'%' : '—' }}</span>
                                                    </td>
                                                @endforeach
                                                <td class="px-2 py-2">
                                                    <div class="flex items-center gap-1">
                                                        <div class="w-10 h-1 bg-slate-100 rounded-full overflow-hidden">
                                                            <div class="h-1 rounded-full {{ $sstyle['bar'] }}" style="width:{{ min($staff['performance'],100) }}%"></div>
                                                        </div>
                                                        <span class="text-[9px] font-black {{ $sstyle['text'] }}">{{ number_format($staff['performance'],1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endif

{{-- ═══════ TIER 3: MY PERFORMANCE — all roles ═══════════════════════════ --}}
<div class="pt-1 border-t border-slate-200">
    <div class="flex items-center justify-between mb-3 pt-3">
        <h2 class="text-sm font-black text-slate-900">My Performance <span class="font-normal text-slate-400 text-xs">· {{ $currentFinancialYear }}</span></h2>
        <a href="{{ route('kpi.index') }}" class="px-3 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-black hover:bg-slate-800 transition">Manage KPIs →</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-3">
        {{-- Score hero --}}
        <div class="lg:col-span-2 brand-panel rounded-2xl p-4 text-white relative overflow-hidden soft-card">
            <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-white/5 -translate-y-8 translate-x-8 pointer-events-none"></div>
            <p class="text-[9px] uppercase tracking-widest font-black text-blue-300">My Performance Score</p>

            @if($individualKpiCount === 0)
                {{-- No KPIs created yet --}}
                <div class="mt-4 mb-2">
                    <p class="text-2xl font-black text-white/30">No KPIs Yet</p>
                    <p class="text-xs text-blue-200 mt-1.5">You have no KPIs for {{ $currentFinancialYear }}.</p>
                    <a href="{{ route('kpi.create') }}" class="inline-block mt-3 px-3 py-1.5 bg-white/15 hover:bg-white/25 rounded-xl text-xs font-black transition border border-white/20">+ Create First KPI</a>
                </div>
            @elseif($individualWeightage <= 0)
                {{-- Has KPIs but weightage not set --}}
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-5xl font-black leading-none text-white/40">—</span>
                    <span class="text-xl font-black text-white/30 mb-1">%</span>
                </div>
                <p class="text-xs text-blue-200 mt-2">{{ $individualKpiCount }} KPI created · weightage not set.</p>
                <a href="{{ route('weightage') }}" class="inline-block mt-1.5 text-xs font-black text-white underline">Set weightage →</a>
            @else
                {{-- Normal: has KPIs + weightage --}}
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-5xl font-black leading-none {{ $individualScoreStyle['text'] }}">{{ number_format($individualPerformance,1) }}</span>
                    <span class="text-xl font-black text-white/50 mb-1">%</span>
                </div>
                <span class="inline-block mt-1.5 px-2 py-0.5 rounded-lg text-xs font-black border {{ $individualScoreStyle['badge'] }}">{{ $individualScoreStyle['label'] }}</span>
                <div class="mt-3 h-1.5 bg-white/20 rounded-full overflow-hidden">
                    <div class="h-1.5 rounded-full {{ $individualScoreStyle['bar'] }}" style="width:{{ min($individualPerformance,100) }}%"></div>
                </div>
                <p class="text-[9px] text-blue-300/80 mt-1.5">{{ $individualKpiCount }} KPI · {{ number_format($individualWeightage,0) }}% weightage set</p>
            @endif

            <div class="mt-3 pt-3 border-t border-white/10 flex gap-2 flex-wrap">
                <a href="{{ route('kpi.index') }}"  class="px-2.5 py-1 bg-white/10 hover:bg-white/20 rounded-lg text-xs font-black transition">My KPIs</a>
                <a href="{{ route('weightage') }}"  class="px-2.5 py-1 bg-white/10 hover:bg-white/20 rounded-lg text-xs font-black transition">Weightage</a>
                @if($isManager)<a href="{{ route('kpi.my-department-kpi') }}" class="px-2.5 py-1 bg-white/10 hover:bg-white/20 rounded-lg text-xs font-black transition">Team KPIs</a>@endif
            </div>
        </div>

        {{-- Quick stats --}}
        @if($individualKpiCount === 0)
        <div class="lg:col-span-3 flex items-center justify-center bg-white rounded-2xl border border-dashed border-slate-200 p-8 soft-card">
            <div class="text-center">
                <p class="text-slate-400 text-sm font-bold">No KPI data to display</p>
                <p class="text-slate-300 text-xs mt-1">Create your KPIs to start tracking performance</p>
                <a href="{{ route('kpi.create') }}" class="inline-block mt-4 px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black hover:bg-slate-700 transition">+ Create KPI</a>
            </div>
        </div>
        @else
        <div class="lg:col-span-3 grid grid-cols-2 sm:grid-cols-4 gap-3 content-start">
            <div class="bg-white rounded-xl border border-slate-200 p-3 soft-card">
                <p class="text-[9px] uppercase font-black text-slate-400">Total KPIs</p>
                <p class="text-3xl font-black text-slate-900 mt-1">{{ $individualKpiCount }}</p>
                <p class="text-[9px] text-slate-400 mt-0.5">This year</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-100 p-3 soft-card">
                <p class="text-[9px] uppercase font-black text-emerald-600">On Track</p>
                <p class="text-3xl font-black text-emerald-700 mt-1">{{ $myOnTrack }}</p>
                <div class="mt-1.5 h-1 bg-emerald-50 rounded-full overflow-hidden">
                    <div class="h-1 bg-emerald-400 rounded-full" style="width:{{ $individualKpiCount > 0 ? round(($myOnTrack/$individualKpiCount)*100) : 0 }}%"></div>
                </div>
            </div>
            <div class="bg-white rounded-xl border {{ $myAtRisk > 0 ? 'border-red-100' : 'border-slate-200' }} p-3 soft-card">
                <p class="text-[9px] uppercase font-black {{ $myAtRisk > 0 ? 'text-red-500' : 'text-slate-400' }}">Needs Attention</p>
                <p class="text-3xl font-black {{ $myAtRisk > 0 ? 'text-red-700' : 'text-slate-400' }} mt-1">{{ $myAtRisk }}</p>
                <p class="text-[9px] mt-0.5 {{ $myAtRisk > 0 ? 'text-red-400 font-bold' : 'text-slate-400' }}">{{ $myAtRisk > 0 ? 'Review required' : 'All clear' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-blue-100 p-3 soft-card">
                <p class="text-[9px] uppercase font-black text-blue-400">Completed Quarters</p>
                <p class="text-3xl font-black text-blue-700 mt-1">{{ $myCompleted }}</p>
                <p class="text-[9px] text-blue-300 mt-0.5">of {{ $myTotalQuarters }}</p>
                <div class="mt-1 h-1 bg-blue-50 rounded-full overflow-hidden">
                    <div class="h-1 bg-blue-400 rounded-full" style="width:{{ $myTotalQuarters > 0 ? round(($myCompleted/$myTotalQuarters)*100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- My KPI cards --}}
    @if($individualKpiCount > 0)
    <div class="mt-4">
        @if($orderedCategoryGroups->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-8 text-center">
                <p class="text-slate-400 text-sm">No KPIs created yet.</p>
                <a href="{{ route('kpi.create') }}" class="mt-3 inline-block px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-black hover:bg-indigo-700">Create First KPI</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($orderedCategoryGroups as $category => $categoryKpis)
                    @php $catStyle = $categoryStyles[$category] ?? $categoryStyles['Default']; @endphp
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-black {{ $catStyle['bg'] }}">{{ $category ?: 'General' }}</span>
                            <span class="text-xs text-slate-400">{{ $categoryKpis->count() }} KPI</span>
                        </div>
                        <div class="grid grid-cols-1 xl:grid-cols-3 gap-2">
                            @foreach($categoryKpis as $kpi)
                                @php
                                    $kpiScore     = $kpi['_score'] ?? 0;
                                    $kpiWeightage = $kpi['_weightage'] ?? 0;
                                    $kpiStatus    = $kpi['status'] ?? 'not_started';
                                    $scoreSt      = $scoreStyle($kpiScore);
                                    $badgeSt      = $statusBadge($kpiStatus);
                                    $lb           = $cardBorder($kpiStatus);
                                    $quarters     = collect($kpi['quarters'] ?? []);
                                @endphp
                                <div onclick="openKpiDetail('{{ $kpi['id'] }}')"
                                     class="bg-white rounded-xl border border-l-4 border-slate-200 {{ $lb }} p-3 cursor-pointer hover:shadow-md transition-shadow soft-card group">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex flex-wrap gap-1 min-w-0">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black {{ $catStyle['sub'] }}">{{ $kpi['sub_category'] ?? '-' }}</span>
                                            @if($kpiWeightage > 0)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-slate-100 text-slate-600">{{ number_format($kpiWeightage,0) }}%</span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-50 text-amber-600 border border-amber-200">No wt</span>
                                            @endif
                                        </div>
                                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-black {{ $badgeSt['class'] }}">{{ $badgeSt['label'] }}</span>
                                    </div>
                                    <h3 class="text-xs font-black text-slate-900 leading-snug mb-1 line-clamp-2">{{ $kpi['kpi_title'] }}</h3>
                                    <p class="text-[10px] text-slate-400 line-clamp-1 mb-2">{{ $kpi['kpi_description'] ?? 'No description.' }}</p>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="flex-1">
                                            <div class="flex justify-between text-[9px] mb-0.5">
                                                <span class="text-slate-400">Achievement</span>
                                                <span class="font-black {{ $scoreSt['text'] }}">{{ number_format($kpiScore,1) }}%</span>
                                            </div>
                                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-1.5 rounded-full {{ $scoreSt['bar'] }}" style="width:{{ min($kpiScore,100) }}%"></div>
                                            </div>
                                        </div>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black border {{ $scoreSt['badge'] }} shrink-0">{{ $scoreSt['label'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @foreach(['Q1','Q2','Q3','Q4'] as $qLabel)
                                            @php
                                                $qRow    = $quarters->firstWhere('quarter',$qLabel);
                                                $qStatus = $qRow ? ($qRow['status'] ?? 'not_started') : null;
                                            @endphp
                                            <div class="flex items-center gap-1 text-[9px]">
                                                <div class="w-2 h-2 rounded-full {{ $qRow ? $qDotColor($qStatus) : 'bg-slate-200' }}"></div>
                                                <span class="font-bold {{ $qRow ? 'text-slate-700' : 'text-slate-400' }}">{{ $qLabel }}</span>
                                            </div>
                                        @endforeach
                                        <span class="ml-auto text-[9px] text-slate-300 group-hover:text-indigo-500 transition font-black">View →</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif
</div>

</div>{{-- /.p-4 --}}
</main>

{{-- ═══════ KPI DETAIL MODALS ════════════════════════════════════════════ --}}
@foreach($kpis ?? [] as $kpi)
    @php
        $modalStatus = $kpi['status'] ?? 'not_started';
        $modalStatusLabel = match($modalStatus) { 'not_started'=>'Not Started','on_track'=>'On Track','monitoring'=>'Monitoring','at_risk'=>'At Risk','risk'=>'Risk','in_trouble'=>'In Trouble','critical'=>'Critical','completed'=>'Completed',default=>'Not Started' };
        $modalStatusClass = match($modalStatus) { 'on_track','monitoring'=>'bg-blue-100 text-blue-700','at_risk','risk'=>'bg-amber-100 text-amber-700','in_trouble','critical'=>'bg-red-100 text-red-700','completed'=>'bg-emerald-100 text-emerald-700',default=>'bg-slate-100 text-slate-700' };
        $mc = ['Financial'=>['bg-emerald-700 text-white',['bg-emerald-50 text-emerald-800 border border-emerald-200']],'Growth & Customer'=>['bg-indigo-700 text-white',['bg-indigo-50 text-indigo-800 border border-indigo-200']],'Initiatives'=>['bg-amber-600 text-white',['bg-amber-50 text-amber-800 border border-amber-200']],'People'=>['bg-pink-700 text-white',['bg-pink-50 text-pink-800 border border-pink-200']],'Default'=>['bg-slate-700 text-white',['bg-slate-50 text-slate-800 border border-slate-200']]];
        $mset = $mc[$kpi['category']??'Default'] ?? $mc['Default'];
        $mCatClass = $mset[0]; $mSubClass = $mset[1][0];
        $mBase = max(0,(float)($kpi['base_target']??0));
        $mActual = max(0,(float)($kpi['actual_value']??0));
        $mAch = $mBase > 0 ? max(0,round(($mActual/$mBase)*100,2)) : 0;
        $mUnit = strtolower($kpi['unit'] ?? '');
        $mUnitLabel = match($mUnit) { 'currency'=>'Currency', 'percentage','percent'=>'Percentage', default=>'Number' };
        $mFmt = fn($v) => match($mUnit) { 'currency'=>'RM '.number_format($v,2), 'percentage','percent'=>number_format($v,2).'%', default=>number_format($v,2) };
        if      ($mAch <= 25)  $mBarColor = 'bg-red-600';
        elseif  ($mAch <= 50)  $mBarColor = 'bg-gradient-to-r from-red-600 to-orange-500';
        elseif  ($mAch <= 75)  $mBarColor = 'bg-gradient-to-r from-orange-500 to-yellow-400';
        elseif  ($mAch <= 100) $mBarColor = 'bg-gradient-to-r from-yellow-400 to-green-500';
        else                   $mBarColor = 'bg-emerald-700';
    @endphp
    <div id="kpi-modal-{{ $kpi['id'] }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4" onclick="closeKpiDetail('{{ $kpi['id'] }}')">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-4 py-3 brand-panel text-white flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-wide text-slate-400">KPI Detail</p>
                    <h3 class="text-sm font-black mt-1 leading-snug line-clamp-2">{{ $kpi['kpi_title'] ?? '-' }}</h3>
                </div>
                <button type="button" onclick="closeKpiDetail('{{ $kpi['id'] }}')" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-xs shrink-0 font-bold">✕</button>
            </div>
            <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto thin-scroll">
                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-2"><p class="text-[9px] text-slate-400 uppercase">Base Target</p><p class="text-xs font-black text-slate-900">{{ $mFmt($mBase) }}</p></div>
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-2"><p class="text-[9px] text-slate-400 uppercase">Stretch</p><p class="text-xs font-black text-slate-900">{{ $mFmt(max(0,(float)($kpi['stretch_target']??0))) }}</p></div>
                    <div class="rounded-xl bg-blue-50 border border-blue-100 p-2"><p class="text-[9px] text-blue-400 uppercase">Actual</p><p class="text-xs font-black text-blue-800">{{ $mFmt($mActual) }}</p></div>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div><p class="text-[10px] text-slate-400 uppercase">Progress</p><p class="text-sm font-black text-slate-900">{{ number_format($mAch,2) }}%</p></div>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-lg {{ $modalStatusClass }}">{{ $modalStatusLabel }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="{{ $mBarColor }} h-2 rounded-full" style="width:{{ min($mAch,100) }}%"></div></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-slate-200 p-3"><p class="text-[10px] text-slate-400 uppercase mb-1">Category</p><span class="inline-block px-2 py-1 rounded-lg text-[10px] font-bold {{ $mCatClass }}">{{ $kpi['category']??'-' }}</span></div>
                    <div class="rounded-xl border border-slate-200 p-3"><p class="text-[10px] text-slate-400 uppercase mb-1">Sub Category</p><span class="inline-block px-2 py-1 rounded-lg text-[10px] font-semibold {{ $mSubClass }}">{{ $kpi['sub_category']??'-' }}</span></div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-3"><p class="text-[10px] text-slate-400 uppercase mb-1">Description</p><p class="text-xs text-slate-700 leading-relaxed">{{ $kpi['kpi_description']??'No description.' }}</p></div>
                <div class="rounded-xl bg-amber-50 border border-amber-100 p-3"><p class="text-[10px] text-amber-500 uppercase mb-1">Remark</p><p class="text-xs text-amber-800 leading-relaxed">{{ $kpi['remark']??'No remark.' }}</p></div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-white border border-slate-100 p-3"><p class="text-[10px] text-slate-400 uppercase">Unit</p><p class="font-bold text-slate-800 mt-1 text-xs">{{ $mUnitLabel }}</p></div>
                    @php
                        $ciRaw = (!empty($kpi['updated_at']) && $kpi['updated_at'] !== ($kpi['created_at'] ?? null))
                            ? $kpi['updated_at']
                            : ($kpi['created_at'] ?? null);
                        $ciDate = $ciRaw
                            ? \Carbon\Carbon::parse($ciRaw)->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')
                            : '-';
                    @endphp
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3"><p class="text-[10px] text-slate-400 uppercase">Last Check-In</p><p class="font-bold text-slate-800 mt-1 text-[11px]">{{ $ciDate }}</p></div>
                </div>
            </div>
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeKpiDetail('{{ $kpi['id'] }}')" class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100">Close</button>
                <a href="{{ route('kpi.edit',$kpi['id']) }}" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold hover:bg-slate-800">Edit KPI</a>
            </div>
        </div>
    </div>
@endforeach

<script>
// ── DATA FROM PHP ───────────────────────────────────────────────────────────
const deptData = @json($deptChartData);
const companyRankingData = @json($companyDeptRanking ?? []);

// ── SCORE COLOR HELPER ──────────────────────────────────────────────────────
function scoreHex(v) {
    v = parseFloat(v);
    if (v <= 25)  return '#ef4444';
    if (v <= 50)  return '#f97316';
    if (v <= 75)  return '#f59e0b';
    if (v <= 100) return '#10b981';
    return '#059669';
}

const palette = ['#3b82f6','#8b5cf6','#f59e0b','#10b981','#ef4444','#06b6d4','#f97316','#ec4899','#14b8a6','#a855f7'];
const bandColors = ['#10b981','#6366f1','#f59e0b','#ef4444'];

// ── CHART: DEPT RANKING (horizontal bar — all company depts) ────────────────
(function() {
    const ctx = document.getElementById('chartDeptRanking');
    if (!ctx) return;
    const src = companyRankingData.length ? companyRankingData : deptData.map(d=>({code:d.code,score:d.annual,staff:d.staff}));
    if (!src.length) return;
    const sorted = [...src].sort((a,b) => b.score - a.score);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sorted.map(d => d.code),
            datasets: [{
                label: 'Annual Score (%)',
                data: sorted.map(d => d.score),
                backgroundColor: sorted.map(d => scoreHex(d.score) + 'cc'),
                borderColor:     sorted.map(d => scoreHex(d.score)),
                borderWidth: 1.5,
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: c => ` ${c.parsed.x.toFixed(1)}%  ·  ${src.find(d=>d.code===c.label)?.staff || 0} staff`
                    }
                }
            },
            scales: {
                x: { min: 0, max: 100, ticks: { callback: v => v+'%', font: { size: 10 } }, grid: { color: '#f1f5f9' } },
                y: { ticks: { font: { size: 11, weight: 'bold' } }, grid: { display: false } }
            }
        }
    });
})();

// ── CHART: COMPANY DONUT ────────────────────────────────────────────────────
(function() {
    const ctx = document.getElementById('chartCompanyDonut');
    if (!ctx) return;
    const bands = @json($myDeptBands);
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Excellent ≥90%', 'Good 75–89%', 'Watch 50–74%', 'Critical <50%'],
            datasets: [{ data: bands, backgroundColor: bandColors, borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            cutout: '70%',
            responsive: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed} staff` } } }
        }
    });
})();

// ── CHART: QUARTERLY TREND (grouped bar) ────────────────────────────────────
(function() {
    const ctx = document.getElementById('chartQuarterTrend');
    if (!ctx || !deptData.length) return;
    const datasets = deptData.map((d, i) => ({
        label: d.code,
        data: [d.q1, d.q2, d.q3, d.q4],
        backgroundColor: palette[i % palette.length] + 'bb',
        borderColor:     palette[i % palette.length],
        borderWidth: 1.5,
        borderRadius: 4,
    }));
    new Chart(ctx, {
        type: 'bar',
        data: { labels: ['Q1', 'Q2', 'Q3', 'Q4'], datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12 } },
                tooltip: { callbacks: { label: c => ` ${c.dataset.label}: ${c.parsed.y.toFixed(1)}%` } }
            },
            scales: {
                x: { ticks: { font: { size: 11, weight: 'bold' } }, grid: { display: false } },
                y: { min: 0, max: 100, ticks: { callback: v => v+'%', font: { size: 10 } }, grid: { color: '#f1f5f9' } }
            }
        }
    });
})();

// ── CHARTS: PER-DEPT DONUT + MINI RING ──────────────────────────────────────
deptData.forEach(function(dept) {
    const safe = dept.code.replace(/[^A-Za-z0-9]/g, '_');

    // Mini ring in accordion header (40×40)
    const ringCtx = document.getElementById('ring-' + safe);
    if (ringCtx) {
        new Chart(ringCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [dept.annual, Math.max(0, 100 - dept.annual)],
                    backgroundColor: [scoreHex(dept.annual), '#f1f5f9'],
                    borderWidth: 0,
                }]
            },
            options: { cutout: '68%', responsive: false, plugins: { legend: { display:false }, tooltip: { enabled:false } }, events: [] }
        });
    }


});

// ── ACCORDION TOGGLE ────────────────────────────────────────────────────────
let allOpen = false;

function toggleDept(safe) {
    const body = document.getElementById('dept-body-' + safe);
    const chev = document.getElementById('chev-' + safe);
    if (!body) return;
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open',   !isOpen);
    body.classList.toggle('closed',  isOpen);
    if (chev) chev.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(-90deg)';
}

function toggleAllDepts() {
    const btn = document.getElementById('toggleAllBtn');
    allOpen = !allOpen;
    deptData.forEach(function(dept) {
        const safe = dept.code.replace(/[^A-Za-z0-9]/g, '_');
        const body = document.getElementById('dept-body-' + safe);
        const chev = document.getElementById('chev-' + safe);
        if (body) { body.classList.toggle('open', allOpen); body.classList.toggle('closed', !allOpen); }
        if (chev)  chev.style.transform = allOpen ? '' : 'rotate(-90deg)';
    });
    if (btn) btn.textContent = allOpen ? 'Collapse All' : 'Expand All';
}

// ── MODAL HELPERS ───────────────────────────────────────────────────────────
function openKpiDetail(id) {
    const m = document.getElementById('kpi-modal-' + id);
    if (!m) return;
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}
function closeKpiDetail(id) {
    const m = document.getElementById('kpi-modal-' + id);
    if (!m) return;
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}
function openQuarterModal(id)  { const m = document.getElementById('quarter-modal-'+id); if(m){ m.classList.remove('hidden'); m.classList.add('flex'); document.body.classList.add('overflow-hidden'); } }
function closeQuarterModal(id) { const m = document.getElementById('quarter-modal-'+id); if(m){ m.classList.add('hidden');    m.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); } }
function openHistoryModal(id)  { const m = document.getElementById('history-modal-'+id); if(m){ m.classList.remove('hidden'); m.classList.add('flex'); document.body.classList.add('overflow-hidden'); } }
function closeHistoryModal(id) { const m = document.getElementById('history-modal-'+id); if(m){ m.classList.add('hidden');    m.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); } }
</script>

</body>
</html>
