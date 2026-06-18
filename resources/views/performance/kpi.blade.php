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

        {{-- KPI Table --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-black text-slate-900">KPI Performance — {{ $qLabel }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ count($kpis) }} KPIs for {{ $currentFinancialYear }}</p>
                </div>
                <span class="text-[9px] font-black text-[#6B9080] bg-[#6B9080]/10 px-3 py-1.5 rounded-full uppercase tracking-wider">{{ $qLabel }} · {{ $currentFinancialYear }}</span>
            </div>

            @if(empty($kpis))
            <div class="border border-dashed border-[#6B9080] rounded-2xl p-10 text-center">
                <p class="text-slate-400 text-sm">No KPIs found for {{ $currentFinancialYear }}</p>
            </div>
            @else
            <div class="border border-[#6B9080]/30 rounded-2xl overflow-hidden">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] text-white">
                            <th class="px-4 py-3 text-left font-black text-[10px] uppercase tracking-wider w-6">#</th>
                            <th class="px-4 py-3 text-left font-black text-[10px] uppercase tracking-wider">KPI Title</th>
                            <th class="px-4 py-3 text-left font-black text-[10px] uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-center font-black text-[10px] uppercase tracking-wider">Weight</th>
                            <th class="px-4 py-3 text-center font-black text-[10px] uppercase tracking-wider">Target</th>
                            <th class="px-4 py-3 text-center font-black text-[10px] uppercase tracking-wider">{{ $qLabel }} Actual</th>
                            <th class="px-4 py-3 text-center font-black text-[10px] uppercase tracking-wider">{{ $qLabel }} Score</th>
                            <th class="px-4 py-3 text-center font-black text-[10px] uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kpis as $i => $kpi)
                        @php
                            $qScore  = $quarterScores[$kpi['id']] ?? null;
                            $actual  = isset($qScore['quarter_actual']) ? (float)$qScore['quarter_actual'] : null;
                            $target  = isset($qScore['quarter_target']) ? (float)$qScore['quarter_target'] : (float)($kpi['base_target'] ?? 0);
                            $score   = ($actual !== null && $target > 0) ? round($actual / $target * 100, 1) : null;
                            $status  = $qScore['status'] ?? ($kpi['status'] ?? 'not_started');

                            $statusConfig = match(strtolower($status)) {
                                'on_track','monitoring' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'On Track'],
                                'at_risk','risk'        => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'label' => 'At Risk'],
                                'in_trouble','critical' => ['bg' => 'bg-red-100',     'text' => 'text-red-700',     'label' => 'Critical'],
                                'completed'             => ['bg' => 'bg-[#F5EAE0]',   'text' => 'text-[#6B3F2A]',   'label' => 'Completed'],
                                default                 => ['bg' => 'bg-slate-100',   'text' => 'text-slate-500',   'label' => 'Not Started'],
                            };

                            $scoreColor = match(true) {
                                $score === null => 'text-slate-300',
                                $score >= 90   => 'text-emerald-600',
                                $score >= 70   => 'text-[#6B9080]',
                                $score >= 50   => 'text-amber-500',
                                default        => 'text-red-500',
                            };
                        @endphp
                        <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} border-b border-[#6B9080]/10 hover:bg-[#6B9080]/5 transition">
                            <td class="px-4 py-3 text-slate-400 font-bold">{{ $i + 1 }}</td>
                            <td class="px-4 py-3">
                                <p class="font-black text-slate-800 leading-snug">{{ $kpi['kpi_title'] }}</p>
                                <p class="text-[9px] text-slate-400 mt-0.5">{{ $kpi['sub_category'] ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $kpi['category'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-black text-[#6B9080]">{{ $kpi['weightage'] ?? '-' }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">
                                {{ number_format((float)($kpi['base_target'] ?? 0), 0) }}
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">
                                {{ $actual !== null ? number_format((float)$actual, 0) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-black text-lg {{ $scoreColor }}">
                                    {{ $score !== null ? number_format((float)$score, 1).'%' : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                    {{ $statusConfig['label'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach

                        {{-- Summary row --}}
                        @php
                            $totalWeight = collect($kpis)->sum(fn($k) => (float)($k['weightage'] ?? 0));
                            $scores = collect($kpis)->map(function($k) use ($quarterScores) {
                                $qs  = $quarterScores[$k['id']] ?? null;
                                $act = isset($qs['quarter_actual']) ? (float)$qs['quarter_actual'] : null;
                                $tgt = isset($qs['quarter_target']) ? (float)$qs['quarter_target'] : (float)($k['base_target'] ?? 0);
                                return ($act !== null && $tgt > 0) ? round($act / $tgt * 100, 1) : null;
                            })->filter(fn($s) => $s !== null);
                            $avgScore = $scores->isNotEmpty() ? round($scores->avg(), 1) : null;
                        @endphp
                        <tr class="bg-gradient-to-r from-[#1a3d34]/5 to-[#6B9080]/10 border-t-2 border-[#6B9080]/30">
                            <td colspan="3" class="px-4 py-3">
                                <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-wider">Overall Summary</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-black text-slate-800">{{ number_format($totalWeight, 0) }}%</span>
                            </td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3 text-center">
                                @if($avgScore !== null)
                                <span class="font-black text-lg {{ $avgScore >= 90 ? 'text-emerald-600' : ($avgScore >= 70 ? 'text-[#6B9080]' : ($avgScore >= 50 ? 'text-amber-500' : 'text-red-500')) }}">
                                    {{ number_format($avgScore, 1) }}%
                                </span>
                                @else
                                <span class="text-slate-300 font-black">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif
        </div>

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
