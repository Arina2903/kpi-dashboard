<!DOCTYPE html>
<html>
<head>
    <title>KPI List</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .glass {
            background: rgba(255,255,255,.9);
            backdrop-filter: blur(14px);
        }
        .card-hover {
            transition: .2s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 35px rgba(15,23,42,.08);
        }
        .modal-bg {
            background: rgba(15,23,42,.65);
            backdrop-filter: blur(8px);
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        .weightage-input::-webkit-outer-spin-button,
        .weightage-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .weightage-input {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300 bg-[#f4f7fb]">

<div class="p-6 space-y-6">

    <!-- HEADER -->
    <div class="rounded-[18px] bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white p-6 shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="/dashboard" class="text-xs text-blue-100 hover:text-white">← Dashboard</a>
            <h1 class="text-3xl font-bold mt-3">KPI List</h1>
            <p class="text-blue-100 text-xs mt-1">
                {{ $user['short_name'] }} · {{ $user['role'] }} · {{ $user['department_code'] }} · {{ $fy }}
            </p>
        </div>

        @if($permission['can_create'])
            <a href="{{ route('kpi.create') }}"
               class="bg-white text-blue-900 hover:bg-blue-50 px-5 py-2.5 rounded-2xl shadow font-bold">
                + Create KPI
            </a>
        @endif
    </div>

    <!-- MESSAGES -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2.5 rounded-xl text-xs">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-2.5 rounded-xl text-xs">
            {{ session('error') }}
        </div>
    @endif


    @php
        /*
            Individual Performance Formula:
            1. Calculate each KPI score = total actual / total target * 100.
            2. Calculate each KPI weighted score = KPI score * weightage / 100.
            3. Individual performance = total weighted score from current user's own KPI only.
        */
        $currentUserId = (string) ($user['id'] ?? '');
        $currentEmployeeId = (string) ($user['id'] ?? '');
        $currentUserName = strtolower(trim($user['short_name'] ?? $user['full_name'] ?? $user['name'] ?? ''));

        $calculateDashboardKpiScore = function ($item) {
            $quarters = collect($item['quarters'] ?? $item['quarter_plans'] ?? []);
            $baseTotal = 0;
            $actualTotal = 0;

            foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarterName) {
                $quarter = $quarters->firstWhere('quarter', $quarterName) ?? [];
                $baseTotal += max(0, (float) ($quarter['quarter_target'] ?? 0));
                $actualTotal += max(0, (float) ($quarter['quarter_actual'] ?? 0));
            }

            if ($baseTotal <= 0) {
                $baseTotal = max(0, (float) ($item['base_target'] ?? 0));
                $actualTotal = max(0, (float) ($item['actual_value'] ?? 0));
            }

            return $baseTotal > 0 ? round(($actualTotal / $baseTotal) * 100, 2) : 0;
        };

        $isCurrentUserKpi = function ($item) use ($currentUserId, $currentEmployeeId, $currentUserName) {
            $kpiEmployeeId = (string) ($item['employee_id'] ?? '');
            $kpiCreatedBy = (string) ($item['created_by'] ?? '');
            $kpiOwnerName = strtolower(trim($item['employee_name'] ?? $item['owner_name'] ?? ''));

            return ($currentUserId !== '' && ($kpiEmployeeId === $currentUserId || $kpiCreatedBy === $currentUserId))
                || ($currentEmployeeId !== '' && $kpiEmployeeId === $currentEmployeeId)
                || ($currentUserName !== '' && $kpiOwnerName === $currentUserName);
        };

        $individualKpiRows = collect($kpis ?? [])->filter(fn($item) => $isCurrentUserKpi($item));
        $individualKpiCount = $individualKpiRows->count();
        $individualTotalWeightage = round($individualKpiRows->sum(fn($item) => (float) ($item['weightage'] ?? 0)), 2);
        $individualPerformance = round($individualKpiRows->sum(function ($item) use ($calculateDashboardKpiScore) {
            $score = $calculateDashboardKpiScore($item);
            $weightage = max(0, (float) ($item['weightage'] ?? 0));
            return ($score * $weightage) / 100;
        }), 2);

        $individualPerformanceWidth = max(0, min(100, $individualPerformance));

        if ($individualPerformance <= 25) {
            $individualPerformanceBar = 'bg-red-600';
            $individualPerformanceText = 'text-red-700';
            $individualPerformanceLabel = 'Critical';
            $individualPerformanceBadge = 'bg-red-50 text-red-700 border-red-100';
        } elseif ($individualPerformance <= 50) {
            $individualPerformanceBar = 'bg-gradient-to-r from-red-600 to-orange-500';
            $individualPerformanceText = 'text-orange-700';
            $individualPerformanceLabel = 'Risk';
            $individualPerformanceBadge = 'bg-orange-50 text-orange-700 border-orange-100';
        } elseif ($individualPerformance <= 75) {
            $individualPerformanceBar = 'bg-gradient-to-r from-orange-500 to-yellow-400';
            $individualPerformanceText = 'text-yellow-700';
            $individualPerformanceLabel = 'Watch';
            $individualPerformanceBadge = 'bg-yellow-50 text-yellow-700 border-yellow-100';
        } elseif ($individualPerformance <= 100) {
            $individualPerformanceBar = 'bg-gradient-to-r from-yellow-400 to-green-500';
            $individualPerformanceText = 'text-green-700';
            $individualPerformanceLabel = 'Good';
            $individualPerformanceBadge = 'bg-green-50 text-green-700 border-green-100';
        } else {
            $individualPerformanceBar = 'bg-green-600';
            $individualPerformanceText = 'text-green-700';
            $individualPerformanceLabel = 'Exceeded';
            $individualPerformanceBadge = 'bg-emerald-50 text-emerald-700 border-emerald-100';
        }
    @endphp

    <!-- SUMMARY -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">

        <!-- INDIVIDUAL PERFORMANCE SPLASH CARD -->
        <div class="glass card-hover p-4 rounded-[18px] border border-indigo-100 bg-gradient-to-br from-white via-indigo-50 to-blue-50 shadow-sm md:col-span-2 xl:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase">Individual Performance</p>
                    <h3 id="individualPerformanceText" class="text-3xl font-black {{ $individualPerformanceText }} mt-1">
                        {{ number_format($individualPerformance, 2) }}%
                    </h3>
                </div>

                <span id="individualPerformanceBadge" class="text-[10px] font-black px-2 py-1 rounded-full border {{ $individualPerformanceBadge }}">
                    {{ $individualPerformanceLabel }}
                </span>
            </div>

            <div class="mt-4 h-3 rounded-full bg-white border border-white/80 overflow-hidden">
                <div id="individualPerformanceBar"
                     class="h-3 rounded-full transition-all duration-300 {{ $individualPerformanceBar }}"
                     style="width: {{ $individualPerformanceWidth }}%">
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-[10px]">
                <div class="rounded-xl bg-white/80 border border-white p-2">
                    <p class="text-slate-400 uppercase font-bold">My KPI</p>
                    <p id="individualKpiCountText" class="text-xs font-black text-slate-900">{{ $individualKpiCount }}</p>
                </div>
                <div class="rounded-xl bg-white/80 border border-white p-2">
                    <p class="text-slate-400 uppercase font-bold">Weightage</p>
                    <p id="individualWeightageText" class="text-xs font-black {{ $individualTotalWeightage == 100 ? 'text-emerald-700' : ($individualTotalWeightage > 100 ? 'text-red-700' : 'text-amber-700') }}">
                        {{ number_format($individualTotalWeightage, 2) }}%
                    </p>
                </div>
            </div>
        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">FY</p>
            <h3 class="text-xl font-bold text-slate-900 mt-1">{{ $fy }}</h3>
        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">Staff</p>
            <h3 class="text-xl font-bold text-slate-900 mt-1">{{ count($employees) }}</h3>
        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">Total KPI</p>
            <h3 class="text-xl font-bold text-slate-900 mt-1">{{ count($kpis) }}</h3>
        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">Scope</p>
            <h3 class="text-xl font-bold text-slate-900 mt-1">
                @if(in_array($user['role'], ['SLT', 'VP', 'CCO', 'CCMO']))
                    Company
                @else
                    Department
                @endif
            </h3>
        </div>
    </div>

    <!-- FILTER -->
    <div class="sticky top-4 z-40 glass rounded-[18px] shadow-sm border border-white/70 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Search</label>
                <input id="searchInput" type="text" placeholder="Title, staff, category..."
                       class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
            </div>

            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Category</label>
                <select id="categoryFilter" class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
                    <option value="">All</option>
                    <option value="Financial">Financial</option>
                    <option value="Growth & Customer">Growth & Customer</option>
                    <option value="Initiatives">Initiatives</option>
                    <option value="People">People</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Status</label>
                <select id="statusFilter" class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">
                    <option value="">All</option>
                    <option value="not_started">Not Started</option>
                    <option value="on_track">On Track</option>
                    <option value="at_risk">At Risk</option>
                    <option value="in_trouble">In Trouble</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="bg-slate-900 text-white rounded-2xl p-4">
                <p class="text-xs text-blue-100">Visible KPI</p>
                <p id="visibleCount" class="text-xl font-bold">{{ count($kpis) }}</p>
            </div>
        </div>
    </div>

        @php

        $staffGroupedKpis = collect($kpis)->groupBy(function($item){
            return $item['employee_name'] ?? 'Unknown';
        });

        @endphp

        <div class="space-y-3">

        @foreach($staffGroupedKpis as $staffName => $staffKpis)

            @php

                $staffPerformance = 0;
                $staffWeightage = 0;

                foreach($staffKpis as $staffKpi){

                    $quarters = collect($staffKpi['quarters'] ?? []);

                    $baseTotal = 0;
                    $actualTotal = 0;

                    foreach(['Q1','Q2','Q3','Q4'] as $quarterName){

                        $quarter = $quarters->firstWhere('quarter', $quarterName) ?? [];

                        $baseTotal += (float)($quarter['quarter_target'] ?? 0);
                        $actualTotal += (float)($quarter['quarter_actual'] ?? 0);
                    }

                    $achievement = $baseTotal > 0
                        ? (($actualTotal / $baseTotal) * 100)
                        : 0;

                    $weightage = (float)($staffKpi['weightage'] ?? 0);

                    $staffPerformance += (($achievement * $weightage) / 100);

                    $staffWeightage += $weightage;
                }

                if($staffPerformance >= 75){
                    $staffColor = 'from-emerald-500 to-green-600';
                    $staffText = 'text-emerald-600';
                }
                elseif($staffPerformance >= 50){
                    $staffColor = 'from-yellow-400 to-orange-500';
                    $staffText = 'text-yellow-600';
                }
                else{
                    $staffColor = 'from-red-500 to-red-700';
                    $staffText = 'text-red-600';
                }

            @endphp

            <!-- STAFF CARD -->
            <div class="staff-wrapper">

                <!-- STAFF HEADER -->
                <div
                    onclick="toggleStaff('{{ Str::slug($staffName) }}')"
                    class="cursor-pointer bg-white border border-slate-200 rounded-[18px] overflow-hidden hover:shadow-lg transition-all duration-300">

                    <div class="px-4 py-3">

                        <div class="flex items-center justify-between gap-4">

                            <!-- LEFT -->
                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#06142f] to-blue-700 flex items-center justify-center text-white text-sm font-black">
                                    {{ strtoupper(substr($staffName,0,1)) }}
                                </div>

                                <div>

                                    <h2 class="text-[15px] font-black text-slate-900">
                                        {{ $staffName }}
                                    </h2>

                                    <div class="flex items-center gap-2 mt-1">

                                        <div class="px-2 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                                            {{ count($staffKpis) }} KPI
                                        </div>

                                        <div class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold">
                                            {{ number_format($staffWeightage,0) }}%
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- RIGHT -->
                            <div class="w-[220px]">

                                <div class="flex items-center justify-between mb-2">

                                    <p class="text-[10px] uppercase font-black text-slate-400">
                                        Performance
                                    </p>

                                    <p class="text-lg font-black {{ $staffText }}">
                                        {{ number_format($staffPerformance,2) }}%
                                    </p>

                                </div>

                                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">

                                    <div
                                        class="h-2.5 rounded-full bg-gradient-to-r {{ $staffColor }}"
                                        style="width: {{ min(100,$staffPerformance) }}%">
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- STAFF MODAL -->
                <div
                    id="staff-{{ Str::slug($staffName) }}"
                    class="hidden fixed inset-0 z-[100]">

                    <!-- BACKDROP -->
                    <div
                        onclick="toggleStaff('{{ Str::slug($staffName) }}')"
                        class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm">
                    </div>

                    <!-- PANEL -->
                    <div class="absolute right-0 top-0 h-full w-full max-w-[900px] bg-[#f8fafc] overflow-y-auto shadow-2xl border-l border-slate-200">

                        <!-- HEADER -->
                        <div class="sticky top-0 z-20 bg-white border-b border-slate-200 px-5 py-3 flex items-center justify-between">

                            <div>

                                <h2 class="text-lg font-black text-slate-900">
                                    {{ $staffName }}
                                </h2>

                                <p class="text-xs text-slate-500 mt-1">
                                    {{ count($staffKpis) }} KPI · Performance Overview
                                </p>

                            </div>

                            <button
                                onclick="toggleStaff('{{ Str::slug($staffName) }}')"
                                class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black">

                                ✕

                            </button>

                        </div>

                        <!-- KPI LIST -->
                        <div class="p-4 space-y-3">

                            @if(count($staffKpis) === 0)

                                <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center">

                                    <h3 class="text-lg font-black text-slate-900">
                                        No KPI Assigned
                                    </h3>

                                    <p class="text-xs text-slate-500 mt-2">
                                        This staff currently has no KPI assigned.
                                    </p>

                                </div>

                                @endif

                            @foreach($staffKpis as $kpi)

                                @php

                                    $quarters = collect($kpi['quarters'] ?? []);

                                    $baseTotal = 0;
                                    $actualTotal = 0;

                                    foreach(['Q1','Q2','Q3','Q4'] as $quarterName){

                                        $quarter = $quarters->firstWhere('quarter', $quarterName) ?? [];

                                        $baseTotal += (float)($quarter['quarter_target'] ?? 0);
                                        $actualTotal += (float)($quarter['quarter_actual'] ?? 0);
                                    }

                                    $achievement = $baseTotal > 0
                                        ? round(($actualTotal / $baseTotal) * 100,2)
                                        : 0;

                                    if($achievement >= 75){
                                        $progressColor = 'from-emerald-400 to-green-600';
                                        $progressText = 'text-emerald-600';
                                    }
                                    elseif($achievement >= 50){
                                        $progressColor = 'from-yellow-400 to-orange-500';
                                        $progressText = 'text-yellow-600';
                                    }
                                    else{
                                        $progressColor = 'from-red-500 to-red-700';
                                        $progressText = 'text-red-600';
                                    }

                                    $today = \Carbon\Carbon::today();

                                        $quarterStatus = null;
                                        $quarterStatusText = null;
                                        $quarterStatusColor = null;

                                        $activeQuarter = null;

                                        foreach(($kpi['quarters'] ?? []) as $quarterCheck){

                                            $startDate = !empty($quarterCheck['start_date'])
                                                ? \Carbon\Carbon::parse($quarterCheck['start_date'])
                                                : null;

                                            $endDate = !empty($quarterCheck['end_date'])
                                                ? \Carbon\Carbon::parse($quarterCheck['end_date'])
                                                : null;

                                            if(!$startDate || !$endDate){
                                                continue;
                                            }

                                            if($today->between($startDate, $endDate)){

                                                $activeQuarter = $quarterCheck;

                                                $daysLeft = $today->diffInDays($endDate, false);

                                                if($daysLeft == 0){

                                                    $quarterStatus = 'today';
                                                    $quarterStatusText = 'Ends Today';
                                                    $quarterStatusColor = 'bg-orange-50 text-orange-700';

                                                } else {

                                                    $quarterStatus = 'ongoing';
                                                    $quarterStatusText = $daysLeft . ' days left';
                                                    $quarterStatusColor = 'bg-emerald-50 text-emerald-700';
                                                }

                                                break;
                                            }

                                            if($today->lt($startDate)){

                                                $daysToStart = $today->diffInDays($startDate, false);

                                                $quarterStatus = 'not_started';
                                                $quarterStatusText = 'Starts in ' . $daysToStart . ' days';
                                                $quarterStatusColor = 'bg-blue-50 text-blue-700';

                                                break;
                                            }

                                            if($today->gt($endDate)){

                                                $daysEnded = $endDate->diffInDays($today);

                                                $quarterStatus = 'ended';
                                                $quarterStatusText = 'Ended ' . $daysEnded . ' days ago';
                                                $quarterStatusColor = 'bg-red-50 text-red-700';
                                            }
                                        }

                                    @endphp

                                <!-- KPI CARD -->
                                <div
                                    onclick="event.stopPropagation(); openKpiDetail(this)"

                                    class="kpi-card cursor-pointer bg-white border border-slate-200 rounded-[18px] overflow-hidden hover:shadow-[0_20px_50px_rgba(15,23,42,.12)] hover:-translate-y-[2px] transition-all duration-300"

                                    data-search="{{ strtolower(($kpi['kpi_title'] ?? '') . ' ' . ($kpi['employee_name'] ?? '') . ' ' . ($kpi['category'] ?? '')) }}"

                                    data-category="{{ $kpi['category'] ?? '' }}"

                                    data-status="{{ $kpi['status'] ?? '' }}"

                                    data-kpi='@json($kpi)'
                                >

                                    <div class="px-4 py-3">

                                        <div class="flex flex-col xl:flex-row xl:items-center gap-4">

                                            <!-- LEFT -->
                                            <div class="flex-1 min-w-0">

                                                <div class="flex flex-wrap items-center gap-2 mb-2">

                                                    <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold">
                                                        {{ $kpi['category'] ?? 'General' }}
                                                    </span>

                                                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                                                        {{ $kpi['sub_category'] ?? 'Sub Category' }}
                                                    </span>

                                                </div>

                                                <h3 class="text-[15px] font-black text-slate-900">
                                                    {{ $kpi['kpi_title'] }}
                                                </h3>

                                                <p class="text-xs text-slate-500 mt-2">
                                                    {{ $kpi['kpi_description'] ?? 'No description available.' }}
                                                </p>

                                                @if($quarterStatus)

                                                    <div class="mt-3">

                                                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black {{ $quarterStatusColor }}">

                                                            @if($quarterStatus == 'ongoing')

                                                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>

                                                            @elseif($quarterStatus == 'not_started')

                                                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>

                                                            @elseif($quarterStatus == 'ended')

                                                                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>

                                                            @elseif($quarterStatus == 'today')

                                                                <div class="w-1.5 h-1.5 rounded-full bg-orange-500"></div>

                                                            @endif

                                                            {{ $quarterStatusText }}

                                                        </div>

                                                    </div>

                                                @endif

                                            </div>

                                            <!-- RIGHT -->
                                            <div class="w-full xl:w-[220px]">

                                                <div class="flex items-center justify-between mb-2">

                                                    <p class="text-xs uppercase font-black text-slate-400">
                                                        Performance
                                                    </p>

                                                    <p class="text-[15px] font-black {{ $progressText }}">
                                                        {{ number_format($achievement,2) }}%
                                                    </p>

                                                </div>

                                                <div class="h-3 bg-slate-100 rounded-full overflow-hidden">

                                                    <div
                                                        class="h-3 rounded-full bg-gradient-to-r {{ $progressColor }}"
                                                        style="width: {{ min(100,$achievement) }}%; transition: width .5s ease;">
                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            </div>

        @endforeach

        </div>

        <!-- NO RESULT -->
        <div id="noFilterResult"
            class="hidden glass rounded-[18px] border border-slate-200 p-10 text-center mt-4">

            <h3 class="text-xl font-black text-slate-900">
                No KPI Found
            </h3>

            <p class="text-xs text-slate-500 mt-2">
                Try changing search, category or status filter.
            </p>

        </div>

</main>

<script>
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const visibleCount = document.getElementById('visibleCount');
    const cards = document.querySelectorAll('.kpi-card');
    const noFilterResult = document.getElementById('noFilterResult');

    function filterRows() {
        const searchValue = searchInput.value.toLowerCase().trim();
        const categoryValue = categoryFilter.value;
        const statusValue = statusFilter.value;

        let count = 0;

        cards.forEach(function(card) {
            const categoryData = card.dataset.category || '';
            const statusData = card.dataset.status || '';
            const searchData = card.dataset.search || '';
            const matchesSearch = searchData.includes(searchValue);
            const matchesCategory = !categoryValue || categoryData === categoryValue;
            const matchesStatus = !statusValue || statusData === statusValue;

            if (matchesSearch && matchesCategory && matchesStatus) {
                card.classList.remove('hidden');
                count++;
            } else {
                card.classList.add('hidden');
            }
        });

        visibleCount.textContent = count;

        if (cards.length > 0 && count === 0) {
            noFilterResult.classList.remove('hidden');
        } else {
            noFilterResult.classList.add('hidden');
        }
    }

    searchInput.addEventListener('input', filterRows);
    categoryFilter.addEventListener('change', filterRows);
    statusFilter.addEventListener('change', filterRows);

    function openQuarterModal(id) {

        const modal = document.getElementById('quarterUpdateModal-' + id);

        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.body.classList.add('overflow-hidden');
    }

    function closeQuarterModal(id) {

        const modal = document.getElementById('quarterUpdateModal-' + id);

        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        document.body.classList.remove('overflow-hidden');
    }

    function openEditApprovalModal(id) {

        const modal = document.getElementById('editApprovalModal-' + id);

        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.body.classList.add('overflow-hidden');
    }

    function closeEditApprovalModal(id) {

        const modal = document.getElementById('editApprovalModal-' + id);

        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        document.body.classList.remove('overflow-hidden');
    }

    function toggleStaff(id){

        const panel = document.getElementById('staff-' + id);

        if(!panel) return;

        panel.classList.toggle('hidden');

        if(panel.classList.contains('hidden')){
            document.body.classList.remove('overflow-hidden');
        }
        else{
            document.body.classList.add('overflow-hidden');
        }
    }

    function openKpiDetail(card){

        const modal = document.getElementById('kpiDetailModal');
        const content = document.getElementById('kpiDetailContent');

        if(!modal || !content || !card) return;

        const kpi = JSON.parse(card.dataset.kpi || '{}');

        const quarters = kpi.quarters || [];

        let quarterHtml = '';

        ['Q1','Q2','Q3','Q4'].forEach(function(q){

            const quarter = quarters.find(x => x.quarter === q) || {};

            let timelineStatus = 'No Timeline';
            let timelineColor = 'text-slate-500';

            const today = new Date();
            today.setHours(0,0,0,0);

            let isExpired = false;

            let isEditable = false;

            let startDate = null;

            let endDate = null;

            if(
                quarter.start_date &&
                quarter.end_date
            ){

                startDate = new Date(quarter.start_date);

                endDate = new Date(quarter.end_date);

                startDate.setHours(0,0,0,0);

                endDate.setHours(0,0,0,0);

                isExpired =
                    today.getTime() > endDate.getTime();

                isEditable =
                    today.getTime() >= startDate.getTime()
                    &&
                    today.getTime() <= endDate.getTime();

                const diffStart = Math.ceil(
                    (startDate - today) / (1000 * 60 * 60 * 24)
                );

                const diffEnd = Math.ceil(
                    (endDate - today) / (1000 * 60 * 60 * 24)
                );

                if(today < startDate){

                    timelineStatus =
                        'Starts in ' + diffStart + ' days';

                    timelineColor = 'text-blue-600';
                }
                else if(today > endDate){

                    timelineStatus = 'Ended';

                    timelineColor = 'text-red-600';
                }
                else if(diffEnd === 0){

                    timelineStatus = 'Ends Today';

                    timelineColor = 'text-orange-600';
                }
                else{

                    timelineStatus =
                        diffEnd + ' days left';

                    timelineColor = 'text-emerald-600';
                }
            }
            const target = parseFloat(quarter.quarter_target || 0);
            const actual = parseFloat(quarter.quarter_actual || 0);

            const score = target > 0
                ? ((actual / target) * 100).toFixed(1)
                : 0;

            const safeScore = Math.max(
                0,
                Math.min(parseFloat(score), 200)
            );

            const width = Math.min(safeScore, 100);

            quarterHtml += `

            <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">

                <!-- HEADER -->
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">

                    <div>

                        <div class="flex items-center gap-2">

                            <div class="w-9 h-9 rounded-2xl bg-indigo-100 text-indigo-700 font-black text-sm flex items-center justify-center">
                                ${q}
                            </div>

                            <div>

                                <h3 class="text-sm font-black text-slate-900">
                                    ${quarter.quarter_title || 'Quarter KPI'}
                                </h3>

                                <p class="text-xs text-slate-400 mt-1">
                                    ${timelineStatus}
                                </p>

                            </div>

                        </div>

                    </div>

                    <div class="text-right">

                        <p class="text-[10px] uppercase text-slate-400 font-black">
                            Achievement
                        </p>

                        <h2 class="text-xl font-black ${timelineColor}">
                            ${safeScore.toFixed(1)}%
                        </h2>

                    </div>

                </div>

                <!-- BODY -->
                <div class="p-5">

                    <p class="text-sm text-slate-500 leading-relaxed">
                        ${quarter.quarter_description || 'No quarter description'}
                    </p>

                    <!-- TARGET -->
                    <div class="grid grid-cols-2 gap-4 mt-5">

                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Quarter Target
                            </p>

                            <h3 class="text-xl font-black text-slate-900 mt-2">
                                ${target}
                            </h3>

                        </div>

                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Actual
                            </p>

                            <h3 class="text-xl font-black text-emerald-600 mt-2">
                                ${actual}
                            </h3>

                        </div>

                    </div>

                    <!-- TIMELINE -->
                    <div class="grid grid-cols-2 gap-4 mt-4">

                        <div>

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Start Date
                            </p>

                            <p class="text-sm font-bold text-slate-800 mt-2">
                                ${quarter.start_date || '-'}
                            </p>

                        </div>

                        <div>

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                End Date
                            </p>

                            <p class="text-sm font-bold text-slate-800 mt-2">
                                ${quarter.end_date || '-'}
                            </p>

                        </div>

                    </div>

                    <!-- UPDATE SECTION -->
                    <div class="mt-5">

                        <div class="flex items-center justify-between mb-2">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Quarter Update
                            </p>

                            ${
                                isEditable
                                ?
                                `
                                <div class="px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black">
                                    Editable
                                </div>
                                `
                                :
                                isExpired
                                ?
                                `
                                <div class="px-2 py-1 rounded-full bg-red-50 text-red-700 text-[10px] font-black">
                                    Approval Required
                                </div>
                                `
                                :
                                `
                                <div class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black">
                                    Upcoming Quarter
                                </div>
                                `
                            }

                        </div>

                        <div class="space-y-4">

                            <!-- ACTUAL -->
                            <div>

                                <label class="text-[10px] uppercase text-slate-400 font-black">
                                    Quarter Actual
                                </label>

                                <input
                                    id="actual-${kpi.id}-${q}"
                                    type="number"
                                    value="${actual}"
                                    ${isEditable ? '' : 'disabled'}
                                    class="
                                        w-full mt-2 h-[46px]
                                        rounded-2xl
                                        border border-slate-200
                                        px-4
                                        text-sm
                                        font-bold
                                        ${isEditable
                                            ? 'bg-white text-slate-900'
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                        }
                                    "
                                >

                            </div>

                            <!-- REMARK -->
                            <div>

                                <label class="text-[10px] uppercase text-slate-400 font-black">
                                    Remark
                                </label>

                                <textarea
                                    id="remark-${kpi.id}-${q}"
                                    ${isEditable ? '' : 'disabled'}
                                    class="
                                        w-full mt-2 rounded-2xl border border-slate-200
                                        p-4 text-sm
                                        ${isEditable
                                            ? 'bg-white text-slate-900'
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                        }
                                    "
                                    rows="4"
                                >${quarter.remark || ''}</textarea>

                            </div>

                            ${
                                isEditable
                                ?
                                `
                                <button
                                    onclick="event.stopPropagation(); updateQuarter(
                                        '${kpi.id}',
                                        '${q}'
                                    )"
                                    class="w-full h-[44px] rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black">

                                    Update Quarter

                                </button>
                                `
                                :
                                isExpired
                                ?
                                `
                                <button
                                    onclick="event.stopPropagation(); requestApproval(
                                        '${kpi.id}',
                                        '${q}'
                                    )"
                                    class="w-full h-[44px] rounded-2xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-black">

                                    Request Approval

                                </button>
                                `
                                :
                                `
                                <button
                                    disabled
                                    class="w-full h-[44px] rounded-2xl bg-slate-200 text-slate-500 text-xs font-black cursor-not-allowed">

                                    Quarter Not Started Yet

                                </button>
                                `
                            }

                        </div>

                    </div>

                    <!-- PERFORMANCE -->
                    <div class="mt-5">

                        <div class="flex items-center justify-between mb-2">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Quarter Performance
                            </p>

                            <p class="text-sm font-black text-slate-900">
                                ${safeScore.toFixed(1)}%
                            </p>

                        </div>

                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">

                            <div
                                class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600"
                                style="width:${width}%">
                            </div>

                        </div>

                    </div>

                </div>

            </div>
            `;
        });

        content.innerHTML = `

        <div class="min-h-screen bg-[#f8fafc]">

            <!-- HEADER -->
            <div class="sticky top-0 z-30 bg-white border-b border-slate-200 px-6 py-4">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <div class="flex items-center gap-2 mb-2">

                            <div class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black">
                                ${kpi.category || 'General'}
                            </div>

                            <div class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-black">
                                ${kpi.sub_category || 'Sub Category'}
                            </div>

                            <div class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-black">
                                ${kpi.financial_year || 'FY'}
                            </div>

                        </div>

                        <h2 class="text-2xl font-black text-slate-900">
                            ${kpi.kpi_title || '-'}
                        </h2>

                        <p class="text-sm text-slate-500 mt-2 max-w-3xl leading-relaxed">
                            ${kpi.kpi_description || 'No KPI description available'}
                        </p>

                    </div>

                    <button
                        onclick="closeKpiDetail()"
                        class="w-11 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xl font-black flex items-center justify-center">

                        ×

                    </button>

                </div>

            </div>

            <!-- BODY -->
            <div class="p-6 grid grid-cols-1 xl:grid-cols-12 gap-5">

                <!-- LEFT -->
                <div class="xl:col-span-8 space-y-5">

                    ${quarterHtml}

                </div>

                <!-- RIGHT -->
                <div class="xl:col-span-4 space-y-5">

                    <!-- KPI INFO -->
                    <div class="rounded-3xl overflow-hidden bg-gradient-to-br from-[#06142f] via-blue-900 to-indigo-900 text-white shadow-xl">

                        <div class="p-5">

                            <p class="text-[11px] uppercase tracking-wider text-blue-200 font-black">
                                KPI Owner
                            </p>

                            <h2 class="text-2xl font-black mt-2">
                                ${kpi.employee_name || '-'}
                            </h2>

                            <div class="grid grid-cols-2 gap-3 mt-6">

                                <div class="bg-white/10 rounded-2xl p-4">

                                    <p class="text-[10px] uppercase text-blue-200 font-black">
                                        Weightage
                                    </p>

                                    <h3 class="text-2xl font-black mt-2">
                                        ${kpi.weightage || 0}%
                                    </h3>

                                </div>

                                <div class="bg-white/10 rounded-2xl p-4">

                                    <p class="text-[10px] uppercase text-blue-200 font-black">
                                        KPI Status
                                    </p>

                                    <h3 class="text-sm font-black mt-3">
                                        ${(kpi.status || 'not_started').replaceAll('_',' ')}
                                    </h3>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- TARGET SUMMARY -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-5">

                        <div class="flex items-center justify-between mb-4">

                            <h3 class="text-sm font-black text-slate-900">
                                KPI Summary
                            </h3>

                        </div>

                        <div class="space-y-4">

                            <div class="flex items-center justify-between">

                                <div>
                                    <p class="text-[10px] uppercase text-slate-400 font-black">
                                        Base Target
                                    </p>

                                    <p class="text-lg font-black text-slate-900 mt-1">
                                        ${kpi.base_target || 0}
                                    </p>
                                </div>

                                <div class="text-right">
                                    <p class="text-[10px] uppercase text-slate-400 font-black">
                                        Stretch Target
                                    </p>

                                    <p class="text-lg font-black text-indigo-700 mt-1">
                                        ${kpi.stretch_target || 0}
                                    </p>
                                </div>

                            </div>

                            <div class="border-t border-slate-100 pt-4">

                                <div class="flex items-center justify-between">

                                    <div>
                                        <p class="text-[10px] uppercase text-slate-400 font-black">
                                            Overall Actual
                                        </p>

                                        <p class="text-lg font-black text-emerald-600 mt-1">
                                            Auto Calculated
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-[10px] uppercase text-slate-400 font-black">
                                            Unit
                                        </p>

                                        <p class="text-sm font-black text-slate-900 mt-2">
                                            ${kpi.unit || '-'}
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    ${
                        ['ADMIN','SLT','CCO','CCMO','VP','MANAGER']
                        .includes('{{ strtoupper($user["role"]) }}')

                        ?

                        `

                        <!-- KPI ACTION -->
                        <div class="bg-white rounded-3xl border border-slate-200 p-5">

                            <div class="flex items-center justify-between mb-4">

                                <h3 class="text-sm font-black text-slate-900">
                                    KPI Action
                                </h3>

                            </div>

                            ${
                                kpi.has_pending_edit_request

                                ?

                                `
                                <div class="w-full h-[46px] rounded-2xl bg-blue-50 text-blue-700 text-sm font-black flex items-center justify-center border border-blue-100">

                                    Edit Approval Pending

                                </div>
                                `

                                :

                                `
                                <button
                                    onclick="openEditTargetModal(
                                        '${kpi.id}',
                                        '${kpi.base_target || 0}',
                                        '${kpi.stretch_target || 0}'
                                    )"
                                    class="w-full h-[46px] rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-black transition-all duration-200">

                                    Request Target Edit

                                </button>
                                `
                            }

                            <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">

                                Only Base Target & Stretch Target can be edited.
                                Approval from reporting superior is required.

                            </p>

                            ${
                                kpi.has_pending_delete_request

                                ?

                                `
                                <div class="w-full mt-3 h-[46px] rounded-2xl bg-amber-50 text-amber-700 text-sm font-black flex items-center justify-center border border-amber-100">

                                    Delete Approval Pending

                                </div>
                                `

                                :

                                `
                                <button
                                    onclick="openDeleteKpiModal(
                                        '${kpi.id}',
                                        \`${kpi.kpi_title || ''}\`
                                    )"
                                    class="w-full mt-3 h-[46px] rounded-2xl bg-red-600 hover:bg-red-700 text-white text-sm font-black transition-all duration-200">

                                    Request KPI Delete

                                </button>
                                `
                            }

                            <p class="text-[11px] text-red-400 mt-3 leading-relaxed">

                                KPI deletion requires approval from superior.

                            </p>

                        </div>

                        `

                        :

                        `

                        <div class="rounded-2xl bg-slate-100 p-4 text-xs text-slate-500 font-bold">

                            KPI governance actions are restricted for your role.

                        </div>

                        `
                    }

                </div>

            </div>

        </div>
        `;

        modal.classList.remove('hidden');

        document.body.classList.add('overflow-hidden');
    }

    async function updateQuarter(kpiId, quarter){

        const button = event.target;

        const originalText = button.innerText;

        button.disabled = true;

        button.innerText = 'Updating...';

        const actualInput = document.getElementById(
            `actual-${kpiId}-${quarter}`
        );

        const remarkInput = document.getElementById(
            `remark-${kpiId}-${quarter}`
        );

        const actual = actualInput
            ? actualInput.value
            : '';

        const remark = remarkInput
            ? remarkInput.value
            : '';

        try{

            const response = await fetch('/kpi/update-quarter', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },

                body: JSON.stringify({
                    kpi_id: kpiId,
                    quarter: quarter,
                    actual: actual,
                    remark: remark
                })
            });

            const result = await response.json();

            if(result.success){

                alert('Quarter updated successfully.');

                location.reload();

            }
            else{

                alert(result.message || 'Update failed.');
            }

        }
        catch(error){

            console.error(error);

            alert('System error.');
        }
        finally{

            button.disabled = false;

            button.innerText = originalText;
        }
    }
    async function requestApproval(kpiId, quarter){

        const actualInput = document.getElementById(
            `actual-${kpiId}-${quarter}`
        );

        const remarkInput = document.getElementById(
            `remark-${kpiId}-${quarter}`
        );

        const actual = actualInput
            ? actualInput.value
            : '';

        const remark = remarkInput
            ? remarkInput.value
            : '';

        try{

            const response = await fetch(
                '/kpi/request-quarter-approval',
                {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },

                    body: JSON.stringify({
                        kpi_id: kpiId,
                        quarter: quarter,
                        actual: actual,
                        remark: remark
                    })
                }
            );

            const result = await response.json();

            if(result.success){

                alert('Approval request submitted.');

                location.reload();

            }
            else{

                alert(result.message || 'Request failed.');
            }

        }
        catch(error){

            console.error(error);

            alert('System error.');
        }
    }

    function openEditTargetModal(
        kpiId,
        baseTarget,
        stretchTarget
    ){

        document.getElementById('editKpiId').value = kpiId;

        document.getElementById('editBaseTarget').value = baseTarget;

        document.getElementById('editStretchTarget').value = stretchTarget;

        document.getElementById('editReason').value = '';

        document
            .getElementById('editTargetModal')
            .classList.remove('hidden');

        document.body.classList.add('overflow-hidden');
    }

    function closeEditTargetModal(){

        document
            .getElementById('editTargetModal')
            .classList.add('hidden');

        document.body.classList.remove('overflow-hidden');
    }

    async function submitEditTargetRequest(){

        const kpiId = document.getElementById('editKpiId').value;

        const baseTarget = document.getElementById('editBaseTarget').value;

        const stretchTarget = document.getElementById('editStretchTarget').value;

        const reason = document.getElementById('editReason').value;

        if(
            baseTarget === '' ||
            stretchTarget === ''
        ){
            alert('Please fill all target fields.');
            return;
        }

        if(reason.trim() === ''){
            alert('Reason is required.');
            return;
        }

        try{

            const response = await fetch(
                '/kpi/' + kpiId + '/request-edit',
                {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },

                    body: JSON.stringify({

                        base_target: baseTarget,

                        stretch_target: stretchTarget,

                        reason: reason

                    })
                }
            );

            const result = await response.json();

            if(result.success){

                alert('Edit request submitted.');

                location.reload();

            } else {

                alert(result.message || 'Request failed.');
            }

        } catch(error){

            console.error(error);

            alert('System error.');
        }
    }

    function openDeleteKpiModal(
        kpiId,
        title
    ){

        document.getElementById('deleteKpiId').value = kpiId;

        document.getElementById('deleteKpiTitle').innerText = title;

        document.getElementById('deleteReason').value = '';

        document
            .getElementById('deleteKpiModal')
            .classList.remove('hidden');

        document.body.classList.add('overflow-hidden');
    }

    function closeDeleteKpiModal(){

        document
            .getElementById('deleteKpiModal')
            .classList.add('hidden');

        document.body.classList.remove('overflow-hidden');
    }

    async function submitDeleteRequest(){

        const kpiId = document.getElementById('deleteKpiId').value;

        const reason = document.getElementById('deleteReason').value;

        if(reason.trim() === ''){

            alert('Reason is required.');

            return;
        }

        try{

            const response = await fetch(
                '/kpi/' + kpiId + '/request-delete',
                {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },

                    body: JSON.stringify({
                        reason: reason
                    })
                }
            );

            const result = await response.json();

            if(result.success){

                alert('Delete request submitted.');

                location.reload();

            }
            else{

                alert(result.message || 'Request failed.');
            }

        }
        catch(error){

            console.error(error);

            alert('System error.');
        }
    }

    function closeKpiDetail(){

        const modal = document.getElementById('kpiDetailModal');

        if(!modal) return;

        modal.classList.add('hidden');

        document.body.classList.remove('overflow-hidden');
    }

</script>

<!-- KPI DETAIL MODAL -->
<div
    id="kpiDetailModal"
    class="hidden fixed inset-0 z-[9999]">

    <!-- BACKDROP -->
    <div
        onclick="closeKpiDetail()"
        class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm">
    </div>

    <!-- PANEL -->
    <div class="absolute right-0 top-0 h-full w-full max-w-[1000px] bg-[#f8fafc] overflow-y-auto shadow-2xl">

        <div id="kpiDetailContent">

        </div>

    </div>

</div>

<!-- EDIT TARGET MODAL -->
<div
    id="editTargetModal"
    class="hidden fixed inset-0 z-[99999]">

    <!-- BACKDROP -->
    <div
        onclick="closeEditTargetModal()"
        class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm">
    </div>

    <!-- PANEL -->
    <div class="absolute inset-0 flex items-center justify-center p-4">

        <div class="w-full max-w-[520px] bg-white rounded-[28px] shadow-2xl overflow-hidden">

            <!-- HEADER -->
            <div class="px-6 py-5 border-b border-slate-100">

                <h2 class="text-xl font-black text-slate-900">
                    Request KPI Target Edit
                </h2>

                <p class="text-xs text-slate-500 mt-2">
                    Changes require approval from superior.
                </p>

            </div>

            <!-- BODY -->
            <div class="p-6 space-y-5">

                <input type="hidden" id="editKpiId">

                <div>

                    <label class="text-[10px] uppercase font-black text-slate-400">
                        Base Target
                    </label>

                    <input
                        id="editBaseTarget"
                        type="number"
                        class="w-full mt-2 h-[48px] rounded-2xl border border-slate-200 px-4 text-sm font-bold"
                    >

                </div>

                <div>

                    <label class="text-[10px] uppercase font-black text-slate-400">
                        Stretch Target
                    </label>

                    <input
                        id="editStretchTarget"
                        type="number"
                        class="w-full mt-2 h-[48px] rounded-2xl border border-slate-200 px-4 text-sm font-bold"
                    >

                </div>

                <div>

                    <label class="text-[10px] uppercase font-black text-slate-400">
                        Reason
                    </label>

                    <textarea
                        id="editReason"
                        rows="5"
                        class="w-full mt-2 rounded-2xl border border-slate-200 p-4 text-sm"
                        placeholder="Why target needs revision..."
                    ></textarea>

                </div>

            </div>

            <!-- FOOTER -->
            <div class="px-6 py-5 border-t border-slate-100 flex items-center gap-3">

                <button
                    onclick="closeEditTargetModal()"
                    class="flex-1 h-[46px] rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black">

                    Cancel

                </button>

                <button
                    onclick="submitEditTargetRequest()"
                    class="flex-1 h-[46px] rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black">

                    Submit Request

                </button>

            </div>

        </div>

    </div>

</div>

<!-- DELETE KPI MODAL -->
<div
    id="deleteKpiModal"
    class="hidden fixed inset-0 z-[999999]">

    <!-- BACKDROP -->
    <div
        onclick="closeDeleteKpiModal()"
        class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm">
    </div>

    <!-- PANEL -->
    <div class="absolute inset-0 flex items-center justify-center p-4">

        <div class="w-full max-w-[520px] bg-white rounded-[28px] shadow-2xl overflow-hidden">

            <!-- HEADER -->
            <div class="px-6 py-5 border-b border-slate-100">

                <h2 class="text-xl font-black text-red-700">
                    Request KPI Delete
                </h2>

                <p class="text-xs text-slate-500 mt-2">
                    This action requires approval from superior.
                </p>

            </div>

            <!-- BODY -->
            <div class="p-6 space-y-5">

                <input type="hidden" id="deleteKpiId">

                <div>

                    <label class="text-[10px] uppercase font-black text-slate-400">
                        KPI
                    </label>

                    <div
                        id="deleteKpiTitle"
                        class="mt-2 rounded-2xl bg-slate-100 p-4 text-sm font-black text-slate-900">
                    </div>

                </div>

                <div>

                    <label class="text-[10px] uppercase font-black text-slate-400">
                        Reason
                    </label>

                    <textarea
                        id="deleteReason"
                        rows="5"
                        class="w-full mt-2 rounded-2xl border border-slate-200 p-4 text-sm"
                        placeholder="Why KPI should be deleted..."
                    ></textarea>

                </div>

            </div>

            <!-- FOOTER -->
            <div class="px-6 py-5 border-t border-slate-100 flex items-center gap-3">

                <button
                    onclick="closeDeleteKpiModal()"
                    class="flex-1 h-[46px] rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black">

                    Cancel

                </button>

                <button
                    onclick="submitDeleteRequest()"
                    class="flex-1 h-[46px] rounded-2xl bg-red-600 hover:bg-red-700 text-white text-sm font-black">

                    Submit Delete Request

                </button>

            </div>

        </div>

    </div>

</div>

</body>
</html>
