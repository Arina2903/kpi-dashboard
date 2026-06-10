<!DOCTYPE html>
<html>
<head>
    <title>My Department KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass { background: rgba(255,255,255,.92); backdrop-filter: blur(14px); }
        .card-hover { transition: .2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 18px 35px rgba(15,23,42,.09); }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .drawer-slide { transition: transform .3s cubic-bezier(.4,0,.2,1); }
    </style>
</head>

<body class="min-h-screen bg-[#f4f7fb]">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300 bg-[#f4f7fb]">
<div class="p-6 space-y-5">

    {{-- HEADER --}}
    <div class="rounded-[20px] bg-gradient-to-r from-[#06142f] via-blue-900 to-indigo-900 text-white p-7 shadow-xl">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <div>
                <a href="/dashboard" class="text-xs text-blue-200 hover:text-white font-semibold">← Dashboard</a>
                <h1 class="text-3xl font-black mt-3 tracking-tight">My Department KPI</h1>
                <p class="text-blue-200 text-xs mt-2 font-medium">
                    {{ $user['department_code'] ?? '-' }} · {{ $user['role'] ?? '-' }} · {{ $fy }}
                </p>
            </div>
            <div class="flex gap-3">
                <div class="bg-white/10 rounded-2xl px-5 py-3 text-center min-w-[80px]">
                    <p class="text-[9px] text-blue-200 uppercase font-black tracking-wider">Staff</p>
                    <h3 class="text-2xl font-black mt-1">{{ count($employees ?? []) }}</h3>
                </div>
                <div class="bg-white/10 rounded-2xl px-5 py-3 text-center min-w-[80px]">
                    <p class="text-[9px] text-blue-200 uppercase font-black tracking-wider">Total KPI</p>
                    <h3 class="text-2xl font-black mt-1">{{ count($kpis ?? []) }}</h3>
                </div>
                <div class="bg-white/10 rounded-2xl px-5 py-3 text-center min-w-[80px]">
                    <p class="text-[9px] text-blue-200 uppercase font-black tracking-wider">FY</p>
                    <h3 class="text-2xl font-black mt-1">{{ $fy }}</h3>
                </div>
            </div>
        </div>
    </div>

    @php
        $departmentPerformance = collect($kpis ?? [])->sum(function($item){
            $quarters = collect($item['quarters'] ?? []);
            $target = $quarters->sum(fn($q) => (float)($q['quarter_target'] ?? 0));
            $actual = $quarters->sum(fn($q) => (float)($q['quarter_actual'] ?? 0));
            $score = $target > 0 ? (($actual / $target) * 100) : 0;
            return ($score * ((float)($item['weightage'] ?? 0))) / 100;
        });

        $deptPerfColor = match(true) {
            $departmentPerformance >= 90 => ['bar' => 'from-emerald-400 to-green-500', 'text' => 'text-emerald-400', 'label' => 'Excellent', 'badge' => 'bg-emerald-500/20 text-emerald-300'],
            $departmentPerformance >= 75 => ['bar' => 'from-blue-400 to-indigo-500',  'text' => 'text-blue-300',    'label' => 'Good',      'badge' => 'bg-blue-500/20 text-blue-200'],
            $departmentPerformance >= 50 => ['bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-yellow-300', 'label' => 'Watch',     'badge' => 'bg-yellow-500/20 text-yellow-200'],
            default                      => ['bar' => 'from-red-400 to-rose-500',     'text' => 'text-red-300',    'label' => 'Critical',  'badge' => 'bg-red-500/20 text-red-300'],
        };

        $statusCounts = collect($kpis ?? [])->countBy(fn($k) => $k['status'] ?? 'not_started');

        $staffGroupedKpis = collect($kpis)
            ->groupBy(fn($item) => $item['employee_name'] ?? 'Unknown')
            ->sortByDesc(function($group){
                $perf = 0;
                foreach($group as $item){
                    $quarters = collect($item['quarters'] ?? []);
                    $target = $quarters->sum(fn($q) => (float)($q['quarter_target'] ?? 0));
                    $actual = $quarters->sum(fn($q) => (float)($q['quarter_actual'] ?? 0));
                    $score = $target > 0 ? (($actual / $target) * 100) : 0;
                    $perf += ($score * ((float)($item['weightage'] ?? 0))) / 100;
                }
                return $perf;
            });
    @endphp

    {{-- DEPT PERFORMANCE + LEGEND --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- DEPT SCORE --}}
        <div class="glass rounded-[20px] border border-white/70 p-6 shadow-sm flex flex-col justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Department Performance</p>
            <div class="flex items-end justify-between mt-3 gap-3">
                <h2 class="text-5xl font-black text-slate-900">{{ number_format($departmentPerformance, 1) }}<span class="text-2xl text-slate-400">%</span></h2>
                <span class="text-xs font-black px-3 py-1.5 rounded-xl
                    {{ $departmentPerformance >= 90 ? 'bg-emerald-100 text-emerald-700' : ($departmentPerformance >= 75 ? 'bg-blue-100 text-blue-700' : ($departmentPerformance >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                    {{ $deptPerfColor['label'] }}
                </span>
            </div>
            <div class="mt-4 h-3 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-3 rounded-full bg-gradient-to-r {{ $deptPerfColor['bar'] }}" style="width: {{ min(100, max(2, $departmentPerformance)) }}%"></div>
            </div>
            <p class="text-[9px] text-slate-400 mt-2 font-medium">Weighted average of all KPI achievements</p>
        </div>

        {{-- KPI STATUS BREAKDOWN --}}
        <div class="glass rounded-[20px] border border-white/70 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">KPI Status Breakdown</p>
                <span class="text-[9px] text-slate-400 font-medium">— set by each staff</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                @php
                    $statusDef = [
                        'completed'   => ['label' => 'Completed',   'color' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'],
                        'on_track'    => ['label' => 'On Track',     'color' => 'bg-blue-100 text-blue-700',      'dot' => 'bg-blue-500'],
                        'at_risk'     => ['label' => 'At Risk',      'color' => 'bg-yellow-100 text-yellow-700',  'dot' => 'bg-yellow-500'],
                        'in_trouble'  => ['label' => 'In Trouble',   'color' => 'bg-red-100 text-red-700',        'dot' => 'bg-red-500'],
                        'not_started' => ['label' => 'Not Started',  'color' => 'bg-slate-100 text-slate-500',    'dot' => 'bg-slate-400'],
                    ];
                @endphp
                @foreach($statusDef as $key => $def)
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl {{ $def['color'] }}">
                    <span class="w-2 h-2 rounded-full {{ $def['dot'] }} shrink-0"></span>
                    <span class="text-[10px] font-black">{{ $def['label'] }}</span>
                    <span class="ml-auto text-sm font-black">{{ $statusCounts[$key] ?? 0 }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- PERFORMANCE ACHIEVEMENT GUIDE --}}
        <div class="glass rounded-[20px] border border-white/70 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-6 h-6 rounded-lg bg-slate-800 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Performance Achievement Guide</p>
                    <p class="text-[9px] text-slate-400 mt-0.5">Actual ÷ Target × 100% — how much of the KPI target is met</p>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-100">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span><span class="text-[11px] font-black text-emerald-700">Excellent</span></div>
                    <span class="text-[11px] font-black text-emerald-600">≥ 90%</span>
                </div>
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-blue-50 border border-blue-100">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span><span class="text-[11px] font-black text-blue-700">Good</span></div>
                    <span class="text-[11px] font-black text-blue-600">75 – 89%</span>
                </div>
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-yellow-50 border border-yellow-100">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500 shrink-0"></span><span class="text-[11px] font-black text-yellow-700">Watch</span></div>
                    <span class="text-[11px] font-black text-yellow-600">50 – 74%</span>
                </div>
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-red-50 border border-red-100">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span><span class="text-[11px] font-black text-red-700">Critical</span></div>
                    <span class="text-[11px] font-black text-red-600">< 50%</span>
                </div>
            </div>
        </div>

    </div>

    {{-- FILTER --}}
    <div class="sticky top-4 z-40 glass rounded-[18px] shadow-sm border border-white/70 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Search</label>
                <input id="searchInput" type="text" placeholder="Name, KPI title, category..."
                       class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Category</label>
                <select id="categoryFilter" class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
                    <option value="">All Categories</option>
                    <option value="Financial">Financial</option>
                    <option value="Growth & Customer">Growth & Customer</option>
                    <option value="Initiatives">Initiatives</option>
                    <option value="People">People</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">KPI Status</label>
                <select id="statusFilter" class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
                    <option value="">All Statuses</option>
                    <option value="not_started">Not Started</option>
                    <option value="on_track">On Track</option>
                    <option value="at_risk">At Risk</option>
                    <option value="in_trouble">In Trouble</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="bg-slate-900 text-white rounded-2xl p-4 flex items-center justify-between">
                <p class="text-xs text-blue-200 font-semibold">Visible KPI</p>
                <p id="visibleCount" class="text-2xl font-black">{{ count($kpis ?? []) }}</p>
            </div>
        </div>
    </div>

    {{-- STAFF LIST --}}
    <div class="space-y-3">

        @foreach($staffGroupedKpis as $staffName => $staffKpis)

        @php
            $staffPerformance = 0;
            $excellentCount = 0;
            $goodCount = 0;
            $watchCount = 0;
            $criticalCount = 0;

            $staffStatusCounts = [];
            foreach($staffKpis as $sk){
                $q = collect($sk['quarters'] ?? []);
                $t = $q->sum(fn($x) => (float)($x['quarter_target'] ?? 0));
                $a = $q->sum(fn($x) => (float)($x['quarter_actual'] ?? 0));
                $score = $t > 0 ? (($a / $t) * 100) : 0;
                $staffPerformance += ($score * (float)($sk['weightage'] ?? 0)) / 100;

                if($score >= 90)      $excellentCount++;
                elseif($score >= 75)  $goodCount++;
                elseif($score >= 50)  $watchCount++;
                else                  $criticalCount++;

                $st = $sk['status'] ?? 'not_started';
                $staffStatusCounts[$st] = ($staffStatusCounts[$st] ?? 0) + 1;
            }

            $spColor = match(true) {
                $staffPerformance >= 90 => ['bar' => 'from-emerald-400 to-green-500', 'text' => 'text-emerald-700', 'label' => 'Excellent', 'badge' => 'bg-emerald-100 text-emerald-700'],
                $staffPerformance >= 75 => ['bar' => 'from-blue-400 to-indigo-500',  'text' => 'text-blue-700',    'label' => 'Good',      'badge' => 'bg-blue-100 text-blue-700'],
                $staffPerformance >= 50 => ['bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-yellow-700', 'label' => 'Watch',     'badge' => 'bg-yellow-100 text-yellow-700'],
                default                 => ['bar' => 'from-red-400 to-rose-500',     'text' => 'text-red-700',    'label' => 'Critical',  'badge' => 'bg-red-100 text-red-700'],
            };

            $staffRole = collect($staffKpis)->first()['employee_role'] ?? '';
            $initials  = collect(explode(' ', strtoupper($staffName)))->filter()->map(fn($p)=>$p[0])->take(2)->join('');
        @endphp

        <div class="glass rounded-[20px] border border-white/70 overflow-hidden shadow-sm staff-block"
             data-staff="{{ strtolower($staffName) }}">

            {{-- STAFF HEADER --}}
            <button onclick="toggleStaff('{{ Str::slug($staffName) }}')"
                    class="w-full p-5 text-left hover:bg-slate-50/80 transition">

                <div class="flex flex-col xl:flex-row xl:items-center gap-4">

                    {{-- AVATAR + INFO --}}
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#06142f] to-blue-700 flex items-center justify-center text-white font-black text-lg shrink-0">
                            {{ $initials ?: '?' }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-lg font-black text-slate-900">{{ strtoupper($staffName) }}</h2>
                                @if($staffRole)
                                <span class="text-[9px] font-black px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500 uppercase">{{ $staffRole }}</span>
                                @endif
                            </div>
                            {{-- KPI STATUS ROW --}}
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span class="text-[9px] font-black text-slate-400 uppercase self-center mr-1">Status:</span>
                                @foreach($statusDef as $sKey => $sDef)
                                @if(($staffStatusCounts[$sKey] ?? 0) > 0)
                                <span class="flex items-center gap-1 px-2 py-0.5 rounded-lg {{ $sDef['color'] }} text-[10px] font-black">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sDef['dot'] }}"></span>
                                    {{ $sDef['label'] }} {{ $staffStatusCounts[$sKey] }}
                                </span>
                                @endif
                                @endforeach
                                <span class="text-[9px] text-slate-300 self-center">·</span>
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-black">{{ count($staffKpis) }} KPIs</span>
                            </div>
                        </div>
                    </div>

                    {{-- ACHIEVEMENT BREAKDOWN --}}
                    <div class="flex flex-wrap gap-1.5 xl:w-[320px]">
                        <span class="text-[9px] font-black text-slate-400 uppercase self-center w-full xl:w-auto xl:mr-1">Achievement:</span>
                        @if($excellentCount > 0)
                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-[10px] font-black border border-emerald-100">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>Excellent {{ $excellentCount }}
                        </span>
                        @endif
                        @if($goodCount > 0)
                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 text-[10px] font-black border border-blue-100">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>Good {{ $goodCount }}
                        </span>
                        @endif
                        @if($watchCount > 0)
                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-yellow-50 text-yellow-700 text-[10px] font-black border border-yellow-100">
                            <span class="w-2 h-2 rounded-full bg-yellow-500"></span>Watch {{ $watchCount }}
                        </span>
                        @endif
                        @if($criticalCount > 0)
                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-red-50 text-red-700 text-[10px] font-black border border-red-100">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>Critical {{ $criticalCount }}
                        </span>
                        @endif
                    </div>

                    {{-- PERFORMANCE SCORE --}}
                    <div class="xl:w-[200px] shrink-0">
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="text-[9px] uppercase font-black text-slate-400 tracking-wider">Performance Achievement</p>
                            <span class="text-xs font-black px-2 py-0.5 rounded-lg {{ $spColor['badge'] }}">{{ $spColor['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-2.5 rounded-full bg-gradient-to-r {{ $spColor['bar'] }}" style="width: {{ min(100, max(2, $staffPerformance)) }}%"></div>
                            </div>
                            <span class="text-sm font-black {{ $spColor['text'] }} shrink-0">{{ number_format($staffPerformance, 1) }}%</span>
                        </div>
                    </div>

                </div>
            </button>

            {{-- STAFF KPI CARDS --}}
            <div id="staff-{{ Str::slug($staffName) }}"
                 class="hidden border-t border-slate-100 bg-slate-50/60 p-4 space-y-3">

                @foreach($staffKpis as $kpi)
                @php
                    $kq = collect($kpi['quarters'] ?? []);
                    $kt = $kq->sum(fn($q) => (float)($q['quarter_target'] ?? 0));
                    $ka = $kq->sum(fn($q) => (float)($q['quarter_actual'] ?? 0));
                    $kachv = $kt > 0 ? (($ka / $kt) * 100) : 0;

                    $kColor = match(true) {
                        $kachv >= 90 => ['bar' => 'from-emerald-400 to-green-500', 'text' => 'text-emerald-700', 'label' => 'Excellent', 'badge' => 'bg-emerald-100 text-emerald-700', 'border' => 'border-l-emerald-400'],
                        $kachv >= 75 => ['bar' => 'from-blue-400 to-indigo-500',  'text' => 'text-blue-700',    'label' => 'Good',      'badge' => 'bg-blue-100 text-blue-700',       'border' => 'border-l-blue-400'],
                        $kachv >= 50 => ['bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-yellow-700', 'label' => 'Watch',     'badge' => 'bg-yellow-100 text-yellow-700',   'border' => 'border-l-yellow-400'],
                        default      => ['bar' => 'from-red-400 to-rose-500',     'text' => 'text-red-700',    'label' => 'Critical',  'badge' => 'bg-red-100 text-red-700',         'border' => 'border-l-red-500'],
                    };

                    $kStatus     = $kpi['status'] ?? 'not_started';
                    $kStatusDef  = $statusDef[$kStatus] ?? $statusDef['not_started'];
                @endphp

                <div onclick="openKpiDrawer(this)"
                     class="kpi-card cursor-pointer bg-white border border-slate-200 border-l-4 {{ $kColor['border'] }} rounded-[16px] p-5 hover:shadow-md transition"
                     data-search="{{ strtolower(($kpi['kpi_title'] ?? '') . ' ' . ($staffName ?? '') . ' ' . ($kpi['category'] ?? '')) }}"
                     data-category="{{ $kpi['category'] ?? '' }}"
                     data-status="{{ $kStatus }}"
                     data-kpi='@json($kpi)'>

                    <div class="flex flex-col xl:flex-row xl:items-center gap-4">

                        {{-- LEFT --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap gap-2 mb-2">
                                <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black">{{ $kpi['category'] ?? '-' }}</span>
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black">{{ $kpi['sub_category'] ?? '-' }}</span>
                                {{-- STATUS BADGE --}}
                                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $kStatusDef['color'] }} text-[10px] font-black">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $kStatusDef['dot'] }}"></span>
                                    {{ $kStatusDef['label'] }}
                                </span>
                            </div>
                            <h3 class="text-sm font-black text-slate-900 leading-snug">{{ $kpi['kpi_title'] ?? '-' }}</h3>
                            <p class="text-xs text-slate-400 mt-1 line-clamp-1">{{ $kpi['kpi_description'] ?? '-' }}</p>
                        </div>

                        {{-- QUARTER DOTS --}}
                        <div class="flex gap-1.5 xl:w-auto shrink-0">
                            @foreach(['Q1','Q2','Q3','Q4'] as $qn)
                            @php
                                $qd  = $kq->firstWhere('quarter', $qn) ?? [];
                                $qkt = (float)($qd['quarter_target'] ?? 0);
                                $qka = (float)($qd['quarter_actual'] ?? 0);
                                $qks = $qkt > 0 ? ($qka / $qkt) * 100 : 0;
                                $qDot = match(true) {
                                    $qks >= 90 => 'bg-emerald-500 text-white',
                                    $qks >= 75 => 'bg-blue-500 text-white',
                                    $qks >= 50 => 'bg-yellow-400 text-white',
                                    $qks > 0   => 'bg-red-500 text-white',
                                    default    => 'bg-slate-200 text-slate-400',
                                };
                            @endphp
                            <div class="w-8 h-8 rounded-xl {{ $qDot }} flex items-center justify-center text-[9px] font-black">{{ $qn }}</div>
                            @endforeach
                        </div>

                        {{-- ACHIEVEMENT --}}
                        <div class="xl:w-[180px] shrink-0">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-[9px] uppercase font-black text-slate-400">Achievement</p>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-lg {{ $kColor['badge'] }}">{{ $kColor['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-2 rounded-full bg-gradient-to-r {{ $kColor['bar'] }}" style="width: {{ min(100, max(2, $kachv)) }}%"></div>
                                </div>
                                <span class="text-xs font-black {{ $kColor['text'] }} shrink-0">{{ number_format($kachv, 1) }}%</span>
                            </div>
                        </div>

                    </div>
                </div>
                @endforeach

            </div>
        </div>

        @endforeach

    </div>

</div>
</main>

{{-- KPI DRAWER --}}
<div id="kpiDrawer" class="hidden fixed inset-0 z-[9999]">
    <div onclick="closeKpiDrawer()" class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"></div>
    <div id="kpiDrawerContent"
         class="drawer-slide absolute right-0 top-0 h-full w-full max-w-[950px] bg-[#f8fafc] overflow-y-auto shadow-2xl">
    </div>
</div>

<script>

const statusLabels = {
    'completed':   { label: 'Completed',  color: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
    'on_track':    { label: 'On Track',   color: 'bg-blue-100 text-blue-700',       dot: 'bg-blue-500' },
    'at_risk':     { label: 'At Risk',    color: 'bg-yellow-100 text-yellow-700',   dot: 'bg-yellow-500' },
    'in_trouble':  { label: 'In Trouble', color: 'bg-red-100 text-red-700',         dot: 'bg-red-500' },
    'not_started': { label: 'Not Started',color: 'bg-slate-100 text-slate-500',     dot: 'bg-slate-400' },
};

function achvBadge(score){
    if(score >= 90) return { label: 'Excellent', color: 'bg-emerald-100 text-emerald-700', bar: 'from-emerald-400 to-green-500' };
    if(score >= 75) return { label: 'Good',      color: 'bg-blue-100 text-blue-700',       bar: 'from-blue-400 to-indigo-500' };
    if(score >= 50) return { label: 'Watch',     color: 'bg-yellow-100 text-yellow-700',   bar: 'from-yellow-400 to-amber-500' };
    return           { label: 'Critical',  color: 'bg-red-100 text-red-700',         bar: 'from-red-400 to-rose-500' };
}

function toggleStaff(id){
    const el = document.getElementById('staff-' + id);
    if(el) el.classList.toggle('hidden');
}

const searchInput   = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const statusFilter  = document.getElementById('statusFilter');
const visibleCount  = document.getElementById('visibleCount');

function filterRows(){
    const search   = searchInput.value.toLowerCase();
    const category = categoryFilter.value;
    const status   = statusFilter.value;
    let visible    = 0;

    document.querySelectorAll('.kpi-card').forEach(card => {
        const ok = (!search   || (card.dataset.search || '').includes(search))
                && (!category || card.dataset.category === category)
                && (!status   || card.dataset.status === status);
        card.classList.toggle('hidden', !ok);
        if(ok) visible++;
    });
    visibleCount.innerText = visible;
}

searchInput.addEventListener('input', filterRows);
categoryFilter.addEventListener('change', filterRows);
statusFilter.addEventListener('change', filterRows);

function openKpiDrawer(card){
    const drawer  = document.getElementById('kpiDrawer');
    const content = document.getElementById('kpiDrawerContent');
    if(!drawer || !content || !card) return;

    const kpi      = JSON.parse(card.dataset.kpi || '{}');
    const quarters = kpi.quarters || [];
    const kStatus  = kpi.status || 'not_started';
    const sDef     = statusLabels[kStatus] || statusLabels['not_started'];

    let totalTarget = 0, totalActual = 0;
    quarters.forEach(q => {
        totalTarget += parseFloat(q.quarter_target || 0);
        totalActual += parseFloat(q.quarter_actual || 0);
    });
    const overallScore = totalTarget > 0 ? (totalActual / totalTarget) * 100 : 0;
    const aBadge = achvBadge(overallScore);

    let quarterHtml = '';
    ['Q1','Q2','Q3','Q4'].forEach(q => {
        const qd     = quarters.find(x => x.quarter === q) || {};
        const target = parseFloat(qd.quarter_target || 0);
        const actual = parseFloat(qd.quarter_actual || 0);
        const score  = target > 0 ? (actual / target) * 100 : 0;
        const qb     = achvBadge(score);
        quarterHtml += `
        <div class="bg-white rounded-[20px] border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-sm">${q}</div>
                    <div>
                        <p class="text-xs font-black text-slate-700">${qd.quarter_title || 'Quarter KPI'}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">${qd.start_date || ''} ${qd.end_date ? '→ '+qd.end_date : ''}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[9px] uppercase text-slate-400 font-black">Achievement</p>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg ${qb.color} text-xs font-black mt-1">${qb.label}</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">${score.toFixed(1)}%</h3>
                </div>
            </div>
            <div class="h-2 bg-slate-100 rounded-full overflow-hidden mb-4">
                <div class="h-2 rounded-full bg-gradient-to-r ${qb.bar}" style="width:${Math.min(100,Math.max(2,score))}%"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-[9px] uppercase text-slate-400 font-black">Target</p>
                    <h3 class="text-xl font-black text-slate-900 mt-1">${target.toLocaleString()}</h3>
                </div>
                <div class="bg-emerald-50 rounded-xl p-4">
                    <p class="text-[9px] uppercase text-emerald-600 font-black">Actual</p>
                    <h3 class="text-xl font-black text-emerald-700 mt-1">${actual.toLocaleString()}</h3>
                </div>
            </div>
        </div>`;
    });

    content.innerHTML = `
    <div class="min-h-screen bg-[#f8fafc]">
        <div class="sticky top-0 z-20 bg-white border-b border-slate-100 p-6">
            <div class="flex items-start justify-between gap-5">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black">${kpi.category || '-'}</span>
                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black">${kpi.sub_category || '-'}</span>
                        <span class="flex items-center gap-1.5 px-3 py-1 rounded-full ${sDef.color} text-[10px] font-black">
                            <span class="w-1.5 h-1.5 rounded-full ${sDef.dot}"></span>KPI Status: ${sDef.label}
                        </span>
                        <span class="px-3 py-1 rounded-full ${aBadge.color} text-[10px] font-black">Achievement: ${aBadge.label} (${overallScore.toFixed(1)}%)</span>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 leading-tight">${kpi.kpi_title || '-'}</h2>
                    <p class="text-sm text-slate-400 mt-2 leading-relaxed">${kpi.kpi_description || '-'}</p>
                </div>
                <button onclick="closeKpiDrawer()" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-lg shrink-0">×</button>
            </div>
            <div class="mt-4 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-2 rounded-full bg-gradient-to-r ${aBadge.bar}" style="width:${Math.min(100,Math.max(2,overallScore))}%"></div>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 xl:grid-cols-12 gap-5">
            <div class="xl:col-span-8 space-y-4">${quarterHtml}</div>
            <div class="xl:col-span-4 space-y-4">
                <div class="rounded-[20px] overflow-hidden bg-gradient-to-br from-[#06142f] via-blue-900 to-indigo-900 text-white shadow-xl p-5">
                    <p class="text-[9px] uppercase text-blue-200 font-black tracking-widest">KPI Owner</p>
                    <h2 class="text-xl font-black mt-2">${kpi.employee_name || '-'}</h2>
                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <div class="bg-white/10 rounded-xl p-4">
                            <p class="text-[9px] uppercase text-blue-200 font-black">Weightage</p>
                            <h3 class="text-2xl font-black mt-1">${kpi.weightage || 0}%</h3>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4">
                            <p class="text-[9px] uppercase text-blue-200 font-black">KPI Status</p>
                            <span class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-lg ${sDef.color} text-xs font-black">
                                <span class="w-1.5 h-1.5 rounded-full ${sDef.dot}"></span>${sDef.label}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[20px] border border-slate-100 p-5">
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-wide">KPI Summary</h3>
                    <div class="mt-4 space-y-3">
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                            <p class="text-[10px] text-slate-400 font-black uppercase">Base Target</p>
                            <h3 class="text-lg font-black text-slate-900">${Number(kpi.base_target || 0).toLocaleString()}</h3>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-indigo-50 rounded-xl">
                            <p class="text-[10px] text-indigo-400 font-black uppercase">Stretch Target</p>
                            <h3 class="text-lg font-black text-indigo-700">${Number(kpi.stretch_target || 0).toLocaleString()}</h3>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl">
                            <p class="text-[10px] text-emerald-600 font-black uppercase">Total Actual</p>
                            <h3 class="text-lg font-black text-emerald-700">${totalActual.toLocaleString()}</h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[20px] border border-slate-100 p-5">
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-wide mb-3">Achievement Guide</h3>
                    <div class="space-y-2 text-[11px]">
                        <div class="flex justify-between px-3 py-1.5 rounded-xl bg-emerald-50"><span class="font-black text-emerald-700">● Excellent</span><span class="text-emerald-600 font-bold">≥ 90%</span></div>
                        <div class="flex justify-between px-3 py-1.5 rounded-xl bg-blue-50"><span class="font-black text-blue-700">● Good</span><span class="text-blue-600 font-bold">75 – 89%</span></div>
                        <div class="flex justify-between px-3 py-1.5 rounded-xl bg-yellow-50"><span class="font-black text-yellow-700">● Watch</span><span class="text-yellow-600 font-bold">50 – 74%</span></div>
                        <div class="flex justify-between px-3 py-1.5 rounded-xl bg-red-50"><span class="font-black text-red-700">● Critical</span><span class="text-red-600 font-bold">< 50%</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;

    drawer.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeKpiDrawer(){
    const drawer = document.getElementById('kpiDrawer');
    if(drawer) drawer.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

</script>

</body>
</html>
