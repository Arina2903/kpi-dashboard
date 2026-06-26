<!DOCTYPE html>
<html>
<head>
    <title>My Department KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass { background: rgba(255,255,255,.92); backdrop-filter: blur(14px); }
        .card-hover { transition: .2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 18px 35px rgba(15,23,42,.09); }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #6B3F2A; box-shadow: 0 0 0 3px rgba(107,63,42,.12); }
        .drawer-slide { transition: transform .3s cubic-bezier(.4,0,.2,1); }
        .cat-section { animation: fadeIn .2s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }
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
                <p class="text-white/60 text-xs mt-2 font-medium">
                    {{ $user['department_code'] ?? '-' }} · {{ $user['role'] ?? '-' }} · {{ $fy }}
                </p>
            </div>
            <div class="flex flex-wrap gap-3 items-center">
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
                @if(session('company_code') === 'RCG' && ($user['department_code'] ?? '') === 'TITAN')
                <button
                    id="openWizardBtn"
                    onclick="openWizard()"
                    class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-black px-4 py-3 rounded-2xl shadow-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    KPI Template Wizard
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- EXECUTIVE GUIDANCE BANNER --}}
    @if(($user['role'] ?? '') === 'EXECUTIVE' && count($kpis) > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-4 flex gap-3 items-start">
        <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="text-[12px] font-bold text-blue-800">Your KPIs have been set up for you</p>
            <p class="text-[11px] text-blue-600 mt-0.5">Your manager has created your KPI targets. For each KPI below, click <strong>Edit</strong> to fill in your quarterly targets (Q1–Q4) and update your actual achievement each quarter.</p>
        </div>
    </div>
    @endif

    @php
        $categoryOrder = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];

        $catThemes = [
            'Financial'         => [
                'icon'       => '💰',
                'headerBg'   => 'from-emerald-800 to-emerald-600',
                'headerText' => 'text-emerald-100',
                'border'     => 'border-l-emerald-500',
                'sectionBg'  => 'bg-emerald-50/60',
                'catPill'    => 'bg-emerald-700 text-white',
                'subPill'    => 'bg-emerald-100 text-emerald-700',
                'countBadge' => 'bg-emerald-900/40 text-emerald-100',
                'infoBg'     => 'bg-emerald-50',
                'divider'    => 'border-emerald-100',
            ],
            'Growth & Customer' => [
                'icon'       => '📈',
                'headerBg'   => 'from-indigo-800 to-indigo-600',
                'headerText' => 'text-indigo-100',
                'border'     => 'border-l-indigo-500',
                'sectionBg'  => 'bg-indigo-50/60',
                'catPill'    => 'bg-indigo-700 text-white',
                'subPill'    => 'bg-indigo-100 text-indigo-700',
                'countBadge' => 'bg-indigo-900/40 text-indigo-100',
                'infoBg'     => 'bg-indigo-50',
                'divider'    => 'border-indigo-100',
            ],
            'Initiatives'       => [
                'icon'       => '🚀',
                'headerBg'   => 'from-amber-700 to-amber-500',
                'headerText' => 'text-amber-100',
                'border'     => 'border-l-amber-500',
                'sectionBg'  => 'bg-amber-50/60',
                'catPill'    => 'bg-amber-600 text-white',
                'subPill'    => 'bg-amber-100 text-amber-700',
                'countBadge' => 'bg-amber-900/40 text-amber-100',
                'infoBg'     => 'bg-amber-50',
                'divider'    => 'border-amber-100',
            ],
            'People'            => [
                'icon'       => '👥',
                'headerBg'   => 'from-pink-800 to-pink-600',
                'headerText' => 'text-pink-100',
                'border'     => 'border-l-pink-500',
                'sectionBg'  => 'bg-pink-50/60',
                'catPill'    => 'bg-pink-700 text-white',
                'subPill'    => 'bg-pink-100 text-pink-700',
                'countBadge' => 'bg-pink-900/40 text-pink-100',
                'infoBg'     => 'bg-pink-50',
                'divider'    => 'border-pink-100',
            ],
        ];
        $catThemeDefault = [
            'icon'       => '📌',
            'headerBg'   => 'from-slate-700 to-slate-600',
            'headerText' => 'text-slate-100',
            'border'     => 'border-l-slate-400',
            'sectionBg'  => 'bg-slate-50/60',
            'catPill'    => 'bg-slate-600 text-white',
            'subPill'    => 'bg-slate-100 text-slate-600',
            'countBadge' => 'bg-slate-900/40 text-slate-100',
            'infoBg'     => 'bg-slate-50',
            'divider'    => 'border-slate-100',
        ];

        $departmentPerformance = collect($kpis ?? [])->sum(function($item){
            $quarters = collect($item['quarters'] ?? []);
            $target = $quarters->sum(fn($q) => (float)($q['quarter_target'] ?? 0));
            $actual = $quarters->sum(fn($q) => (float)($q['quarter_actual'] ?? 0));
            $score = $target > 0 ? (($actual / $target) * 100) : 0;
            return ($score * ((float)($item['weightage'] ?? 0))) / 100;
        });

        $deptPerfColor = match(true) {
            $departmentPerformance >= 90 => ['bar' => 'from-emerald-400 to-green-500', 'text' => 'text-emerald-400', 'label' => 'Excellent', 'badge' => 'bg-emerald-100 text-emerald-700'],
            $departmentPerformance >= 75 => ['bar' => 'from-[#8B5E4A] to-[#6B3F2A]',  'text' => 'text-white/50',    'label' => 'Good',      'badge' => 'bg-[#F5EAE0] text-[#6B3F2A]'],
            $departmentPerformance >= 50 => ['bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-yellow-300', 'label' => 'Watch',     'badge' => 'bg-yellow-100 text-yellow-700'],
            default                      => ['bar' => 'from-red-400 to-rose-500',     'text' => 'text-red-300',    'label' => 'Critical',  'badge' => 'bg-red-100 text-red-700'],
        };

        $statusCounts = collect($kpis ?? [])->countBy(fn($k) => $k['status'] ?? 'not_started');

        $rolePriorityFn = fn($role) => match(strtoupper(trim($role ?? ''))) {
            'SLT'       => 1,
            'VP'        => 2,
            'MANAGER'   => 3,
            'EXECUTIVE' => 4,
            default     => 5,
        };

        $staffGroupedKpis = collect($kpis)
            ->groupBy(fn($item) => $item['employee_name'] ?? 'Unknown')
            ->sortBy(fn($group) => $rolePriorityFn(
                collect($group)->first()['employee_role'] ?? collect($group)->first()['owner_role'] ?? ''
            ));

        $statusDef = [
            'completed'   => ['label' => 'Completed',  'color' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'],
            'on_track'    => ['label' => 'On Track',   'color' => 'bg-[#F5EAE0] text-[#6B3F2A]',       'dot' => 'bg-[#6B3F2A]'],
            'at_risk'     => ['label' => 'At Risk',    'color' => 'bg-yellow-100 text-yellow-700',   'dot' => 'bg-yellow-500'],
            'in_trouble'  => ['label' => 'In Trouble', 'color' => 'bg-red-100 text-red-700',         'dot' => 'bg-red-500'],
            'not_started' => ['label' => 'Not Started','color' => 'bg-slate-100 text-slate-500',     'dot' => 'bg-slate-400'],
        ];
    @endphp

    {{-- DEPT PERFORMANCE + LEGEND --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- DEPT SCORE --}}
        <div class="glass rounded-[20px] border border-white/70 p-6 shadow-sm flex flex-col justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Department Performance</p>
            <div class="flex items-end justify-between mt-3 gap-3">
                <h2 class="text-5xl font-black text-slate-900">{{ number_format($departmentPerformance, 1) }}<span class="text-2xl text-slate-400">%</span></h2>
                <span class="text-xs font-black px-3 py-1.5 rounded-xl {{ $deptPerfColor['badge'] }}">{{ $deptPerfColor['label'] }}</span>
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
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach($statusDef as $key => $def)
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl {{ $def['color'] }}">
                    <span class="w-2 h-2 rounded-full {{ $def['dot'] }} shrink-0"></span>
                    <span class="text-[10px] font-black">{{ $def['label'] }}</span>
                    <span class="ml-auto text-sm font-black">{{ $statusCounts[$key] ?? 0 }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- PERFORMANCE GUIDE --}}
        <div class="glass rounded-[20px] border border-white/70 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-6 h-6 rounded-lg bg-slate-800 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Performance Achievement Guide</p>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-100">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500"></span><span class="text-[11px] font-black text-emerald-700">Excellent</span></div>
                    <span class="text-[11px] font-black text-emerald-600">≥ 90%</span>
                </div>
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-[#FBF5EF] border border-[#6B3F2A]/20">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#6B3F2A]"></span><span class="text-[11px] font-black text-[#6B3F2A]">Good</span></div>
                    <span class="text-[11px] font-black text-[#6B3F2A]">75 – 89%</span>
                </div>
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-yellow-50 border border-yellow-100">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500"></span><span class="text-[11px] font-black text-yellow-700">Watch</span></div>
                    <span class="text-[11px] font-black text-yellow-600">50 – 74%</span>
                </div>
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-red-50 border border-red-100">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500"></span><span class="text-[11px] font-black text-red-700">Critical</span></div>
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
                <input id="searchInput" type="text" placeholder="Name, KPI title, sub-category..."
                       class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Category</label>
                <select id="categoryFilter" class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
                    <option value="">All Categories</option>
                    <option value="Financial">💰 Financial</option>
                    <option value="Growth & Customer">📈 Growth & Customer</option>
                    <option value="Initiatives">🚀 Initiatives</option>
                    <option value="People">👥 People</option>
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
    <div class="space-y-4">

        @foreach($staffGroupedKpis as $staffName => $staffKpis)

        @php
            $staffPerformance = 0;
            $excellentCount = 0; $goodCount = 0; $watchCount = 0; $criticalCount = 0;
            $staffStatusCounts = [];

            foreach($staffKpis as $sk){
                $q = collect($sk['quarters'] ?? []);
                $t = $q->sum(fn($x) => (float)($x['quarter_target'] ?? 0));
                $a = $q->sum(fn($x) => (float)($x['quarter_actual'] ?? 0));
                $score = $t > 0 ? (($a / $t) * 100) : 0;
                $staffPerformance += ($score * (float)($sk['weightage'] ?? 0)) / 100;
                if($score >= 90)     $excellentCount++;
                elseif($score >= 75) $goodCount++;
                elseif($score >= 50) $watchCount++;
                else                 $criticalCount++;
                $st = $sk['status'] ?? 'not_started';
                $staffStatusCounts[$st] = ($staffStatusCounts[$st] ?? 0) + 1;
            }

            $spColor = match(true) {
                $staffPerformance >= 90 => ['bar' => 'from-emerald-400 to-green-500', 'text' => 'text-emerald-700', 'label' => 'Excellent', 'badge' => 'bg-emerald-100 text-emerald-700'],
                $staffPerformance >= 75 => ['bar' => 'from-[#8B5E4A] to-[#6B3F2A]',  'text' => 'text-[#6B3F2A]',    'label' => 'Good',      'badge' => 'bg-[#F5EAE0] text-[#6B3F2A]'],
                $staffPerformance >= 50 => ['bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-yellow-700', 'label' => 'Watch',     'badge' => 'bg-yellow-100 text-yellow-700'],
                default                 => ['bar' => 'from-red-400 to-rose-500',     'text' => 'text-red-700',    'label' => 'Critical',  'badge' => 'bg-red-100 text-red-700'],
            };

            $staffRole     = collect($staffKpis)->first()['employee_role'] ?? '';
            $initials      = collect(explode(' ', strtoupper($staffName)))->filter()->map(fn($p)=>$p[0])->take(2)->join('');
            $staffSlug     = Str::slug($staffName);

            // Group by category in defined order
            $byCategory = collect($staffKpis)->groupBy('category');
            $orderedCats = [];
            foreach($categoryOrder as $co){
                if($byCategory->has($co)) $orderedCats[$co] = $byCategory[$co];
            }
            // Any remaining categories not in order
            foreach($byCategory as $catKey => $catItems){
                if(!isset($orderedCats[$catKey])) $orderedCats[$catKey] = $catItems;
            }

            // Category weightage totals
            $catWeightages = [];
            foreach($orderedCats as $catKey => $catKpis){
                $catWeightages[$catKey] = collect($catKpis)->sum(fn($k) => (float)($k['weightage'] ?? 0));
            }
        @endphp

        <div class="glass rounded-[20px] border border-white/70 overflow-hidden shadow-sm staff-block"
             data-staff="{{ strtolower($staffName) }}">

            {{-- STAFF HEADER --}}
            <button onclick="toggleStaff('{{ $staffSlug }}')"
                    class="w-full p-5 text-left hover:bg-slate-50/80 transition group">
                <div class="flex flex-col xl:flex-row xl:items-center gap-4">

                    {{-- AVATAR + INFO --}}
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#06142f] to-[#5a3323] flex items-center justify-center text-white font-black text-lg shrink-0 group-hover:scale-105 transition">
                            {{ $initials ?: '?' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-lg font-black text-slate-900">{{ strtoupper($staffName) }}</h2>
                                @if($staffRole)
                                <span class="text-[9px] font-black px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500 uppercase">{{ $staffRole }}</span>
                                @endif
                                <span class="text-[9px] font-black px-2 py-0.5 rounded-lg bg-slate-900 text-white">{{ count($staffKpis) }} KPIs</span>
                            </div>
                            {{-- CATEGORY PILLS --}}
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach($orderedCats as $catKey => $catKpis)
                                @php $ct = $catThemes[$catKey] ?? $catThemeDefault; @endphp
                                <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full {{ $ct['catPill'] }} text-[9px] font-black opacity-90">
                                    {{ $ct['icon'] }} {{ $catKey }} <span class="opacity-70 ml-0.5">({{ count($catKpis) }})</span>
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ACHIEVEMENT BREAKDOWN --}}
                    <div class="flex flex-wrap gap-1.5 xl:w-[280px]">
                        @if($excellentCount > 0)
                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-[10px] font-black border border-emerald-100">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>Excellent {{ $excellentCount }}
                        </span>
                        @endif
                        @if($goodCount > 0)
                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-[#FBF5EF] text-[#6B3F2A] text-[10px] font-black border border-[#6B3F2A]/20">
                            <span class="w-2 h-2 rounded-full bg-[#6B3F2A]"></span>Good {{ $goodCount }}
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
                            <p class="text-[9px] uppercase font-black text-slate-400 tracking-wider">Performance</p>
                            <span class="text-xs font-black px-2 py-0.5 rounded-lg {{ $spColor['badge'] }}">{{ $spColor['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-2.5 rounded-full bg-gradient-to-r {{ $spColor['bar'] }}" style="width: {{ min(100, max(2, $staffPerformance)) }}%"></div>
                            </div>
                            <span class="text-sm font-black {{ $spColor['text'] }} shrink-0">{{ number_format($staffPerformance, 1) }}%</span>
                        </div>
                    </div>

                    {{-- EXPAND ICON --}}
                    <div class="shrink-0">
                        <div id="arrow-{{ $staffSlug }}" class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                </div>
            </button>

            {{-- CATEGORY SECTIONS (hidden by default) --}}
            <div id="staff-{{ $staffSlug }}" class="hidden border-t border-slate-100">

                @foreach($orderedCats as $catKey => $catKpis)
                @php
                    $ct         = $catThemes[$catKey] ?? $catThemeDefault;
                    $catSlug    = $staffSlug . '-' . Str::slug($catKey);
                    $catWt      = round($catWeightages[$catKey] ?? 0, 1);

                    // Category performance
                    $catPerf = 0;
                    $catKpiCount = count($catKpis);
                    foreach($catKpis as $ck){
                        $cq = collect($ck['quarters'] ?? []);
                        $ct_ = $cq->sum(fn($q) => (float)($q['quarter_target'] ?? 0));
                        $ca_ = $cq->sum(fn($q) => (float)($q['quarter_actual'] ?? 0));
                        $cs = $ct_ > 0 ? ($ca_ / $ct_) * 100 : 0;
                        $catPerf += ($cs * (float)($ck['weightage'] ?? 0)) / 100;
                    }
                    $catPerfColor = match(true) {
                        $catPerf >= 90 => 'text-emerald-400',
                        $catPerf >= 75 => 'text-white/50',
                        $catPerf >= 50 => 'text-yellow-300',
                        default        => 'text-red-300',
                    };
                @endphp

                <div class="cat-section" data-category-section="{{ $catKey }}">

                    {{-- CATEGORY HEADER --}}
                    <button onclick="toggleCategory('{{ $catSlug }}')"
                            class="w-full text-left px-6 py-3.5 bg-gradient-to-r {{ $ct['headerBg'] }} flex items-center justify-between group hover:opacity-95 transition">
                        <div class="flex items-center gap-3">
                            <span class="text-base">{{ $ct['icon'] }}</span>
                            <div>
                                <span class="text-sm font-black text-white tracking-wide">{{ $catKey }}</span>
                                <span class="ml-2 text-[10px] font-black px-2 py-0.5 rounded-full {{ $ct['countBadge'] }}">{{ $catKpiCount }} KPI{{ $catKpiCount > 1 ? 's' : '' }}</span>
                                <span class="ml-1.5 text-[10px] {{ $ct['headerText'] }} opacity-70">· {{ $catWt }}% weightage</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-black {{ $catPerfColor }}">{{ number_format($catPerf, 1) }}%</span>
                            <div id="cat-arrow-{{ $catSlug }}" class="w-6 h-6 rounded-lg bg-white/10 flex items-center justify-center text-white transition-transform">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </button>

                    {{-- KPI CARDS FOR THIS CATEGORY --}}
                    <div id="cat-{{ $catSlug }}" class="{{ $ct['sectionBg'] }} border-b {{ $ct['divider'] }} p-4 space-y-2.5">

                        @foreach($catKpis as $kpi)
                        @php
                            $kq    = collect($kpi['quarters'] ?? []);
                            $kt    = $kq->sum(fn($q) => (float)($q['quarter_target'] ?? 0));
                            $ka    = $kq->sum(fn($q) => (float)($q['quarter_actual'] ?? 0));
                            $kachv = $kt > 0 ? (($ka / $kt) * 100) : 0;

                            $kColor = match(true) {
                                $kachv >= 90 => ['bar' => 'from-emerald-400 to-green-500', 'text' => 'text-emerald-700', 'label' => 'Excellent', 'badge' => 'bg-emerald-100 text-emerald-700'],
                                $kachv >= 75 => ['bar' => 'from-[#8B5E4A] to-[#6B3F2A]',  'text' => 'text-[#6B3F2A]',    'label' => 'Good',      'badge' => 'bg-[#F5EAE0] text-[#6B3F2A]'],
                                $kachv >= 50 => ['bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-yellow-700', 'label' => 'Watch',     'badge' => 'bg-yellow-100 text-yellow-700'],
                                default      => ['bar' => 'from-red-400 to-rose-500',     'text' => 'text-red-700',    'label' => 'Critical',  'badge' => 'bg-red-100 text-red-700'],
                            };

                            $kStatus    = $kpi['status'] ?? 'not_started';
                            $kStatusDef = $statusDef[$kStatus] ?? $statusDef['not_started'];
                        @endphp

                        <div onclick="openKpiDrawer(this)"
                             class="kpi-card cursor-pointer bg-white border border-l-4 {{ $ct['border'] }} rounded-[14px] p-4 hover:shadow-md transition-all hover:-translate-y-0.5"
                             data-search="{{ strtolower(($kpi['kpi_title'] ?? '') . ' ' . ($staffName ?? '') . ' ' . ($kpi['category'] ?? '') . ' ' . ($kpi['sub_category'] ?? '')) }}"
                             data-category="{{ $kpi['category'] ?? '' }}"
                             data-status="{{ $kStatus }}"
                             data-kpi='@json($kpi)'>

                            <div class="flex flex-col xl:flex-row xl:items-center gap-3">

                                {{-- LEFT: TITLE + BADGES --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap gap-1.5 mb-2">
                                        <span class="px-2.5 py-1 rounded-full {{ $ct['catPill'] }} text-[9px] font-black">{{ $ct['icon'] }} {{ $kpi['category'] ?? '-' }}</span>
                                        @if($kpi['sub_category'] ?? '')
                                        <span class="px-2.5 py-1 rounded-full {{ $ct['subPill'] }} text-[9px] font-black">{{ $kpi['sub_category'] }}</span>
                                        @endif
                                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-full {{ $kStatusDef['color'] }} text-[9px] font-black">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $kStatusDef['dot'] }}"></span>{{ $kStatusDef['label'] }}
                                        </span>
                                        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-black">{{ $kpi['weightage'] ?? 0 }}%</span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-900 leading-snug">{{ $kpi['kpi_title'] ?? '-' }}</h3>
                                    <p class="text-xs text-slate-400 mt-1 line-clamp-1">{{ $kpi['kpi_description'] ?? '-' }}</p>
                                </div>

                                {{-- QUARTER DOTS --}}
                                <div class="flex gap-1.5 shrink-0">
                                    @foreach(['Q1','Q2','Q3','Q4'] as $qn)
                                    @php
                                        $qd  = $kq->firstWhere('quarter', $qn) ?? [];
                                        $qkt = (float)($qd['quarter_target'] ?? 0);
                                        $qka = (float)($qd['quarter_actual'] ?? 0);
                                        $qks = $qkt > 0 ? ($qka / $qkt) * 100 : 0;
                                        $qDot = match(true) {
                                            $qks >= 90 => 'bg-emerald-500 text-white',
                                            $qks >= 75 => 'bg-[#6B3F2A] text-white',
                                            $qks >= 50 => 'bg-yellow-400 text-white',
                                            $qks > 0   => 'bg-red-500 text-white',
                                            default    => 'bg-slate-200 text-slate-400',
                                        };
                                    @endphp
                                    <div class="w-8 h-8 rounded-xl {{ $qDot }} flex items-center justify-center text-[9px] font-black">{{ $qn }}</div>
                                    @endforeach
                                </div>

                                {{-- ACHIEVEMENT --}}
                                <div class="xl:w-[170px] shrink-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-[9px] uppercase font-black text-slate-400">Achievement</p>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-lg {{ $kColor['badge'] }}">{{ $kColor['label'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-2 rounded-full bg-gradient-to-r {{ $kColor['bar'] }}" style="width: {{ min(100, max(2, $kachv)) }}%"></div>
                                        </div>
                                        <span class="text-xs font-black {{ $kColor['text'] }} shrink-0 w-12 text-right">{{ number_format($kachv, 1) }}%</span>
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
const categoryColors = {
    'Financial':         { catPill: 'bg-emerald-700 text-white', subPill: 'bg-emerald-100 text-emerald-700', icon: '💰', headerGrad: 'linear-gradient(to right,#065f46,#059669)' },
    'Growth & Customer': { catPill: 'bg-indigo-700 text-white',  subPill: 'bg-indigo-100 text-indigo-700',   icon: '📈', headerGrad: 'linear-gradient(to right,#3730a3,#4f46e5)' },
    'Initiatives':       { catPill: 'bg-amber-600 text-white',   subPill: 'bg-amber-100 text-amber-700',     icon: '🚀', headerGrad: 'linear-gradient(to right,#92400e,#f59e0b)' },
    'People':            { catPill: 'bg-pink-700 text-white',    subPill: 'bg-pink-100 text-pink-700',       icon: '👥', headerGrad: 'linear-gradient(to right,#9d174d,#db2777)' },
};

const statusLabels = {
    'completed':   { label: 'Completed',  color: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
    'on_track':    { label: 'On Track',   color: 'bg-[#F5EAE0] text-[#6B3F2A]',       dot: 'bg-[#6B3F2A]' },
    'at_risk':     { label: 'At Risk',    color: 'bg-yellow-100 text-yellow-700',   dot: 'bg-yellow-500' },
    'in_trouble':  { label: 'In Trouble', color: 'bg-red-100 text-red-700',         dot: 'bg-red-500' },
    'not_started': { label: 'Not Started',color: 'bg-slate-100 text-slate-500',     dot: 'bg-slate-400' },
};

function achvBadge(score){
    if(score >= 90) return { label: 'Excellent', color: 'bg-emerald-100 text-emerald-700', bar: 'from-emerald-400 to-green-500' };
    if(score >= 75) return { label: 'Good',      color: 'bg-[#F5EAE0] text-[#6B3F2A]',       bar: 'from-[#8B5E4A] to-[#6B3F2A]' };
    if(score >= 50) return { label: 'Watch',     color: 'bg-yellow-100 text-yellow-700',   bar: 'from-yellow-400 to-amber-500' };
    return           { label: 'Critical',  color: 'bg-red-100 text-red-700',         bar: 'from-red-400 to-rose-500' };
}

function toggleStaff(id){
    const el    = document.getElementById('staff-' + id);
    const arrow = document.getElementById('arrow-' + id);
    if(!el) return;
    const isHidden = el.classList.toggle('hidden');
    if(arrow) arrow.classList.toggle('rotate-180', !isHidden);
}

function toggleCategory(id){
    const el    = document.getElementById('cat-' + id);
    const arrow = document.getElementById('cat-arrow-' + id);
    if(!el) return;
    const isHidden = el.classList.toggle('hidden');
    if(arrow) arrow.classList.toggle('rotate-180', !isHidden);
}

const searchInput    = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const statusFilter   = document.getElementById('statusFilter');
const visibleCount   = document.getElementById('visibleCount');

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

    // Auto-expand staff + category sections that have visible KPIs
    document.querySelectorAll('.staff-block').forEach(block => {
        const slug = block.querySelector('[id^="staff-"]')?.id?.replace('staff-', '');
        if(!slug) return;
        const staffSection = document.getElementById('staff-' + slug);
        const hasVisible   = [...block.querySelectorAll('.kpi-card')].some(c => !c.classList.contains('hidden'));
        if(hasVisible && staffSection && staffSection.classList.contains('hidden')){
            toggleStaff(slug);
        }
    });

    // Auto-expand category sections with visible cards when filtering
    if(category || status || search){
        document.querySelectorAll('[id^="cat-"]').forEach(sec => {
            if(!sec.id.startsWith('cat-')) return;
            const hasVis = [...sec.querySelectorAll('.kpi-card')].some(c => !c.classList.contains('hidden'));
            if(hasVis) sec.classList.remove('hidden');
        });
    }
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
    const catColor = categoryColors[kpi.category] || { catPill:'bg-slate-600 text-white', subPill:'bg-slate-100 text-slate-600', icon:'📌', headerGrad:'linear-gradient(to right,#475569,#64748b)' };

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
                <div class="bg-slate-50 rounded-xl p-4"><p class="text-[9px] uppercase text-slate-400 font-black">Target</p><h3 class="text-xl font-black text-slate-900 mt-1">${target.toLocaleString()}</h3></div>
                <div class="bg-emerald-50 rounded-xl p-4"><p class="text-[9px] uppercase text-emerald-600 font-black">Actual</p><h3 class="text-xl font-black text-emerald-700 mt-1">${actual.toLocaleString()}</h3></div>
            </div>
        </div>`;
    });

    content.innerHTML = `
    <div class="min-h-screen bg-[#f8fafc]">
        <div class="sticky top-0 z-20 bg-white border-b border-slate-100 p-6">
            <div class="flex items-start justify-between gap-5">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full ${catColor.catPill} text-[10px] font-black">${catColor.icon} ${kpi.category || '-'}</span>
                        <span class="px-3 py-1 rounded-full ${catColor.subPill} text-[10px] font-black">${kpi.sub_category || '-'}</span>
                        <span class="flex items-center gap-1.5 px-3 py-1 rounded-full ${sDef.color} text-[10px] font-black">
                            <span class="w-1.5 h-1.5 rounded-full ${sDef.dot}"></span>Status: ${sDef.label}
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
                <div class="rounded-[20px] overflow-hidden text-white shadow-xl p-5" style="background:${catColor.headerGrad}">
                    <p class="text-[9px] uppercase opacity-70 font-black tracking-widest">KPI Owner</p>
                    <h2 class="text-xl font-black mt-2">${kpi.employee_name || '-'}</h2>
                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <div class="bg-white/10 rounded-xl p-4">
                            <p class="text-[9px] uppercase opacity-70 font-black">Weightage</p>
                            <h3 class="text-2xl font-black mt-1">${kpi.weightage || 0}%</h3>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4">
                            <p class="text-[9px] uppercase opacity-70 font-black">KPI Status</p>
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
                        <div class="flex justify-between px-3 py-1.5 rounded-xl bg-[#FBF5EF]"><span class="font-black text-[#6B3F2A]">● Good</span><span class="text-[#6B3F2A] font-bold">75 – 89%</span></div>
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
    document.getElementById('kpiDrawer')?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// ─────────────────────────────────────────────
// GENERATE KPIs FROM TEMPLATE (non-Titan depts)
// ─────────────────────────────────────────────
async function applyKpiTemplate() {
    const btn = document.getElementById('applyTemplateBtn');
    if (!confirm('This will generate KPI entries for ALL staff in this department based on the template. Existing KPIs will not be overwritten. Continue?')) return;
    btn.disabled = true;
    btn.textContent = 'Generating…';
    try {
        const res = await fetch('{{ route("kpi.apply-template") }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body: JSON.stringify({})
        });
        const data = await res.json();
        if (data.success) { alert(data.message); location.reload(); }
        else { alert('Error: ' + (data.error ?? 'Unknown error')); }
    } catch (e) {
        alert('Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Generate KPIs from Template';
    }
}

// ─────────────────────────────────────────────
// KPI TEMPLATE WIZARD
// ─────────────────────────────────────────────
let wizardTemplates = [];
let wizardEmployees = [];

async function openWizard() {
    const w = document.getElementById('kpiWizard');
    w.classList.remove('hidden');
    w.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    await loadWizardStep1();
}

function closeWizard() {
    const w = document.getElementById('kpiWizard');
    w.classList.add('hidden');
    w.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

async function loadWizardStep1() {
    showStep(1);
    const res = await fetch('{{ route("kpi-templates.index") }}');
    wizardTemplates = await res.json();
    renderTemplates();
}

function renderTemplates() {
    const container = document.getElementById('templateList');
    if (!wizardTemplates.length) {
        container.innerHTML = '<p class="text-slate-400 text-sm italic py-4 text-center">No templates yet. Add one below.</p>';
        return;
    }
    const unitColor = {
        percentage: 'bg-purple-100 text-purple-700',
        currency:   'bg-emerald-100 text-emerald-700',
        number:     'bg-blue-100 text-blue-700',
    };
    container.innerHTML = wizardTemplates.map(t => `
        <div class="flex items-start gap-3 py-3 border-b border-slate-100 last:border-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-black uppercase bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">${t.category}</span>
                    ${t.sub_category ? `<span class="text-[10px] text-slate-500">${t.sub_category}</span>` : ''}
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full ${(unitColor[t.unit]||'bg-slate-100 text-slate-600')}">${t.unit}</span>
                </div>
                <p class="text-sm font-bold text-slate-800 mt-1">${t.kpi_title}</p>
                ${t.kpi_description ? `<p class="text-xs text-slate-500 mt-0.5">${t.kpi_description}</p>` : ''}
            </div>
            <button onclick="deleteTemplate('${t.id}')" class="text-red-400 hover:text-red-600 text-xs font-semibold shrink-0 mt-1">Delete</button>
        </div>
    `).join('');
}

async function deleteTemplate(id) {
    if (!confirm('Delete this template?')) return;
    await fetch(`/kpi-templates/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    });
    wizardTemplates = wizardTemplates.filter(t => t.id !== id);
    renderTemplates();
}

async function addTemplate() {
    const form = document.getElementById('addTemplateForm');
    const data = {
        category:        document.getElementById('tpl_category').value.trim(),
        sub_category:    document.getElementById('tpl_sub').value.trim(),
        kpi_title:       document.getElementById('tpl_title').value.trim(),
        kpi_description: document.getElementById('tpl_desc').value.trim(),
        unit:            document.getElementById('tpl_unit').value,
    };
    if (!data.category || !data.kpi_title) {
        alert('Category and KPI Title are required.');
        return;
    }
    const res = await fetch('{{ route("kpi-templates.store") }}', {
        method:  'POST',
        headers: {
            'Content-Type':  'application/json',
            'X-CSRF-TOKEN':  '{{ csrf_token() }}',
        },
        body: JSON.stringify(data),
    });
    const json = await res.json();
    if (json.success) {
        wizardTemplates.push(json.template);
        renderTemplates();
        form.reset();
    }
}

async function goToStep2() {
    if (!wizardTemplates.length) {
        alert('Please add at least one template first.');
        return;
    }
    showStep(2);
    renderEmployees();
}

function renderEmployees() {
    const employees = @json($employees ?? []);
    const kpis      = @json($kpis ?? []);
    wizardEmployees  = employees;

    const empKpiCount = {};
    kpis.forEach(k => {
        empKpiCount[k.employee_id] = (empKpiCount[k.employee_id] || 0) + 1;
    });

    const container = document.getElementById('employeeList');
    const roleColor = {
        EXECUTIVE: 'bg-slate-100 text-slate-600',
        MANAGER:   'bg-blue-100 text-blue-700',
        SLT:       'bg-purple-100 text-purple-700',
        VP:        'bg-amber-100 text-amber-700',
    };

    container.innerHTML = employees.map(e => `
        <label class="flex items-center gap-3 py-3 border-b border-slate-100 last:border-0 cursor-pointer hover:bg-slate-50 px-2 rounded-lg">
            <input type="checkbox" class="emp-check w-4 h-4 rounded" value="${e.id}" checked>
            <div class="flex-1">
                <p class="text-sm font-bold text-slate-800">${e.short_name ?? e.employee_id}</p>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full ${roleColor[e.role] || 'bg-slate-100 text-slate-600'}">${e.role}</span>
            </div>
            ${empKpiCount[e.id]
                ? `<span class="text-[10px] bg-teal-100 text-teal-700 font-semibold px-2 py-0.5 rounded-full">${empKpiCount[e.id]} KPI(s) exist</span>`
                : '<span class="text-[10px] bg-red-50 text-red-400 font-semibold px-2 py-0.5 rounded-full">No KPIs yet</span>'
            }
        </label>
    `).join('');
}

function toggleAllEmployees(checked) {
    document.querySelectorAll('.emp-check').forEach(c => c.checked = checked);
}

async function generateForSelected() {
    const ids = [...document.querySelectorAll('.emp-check:checked')].map(c => c.value);
    if (!ids.length) {
        alert('Please select at least one staff member.');
        return;
    }
    const btn = document.getElementById('generateBtn');
    btn.disabled    = true;
    btn.textContent = 'Generating…';

    try {
        const res = await fetch('{{ route("kpi.apply-template") }}', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ employee_ids: ids }),
        });
        const data = await res.json();
        if (data.success) {
            closeWizard();
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error ?? 'Unknown'));
        }
    } catch (e) {
        alert('Network error. Please try again.');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Generate KPIs for Selected →';
    }
}

function showStep(n) {
    const s1 = document.getElementById('wizardStep1');
    const s2 = document.getElementById('wizardStep2');
    s1.classList.toggle('hidden', n !== 1);
    s2.classList.toggle('hidden', n !== 2);
    s2.classList.toggle('flex', n === 2);
}
</script>

{{-- KPI TEMPLATE WIZARD MODAL --}}
<div id="kpiWizard" class="hidden fixed inset-0 bg-black/60 z-[9999] items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">

        {{-- STEP 1: TEMPLATES --}}
        <div id="wizardStep1">
            <div class="bg-gradient-to-r from-[#06142f] to-blue-900 text-white px-6 py-5 flex items-center justify-between shrink-0">
                <div>
                    <p class="text-[10px] text-blue-300 uppercase font-black tracking-widest">Step 1 of 2</p>
                    <h2 class="text-lg font-black mt-0.5">KPI Templates</h2>
                    <p class="text-[11px] text-blue-200 mt-0.5">Define what KPIs your team should have. These will be applied to selected staff.</p>
                </div>
                <button onclick="closeWizard()" class="text-white/60 hover:text-white text-2xl leading-none">×</button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                {{-- Template list --}}
                <div id="templateList" class="mb-4"></div>

                {{-- Add form --}}
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4" id="addTemplateForm">
                    <p class="text-[11px] font-black text-slate-600 uppercase tracking-wider mb-3">Add New Template</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Category *</label>
                            <input id="tpl_category" type="text" placeholder="e.g. Financial" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Sub Category</label>
                            <input id="tpl_sub" type="text" placeholder="e.g. Revenue" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide block mb-1">KPI Title *</label>
                        <input id="tpl_title" type="text" placeholder="e.g. Total Collection Achievement (Revenue)" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                    </div>
                    <div class="mb-3">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Description</label>
                        <input id="tpl_desc" type="text" placeholder="e.g. To achieve targeted individual monthly revenue collection." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                    </div>
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Unit *</label>
                            <select id="tpl_unit" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                                <option value="percentage">Percentage (%)</option>
                                <option value="currency">Currency (RM)</option>
                                <option value="number">Number</option>
                            </select>
                        </div>
                        <button onclick="addTemplate()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-black px-4 py-2 rounded-lg transition">
                            + Add
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex justify-between items-center shrink-0 bg-white">
                <button onclick="closeWizard()" class="text-slate-500 text-sm font-semibold hover:text-slate-700">Cancel</button>
                <button onclick="goToStep2()" class="bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-black px-6 py-2.5 rounded-xl transition">
                    Next: Select Staff →
                </button>
            </div>
        </div>

        {{-- STEP 2: SELECT EMPLOYEES --}}
        <div id="wizardStep2" class="hidden flex-col h-full">
            <div class="bg-gradient-to-r from-[#06142f] to-blue-900 text-white px-6 py-5 flex items-center justify-between shrink-0">
                <div>
                    <p class="text-[10px] text-blue-300 uppercase font-black tracking-widest">Step 2 of 2</p>
                    <h2 class="text-lg font-black mt-0.5">Select Staff</h2>
                    <p class="text-[11px] text-blue-200 mt-0.5">Choose who should receive these KPI templates. Existing KPIs will not be duplicated.</p>
                </div>
                <button onclick="closeWizard()" class="text-white/60 hover:text-white text-2xl leading-none">×</button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div class="flex gap-3 mb-3">
                    <button onclick="toggleAllEmployees(true)" class="text-[11px] text-blue-600 font-semibold hover:underline">Select All</button>
                    <span class="text-slate-300">|</span>
                    <button onclick="toggleAllEmployees(false)" class="text-[11px] text-slate-500 font-semibold hover:underline">Deselect All</button>
                </div>
                <div id="employeeList"></div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex justify-between items-center shrink-0 bg-white">
                <button onclick="showStep(1)" class="text-slate-500 text-sm font-semibold hover:text-slate-700">← Back</button>
                <button id="generateBtn" onclick="generateForSelected()" class="bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-black px-6 py-2.5 rounded-xl transition">
                    Generate KPIs for Selected →
                </button>
            </div>
        </div>

    </div>
</div>

</body>
</html>
