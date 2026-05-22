<!DOCTYPE html>
<html>
<head>
    <title>Create KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --navy: #06142f;
            --navy-soft: #0b1f46;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(59,130,246,.16), transparent 28%),
                linear-gradient(135deg, #f8fbff 0%, #eef5ff 45%, #f9fbff 100%);
        }

        .hero-gradient {
            background: linear-gradient(135deg, #06142f 0%, #0b1f46 55%, #101827 100%);
        }

        .soft-glow {
            box-shadow:
                0 22px 45px rgba(6,20,47,.10),
                0 8px 18px rgba(37,99,235,.08);
        }

        .glass-card {
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,.78);
            box-shadow: 0 18px 35px rgba(15,23,42,.06);
        }

        .step-bubble {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 900;
            background: linear-gradient(135deg, #06142f, #2563eb);
            box-shadow:
                0 10px 22px rgba(37,99,235,.25),
                0 0 0 8px rgba(37,99,235,.08);
        }

        .section-line {
            width: 6px;
            border-radius: 999px;
            background: linear-gradient(180deg, #06142f, #38bdf8, #dbeafe);
        }

        .quarter-card {
            background: linear-gradient(135deg, rgba(255,255,255,.95), rgba(248,250,252,.92));
            border: 1px solid rgba(226,232,240,.85);
            box-shadow: 0 12px 25px rgba(15,23,42,.05);
        }

        .quarter-dot {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 12px;
            color: #06142f;
            background: linear-gradient(135deg, #e0f2fe, #ffffff);
            box-shadow: 0 8px 18px rgba(14,165,233,.18);
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37,99,235,.12);
            background: #ffffff;
        }

        .field {
            border: 1px solid #dbe5f0;
            background: rgba(248,250,252,.85);
            transition: .18s ease;
        }

        .field:hover {
            border-color: #bfdbfe;
            background: #ffffff;
        }
    </style>
</head>

<body class="min-h-screen">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">

<div class="p-6">
<div class="max-w-7xl mx-auto space-y-5">

    <!-- HEADER -->
    <div class="hero-gradient rounded-[2rem] text-white p-6 soft-glow overflow-hidden">
        <a href="{{ route('kpi.index') }}" class="text-sm text-blue-100 hover:text-white">
            ← Back to KPI List
        </a>

        <div class="mt-3">
            <h1 class="text-3xl font-black tracking-tight">Create My KPI</h1>
            <p class="text-sm text-blue-100 mt-1">
                {{ $fy ?? 'FY' . now()->year }} · KPI ini akan direkod atas nama anda sendiri.
            </p>
        </div>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div class="bg-white/10 border border-white/15 rounded-3xl p-4">
                <p class="text-blue-100 text-xs">Owner</p>
                <p class="font-black text-lg">{{ $user['short_name'] ?? '-' }}</p>
            </div>

            <div class="bg-white/10 border border-white/15 rounded-3xl p-4">
                <p class="text-blue-100 text-xs">Role</p>
                <p class="font-black text-lg">{{ $user['role'] ?? '-' }}</p>
            </div>

            <div class="bg-white/10 border border-white/15 rounded-3xl p-4">
                <p class="text-blue-100 text-xs">Department</p>
                <p class="font-black text-lg">{{ $user['department_code'] ?? '-' }}</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 text-red-700 px-4 py-3 rounded-2xl text-sm border border-red-200 shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('kpi.store') }}" id="createKpiForm">
        @csrf

        <input type="hidden" name="financial_year" value="{{ $fy ?? 'FY' . now()->year }}">
        <input type="hidden" name="employee_id" value="{{ $user['id'] }}">
        <input type="hidden" name="created_by" value="{{ $user['id'] }}">
        <input type="hidden" name="department_code" value="{{ $user['department_code'] }}">
        <input type="hidden" name="company_code" value="{{ $user['company_code'] }}">
        <input type="hidden" name="kpi_scope" value="individual">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

            <div class="lg:col-span-8 space-y-5">

                <!-- 1. CATEGORY -->
                <section class="glass-card rounded-[2rem] p-5">
                    <div class="flex gap-4">
                        <div class="section-line"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="step-bubble">1</div>
                                <div>
                                    <h2 class="font-black text-slate-900">KPI Category</h2>
                                    <p class="text-xs text-slate-500 mt-1">Pilih area KPI yang sesuai.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                                <div>
                                    <label class="text-sm font-bold text-slate-700">Category</label>
                                    <select name="category" id="category" class="field w-full mt-2 rounded-2xl p-3" required>
                                        <option value="">Select Category</option>
                                        <option value="Financial" {{ old('category') === 'Financial' ? 'selected' : '' }}>Financial</option>
                                        <option value="Growth & Customer" {{ old('category') === 'Growth & Customer' ? 'selected' : '' }}>Growth & Customer</option>
                                        <option value="Initiatives" {{ old('category') === 'Initiatives' ? 'selected' : '' }}>Initiatives</option>
                                        <option value="People" {{ old('category') === 'People' ? 'selected' : '' }}>People</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Sub Category</label>
                                    <select name="sub_category" id="subCategory" class="field w-full mt-2 rounded-2xl p-3" required>
                                        <option value="">Select Category first</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. KPI DETAILS -->
                <section class="glass-card rounded-[2rem] p-5">
                    <div class="flex gap-4">
                        <div class="section-line"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="step-bubble">2</div>
                                <div>
                                    <h2 class="font-black text-slate-900">KPI Details</h2>
                                    <p class="text-xs text-slate-500 mt-1">Tulis KPI yang jelas dan boleh diukur.</p>
                                </div>
                            </div>

                            <div class="space-y-4 mt-5">
                                <div>
                                    <label class="text-sm font-bold text-slate-700">KPI Title</label>
                                    <input
                                        name="kpi_title"
                                        id="kpiTitle"
                                        value="{{ old('kpi_title') }}"
                                        class="field w-full mt-2 rounded-2xl p-3"
                                        placeholder="Example: Complete AI automation system for internal process"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Description</label>
                                    <textarea
                                        name="kpi_description"
                                        id="kpiDescription"
                                        rows="3"
                                        class="field w-full mt-2 rounded-2xl p-3"
                                        placeholder="Explain what this KPI is trying to achieve."
                                    >{{ old('kpi_description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. TARGET -->
                <section class="glass-card rounded-[2rem] p-5">
                    <div class="flex gap-4">
                        <div class="section-line"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="step-bubble">3</div>
                                <div>
                                    <h2 class="font-black text-slate-900">Full-Year Target</h2>
                                    <p class="text-xs text-slate-500 mt-1">Target tahunan dahulu. Quarter ialah pecahan target ini.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-5">
                                <div>
                                    <label class="text-sm font-bold text-slate-700">Unit</label>
                                    <select name="unit" id="unit" class="field w-full mt-2 rounded-2xl p-3" required>
                                        <option value="number" {{ old('unit') === 'number' ? 'selected' : '' }}>Number</option>
                                        <option value="currency" {{ old('unit') === 'currency' ? 'selected' : '' }}>Currency / RM</option>
                                        <option value="percentage" {{ old('unit') === 'percentage' ? 'selected' : '' }}>Percentage / %</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Base Target</label>
                                    <input name="base_target" id="baseTarget" type="number" step="0.01" value="{{ old('base_target') }}" class="field w-full mt-2 rounded-2xl p-3" required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Stretch Target</label>
                                    <input name="stretch_target" id="stretchTarget" type="number" step="0.01" value="{{ old('stretch_target') }}" class="field w-full mt-2 rounded-2xl p-3" required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Actual Now</label>
                                    <input name="actual_value" id="actualValue" type="number" step="0.01" value="{{ old('actual_value', 0) }}" class="field w-full mt-2 rounded-2xl p-3" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. STATUS -->
                <section class="glass-card rounded-[2rem] p-5">
                    <div class="flex gap-4">
                        <div class="section-line"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="step-bubble">4</div>
                                <div>
                                    <h2 class="font-black text-slate-900">Current Status & Remark</h2>
                                    <p class="text-xs text-slate-500 mt-1">Letak status keseluruhan KPI sekarang.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
                                <div>
                                    <label class="text-sm font-bold text-slate-700">Status</label>
                                    <select name="status" id="status" class="field w-full mt-2 rounded-2xl p-3" required>
                                        <option value="not_started" {{ old('status', 'not_started') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                        <option value="on_track" {{ old('status') === 'on_track' ? 'selected' : '' }}>On Track</option>
                                        <option value="at_risk" {{ old('status') === 'at_risk' ? 'selected' : '' }}>At Risk</option>
                                        <option value="in_trouble" {{ old('status') === 'in_trouble' ? 'selected' : '' }}>In Trouble</option>
                                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-sm font-bold text-slate-700">Remark</label>
                                    <textarea name="remark" id="remark" rows="2" class="field w-full mt-2 rounded-2xl p-3" placeholder="Optional note">{{ old('remark') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 5. QUARTERS -->
                <section class="glass-card rounded-[2rem] p-5">
                    <div class="flex gap-4">
                        <div class="section-line"></div>
                        <div class="flex-1">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="step-bubble">5</div>
                                    <div>
                                        <h2 class="font-black text-slate-900">Quarter Breakdown</h2>
                                        <p class="text-xs text-slate-500 mt-1">Setiap KPI ada Q1, Q2, Q3, Q4.</p>
                                    </div>
                                </div>

                                <button type="button" onclick="autoDivideQuarter()" class="bg-[#06142f] hover:bg-[#0b1f46] text-white text-xs font-black px-5 py-3 rounded-2xl shadow-lg shadow-blue-900/20">
                                    Auto Divide Base
                                </button>
                            </div>

                            <div class="space-y-4 mt-5">
                                @foreach(['Q1','Q2','Q3','Q4'] as $quarter)
                                    <div class="quarter-card rounded-[1.6rem] p-5">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="quarter-dot">{{ $quarter }}</div>
                                            <div>
                                                <h3 class="font-black text-slate-900">{{ $quarter }} Plan</h3>
                                                <p class="text-xs text-slate-500">Specific target and action for {{ $quarter }}.</p>
                                            </div>
                                        </div>

                                        <input type="hidden" name="quarters[{{ $quarter }}][quarter]" value="{{ $quarter }}">

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="text-xs font-bold text-slate-600">Quarter Title</label>
                                                <input name="quarters[{{ $quarter }}][quarter_title]" value="{{ old("quarters.$quarter.quarter_title") }}" class="field w-full mt-1 rounded-2xl p-3 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-slate-600">Quarter Status</label>
                                                <select name="quarters[{{ $quarter }}][status]" class="field w-full mt-1 rounded-2xl p-3 text-sm">
                                                    <option value="not_started" {{ old("quarters.$quarter.status", 'not_started') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                                    <option value="on_track" {{ old("quarters.$quarter.status") === 'on_track' ? 'selected' : '' }}>On Track</option>
                                                    <option value="at_risk" {{ old("quarters.$quarter.status") === 'at_risk' ? 'selected' : '' }}>At Risk</option>
                                                    <option value="in_trouble" {{ old("quarters.$quarter.status") === 'in_trouble' ? 'selected' : '' }}>In Trouble</option>
                                                    <option value="completed" {{ old("quarters.$quarter.status") === 'completed' ? 'selected' : '' }}>Completed</option>
                                                </select>
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="text-xs font-bold text-slate-600">Quarter Description</label>
                                                <textarea name="quarters[{{ $quarter }}][quarter_description]" rows="2" class="field w-full mt-1 rounded-2xl p-3 text-sm">{{ old("quarters.$quarter.quarter_description") }}</textarea>
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-slate-600">Quarter Target</label>
                                                <input type="number" step="0.01" name="quarters[{{ $quarter }}][quarter_target]" value="{{ old("quarters.$quarter.quarter_target") }}" class="quarter-target field w-full mt-1 rounded-2xl p-3 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-slate-600">Quarter Actual</label>
                                                <input type="number" step="0.01" name="quarters[{{ $quarter }}][quarter_actual]" value="{{ old("quarters.$quarter.quarter_actual", 0) }}" class="quarter-actual field w-full mt-1 rounded-2xl p-3 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-slate-600">Start Date</label>
                                                <input type="date" name="quarters[{{ $quarter }}][start_date]" value="{{ old("quarters.$quarter.start_date") }}" class="field w-full mt-1 rounded-2xl p-3 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-slate-600">End Date</label>
                                                <input type="date" name="quarters[{{ $quarter }}][end_date]" value="{{ old("quarters.$quarter.end_date") }}" class="field w-full mt-1 rounded-2xl p-3 text-sm">
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="text-xs font-bold text-slate-600">Quarter Remark</label>
                                                <textarea name="quarters[{{ $quarter }}][remark]" rows="2" class="field w-full mt-1 rounded-2xl p-3 text-sm">{{ old("quarters.$quarter.remark") }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-5">
                                <div class="rounded-3xl bg-white/80 border border-blue-100 p-4 shadow-sm shadow-blue-100">
                                    <p class="text-xs text-slate-500">Quarter Target Total</p>
                                    <p id="quarterTargetTotal" class="text-xl font-black text-[#06142f]">0.00</p>
                                </div>

                                <div class="rounded-3xl bg-white/80 border border-cyan-100 p-4 shadow-sm shadow-cyan-100">
                                    <p class="text-xs text-slate-500">Quarter Actual Total</p>
                                    <p id="quarterActualTotal" class="text-xl font-black text-[#06142f]">0.00</p>
                                </div>

                                <div id="quarterStatusBox" class="rounded-3xl bg-blue-50 border border-blue-100 p-4">
                                    <p class="text-xs text-blue-500">Quarter vs Base Target</p>
                                    <p id="quarterMatchStatus" class="text-sm font-black text-blue-700">Optional</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- SUMMARY -->
            <aside class="lg:col-span-4">
                <div class="sticky top-5 space-y-4">
                    <div class="glass-card rounded-[2rem] p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-[#06142f] text-white flex items-center justify-center shadow-lg shadow-blue-900/20 font-black">
                                ✓
                            </div>
                            <div>
                                <h2 class="font-black text-slate-900">KPI Summary</h2>
                                <p class="text-xs text-slate-500 mt-1">Semak sebelum submit.</p>
                            </div>
                        </div>

                        <div class="space-y-4 mt-5 text-sm">
                            <div class="rounded-3xl bg-slate-50 border border-slate-100 p-4">
                                <p class="text-xs text-slate-400">Owner</p>
                                <p class="font-black text-[#06142f]">{{ $user['short_name'] ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">Title</p>
                                <p id="summaryTitle" class="font-black text-slate-900">Not entered yet</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-2xl bg-blue-50 border border-blue-100 p-3">
                                    <p class="text-xs text-blue-500">Category</p>
                                    <p id="summaryCategory" class="font-bold text-[#06142f]">-</p>
                                </div>

                                <div class="rounded-2xl bg-cyan-50 border border-cyan-100 p-3">
                                    <p class="text-xs text-cyan-600">Sub Category</p>
                                    <p id="summarySubCategory" class="font-bold text-[#06142f]">-</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="bg-white rounded-2xl p-3 border border-blue-100 shadow-sm">
                                    <p class="text-xs text-slate-400">Base</p>
                                    <p id="summaryBase" class="font-black text-[#06142f]">0</p>
                                </div>

                                <div class="bg-white rounded-2xl p-3 border border-purple-100 shadow-sm">
                                    <p class="text-xs text-slate-400">Stretch</p>
                                    <p id="summaryStretch" class="font-black text-[#06142f]">0</p>
                                </div>

                                <div class="bg-white rounded-2xl p-3 border border-cyan-100 shadow-sm">
                                    <p class="text-xs text-slate-400">Actual</p>
                                    <p id="summaryActual" class="font-black text-[#06142f]">0</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">Status</p>
                                <span id="summaryStatus" class="inline-flex mt-1 px-3 py-1 rounded-full text-xs font-black bg-slate-100 text-slate-700">
                                    Not Started
                                </span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-[#06142f] hover:bg-[#0b1f46] text-white font-black p-4 rounded-3xl shadow-xl shadow-blue-900/20 transition">
                        Create My KPI
                    </button>
                </div>
            </aside>

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

    const categoryInput = document.getElementById('category');
    const subCategoryInput = document.getElementById('subCategory');
    const unitInput = document.getElementById('unit');
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

        box.className = 'rounded-3xl p-4 border';

        if (targetTotal <= 0) {
            status.textContent = 'Optional';
            status.className = 'text-sm font-black text-blue-700';
            box.classList.add('bg-blue-50', 'border-blue-100');
            return;
        }

        if (base <= 0) {
            status.textContent = 'Fill Base Target first';
            status.className = 'text-sm font-black text-amber-700';
            box.classList.add('bg-amber-50', 'border-amber-200');
            return;
        }

        if (Math.abs(targetTotal - base) < 0.01) {
            status.textContent = 'Matched with Base Target';
            status.className = 'text-sm font-black text-emerald-700';
            box.classList.add('bg-emerald-50', 'border-emerald-200');
        } else if (targetTotal > base) {
            status.textContent = 'Quarter total exceeds Base Target';
            status.className = 'text-sm font-black text-red-700';
            box.classList.add('bg-red-50', 'border-red-200');
        } else {
            status.textContent = 'Quarter total below Base Target';
            status.className = 'text-sm font-black text-amber-700';
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
        badge.className = 'inline-flex mt-1 px-3 py-1 rounded-full text-xs font-black';

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
            alert('Please fill Base Target first.');
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
    unitInput.addEventListener('change', updateSummary);
    kpiTitle.addEventListener('input', updateSummary);
    baseTarget.addEventListener('input', updateSummary);
    stretchTarget.addEventListener('input', updateSummary);
    actualValue.addEventListener('input', updateSummary);
    statusInput.addEventListener('change', updateSummary);

    quarterTargetInputs.forEach(input => input.addEventListener('input', updateSummary));
    quarterActualInputs.forEach(input => input.addEventListener('input', updateSummary));

    updateSubCategories();
    updateSummary();
</script>

</body>
</html>
