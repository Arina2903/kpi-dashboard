<!DOCTYPE html>
<html>
<head>
    <title>My Department KPI</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        .glass{
            background:rgba(255,255,255,.9);
            backdrop-filter:blur(14px);
        }

        .card-hover{
            transition:.2s ease;
        }

        .card-hover:hover{
            transform:translateY(-2px);
            box-shadow:0 18px 35px rgba(15,23,42,.08);
        }

        input:focus,
        textarea:focus,
        select:focus{
            outline:none;
            border-color:#2563eb;
            box-shadow:0 0 0 3px rgba(37,99,235,.12);
        }

    </style>

</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100">

@include('partials.sidebar')

<main
    id="mainContent"
    class="ml-[230px] min-h-screen transition-all duration-300 bg-[#f4f7fb]">

<div class="p-6 space-y-6">

    <!-- HEADER -->
    <div class="rounded-[18px] bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white p-6 shadow-xl">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">

            <div>

                <a
                    href="/dashboard"
                    class="text-xs text-blue-100 hover:text-white">

                    ← Dashboard

                </a>

                <h1 class="text-3xl font-black mt-3">
                    My Department KPI
                </h1>

                <p class="text-blue-100 text-xs mt-2">

                    {{ $user['department_code'] }}
                    ·
                    {{ $fy }}

                </p>

            </div>

        </div>

    </div>

    @php

        $departmentPerformance = collect($kpis ?? [])->sum(function($item){

            $quarters = collect($item['quarters'] ?? []);

            $target = $quarters->sum(fn($q)
                => (float)($q['quarter_target'] ?? 0));

            $actual = $quarters->sum(fn($q)
                => (float)($q['quarter_actual'] ?? 0));

            $score = $target > 0
                ? (($actual / $target) * 100)
                : 0;

            return (
                $score *
                ((float)($item['weightage'] ?? 0))
            ) / 100;

        });

        $staffGroupedKpis = collect($kpis)

            ->groupBy(function($item){

                return $item['employee_name']
                    ?? 'Unknown';

            })

            ->sortByDesc(function($group){

                $performance = 0;

                foreach($group as $item){

                    $quarters = collect(
                        $item['quarters'] ?? []
                    );

                    $target = $quarters->sum(fn($q)
                        => (float)($q['quarter_target'] ?? 0));

                    $actual = $quarters->sum(fn($q)
                        => (float)($q['quarter_actual'] ?? 0));

                    $score = $target > 0
                        ? (($actual / $target) * 100)
                        : 0;

                    $performance += (
                        $score *
                        ((float)($item['weightage'] ?? 0))
                    ) / 100;
                }

                return $performance;
            });

    @endphp

    <!-- SUMMARY -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">

            <p class="text-slate-500 text-xs font-semibold uppercase">
                Department Performance
            </p>

            <h3 class="text-3xl font-black text-indigo-700 mt-1">
                {{ number_format($departmentPerformance,2) }}%
            </h3>

            <div class="mt-4 h-3 rounded-full bg-slate-100 overflow-hidden">

                <div
                    class="h-3 rounded-full bg-gradient-to-r from-indigo-500 to-blue-600"
                    style="width: {{ min(100,$departmentPerformance) }}%">
                </div>

            </div>

        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">

            <p class="text-slate-500 text-xs font-semibold uppercase">
                Staff
            </p>

            <h3 class="text-2xl font-black text-slate-900 mt-1">
                {{ count($employees ?? []) }}
            </h3>

        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">

            <p class="text-slate-500 text-xs font-semibold uppercase">
                Total KPI
            </p>

            <h3 class="text-2xl font-black text-slate-900 mt-1">
                {{ count($kpis ?? []) }}
            </h3>

        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">

            <p class="text-slate-500 text-xs font-semibold uppercase">
                FY
            </p>

            <h3 class="text-2xl font-black text-slate-900 mt-1">
                {{ $fy }}
            </h3>

        </div>

        <div class="glass card-hover p-4 rounded-[18px] border border-white/70">

            <p class="text-slate-500 text-xs font-semibold uppercase">
                Scope
            </p>

            <h3 class="text-2xl font-black text-slate-900 mt-1">
                Department
            </h3>

        </div>

    </div>

    <!-- FILTER -->
    <div class="sticky top-4 z-40 glass rounded-[18px] shadow-sm border border-white/70 p-4">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

            <div>

                <label class="text-xs font-bold text-slate-500 uppercase">
                    Search
                </label>

                <input
                    id="searchInput"
                    type="text"
                    placeholder="Title, staff, category..."
                    class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">

            </div>

            <div>

                <label class="text-xs font-bold text-slate-500 uppercase">
                    Category
                </label>

                <select
                    id="categoryFilter"
                    class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">

                    <option value="">All</option>

                    <option value="Financial">
                        Financial
                    </option>

                    <option value="Growth & Customer">
                        Growth & Customer
                    </option>

                    <option value="Initiatives">
                        Initiatives
                    </option>

                    <option value="People">
                        People
                    </option>

                </select>

            </div>

            <div>

                <label class="text-xs font-bold text-slate-500 uppercase">
                    Status
                </label>

                <select
                    id="statusFilter"
                    class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-xs">

                    <option value="">All</option>

                    <option value="not_started">
                        Not Started
                    </option>

                    <option value="on_track">
                        On Track
                    </option>

                    <option value="at_risk">
                        At Risk
                    </option>

                    <option value="in_trouble">
                        In Trouble
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                </select>

            </div>

            <div class="bg-slate-900 text-white rounded-2xl p-4">

                <p class="text-xs text-blue-100">
                    Visible KPI
                </p>

                <p id="visibleCount"
                   class="text-xl font-black">

                    {{ count($kpis ?? []) }}

                </p>

            </div>

        </div>

    </div>

    <!-- STAFF -->
    <div class="space-y-4">

        @foreach($staffGroupedKpis as $staffName => $staffKpis)

            @php

                $staffPerformance = 0;

                $criticalCount = 0;
                $riskCount = 0;
                $goodCount = 0;

                foreach($staffKpis as $staffKpi){

                    $quarters = collect(
                        $staffKpi['quarters'] ?? []
                    );

                    $target = $quarters->sum(fn($q)
                        => (float)($q['quarter_target'] ?? 0));

                    $actual = $quarters->sum(fn($q)
                        => (float)($q['quarter_actual'] ?? 0));

                    $score = $target > 0
                        ? (($actual / $target) * 100)
                        : 0;

                    $weightage = (float)(
                        $staffKpi['weightage'] ?? 0
                    );

                    $staffPerformance += (
                        $score * $weightage
                    ) / 100;

                    if($score < 50){
                        $criticalCount++;
                    }
                    elseif($score < 75){
                        $riskCount++;
                    }
                    else{
                        $goodCount++;
                    }

                }

            @endphp

            <div class="glass rounded-[18px] border border-white/70 overflow-hidden">

                <!-- STAFF HEADER -->
                <button
                    onclick="toggleStaff('{{ Str::slug($staffName) }}')"
                    class="w-full p-5 text-left hover:bg-slate-50 transition">

                    <div class="flex flex-col xl:flex-row xl:items-center gap-5">

                        <!-- LEFT -->
                        <div class="flex items-center gap-4 flex-1">

                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#06142f] to-blue-700 flex items-center justify-center text-white font-black text-lg">

                                {{ strtoupper(substr($staffName,0,1)) }}

                            </div>

                            <div>

                                <h2 class="text-lg font-black text-slate-900">
                                    {{ $staffName }}
                                </h2>

                                <div class="flex flex-wrap gap-2 mt-2">

                                    <div class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-black">
                                        {{ count($staffKpis) }} KPI
                                    </div>

                                    <div class="px-3 py-1 rounded-full bg-red-50 text-red-700 text-[10px] font-black">
                                        Critical {{ $criticalCount }}
                                    </div>

                                    <div class="px-3 py-1 rounded-full bg-yellow-50 text-yellow-700 text-[10px] font-black">
                                        Risk {{ $riskCount }}
                                    </div>

                                    <div class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-[10px] font-black">
                                        Good {{ $goodCount }}
                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="w-full xl:w-[260px]">

                            <div class="flex items-center justify-between mb-2">

                                <p class="text-[10px] uppercase font-black text-slate-400">
                                    Performance
                                </p>

                                <p class="text-2xl font-black text-indigo-700">
                                    {{ number_format($staffPerformance,2) }}%
                                </p>

                            </div>

                            <div class="h-3 bg-slate-100 rounded-full overflow-hidden">

                                <div
                                    class="h-3 rounded-full bg-gradient-to-r from-indigo-500 to-blue-600"
                                    style="width: {{ min(100,$staffPerformance) }}%">
                                </div>

                            </div>

                        </div>

                    </div>

                </button>

                <!-- STAFF KPI -->
                <div
                    id="staff-{{ Str::slug($staffName) }}"
                    class="hidden border-t border-slate-100 bg-slate-50/70 p-4 space-y-3">

                    @foreach($staffKpis as $kpi)

                        @php

                            $quarters = collect(
                                $kpi['quarters'] ?? []
                            );

                            $target = $quarters->sum(fn($q)
                                => (float)($q['quarter_target'] ?? 0));

                            $actual = $quarters->sum(fn($q)
                                => (float)($q['quarter_actual'] ?? 0));

                            $achievement = $target > 0
                                ? (($actual / $target) * 100)
                                : 0;

                        @endphp

                        <div
                            onclick="openKpiDrawer(this)"
                            class="kpi-card cursor-pointer bg-white border border-slate-200 rounded-[18px] p-5 hover:shadow-lg transition"

                            data-search="{{ strtolower(($kpi['kpi_title'] ?? '') . ' ' . ($staffName ?? '') . ' ' . ($kpi['category'] ?? '')) }}"

                            data-category="{{ $kpi['category'] ?? '' }}"
                            data-status="{{ $kpi['status'] ?? '' }}"
                            data-kpi='@json($kpi)'>

                            <div class="flex flex-col xl:flex-row xl:items-center gap-5">

                                <!-- LEFT -->
                                <div class="flex-1">

                                    <div class="flex flex-wrap gap-2 mb-3">

                                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black">
                                            {{ $kpi['category'] ?? '-' }}
                                        </span>

                                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-black">
                                            {{ $kpi['sub_category'] ?? '-' }}
                                        </span>

                                    </div>

                                    <h3 class="text-lg font-black text-slate-900">
                                        {{ $kpi['kpi_title'] }}
                                    </h3>

                                    <p class="text-sm text-slate-500 mt-2">
                                        {{ $kpi['kpi_description'] ?? '-' }}
                                    </p>

                                </div>

                                <!-- RIGHT -->
                                <div class="w-full xl:w-[240px]">

                                    <div class="flex items-center justify-between mb-2">

                                        <p class="text-[10px] uppercase font-black text-slate-400">
                                            Performance
                                        </p>

                                        <p class="text-lg font-black text-indigo-700">
                                            {{ number_format($achievement,2) }}%
                                        </p>

                                    </div>

                                    <div class="h-3 bg-slate-100 rounded-full overflow-hidden">

                                        <div
                                            class="h-3 rounded-full bg-gradient-to-r from-indigo-500 to-blue-600"
                                            style="width: {{ min(100,$achievement) }}%">
                                        </div>

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

<!-- KPI DRAWER -->
<div
    id="kpiDrawer"
    class="hidden fixed inset-0 z-[9999]">

    <div
        onclick="closeKpiDrawer()"
        class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm">
    </div>

    <div
        id="kpiDrawerContent"
        class="absolute right-0 top-0 h-full w-full max-w-[950px] bg-[#f8fafc] overflow-y-auto shadow-2xl">
    </div>

</div>

<script>

function toggleStaff(id){

    const el = document.getElementById(
        'staff-' + id
    );

    if(!el) return;

    el.classList.toggle('hidden');
}

const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const statusFilter = document.getElementById('statusFilter');
const visibleCount = document.getElementById('visibleCount');

function filterRows(){

    const search = searchInput.value.toLowerCase();
    const category = categoryFilter.value;
    const status = statusFilter.value;

    let visible = 0;

    document.querySelectorAll('.kpi-card')
        .forEach(card => {

        const matchesSearch =
            (card.dataset.search || '')
            .includes(search);

        const matchesCategory =
            !category
            ||
            card.dataset.category === category;

        const matchesStatus =
            !status
            ||
            card.dataset.status === status;

        if(
            matchesSearch
            &&
            matchesCategory
            &&
            matchesStatus
        ){

            card.classList.remove('hidden');

            visible++;

        }else{

            card.classList.add('hidden');
        }

    });

    visibleCount.innerText = visible;
}

searchInput.addEventListener('input', filterRows);
categoryFilter.addEventListener('change', filterRows);
statusFilter.addEventListener('change', filterRows);

function openKpiDrawer(card){

    const drawer = document.getElementById(
        'kpiDrawer'
    );

    const content = document.getElementById(
        'kpiDrawerContent'
    );

    if(!drawer || !content || !card) return;

    const kpi = JSON.parse(
        card.dataset.kpi || '{}'
    );

    const quarters = kpi.quarters || [];

    let quarterHtml = '';

    ['Q1','Q2','Q3','Q4'].forEach(q => {

        const quarter = quarters.find(
            x => x.quarter === q
        ) || {};

        const target =
            parseFloat(
                quarter.quarter_target || 0
            );

        const actual =
            parseFloat(
                quarter.quarter_actual || 0
            );

        const score = target > 0
            ? ((actual / target) * 100)
            : 0;

        quarterHtml += `

        <div class="bg-white rounded-3xl border border-slate-200 p-5">

            <div class="flex items-center justify-between">

                <div>

                    <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-black">
                        ${q}
                    </div>

                    <h3 class="text-lg font-black text-slate-900 mt-3">
                        ${quarter.quarter_title || 'Quarter KPI'}
                    </h3>

                </div>

                <div class="text-right">

                    <p class="text-xs uppercase text-slate-400 font-black">
                        Performance
                    </p>

                    <h2 class="text-2xl font-black text-indigo-700 mt-2">
                        ${score.toFixed(1)}%
                    </h2>

                </div>

            </div>

            <div class="grid grid-cols-2 gap-4 mt-5">

                <div class="bg-slate-50 rounded-2xl p-4">

                    <p class="text-[10px] uppercase text-slate-400 font-black">
                        Target
                    </p>

                    <h3 class="text-xl font-black text-slate-900 mt-2">
                        ${target}
                    </h3>

                </div>

                <div class="bg-slate-50 rounded-2xl p-4">

                    <p class="text-[10px] uppercase text-slate-400 font-black">
                        Actual
                    </p>

                    <h3 class="text-xl font-black text-emerald-600 mt-2">
                        ${actual}
                    </h3>

                </div>

            </div>

        </div>

        `;
    });

    content.innerHTML = `

    <div class="min-h-screen bg-[#f8fafc]">

        <!-- HEADER -->
        <div class="sticky top-0 z-20 bg-white border-b border-slate-200 p-6">

            <div class="flex items-start justify-between gap-5">

                <div>

                    <div class="flex flex-wrap gap-2 mb-3">

                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black">
                            ${kpi.category || '-'}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-black">
                            ${kpi.sub_category || '-'}
                        </span>

                    </div>

                    <h2 class="text-3xl font-black text-slate-900">
                        ${kpi.kpi_title || '-'}
                    </h2>

                    <p class="text-sm text-slate-500 mt-3 max-w-3xl">
                        ${kpi.kpi_description || '-'}
                    </p>

                </div>

                <button
                    onclick="closeKpiDrawer()"
                    class="w-11 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xl font-black">

                    ×

                </button>

            </div>

        </div>

        <!-- BODY -->
        <div class="p-6 grid grid-cols-1 xl:grid-cols-12 gap-5">

            <div class="xl:col-span-8 space-y-5">

                ${quarterHtml}

            </div>

            <div class="xl:col-span-4 space-y-5">

                <div class="rounded-3xl overflow-hidden bg-gradient-to-br from-[#06142f] via-blue-900 to-indigo-900 text-white shadow-xl">

                    <div class="p-5">

                        <p class="text-[11px] uppercase text-blue-200 font-black">
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
                                    Status
                                </p>

                                <h3 class="text-sm font-black mt-3">
                                    ${(kpi.status || '-').replaceAll('_',' ')}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="bg-white rounded-3xl border border-slate-200 p-5">

                    <h3 class="text-sm font-black text-slate-900">
                        KPI Summary
                    </h3>

                    <div class="space-y-5 mt-5">

                        <div class="flex items-center justify-between">

                            <div>

                                <p class="text-[10px] uppercase text-slate-400 font-black">
                                    Base Target
                                </p>

                                <h3 class="text-xl font-black text-slate-900 mt-2">
                                    ${kpi.base_target || 0}
                                </h3>

                            </div>

                            <div class="text-right">

                                <p class="text-[10px] uppercase text-slate-400 font-black">
                                    Stretch Target
                                </p>

                                <h3 class="text-xl font-black text-indigo-700 mt-2">
                                    ${kpi.stretch_target || 0}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    `;

    drawer.classList.remove('hidden');

    document.body.classList.add('overflow-hidden');
}

function closeKpiDrawer(){

    const drawer = document.getElementById(
        'kpiDrawer'
    );

    if(!drawer) return;

    drawer.classList.add('hidden');

    document.body.classList.remove('overflow-hidden');
}

</script>

</body>
</html>
