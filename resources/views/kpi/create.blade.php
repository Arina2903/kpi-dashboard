<!DOCTYPE html>
<html>
<head>
    <title>Create KPI</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .category-card{
            position:relative;

            min-height:72px;

            display:flex;
            align-items:center;
            justify-content:center;

            border:1px solid #e5e7eb;
            border-radius:14px;

            background:#ffffff;

            font-size:14px;
            font-weight:700;

            color:#334155;

            transition:.15s ease;
        }

        .category-radio:checked + .category-card::after{
            content:'✓';
            position:absolute;
            top:8px;
            right:8px;
            width:14px;
            height:14px;
            border-radius:999px;
            background:#2563eb;
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:8px;
            font-weight:700;
        }

        .category-card:hover{
            border-color:#94a3b8;
        }

        .category-radio:checked + .category-card{
            background:#eff6ff;
            border-color:#2563eb;
            color:#1e40af;
        }

        .category-title{
            font-size:12px;
            font-weight:800;
        }

        .category-desc{
            font-size:10px;
            margin-top:2px;
            opacity:.8;
        }

        .sub-card{
            min-height:42px;
            padding:0 16px;
            display:flex;
            align-items:center;
            justify-content:center;
            border:1px solid #e5e7eb;
            border-radius:999px;
            background:white;
            font-size:13px;
            font-weight:600;
            color:#475569;
            transition:.15s ease;
            position:relative;
        }

        .sub-card:hover{
            transform:translateY(-1px);
            box-shadow: 0 8px 20px rgba(15,23,42,.06);
        }

        .sub-radio:checked + .sub-card{
            background:#f8fafc;
            border-color:#06142f;
            color:#06142f;
            box-shadow:
                inset 0 0 0 1px #2563eb;
        }

        .sub-radio:checked + .sub-card::after{
            content:'✓';
            position:absolute;
            top:8px;
            right:8px;
            width:14px;
            height:14px;
            border-radius:999px;
            background:#06b6d4;
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:8px;
        }

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
            background:#ffffff;
            border:1px solid #e5e7eb;
            border-radius:16px;
            box-shadow:0 1px 3px rgba(15,23,42,.05);
            overflow:hidden;
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
            width:32px;
            height:32px;
            border-radius:10px;

            display:flex;
            align-items:center;
            justify-content:center;

            background:#06142f;
            color:white;

            font-size:13px;
            font-weight:700;

            box-shadow:none;
        }

        .field{
            width:100%;
            min-height:48px;
            border:1px solid #d1d5db;
            background:white;
            color:#111827;
            transition:.15s ease;
            box-shadow:none;
        }

        textarea.field{
            min-height:110px;
            height:auto;
        }

        .field:hover{
            border-color:#93c5fd;
            background:white;
        }

        .field:focus{
            outline:none;
            border-color:#2563eb;
            box-shadow:
                0 0 0 3px rgba(37,99,235,.12);
            background:white;
        }

        .quarter-card{
            background:#ffffff;
            border:1px solid #e9d5ff;
            border-radius:24px;
            transition:.15s ease;
        }

        .quarter-card:hover{
            border-color:#a855f7;
            box-shadow:0 10px 25px rgba(124,58,237,.08);
        }

        .quarter-card:hover{
            border-color:#cbd5e1;
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

            color:#6d28d9;

            background:
                linear-gradient(
                    135deg,
                    #f5f3ff 0%,
                    #ffffff 100%
                );

            border:1px solid #ddd6fe;

            box-shadow:
                0 8px 20px rgba(124,58,237,.12);
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
            background:#f8fafc;
            border-color:#06142f;
            color:#06142f;
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
            background:#f8fafc;
            border-color:#06142f;
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

            background:#f8fafc;
            color:#06142f;

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

        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            <div class="bg-white/10 border border-white/15 rounded-xl px-3 py-2">
                <p class="text-blue-100 text-xs">Owner</p>
                <p class="font-black text-lg">{{ $user['short_name'] ?? '-' }}</p>
            </div>

            <div class="bg-white/10 border border-white/15 rounded-xl px-3 py-2">
                <p class="text-blue-100 text-xs">Role</p>
                <p class="font-black text-lg">{{ $user['role'] ?? '-' }}</p>
            </div>

            <div class="bg-white/10 border border-white/15 rounded-xl px-3 py-2">
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

            <div class="lg:col-span-8">

                    <!-- KPI OWNERSHIP & ASSIGNMENT -->

                    <section
                        id="section1"
                        class="section-card p-6 scroll-mt-24">

                    <div class="absolute top-0 right-0 w-40 h-40 bg-cyan-200/20 blur-3xl rounded-full"></div>

                    <div class="relative flex gap-4">

                        <div class="w-2 rounded-full bg-gradient-to-b from-blue-700 to-cyan-500"></div>

                        <div class="flex-1">

                            <!-- HEADER -->
                            <div class="flex items-center gap-4">

                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-cyan-500 text-white flex items-center justify-center font-black shadow-lg">
                                    1
                                </div>

                                <div>
                                    <h3 class="font-black text-slate-900 text-lg">
                                        KPI Ownership & Assignment
                                    </h3>

                                    <p class="text-sm text-slate-500">
                                        Tentukan siapa pemilik KPI dan siapa yang bertanggungjawab melaksanakan KPI ini.
                                    </p>
                                </div>

                            </div>

                            <!-- CONTENT -->
                            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 mt-6">

                                <!-- ASSIGNMENT -->
                                <div class="xl:col-span-4">

                                    <div class="rounded-[24px] border border-blue-100 bg-[#f8fbff] p-5 h-full">

                                        <div class="flex items-center gap-4">

                                            <div>

                                                <p class="text-[11px] uppercase tracking-wider text-blue-500 font-black">
                                                    EXECUTION OWNER
                                                </p>

                                                <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                    KPI Assignment
                                                </h3>

                                                <p class="text-sm text-slate-500">
                                                    Orang yang akan melaksanakan KPI ini setiap hari.
                                                </p>

                                            </div>

                                        </div>

                                        <div class="mt-6">

                                            <label class="text-sm font-bold text-slate-700">
                                                Assign To
                                            </label>

                                            <select
                                                name="assigned_employee_id"
                                                id="assignedEmployee"
                                                class="w-full mt-2 rounded-2xl border border-blue-200 bg-white px-4 py-4 font-semibold text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">

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

                                <div class="xl:col-span-8">
                                    <div class="rounded-[24px] border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-5 h-full flex flex-col">

                                        <!-- header -->
                                        <div class="flex items-start justify-between gap-3 mb-4">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-wider text-indigo-600 font-black">MY ASSIGNED KPI</p>
                                                <h3 class="font-black text-slate-900 text-xl mt-0.5">KPI Assigned To Me</h3>
                                            </div>
                                            @php $totalAssign = $pendingAssignments + $acceptedAssignments + $rejectedAssignments; @endphp
                                            <span class="px-3 py-1.5 rounded-xl bg-indigo-100 text-indigo-700 font-black text-xs shrink-0">
                                                {{ $totalAssign }} KPI
                                            </span>
                                        </div>

                                        <!-- inline card list -->
                                        <div id="assignmentInlineList" class="space-y-2 overflow-y-auto flex-1 min-h-0 max-h-56 pr-1">

                                            @if($totalAssign === 0)
                                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                                <div class="text-3xl mb-2">🎉</div>
                                                <p class="font-bold text-slate-500 text-sm">No KPI assigned to you yet.</p>
                                                <p class="text-xs text-slate-400 mt-1">Assignments will appear here.</p>
                                            </div>
                                            @else

                                            @foreach($assignmentGroups as $group)
                                                @foreach($group['kpis'] as $row)
                                                <div class="assignment-card border-l-4 border-l-indigo-300 bg-white hover:bg-indigo-50/50 rounded-2xl p-3 cursor-pointer border border-indigo-100 transition-all hover:shadow-sm select-none"
                                                    data-status="{{ $row['status'] ?? 'pending' }}"
                                                    data-kpi='@json($row)'>
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="font-black text-slate-900 text-sm leading-snug">{{ $row['kpi_title'] }}</p>
                                                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $row['category'] }} · {{ $row['sub_category'] }}</p>
                                                            <p class="text-[11px] text-slate-400 mt-1">From: <span class="font-bold text-slate-600">{{ $row['owner_name'] }}</span></p>
                                                        </div>
                                                        <span class="text-[9px] text-indigo-500 font-bold uppercase tracking-wider shrink-0 mt-0.5">View detail →</span>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @endforeach

                                            <div id="assignFilterEmpty" class="hidden py-8 text-center">
                                                <p class="text-2xl mb-1">🔍</p>
                                                <p class="text-sm font-bold text-slate-400">No KPIs in this category.</p>
                                            </div>

                                            @endif

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </section>

                <!-- 2. KPI CATEGORY -->
                <section
                    id="section2"
                    class="section-card p-6 scroll-mt-24">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-emerald-200/20 blur-3xl rounded-full"></div>

                    <div class="relative flex gap-4">

                        <div class="w-2 rounded-full bg-gradient-to-b from-emerald-600 to-teal-500"></div>

                        <div class="flex-1">

                            <!-- HEADER -->
                            <div class="flex items-center gap-4">

                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center font-black shadow-lg">
                                    2
                                </div>

                                <div>

                                    <h3 class="font-black text-slate-900 text-lg">
                                        KPI Category & Classification
                                    </h3>

                                    <p class="text-sm text-slate-500">
                                        Pilih kategori KPI dan klasifikasi yang paling tepat.
                                    </p>

                                </div>

                            </div>

                            <!-- CONTENT -->
                            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mt-6">

                                <!-- CATEGORY -->
                                <div class="xl:col-span-2">

                                    <div class="rounded-[24px] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 h-full">

                                        <div class="flex items-center gap-4">

                                            <div>

                                                <p class="text-[11px] uppercase tracking-wider text-emerald-600 font-black">
                                                    KPI CATEGORY
                                                </p>

                                                <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                    Main Category <span class="text-red-500 text-lg">*</span>
                                                </h3>

                                                <p class="text-sm text-slate-500">
                                                    Pilih kategori KPI utama.
                                                </p>

                                            </div>

                                        </div>

                                        <div class="grid grid-cols-2 gap-3 mt-6">

                                            <label class="cursor-pointer">
                                                <input
                                                    type="radio"
                                                    hidden
                                                    class="category-radio"
                                                    name="category"
                                                    value="Financial"
                                                    {{ old('category') == 'Financial' ? 'checked' : '' }}
                                                    required>

                                                <div class="category-card">
                                                    Financial
                                                </div>
                                            </label>

                                            <label class="cursor-pointer">
                                                <input
                                                    type="radio"
                                                    hidden
                                                    class="category-radio"
                                                    name="category"
                                                    value="Growth & Customer"
                                                    {{ old('category') == 'Growth & Customer' ? 'checked' : '' }}
                                                    required>

                                                <div class="category-card">
                                                    Growth
                                                </div>
                                            </label>

                                            <label class="cursor-pointer">
                                                <input
                                                    type="radio"
                                                    hidden
                                                    class="category-radio"
                                                    name="category"
                                                    value="Initiatives"
                                                    {{ old('category') == 'Initiatives' ? 'checked' : '' }}
                                                    required>

                                                <div class="category-card">
                                                    Initiatives
                                                </div>
                                            </label>

                                            <label class="cursor-pointer">
                                                <input
                                                    type="radio"
                                                    hidden
                                                    class="category-radio"
                                                    name="category"
                                                    value="People"
                                                    {{ old('category') == 'People' ? 'checked' : '' }}
                                                    required>

                                                <div class="category-card">
                                                    People
                                                </div>
                                            </label>

                                        </div>

                                    </div>

                                </div>

                                <!-- SUB CATEGORY -->
                                <div class="xl:col-span-3">

                                    <div class="rounded-[24px] border border-cyan-100 bg-[#f8fbff] p-5 h-full">

                                        <div class="flex items-center gap-4">

                                            <div>

                                                <p class="text-[11px] uppercase tracking-wider text-cyan-600 font-black">
                                                    KPI SUB CATEGORY
                                                </p>

                                                <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                    Classification <span class="text-red-500 text-lg">*</span>
                                                </h3>

                                                <p class="text-sm text-slate-500">
                                                    Pilih sub category selepas category dipilih.
                                                </p>

                                            </div>

                                        </div>

                                        <div
                                            id="subCategoryContainer"
                                            class="grid grid-cols-2 gap-3 mt-6">

                                            <div class="col-span-2 text-sm text-slate-400">
                                                Choose a category first
                                            </div>

                                        </div>

                                        {{-- Linkage warning banner --}}
                                        <div id="linkageWarning" class="hidden mt-3"></div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- 3. KPI DETAILS -->
                <section
                    id="section3"
                    class="section-card p-6 scroll-mt-24">

                <div class="absolute top-0 right-0 w-40 h-40 bg-indigo-200/20 blur-3xl rounded-full"></div>

                <div class="relative flex gap-4">

                    <div class="w-2 rounded-full bg-gradient-to-b from-[#06142f] to-indigo-500"></div>

                    <div class="flex-1">

                        <!-- HEADER -->
                        <div class="flex items-center gap-4">

                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-500 text-white flex items-center justify-center font-black shadow-lg">
                                3
                            </div>

                            <div>

                                <h3 class="font-black text-slate-900 text-lg">
                                    KPI Details
                                </h3>

                                <p class="text-sm text-slate-500">
                                    Tetapkan KPI yang jelas, spesifik dan mudah difahami.
                                </p>

                            </div>

                        </div>

                        <!-- CONTENT -->
                        <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mt-6">

                            <!-- KPI TITLE -->
                            <div class="xl:col-span-2">

                                <div class="rounded-[24px] border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-5 h-full">

                                    <div class="flex items-center gap-4">

                                        <div>

                                            <p class="text-[11px] uppercase tracking-wider text-indigo-600 font-black">
                                                KPI TITLE
                                            </p>

                                            <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                KPI Name
                                            </h3>

                                            <p class="text-sm text-slate-500">
                                                Nama KPI yang akan diukur.
                                            </p>

                                        </div>

                                    </div>

                                    <div class="mt-6">

                                        <label class="text-sm font-bold text-slate-700">
                                            KPI Title <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            name="kpi_title"
                                            id="kpiTitle"
                                            value="{{ old('kpi_title') }}"
                                            class="field w-full mt-2 rounded-2xl p-4"
                                            placeholder="Example: Increase Monthly Revenue by 20%"
                                            required>

                                    </div>

                                </div>

                            </div>

                            <!-- KPI DESCRIPTION -->
                            <div class="xl:col-span-3">

                                <div class="rounded-[24px] border border-blue-100 bg-[#f8fbff] p-5 h-full">

                                    <div class="flex items-center gap-4">

                                        <div>

                                            <p class="text-[11px] uppercase tracking-wider text-blue-500 font-black">
                                                KPI DESCRIPTION
                                            </p>

                                            <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                KPI Explanation
                                            </h3>

                                            <p class="text-sm text-slate-500">
                                                Terangkan objektif KPI ini dan bagaimana ia diukur.
                                            </p>

                                        </div>

                                    </div>

                                    <div class="mt-6">

                                        <label class="text-sm font-bold text-slate-700">
                                            Description <span class="text-red-500">*</span>
                                        </label>

                                        <textarea
                                            name="kpi_description"
                                            id="kpiDescription"
                                            rows="6"
                                            class="field w-full mt-2 rounded-2xl p-4"
                                            placeholder="Explain what success looks like, how this KPI will be measured and why it is important."
                                            required
                                        >{{ old('kpi_description') }}</textarea>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                </section>


                <!-- 4. FULL YEAR TARGET -->

                <section
                    id="section4"
                    class="section-card p-6 scroll-mt-24">
                <div class="absolute top-0 right-0 w-40 h-40 bg-sky-200/20 blur-3xl rounded-full"></div>

                <div class="relative flex gap-4">

                    <div class="w-2 rounded-full bg-gradient-to-b from-[#06142f] to-sky-500"></div>

                    <div class="flex-1">

                        <!-- HEADER -->
                        <div class="flex items-center gap-4">

                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-sky-600 to-blue-500 text-white flex items-center justify-center font-black shadow-lg">
                                4
                            </div>

                            <div>

                                <h3 class="font-black text-slate-900 text-lg">
                                    Full-Year Target
                                </h3>

                                <p class="text-sm text-slate-500">
                                    Tetapkan sasaran tahunan yang ingin dicapai.
                                </p>

                            </div>

                        </div>

                        <!-- CONTENT -->
                        <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mt-6">

                            <!-- UNIT -->
                            <div class="xl:col-span-2">

                                <div class="rounded-[24px] border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-5 h-full">

                                    <div class="flex items-center gap-4">

                                        <div>

                                            <p class="text-[11px] uppercase tracking-wider text-sky-600 font-black">
                                                KPI UNIT
                                            </p>

                                            <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                Measurement
                                            </h3>

                                            <p class="text-sm text-slate-500">
                                                Pilih unit pengukuran KPI.
                                            </p>

                                        </div>

                                    </div>

                                    <div class="mt-6">

                                        <label class="text-sm font-bold text-slate-700">
                                            Unit <span class="text-red-500">*</span>
                                        </label>

                                        <select
                                            name="unit"
                                            id="unit"
                                            class="field w-full mt-2 rounded-2xl p-4"
                                            required>

                                            <option value="number"
                                                {{ old('unit') === 'number' ? 'selected' : '' }}>
                                                Number
                                            </option>

                                            <option value="currency"
                                                {{ old('unit') === 'currency' ? 'selected' : '' }}>
                                                Currency / RM
                                            </option>

                                            <option value="percentage"
                                                {{ old('unit') === 'percentage' ? 'selected' : '' }}>
                                                Percentage / %
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>

                            <!-- TARGET -->
                            <div class="xl:col-span-3">

                                <div class="rounded-[24px] border border-blue-100 bg-[#f8fbff] p-5 h-full">

                                    <div class="flex items-center gap-4">

                                        <div>

                                            <p class="text-[11px] uppercase tracking-wider text-blue-500 font-black">
                                                TARGET SETTING
                                            </p>

                                            <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                Annual Target
                                            </h3>

                                            <p class="text-sm text-slate-500">
                                                Tetapkan Base dan Stretch Target KPI.
                                            </p>

                                        </div>

                                    </div>

                                    <div class="grid md:grid-cols-2 gap-4 mt-6">

                                        <div>

                                            <label class="text-sm font-bold text-slate-700">
                                                Base Target <span class="text-red-500">*</span>
                                            </label>

                                            <input
                                                name="base_target"
                                                id="baseTarget"
                                                type="number"
                                                step="0.01"
                                                value="{{ old('base_target') }}"
                                                class="field w-full mt-2 rounded-2xl p-4"
                                                required>

                                        </div>

                                        <div>

                                            <label class="text-sm font-bold text-slate-700">
                                                Stretch Target <span class="text-red-500">*</span>
                                            </label>

                                            <input
                                                name="stretch_target"
                                                id="stretchTarget"
                                                type="number"
                                                step="0.01"
                                                value="{{ old('stretch_target') }}"
                                                class="field w-full mt-2 rounded-2xl p-4"
                                                required>

                                        </div>

                                    </div>

                                    <div class="mt-4 rounded-xl bg-blue-50 border border-blue-100 p-3">

                                        <p class="text-xs text-blue-700">
                                            Base Target ialah sasaran minimum yang wajib dicapai. Stretch Target ialah sasaran lebih tinggi yang menunjukkan prestasi cemerlang.
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                </section>


                <!-- 5. CURRENT STATUS -->
                <section
                    id="section5"
                    class="section-card p-6 scroll-mt-24">

                <div class="absolute top-0 right-0 w-40 h-40 bg-amber-200/20 blur-3xl rounded-full"></div>

                <div class="relative flex gap-4">

                    <div class="w-2 rounded-full bg-gradient-to-b from-amber-500 to-red-500"></div>

                    <div class="flex-1">

                        <!-- HEADER -->
                        <div class="flex items-center gap-4">

                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-red-500 text-white flex items-center justify-center font-black shadow-lg">
                                5
                            </div>

                            <div>

                                <h3 class="font-black text-slate-900 text-lg">
                                    Current Status & Remark
                                </h3>

                                <p class="text-sm text-slate-500">
                                    Nyatakan status KPI semasa dan sebarang maklumat tambahan yang berkaitan.
                                </p>

                            </div>

                        </div>

                        <!-- CONTENT -->
                        <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mt-6">

                            <!-- STATUS -->
                            <div class="xl:col-span-2">

                                <div class="rounded-[24px] border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5 h-full">

                                    <div class="flex items-center gap-4">

                                        <div>

                                            <p class="text-[11px] uppercase tracking-wider text-amber-600 font-black">
                                                KPI STATUS
                                            </p>

                                            <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                Current Status
                                            </h3>

                                            <p class="text-sm text-slate-500">
                                                Pilih keadaan KPI pada masa ini.
                                            </p>

                                        </div>

                                    </div>

                                    <div class="mt-6">

                                        <label class="text-sm font-bold text-slate-700">
                                            Status <span class="text-red-500">*</span>
                                        </label>

                                        <select
                                            name="status"
                                            id="status"
                                            class="w-full mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-4 font-bold transition-all duration-200"
                                            required>

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

                                </div>

                            </div>

                            <!-- REMARK -->
                            <div class="xl:col-span-3">

                                <div class="rounded-[24px] border border-red-100 bg-[#fffaf8] p-5 h-full">

                                    <div class="flex items-center gap-4">

                                        <div>

                                            <p class="text-[11px] uppercase tracking-wider text-red-500 font-black">
                                                KPI REMARK
                                            </p>

                                            <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                Additional Context
                                            </h3>

                                            <p class="text-sm text-slate-500">
                                                Catatan tambahan berkaitan KPI ini.
                                            </p>

                                        </div>

                                    </div>

                                    <div class="mt-6">

                                        <label class="text-sm font-bold text-slate-700">
                                            Remark
                                        </label>

                                        <textarea
                                            name="remark"
                                            id="remark"
                                            rows="6"
                                            class="field w-full mt-2 rounded-2xl p-4"
                                            placeholder="Optional note, context, challenge, assumption or additional information."
                                        >{{ old('remark') }}</textarea>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                </section>


                <!-- 6. QUARTERS -->
                <section
                    id="section6"
                    class="section-card p-6 scroll-mt-24">

                    <div class="absolute top-0 right-0 w-40 h-40 bg-purple-200/20 blur-3xl rounded-full"></div>

                    <div class="relative flex gap-4">

                        <div class="w-2 rounded-full bg-gradient-to-b from-purple-500 to-indigo-600"></div>

                        <div class="flex-1">

                            <!-- HEADER -->
                            <div class="flex items-center justify-between gap-4">

                                <div class="flex items-center gap-4">

                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white flex items-center justify-center font-black shadow-lg">
                                        6
                                    </div>

                                    <div>

                                        <h3 class="font-black text-slate-900 text-lg">
                                            Quarter Breakdown
                                        </h3>

                                    </div>

                                </div>

                                <button
                                    type="button"
                                    onclick="autoDivideQuarter()"
                                    class="outline-btn px-5 py-3 rounded-2xl text-sm font-black">
                                    Auto Fill Annual
                                </button>

                            </div>

                            <div class="mt-6 space-y-5">

                            <div class="space-y-5">

                                @foreach(['Q1','Q2','Q3','Q4'] as $quarter)

                                    <div class="rounded-[24px] border border-purple-100 bg-gradient-to-br from-purple-50 to-white p-5">

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

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                                            <!-- TITLE -->
                                            <div>

                                                <label class="label">
                                                    Quarter Title <span class="text-red-500">*</span>
                                                </label>

                                                <input
                                                    name="quarters[{{ $quarter }}][quarter_title]"
                                                    value="{{ old("quarters.$quarter.quarter_title") }}"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                    placeholder="Example: Launch AI KPI dashboard V1"
                                                    required
                                                >

                                            </div>

                                            <!-- STATUS -->
                                            <div>

                                                <label class="label">
                                                    Quarter Status <span class="text-red-500">*</span>
                                                </label>

                                                <select
                                                    name="quarters[{{ $quarter }}][status]"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                    required
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
                                                    Quarter Description <span class="text-red-500">*</span>
                                                </label>

                                                <textarea
                                                    name="quarters[{{ $quarter }}][quarter_description]"
                                                    rows="3"
                                                    class="field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                    placeholder="Explain the focus and expected outcome for this quarter."
                                                    required
                                                >{{ old("quarters.$quarter.quarter_description") }}</textarea>

                                            </div>

                                            <!-- TARGET -->
                                            <div>

                                                <label class="label">
                                                    Quarter Target <span class="text-red-500">*</span>
                                                </label>

                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="quarters[{{ $quarter }}][quarter_target]"
                                                    value="{{ old("quarters.$quarter.quarter_target") }}"
                                                    class="quarter-target field mt-2 rounded-2xl px-4 py-3 text-sm"
                                                    required
                                                >

                                            </div>

                                            <!-- TIMELINE (AUTO LOCKED) -->
                                            @php

                                                $year =
                                                    str_replace(
                                                        'FY',
                                                        '',
                                                        $fy ?? ('FY'.now()->year)
                                                    );

                                                $quarterDates = [

                                                    'Q1'=>[
                                                        'start'=>$year.'-01-01',
                                                        'end'=>$year.'-03-31'
                                                    ],

                                                    'Q2'=>[
                                                        'start'=>$year.'-04-01',
                                                        'end'=>$year.'-06-30'
                                                    ],

                                                    'Q3'=>[
                                                        'start'=>$year.'-07-01',
                                                        'end'=>$year.'-09-30'
                                                    ],

                                                    'Q4'=>[
                                                        'start'=>$year.'-10-01',
                                                        'end'=>$year.'-12-31'
                                                    ]

                                                ];

                                                $timeline =
                                                    $quarterDates[$quarter];

                                                @endphp

                                                <div class="md:col-span-2">

                                                    <label class="label">
                                                        Timeline <span class="text-red-500">*</span>
                                                    </label>

                                                    <div class="grid grid-cols-2 gap-3 mt-2">

                                                        <div>

                                                            <input
                                                                type="date"
                                                                name="quarters[{{ $quarter }}][start_date]"
                                                                value="{{ old("quarters.$quarter.start_date",$timeline['start']) }}"
                                                                min="{{ $timeline['start'] }}"
                                                                max="{{ $timeline['end'] }}"
                                                                class="field rounded-2xl px-4 py-3 text-sm"
                                                                required
                                                            >

                                                        </div>

                                                        <div>

                                                            <input
                                                                type="date"
                                                                name="quarters[{{ $quarter }}][end_date]"
                                                                value="{{ old("quarters.$quarter.end_date",$timeline['end']) }}"
                                                                min="{{ $timeline['start'] }}"
                                                                max="{{ $timeline['end'] }}"
                                                                class="field rounded-2xl px-4 py-3 text-sm"
                                                                required
                                                            >

                                                        </div>

                                                    </div>

                                                    <p class="text-xs text-purple-600 mt-2">

                                                        Allowed Range:
                                                        {{ \Carbon\Carbon::parse($timeline['start'])->format('d/m/Y') }}
                                                        →
                                                        {{ \Carbon\Carbon::parse($timeline['end'])->format('d/m/Y') }}

                                                    </p>

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
                                        id="summaryQuarterTargetTotal"
                                        class="text-2xl font-black text-[#06142f] mt-2"
                                    >
                                        0.00
                                    </p>

                                    <p id="quarterTotalHint" class="text-xs font-bold mt-1"></p>

                                </div>


                            </div>

                        </div>

                    </div>

                </section>

            </div>

            <!-- KPI SUMMARY -->
            <aside class="lg:col-span-4">

                <div
                    class="sticky top-4
                        max-h-[calc(100vh-2rem)]
                        overflow-y-auto
                        rounded-[28px]
                        border border-slate-200
                        bg-white
                        p-6
                        shadow-[0_20px_50px_rgba(15,23,42,0.08)]">

                    <!-- HEADER -->
                    <div class="flex items-center gap-4">

                        <div
                            class="w-12 h-12
                                rounded-2xl
                                bg-[#06142f]
                                text-white
                                flex
                                items-center
                                justify-center
                                font-black
                                shadow-lg">
                            ✓
                        </div>

                        <div>
                            <h2 class="font-black text-lg text-slate-900">
                                KPI Summary
                            </h2>

                            <p class="text-xs text-slate-500">
                                Semak sebelum submit.
                            </p>
                        </div>

                    </div>

                    <!-- DIVIDER -->
                    <div class="h-px bg-slate-200 my-5"></div>

                    <!-- OWNER -->
                    <div
                        class="rounded-2xl
                            bg-slate-50
                            border
                            border-slate-200
                            p-4">

                        <p class="text-xs text-slate-400">
                            Owner
                        </p>

                        <p class="font-black text-[#06142f] mt-1">
                            {{ $user['short_name'] ?? '-' }}
                        </p>

                    </div>

                    <!-- TITLE -->
                    <div class="mt-4">

                        <p class="text-xs text-slate-400">
                            KPI Title
                        </p>

                        <p
                            id="summaryTitle"
                            class="font-black text-slate-900 mt-1">
                            Not entered yet
                        </p>

                    </div>

                    <!-- CATEGORY -->
                    <div class="grid grid-cols-2 gap-3 mt-4">

                        <div
                            class="rounded-2xl
                                bg-blue-50
                                border
                                border-blue-100
                                p-3">

                            <p class="text-xs text-blue-500">
                                Category
                            </p>

                            <p
                                id="summaryCategory"
                                class="font-bold text-[#06142f] mt-1">
                                -
                            </p>

                        </div>

                        <div
                            class="rounded-2xl
                                bg-blue-50
                                border
                                border-blue-100
                                p-3">

                            <p class="text-xs text-blue-500">
                                Sub Category
                            </p>

                            <p
                                id="summarySubCategory"
                                class="font-bold text-[#06142f] mt-1">
                                -
                            </p>

                        </div>

                    </div>

                    <!-- TARGET -->
                    <div class="grid grid-cols-2 gap-3 mt-4">

                        <div
                            class="rounded-2xl
                                border
                                border-blue-100
                                bg-white
                                p-4">

                            <p class="text-xs text-slate-400">
                                Base Target
                            </p>

                            <p
                                id="summaryBase"
                                class="font-black text-lg text-[#06142f] mt-1">
                                0.00
                            </p>

                        </div>

                        <div
                            class="rounded-2xl
                                border
                                border-purple-100
                                bg-white
                                p-4">

                            <p class="text-xs text-slate-400">
                                Stretch Target
                            </p>

                            <p
                                id="summaryStretch"
                                class="font-black text-lg text-[#06142f] mt-1">
                                0.00
                            </p>

                        </div>

                    </div>

                    <!-- STATUS -->
                    <div class="rounded-[20px] border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-4 mt-4">

                        <p class="text-[11px] uppercase tracking-wider text-amber-600 font-black">
                            CURRENT STATUS
                        </p>

                        <div class="mt-2">

                            <p
                                id="summaryStatus"
                                class="font-black">

                                <span class="text-slate-500">
                                    Not Started
                                </span>

                            </p>

                        </div>

                    </div>
                    <!-- QUARTER TOTAL -->
                    <div
                        class="mt-4
                            rounded-2xl
                            bg-blue-50
                            border
                            border-blue-100
                            p-4">

                        <p class="text-xs text-blue-500">
                            Quarter Target Total
                        </p>

                        <p
                            id="sidebarQuarterTargetTotal"
                            class="text-xl font-black text-[#06142f] mt-1">
                            0.00
                        </p>

                    </div>


                    <!-- SUBMIT -->
                    <div class="mt-6">

                        <button
                            type="submit"
                            class="w-full
                                bg-[#06142f]
                                hover:bg-[#0b1f46]
                                text-white
                                font-black
                                py-4
                                rounded-2xl
                                transition
                                shadow-lg">

                            Create My KPI

                        </button>

                    </div>

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

    const categoryInputs = document.querySelectorAll('input[name="category"]');
    const subCategoryContainer = document.getElementById('subCategoryContainer');

    const unitInput = document.getElementById('unit');

    const kpiTitle = document.getElementById('kpiTitle');

    const baseTarget = document.getElementById('baseTarget');
    const stretchTarget = document.getElementById('stretchTarget');

    const statusInput = document.getElementById('status');

    const quarterTargetInputs = document.querySelectorAll('.quarter-target');

    /*
    |--------------------------------------------------------------------------
    | SUB CATEGORY
    |--------------------------------------------------------------------------
    */

    function updateSubCategories(){
        const selectedCategory = document.querySelector('input[name="category"]:checked')?.value || '';
        const oldSubCategory = @json(old('sub_category'));

        subCategoryContainer.innerHTML='';

        if(
            !selectedCategory || !subCategories[selectedCategory]
        ){
            subCategoryContainer.innerHTML= `<div class="text-xs text-slate-400">Choose a category</div>`;
            updateSummary();
            return;
        }

        subCategories[selectedCategory]
        .forEach(subCategory=>{

            subCategoryContainer.innerHTML +=
            `<label class="cursor-pointer">
                <input type="radio"hidden class="sub-radio"name="sub_category"
                    value="${subCategory}"
                    required
                    ${
                        oldSubCategory === subCategory
                        ? 'checked'
                        : ''
                    }
                >

                <div class="sub-card">
                    <div class="font-semibold text-sm text-slate-800">
                        ${subCategory}
                    </div>
                </div>
            </label>
            `;
        });

        bindSubCategoryEvents();
        updateSummary();
    }

    function bindSubCategoryEvents(){
        document.querySelectorAll('input[name="sub_category"]')
        .forEach(input=>{
            input.addEventListener('change', () => { updateSummary(); updateLinkageWarning(); });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | LINKAGE WARNING
    |--------------------------------------------------------------------------
    */

    const linkageMap = @json($linkageMap ?? []);

    function fmtLinkVal(v, u) {
        const n = Number(v) || 0;
        if (u === 'currency')   return 'RM ' + n.toLocaleString('en-MY', {maximumFractionDigits:0});
        if (u === 'percentage') return n.toFixed(1) + '%';
        return n.toLocaleString('en-MY', {maximumFractionDigits:0});
    }

    function updateLinkageWarning() {
        const box = document.getElementById('linkageWarning');
        if (!box) return;
        const sub = document.querySelector('input[name="sub_category"]:checked')?.value;
        if (!sub || !linkageMap[sub]) { box.classList.add('hidden'); box.innerHTML = ''; return; }
        const lnk = linkageMap[sub];
        const met = lnk.met;
        box.className = `mt-3 p-3 rounded-xl border text-left ${met ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`;
        box.innerHTML = `
            <div class="flex items-start gap-2">
                <span class="text-base leading-none mt-0.5">${met ? '✅' : '⚠️'}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-black ${met ? 'text-emerald-800' : 'text-amber-800'}">
                        Target Linkage: ${sub} — ${fmtLinkVal(lnk.target, lnk.unit)} required
                        ${met ? '<span class="ml-2 text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded border border-emerald-200">Met ✓</span>' : '<span class="ml-2 text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded border border-amber-200">Gap</span>'}
                    </p>
                    <p class="text-[11px] ${met ? 'text-emerald-700' : 'text-amber-700'} mt-1">
                        From: <strong>${lnk.assigner_name ?? '-'}</strong> &nbsp;·&nbsp;
                        Currently covered by your KPIs: <strong>${fmtLinkVal(lnk.covered, lnk.unit)}</strong>
                        ${!met ? ` &nbsp;·&nbsp; Gap: <strong>${fmtLinkVal(lnk.gap, lnk.unit)}</strong>` : ''}
                    </p>
                    ${!met ? '<p class="text-[10px] text-amber-500 mt-1 italic">You can still create this KPI — it will count toward covering your linked target.</p>' : ''}
                </div>
            </div>
        `;
        box.classList.remove('hidden');
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

        quarterTargetInputs.forEach(input => {
            targetTotal += Number(input.value || 0);
        });

        const base = Number(baseTarget.value || 0);
        const matched = base > 0 && Math.abs(targetTotal - base) < 0.01;
        const totalText = formatValue(targetTotal);

        const summaryEl = document.getElementById('summaryQuarterTargetTotal');
        const sidebarEl = document.getElementById('sidebarQuarterTargetTotal');

        if(summaryEl){
            summaryEl.textContent = totalText;
            summaryEl.className = matched
                ? 'text-2xl font-black mt-2 text-emerald-600'
                : (targetTotal > 0 ? 'text-2xl font-black mt-2 text-red-500' : 'text-2xl font-black mt-2 text-[#06142f]');
        }

        if(sidebarEl){
            sidebarEl.textContent = totalText;
            sidebarEl.className = matched
                ? 'font-black text-emerald-600'
                : (targetTotal > 0 ? 'font-black text-red-500' : 'font-black');
        }

        const hintEl = document.getElementById('quarterTotalHint');
        if(hintEl){
            if(base <= 0){
                hintEl.textContent = '';
            } else if(matched){
                hintEl.textContent = '✓ Matches Base Target';
                hintEl.className = 'text-xs font-bold text-emerald-600 mt-1';
            } else {
                hintEl.textContent = `Must equal Base Target (${formatValue(base)})`;
                hintEl.className = 'text-xs font-bold text-red-500 mt-1';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS BADGE
    |--------------------------------------------------------------------------
    */

    function updateStatusBadge()
    {
        const indicator =
            document.getElementById(
                'summaryStatus'
            );

        if(!indicator){
            return;
        }

        switch(statusInput.value)
        {
            case 'on_track':

                indicator.innerHTML =
                    '<span class="text-blue-600">On Track</span>';

                break;

            case 'at_risk':

                indicator.innerHTML =
                    '<span class="text-orange-500">At Risk</span>';

                break;

            case 'in_trouble':

                indicator.innerHTML =
                    '<span class="text-red-600">In Trouble</span>';

                break;

            case 'completed':

                indicator.innerHTML =
                    '<span class="text-emerald-600">Completed</span>';

                break;

            default:

                indicator.innerHTML =
                    '<span class="text-slate-500">Not Started</span>';
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

        const selectedCategory =
            document.querySelector(
                'input[name="category"]:checked'
            )?.value || '-';

            document.getElementById(
                'summaryCategory'
            ).textContent =
            selectedCategory;

        document.getElementById('summarySubCategory').textContent =
        document.querySelector('input[name="sub_category"]:checked')?.value || '-';

        document.getElementById('summaryBase')
            .textContent =
                formatValue(baseTarget.value);

        document.getElementById('summaryStretch')
            .textContent =
                formatValue(stretchTarget.value);

        updateQuarterTotals();
        updateStatusBadge();
        updateStatusTheme();
        updateCompletion();
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

        const perQuarter = (base / 4).toFixed(2);

        quarterTargetInputs.forEach(input => {

            input.value = perQuarter;

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

        const quarterTotal = Array.from(quarterTargetInputs)
            .reduce((sum, i) => sum + Number(i.value || 0), 0);

        if (base > 0 && Math.abs(quarterTotal - base) > 0.01) {

            event.preventDefault();

            alert(
                `Quarter Target total (${quarterTotal.toFixed(2)}) must equal Base Target (${base.toFixed(2)}).\n\nPlease adjust your quarter targets or use Auto Fill Annual.`
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PREVENT DOUBLE SUBMIT
        |--------------------------------------------------------------------------
        */

        const submitButton =
            form.querySelector(
                'button[type="submit"]'
            );

        if(submitButton){

            submitButton.disabled = true;

            submitButton.innerHTML =
                'Creating KPI...';

        }

        submitButton.innerHTML =
            'Creating KPI...';

    });

    /*
    |--------------------------------------------------------------------------
    | EVENTS
    |--------------------------------------------------------------------------
    */

    categoryInputs.forEach(input => {

        input.addEventListener(
            'change',
            updateSubCategories
        );

    });

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

    statusInput.addEventListener(
        'change',
        updateSummary
    );

    quarterTargetInputs.forEach(input => {
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

    const quarterRanges = {

        Q1:{
            start:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-01-01',
            end:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-03-31'
        },

        Q2:{
            start:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-04-01',
            end:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-06-30'
        },

        Q3:{
            start:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-07-01',
            end:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-09-30'
        },

        Q4:{
            start:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-10-01',
            end:'{{ str_replace("FY","",$fy ?? "FY".now()->year) }}-12-31'
        }

    };

    function validateQuarterRanges(){

        ['Q1','Q2','Q3','Q4'].forEach(q=>{

            const startField =
                document.querySelector(
                    `[name="quarters[${q}][start_date]"]`
                );

            const endField =
                document.querySelector(
                    `[name="quarters[${q}][end_date]"]`
                );

            if(!startField || !endField){
                return;
            }

            const start = startField.value;
            const end = endField.value;

            if(
                start &&
                (
                    start < quarterRanges[q].start
                    ||
                    start > quarterRanges[q].end
                )
            ){
                startField.value =
                    quarterRanges[q].start;
            }

            if(
                end &&
                (
                    end < quarterRanges[q].start
                    ||
                    end > quarterRanges[q].end
                )
            ){
                endField.value =
                    quarterRanges[q].end;
            }

        });

    }

    updateSubCategories();
    updateLinkageWarning();
    updateSummary();
    updateStatusBadge();
    validateQuarterRanges();

function updateCompletion(){

    let total = 6;
    let completed = 0;

    if(
        document.querySelector(
            'input[name="category"]:checked'
        )
    ){
        completed++;
    }

    if(
        document.querySelector(
            'input[name="sub_category"]:checked'
        )
    ){
        completed++;
    }

    if(kpiTitle.value){
        completed++;
    }

    if(baseTarget.value){
        completed++;
    }

    if(stretchTarget.value){
        completed++;
    }

    if(statusInput.value){
        completed++;
    }

    const percent =
        Math.round(
            (completed / total) * 100
        );

    const completionPercent =
        document.getElementById(
            'completionPercent'
        );

    const completionBar =
        document.getElementById(
            'completionBar'
        );

    if(completionPercent){

        completionPercent.textContent =
            percent + '%';

    }

    if(completionBar){

        completionBar.style.width =
            percent + '%';

    }
}

const sections =
document.querySelectorAll(
    '[id^="section"]'
);

const navItems =
document.querySelectorAll(
    '.step-nav'
);

window.addEventListener(
    'scroll',
    () => {

        let current = '';

        sections.forEach(section => {

            const top =
                section.offsetTop - 150;

            if(
                window.scrollY >= top
            ){
                current =
                    section.id;
            }

        });

        navItems.forEach(item => {

            item.classList.remove(
                'text-blue-600',
                'font-black'
            );

            const href =
                item.getAttribute('href');

            if(
                href === '#' + current
            ){
                item.classList.add(
                    'text-blue-600',
                    'font-black'
                );
            }

        });

    }
);

document.addEventListener(
    'click',
    function(e){

        if(
            e.target.id === 'closeDetailModal' ||
            e.target.closest?.('#closeDetailModal')
        ){
            const dModal =
                document.getElementById(
                    'assignmentDetailModal'
                );

            if(dModal){
                dModal.classList.add('hidden');
                dModal.classList.remove('flex');
            }
        }

    }
);

function bindAssignmentCards(){

    document
    .querySelectorAll('.assignment-card')
    .forEach(card=>{

        card.addEventListener(
            'click',
            function(event){

                event.stopPropagation();

                try{

                    const raw =
                        this.dataset.kpi;

                    if(!raw){
                        console.error(
                            'data-kpi missing'
                        );
                        return;
                    }

                    const data =
                        JSON.parse(raw);

                    const detailModal =
                        document.getElementById(
                            'assignmentDetailModal'
                        );

                    const detailContent =
                        document.getElementById(
                            'detailContent'
                        );

                    if(
                        !detailModal ||
                        !detailContent
                    ){
                        console.error(
                            'Detail modal missing'
                        );
                        return;
                    }

                    const statusMap = {
                        'on_track'   : ['On Track',    'status-on-track'],
                        'at_risk'    : ['At Risk',     'status-risk'],
                        'in_trouble' : ['In Trouble',  'status-trouble'],
                        'completed'  : ['Completed',   'status-completed'],
                        'not_started': ['Not Started', 'status-not-started'],
                    };

                    const fmtDate = (d) => {
                        if(!d) return '-';
                        const dt = new Date(d);
                        return isNaN(dt) ? d : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'});
                    };

                    let quarterHtml = '';

                    if(Array.isArray(data.quarters)){
                        data.quarters.forEach(q => {
                            const [statusLabel, statusCls] = statusMap[q.status] ?? ['Not Started', 'status-not-started'];
                            quarterHtml += `
                            <div class="rounded-[20px] border border-purple-100 bg-gradient-to-br from-purple-50 to-white p-4">
                                <div class="flex items-center gap-3">
                                    <div class="quarter-dot flex-shrink-0">${q.quarter ?? '-'}</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 mb-3">
                                            <p class="font-black text-slate-900 text-sm truncate">${q.quarter_title ?? '-'}</p>
                                            <span class="status-pill ${statusCls} flex-shrink-0">${statusLabel}</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="rounded-xl bg-white border border-purple-100 p-2">
                                                <p class="text-[9px] text-purple-400 uppercase font-black tracking-wider">Target</p>
                                                <p class="text-sm font-black text-[#06142f] mt-0.5">${Number(q.quarter_target ?? 0).toLocaleString()}</p>
                                            </div>
                                            <div class="rounded-xl bg-white border border-purple-100 p-2">
                                                <p class="text-[9px] text-purple-400 uppercase font-black tracking-wider">Start</p>
                                                <p class="text-xs font-bold text-slate-700 mt-0.5">${fmtDate(q.start_date)}</p>
                                            </div>
                                            <div class="rounded-xl bg-white border border-purple-100 p-2">
                                                <p class="text-[9px] text-purple-400 uppercase font-black tracking-wider">End</p>
                                                <p class="text-xs font-bold text-slate-700 mt-0.5">${fmtDate(q.end_date)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            `;
                        });
                    }

                    detailContent.innerHTML = `
                    <div class="space-y-6">

                        <!-- KPI INFORMATION -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-1 h-5 rounded-full bg-gradient-to-b from-indigo-600 to-cyan-500"></div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">KPI Information</p>
                            </div>
                            <div class="space-y-2">
                                <div class="rounded-[20px] bg-[#06142f] p-4">
                                    <p class="text-[9px] uppercase tracking-widest text-blue-300 font-black">KPI Name</p>
                                    <p class="font-black text-white text-base mt-1 leading-snug">${data.kpi_title ?? '-'}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-2xl bg-blue-50 border border-blue-100 p-3">
                                        <p class="text-[9px] uppercase tracking-widest text-blue-500 font-black">Category</p>
                                        <p class="font-bold text-slate-900 mt-1 text-sm">${data.category ?? '-'}</p>
                                    </div>
                                    <div class="rounded-2xl bg-blue-50 border border-blue-100 p-3">
                                        <p class="text-[9px] uppercase tracking-widest text-blue-500 font-black">Sub Category</p>
                                        <p class="font-bold text-slate-900 mt-1 text-sm">${data.sub_category ?? '-'}</p>
                                    </div>
                                </div>
                                ${data.kpi_description && data.kpi_description !== '-' ? `
                                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                    <p class="text-[9px] uppercase tracking-widest text-slate-500 font-black">Description</p>
                                    <p class="text-sm text-slate-600 mt-1 leading-relaxed">${data.kpi_description}</p>
                                </div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- ASSIGNMENT -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-1 h-5 rounded-full bg-gradient-to-b from-emerald-500 to-teal-400"></div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Assignment</p>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                                    <p class="text-[9px] uppercase tracking-widest text-emerald-600 font-black">Assigned By</p>
                                    <p class="font-bold text-slate-900 mt-1 text-sm">${data.owner_name ?? '-'}</p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                                    <p class="text-[9px] uppercase tracking-widest text-emerald-600 font-black">Role</p>
                                    <p class="font-bold text-slate-900 mt-1 text-sm">${data.owner_role ?? '-'}</p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                                    <p class="text-[9px] uppercase tracking-widest text-emerald-600 font-black">Assigned Date</p>
                                    <p class="font-bold text-slate-900 mt-1 text-sm">${fmtDate(data.created_at)}</p>
                                </div>
                            </div>
                        </div>

                        <!-- TARGET -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-1 h-5 rounded-full bg-gradient-to-b from-sky-500 to-blue-500"></div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Target</p>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
                                    <p class="text-[9px] uppercase tracking-widest text-blue-500 font-black">Base Target</p>
                                    <p class="text-xl font-black text-[#06142f] mt-1">${Number(data.base_target ?? 0).toLocaleString()}</p>
                                    <p class="text-[9px] text-blue-300 mt-1 uppercase font-bold">Annual</p>
                                </div>
                                <div class="rounded-2xl bg-purple-50 border border-purple-100 p-4">
                                    <p class="text-[9px] uppercase tracking-widest text-purple-500 font-black">Stretch Target</p>
                                    <p class="text-xl font-black text-[#06142f] mt-1">${Number(data.stretch_target ?? 0).toLocaleString()}</p>
                                    <p class="text-[9px] text-purple-300 mt-1 uppercase font-bold">Annual</p>
                                </div>
                                <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
                                    <p class="text-[9px] uppercase tracking-widest text-amber-600 font-black">Weightage</p>
                                    <p class="text-xl font-black text-[#06142f] mt-1">${data.weightage ?? 0}%</p>
                                    <p class="text-[9px] text-amber-300 mt-1 uppercase font-bold">Weight</p>
                                </div>
                            </div>
                        </div>

                        <!-- QUARTER BREAKDOWN -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-1 h-5 rounded-full bg-gradient-to-b from-purple-500 to-indigo-500"></div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Quarter Breakdown</p>
                            </div>
                            <div class="space-y-2">
                                ${quarterHtml}
                            </div>
                        </div>

                    </div>
                    `;

                    detailModal.classList.remove(
                        'hidden'
                    );

                    detailModal.classList.add(
                        'flex'
                    );

                }catch(error){

                    console.error(
                        'Assignment Detail Error:',
                        error
                    );

                }

            }
        );

    });

}

function syncMainStatusFromQuarters(){
    const quarterStatuses = document.querySelectorAll('select[name*="[status]"]');
    const anyActive = Array.from(quarterStatuses).some(s => s.value !== 'not_started');
    const mainStatus = document.getElementById('status');
    if(!mainStatus) return;
    if(anyActive && mainStatus.value === 'not_started'){
        mainStatus.value = 'on_track';
        mainStatus.dispatchEvent(new Event('change'));
    } else if(!anyActive){
        mainStatus.value = 'not_started';
        mainStatus.dispatchEvent(new Event('change'));
    }
}

document.addEventListener('DOMContentLoaded', function(){

    bindAssignmentCards();

    document.querySelectorAll('select[name*="[status]"]').forEach(function(sel){
        sel.addEventListener('change', syncMainStatusFromQuarters);
    });

    /* --- filter tabs for inline assignment list --- */
});

const closeDetailBtn =
    document.getElementById(
        'closeDetailModal'
    );

if(closeDetailBtn){

    closeDetailBtn.addEventListener(
        'click',
        function(){

            const modal =
                document.getElementById(
                    'assignmentDetailModal'
                );

            if(!modal){
                return;
            }

            modal.classList.add(
                'hidden'
            );

            modal.classList.remove(
                'flex'
            );

        }
    );

}

const detailModal =
    document.getElementById(
        'assignmentDetailModal'
    );

if(detailModal){

    detailModal.addEventListener(
        'click',
        function(e){

            if(
                e.target === this
            ){

                this.classList.add(
                    'hidden'
                );

                this.classList.remove(
                    'flex'
                );

            }

        }
    );

}

document.addEventListener(
    'keydown',
    function(e){

        if(
            e.key === 'Escape'
        ){

            const modal =
                document.getElementById(
                    'assignmentDetailModal'
                );

            if(
                modal &&
                !modal.classList.contains(
                    'hidden'
                )
            ){

                modal.classList.add(
                    'hidden'
                );

                modal.classList.remove(
                    'flex'
                );

            }

        }

    }
);

function toggleOwner(ownerId){

    const section =
        document.getElementById(
            'owner-' + ownerId
        );

    if(!section){
        return;
    }

    section.classList.toggle('hidden');

}

 // DOMContentLoaded

</script>

<div
    id="assignmentDetailModal"
    class="fixed inset-0 hidden bg-black/60 z-[99999] items-center justify-center p-4">

    <div class="bg-white w-full max-w-xl rounded-[2rem] overflow-hidden shadow-2xl flex flex-col max-h-[88vh]">

        <!-- modal header -->
        <div class="hero-gradient px-6 py-5 flex items-center justify-between flex-shrink-0">
            <div>
                <p class="text-blue-200 text-[10px] font-black uppercase tracking-widest">KPI Assignment</p>
                <h2 class="font-black text-white text-lg mt-0.5">KPI Detail</h2>
            </div>
            <button
                type="button"
                id="closeDetailModal"
                class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white font-black text-sm flex items-center justify-center transition-all">
                ✕
            </button>
        </div>

        <!-- scrollable body -->
        <div id="detailContent" class="overflow-y-auto p-6 flex-1"></div>

        <!-- view-only footer -->
        <div class="shrink-0 border-t border-slate-100 px-6 py-4 flex items-center justify-center bg-slate-50">
            <p class="text-xs text-slate-400 font-bold flex items-center gap-1.5">
                <span>👁</span> View Only — This KPI has been assigned to you for reference.
            </p>
        </div>

    </div>

</div>



</body>
</html>
