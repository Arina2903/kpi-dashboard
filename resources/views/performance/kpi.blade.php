<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Appraisal · {{ $qLabel }} · {{ $currentFinancialYear }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, * { font-family: 'Inter', sans-serif; }
        .soft-card { box-shadow: 0 8px 30px rgba(15,23,42,.07); }
        @media print {
            #sidebar, #sidebarCloseBtn { display: none !important; }
            #mainContent { margin-left: 0 !important; }
            .no-print { display: none !important; }
            .print-break { page-break-before: always; }
        }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen">

{{-- ═══ STICKY HEADER ══════════════════════════════════════════════════════ --}}
<div class="sticky top-0 z-30 px-4 pt-4 pb-2 bg-[#f0f2f7]">
    <div class="rounded-[18px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#2d5548] text-white px-6 py-4 shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-xl font-black">Performance Appraisal</h1>
            <p class="text-white/70 text-[11px] mt-0.5">{{ $currentUserName }} · {{ $userPosition }} · {{ $departmentName }} · {{ $currentFinancialYear }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 no-print">
            <button onclick="window.print()" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl font-bold text-xs transition border border-white/20 flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / PDF
            </button>
        </div>
    </div>
</div>

<div class="px-4 pb-8 space-y-4">

{{-- ═══ WINDOW STATUS BANNER ═══════════════════════════════════════════════ --}}
@if($isWindowOpen)
<div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-3 flex items-center gap-3">
    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
    <p class="text-sm font-black text-emerald-700">Submission window is open</p>
    <span class="text-xs text-emerald-600">{{ $windowStart }} → {{ $windowEnd }}</span>
</div>
@else
<div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-3 flex items-center gap-3">
    <div class="w-2 h-2 rounded-full bg-amber-400"></div>
    <p class="text-sm font-black text-amber-700">{{ $qLabel }} submission window</p>
    <span class="text-xs text-amber-600">{{ $windowStart }} → {{ $windowEnd }}</span>
</div>
@endif

{{-- ═══ APPRAISAL FORM CARD ════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl border border-[#6B9080] soft-card overflow-hidden">

    {{-- Top accent strip --}}
    <div class="h-1 bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#A4C3B2]"></div>

    <div class="p-8">

        {{-- Report Header --}}
        <div class="flex items-start justify-between mb-8">
            <div>
                @php
                    $logoMap = ['RCG'=>'images/RCG-Logo.png','RGHB'=>'images/RGHB-Logo.png','RCT'=>'images/RCT-Logo.png'];
                    $logo = $logoMap[session('company_code')] ?? null;
                @endphp
                @if($logo)
                <img src="{{ asset(ltrim($logo,'/')) }}" alt="Logo" class="h-12 object-contain mb-1">
                @else
                <p class="text-2xl font-black text-[#1a3d34]">{{ session('company_display_name') }}</p>
                @endif
                <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest">Accelerating Your Business Success</p>
            </div>

            {{-- Quarter badge --}}
            <div class="flex flex-col items-center gap-1">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#1a3d34] to-[#6B9080] flex items-center justify-center shadow-lg">
                    <span class="text-3xl font-black text-white">{{ $qLabel }}</span>
                </div>
                <span class="text-[9px] font-black text-[#6B9080] uppercase tracking-widest">{{ $currentFinancialYear }}</span>
            </div>
        </div>

        {{-- Title --}}
        <div class="text-center mb-8">
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-[0.2em] mb-3">Private & Confidential</p>
            <div class="bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#2d5548] rounded-xl px-8 py-4 inline-block">
                <h2 class="text-xl font-black text-white tracking-widest uppercase">Executive / Non Executive Performance Appraisal</h2>
            </div>
            <p class="text-xs text-slate-400 mt-3">KPI Evaluation · Quarter {{ $displayQuarter }} · {{ $currentFinancialYear }}</p>
        </div>

        {{-- Purpose of Review --}}
        <div class="border border-[#6B9080]/30 rounded-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] px-5 py-2.5">
                <p class="text-[10px] font-black text-white uppercase tracking-widest">Purpose of Review</p>
            </div>
            <div class="p-5 flex flex-col md:flex-row md:items-start gap-6">
                {{-- Checkboxes --}}
                <div class="flex flex-col gap-3">
                    @foreach([
                        ['id' => 'por_confirmation',     'label' => 'Confirmation'],
                        ['id' => 'por_quarterly_review', 'label' => 'Quarterly Review'],
                        ['id' => 'por_others',           'label' => 'Others'],
                    ] as $opt)
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               id="{{ $opt['id'] }}"
                               name="purpose_of_review[]"
                               value="{{ $opt['label'] }}"
                               {{ $opt['id'] === 'por_quarterly_review' ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-[#6B9080] text-[#6B9080] accent-[#6B9080] cursor-pointer">
                        <span class="text-xs font-black text-slate-700 uppercase tracking-wider group-hover:text-[#6B9080] transition">
                            {{ $opt['label'] }}
                        </span>
                    </label>
                    @endforeach
                </div>

                {{-- Divider --}}
                <div class="hidden md:block w-px bg-[#6B9080]/20 self-stretch"></div>

                {{-- Others specify --}}
                <div class="flex-1 flex flex-col gap-1.5">
                    <label for="por_others_text" class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest">Please specify (if Others)</label>
                    <input type="text" id="por_others_text" placeholder="Describe purpose…"
                           class="border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-slate-50 focus:outline-none focus:border-[#6B9080] focus:bg-white transition w-full max-w-md">
                </div>

                {{-- Year / Period --}}
                <div class="flex flex-col gap-1.5 md:items-end">
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest">Year / Period Under Review</p>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-black text-slate-800">{{ now()->year }}</span>
                        <span class="text-slate-400 font-bold">/</span>
                        <span class="text-lg font-black text-[#6B9080]">{{ $qLabel }}</span>
                    </div>
                    <span class="text-[10px] text-slate-400">{{ $currentFinancialYear }}</span>
                </div>
            </div>
        </div>

        {{-- Employee Info Fields --}}
        <div class="border border-[#6B9080]/30 rounded-2xl overflow-hidden mb-8">
            @php
                $fields = [
                    ['label' => 'Name',                 'value' => $currentUserName],
                    ['label' => 'Position',              'value' => $userPosition],
                    ['label' => 'Reporting To',          'value' => $reportsToName],
                    ['label' => 'Department / Division', 'value' => $departmentName],
                    ['label' => 'Year / Period Under Review', 'value' => $currentFinancialYear . ' / ' . $qLabel],
                ];
            @endphp
            @foreach($fields as $i => $field)
            <div class="flex items-center {{ $i < count($fields)-1 ? 'border-b border-[#6B9080]/20' : '' }} {{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50' }}">
                <div class="w-52 shrink-0 px-5 py-3.5 border-r border-[#6B9080]/20">
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest">{{ $field['label'] }}</p>
                </div>
                <div class="flex-1 px-5 py-3.5">
                    <p class="text-sm font-semibold text-slate-800">{{ $field['value'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             SECTION 1 – TO BE COMPLETED BY EMPLOYEE UNDER REVIEW
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/40 rounded-2xl overflow-hidden mb-6">

            {{-- Section Header --}}
            <div class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] px-5 py-3">
                <p class="text-[11px] font-black text-white uppercase tracking-widest">Section 1 – To Be Completed by Employee Under Review</p>
            </div>

            <div class="p-6 space-y-6">

                {{-- PART A --}}
                <div>
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-4 pb-1 border-b border-[#6B9080]/20">Part A – Employee's Particulars</p>

                    {{-- Row 1: Name + Start Date --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Name</label>
                            <input type="text" value="{{ $currentUserName }}" readonly
                                   class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-slate-50 focus:outline-none cursor-default">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Start Date</label>
                            <input type="text" value="{{ isset($user['start_date']) ? \Carbon\Carbon::parse($user['start_date'])->format('d M Y') : (isset($user['created_at']) ? \Carbon\Carbon::parse($user['created_at'])->format('d M Y') : '') }}"
                                   placeholder="DD MMM YYYY"
                                   class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                        </div>
                    </div>

                    {{-- Row 2: Current Position + Dept/Div --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Current Position</label>
                            <input type="text" value="{{ $userPosition }}" readonly
                                   class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-slate-50 focus:outline-none cursor-default">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Dept / Div</label>
                            <input type="text" value="{{ $departmentName }}" readonly
                                   class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-slate-50 focus:outline-none cursor-default">
                        </div>
                    </div>

                    {{-- Row 3: Months/Years of Service --}}
                    <div class="mb-4">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Months / Years of Service</label>
                        @php
                            $serviceText = '';
                            $serviceDate = $user['start_date'] ?? $user['created_at'] ?? null;
                            if ($serviceDate) {
                                $start   = \Carbon\Carbon::parse($serviceDate);
                                $diff    = $start->diff(now());
                                $yrs     = $diff->y;
                                $mos     = $diff->m;
                                $serviceText = ($yrs > 0 ? $yrs . ' yr' . ($yrs > 1 ? 's' : '') . ' ' : '') . $mos . ' month' . ($mos !== 1 ? 's' : '');
                            }
                        @endphp
                        <input type="text" value="{{ $serviceText }}" readonly
                               class="w-full md:w-1/2 border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-slate-50 focus:outline-none cursor-default">
                    </div>

                    {{-- Row 4: Leave / Lateness --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Medical Leave Taken During Review Period</label>
                                <input type="number" min="0" placeholder="Days"
                                       class="w-32 border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Emergency Leave Taken During Review Period</label>
                                <input type="number" min="0" placeholder="Days"
                                       class="w-32 border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">No. of Lateness During Review Period</label>
                            <input type="number" min="0" placeholder="Times"
                                   class="w-32 border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                        </div>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-t border-[#6B9080]/15"></div>

                {{-- PART B --}}
                <div>
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-1">Part B</p>
                    <p class="text-xs text-slate-600 mb-3">Summarize present duties &amp; indicate if any key tasks set for the year / period have been achieved.</p>
                    <textarea rows="5" placeholder="Write your summary here…"
                              class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-3 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition resize-none"></textarea>
                </div>

                {{-- Divider --}}
                <div class="border-t border-[#6B9080]/15"></div>

                {{-- PART C --}}
                <div>
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-1">Part C</p>
                    <p class="text-xs text-slate-600 mb-3"><span class="italic">[Where applicable]</span> List what you see as your key tasks for the forthcoming year / period.</p>
                    <textarea rows="5" placeholder="List your key tasks for the next period…"
                              class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-3 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition resize-none"></textarea>
                </div>

                {{-- Divider --}}
                <div class="border-t border-[#6B9080]/15"></div>

                {{-- PART D --}}
                <div>
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-1">Part D – To Be Completed by the Appraiser / Superior</p>
                    <p class="text-xs text-slate-500 mb-4 italic">
                        I hereby confirm that the above information provided by the appraisee is correct and that the appraisee has been directly reporting to me since
                        <input type="text" placeholder="DD MMM YYYY"
                               class="inline-block w-32 border-b border-[#6B9080] bg-transparent text-sm text-slate-700 px-1 focus:outline-none mx-1">
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Name</label>
                            <input type="text" value="{{ $reportsToName !== '-' ? $reportsToName : '' }}"
                                   placeholder="Appraiser name"
                                   class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Designation</label>
                            <input type="text" placeholder="Appraiser designation"
                                   class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                        </div>
                    </div>
                </div>

            </div>{{-- /p-6 --}}
        </div>{{-- /Section 1 --}}

        {{-- ══ SECTION 2 ══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/40 rounded-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] px-5 py-3">
                <p class="text-[11px] font-black text-white uppercase tracking-widest">Section 2 – OKR / KPI Quarterly Performance Review</p>
            </div>

            <div class="overflow-x-auto">
            @if(empty($kpis))
            <div class="p-10 text-center">
                <p class="text-slate-400 text-sm">No KPIs found for {{ $currentFinancialYear }}</p>
            </div>
            @else
            <table class="w-full text-xs" id="sec2Table">
                <thead>
                    <tr class="bg-[#1a3d34] text-white">
                        <th class="px-3 py-3 text-center font-black text-[10px] uppercase tracking-wider w-10">No.</th>
                        <th class="px-3 py-3 text-left font-black text-[10px] uppercase tracking-wider w-40">OKR</th>
                        <th class="px-3 py-3 text-center font-black text-[10px] uppercase tracking-wider w-10">Sub</th>
                        <th class="px-3 py-3 text-left font-black text-[10px] uppercase tracking-wider">Initiative</th>
                        <th class="px-3 py-3 text-center font-black text-[10px] uppercase tracking-wider w-20">A · Actual</th>
                        <th class="px-3 py-3 text-center font-black text-[10px] uppercase tracking-wider w-20">B · Target</th>
                        <th class="px-3 py-3 text-center font-black text-[10px] uppercase tracking-wider w-24">C · Score<br><span class="font-normal normal-case">(A÷B)×5</span></th>
                        <th class="px-3 py-3 text-center font-black text-[10px] uppercase tracking-wider w-24">D · Pro-Rated<br><span class="font-normal normal-case">Self Score</span></th>
                        <th class="px-3 py-3 text-center font-black text-[10px] uppercase tracking-wider w-24">Appraiser<br><span class="font-normal normal-case">Score</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($kpis as $kpiIdx => $kpi)
                @php
                    $qs        = $quarterScores[$kpi['id']] ?? null;
                    $dbActual  = isset($qs['quarter_actual']) ? (float)$qs['quarter_actual'] : '';
                    $dbTarget  = isset($qs['quarter_target']) ? (float)$qs['quarter_target'] : (float)($kpi['base_target'] ?? '');
                    $subCount  = 4;
                    $kpiNo     = $kpiIdx + 1;
                @endphp
                {{-- OKR group header row --}}
                <tr class="bg-[#6B9080]/10 border-b border-[#6B9080]/20">
                    <td class="px-3 py-3 text-center font-black text-slate-800">{{ $kpiNo }}</td>
                    <td class="px-3 py-3" colspan="2">
                        <p class="font-black text-[#1a3d34] text-[11px] uppercase tracking-wide">OKR / KPI</p>
                        <p class="text-xs text-slate-700 mt-0.5 leading-snug">{{ $kpi['kpi_title'] }}</p>
                        <p class="text-[9px] text-slate-400">{{ $kpi['category'] ?? '' }}{{ isset($kpi['sub_category']) ? ' · '.$kpi['sub_category'] : '' }}</p>
                    </td>
                    <td colspan="6" class="px-3 py-3 text-right">
                        <span class="text-[9px] font-black text-[#6B9080] bg-[#6B9080]/10 px-2 py-1 rounded-full uppercase tracking-wider">{{ $kpi['weightage'] ?? '—' }}% weight</span>
                    </td>
                </tr>
                {{-- 4 sub-initiative rows --}}
                @for($sub = 1; $sub <= $subCount; $sub++)
                @php
                    $subLabel  = $kpiNo . '.' . $sub;
                    $isFirst   = $sub === 1;
                    $rowBg     = $sub % 2 === 0 ? 'bg-slate-50/40' : 'bg-white';
                @endphp
                <tr class="{{ $rowBg }} border-b border-[#6B9080]/10 sec2-row" data-kpi="{{ $kpiNo }}" data-sub="{{ $sub }}">
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2 text-center text-[10px] font-black text-slate-500">{{ $subLabel }}</td>
                    {{-- Initiative text --}}
                    <td class="px-3 py-2">
                        <input type="text"
                               placeholder="Initiative {{ $subLabel }}…"
                               class="w-full bg-transparent border-b border-[#6B9080]/30 py-1 text-[11px] text-slate-700 placeholder-slate-300 focus:outline-none focus:border-[#6B9080] transition">
                    </td>
                    {{-- A: Actual --}}
                    <td class="px-3 py-2 text-center">
                        <input type="number" step="any" min="0"
                               value="{{ $isFirst && $dbActual !== '' ? $dbActual : '' }}"
                               placeholder="—"
                               data-col="actual"
                               class="w-16 text-center bg-white border border-[#6B9080]/30 rounded-lg px-1 py-1 text-[11px] text-slate-700 focus:outline-none focus:border-[#6B9080] transition sec2-actual">
                    </td>
                    {{-- B: Target --}}
                    <td class="px-3 py-2 text-center">
                        <input type="number" step="any" min="0"
                               value="{{ $isFirst && $dbTarget !== '' ? $dbTarget : '' }}"
                               placeholder="—"
                               data-col="target"
                               class="w-16 text-center bg-white border border-[#6B9080]/30 rounded-lg px-1 py-1 text-[11px] text-slate-700 focus:outline-none focus:border-[#6B9080] transition sec2-target">
                    </td>
                    {{-- C: Score auto-calc --}}
                    <td class="px-3 py-2 text-center">
                        <span class="sec2-score text-sm font-black text-slate-300">—</span>
                    </td>
                    {{-- D: Pro-Rated Self Score --}}
                    <td class="px-3 py-2 text-center">
                        <input type="number" step="0.1" min="0" max="5" placeholder="—"
                               class="w-16 text-center bg-white border border-[#6B9080]/30 rounded-lg px-1 py-1 text-[11px] text-slate-700 focus:outline-none focus:border-[#6B9080] transition">
                    </td>
                    {{-- Appraiser Score --}}
                    <td class="px-3 py-2 text-center">
                        <input type="number" step="0.1" min="0" max="5" placeholder="—"
                               class="w-16 text-center bg-white border border-[#6B9080]/30 rounded-lg px-1 py-1 text-[11px] text-slate-700 focus:outline-none focus:border-[#6B9080] transition">
                    </td>
                </tr>
                @endfor
                @endforeach

                {{-- Total Score row --}}
                <tr class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] text-white">
                    <td colspan="6" class="px-4 py-3 text-right">
                        <p class="text-[10px] font-black uppercase tracking-widest">Total Score Section 2</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span id="sec2TotalScore" class="text-base font-black">—</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span id="sec2TotalSelf" class="text-base font-black">—</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span id="sec2TotalAppraiser" class="text-base font-black">—</span>
                    </td>
                </tr>
                {{-- % row --}}
                <tr class="bg-[#1a3d34]/5 border-t border-[#6B9080]/20">
                    <td colspan="6" class="px-4 py-3 text-right">
                        <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest">% Total</p>
                        <p class="text-[9px] text-slate-400">Formula: Total Score Section 2 ÷ {{ count($kpis) * 5 }} × 70</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span id="sec2PctScore" class="text-base font-black text-slate-300">—</span>
                        <p class="text-[9px] text-slate-400">/ 70 pts</p>
                    </td>
                    <td colspan="2" class="px-4 py-3 text-center text-[9px] text-slate-400 italic leading-relaxed">
                        Example: A (target) = 100 calls,<br>A (actual) = 60,<br>Score C = (A÷B) × 5 = 3.0
                    </td>
                </tr>
                </tbody>
            </table>
            @endif
            </div>
        </div>

        <script>
        // Section 2 — live score calculation
        (function () {
            const maxPerKpi = 5; // max score per sub-initiative
            const kpiCount  = {{ count($kpis) }};

            function scoreColorClass(v) {
                if (v === null) return 'text-slate-300';
                if (v >= 4)    return 'text-emerald-600';
                if (v >= 3)    return 'text-[#6B9080]';
                if (v >= 2)    return 'text-amber-500';
                return 'text-red-500';
            }

            function recalcRow(row) {
                const a = parseFloat(row.querySelector('.sec2-actual')?.value);
                const b = parseFloat(row.querySelector('.sec2-target')?.value);
                const scoreEl = row.querySelector('.sec2-score');
                if (!scoreEl) return null;
                if (!isNaN(a) && !isNaN(b) && b > 0) {
                    const c = Math.min((a / b) * 5, 5);
                    scoreEl.textContent = c.toFixed(2);
                    scoreEl.className = 'sec2-score text-sm font-black ' + scoreColorClass(c);
                    return c;
                }
                scoreEl.textContent = '—';
                scoreEl.className = 'sec2-score text-sm font-black text-slate-300';
                return null;
            }

            function recalcAll() {
                const rows   = document.querySelectorAll('#sec2Table tr.sec2-row');
                let totalC   = 0, countC = 0;
                let totalSelf = 0, countSelf = 0;
                let totalApp = 0, countApp = 0;

                rows.forEach(row => {
                    const c = recalcRow(row);
                    if (c !== null) { totalC += c; countC++; }

                    const selfInp = row.querySelectorAll('input[type=number]')[2];
                    const appInp  = row.querySelectorAll('input[type=number]')[3];
                    const sv = parseFloat(selfInp?.value);
                    const av = parseFloat(appInp?.value);
                    if (!isNaN(sv)) { totalSelf += sv; countSelf++; }
                    if (!isNaN(av)) { totalApp  += av; countApp++; }
                });

                const maxTotal = kpiCount * 5;

                const scoreEl    = document.getElementById('sec2TotalScore');
                const selfEl     = document.getElementById('sec2TotalSelf');
                const appEl      = document.getElementById('sec2TotalAppraiser');
                const pctEl      = document.getElementById('sec2PctScore');

                scoreEl.textContent = countC    ? totalC.toFixed(2)    : '—';
                selfEl.textContent  = countSelf ? totalSelf.toFixed(2) : '—';
                appEl.textContent   = countApp  ? totalApp.toFixed(2)  : '—';

                if (countC) {
                    const pct = (totalC / maxTotal * 70).toFixed(1);
                    pctEl.textContent = pct + '%';
                    pctEl.className = 'text-base font-black ' + scoreColorClass(totalC / maxTotal * 5);
                } else {
                    pctEl.textContent = '—';
                    pctEl.className = 'text-base font-black text-slate-300';
                }
            }

            document.getElementById('sec2Table')?.addEventListener('input', recalcAll);
            recalcAll();
        })();
        </script>

        {{-- Signature Section --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10 pt-8 border-t border-[#6B9080]/20">
            @foreach([
                ['role' => 'Employee',    'name' => $currentUserName,  'label' => 'Signature & Date'],
                ['role' => 'Reporting To','name' => $reportsToName,     'label' => 'Signature & Date'],
                ['role' => 'HR / Verified','name' => '',               'label' => 'Signature & Date'],
            ] as $sig)
            <div class="text-center">
                <div class="h-16 border-b-2 border-[#6B9080]/40 border-dashed mb-3 mx-4"></div>
                <p class="text-xs font-black text-slate-700">{{ $sig['name'] ?: '_______________' }}</p>
                <p class="text-[9px] text-[#6B9080] font-semibold uppercase tracking-wider mt-1">{{ $sig['role'] }}</p>
                <p class="text-[9px] text-slate-400 mt-0.5">{{ $sig['label'] }}</p>
            </div>
            @endforeach
        </div>

    </div>{{-- /p-8 --}}
</div>{{-- /appraisal card --}}

</div>{{-- /px-4 --}}
</main>

</body>
</html>
