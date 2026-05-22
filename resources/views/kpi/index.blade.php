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

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">

<div class="p-6 space-y-6">

    <!-- HEADER -->
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white p-6 shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="/dashboard" class="text-sm text-blue-100 hover:text-white">← Dashboard</a>
            <h1 class="text-3xl font-bold mt-3">KPI List</h1>
            <p class="text-blue-100 text-sm mt-1">
                {{ $user['short_name'] }} · {{ $user['role'] }} · {{ $user['department_code'] }} · {{ $fy }}
            </p>
        </div>

        @if($permission['can_create'])
            <a href="{{ route('kpi.create') }}"
               class="bg-white text-blue-900 hover:bg-blue-50 px-5 py-3 rounded-2xl shadow font-bold">
                + Create KPI
            </a>
        @endif
    </div>

    <!-- MESSAGES -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-xl text-sm">
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
        $currentEmployeeId = (string) ($user['employee_id'] ?? '');
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
        <div class="glass card-hover p-5 rounded-3xl border border-indigo-100 bg-gradient-to-br from-white via-indigo-50 to-blue-50 shadow-sm md:col-span-2 xl:col-span-1">
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
                    <p id="individualKpiCountText" class="text-sm font-black text-slate-900">{{ $individualKpiCount }}</p>
                </div>
                <div class="rounded-xl bg-white/80 border border-white p-2">
                    <p class="text-slate-400 uppercase font-bold">Weightage</p>
                    <p id="individualWeightageText" class="text-sm font-black {{ $individualTotalWeightage == 100 ? 'text-emerald-700' : ($individualTotalWeightage > 100 ? 'text-red-700' : 'text-amber-700') }}">
                        {{ number_format($individualTotalWeightage, 2) }}%
                    </p>
                </div>
            </div>
        </div>

        <div class="glass card-hover p-5 rounded-3xl border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">FY</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $fy }}</h3>
        </div>

        <div class="glass card-hover p-5 rounded-3xl border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">Staff</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ count($employees) }}</h3>
        </div>

        <div class="glass card-hover p-5 rounded-3xl border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">Total KPI</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ count($kpis) }}</h3>
        </div>

        <div class="glass card-hover p-5 rounded-3xl border border-white/70">
            <p class="text-slate-500 text-xs font-semibold uppercase">Scope</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1">
                @if(in_array($user['role'], ['SLT', 'VP', 'CCO', 'CCMO']))
                    Company
                @else
                    Department
                @endif
            </h3>
        </div>
    </div>

    <!-- FILTER -->
    <div class="glass rounded-3xl shadow-sm border border-white/70 p-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Search</label>
                <input id="searchInput" type="text" placeholder="Title, staff, category..."
                       class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-3 text-sm">
            </div>

            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Category</label>
                <select id="categoryFilter" class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-3 text-sm">
                    <option value="">All</option>
                    <option value="Financial">Financial</option>
                    <option value="Growth & Customer">Growth & Customer</option>
                    <option value="Initiatives">Initiatives</option>
                    <option value="People">People</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Status</label>
                <select id="statusFilter" class="w-full mt-2 border border-slate-200 bg-white rounded-xl px-4 py-3 text-sm">
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
                <p id="visibleCount" class="text-2xl font-bold">{{ count($kpis) }}</p>
            </div>
        </div>
    </div>

    @php
        $weightageTotal = collect($kpis ?? [])->sum(fn($item) => (float) ($item['weightage'] ?? 0));
        $weightageRemaining = round(100 - $weightageTotal, 2);
        $weightageKpiCount = count($kpis ?? []);
        $equalWeightageSuggestion = $weightageKpiCount > 0 ? round(100 / $weightageKpiCount, 2) : 0;

        $weightagePanelClass = $weightageTotal > 100
            ? 'border-red-200 bg-red-50/70'
            : ($weightageTotal == 100
                ? 'border-emerald-200 bg-emerald-50/70'
                : 'border-amber-200 bg-amber-50/70');

        $weightageTextClass = $weightageTotal > 100
            ? 'text-red-700'
            : ($weightageTotal == 100 ? 'text-emerald-700' : 'text-amber-700');

        $weightageStatusText = $weightageTotal > 100
            ? 'Over 100%. Reduce weightage before finalizing.'
            : ($weightageTotal == 100
                ? 'Complete. Total weightage is exactly 100%.'
                : 'Still below 100%. Balance the remaining weightage.');
    @endphp

    <!-- WEIGHTAGE CONTROL -->
    <div id="weightageControlPanel" class="glass rounded-3xl shadow-sm border {{ $weightagePanelClass }} p-5">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">
            <div class="xl:col-span-4">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Weightage Control</p>
                <h3 class="text-xl font-black text-slate-900 mt-1">Set KPI Weightage</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Enter each KPI weightage directly in the list below. Total should be 100%.
                </p>
            </div>

            <div class="xl:col-span-2 rounded-2xl bg-white border border-white/80 p-4">
                <p class="text-[10px] text-slate-400 uppercase font-bold">Current Total</p>
                <p id="weightageTotalText" class="text-2xl font-black {{ $weightageTextClass }}">
                    {{ number_format($weightageTotal, 2) }}%
                </p>
            </div>

            <div class="xl:col-span-2 rounded-2xl bg-white border border-white/80 p-4">
                <p class="text-[10px] text-slate-400 uppercase font-bold">Balance</p>
                <p id="weightageRemainingText" class="text-2xl font-black {{ $weightageTextClass }}">
                    {{ number_format($weightageRemaining, 2) }}%
                </p>
            </div>

            <div class="xl:col-span-2 rounded-2xl bg-white border border-white/80 p-4">
                <p class="text-[10px] text-slate-400 uppercase font-bold">Equal Split</p>
                <p class="text-2xl font-black text-slate-900">
                    {{ number_format($equalWeightageSuggestion, 2) }}%
                </p>
            </div>

            <div class="xl:col-span-2 flex flex-col gap-2">
                <button type="button"
                        onclick="balanceEmptyWeightage()"
                        class="w-full rounded-xl bg-white border border-amber-200 text-amber-700 px-4 py-2 text-xs font-black hover:bg-amber-100">
                    Balance Empty
                </button>

                <button type="button"
                        onclick="equalizeAllWeightage()"
                        class="w-full rounded-xl bg-white border border-slate-200 text-slate-700 px-4 py-2 text-xs font-black hover:bg-slate-100">
                    Equalize All
                </button>
            </div>
        </div>

        <div class="mt-4">
            <div class="h-3 rounded-full bg-white overflow-hidden border border-white/80">
                <div id="weightageProgressBar"
                     class="h-3 rounded-full transition-all duration-300 {{ $weightageTotal > 100 ? 'bg-red-600' : ($weightageTotal == 100 ? 'bg-emerald-600' : 'bg-amber-500') }}"
                     style="width: {{ min(max($weightageTotal, 0), 100) }}%">
                </div>
            </div>

            <p id="weightageStatusText" class="text-xs font-bold mt-2 {{ $weightageTextClass }}">
                {{ $weightageStatusText }}
            </p>
        </div>
    </div>

    <!-- KPI LIST -->
    <div class="space-y-4" id="kpiList">
        @forelse($kpis as $kpi)
            @php
                $quarters = collect($kpi['quarters'] ?? $kpi['quarter_plans'] ?? []);

                /*
                    KPI score formula:
                    Score = Actual / Base * 100

                    Overall score must come from Q1 + Q2 + Q3 + Q4.
                    If a quarter has no actual value, actual is treated as 0.
                    Progress bar width is capped at 100%, but the score text can go above 100%.
                */
                $quarterScoreData = [];
                $overallBaseTarget = 0;
                $overallActualValue = 0;

                foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarterName) {
                    $quarterRow = $quarters->firstWhere('quarter', $quarterName) ?? [];

                    $quarterBaseValue = max(0, (float) ($quarterRow['quarter_target'] ?? 0));
                    $quarterActualValue = max(0, (float) ($quarterRow['quarter_actual'] ?? 0));
                    $quarterScoreValue = $quarterBaseValue > 0
                        ? round(($quarterActualValue / $quarterBaseValue) * 100, 2)
                        : 0;

                    $quarterScoreData[$quarterName] = [
                        'data' => $quarterRow,
                        'base' => $quarterBaseValue,
                        'actual' => $quarterActualValue,
                        'score' => $quarterScoreValue,
                        'width' => max(0, min(100, $quarterScoreValue)),
                    ];

                    $overallBaseTarget += $quarterBaseValue;
                    $overallActualValue += $quarterActualValue;
                }

                // Fallback only when no quarter base exists yet, so old KPI records still display something useful.
                if ($overallBaseTarget <= 0) {
                    $overallBaseTarget = max(0, (float) ($kpi['base_target'] ?? 0));
                    $overallActualValue = max(0, (float) ($kpi['actual_value'] ?? 0));
                }

                $achievement = $overallBaseTarget > 0
                    ? round(($overallActualValue / $overallBaseTarget) * 100, 2)
                    : 0;

                $progressWidth = max(0, min(100, $achievement));

                if ($achievement <= 25) {
                    $progressColor = 'bg-red-600';
                    $progressTextColor = 'text-red-700';
                    $progressBadgeClass = 'bg-red-50 text-red-700 border-red-100';
                    $progressLabel = 'Critical';
                } elseif ($achievement <= 50) {
                    $progressColor = 'bg-gradient-to-r from-red-600 to-orange-500';
                    $progressTextColor = 'text-orange-700';
                    $progressBadgeClass = 'bg-orange-50 text-orange-700 border-orange-100';
                    $progressLabel = 'Risk';
                } elseif ($achievement <= 75) {
                    $progressColor = 'bg-gradient-to-r from-orange-500 to-yellow-400';
                    $progressTextColor = 'text-yellow-700';
                    $progressBadgeClass = 'bg-yellow-50 text-yellow-700 border-yellow-100';
                    $progressLabel = 'Watch';
                } elseif ($achievement <= 100) {
                    $progressColor = 'bg-gradient-to-r from-yellow-400 to-green-500';
                    $progressTextColor = 'text-green-700';
                    $progressBadgeClass = 'bg-green-50 text-green-700 border-green-100';
                    $progressLabel = 'Good';
                } else {
                    $progressColor = 'bg-green-600';
                    $progressTextColor = 'text-green-700';
                    $progressBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                    $progressLabel = 'Excellent';
                }

                $status = $kpi['status'] ?? 'not_started';

                $statusClass = match($status) {
                    'not_started' => 'bg-slate-100 text-slate-700',
                    'on_track' => 'bg-blue-100 text-blue-700',
                    'at_risk' => 'bg-yellow-100 text-yellow-700',
                    'in_trouble' => 'bg-red-100 text-red-700',
                    'completed' => 'bg-green-100 text-green-700',
                    default => 'bg-slate-100 text-slate-700',
                };

                $statusLabel = match($status) {
                    'not_started' => 'Not Started',
                    'on_track' => 'On Track',
                    'at_risk' => 'At Risk',
                    'in_trouble' => 'In Trouble',
                    'completed' => 'Completed',
                    default => 'Not Started',
                };

                $canUpdateThisKpi =
                    ($permission['can_update'] ?? false)
                    && (
                        in_array($user['role'], ['SLT', 'CCO', 'CCMO', 'VP'])
                        || (($kpi['employee_id'] ?? null) === ($user['id'] ?? null))
                        || (($kpi['created_by'] ?? null) === ($user['id'] ?? null))
                    );

                $canDeleteThisKpi =
                    ($permission['can_delete'] ?? false)
                    && (
                        in_array($user['role'], ['SLT', 'CCO', 'CCMO', 'VP'])
                        || (($kpi['employee_id'] ?? null) === ($user['id'] ?? null))
                        || (($kpi['created_by'] ?? null) === ($user['id'] ?? null))
                    );

                $isIndividualRow = $isCurrentUserKpi($kpi);

            @endphp

            <div class="kpi-card glass rounded-3xl border border-white/70 shadow-sm overflow-hidden card-hover"
                 data-search="{{ strtolower(($kpi['employee_name'] ?? '') . ' ' . ($kpi['category'] ?? '') . ' ' . ($kpi['sub_category'] ?? '') . ' ' . ($kpi['kpi_title'] ?? '') . ' ' . ($kpi['kpi_description'] ?? '')) }}"
                 data-category="{{ $kpi['category'] ?? '' }}"
                 data-status="{{ $kpi['status'] ?? '' }}">

                <div class="p-5 grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">

                    <!-- KPI MAIN -->
                    <div class="xl:col-span-3">
                        <div class="text-xs font-bold text-blue-700 bg-blue-100 inline-flex px-3 py-1 rounded-full">
                            {{ $kpi['category'] ?? '-' }}
                        </div>

                        <h2 class="font-bold text-slate-900 mt-3 text-lg leading-snug">
                            {{ $kpi['kpi_title'] ?? '-' }}
                        </h2>

                        <p class="text-xs text-slate-500 mt-1">
                            {{ $kpi['sub_category'] ?? '-' }}
                        </p>

                        @if(!empty($kpi['kpi_description']))
                            <p class="text-sm text-slate-600 mt-3 line-clamp-2">
                                {{ $kpi['kpi_description'] }}
                            </p>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-2 text-xs">

                            <!-- OWNER -->
                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold">
                                Owner: {{ $kpi['employee_name'] ?? 'Unassigned' }}
                            </span>

                            <!-- STATUS -->
                            <span class="px-3 py-1 rounded-full {{ $statusClass }} font-semibold">
                                {{ $statusLabel }}
                            </span>

                        </div>
                    </div>

                    <!-- TARGET -->
                    <div class="xl:col-span-3 grid grid-cols-3 gap-3">
                        <div class="bg-white rounded-2xl p-3 border border-slate-100">
                            <p class="text-xs text-slate-400">Base</p>
                            <p class="font-bold text-slate-900">{{ number_format($overallBaseTarget, 2) }}</p>
                        </div>

                        <div class="bg-white rounded-2xl p-3 border border-slate-100">
                            <p class="text-xs text-slate-400">Stretch</p>
                            <p class="font-bold text-slate-900">{{ number_format((float) ($kpi['stretch_target'] ?? 0), 2) }}</p>
                        </div>

                        <div class="bg-white rounded-2xl p-3 border border-slate-100">
                            <p class="text-xs text-slate-400">Actual</p>
                            <p class="font-bold text-slate-900">{{ number_format($overallActualValue, 2) }}</p>
                        </div>
                    </div>

                    <!-- WEIGHTAGE -->
                    <div class="xl:col-span-2">
                        <form method="POST"
                              action="{{ route('kpi.update', $kpi['id']) }}"
                              class="rounded-2xl bg-white border border-slate-100 p-3">
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="category" value="{{ $kpi['category'] ?? '' }}">
                            <input type="hidden" name="sub_category" value="{{ $kpi['sub_category'] ?? '' }}">
                            <input type="hidden" name="kpi_title" value="{{ $kpi['kpi_title'] ?? '' }}">
                            <input type="hidden" name="kpi_description" value="{{ $kpi['kpi_description'] ?? '' }}">
                            <input type="hidden" name="unit" value="{{ $kpi['unit'] ?? 'number' }}">
                            <input type="hidden" name="base_target" value="{{ $kpi['base_target'] ?? 0 }}">
                            <input type="hidden" name="stretch_target" value="{{ $kpi['stretch_target'] ?? 0 }}">
                            <input type="hidden" name="actual_value" value="{{ $kpi['actual_value'] ?? 0 }}">
                            <input type="hidden" name="status" value="{{ $kpi['status'] ?? 'not_started' }}">
                            <input type="hidden" name="remark" value="{{ $kpi['remark'] ?? '' }}">

                            <div class="flex items-center justify-between gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Weightage</label>
                                <span class="text-[10px] text-slate-400">%</span>
                            </div>

                            <div class="mt-2 flex items-center gap-2">
                                <input type="number"
                                       name="weightage"
                                       value="{{ number_format((float) ($kpi['weightage'] ?? 0), 2, '.', '') }}"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       data-score="{{ $achievement }}"
                                       data-is-individual="{{ $isIndividualRow ? '1' : '0' }}"
                                       class="weightage-input w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-black text-slate-900"
                                       oninput="recalculateWeightage()">

                                @if($canUpdateThisKpi)
                                    <button type="submit"
                                            class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-black text-white hover:bg-slate-700">
                                        Save
                                    </button>
                                @endif
                            </div>

                            <p class="mt-2 text-[10px] text-slate-500">
                                Performance score:
                                <span class="weighted-score font-black text-slate-900">
                                    {{ number_format(($achievement * (float) ($kpi['weightage'] ?? 0)) / 100, 2) }}%
                                </span>
                            </p>
                        </form>
                    </div>

                    <!-- KPI SCORE -->
                    <div class="xl:col-span-2">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-bold text-slate-500 uppercase">Overall KPI Score</p>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full border {{ $progressBadgeClass }}">
                                {{ $progressLabel }}
                            </span>
                        </div>

                        <div class="mt-2 w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                            <div class="{{ $progressColor }} h-3 rounded-full transition-all duration-500"
                                 style="width: {{ $progressWidth }}%">
                            </div>
                        </div>

                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="font-black {{ $progressTextColor }}">
                                Score: {{ number_format($achievement, 2) }}%
                            </span>
                        </div>
                    </div>

                    <!-- ACTION -->
                    <div class="xl:col-span-2 flex xl:justify-end gap-2">
                        <button type="button"
                                onclick="toggleQuarter('{{ $kpi['id'] }}')"
                                class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-3 py-2 rounded-xl text-xs font-bold">
                            Quarter
                        </button>

                        @if($canUpdateThisKpi)
                            <button type="button"
                                    onclick="openEditModal('{{ $kpi['id'] }}')"
                                    class="bg-slate-900 hover:bg-slate-700 text-white px-3 py-2 rounded-xl text-xs font-bold">
                                Edit
                            </button>
                        @endif

                        @if($canDeleteThisKpi)
                            <form action="{{ route('kpi.destroy', $kpi['id']) }}"
                                  method="POST"
                                  onsubmit="return confirm('Padam KPI ini? Tindakan ini tidak boleh dibatalkan.')">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-xl text-xs font-bold">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- QUARTER PANEL -->
                <div id="quarter-{{ $kpi['id'] }}" class="hidden border-t border-slate-100 bg-slate-50/70 p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-slate-900">Quarter Score Breakdown</h3>
                        </div>

                        <div class="hidden md:flex items-center gap-2 text-[10px] font-bold">
                            <span class="px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">≤25 Critical</span>
                            <span class="px-2 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-100">≤50 Risk</span>
                            <span class="px-2 py-1 rounded-full bg-yellow-50 text-yellow-700 border border-yellow-100">≤75 Watch</span>
                            <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">>75 Good</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        @foreach(['Q1','Q2','Q3','Q4'] as $q)
                            @php
                                $quarterComputed = $quarterScoreData[$q] ?? ['data' => [], 'base' => 0, 'actual' => 0, 'score' => 0, 'width' => 0];
                                $quarterData = $quarterComputed['data'];
                                $quarterStatus = $quarterData['status'] ?? 'not_started';

                                $quarterBase = $quarterComputed['base'];
                                $quarterActual = $quarterComputed['actual'];
                                $quarterScore = $quarterComputed['score'];
                                $quarterWidth = $quarterComputed['width'];

                                if ($quarterScore <= 25) {
                                    $quarterProgressColor = 'bg-red-600';
                                    $quarterTextColor = 'text-red-700';
                                    $quarterScoreClass = 'bg-red-50 text-red-700 border-red-100';
                                    $quarterScoreLabel = 'Critical';
                                } elseif ($quarterScore <= 50) {
                                    $quarterProgressColor = 'bg-gradient-to-r from-red-600 to-orange-500';
                                    $quarterTextColor = 'text-orange-700';
                                    $quarterScoreClass = 'bg-orange-50 text-orange-700 border-orange-100';
                                    $quarterScoreLabel = 'Risk';
                                } elseif ($quarterScore <= 75) {
                                    $quarterProgressColor = 'bg-gradient-to-r from-orange-500 to-yellow-400';
                                    $quarterTextColor = 'text-yellow-700';
                                    $quarterScoreClass = 'bg-yellow-50 text-yellow-700 border-yellow-100';
                                    $quarterScoreLabel = 'Watch';
                                } elseif ($quarterScore <= 100) {
                                    $quarterProgressColor = 'bg-gradient-to-r from-yellow-400 to-green-500';
                                    $quarterTextColor = 'text-green-700';
                                    $quarterScoreClass = 'bg-green-50 text-green-700 border-green-100';
                                    $quarterScoreLabel = 'Good';
                                } else {
                                    $quarterProgressColor = 'bg-green-600';
                                    $quarterTextColor = 'text-green-700';
                                    $quarterScoreClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                    $quarterScoreLabel = 'Excellent';
                                }

                                $quarterStatusLabel = match($quarterStatus) {
                                    'not_started' => 'Not Started',
                                    'on_track' => 'On Track',
                                    'at_risk' => 'At Risk',
                                    'in_trouble' => 'In Trouble',
                                    'completed' => 'Completed',
                                    default => 'Not Started',
                                };
                            @endphp

                            <div class="bg-white rounded-2xl border border-slate-100 p-4">
                                <div class="flex justify-between items-start gap-2">
                                    <div>
                                        <h3 class="font-black text-slate-900">{{ $q }}</h3>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Quarter KPI Score</p>
                                    </div>

                                    <span class="text-[10px] px-2 py-1 rounded-full border {{ !empty($quarterData) ? $quarterScoreClass : 'bg-slate-50 text-slate-500 border-slate-100' }} font-bold">
                                        {{ !empty($quarterData) ? $quarterScoreLabel : 'Empty' }}
                                    </span>
                                </div>

                                @if(!empty($quarterData['quarter_title']))
                                    <p class="mt-3 text-sm font-bold text-slate-800 line-clamp-1">
                                        {{ $quarterData['quarter_title'] }}
                                    </p>
                                @endif

                                @if(!empty($quarterData['quarter_description']))
                                    <p class="mt-1 text-xs text-slate-500 line-clamp-2">
                                        {{ $quarterData['quarter_description'] }}
                                    </p>
                                @endif

                                <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-2">
                                        <p class="text-slate-400">Base</p>
                                        <p class="font-black text-slate-900">{{ number_format($quarterBase, 2) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-2">
                                        <p class="text-slate-400">Actual</p>
                                        <p class="font-black text-slate-900">{{ number_format($quarterActual, 2) }}</p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-[10px] uppercase font-bold text-slate-400">KPI Score</p>
                                        <p class="text-xs font-black {{ $quarterTextColor }}">
                                            {{ number_format($quarterScore, 2) }}%
                                        </p>
                                    </div>

                                    <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                        <div class="{{ $quarterProgressColor }} h-2.5 rounded-full transition-all duration-500"
                                             style="width: {{ $quarterWidth }}%">
                                        </div>
                                    </div>

                                    <p class="mt-1 text-[10px] text-slate-500">
                                        Score: {{ number_format($quarterScore, 2) }}%
                                    </p>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <p class="text-slate-400">Start</p>
                                        <p class="font-bold text-slate-900">{{ $quarterData['start_date'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">End</p>
                                        <p class="font-bold text-slate-900">{{ $quarterData['end_date'] ?? '-' }}</p>
                                    </div>
                                </div>

                                @if(!empty($quarterData['remark']))
                                    <p class="mt-3 text-xs text-slate-500 bg-slate-50 rounded-xl p-2">
                                        {{ $quarterData['remark'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- EDIT MODAL -->
            @if($canUpdateThisKpi)
                <div id="editModal-{{ $kpi['id'] }}" class="fixed inset-0 z-50 hidden modal-bg items-center justify-center p-5">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto">

                        <form method="POST" action="{{ route('kpi.update', $kpi['id']) }}">
                            @csrf
                            @method('PUT')

                            <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 rounded-t-3xl flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900">Edit KPI</h2>
                                    <p class="text-xs text-slate-500">Update KPI and quarter plan.</p>
                                </div>

                                <button type="button"
                                        onclick="closeEditModal('{{ $kpi['id'] }}')"
                                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-bold">
                                    Close
                                </button>
                            </div>

                            <div class="p-6 space-y-5">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Category</label>
                                        <select name="category" class="w-full mt-2 border border-slate-200 rounded-xl p-3">
                                            <option value="Financial" {{ ($kpi['category'] ?? '') === 'Financial' ? 'selected' : '' }}>Financial</option>
                                            <option value="Growth & Customer" {{ ($kpi['category'] ?? '') === 'Growth & Customer' ? 'selected' : '' }}>Growth & Customer</option>
                                            <option value="Initiatives" {{ ($kpi['category'] ?? '') === 'Initiatives' ? 'selected' : '' }}>Initiatives</option>
                                            <option value="People" {{ ($kpi['category'] ?? '') === 'People' ? 'selected' : '' }}>People</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Sub Category</label>
                                        <input name="sub_category" value="{{ $kpi['sub_category'] ?? '' }}"
                                               class="w-full mt-2 border border-slate-200 rounded-xl p-3">
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-slate-500 uppercase">KPI Title</label>
                                    <input name="kpi_title" value="{{ $kpi['kpi_title'] ?? '' }}"
                                           class="w-full mt-2 border border-slate-200 rounded-xl p-3" required>
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-slate-500 uppercase">Description</label>
                                    <textarea name="kpi_description" rows="2"
                                              class="w-full mt-2 border border-slate-200 rounded-xl p-3">{{ $kpi['kpi_description'] ?? '' }}</textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Unit</label>
                                        <select name="unit" class="w-full mt-2 border border-slate-200 rounded-xl p-3">
                                            <option value="number" {{ ($kpi['unit'] ?? '') === 'number' ? 'selected' : '' }}>Number</option>
                                            <option value="currency" {{ ($kpi['unit'] ?? '') === 'currency' ? 'selected' : '' }}>Currency / RM</option>
                                            <option value="percentage" {{ ($kpi['unit'] ?? '') === 'percentage' ? 'selected' : '' }}>Percentage / %</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Base</label>
                                        <input name="base_target" type="number" step="0.01" value="{{ $kpi['base_target'] ?? 0 }}"
                                               class="w-full mt-2 border border-slate-200 rounded-xl p-3" required>
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Stretch</label>
                                        <input name="stretch_target" type="number" step="0.01" value="{{ $kpi['stretch_target'] ?? 0 }}"
                                               class="w-full mt-2 border border-slate-200 rounded-xl p-3" required>
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Actual</label>
                                        <input name="actual_value" type="number" step="0.01" value="{{ $kpi['actual_value'] ?? 0 }}"
                                               class="w-full mt-2 border border-slate-200 rounded-xl p-3" required>
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Weightage %</label>
                                        <input name="weightage" type="number" min="0" max="100" step="0.01" value="{{ $kpi['weightage'] ?? 0 }}"
                                               class="w-full mt-2 border border-slate-200 rounded-xl p-3">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Status</label>
                                        <select name="status" class="w-full mt-2 border border-slate-200 rounded-xl p-3">
                                            <option value="not_started" {{ $status === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                            <option value="on_track" {{ $status === 'on_track' ? 'selected' : '' }}>On Track</option>
                                            <option value="at_risk" {{ $status === 'at_risk' ? 'selected' : '' }}>At Risk</option>
                                            <option value="in_trouble" {{ $status === 'in_trouble' ? 'selected' : '' }}>In Trouble</option>
                                            <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase">Remark</label>
                                        <input name="remark" value="{{ $kpi['remark'] ?? '' }}"
                                               class="w-full mt-2 border border-slate-200 rounded-xl p-3">
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-bold text-slate-900 mb-3">Quarter Plan</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach(['Q1','Q2','Q3','Q4'] as $q)
                                            @php
                                                $quarterData = collect($quarters)->firstWhere('quarter', $q) ?? [];
                                            @endphp

                                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                                                <h4 class="font-bold text-slate-900 mb-3">{{ $q }}</h4>

                                                <input type="hidden" name="quarters[{{ $q }}][quarter]" value="{{ $q }}">

                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="col-span-2">
                                                        <label class="text-xs font-semibold text-slate-500">Quarter Title</label>
                                                        <input type="text"
                                                               name="quarters[{{ $q }}][quarter_title]"
                                                               value="{{ $quarterData['quarter_title'] ?? '' }}"
                                                               class="w-full mt-1 border border-slate-200 rounded-xl p-2">
                                                    </div>

                                                    <div class="col-span-2">
                                                        <label class="text-xs font-semibold text-slate-500">Quarter Description</label>
                                                        <textarea name="quarters[{{ $q }}][quarter_description]" rows="2"
                                                                  class="w-full mt-1 border border-slate-200 rounded-xl p-2">{{ $quarterData['quarter_description'] ?? '' }}</textarea>
                                                    </div>

                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-500">Target</label>
                                                        <input type="number" step="0.01"
                                                               name="quarters[{{ $q }}][quarter_target]"
                                                               value="{{ $quarterData['quarter_target'] ?? '' }}"
                                                               class="w-full mt-1 border border-slate-200 rounded-xl p-2">
                                                    </div>

                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-500">Actual</label>
                                                        <input type="number" step="0.01"
                                                               name="quarters[{{ $q }}][quarter_actual]"
                                                               value="{{ $quarterData['quarter_actual'] ?? '' }}"
                                                               class="w-full mt-1 border border-slate-200 rounded-xl p-2">
                                                    </div>

                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-500">Start</label>
                                                        <input type="date"
                                                               name="quarters[{{ $q }}][start_date]"
                                                               value="{{ $quarterData['start_date'] ?? '' }}"
                                                               class="w-full mt-1 border border-slate-200 rounded-xl p-2">
                                                    </div>

                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-500">End</label>
                                                        <input type="date"
                                                               name="quarters[{{ $q }}][end_date]"
                                                               value="{{ $quarterData['end_date'] ?? '' }}"
                                                               class="w-full mt-1 border border-slate-200 rounded-xl p-2">
                                                    </div>

                                                    <div class="col-span-2">
                                                        <label class="text-xs font-semibold text-slate-500">Status</label>
                                                        <select name="quarters[{{ $q }}][status]"
                                                                class="w-full mt-1 border border-slate-200 rounded-xl p-2">
                                                            <option value="not_started" {{ ($quarterData['status'] ?? 'not_started') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                                            <option value="on_track" {{ ($quarterData['status'] ?? '') === 'on_track' ? 'selected' : '' }}>On Track</option>
                                                            <option value="at_risk" {{ ($quarterData['status'] ?? '') === 'at_risk' ? 'selected' : '' }}>At Risk</option>
                                                            <option value="in_trouble" {{ ($quarterData['status'] ?? '') === 'in_trouble' ? 'selected' : '' }}>In Trouble</option>
                                                            <option value="completed" {{ ($quarterData['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-span-2">
                                                        <label class="text-xs font-semibold text-slate-500">Remark</label>
                                                        <textarea name="quarters[{{ $q }}][remark]" rows="2"
                                                                  class="w-full mt-1 border border-slate-200 rounded-xl p-2">{{ $quarterData['remark'] ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            </div>

                            <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 rounded-b-3xl flex justify-end gap-3">
                                <button type="button"
                                        onclick="closeEditModal('{{ $kpi['id'] }}')"
                                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl font-bold">
                                    Cancel
                                </button>

                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold shadow">
                                    Save Changes
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            @endif

        @empty
            <div class="glass rounded-3xl border border-white/70 p-10 text-center text-slate-500">
                No KPI found for {{ $fy }}.
            </div>
        @endforelse

        <div id="noFilterResult" class="hidden glass rounded-3xl border border-white/70 p-10 text-center text-slate-500">
            No KPI matched your filter.
        </div>
    </div>

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
            const searchData = card.dataset.search || '';
            const categoryData = card.dataset.category || '';
            const statusData = card.dataset.status || '';

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

    function toggleQuarter(id) {
        const panel = document.getElementById('quarter-' + id);
        if (!panel) return;
        panel.classList.toggle('hidden');
    }

    function openEditModal(id) {
        const modal = document.getElementById('editModal-' + id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeEditModal(id) {
        const modal = document.getElementById('editModal-' + id);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function getWeightageInputs() {
        return Array.from(document.querySelectorAll('.weightage-input'));
    }

    function recalculateWeightage() {
        const inputs = getWeightageInputs();
        let total = 0;

        let individualTotal = 0;
        let individualWeightageTotal = 0;

        inputs.forEach(function(input) {
            const value = parseFloat(input.value || 0);
            total += isNaN(value) ? 0 : value;

            const score = parseFloat(input.dataset.score || 0);
            const weightedScoreElement = input.closest('form')?.querySelector('.weighted-score');
            const weight = isNaN(value) ? 0 : value;
            const weightedScore = (score * weight) / 100;

            if (weightedScoreElement) {
                weightedScoreElement.textContent = weightedScore.toFixed(2) + '%';
            }

            if (input.dataset.isIndividual === '1') {
                individualTotal += weightedScore;
                individualWeightageTotal += weight;
            }
        });

        total = Math.round(total * 100) / 100;
        const remaining = Math.round((100 - total) * 100) / 100;

        const totalText = document.getElementById('weightageTotalText');
        const remainingText = document.getElementById('weightageRemainingText');
        const statusText = document.getElementById('weightageStatusText');
        const progressBar = document.getElementById('weightageProgressBar');
        const panel = document.getElementById('weightageControlPanel');

        if (totalText) totalText.textContent = total.toFixed(2) + '%';
        if (remainingText) remainingText.textContent = remaining.toFixed(2) + '%';


        updateIndividualPerformanceCard(individualTotal, individualWeightageTotal);

        const cappedWidth = Math.max(0, Math.min(100, total));
        if (progressBar) {
            progressBar.style.width = cappedWidth + '%';
            progressBar.className = 'h-3 rounded-full transition-all duration-300';

            if (total > 100) {
                progressBar.classList.add('bg-red-600');
            } else if (total === 100) {
                progressBar.classList.add('bg-emerald-600');
            } else {
                progressBar.classList.add('bg-amber-500');
            }
        }

        [totalText, remainingText, statusText].forEach(function(el) {
            if (!el) return;
            el.classList.remove('text-red-700', 'text-emerald-700', 'text-amber-700');
            el.classList.add(total > 100 ? 'text-red-700' : (total === 100 ? 'text-emerald-700' : 'text-amber-700'));
        });

        if (panel) {
            panel.classList.remove('border-red-200', 'bg-red-50/70', 'border-emerald-200', 'bg-emerald-50/70', 'border-amber-200', 'bg-amber-50/70');
            if (total > 100) {
                panel.classList.add('border-red-200', 'bg-red-50/70');
            } else if (total === 100) {
                panel.classList.add('border-emerald-200', 'bg-emerald-50/70');
            } else {
                panel.classList.add('border-amber-200', 'bg-amber-50/70');
            }
        }

        if (statusText) {
            if (total > 100) {
                statusText.textContent = 'Over 100%. Reduce weightage before finalizing.';
            } else if (total === 100) {
                statusText.textContent = 'Complete. Total weightage is exactly 100%.';
            } else {
                statusText.textContent = 'Still below 100%. Balance the remaining weightage.';
            }
        }
    }


    function updateIndividualPerformanceCard(score, weightage) {
        const scoreText = document.getElementById('individualPerformanceText');
        const badge = document.getElementById('individualPerformanceBadge');
        const bar = document.getElementById('individualPerformanceBar');
        const weightageText = document.getElementById('individualWeightageText');

        score = Math.round(score * 100) / 100;
        weightage = Math.round(weightage * 100) / 100;

        const width = Math.max(0, Math.min(100, score));
        let textClass = 'text-red-700';
        let barClass = 'bg-red-600';
        let badgeClass = 'bg-red-50 text-red-700 border-red-100';
        let label = 'Critical';

        if (score > 100) {
            textClass = 'text-green-700';
            barClass = 'bg-green-600';
            badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
            label = 'Exceeded';
        } else if (score > 75) {
            textClass = 'text-green-700';
            barClass = 'bg-gradient-to-r from-yellow-400 to-green-500';
            badgeClass = 'bg-green-50 text-green-700 border-green-100';
            label = 'Good';
        } else if (score > 50) {
            textClass = 'text-yellow-700';
            barClass = 'bg-gradient-to-r from-orange-500 to-yellow-400';
            badgeClass = 'bg-yellow-50 text-yellow-700 border-yellow-100';
            label = 'Watch';
        } else if (score > 25) {
            textClass = 'text-orange-700';
            barClass = 'bg-gradient-to-r from-red-600 to-orange-500';
            badgeClass = 'bg-orange-50 text-orange-700 border-orange-100';
            label = 'Risk';
        }

        if (scoreText) {
            scoreText.textContent = score.toFixed(2) + '%';
            scoreText.classList.remove('text-red-700', 'text-orange-700', 'text-yellow-700', 'text-green-700');
            scoreText.classList.add(textClass);
        }

        if (badge) {
            badge.textContent = label;
            badge.className = 'text-[10px] font-black px-2 py-1 rounded-full border ' + badgeClass;
        }

        if (bar) {
            bar.style.width = width + '%';
            bar.className = 'h-3 rounded-full transition-all duration-300 ' + barClass;
        }

        if (weightageText) {
            weightageText.textContent = weightage.toFixed(2) + '%';
            weightageText.classList.remove('text-red-700', 'text-emerald-700', 'text-amber-700');
            weightageText.classList.add(weightage > 100 ? 'text-red-700' : (weightage === 100 ? 'text-emerald-700' : 'text-amber-700'));
        }
    }

    function balanceEmptyWeightage() {
        const inputs = getWeightageInputs();
        const emptyInputs = inputs.filter(function(input) {
            const value = parseFloat(input.value || 0);
            return !value || value <= 0;
        });

        if (emptyInputs.length === 0) {
            return;
        }

        let usedWeightage = 0;
        inputs.forEach(function(input) {
            if (!emptyInputs.includes(input)) {
                const value = parseFloat(input.value || 0);
                usedWeightage += isNaN(value) ? 0 : value;
            }
        });

        const balance = Math.max(0, 100 - usedWeightage);
        const share = Math.floor((balance / emptyInputs.length) * 100) / 100;

        emptyInputs.forEach(function(input, index) {
            if (index === emptyInputs.length - 1) {
                const assignedBeforeLast = share * (emptyInputs.length - 1);
                input.value = (Math.round((balance - assignedBeforeLast) * 100) / 100).toFixed(2);
            } else {
                input.value = share.toFixed(2);
            }
        });

        recalculateWeightage();
    }

    function equalizeAllWeightage() {
        const inputs = getWeightageInputs();
        if (inputs.length === 0) return;

        const share = Math.floor((100 / inputs.length) * 100) / 100;
        let assigned = 0;

        inputs.forEach(function(input, index) {
            if (index === inputs.length - 1) {
                input.value = (Math.round((100 - assigned) * 100) / 100).toFixed(2);
            } else {
                input.value = share.toFixed(2);
                assigned += share;
            }
        });

        recalculateWeightage();
    }

    searchInput.addEventListener('input', filterRows);
    categoryFilter.addEventListener('change', filterRows);
    statusFilter.addEventListener('change', filterRows);
    recalculateWeightage();
</script>

</body>
</html>
