<!DOCTYPE html>
<html>
<head>
    <title>Create KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root{
            --navy:#06142f;
            --navy-soft:#0b1f46;
            --slate:#0f172a;
            --surface:#ffffff;
            --surface-soft:#f8fafc;
            --border:#e2e8f0;
            --text:#0f172a;
            --muted:#64748b;

            --blue:#2563eb;
            --blue-soft:#dbeafe;

            --cyan:#06b6d4;
            --cyan-soft:#cffafe;

            --emerald:#10b981;
            --emerald-soft:#d1fae5;

            --amber:#f59e0b;
            --amber-soft:#fef3c7;

            --red:#ef4444;
            --red-soft:#fee2e2;

            --purple:#7c3aed;
            --purple-soft:#ede9fe;
        }

        body{
            background:
                radial-gradient(circle at top left, rgba(37,99,235,.08), transparent 24%),
                radial-gradient(circle at bottom right, rgba(6,182,212,.08), transparent 28%),
                linear-gradient(135deg,#f8fbff 0%,#eff6ff 45%,#f8fafc 100%);
        }

        .hero-gradient{
            background:
                linear-gradient(
                    135deg,
                    #06142f 0%,
                    #0b1f46 45%,
                    #101827 100%
                );
        }

        .soft-glow{
            box-shadow:
                0 18px 40px rgba(15,23,42,.08),
                0 8px 20px rgba(37,99,235,.06);
        }

        .glass-card{
            background:rgba(255,255,255,.86);
            backdrop-filter:blur(18px);

            border:1px solid rgba(255,255,255,.9);

            box-shadow:
                0 14px 30px rgba(15,23,42,.05);
        }

        .section-card{
            position:relative;
            overflow:hidden;

            background:rgba(255,255,255,.92);

            border:1px solid rgba(226,232,240,.9);

            backdrop-filter:blur(14px);

            box-shadow:
                0 12px 28px rgba(15,23,42,.05);

            border-radius:32px;
        }

        .section-card::before{
            content:'';
            position:absolute;
            top:0;
            left:0;

            width:100%;
            height:1px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(255,255,255,.95),
                    transparent
                );
        }

        .section-line{
            width:5px;
            border-radius:999px;

            background:
                linear-gradient(
                    180deg,
                    #06142f 0%,
                    #2563eb 45%,
                    #06b6d4 100%
                );

            opacity:.9;
        }

        .step-bubble{
            width:58px;
            height:58px;

            border-radius:22px;

            display:flex;
            align-items:center;
            justify-content:center;

            font-weight:900;
            font-size:18px;

            color:white;

            background:
                linear-gradient(
                    135deg,
                    #06142f 0%,
                    #2563eb 55%,
                    #06b6d4 100%
                );

            box-shadow:
                0 12px 25px rgba(37,99,235,.22);

            flex-shrink:0;

            border:1px solid rgba(255,255,255,.15);
        }

        .field{
            width:100%;

            border:1px solid #dbe5f0;

            background:
                linear-gradient(
                    180deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.98)
                );

            color:#0f172a;

            transition:.18s ease;

            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.7);
        }

        .field:hover{
            border-color:#93c5fd;
            background:white;
        }

        .field:focus{
            outline:none;

            border-color:#2563eb;

            background:white;

            box-shadow:
                0 0 0 4px rgba(37,99,235,.10),
                0 10px 20px rgba(37,99,235,.06);
        }

        .quarter-card{
            background:
                linear-gradient(
                    180deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.98)
                );

            border:1px solid #e2e8f0;

            border-radius:28px;

            transition:.18s ease;

            box-shadow:
                0 8px 24px rgba(15,23,42,.04);
        }

        .quarter-card:hover{
            transform:translateY(-2px);

            border-color:#bfdbfe;

            box-shadow:
                0 18px 35px rgba(15,23,42,.07);
        }

        .quarter-dot{
            width:44px;
            height:44px;

            border-radius:16px;

            display:flex;
            align-items:center;
            justify-content:center;

            font-weight:900;
            font-size:13px;

            color:#06142f;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff 0%,
                    #ffffff 100%
                );

            border:1px solid #dbeafe;

            box-shadow:
                0 8px 20px rgba(37,99,235,.12);
        }

        .summary-card{
            background:
                linear-gradient(
                    180deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.98)
                );

            border:1px solid #e2e8f0;

            border-radius:28px;

            box-shadow:
                0 12px 28px rgba(15,23,42,.05);
        }

        .metric-card{
            border-radius:22px;

            background:white;

            border:1px solid #e2e8f0;

            box-shadow:
                0 6px 18px rgba(15,23,42,.04);
        }

        .metric-blue{
            background:linear-gradient(180deg,#f8fbff,#eff6ff);
            border-color:#dbeafe;
        }

        .metric-cyan{
            background:linear-gradient(180deg,#f4feff,#ecfeff);
            border-color:#cffafe;
        }

        .metric-purple{
            background:linear-gradient(180deg,#faf7ff,#f5f3ff);
            border-color:#ede9fe;
        }

        .metric-emerald{
            background:linear-gradient(180deg,#f5fffb,#ecfdf5);
            border-color:#d1fae5;
        }

        .metric-amber{
            background:linear-gradient(180deg,#fffdf5,#fffbeb);
            border-color:#fde68a;
        }

        .metric-red{
            background:linear-gradient(180deg,#fff8f8,#fef2f2);
            border-color:#fecaca;
        }

        .status-pill{
            display:inline-flex;
            align-items:center;
            justify-content:center;

            padding:.5rem .9rem;

            border-radius:999px;

            font-size:12px;
            font-weight:800;

            border:1px solid transparent;
        }

        .status-not-started{
            background:#f1f5f9;
            color:#475569;
            border-color:#e2e8f0;
        }

        .status-on-track{
            background:#eff6ff;
            color:#1d4ed8;
            border-color:#bfdbfe;
        }

        .status-risk{
            background:#fffbeb;
            color:#b45309;
            border-color:#fde68a;
        }

        .status-trouble{
            background:#fef2f2;
            color:#b91c1c;
            border-color:#fecaca;
        }

        .status-completed{
            background:#ecfdf5;
            color:#047857;
            border-color:#a7f3d0;
        }

        .primary-btn{
            background:
                linear-gradient(
                    135deg,
                    #06142f 0%,
                    #0b1f46 45%,
                    #2563eb 100%
                );

            border:1px solid rgba(255,255,255,.08);

            box-shadow:
                0 14px 30px rgba(6,20,47,.20);
        }

        .primary-btn:hover{
            transform:translateY(-1px);

            box-shadow:
                0 20px 35px rgba(6,20,47,.25);
        }

        .outline-btn{
            background:white;

            border:1px solid #dbeafe;

            color:#1d4ed8;

            transition:.18s ease;
        }

        .outline-btn:hover{
            background:#eff6ff;
            border-color:#93c5fd;
        }

        .info-card{
            border-radius:24px;

            border:1px solid #e2e8f0;

            background:
                linear-gradient(
                    180deg,
                    rgba(255,255,255,.95),
                    rgba(248,250,252,.95)
                );
        }

        .info-card-blue{
            border-color:#dbeafe;
            background:
                linear-gradient(
                    180deg,
                    #f8fbff,
                    #eff6ff
                );
        }

        .info-card-cyan{
            border-color:#cffafe;
            background:
                linear-gradient(
                    180deg,
                    #f4feff,
                    #ecfeff
                );
        }

        .info-card-emerald{
            border-color:#d1fae5;
            background:
                linear-gradient(
                    180deg,
                    #f5fffb,
                    #ecfdf5
                );
        }

        .info-card-amber{
            border-color:#fde68a;
            background:
                linear-gradient(
                    180deg,
                    #fffdf5,
                    #fffbeb
                );
        }

        .info-card-red{
            border-color:#fecaca;
            background:
                linear-gradient(
                    180deg,
                    #fff8f8,
                    #fef2f2
                );
        }

        .label{
            font-size:13px;
            font-weight:800;
            color:#334155;
            letter-spacing:.01em;
        }

        .helper{
            font-size:12px;
            color:#64748b;
            line-height:1.5;
        }

        .floating-glow{
            position:absolute;
            border-radius:999px;
            filter:blur(60px);
            opacity:.18;
            pointer-events:none;
        }

        .floating-blue{
            width:180px;
            height:180px;
            top:-40px;
            right:-40px;
            background:#60a5fa;
        }

        .floating-cyan{
            width:140px;
            height:140px;
            bottom:-40px;
            left:-40px;
            background:#22d3ee;
        }

        .divider-soft{
            height:1px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(148,163,184,.35),
                    transparent
                );
        }

        .mini-badge{
            padding:.28rem .7rem;

            border-radius:999px;

            font-size:11px;
            font-weight:800;

            background:#eff6ff;
            color:#1d4ed8;

            border:1px solid #dbeafe;
        }

        .sidebar-shadow{
            box-shadow:
                4px 0 25px rgba(15,23,42,.06);
        }

        .table-soft{
            border-collapse:separate;
            border-spacing:0 10px;
        }

        .table-soft tr{
            background:white;
            box-shadow:
                0 6px 18px rgba(15,23,42,.04);
        }

        .table-soft td{
            padding:16px;
        }

        .table-soft tr td:first-child{
            border-top-left-radius:18px;
            border-bottom-left-radius:18px;
        }

        .table-soft tr td:last-child{
            border-top-right-radius:18px;
            border-bottom-right-radius:18px;
        }

        .card-hover{
            transition:.18s ease;
        }

        .card-hover:hover{
            transform:translateY(-2px);
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
        <input type="hidden" name="kpi_scope" value="individual">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

            <div class="lg:col-span-8 space-y-5">

                <!-- 1. KPI OWNERSHIP -->
                <section class="rounded-[2rem] border border-slate-200/70 bg-white/90 backdrop-blur-xl shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <!-- DECOR -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-cyan-200/20 blur-3xl rounded-full"></div>

                    <div class="relative flex gap-4">

                        <div class="w-2 rounded-full bg-gradient-to-b from-blue-700 to-cyan-500"></div>

                        <div class="flex-1">

                            <div class="flex items-center gap-4">

                                <div class="w-14 h-14 rounded-3xl bg-gradient-to-br from-indigo-600 to-cyan-500 text-white flex items-center justify-center font-black text-xl shadow-xl shadow-indigo-300/40">
                                    1
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 text-xl">
                                        KPI Ownership & Assignment
                                    </h2>
                                </div>

                            </div>

                            <!-- OWNER CARD -->
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                                <!-- OWNER -->
                                <div class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-5 shadow-sm">

                                    <div class="flex items-center gap-4">

                                        <div class="w-14 h-14 rounded-2xl bg-[#06142f] text-white flex items-center justify-center font-black text-lg shadow-lg shadow-slate-900/20">
                                            O
                                        </div>

                                        <div>
                                            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-black">
                                                KPI OWNER
                                            </p>

                                            <h3 class="font-black text-slate-900 text-xl">
                                                {{ $user['short_name'] }}
                                            </h3>

                                            <p class="text-xs text-slate-500 mt-1">
                                                {{ $user['role'] }} · {{ $user['department_code'] }}
                                            </p>
                                        </div>

                                    </div>

                                </div>

                                <!-- ASSIGN -->
                                <div class="rounded-[2rem] border border-blue-100 bg-[#f8fbff] p-5 shadow-sm shadow-blue-100/30">

                                    <div class="flex items-center gap-4">

                                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white flex items-center justify-center font-black text-lg shadow-lg shadow-cyan-200">
                                            A
                                        </div>

                                        <div>
                                            <p class="text-[11px] uppercase tracking-wider text-blue-500 font-black">
                                                KPI ASSIGNMENT
                                            </p>

                                            <h3 class="font-black text-slate-900 text-xl">
                                                Execution Owner
                                            </h3>
                                        </div>

                                    </div>

                                    <div class="mt-5">

                                        <label class="text-sm font-bold text-slate-700">
                                            Assign To
                                        </label>

                                        <select
                                            name="assigned_employee_id"
                                            id="assignedEmployee"
                                            class="w-full mt-2 rounded-2xl border border-blue-200 bg-white px-4 py-3 font-semibold text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                        >

                                            <option value="">
                                                No Assignment
                                            </option>

                                            <option value="{{ $user['id'] }}">
                                                Myself - {{ $user['short_name'] }}
                                            </option>

                                            @foreach($reportingStaff as $staff)

                                                <option value="{{ $staff['id'] }}">

                                                    {{ $staff['short_name'] }}
                                                    ({{ $staff['role'] }})

                                                </option>

                                            @endforeach

                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- 2. KPI CATEGORY -->
                <section class="rounded-[2rem] border border-slate-200/70 bg-white/90 backdrop-blur-xl shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-emerald-200/20 blur-3xl rounded-full"></div>

                    <div class="relative flex gap-4">

                        <div class="w-2 rounded-full bg-gradient-to-b from-[#06142f] to-slate-500"></div>

                        <div class="flex-1">

                            <div class="flex items-center gap-4">

                                <div class="w-14 h-14 rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center font-black text-xl shadow-xl shadow-emerald-300/40">
                                    2
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 text-xl">
                                        KPI Category
                                    </h2>

                                    <p class="text-sm text-slate-600 mt-1">
                                        Pilih jenis KPI berdasarkan focus business area.
                                    </p>
                                </div>

                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">

                                <div>

                                    <label class="text-sm font-bold text-slate-700">
                                        Category
                                    </label>

                                    <select
                                        name="category"
                                        id="category"
                                        class="field w-full mt-2 rounded-2xl p-3 border-slate-200 bg-slate-50"
                                        required
                                    >

                                        <option value="">
                                            Select Category
                                        </option>

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

                                    <label class="text-sm font-bold text-slate-700">
                                        Sub Category
                                    </label>

                                    <select
                                        name="sub_category"
                                        id="subCategory"
                                        class="field w-full mt-2 rounded-2xl p-3 border-teal-200 bg-teal-50/40"
                                        required
                                    >

                                        <option value="">
                                            Select Category first
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- 3. KPI DETAILS -->
                <section class="rounded-[2rem] border border-slate-200/70 bg-white/90 backdrop-blur-xl shadow-[0_10px_30px_rgba(15,23,42,0.05)] overflow-hidden relative p-5">
                    <div class="flex gap-4">
                        <div class="w-1 rounded-full bg-gradient-to-b from-[#06142f] to-indigo-500"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="step-bubble">3</div>
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



                <!-- 4. TARGET -->
                <section class="rounded-[2rem] p-5 border border-slate-200/70 bg-white/90 backdrop-blur-xl shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <div class="flex gap-4">
                        <div class="w-1 rounded-full bg-gradient-to-b from-[#06142f] to-sky-500"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="step-bubble">4</div>
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

                <!-- 5. STATUS -->
                <section class="rounded-[2rem] p-5 border border-slate-200/70 bg-white/90 backdrop-blur-xl shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <div class="flex gap-4">
                        <div class="w-1 rounded-full bg-gradient-to-b from-amber-500 to-red-500"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="step-bubble">5</div>
                                <div>
                                    <h2 class="font-black text-slate-900">Current Status & Remark</h2>
                                    <p class="text-xs text-slate-500 mt-1">Letak status keseluruhan KPI sekarang.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
                                <div>
                                    <label class="text-sm font-bold text-slate-700">Status</label>
                                    <select
                                        name="status"
                                        id="status"
                                        class="w-full mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold transition-all duration-200"
                                        required
                                    >

                                        <option value="not_started"
                                            {{ old('status', 'not_started') === 'not_started' ? 'selected' : '' }}>
                                            Not Started
                                        </option>

                                        <option value="on_track"
                                            {{ old('status') === 'on_track' ? 'selected' : '' }}>
                                            On Track
                                        </option>

                                        <option value="at_risk"
                                            {{ old('status') === 'at_risk' ? 'selected' : '' }}>
                                            At Risk
                                        </option>

                                        <option value="in_trouble"
                                            {{ old('status') === 'in_trouble' ? 'selected' : '' }}>
                                            In Trouble
                                        </option>

                                        <option value="completed"
                                            {{ old('status') === 'completed' ? 'selected' : '' }}>
                                            Completed
                                        </option>

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
                <section class="section-card p-6">

                    <div class="floating-glow floating-blue"></div>
                    <div class="floating-glow floating-cyan"></div>

                    <div class="relative flex gap-4">

                        <div class="section-line"></div>

                        <div class="flex-1">

                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                                <div class="flex items-center gap-4">

                                    <div class="step-bubble">
                                        6
                                    </div>

                                    <div>
                                        <h2 class="font-black text-slate-900 text-xl">
                                            Quarter Breakdown
                                        </h2>

                                        <p class="helper mt-1">
                                            Breakdown target tahunan kepada execution plan setiap quarter.
                                        </p>
                                    </div>

                                </div>

                                <button
                                    type="button"
                                    onclick="autoDivideQuarter()"
                                    class="outline-btn px-5 py-3 rounded-2xl text-sm font-black"
                                >
                                    Auto Divide Base
                                </button>

                            </div>

                            <div class="divider-soft my-6"></div>

                            <div class="space-y-5">

                                @foreach(['Q1','Q2','Q3','Q4'] as $quarter)

                                    <div class="quarter-card p-5">

                                        <div class="flex items-center justify-between gap-4 mb-5">

                                            <div class="flex items-center gap-3">

                                                <div class="quarter-dot">
                                                    {{ $quarter }}
                                                </div>

                                                <div>
                                                    <h3 class="font-black text-slate-900">
                                                        {{ $quarter }} Planning
                                                    </h3>

                                                    <p class="helper">
                                                        Specific target, timeline dan action plan.
                                                    </p>
                                                </div>

                                            </div>

                                            <div class="mini-badge">
                                                Quarterly Execution
                                            </div>

                                        </div>

                                        <input
                                            type="hidden"
                                            name="quarters[{{ $quarter }}][quarter]"
                                            value="{{ $quarter }}"
                                        >

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                            <!-- TITLE -->
                                            <div>

                                                <label class="label">
                                                    Quarter Title
                                                </label>

                                                <input
                                                    name="quarters[{{ $quarter }}][quarter_title]"
                                                    value="{{ old("quarters.$quarter.quarter_title") }}"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                    placeholder="Example: Launch AI KPI dashboard V1"
                                                >

                                            </div>

                                            <!-- STATUS -->
                                            <div>

                                                <label class="label">
                                                    Quarter Status
                                                </label>

                                                <select
                                                    name="quarters[{{ $quarter }}][status]"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                >

                                                    <option value="not_started"
                                                        {{ old("quarters.$quarter.status",'not_started') === 'not_started' ? 'selected' : '' }}>
                                                        Not Started
                                                    </option>

                                                    <option value="on_track"
                                                        {{ old("quarters.$quarter.status") === 'on_track' ? 'selected' : '' }}>
                                                        On Track
                                                    </option>

                                                    <option value="at_risk"
                                                        {{ old("quarters.$quarter.status") === 'at_risk' ? 'selected' : '' }}>
                                                        At Risk
                                                    </option>

                                                    <option value="in_trouble"
                                                        {{ old("quarters.$quarter.status") === 'in_trouble' ? 'selected' : '' }}>
                                                        In Trouble
                                                    </option>

                                                    <option value="completed"
                                                        {{ old("quarters.$quarter.status") === 'completed' ? 'selected' : '' }}>
                                                        Completed
                                                    </option>

                                                </select>

                                            </div>

                                            <!-- DESCRIPTION -->
                                            <div class="md:col-span-2">

                                                <label class="label">
                                                    Quarter Description
                                                </label>

                                                <textarea
                                                    name="quarters[{{ $quarter }}][quarter_description]"
                                                    rows="3"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                    placeholder="Explain the focus and expected outcome for this quarter."
                                                >{{ old("quarters.$quarter.quarter_description") }}</textarea>

                                            </div>

                                            <!-- TARGET -->
                                            <div>

                                                <label class="label">
                                                    Quarter Target
                                                </label>

                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="quarters[{{ $quarter }}][quarter_target]"
                                                    value="{{ old("quarters.$quarter.quarter_target") }}"
                                                    class="quarter-target field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                >

                                            </div>

                                            <!-- ACTUAL -->
                                            <div>

                                                <label class="label">
                                                    Quarter Actual
                                                </label>

                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="quarters[{{ $quarter }}][quarter_actual]"
                                                    value="{{ old("quarters.$quarter.quarter_actual",0) }}"
                                                    class="quarter-actual field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                >

                                            </div>

                                            <!-- START -->
                                            <div>

                                                <label class="label">
                                                    Start Date
                                                </label>

                                                <input
                                                    type="date"
                                                    name="quarters[{{ $quarter }}][start_date]"
                                                    value="{{ old("quarters.$quarter.start_date") }}"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                >

                                            </div>

                                            <!-- END -->
                                            <div>

                                                <label class="label">
                                                    End Date
                                                </label>

                                                <input
                                                    type="date"
                                                    name="quarters[{{ $quarter }}][end_date]"
                                                    value="{{ old("quarters.$quarter.end_date") }}"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                >

                                            </div>

                                            <!-- REMARK -->
                                            <div class="md:col-span-2">

                                                <label class="label">
                                                    Quarter Remark
                                                </label>

                                                <textarea
                                                    name="quarters[{{ $quarter }}][remark]"
                                                    rows="2"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                    placeholder="Optional internal notes"
                                                >{{ old("quarters.$quarter.remark") }}</textarea>

                                            </div>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                            <!-- SUMMARY -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">

                                <div class="metric-card metric-blue p-5">

                                    <p class="text-xs font-bold text-blue-500 uppercase tracking-wide">
                                        Quarter Target Total
                                    </p>

                                    <p
                                        id="quarterTargetTotal"
                                        class="text-2xl font-black text-[#06142f] mt-2"
                                    >
                                        0.00
                                    </p>

                                </div>

                                <div class="metric-card metric-cyan p-5">

                                    <p class="text-xs font-bold text-cyan-600 uppercase tracking-wide">
                                        Quarter Actual Total
                                    </p>

                                    <p
                                        id="quarterActualTotal"
                                        class="text-2xl font-black text-[#06142f] mt-2"
                                    >
                                        0.00
                                    </p>

                                </div>

                                <div
                                    id="quarterStatusBox"
                                    class="metric-card metric-blue p-5"
                                >

                                    <p class="text-xs font-bold text-blue-500 uppercase tracking-wide">
                                        Quarter vs Base
                                    </p>

                                    <p
                                        id="quarterMatchStatus"
                                        class="text-sm font-black text-blue-700 mt-2"
                                    >
                                        Optional
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

            </div>

            <!-- SUMMARY -->
            <aside class="lg:col-span-4">
                <div class="sticky top-5 space-y-4">
                    <div class="rounded-[2rem] p-5 border border-slate-200 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
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
    /*
    |--------------------------------------------------------------------------
    | SUB CATEGORY MAP
    |--------------------------------------------------------------------------
    */

    const subCategories = {
        "Financial": [
            "Revenue",
            "Operating Cost Optimisation"
        ],

        "Growth & Customer": [
            "New Customer Acquisition",
            "Growth"
        ],

        "Initiatives": [
            "Continuous Improvement & New Business"
        ],

        "People": [
            "Certification of Competence (COC)",
            "Staff Development"
        ]
    };

    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const form = document.getElementById('createKpiForm');

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

    /*
    |--------------------------------------------------------------------------
    | SUB CATEGORY
    |--------------------------------------------------------------------------
    */

    function updateSubCategories() {

        const selectedCategory = categoryInput.value;
        const oldSubCategory = @json(old('sub_category'));

        subCategoryInput.innerHTML = '';

        if (
            !selectedCategory ||
            !subCategories[selectedCategory]
        ) {

            subCategoryInput.innerHTML =
                '<option value="">Select Category first</option>';

            updateSummary();

            return;
        }

        subCategoryInput.innerHTML =
            '<option value="">Select Sub Category</option>';

        subCategories[selectedCategory].forEach(subCategory => {

            const option = document.createElement('option');

            option.value = subCategory;
            option.textContent = subCategory;

            if (oldSubCategory === subCategory) {
                option.selected = true;
            }

            subCategoryInput.appendChild(option);

        });

        updateSummary();
    }

    /*
    |--------------------------------------------------------------------------
    | FORMAT VALUE
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | QUARTER TOTAL
    |--------------------------------------------------------------------------
    */

    function updateQuarterTotals() {

        let targetTotal = 0;
        let actualTotal = 0;

        quarterTargetInputs.forEach(input => {
            targetTotal += Number(input.value || 0);
        });

        quarterActualInputs.forEach(input => {
            actualTotal += Number(input.value || 0);
        });

        document.getElementById('quarterTargetTotal')
            .textContent = formatValue(targetTotal);

        document.getElementById('quarterActualTotal')
            .textContent = formatValue(actualTotal);

        const base = Number(baseTarget.value || 0);

        const statusText =
            document.getElementById('quarterMatchStatus');

        const statusBox =
            document.getElementById('quarterStatusBox');

        statusBox.className =
            'metric-card p-5';

        /*
        |--------------------------------------------------------------------------
        | STATUS LOGIC
        |--------------------------------------------------------------------------
        */

        if (targetTotal <= 0) {

            statusText.textContent = 'Optional';

            statusText.className =
                'text-sm font-black text-blue-700';

            statusBox.classList.add(
                'metric-blue'
            );

            return;
        }

        if (base <= 0) {

            statusText.textContent =
                'Please fill Base Target first';

            statusText.className =
                'text-sm font-black text-amber-700';

            statusBox.classList.add(
                'metric-amber'
            );

            return;
        }

        const difference =
            Math.abs(targetTotal - base);

        /*
        |--------------------------------------------------------------------------
        | MATCH
        |--------------------------------------------------------------------------
        */

        if (difference < 0.01) {

            statusText.textContent =
                'Quarter total matched with Base Target';

            statusText.className =
                'text-sm font-black text-emerald-700';

            statusBox.classList.add(
                'metric-emerald'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | EXCEED
        |--------------------------------------------------------------------------
        */

        else if (targetTotal > base) {

            statusText.textContent =
                'Quarter target exceeds Base Target';

            statusText.className =
                'text-sm font-black text-red-700';

            statusBox.classList.add(
                'metric-red'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | BELOW
        |--------------------------------------------------------------------------
        */

        else {

            statusText.textContent =
                'Quarter target below Base Target';

            statusText.className =
                'text-sm font-black text-amber-700';

            statusBox.classList.add(
                'metric-amber'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS BADGE
    |--------------------------------------------------------------------------
    */

    function updateStatusBadge() {

        const badge =
            document.getElementById('summaryStatus');

        const status = statusInput.value;

        const labelMap = {
            not_started: 'Not Started',
            on_track: 'On Track',
            at_risk: 'At Risk',
            in_trouble: 'In Trouble',
            completed: 'Completed'
        };

        badge.textContent =
            labelMap[status] || '-';

        badge.className =
            'inline-flex mt-1 px-3 py-1 rounded-full text-xs font-black';

        if (status === 'not_started') {
            badge.classList.add(
                'bg-slate-100',
                'text-slate-700'
            );
        }

        if (status === 'on_track') {
            badge.classList.add(
                'bg-blue-100',
                'text-blue-700'
            );
        }

        if (status === 'at_risk') {
            badge.classList.add(
                'bg-amber-100',
                'text-amber-700'
            );
        }

        if (status === 'in_trouble') {
            badge.classList.add(
                'bg-red-100',
                'text-red-700'
            );
        }

        if (status === 'completed') {
            badge.classList.add(
                'bg-emerald-100',
                'text-emerald-700'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    function updateSummary() {

        document.getElementById('summaryTitle')
            .textContent =
                kpiTitle.value || 'Not entered yet';

        document.getElementById('summaryCategory')
            .textContent =
                categoryInput.value || '-';

        document.getElementById('summarySubCategory')
            .textContent =
                subCategoryInput.value || '-';

        document.getElementById('summaryBase')
            .textContent =
                formatValue(baseTarget.value);

        document.getElementById('summaryStretch')
            .textContent =
                formatValue(stretchTarget.value);

        document.getElementById('summaryActual')
            .textContent =
                formatValue(actualValue.value);

        updateQuarterTotals();
        updateStatusBadge();
        updateStatusTheme();
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO DIVIDE
    |--------------------------------------------------------------------------
    */

    function autoDivideQuarter() {

        const base =
            Number(baseTarget.value || 0);

        if (base <= 0) {

            alert('Please fill Base Target first.');

            return;
        }

        const quarterValue = base / 4;

        quarterTargetInputs.forEach(input => {

            input.value =
                quarterValue.toFixed(2);

        });

        updateSummary();
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE FORM
    |--------------------------------------------------------------------------
    */

    form.addEventListener('submit', function (event) {

        const base =
            Number(baseTarget.value || 0);

        const stretch =
            Number(stretchTarget.value || 0);

        if (stretch < base) {

            event.preventDefault();

            alert(
                'Stretch Target must be greater than or equal to Base Target.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | QUARTER DATE VALIDATION
        |--------------------------------------------------------------------------
        */

        let invalidDate = false;

        ['Q1','Q2','Q3','Q4'].forEach(q => {

            const start =
                document.querySelector(
                    `[name="quarters[${q}][start_date]"]`
                ).value;

            const end =
                document.querySelector(
                    `[name="quarters[${q}][end_date]"]`
                ).value;

            if (
                start &&
                end &&
                end < start
            ) {

                invalidDate = true;

            }

        });

        if (invalidDate) {

            event.preventDefault();

            alert(
                'Quarter end date cannot be earlier than start date.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PREVENT DOUBLE SUBMIT
        |--------------------------------------------------------------------------
        */

        const submitButton =
            form.querySelector('button[type="submit"]');

        submitButton.disabled = true;

        submitButton.innerHTML =
            'Creating KPI...';

    });

    /*
    |--------------------------------------------------------------------------
    | EVENTS
    |--------------------------------------------------------------------------
    */

    categoryInput.addEventListener(
        'change',
        updateSubCategories
    );

    subCategoryInput.addEventListener(
        'change',
        updateSummary
    );

    unitInput.addEventListener(
        'change',
        updateSummary
    );

    kpiTitle.addEventListener(
        'input',
        updateSummary
    );

    baseTarget.addEventListener(
        'input',
        updateSummary
    );

    stretchTarget.addEventListener(
        'input',
        updateSummary
    );

    actualValue.addEventListener(
        'input',
        updateSummary
    );

    statusInput.addEventListener(
        'change',
        updateSummary
    );

    quarterTargetInputs.forEach(input => {
        input.addEventListener('input', updateSummary);
    });

    quarterActualInputs.forEach(input => {
        input.addEventListener('input', updateSummary);
    });

    /*
    |--------------------------------------------------------------------------
    | INITIALIZE
    |--------------------------------------------------------------------------
    */

    function updateStatusTheme() {

        const status =
            statusInput.value;

        statusInput.className =
            'w-full mt-2 rounded-2xl border px-4 py-3 font-bold transition-all duration-200';

        if (status === 'not_started') {

            statusInput.classList.add(
                'bg-slate-50',
                'border-slate-200',
                'text-slate-700'
            );

        }

        else if (status === 'on_track') {

            statusInput.classList.add(
                'metric-blue',
                'text-blue-700'
            );

        }

        else if (status === 'at_risk') {

            statusInput.classList.add(
                'metric-amber',
                'text-amber-700'
            );

        }

        else if (status === 'in_trouble') {

            statusInput.classList.add(
                'bg-red-50',
                'border-red-300',
                'text-red-700'
            );

        }

        else if (status === 'completed') {

            statusInput.classList.add(
                'metric-emerald',
                'text-emerald-700'
            );

        }

    }

    updateSubCategories();
    updateSummary();

document.addEventListener('DOMContentLoaded', () => {

    /*
    |--------------------------------------------------------------------------
    | CURRENT DATE
    |--------------------------------------------------------------------------
    */

    const today = new Date();

    const currentMonth = today.getMonth() + 1;

    const currentYear = today.getFullYear();

    /*
    |--------------------------------------------------------------------------
    | DETERMINE ACTIVE QUARTER
    |--------------------------------------------------------------------------
    */

    let activeQuarter = '';

    if(currentMonth >= 1 && currentMonth <= 3){

        activeQuarter = 'Q1';

    }else if(currentMonth >= 4 && currentMonth <= 6){

        activeQuarter = 'Q2';

    }else if(currentMonth >= 7 && currentMonth <= 9){

        activeQuarter = 'Q3';

    }else{

        activeQuarter = 'Q4';
    }

    /*
    |--------------------------------------------------------------------------
    | QUARTER DATE RANGE
    |--------------------------------------------------------------------------
    */

    const quarterRanges = {

        Q1: {
            min: `${currentYear}-01-01`,
            max: `${currentYear}-03-31`
        },

        Q2: {
            min: `${currentYear}-04-01`,
            max: `${currentYear}-06-30`
        },

        Q3: {
            min: `${currentYear}-07-01`,
            max: `${currentYear}-09-30`
        },

        Q4: {
            min: `${currentYear}-10-01`,
            max: `${currentYear}-12-31`
        }

    };

    /*
    |--------------------------------------------------------------------------
    | APPLY RESTRICTIONS
    |--------------------------------------------------------------------------
    */

    ['Q1','Q2','Q3','Q4'].forEach((quarter) => {

        /*
        |--------------------------------------------------------------------------
        | INPUTS
        |--------------------------------------------------------------------------
        */

        const startInput = document.querySelector(
            `input[name="quarters[${quarter}][start_date]"]`
        );

        const endInput = document.querySelector(
            `input[name="quarters[${quarter}][end_date]"]`
        );

        const targetInput = document.querySelector(
            `input[name="quarters[${quarter}][quarter_target]"]`
        );

        const actualInput = document.querySelector(
            `input[name="quarters[${quarter}][quarter_actual]"]`
        );

        const titleInput = document.querySelector(
            `input[name="quarters[${quarter}][quarter_title]"]`
        );

        const descInput = document.querySelector(
            `textarea[name="quarters[${quarter}][quarter_description]"]`
        );

        const remarkInput = document.querySelector(
            `textarea[name="quarters[${quarter}][remark]"]`
        );

        const statusInput = document.querySelector(
            `select[name="quarters[${quarter}][status]"]`
        );

        /*
        |--------------------------------------------------------------------------
        | ACTIVE QUARTER
        |--------------------------------------------------------------------------
        */

        if(quarter === activeQuarter){

            /*
            |--------------------------------------------------------------------------
            | ENABLE
            |--------------------------------------------------------------------------
            */

            [
                startInput,
                endInput,
                targetInput,
                actualInput,
                titleInput,
                descInput,
                remarkInput,
                statusInput
            ].forEach((el) => {

                if(el){

                    el.disabled = false;
                }
            });

            /*
            |--------------------------------------------------------------------------
            | DATE LIMIT
            |--------------------------------------------------------------------------
            */

            if(startInput){

                startInput.min = quarterRanges[quarter].min;
                startInput.max = quarterRanges[quarter].max;

                if(!startInput.value){

                    startInput.value = quarterRanges[quarter].min;
                }
            }

            if(endInput){

                endInput.min = quarterRanges[quarter].min;
                endInput.max = quarterRanges[quarter].max;

                if(!endInput.value){

                    endInput.value = quarterRanges[quarter].max;
                }
            }

        }else{

            /*
            |--------------------------------------------------------------------------
            | DISABLE OTHER QUARTERS
            |--------------------------------------------------------------------------
            */

            [
                startInput,
                endInput,
                targetInput,
                actualInput,
                titleInput,
                descInput,
                remarkInput,
                statusInput
            ].forEach((el) => {

                if(el){

                    el.disabled = true;

                    el.classList.add(
                        'opacity-50',
                        'cursor-not-allowed',
                        'bg-slate-100'
                    );
                }
            });
        }

    });

});

</script>

</body>
</html>
