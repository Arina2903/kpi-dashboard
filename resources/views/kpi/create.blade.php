<!DOCTYPE html>
<html>
<head>
    <title>Create KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(14px);
        }

        .form-card {
            transition: all 0.2s ease;
        }

        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.08);
        }

        .step-circle {
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            background: #ffffff;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">

    <div class="p-5">
        <div class="max-w-7xl mx-auto">

            <!-- HEADER -->
            <div class="mb-5 rounded-3xl bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white p-6 shadow-xl flex items-center justify-between gap-4">
                <div>
                    <a href="{{ route('kpi.index') }}" class="text-blue-100 hover:text-white text-sm">
                        ← Back to KPI List
                    </a>

                    <h1 class="text-3xl font-bold mt-3">Create KPI</h1>
                    <p class="text-blue-100 text-sm mt-1">
                        {{ $fy ?? 'FY' . now()->year }} · Fill overall KPI first, then optional quarterly breakdown.
                    </p>
                </div>

                <div class="bg-white/15 border border-white/20 rounded-2xl px-5 py-3 min-w-[190px]">
                    <p class="text-xs text-blue-100">Remaining Weightage</p>
                    <p id="remainingText" class="text-2xl font-bold text-white">
                        {{ number_format($remainingWeightage ?? 100, 2) }}%
                    </p>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded-xl text-sm border border-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <div id="weightageWarning" class="hidden mb-4 bg-red-100 text-red-700 px-4 py-3 rounded-xl text-sm border border-red-300">
                Weightage exceeded. Please reduce the KPI weightage.
            </div>

            <form method="POST" action="{{ route('kpi.store') }}" id="createKpiForm">
                @csrf

                <input type="hidden" name="financial_year" value="{{ $fy ?? 'FY' . now()->year }}">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                    <div class="lg:col-span-8 space-y-4">

                        <!-- STEP 1 -->
                        <div class="form-card glass-card rounded-3xl shadow-sm border border-white/70 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="step-circle w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm">1</div>
                                <div>
                                    <h2 class="font-bold text-slate-900">KPI Category</h2>
                                    <p class="text-xs text-slate-500">Choose the main area and sub area for this KPI.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="font-semibold text-sm text-slate-700">Category</label>
                                    <select name="category" id="category" class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50" required>
                                        <option value="">Select Category</option>
                                        <option value="Financial" {{ old('category') === 'Financial' ? 'selected' : '' }}>Financial</option>
                                        <option value="Growth & Customer" {{ old('category') === 'Growth & Customer' ? 'selected' : '' }}>Growth & Customer</option>
                                        <option value="Initiatives" {{ old('category') === 'Initiatives' ? 'selected' : '' }}>Initiatives</option>
                                        <option value="People" {{ old('category') === 'People' ? 'selected' : '' }}>People</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="font-semibold text-sm text-slate-700">Sub Category</label>
                                    <select name="sub_category" id="subCategory" class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50" required>
                                        <option value="">Select Category first</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2 -->
                        <div class="form-card glass-card rounded-3xl shadow-sm border border-white/70 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="step-circle w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm">2</div>
                                <div>
                                    <h2 class="font-bold text-slate-900">KPI Details</h2>
                                    <p class="text-xs text-slate-500">Write what this KPI is trying to achieve.</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="font-semibold text-sm text-slate-700">KPI Title</label>
                                    <input
                                        name="kpi_title"
                                        id="kpiTitle"
                                        value="{{ old('kpi_title') }}"
                                        class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50"
                                        placeholder="Example: Achieve RM500,000 revenue in FY2026"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="font-semibold text-sm text-slate-700">Description</label>
                                    <textarea
                                        name="kpi_description"
                                        id="kpiDescription"
                                        class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50"
                                        rows="2"
                                        placeholder="Example: Total confirmed revenue collected from new and existing clients."
                                    >{{ old('kpi_description') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3 -->
                        <div class="form-card glass-card rounded-3xl shadow-sm border border-white/70 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="step-circle w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm">3</div>
                                <div>
                                    <h2 class="font-bold text-slate-900">FY2026 Overall Target</h2>
                                    <p class="text-xs text-slate-500">This is the full-year target. Q1 + Q2 + Q3 + Q4 should follow this total.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="font-semibold text-sm text-slate-700">Unit</label>
                                    <select name="unit" id="unit" class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50" required>
                                        <option value="number" {{ old('unit') === 'number' ? 'selected' : '' }}>Number</option>
                                        <option value="currency" {{ old('unit') === 'currency' ? 'selected' : '' }}>Currency / RM</option>
                                        <option value="percentage" {{ old('unit') === 'percentage' ? 'selected' : '' }}>Percentage / %</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="font-semibold text-sm text-slate-700">
                                        Base Target <span class="unitLabel text-slate-400">(Number)</span>
                                    </label>
                                    <input name="base_target" id="baseTarget" type="number" step="0.01" value="{{ old('base_target') }}" class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50" placeholder="Minimum target" required>
                                </div>

                                <div>
                                    <label class="font-semibold text-sm text-slate-700">
                                        Stretch Target <span class="unitLabel text-slate-400">(Number)</span>
                                    </label>
                                    <input name="stretch_target" id="stretchTarget" type="number" step="0.01" value="{{ old('stretch_target') }}" class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50" placeholder="Higher target" required>
                                </div>

                                <div>
                                    <label class="font-semibold text-sm text-slate-700">
                                        Actual <span class="unitLabel text-slate-400">(Number)</span>
                                    </label>
                                    <input name="actual_value" id="actualValue" type="number" step="0.01" value="{{ old('actual_value', 0) }}" class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50" placeholder="Current result" required>
                                </div>
                            </div>

                            <div class="mt-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-4 text-sm text-blue-800">
                                <strong>Simple rule:</strong> Base = committed annual target. Stretch = best-case annual target. Actual = current achievement.
                            </div>
                        </div>

                        <!-- STEP 4 -->
                        <div class="form-card glass-card rounded-3xl shadow-sm border border-white/70 p-5">
                            <div class="flex items-center justify-between gap-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="step-circle w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm">4</div>
                                    <div>
                                        <h2 class="font-bold text-slate-900">Quarter Breakdown</h2>
                                        <p class="text-xs text-slate-500">Each KPI has 4 quarter plans. Fill target, title, description and timeline.</p>
                                    </div>
                                </div>

                                <button type="button" onclick="autoDivideQuarter()" class="text-xs font-bold bg-slate-900 text-white px-4 py-2 rounded-xl hover:bg-slate-700 shadow-sm">
                                    Auto Divide Base
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                @foreach(['Q1','Q2','Q3','Q4'] as $quarter)
                                    @php
                                        $quarterColor = [
                                            'Q1' => 'from-blue-50 to-white border-blue-200 text-blue-700 bg-blue-100',
                                            'Q2' => 'from-emerald-50 to-white border-emerald-200 text-emerald-700 bg-emerald-100',
                                            'Q3' => 'from-amber-50 to-white border-amber-200 text-amber-700 bg-amber-100',
                                            'Q4' => 'from-indigo-50 to-white border-indigo-200 text-indigo-700 bg-indigo-100',
                                        ][$quarter];
                                    @endphp

                                    <div class="rounded-3xl border bg-gradient-to-br {{ $quarterColor }} p-5 shadow-sm">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h3 class="font-bold text-slate-900 text-lg">{{ $quarter }} Plan</h3>
                                                <p class="text-xs text-slate-500">Specific plan under this KPI for {{ $quarter }}.</p>
                                            </div>

                                            <span class="text-[10px] font-bold px-3 py-1 rounded-full {{ $quarterColor }}">
                                                {{ $quarter }}
                                            </span>
                                        </div>

                                        <input type="hidden" name="quarters[{{ $quarter }}][quarter]" value="{{ $quarter }}">

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="text-xs font-semibold text-slate-600">Quarter Title</label>
                                                <input
                                                    type="text"
                                                    name="quarters[{{ $quarter }}][quarter_title]"
                                                    value="{{ old("quarters.$quarter.quarter_title") }}"
                                                    class="w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                    placeholder="Example: Secure RM125,000 revenue in {{ $quarter }}"
                                                >
                                            </div>

                                            <div>
                                                <label class="text-xs font-semibold text-slate-600">Status</label>
                                                <select
                                                    name="quarters[{{ $quarter }}][status]"
                                                    class="w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                >
                                                    <option value="not_started" {{ old("quarters.$quarter.status", 'not_started') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                                    <option value="on_track" {{ old("quarters.$quarter.status") === 'on_track' ? 'selected' : '' }}>On Track</option>
                                                    <option value="at_risk" {{ old("quarters.$quarter.status") === 'at_risk' ? 'selected' : '' }}>At Risk</option>
                                                    <option value="in_trouble" {{ old("quarters.$quarter.status") === 'in_trouble' ? 'selected' : '' }}>In Trouble</option>
                                                    <option value="completed" {{ old("quarters.$quarter.status") === 'completed' ? 'selected' : '' }}>Completed</option>
                                                </select>
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="text-xs font-semibold text-slate-600">Quarter Description</label>
                                                <textarea
                                                    name="quarters[{{ $quarter }}][quarter_description]"
                                                    rows="2"
                                                    class="w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                    placeholder="Explain what should be achieved in {{ $quarter }}."
                                                >{{ old("quarters.$quarter.quarter_description") }}</textarea>
                                            </div>

                                            <div>
                                                <label class="text-xs font-semibold text-slate-600">Quarter Target</label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="quarters[{{ $quarter }}][quarter_target]"
                                                    value="{{ old("quarters.$quarter.quarter_target") }}"
                                                    class="quarter-target w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                    placeholder="Target"
                                                >
                                            </div>

                                            <div>
                                                <label class="text-xs font-semibold text-slate-600">Quarter Actual</label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="quarters[{{ $quarter }}][quarter_actual]"
                                                    value="{{ old("quarters.$quarter.quarter_actual", 0) }}"
                                                    class="quarter-actual w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                    placeholder="0"
                                                >
                                            </div>

                                            <div>
                                                <label class="text-xs font-semibold text-slate-600">Start Date</label>
                                                <input
                                                    type="date"
                                                    name="quarters[{{ $quarter }}][start_date]"
                                                    value="{{ old("quarters.$quarter.start_date") }}"
                                                    class="quarter-start w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                >
                                            </div>

                                            <div>
                                                <label class="text-xs font-semibold text-slate-600">End Date</label>
                                                <input
                                                    type="date"
                                                    name="quarters[{{ $quarter }}][end_date]"
                                                    value="{{ old("quarters.$quarter.end_date") }}"
                                                    class="quarter-end w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                >
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="text-xs font-semibold text-slate-600">Remark</label>
                                                <textarea
                                                    name="quarters[{{ $quarter }}][remark]"
                                                    rows="2"
                                                    class="w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm bg-white"
                                                    placeholder="Optional note for {{ $quarter }}"
                                                >{{ old("quarters.$quarter.remark") }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="bg-white/80 border border-slate-200 rounded-2xl p-4">
                                    <p class="text-xs text-slate-400">Q1–Q4 Target Total</p>
                                    <p id="quarterTargetTotal" class="text-xl font-bold text-slate-900">0.00</p>
                                </div>

                                <div class="bg-white/80 border border-slate-200 rounded-2xl p-4">
                                    <p class="text-xs text-slate-400">Q1–Q4 Actual Total</p>
                                    <p id="quarterActualTotal" class="text-xl font-bold text-slate-900">0.00</p>
                                </div>

                                <div id="quarterStatusBox" class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
                                    <p class="text-xs text-blue-500">Quarter vs FY Base</p>
                                    <p id="quarterMatchStatus" class="text-sm font-bold text-blue-700">Optional</p>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 5 -->
                        <div class="form-card glass-card rounded-3xl shadow-sm border border-white/70 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="step-circle w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm">5</div>
                                <div>
                                    <h2 class="font-bold text-slate-900">Status & Remark</h2>
                                    <p class="text-xs text-slate-500">Set current KPI status.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Status</label>
                                    <select name="status" id="status" class="w-full border border-slate-200 bg-slate-50 px-4 py-3 rounded-2xl" required>
                                        <option value="not_started" {{ old('status', 'not_started') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                        <option value="on_track" {{ old('status') === 'on_track' ? 'selected' : '' }}>On Track</option>
                                        <option value="at_risk" {{ old('status') === 'at_risk' ? 'selected' : '' }}>At Risk</option>
                                        <option value="in_trouble" {{ old('status') === 'in_trouble' ? 'selected' : '' }}>In Trouble</option>
                                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="font-semibold text-sm text-slate-700">Remark</label>
                                    <textarea name="remark" id="remark" class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50" rows="2" placeholder="Optional overall note">{{ old('remark') }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT SUMMARY -->
                    <div class="lg:col-span-4">
                        <div class="sticky top-5 space-y-4">

                            <div class="glass-card rounded-3xl shadow-lg border border-white/70 p-5">
                                <h2 class="font-bold text-slate-900">Live KPI Summary</h2>
                                <p class="text-xs text-slate-500 mt-1">Check before submit.</p>

                                <div class="mt-5 space-y-4 text-sm">
                                    <div>
                                        <p class="text-xs text-slate-400">Title</p>
                                        <p id="summaryTitle" class="font-semibold text-slate-800">Not entered yet</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-slate-400">Category</p>
                                            <p id="summaryCategory" class="font-semibold text-slate-800">-</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400">Sub Category</p>
                                            <p id="summarySubCategory" class="font-semibold text-slate-800">-</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                            <p class="text-xs text-slate-400">Base</p>
                                            <p id="summaryBase" class="font-bold text-slate-900">0</p>
                                        </div>

                                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                            <p class="text-xs text-slate-400">Stretch</p>
                                            <p id="summaryStretch" class="font-bold text-slate-900">0</p>
                                        </div>

                                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                            <p class="text-xs text-slate-400">Actual</p>
                                            <p id="summaryActual" class="font-bold text-slate-900">0</p>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs text-slate-400">Status</p>
                                        <span id="summaryStatus" class="inline-flex mt-1 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                            Not Started
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card rounded-3xl shadow-lg border border-white/70 p-5">
                                <h2 class="font-bold text-slate-900">Weightage</h2>
                                <p class="text-xs text-slate-500 mt-1">How important this KPI is compared to other KPIs.</p>

                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                        <p class="text-xs text-slate-400">Used</p>
                                        <p class="text-xl font-bold">{{ number_format($usedWeightage ?? 0, 2) }}%</p>
                                    </div>

                                    <div class="bg-blue-50 rounded-2xl p-4 border border-blue-100">
                                        <p class="text-xs text-blue-500">Remaining</p>
                                        <p class="text-xl font-bold text-blue-700">{{ number_format($remainingWeightage ?? 100, 2) }}%</p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="font-semibold text-sm text-slate-700">This KPI Weightage (%)</label>
                                    <input
                                        name="weightage"
                                        id="weightage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="{{ $remainingWeightage ?? 100 }}"
                                        value="{{ old('weightage') }}"
                                        class="w-full mt-2 border border-slate-200 rounded-xl p-3 bg-slate-50"
                                        placeholder="Example: 20"
                                        required
                                    >

                                    <p id="weightageHelper" class="text-[11px] text-slate-400 mt-2">
                                        Maximum allowed: {{ number_format($remainingWeightage ?? 100, 2) }}%
                                    </p>
                                </div>

                                <div class="mt-4 w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                                    <div id="weightageProgress" class="bg-gradient-to-r from-blue-600 to-indigo-600 h-3 rounded-full transition-all" style="width: {{ min($usedWeightage ?? 0, 100) }}%"></div>
                                </div>
                            </div>

                            <button
                                id="submitButton"
                                type="submit"
                                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-slate-300 disabled:to-slate-300 disabled:cursor-not-allowed text-white font-bold p-4 rounded-2xl transition shadow-lg hover:shadow-xl"
                            >
                                Create KPI & View KPI List
                            </button>

                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>

</main>

<script>
    const subCategories = {
        "Financial": ["Revenue", "Operating Cost Optimisation"],
        "Growth & Customer": ["New Customer Acquisition", "Growth"],
        "Initiatives": ["Continuous Improvement & New Business"],
        "People": ["Certification of Competence (COC)", "Staff Development"]
    };

    const usedWeightage = Number(@json($usedWeightage ?? 0));
    const remainingWeightage = Number(@json($remainingWeightage ?? 100));

    const categoryInput = document.getElementById('category');
    const subCategoryInput = document.getElementById('subCategory');
    const weightageInput = document.getElementById('weightage');
    const weightageWarning = document.getElementById('weightageWarning');
    const weightageHelper = document.getElementById('weightageHelper');
    const submitButton = document.getElementById('submitButton');
    const weightageProgress = document.getElementById('weightageProgress');
    const remainingText = document.getElementById('remainingText');

    const unitInput = document.getElementById('unit');
    const unitLabels = document.querySelectorAll('.unitLabel');

    const kpiTitle = document.getElementById('kpiTitle');
    const baseTarget = document.getElementById('baseTarget');
    const stretchTarget = document.getElementById('stretchTarget');
    const actualValue = document.getElementById('actualValue');
    const statusInput = document.getElementById('status');

    const quarterTargetInputs = document.querySelectorAll('.quarter-target');
    const quarterActualInputs = document.querySelectorAll('.quarter-actual');

    function updateSubCategories() {
        const selectedCategory = categoryInput.value;
        const selectedOldSubCategory = @json(old('sub_category'));

        subCategoryInput.innerHTML = '';

        if (!selectedCategory || !subCategories[selectedCategory]) {
            subCategoryInput.innerHTML = '<option value="">Select Category first</option>';
            updateSummary();
            return;
        }

        subCategoryInput.innerHTML = '<option value="">Select Sub Category</option>';

        subCategories[selectedCategory].forEach(function (subCategory) {
            const option = document.createElement('option');
            option.value = subCategory;
            option.textContent = subCategory;

            if (selectedOldSubCategory === subCategory) {
                option.selected = true;
            }

            subCategoryInput.appendChild(option);
        });

        updateSummary();
    }

    function updateWeightageState() {
        const inputWeightage = Number(weightageInput.value || 0);
        const newTotal = usedWeightage + inputWeightage;
        const newRemaining = 100 - newTotal;
        const exceeded = inputWeightage > remainingWeightage || newTotal > 100;

        weightageProgress.style.width = Math.min(newTotal, 100) + '%';
        remainingText.textContent = Math.max(newRemaining, 0).toFixed(2) + '%';

        if (exceeded) {
            weightageInput.classList.add('border-red-500', 'bg-red-50', 'text-red-700');
            weightageWarning.classList.remove('hidden');
            weightageHelper.textContent = 'Exceeded. Maximum allowed is ' + remainingWeightage.toFixed(2) + '%.';
            weightageHelper.classList.remove('text-slate-400');
            weightageHelper.classList.add('text-red-600', 'font-semibold');
            submitButton.disabled = true;
        } else {
            weightageInput.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
            weightageWarning.classList.add('hidden');
            weightageHelper.textContent = 'Maximum allowed: ' + remainingWeightage.toFixed(2) + '%.';
            weightageHelper.classList.add('text-slate-400');
            weightageHelper.classList.remove('text-red-600', 'font-semibold');
            submitButton.disabled = false;
        }
    }

    function updateUnitState() {
        let label = 'Number';

        if (unitInput.value === 'currency') label = 'RM';
        if (unitInput.value === 'percentage') label = '%';

        unitLabels.forEach(function (item) {
            item.textContent = '(' + label + ')';
        });

        updateSummary();
    }

    function formatValue(value) {
        const number = Number(value || 0);

        if (unitInput.value === 'currency') {
            return 'RM ' + number.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        if (unitInput.value === 'percentage') {
            return number.toFixed(2) + '%';
        }

        return number.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function updateQuarterTotals() {
        let targetTotal = 0;
        let actualTotal = 0;

        quarterTargetInputs.forEach(input => {
            targetTotal += Number(input.value || 0);
        });

        quarterActualInputs.forEach(input => {
            actualTotal += Number(input.value || 0);
        });

        document.getElementById('quarterTargetTotal').textContent = formatValue(targetTotal);
        document.getElementById('quarterActualTotal').textContent = formatValue(actualTotal);

        const base = Number(baseTarget.value || 0);
        const status = document.getElementById('quarterMatchStatus');
        const box = document.getElementById('quarterStatusBox');

        box.className = 'rounded-2xl p-4 border';

        if (targetTotal <= 0) {
            status.textContent = 'Optional';
            status.className = 'text-sm font-bold text-blue-700';
            box.classList.add('bg-blue-50', 'border-blue-100');
            return;
        }

        if (base <= 0) {
            status.textContent = 'Fill FY Base first';
            status.className = 'text-sm font-bold text-amber-700';
            box.classList.add('bg-amber-50', 'border-amber-200');
            return;
        }

        if (targetTotal === base) {
            status.textContent = 'Matched with FY Base';
            status.className = 'text-sm font-bold text-emerald-700';
            box.classList.add('bg-emerald-50', 'border-emerald-200');
        } else if (targetTotal > base) {
            status.textContent = 'Quarter total exceeds FY Base';
            status.className = 'text-sm font-bold text-red-700';
            box.classList.add('bg-red-50', 'border-red-200');
        } else {
            status.textContent = 'Quarter total below FY Base';
            status.className = 'text-sm font-bold text-amber-700';
            box.classList.add('bg-amber-50', 'border-amber-200');
        }
    }

    function updateStatusBadge() {
        const badge = document.getElementById('summaryStatus');
        const status = statusInput.value;

        const labelMap = {
            not_started: 'Not Started',
            on_track: 'On Track',
            at_risk: 'At Risk',
            in_trouble: 'In Trouble',
            completed: 'Completed'
        };

        badge.textContent = labelMap[status] || '-';
        badge.className = 'inline-flex mt-1 px-3 py-1 rounded-full text-xs font-bold';

        if (status === 'not_started') badge.classList.add('bg-slate-100', 'text-slate-700');
        if (status === 'on_track') badge.classList.add('bg-blue-100', 'text-blue-700');
        if (status === 'at_risk') badge.classList.add('bg-amber-100', 'text-amber-700');
        if (status === 'in_trouble') badge.classList.add('bg-red-100', 'text-red-700');
        if (status === 'completed') badge.classList.add('bg-emerald-100', 'text-emerald-700');
    }

    function updateSummary() {
        document.getElementById('summaryTitle').textContent = kpiTitle.value || 'Not entered yet';
        document.getElementById('summaryCategory').textContent = categoryInput.value || '-';
        document.getElementById('summarySubCategory').textContent = subCategoryInput.value || '-';
        document.getElementById('summaryBase').textContent = formatValue(baseTarget.value);
        document.getElementById('summaryStretch').textContent = formatValue(stretchTarget.value);
        document.getElementById('summaryActual').textContent = formatValue(actualValue.value);

        updateQuarterTotals();
        updateStatusBadge();
    }

    function autoDivideQuarter() {
        const base = Number(baseTarget.value || 0);

        if (base <= 0) {
            alert('Please fill FY Base Target first.');
            return;
        }

        const quarterValue = base / 4;

        quarterTargetInputs.forEach(input => {
            input.value = quarterValue.toFixed(2);
        });

        updateSummary();
    }

    categoryInput.addEventListener('change', updateSubCategories);
    subCategoryInput.addEventListener('change', updateSummary);
    weightageInput.addEventListener('input', updateWeightageState);
    unitInput.addEventListener('change', updateUnitState);
    kpiTitle.addEventListener('input', updateSummary);
    baseTarget.addEventListener('input', updateSummary);
    stretchTarget.addEventListener('input', updateSummary);
    actualValue.addEventListener('input', updateSummary);
    statusInput.addEventListener('change', updateSummary);

    quarterTargetInputs.forEach(input => input.addEventListener('input', updateSummary));
    quarterActualInputs.forEach(input => input.addEventListener('input', updateSummary));

    updateSubCategories();
    updateWeightageState();
    updateUnitState();
    updateSummary();
</script>

</body>
</html>
