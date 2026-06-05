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
        $myKpis = $individualKpiRows;
        $individualKpiCount = $individualKpiRows->count();
        $individualTotalWeightage = round($individualKpiRows->sum(fn($item) => (float) ($item['weightage'] ?? 0)), 2);
        $individualPerformance = $individualKpiRows->sum(
            function ($item) use ($calculateDashboardKpiScore) {
                $score = $calculateDashboardKpiScore($item);
                $weightage = max(
                    0,
                    (float) ($item['weightage'] ?? 0)
                );
                return ($score * $weightage) / 100;
            }
        );

        $individualPerformanceDisplay =
        round(
            $individualPerformance,
            1
        );

        $individualPerformanceWidth =
        max(
            0,
            min(
                100,
                $individualPerformanceDisplay
            )
        );

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
                    <h3
                        id="individualPerformanceText"
                        class="text-3xl font-black {{ $individualPerformanceText }} mt-1">

                        {{ number_format($individualPerformanceDisplay, 1) }}%

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
        </div>

        <!-- FY -->
        <div class="glass card-hover p-4 rounded-[18px] border border-emerald-100 bg-gradient-to-br from-white via-emerald-50 to-green-50 shadow-sm">

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase">
                        Financial Year
                    </p>

                    <h3 class="text-2xl font-black text-emerald-700 mt-1">
                        {{ $fy }}
                    </h3>
                </div>
            </div>

        </div>

        <!-- TOTAL KPI -->
        <div class="glass card-hover p-4 rounded-[18px] border border-violet-100 bg-gradient-to-br from-white via-violet-50 to-purple-50 shadow-sm">

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase">
                        Total KPI
                    </p>

                    <h3 class="text-2xl font-black text-violet-700 mt-1">
                        {{ $individualKpiCount }}
                    </h3>
                </div>
            </div>

        </div>

        <!-- WEIGHTAGE -->
        <div class="glass card-hover p-4 rounded-[18px] border border-amber-100 bg-gradient-to-br from-white via-amber-50 to-yellow-50 shadow-sm">

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase">
                        Weightage
                    </p>

                    <h3 class="text-2xl font-black
                        {{ $individualTotalWeightage == 100 ? 'text-emerald-700' : ($individualTotalWeightage > 100 ? 'text-red-700' : 'text-amber-700') }}">
                        {{ number_format($individualTotalWeightage,2) }}%
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="glass rounded-[20px] shadow-sm border border-indigo-100 bg-gradient-to-r from-white via-indigo-50/40 to-blue-50/40 p-5">
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
        </div>
    </div>

    @php

        $myKpis = $individualKpiRows;

        $groupedKpis =
        $myKpis->groupBy('category');

    @endphp

    <div class="space-y-4">

        @forelse($groupedKpis as $category => $categoryKpis)

        @php

            $headerStyle = match($category){

                'Financial'
                    => 'bg-emerald-700',

                'Growth & Customer'
                    => 'bg-indigo-700',

                'Initiatives'
                    => 'bg-amber-600',

                'People'
                    => 'bg-pink-700',

                default
                    => 'bg-slate-700'
            };

            @endphp

            <div class="category-group">

            <div class="category-header {{ $headerStyle }} rounded-[24px] text-white px-6 py-5 mb-4 ">
                <h2 class="text-xl font-black">
                    {{ strtoupper($category) }}
                </h2>

                <p class="text-sm text-white/80">
                    {{ count($categoryKpis) }} KPI
                </p>

            </div>

            @foreach($categoryKpis as $kpi)

            @php

                $quarters = collect($kpi['quarters'] ?? []);

                $baseTotal = 0;
                $actualTotal = 0;

                $latestActual = 0;

                foreach(['Q1','Q2','Q3','Q4'] as $quarterName){

                    $quarter = $quarters->firstWhere('quarter', $quarterName) ?? [];

                    $targetValue = (float)($quarter['quarter_target'] ?? 0);
                    $actualValue = (float)($quarter['quarter_actual'] ?? 0);

                    $baseTotal += $targetValue;
                    $actualTotal += $actualValue;

                    if($actualValue > 0){
                        $latestActual = $actualValue;
                    }
                }

                if($latestActual <= 0){
                    $latestActual = (float)($kpi['actual_value'] ?? 0);
                }

                $achievement = $baseTotal > 0
                    ? round(($actualTotal / $baseTotal) * 100,2)
                    : 0;

                if($achievement >= 90){
                    $progressText = 'text-emerald-600';
                }
                elseif($achievement >= 75){
                    $progressText = 'text-yellow-600';
                }
                elseif($achievement >= 50){
                    $progressText = 'text-orange-600';
                }
                else{
                    $progressText = 'text-red-600';
                }
                if($achievement >= 90){
                    $achievementBadge =
                    'bg-emerald-100 text-emerald-700';

                    $achievementLabel =
                    'Excellent';
                }
                elseif($achievement >= 75){

                    $achievementBadge =
                    'bg-yellow-100 text-yellow-700';

                    $achievementLabel =
                    'Watch';

                }
                elseif($achievement >= 50){

                    $achievementBadge =
                    'bg-orange-100 text-orange-700';

                    $achievementLabel =
                    'Risk';

                }
                else{

                    $achievementBadge =
                    'bg-red-100 text-red-700';

                    $achievementLabel =
                    'Critical';
                }

            @endphp

            @php
                $categoryStyle = match($kpi['category'] ?? '') {
                    'Financial'
                        => 'bg-emerald-700 text-white',

                    'Growth & Customer'
                        => 'bg-indigo-700 text-white',

                    'Initiatives'
                        => 'bg-amber-600 text-white',

                    'People'
                        => 'bg-pink-700 text-white',

                    default
                        => 'bg-slate-700 text-white'
                };

                $buttonStyle = match($kpi['category'] ?? '') {

                    'Financial'
                        => 'from-emerald-600 to-emerald-700',

                    'Growth & Customer'
                        => 'from-indigo-600 to-indigo-700',

                    'Initiatives'
                        => 'from-amber-500 to-amber-700',

                    'People'
                        => 'from-pink-600 to-pink-700',

                    default
                        => 'from-slate-600 to-slate-700'
                };

                if($achievement >= 90){
                    $progressBar =
                    'from-emerald-500 to-green-600';
                }
                elseif($achievement >= 75){
                    $progressBar =
                    'from-yellow-400 to-yellow-600';
                }
                elseif($achievement >= 50){
                    $progressBar =
                    'from-orange-400 to-orange-600';
                }
                else{
                    $progressBar =
                    'from-red-500 to-red-700';
                }

                $quarterColors = [];

                foreach(['Q1','Q2','Q3','Q4'] as $q){

                    $quarter = $quarters->firstWhere('quarter',$q) ?? [];

                    $target = (float)($quarter['quarter_target'] ?? 0);
                    $actual = (float)($quarter['quarter_actual'] ?? 0);

                    $score =
                        $target > 0
                        ? ($actual / $target) * 100
                        : 0;

                    if($score >= 90){

                        $quarterColors[$q] =
                        'bg-green-500 text-white';

                    }
                    elseif($score >= 75){

                        $quarterColors[$q] =
                        'bg-yellow-500 text-white';

                    }
                    elseif($score >= 50){

                        $quarterColors[$q] =
                        'bg-orange-500 text-white';

                    }
                    elseif($score > 0){

                        $quarterColors[$q] =
                        'bg-red-500 text-white';

                    }
                    else{

                        $quarterColors[$q] =
                        'bg-slate-200 text-slate-500';
                    }
                }
            @endphp

            <div
                onclick="openKpiDetail(this)"
                class="
                    kpi-card
                    cursor-pointer
                    bg-white
                    border
                    border-slate-200
                    rounded-[24px]
                    p-6
                    shadow-sm
                    hover:shadow-2xl
                    hover:scale-[1.01]
                    hover:-translate-y-1
                    transition-all
                    duration-300
                "
                data-search="{{ strtolower(($kpi['kpi_title'] ?? '') . ' ' . ($kpi['category'] ?? '')) }}"
                data-category="{{ $kpi['category'] ?? '' }}"
                data-status="{{ $kpi['status'] ?? '' }}"
                data-kpi='@json($kpi)'
            >

                <!-- TOP -->
                <div class="flex items-start justify-between gap-5">

                    <!-- LEFT -->
                    <div class="flex-1 min-w-0">

                        <div class="flex flex-wrap items-center gap-2 mb-3">

                            <span class="px-3 py-1 rounded-full {{ $categoryStyle }} text-[10px] font-black">
                                {{ $kpi['category'] ?? 'General' }}
                            </span>

                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black">
                                {{ $kpi['sub_category'] ?? 'Sub Category' }}
                            </span>

                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black">
                                {{ $kpi['financial_year'] ?? '-' }}
                            </span>

                        </div>

                        <h3 class="text-xl font-black text-slate-900">
                            {{ $kpi['kpi_title'] ?? 'Untitled KPI' }}
                        </h3>

                        <p class="text-xs text-slate-500 mt-2 leading-relaxed max-w-3xl">
                            {{ $kpi['kpi_description'] ?? 'No description available.' }}
                        </p>

                    </div>

                    <!-- RIGHT -->
                    <div class="text-right shrink-0">

                        <p class="text-[10px] uppercase text-slate-400 font-black">
                            Performance
                        </p>

                        <h2 class="text-2xl font-black {{ $progressText }} mt-1">
                            {{ number_format($achievement,1) }}%
                        </h2>

                        <div
                            class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black {{ $achievementBadge }}">
                            {{ $achievementLabel }}
                        </div>

                    </div>

                </div>

                <!-- PROGRESS -->
                <div class="mt-4">

                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">

                        <div
                            class="h-2 rounded-full bg-gradient-to-r {{ $progressBar }}"
                            style="width: {{ max(3,min(100,$achievement)) }}%">
                        </div>

                    </div>

                </div>

                <div class="flex gap-2 mt-3">

                @foreach(['Q1','Q2','Q3','Q4'] as $q)

                    <span class="
                        w-8
                        h-8
                        rounded-lg
                        text-[10px]
                        font-black
                        flex
                        items-center
                        justify-center
                        {{ $quarterColors[$q] }}
                    ">
                        {{ $q }}
                    </span>

                @endforeach

            </div>

                <!-- KPI META -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">

                    <div class="rounded-2xl bg-slate-50 border-slate-100 border px-4 py-3">

                        <p class="text-[10px] uppercase text-slate-400 font-black">
                            Weightage
                        </p>

                        <p class="text-sm font-black text-slate-900 mt-1">
                            {{ number_format($kpi['weightage'] ?? 0,0) }}%
                        </p>

                    </div>

                    @php

                        $actualDisplay = match($kpi['unit'] ?? '') {

                            'currency'
                                => number_format($latestActual,2),

                            'percentage'
                                => number_format($latestActual,2).' %',

                            default
                                => number_format($latestActual,0)

                        };

                    @endphp

                    <div class="rounded-2xl bg-slate-50 border-slate-100 border px-4 py-3">

                        <p class="text-[10px] uppercase text-slate-400 font-black">
                            Actual
                        </p>

                        <p class="text-lg font-black text-slate-900">
                            {{ $actualDisplay }}
                        </p>

                    </div>

                    <button
                        onclick='
                            event.stopPropagation();
                            openEditKpiModal(@json($kpi));
                        '
                        class="
                            px-4
                            py-3
                            rounded-2xl
                            bg-indigo-600
                            hover:bg-indigo-700
                            text-white
                            text-xs
                            font-black
                        "
                    >
                        Edit KPI
                    </button>

                    <button
                        onclick="
                            event.stopPropagation();
                            openDeleteKpiModal(
                                '{{ $kpi['id'] }}',
                                '{{ addslashes($kpi['kpi_title']) }}'
                            );
                        "
                        class="
                            px-4
                            py-3
                            rounded-2xl
                            bg-red-600
                            hover:bg-red-700
                            text-white
                            text-xs
                            font-black
                        "
                    >
                        Delete KPI
                    </button>

                </div>

            </div>
            @endforeach

            </div>

        @empty

            <div class="bg-white border border-dashed border-slate-300 rounded-[24px] p-16 text-center">

                <h3 class="text-2xl font-black text-slate-900">
                    No KPI Created Yet
                </h3>

                <p class="text-sm text-slate-500 mt-3">
                    Start creating KPI for your yearly execution tracking.
                </p>

                </div>

            @endforelse

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

        document
            .querySelectorAll('.category-group')
            .forEach(group => {

                const visibleCards =
                    group.querySelectorAll(
                        '.kpi-card:not(.hidden)'
                    );

                if(visibleCards.length === 0){

                    group.classList.add('hidden');

                }else{

                    group.classList.remove('hidden');
                }

            });

        if (cards.length > 0 && count === 0) {
            noFilterResult.classList.remove('hidden');
        } else {
            noFilterResult.classList.add('hidden');
        }
    }

    searchInput.addEventListener('input', filterRows);
    categoryFilter.addEventListener('change', filterRows);
    statusFilter.addEventListener('change', filterRows);

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

    let activeKpi = null;

function openKpiDetail(card){

    const modal = document.getElementById('kpiDetailModal');
    const content = document.getElementById('kpiDetailContent');

    if(!modal || !content || !card) return;

    activeKpi = JSON.parse(
        card.dataset.kpi || '{}'
    );

    renderKpiDetail('Q1');

    modal.classList.remove('hidden');

    document.body.classList.add('overflow-hidden');
}

function renderKpiDetail(activeQuarter){

    const content = document.getElementById(
        'kpiDetailContent'
    );

    const kpi = activeKpi;

    const timeline =
    kpi.approval_timeline || [];

    let timelineHtml = '';

    timeline.forEach(item => {

        let dotColor = 'bg-yellow-500';

        if(
            (item.status || '').toLowerCase()
            === 'approved'
        ){
            dotColor = 'bg-emerald-500';
        }

        if(
            (item.status || '').toLowerCase()
            === 'rejected'
        ){
            dotColor = 'bg-red-500';
        }

        timelineHtml += `

            <div class="flex gap-4">

                <div
                    class="w-3 h-3 rounded-full mt-2 ${dotColor}">
                </div>

                <div class="flex-1">

                   <div class="font-black">
                        ${(item.status || '-')
                            .charAt(0)
                            .toUpperCase()
                            +
                            (item.status || '-')
                            .slice(1)}
                    </div>

                    <div class="text-xs text-slate-500">

                        ${item.type || '-'}

                    </div>

                    <div class="text-xs text-slate-500">

                        Requested By:
                        ${item.by || '-'}

                    </div>

                    <div class="text-xs text-slate-500">

                        Approver:
                        ${item.approver || '-'}

                    </div>

                    <div class="text-xs text-slate-400">

                        ${item.date || '-'}

                    </div>

                </div>

            </div>

        `;
    });

    if(!kpi || !content) return;

    const quarters = kpi.quarters || [];

    const quarter =
        quarters.find(
            q => q.quarter === activeQuarter
        ) || {};

    const today =
        new Date();

    const startDate =
        new Date(
            quarter.start_date
        );

    const endDate =
        new Date(
            quarter.end_date
        );

    const beforeQuarter =
        today < startDate;

    const afterQuarter =
        today > endDate;

    const completed =
        quarter.status === 'completed';

    const reasonRequired =
        afterQuarter || completed;

    const target = parseFloat(
        quarter.quarter_target || 0
    );

    const actual = parseFloat(
        quarter.quarter_actual || 0
    );

    const score =
        target > 0
        ? Number(((actual / target) * 100).toFixed(1))
        : 0;

    let scoreColor = 'text-red-600';
    let progressBar = 'from-red-500 to-red-700';

    if(score >= 90){

        scoreColor = 'text-emerald-600';
        progressBar = 'from-emerald-500 to-green-600';

    }
    else if(score >= 75){

        scoreColor = 'text-yellow-600';
        progressBar = 'from-yellow-400 to-yellow-600';

    }
    else if(score >= 50){

        scoreColor = 'text-orange-600';
        progressBar = 'from-orange-400 to-orange-600';

    }

    let categoryClass = 'bg-slate-700 text-white';
    let categoryLight = 'bg-slate-100 text-slate-700';
    let categoryGradient = 'from-slate-500 to-slate-700';
    let categoryButton = 'bg-slate-700 hover:bg-slate-800';
    let ownerCardGradient = 'from-slate-700 via-slate-800 to-slate-900';

    if(kpi.category === 'Financial'){
        categoryClass = 'bg-emerald-700 text-white';
        categoryLight = 'bg-emerald-100 text-emerald-700';
        categoryGradient = 'from-emerald-500 to-emerald-700';
        categoryButton = 'bg-emerald-600 hover:bg-emerald-700';
        ownerCardGradient = 'from-emerald-700 via-emerald-800 to-green-900';
    }
    else if(kpi.category === 'Growth & Customer'){
        categoryClass = 'bg-indigo-700 text-white';
        categoryLight = 'bg-indigo-100 text-indigo-700';
        categoryGradient = 'from-indigo-500 to-indigo-700';
        categoryButton = 'bg-indigo-600 hover:bg-indigo-700';
        ownerCardGradient = 'from-indigo-700 via-indigo-800 to-blue-900';
    }
    else if(kpi.category === 'Initiatives'){
        categoryClass = 'bg-amber-600 text-white';
        categoryLight = 'bg-amber-100 text-amber-700';
        categoryGradient = 'from-amber-500 to-amber-700';
        categoryButton = 'bg-amber-600 hover:bg-amber-700';
        ownerCardGradient = 'from-amber-600 via-amber-700 to-orange-900';
    }
    else if(kpi.category === 'People'){
        categoryClass = 'bg-pink-700 text-white';
        categoryLight = 'bg-pink-100 text-pink-700';
        categoryGradient = 'from-pink-500 to-pink-700';
        categoryButton = 'bg-pink-600 hover:bg-pink-700';
        ownerCardGradient = 'from-pink-700 via-pink-800 to-rose-900';
    }

    let tabs = '';

    ['Q1','Q2','Q3','Q4'].forEach(q => {

        const active =
            q === activeQuarter;

        tabs += `
            <button
                onclick="renderKpiDetail('${q}')"
                class="
                    quarter-tab
                    px-4
                    py-3
                    rounded-2xl
                    font-black
                    text-sm
                    ${
                        active
                        ? 'bg-slate-900 text-white'
                        : 'bg-white border border-slate-200 text-slate-600'
                    }
                ">
                ${q}
            </button>
        `;
    });

    content.innerHTML = `

    <div class="min-h-screen bg-[#f8fafc]">

        <!-- HEADER -->
        <div class="sticky top-0 z-30 bg-white border-b border-slate-200 px-6 py-5">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <div class="flex flex-wrap items-center gap-2 mb-3">

                        <div class="px-3 py-1 rounded-full text-[10px] font-black ${categoryClass}">
                            ${kpi.category || 'General'}
                        </div>

                        <div class="px-3 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">
                            ${kpi.sub_category || '-'}
                        </div>

                        <div class="px-3 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">
                            ${kpi.financial_year || '-'}
                        </div>

                    </div>

                    <h2 class="text-3xl font-black text-slate-900">
                        ${kpi.kpi_title || '-'}
                    </h2>

                    <p class="text-sm text-slate-500 mt-2 max-w-3xl">
                        ${kpi.kpi_description || 'No description'}
                    </p>

                </div>

                <button
                    onclick="closeKpiDetail()"
                    class="w-11 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-xl font-black">

                    ×

                </button>

            </div>

        </div>

        <!-- BODY -->
        <div
            class="
            p-6
            flex
            gap-6
            items-start
            "
        >

            <!-- LEFT -->
            <div
                class="
                flex-1
                min-w-0
                space-y-5
                "
            >

                <!-- QUARTER TABS -->
                <div class="flex flex-wrap gap-3">

                    ${tabs}

                </div>

                <!-- MAIN QUARTER CARD -->
                <div class="bg-white rounded-3xl border border-slate-200 p-6">

                    <div class="flex items-start justify-between gap-5">

                        <div>

                            <div class="flex items-center gap-3">

                                <div class="w-14 h-14 rounded-2xl ${categoryLight} flex items-center justify-center text-lg font-black">
                                    ${activeQuarter}
                                </div>

                                <div>

                                    <h3 class="text-xl font-black text-slate-900">
                                        ${quarter.quarter_title || 'Quarter KPI'}
                                    </h3>

                                    <p class="text-sm text-slate-500 mt-1">
                                        ${quarter.quarter_description || 'No description'}
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="text-right">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Achievement
                            </p>

                            <h2 class="text-4xl font-black ${scoreColor} mt-1">
                                ${score}%
                            </h2>

                        </div>

                    </div>

                    <!-- PERFORMANCE -->
                    <div class="mt-6">

                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">

                            <div
                                class="h-3 rounded-full bg-gradient-to-r ${progressBar}"
                                style="width:${Math.max(3,Math.min(score,100))}%">
                            </div>

                        </div>

                    </div>

                    <!-- KPI DETAILS -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">

                        <div class="rounded-2xl bg-slate-50 p-4">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Quarter Target
                            </p>

                            <h3 class="text-2xl font-black text-slate-900 mt-2">
                                ${Number(target).toLocaleString()}
                            </h3>

                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Actual
                            </p>

                            <h3 class="text-2xl font-black text-slate-900 mt-2">
                                ${Number(actual).toLocaleString()}
                            </h3>

                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                Start Date
                            </p>

                            <h3 class="text-sm font-black text-slate-900 mt-3">
                                ${quarter.start_date || '-'}
                            </h3>

                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4">

                            <p class="text-[10px] uppercase text-slate-400 font-black">
                                End Date
                            </p>

                            <h3 class="text-sm font-black text-slate-900 mt-3">
                                ${quarter.end_date || '-'}
                            </h3>

                        </div>

                    </div>

                    <!-- UPDATE FLOW -->
                    <div class="grid grid-cols-1 gap-4 mt-6">

                        <!-- STATUS -->

                        <div>

                            <label class="text-[10px] uppercase text-slate-400 font-black">

                                Status

                            </label>

                            <select
                                id="status-${quarter.id}"
                                class="
                                    w-full
                                    mt-2
                                    h-[48px]
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    px-4
                                "
                            >

                                <option
                                    value="not_started"
                                    ${(quarter.status === 'not_started')
                                        ? 'selected'
                                        : ''}
                                >
                                    Not Started
                                </option>

                                <option
                                    value="on_track"
                                    ${(quarter.status === 'on_track')
                                        ? 'selected'
                                        : ''}
                                >
                                    On Track
                                </option>

                                <option
                                    value="at_risk"
                                    ${(quarter.status === 'at_risk')
                                        ? 'selected'
                                        : ''}
                                >
                                    At Risk
                                </option>

                                <option
                                    value="in_trouble"
                                    ${(quarter.status === 'in_trouble')
                                        ? 'selected'
                                        : ''}
                                >
                                    In Trouble
                                </option>

                                <option
                                    value="completed"
                                    ${(quarter.status === 'completed')
                                        ? 'selected'
                                        : ''}
                                >
                                    Completed
                                </option>

                            </select>

                        </div>

                        <button
                            onclick="saveQuarterStatus('${quarter.id}')"
                            class="
                                h-[50px]
                                rounded-2xl
                                bg-slate-800
                                text-white
                                font-black
                            "
                        >

                            Save Status

                        </button>

                        <hr class="my-5">

                            ${!beforeQuarter ? `

                                <button
                                    onclick="
                                        toggleActualUpdate(
                                            '${quarter.id}'
                                        )
                                    "
                                    class="
                                        h-[50px]
                                        rounded-2xl
                                        bg-amber-600
                                        text-white
                                        font-black
                                    "
                                >
                                    Update Actual
                                </button>

                                ` : ''}

                            <div
                                id="actual-update-${quarter.id}"
                                class="hidden space-y-4"
                            >

                                <div>

                                    <label>

                                        New Actual

                                    </label>

                                    <input
                                        id="newActual-${quarter.id}"
                                        type="number"
                                        min="0"
                                        class="
                                            w-full
                                            mt-2
                                            h-[48px]
                                            rounded-2xl
                                            border
                                            border-slate-200
                                            px-4
                                        "
                                    >

                                </div>

                                ${reasonRequired
                                    ? `
                                    <div>

                                        <label>

                                            Reason
                                            <span class="text-red-500">*</span>

                                        </label>

                                        <textarea
                                            id="reason-${quarter.id}"
                                            rows="4"
                                            class="
                                                w-full
                                                mt-2
                                                rounded-2xl
                                                border
                                                border-slate-200
                                                p-4
                                            "
                                        ></textarea>

                                    </div>
                                    `
                                    : ''
                                    }
                                <button
                                    onclick="
                                        submitActualUpdateRequest(
                                            '${kpi.id}',
                                            '${quarter.id}'
                                        )
                                    "
                                    class="
                                        h-[50px]
                                        rounded-2xl
                                        ${categoryButton}
                                        text-white
                                        font-black
                                    "
                                >

                                    Submit Actual Update Request

                                </button>

                            </div>

                        </div>

                </div>

            </div>

            <!-- RIGHT -->
            <div
                class="
                w-[360px]
                shrink-0
                "
            >

                <div
                    class="
                    sticky
                    top-6
                    space-y-5
                    "
                >

                <!-- OWNER -->
                <div class="rounded-3xl overflow-hidden bg-gradient-to-br ${ownerCardGradient} text-white p-6">

                    <p class="text-xs uppercase text-blue-200 font-black">
                        KPI Owner
                    </p>

                    <h2 class="text-2xl font-black mt-2">
                        ${kpi.employee_name || '-'}
                    </h2>

                    <div class="grid grid-cols-2 gap-4 mt-6">

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
                                Status
                            </p>

                            <h3 class="text-sm font-black mt-3">
                                ${(quarter.status || '-').replaceAll('_',' ')}
                            </h3>

                        </div>

                    </div>

                </div>

                <!-- HISTORY -->
                <div class="bg-white rounded-3xl border border-slate-200 p-5">

                    <h3 class="text-sm font-black text-slate-900 mb-5">
                        KPI History
                    </h3>

                    <div class="space-y-4">

                        <div class="border-l-2 border-slate-300 pl-4">

                            <p class="text-xs font-black text-slate-900">
                                Quarter Updated
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                ${quarter.updated_at || 'No update yet'}
                            </p>

                        </div>

                    </div>

                </div>

                <div class="bg-white rounded-3xl border border-slate-200 p-5">

                    <h3 class="text-sm font-black text-slate-900 mb-5">
                        Approval Timeline
                    </h3>

                    ${timelineHtml || `
                        <div class="text-xs text-slate-500">
                            No approval history
                        </div>
                    `}

                </div>

                    </div>
                </div>

            </div>

        </div>

    </div>
    `;
}

async function saveQuarterStatus(
    quarterId
){

    const status =
        document.getElementById(
            'status-' + quarterId
        ).value;

    const response =
        await fetch(

            '/kpi/quarter/' +
            quarterId +
            '/status',

            {

                method : 'POST',

                headers : {

                    'Content-Type'
                        : 'application/json',

                    'X-CSRF-TOKEN'
                        : '{{ csrf_token() }}'

                },

                body : JSON.stringify({

                    status : status

                })

            }

        );

    const result =
        await response.json();

    alert(
        result.message
    );

    if(result.success){

        window.location.reload(true);

    }
}

function closeKpiDetail(){

    const modal = document.getElementById(
        'kpiDetailModal'
    );

    if(!modal) return;

    modal.classList.add('hidden');

    document.body.classList.remove(
        'overflow-hidden'
    );
}

    async function submitActualUpdateRequest(
        kpiId,
        quarterId
    ){

        const actual =
            document.getElementById(
                'newActual-' + quarterId
            ).value;

        if(!actual){

            alert(
                'New Actual required'
            );

            return;
        }

        const reasonField =
            document.getElementById(
                'reason-' + quarterId
            );

        const reason =
            reasonField
                ? reasonField.value.trim()
                : '';

        const quarter =
            activeKpi.quarters.find(
                q => q.id == quarterId
            ) || {};

        const today =
            new Date();

        const startDate =
            new Date(
                quarter.start_date
            );

        const endDate =
            new Date(
                quarter.end_date
            );

        const reasonRequired =
            (
                today > endDate
            )
            ||
            (
                quarter.status ===
                'completed'
            );

        if(
            reasonRequired &&
            reason.length < 20
        ){

            alert(
                'Reason minimum 20 characters'
            );

            return;
        }

        const response = await fetch(

            '/kpi/' +
            kpiId +
            '/quarter/' +
            quarterId +
            '/actual-request',

            {

                method : 'POST',

                headers : {

                    'Content-Type'
                        : 'application/json',

                    'X-CSRF-TOKEN'
                        : '{{ csrf_token() }}'
                },

                body : JSON.stringify({

                    requested_actual:
                        actual,

                    reason:
                        reason

                })

            }

        );

        const result =
            await response.json();

        alert(
            result.message
        );

        if(result.success){

            location.reload();
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

    let currentKpi = null;

    function openEditKpiModal(kpi){

        currentKpi = kpi;

        currentKpi.original_base =
            Number(kpi.base_target || 0);

        currentKpi.original_stretch =
            Number(kpi.stretch_target || 0);

        const modal =
            document.getElementById(
                'editKpiModal'
            );

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document
            .getElementById('edit_kpi_title')
            .value =
            kpi.kpi_title || '';

        document
            .getElementById('edit_kpi_description')
            .value =
            kpi.kpi_description || '';

        document
            .getElementById('edit_status')
            .value =
            kpi.status || '';

        document
            .getElementById('edit_base_target')
            .value =
            kpi.base_target || 0;

        document
            .getElementById('edit_stretch_target')
            .value =
            kpi.stretch_target || 0;

        document
            .getElementById(
                'targetReasonBox'
            )
            .classList
            .add('hidden');

        document
            .getElementById(
                'target_change_reason'
            )
            .value = '';

    }

    function closeEditKpiModal(){

        const modal =
            document.getElementById(
                'editKpiModal'
            );

        modal.classList.add('hidden');
        modal.classList.remove('flex');

    }

    document
        .getElementById('edit_base_target')
        .addEventListener('input', checkTargetChanged);

    document
        .getElementById('edit_stretch_target')
        .addEventListener('input', checkTargetChanged);

    function checkTargetChanged(){

        const base =
            Number(
                document.getElementById(
                    'edit_base_target'
                ).value || 0
            );

        const stretch =
            Number(
                document.getElementById(
                    'edit_stretch_target'
                ).value || 0
            );

        const changed =
            base !== currentKpi.original_base
            ||
            stretch !== currentKpi.original_stretch;

        document
            .getElementById(
                'targetReasonBox'
            )
            .classList[
                changed
                    ? 'remove'
                    : 'add'
            ]('hidden');
    }

    async function submitKpiEdit(){

        const title =
            document.getElementById(
                'edit_kpi_title'
            ).value;

        const description =
            document.getElementById(
                'edit_kpi_description'
            ).value;

        const status =
            document.getElementById(
                'edit_status'
            ).value;

        const base =
            Number(
                document.getElementById(
                    'edit_base_target'
                ).value
            );

        const stretch =
            Number(
                document.getElementById(
                    'edit_stretch_target'
                ).value
            );

        const targetChanged =

            base !== currentKpi.original_base

            ||

            stretch !== currentKpi.original_stretch;

        /*
        |--------------------------------------------------------------------------
        | TARGET CHANGE REQUEST
        |--------------------------------------------------------------------------
        */

        if(targetChanged){

            const reason =
                document.getElementById(
                    'target_change_reason'
                ).value;

            if(reason.trim().length < 30){

                alert(
                    'Reason minimum 30 characters'
                );

                return;
            }

            const response =
                await fetch(

                    `/kpi/${currentKpi.id}/request-target-change`,

                    {

                        method : 'POST',

                        headers : {

                            'Content-Type'
                                : 'application/json',

                            'X-CSRF-TOKEN'
                                : '{{ csrf_token() }}'

                        },

                        body : JSON.stringify({

                            new_base_target:
                                base,

                            new_stretch_target:
                                stretch,

                            reason:
                                reason

                        })

                    }

                );

            const result =
                await response.json();

            alert(
                result.message
            );

            if(result.success){

                location.reload();

            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL KPI UPDATE
        |--------------------------------------------------------------------------
        */

        const response =
            await fetch(

                `/kpi/${currentKpi.id}`,

                {

                    method : 'PUT',

                    headers : {

                        'Content-Type'
                            : 'application/json',

                        'X-CSRF-TOKEN'
                            : '{{ csrf_token() }}'

                    },

                    body : JSON.stringify({

                        category:
                            currentKpi.category,

                        sub_category:
                            currentKpi.sub_category,

                        unit:
                            currentKpi.unit,

                        actual_value:
                            currentKpi.actual_value,

                        kpi_title:
                            title,

                        kpi_description:
                            description,

                        status:
                            status

                    })

                }

            );

        const result =
            await response.json();

        alert(
            result.message ??
            'Updated'
        );

        if(response.ok){

            location.reload();

        }

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

        document.getElementById(
            'deleteKpiId'
        ).value = kpiId;

        document.getElementById(
            'deleteKpiTitle'
        ).innerText = title;

        document.getElementById(
            'deleteReason'
        ).value = '';

        const modal =
            document.getElementById(
                'deleteKpiModal'
            );

        modal.classList.remove(
            'hidden'
        );

        modal.classList.add(
            'flex'
        );

        document.body.classList.add(
            'overflow-hidden'
        );
    }

    function closeDeleteKpiModal(){

        const modal =
            document.getElementById(
                'deleteKpiModal'
            );

        modal.classList.add(
            'hidden'
        );

        modal.classList.remove(
            'flex'
        );

        document.body.classList.remove(
            'overflow-hidden'
        );
    }

    function toggleActualUpdate(
        quarterId
    ){

        const panel =
            document.getElementById(
                'actual-update-' +
                quarterId
            );

        if(!panel){
            return;
        }

        panel.classList.toggle(
            'hidden'
        );
    }

    async function submitDeleteRequest(){

        const kpiId = document.getElementById('deleteKpiId').value;

        const reason = document.getElementById('deleteReason').value;

        if(reason.trim().length < 30){

            alert(
                'Reason minimum 30 characters'
            );

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
    <div class="absolute right-0 top-0 h-full w-full max-w-[1400px] bg-[#f8fafc] overflow-y-auto shadow-2xl">

        <div id="kpiDetailContent">

            <div class="bg-white rounded-3xl border border-slate-200 p-5">

                <h3 class="text-sm font-black text-slate-900 mb-4">
                    KPI History
                </h3>

                <div class="space-y-4 max-h-[400px] overflow-y-auto">

                    <!-- Dynamic history here -->

                </div>

            </div>

        </div>

    </div>

</div>

<!-- KPI EDIT MODAL -->

<div
    id="editKpiModal"
    class="fixed inset-0 z-[9999] hidden items-center justify-center modal-bg p-6"
>

    <div
    class=" bg-white rounded-[28px] w-full max-w-3xl max-h-[90vh] overflow-y-auto p-5">

        <div class="flex justify-between items-center">

            <h2 class="text-xl font-black">
                Edit KPI
            </h2>

            <button
                onclick="closeEditKpiModal()"
                class="text-slate-500"
            >
                ✕
            </button>

        </div>

        <form
            id="editKpiForm"
            method="POST"
            action=""
            class="space-y-5 mt-6"
        >

            @csrf

            <!-- TITLE -->

            <div>

                <label class="text-xs font-black uppercase">
                    KPI Title
                </label>

                <input
                    id="edit_kpi_title"
                    name="kpi_title"
                    class="w-full border rounded-xl px-3 py-2 mt-1 text-sm"
                >

            </div>

            <!-- DESCRIPTION -->

            <div>

                <label class="text-xs font-black uppercase">
                    Description
                </label>

                <textarea
                    id="edit_kpi_description"
                    name="kpi_description"
                    rows="4"
                    class="w-full border rounded-xl px-3 py-2 mt-1 text-sm"
                ></textarea>

            </div>

            <!-- STATUS -->

            <div>

                <label class="text-xs font-black uppercase">
                    Status
                </label>

                <select
                    id="edit_status"
                    name="status"
                    class="w-full border rounded-xl px-3 py-2 mt-1 text-sm"
                >
                    <option value="not_started">Not Started</option>
                    <option value="on_track">On Track</option>
                    <option value="at_risk">At Risk</option>
                    <option value="in_trouble">In Trouble</option>
                    <option value="completed">Completed</option>
                </select>

            </div>

            <!-- TARGET -->

            <div class="grid grid-cols-2 gap-4">

                <div>

                    <label class="text-xs font-black uppercase">
                        Base Target
                    </label>

                    <input
                        id="edit_base_target"
                        type="number"
                        oninput="checkTargetChanged()"
                        step="0.01"
                        class="w-full border rounded-xl px-3 py-2 mt-1 text-sm"
                    >

                </div>

                <div>

                    <label class="text-xs font-black uppercase">
                        Stretch Target
                    </label>

                    <input
                        id="edit_stretch_target"
                        type="number"
                        oninput="checkTargetChanged()"
                        step="0.01"
                        class="w-full border rounded-xl px-3 py-2 mt-1 text-sm"
                    >

                </div>

            </div>

            <!-- TARGET APPROVAL NOTICE -->
            <div
                class="
                    rounded-xl
                    bg-amber-50
                    border
                    border-amber-200
                    p-3
                    text-xs
                    text-amber-800
                "
            >

                Any change to Base Target or Stretch Target
                requires approval before it is updated.

                KPI Title, Description and Status
                will be updated immediately.

            </div>

            <!-- REASON -->

            <div id="targetReasonBox" class="hidden">

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-red-600
                    "
                >
                    Reason For Target Change
                </label>

                <textarea
                    id="target_change_reason"
                    rows="3"
                    class="
                        w-full
                        border
                        border-red-300
                        rounded-xl
                        px-4
                        py-3
                        mt-2
                    "
                    placeholder="
            Explain why target needs to change.
            This will be reviewed by approver.
                    "
                ></textarea>

            </div>
            <div
                class="
                    sticky
                    bottom-0
                    bg-white
                    pt-4
                    flex
                    justify-end
                "
            >

                <button
                    type="button"
                    onclick="submitKpiEdit()"
                    class="bg-indigo-600 text-white px-5 py-3 rounded-xl font-black"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<!-- DELETE KPI MODAL -->

<div
    id="deleteKpiModal"
    class="
        fixed
        inset-0
        z-[99999]
        hidden
        flex
        items-center
        justify-center
        modal-bg
        p-6
    "
>

    <div
        class="
            bg-white
            rounded-[28px]
            w-full
            max-w-lg
            p-6
        "
    >

        <input
            type="hidden"
            id="deleteKpiId"
        >

        <div class="flex justify-between items-center">

            <h2 class="text-xl font-black text-red-700">
                Request KPI Deletion
            </h2>

            <button
                onclick="closeDeleteKpiModal()"
                class="text-slate-500"
            >
                ✕
            </button>

        </div>

        <div
            class="
                mt-5
                rounded-xl
                bg-red-50
                border
                border-red-200
                p-4
            "
        >

            <div class="text-xs text-red-500 uppercase font-black">
                KPI
            </div>

            <div
                id="deleteKpiTitle"
                class="
                    mt-2
                    text-sm
                    font-black
                    text-slate-900
                "
            ></div>

        </div>

        <div class="mt-5">

            <label
                class="
                    text-xs
                    font-black
                    uppercase
                    text-red-600
                "
            >
                Reason For Deletion
            </label>

            <textarea
                id="deleteReason"
                rows="4"
                class="
                    w-full
                    border
                    border-red-300
                    rounded-xl
                    px-4
                    py-3
                    mt-2
                "
                placeholder="
Explain why KPI should be deleted.
Minimum 30 characters.
                "
            ></textarea>

        </div>

        <div
            class="
                flex
                justify-end
                gap-3
                mt-6
            "
        >

            <button
                onclick="closeDeleteKpiModal()"
                class="
                    px-5
                    py-3
                    rounded-xl
                    border
                    border-slate-300
                "
            >
                Cancel
            </button>

            <button
                onclick="submitDeleteRequest()"
                class="
                    px-5
                    py-3
                    rounded-xl
                    bg-red-600
                    hover:bg-red-700
                    text-white
                    font-black
                "
            >
                Submit Delete Request
            </button>

        </div>

    </div>

</div>

</body>
</html>
