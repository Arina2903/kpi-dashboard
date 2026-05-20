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

    <!-- SUMMARY -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <p class="text-xs text-blue-100">Visible</p>
                <p id="visibleCount" class="text-2xl font-bold">{{ count($kpis) }}</p>
            </div>
        </div>
    </div>

    <!-- KPI LIST -->
    <div class="space-y-4" id="kpiList">
        @forelse($kpis as $kpi)
            @php
                $baseTarget = max(0, (float) ($kpi['base_target'] ?? 0));
                $actualValue = max(0, (float) ($kpi['actual_value'] ?? 0));

                $rawAchievement = $baseTarget > 0 ? round(($actualValue / $baseTarget) * 100, 2) : 0;
                $achievement = max(0, min(100, $rawAchievement));

                if ($achievement <= 25) {
                    $progressColor = 'bg-red-600';
                    $progressTextColor = 'text-red-700';
                } elseif ($achievement <= 50) {
                    $progressColor = 'bg-gradient-to-r from-red-600 to-orange-500';
                    $progressTextColor = 'text-orange-700';
                } elseif ($achievement <= 75) {
                    $progressColor = 'bg-gradient-to-r from-orange-500 to-yellow-400';
                    $progressTextColor = 'text-yellow-700';
                } elseif ($achievement <= 85) {
                    $progressColor = 'bg-gradient-to-r from-yellow-400 to-green-500';
                    $progressTextColor = 'text-green-700';
                } else {
                    $progressColor = 'bg-green-600';
                    $progressTextColor = 'text-green-700';
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

                $quarters = $kpi['quarters'] ?? $kpi['quarter_plans'] ?? [];
            @endphp

            <div class="kpi-card glass rounded-3xl border border-white/70 shadow-sm overflow-hidden card-hover"
                 data-search="{{ strtolower(($kpi['employee_name'] ?? '') . ' ' . ($kpi['category'] ?? '') . ' ' . ($kpi['sub_category'] ?? '') . ' ' . ($kpi['kpi_title'] ?? '') . ' ' . ($kpi['kpi_description'] ?? '')) }}"
                 data-category="{{ $kpi['category'] ?? '' }}"
                 data-status="{{ $kpi['status'] ?? '' }}">

                <div class="p-5 grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">

                    <!-- KPI MAIN -->
                    <div class="xl:col-span-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-xs font-bold text-blue-700 bg-blue-100 inline-flex px-3 py-1 rounded-full">
                                    {{ $kpi['category'] ?? '-' }}
                                </div>

                                <h2 class="font-bold text-slate-900 mt-3 text-lg leading-snug">
                                    {{ $kpi['kpi_title'] ?? '-' }}
                                </h2>

                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $kpi['sub_category'] ?? '-' }}
                                </p>
                            </div>
                        </div>

                        @if(!empty($kpi['kpi_description']))
                            <p class="text-sm text-slate-600 mt-3 line-clamp-2">
                                {{ $kpi['kpi_description'] }}
                            </p>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold">
                                {{ $kpi['employee_name'] ?? 'Unassigned' }}
                            </span>

                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold">
                                {{ $kpi['department_code'] ?? '-' }}
                            </span>

                            <span class="px-3 py-1 rounded-full {{ $statusClass }} font-semibold">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- TARGET -->
                    <div class="xl:col-span-3 grid grid-cols-3 gap-3">
                        <div class="bg-white rounded-2xl p-3 border border-slate-100">
                            <p class="text-xs text-slate-400">Base</p>
                            <p class="font-bold text-slate-900">{{ $kpi['base_target'] ?? '-' }}</p>
                        </div>

                        <div class="bg-white rounded-2xl p-3 border border-slate-100">
                            <p class="text-xs text-slate-400">Stretch</p>
                            <p class="font-bold text-slate-900">{{ $kpi['stretch_target'] ?? '-' }}</p>
                        </div>

                        <div class="bg-white rounded-2xl p-3 border border-slate-100">
                            <p class="text-xs text-slate-400">Actual</p>
                            <p class="font-bold text-slate-900">{{ number_format($actualValue, 2) }}</p>
                        </div>
                    </div>

                    <!-- PROGRESS -->
                    <div class="xl:col-span-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-500 uppercase">Progress</p>
                            <p class="text-sm font-bold {{ $progressTextColor }}">{{ number_format($achievement, 2) }}%</p>
                        </div>

                        <div class="mt-2 w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                            <div class="{{ $progressColor }} h-3 rounded-full transition-all duration-500"
                                 style="width: {{ $achievement }}%">
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-slate-500">
                            Weightage:
                            <span class="font-bold text-slate-900">
                                {{ number_format((float)($kpi['weightage'] ?? 0), 2) }}%
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
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        @foreach(['Q1','Q2','Q3','Q4'] as $q)
                            @php
                                $quarterData = collect($quarters)->firstWhere('quarter', $q) ?? [];
                            @endphp

                            <div class="bg-white rounded-2xl border border-slate-100 p-4">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-slate-900">{{ $q }}</h3>
                                    <span class="text-[10px] px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-bold">
                                        {{ !empty($quarterData) ? 'Set' : 'Empty' }}
                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <p class="text-slate-400">Target</p>
                                        <p class="font-bold text-slate-900">{{ $quarterData['quarter_target'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">Actual</p>
                                        <p class="font-bold text-slate-900">{{ $quarterData['quarter_actual'] ?? '-' }}</p>
                                    </div>
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
                                    <p class="text-xs text-slate-500">Update main target and quarter plan.</p>
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
                                        <label class="text-xs font-bold text-slate-500 uppercase">Weightage</label>
                                        <input name="weightage" type="number" step="0.01" value="{{ $kpi['weightage'] ?? 0 }}"
                                               class="w-full mt-2 border border-slate-200 rounded-xl p-3" required>
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

    searchInput.addEventListener('input', filterRows);
    categoryFilter.addEventListener('change', filterRows);
    statusFilter.addEventListener('change', filterRows);
</script>

</body>
</html>
