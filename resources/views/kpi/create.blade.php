<!DOCTYPE html>
<html>
<head>
    <title>Create KPI</title>
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
                            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mt-6">

                                <!-- OWNER -->
                                <div class="xl:col-span-2">

                                    <div class="rounded-[24px] border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-5 h-full">

                                        <div class="flex items-center gap-4">

                                            <div>

                                                <p class="text-[11px] uppercase tracking-wider text-slate-400 font-black">
                                                    KPI OWNER
                                                </p>

                                                <h3 class="font-black text-slate-900 text-2xl mt-1">
                                                    {{ $user['short_name'] }}
                                                </h3>

                                                <p class="text-sm text-slate-500">
                                                    {{ $user['role'] }}
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    {{ $user['department_code'] }}
                                                </p>

                                            </div>

                                        </div>

                                        <div class="mt-5 grid grid-cols-2 gap-3">

                                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">

                                                <p class="text-[10px] uppercase text-slate-400 font-bold">
                                                    Role
                                                </p>

                                                <p class="font-bold text-slate-800 mt-1">
                                                    {{ $user['role'] }}
                                                </p>

                                            </div>

                                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">

                                                <p class="text-[10px] uppercase text-slate-400 font-bold">
                                                    Department
                                                </p>

                                                <p class="font-bold text-slate-800 mt-1">
                                                    {{ $user['department_code'] }}
                                                </p>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <!-- ASSIGNMENT -->
                                <div class="xl:col-span-3">

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
                                                    Main Category
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
                                                    Classification
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
                                            KPI Title
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
                                            Description
                                        </label>

                                        <textarea
                                            name="kpi_description"
                                            id="kpiDescription"
                                            rows="6"
                                            class="field w-full mt-2 rounded-2xl p-4"
                                            placeholder="Explain what success looks like, how this KPI will be measured and why it is important."
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
                                            Unit
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
                                                Base Target
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
                                                Stretch Target
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
                                            Status
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
                                    Auto Divide Base
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
                                                        Timeline
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

                                </div>

                                <div
                                    id="summaryQuarterStatusBox"
                                    class="metric-card metric-blue p-5"
                                >

                                    <p class="text-xs font-bold text-blue-500 uppercase tracking-wide">
                                        Quarter vs Base
                                    </p>

                                    <p
                                        id="summaryQuarterMatchStatus"
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
                                bg-cyan-50
                                border
                                border-cyan-100
                                p-3">

                            <p class="text-xs text-cyan-600">
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
                            id="summaryQuarterTargetTotal"
                            class="text-xl font-black text-[#06142f] mt-1">
                            0.00
                        </p>

                    </div>

                    <!-- QUARTER STATUS -->
                    <div
                        id="summaryQuarterStatusBox"
                        class="mt-4
                            rounded-2xl
                            bg-slate-50
                            border
                            border-slate-200
                            p-4">

                        <p class="text-xs text-slate-500">
                            Quarter vs Base
                        </p>

                        <p
                            id="summaryQuarterMatchStatus"
                            class="font-black text-slate-900 mt-1">
                            Optional
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
        document .querySelectorAll('input[name="sub_category"]')
        .forEach(input=>{
            input.addEventListener(
                'change',
                updateSummary
            );
        });
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

        document.getElementById(
            'summaryQuarterTargetTotal'
        ).textContent =
            formatValue(targetTotal);

        const base = Number(baseTarget.value || 0);

        const statusText =
            document.getElementById(
                'summaryQuarterMatchStatus'
            );

        const statusBox =
            document.getElementById(
                'summaryQuarterStatusBox'
            );

        const summaryStatusText =
            document.getElementById(
                'summaryQuarterMatchStatus'
            );

        const summaryStatusBox =
            document.getElementById(
                'summaryQuarterStatusBox'
            );

        summaryStatusBox.className =
            'mt-4 rounded-2xl border p-4';

        /*
        |--------------------------------------------------------------------------
        | STATUS LOGIC
        |--------------------------------------------------------------------------
        */

        if (targetTotal <= 0) {

            statusText.textContent = 'Optional';

            summaryStatusText.textContent =
                statusText.textContent;

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

    ['Q1','Q2','Q3','Q4'].forEach(q=>{

        const start =
            document.querySelector(
                `[name="quarters[${q}][start_date]"]`
            ).value;

        const end =
            document.querySelector(
                `[name="quarters[${q}][end_date]"]`
            ).value;

        if(
            start < quarterRanges[q].start
            ||
            start > quarterRanges[q].end
        ){
            throw new Error(
                `${q} start date outside quarter`
            );
        }

        if(
            end < quarterRanges[q].start
            ||
            end > quarterRanges[q].end
        ){
            throw new Error(
                `${q} end date outside quarter`
            );
        }

    });

    updateSubCategories();
    updateSummary();
    updateStatusBadge();

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

    document.getElementById(
        'completionPercent'
    ).textContent =
        percent + '%';

    document.getElementById(
        'completionBar'
    ).style.width =
        percent + '%';
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

</script>

</body>
</html>
