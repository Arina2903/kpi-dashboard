<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attitude Appraisal · {{ $qLabel }} · {{ $currentFinancialYear }}</title>
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
        }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen">

{{-- STICKY HEADER --}}
<div class="sticky top-0 z-30 px-4 pt-4 pb-2 bg-[#f0f2f7]">
    <div class="rounded-[18px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#2d5548] text-white px-6 py-4 shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-xl font-black">Attitude Appraisal</h1>
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

{{-- WINDOW BANNER --}}
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

{{-- APPRAISAL CARD --}}
<div class="bg-white rounded-2xl border border-[#6B9080] soft-card overflow-hidden">
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
            <p class="text-xs text-slate-400 mt-3">Attitude & Competency Evaluation · Quarter {{ $displayQuarter }} · {{ $currentFinancialYear }}</p>
        </div>

        {{-- Purpose of Review --}}
        <div class="border border-[#6B9080]/30 rounded-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] px-5 py-2.5">
                <p class="text-[10px] font-black text-white uppercase tracking-widest">Purpose of Review</p>
            </div>
            <div class="p-5 flex flex-col md:flex-row md:items-start gap-6">
                <div class="flex flex-col gap-3">
                    @foreach([
                        ['id' => 'por_confirmation',     'label' => 'Confirmation'],
                        ['id' => 'por_quarterly_review', 'label' => 'Quarterly Review'],
                        ['id' => 'por_others',           'label' => 'Others'],
                    ] as $opt)
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" id="{{ $opt['id'] }}" value="{{ $opt['label'] }}"
                               {{ $opt['id'] === 'por_quarterly_review' ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-[#6B9080] accent-[#6B9080] cursor-pointer">
                        <span class="text-xs font-black text-slate-700 uppercase tracking-wider group-hover:text-[#6B9080] transition">{{ $opt['label'] }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="hidden md:block w-px bg-[#6B9080]/20 self-stretch"></div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <label class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest">Please specify (if Others)</label>
                    <input type="text" placeholder="Describe purpose…"
                           class="border border-[#6B9080]/40 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-slate-50 focus:outline-none focus:border-[#6B9080] focus:bg-white transition w-full max-w-md">
                </div>
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

        {{-- Employee info strip --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
            @foreach([
                ['label'=>'Name',           'value'=>$currentUserName],
                ['label'=>'Position',        'value'=>$userPosition],
                ['label'=>'Department',      'value'=>$departmentName],
                ['label'=>'Reporting To',    'value'=>$reportsToName],
            ] as $f)
            <div class="border border-[#6B9080]/30 rounded-xl px-4 py-3 bg-slate-50">
                <p class="text-[9px] font-black text-[#6B9080] uppercase tracking-widest mb-1">{{ $f['label'] }}</p>
                <p class="text-xs font-semibold text-slate-800 leading-snug">{{ $f['value'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- ══ SECTION 3 ══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/40 rounded-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] px-5 py-3">
                <p class="text-[11px] font-black text-white uppercase tracking-widest">Section 3 – Executive / Non-Executive : Performance Appraisal</p>
            </div>

            {{-- Rating Scale Legend --}}
            <div class="p-5 border-b border-[#6B9080]/15">
                <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-3">Rating Scale</p>
                <div class="border border-[#6B9080]/30 rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-[#1a3d34] text-white">
                                <th class="px-4 py-2.5 text-left font-black text-[10px] uppercase tracking-wider w-40">Category</th>
                                <th class="px-4 py-2.5 text-center font-black text-[10px] uppercase tracking-wider w-16">Rating</th>
                                <th class="px-4 py-2.5 text-left font-black text-[10px] uppercase tracking-wider">Definition</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $ratings = [
                                ['cat'=>'Outstanding',     'score'=>5, 'def'=>'Outstanding & exceptional performance in the position, showing a high level of initiative, consistently sound judgement and excellent decision making.', 'bg'=>'bg-emerald-50', 'badge'=>'bg-emerald-100 text-emerald-700'],
                                ['cat'=>'Above Average',   'score'=>4, 'def'=>'Performance that consistently meets all normal requirements of the position and exceeds requirements in one or more major aspects of the work.', 'bg'=>'bg-[#6B9080]/5', 'badge'=>'bg-[#6B9080]/15 text-[#1a3d34]'],
                                ['cat'=>'Average',         'score'=>3, 'def'=>'Performance which meets the normal requirements of the position.', 'bg'=>'bg-white', 'badge'=>'bg-slate-100 text-slate-600'],
                                ['cat'=>'Below Average',   'score'=>2, 'def'=>'Performance that is below what is normally expected in the position and which requires improvement in one or more basic aspects of the work (remedial steps required).', 'bg'=>'bg-amber-50', 'badge'=>'bg-amber-100 text-amber-700'],
                                ['cat'=>'Unsatisfactory',  'score'=>1, 'def'=>'Inadequate performance which does not meet the normal performance, and the improvement has not been forthcoming (the employee is to be informed and appropriate action taken such as counselling, redevelopment and training, reassignment, demotion or termination, depending on the circumstances).', 'bg'=>'bg-red-50', 'badge'=>'bg-red-100 text-red-700'],
                            ];
                            @endphp
                            @foreach($ratings as $r)
                            <tr class="{{ $r['bg'] }} border-b border-[#6B9080]/10">
                                <td class="px-4 py-2.5">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black {{ $r['badge'] }}">{{ $r['cat'] }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="text-base font-black text-slate-700">{{ $r['score'] }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-600 leading-relaxed">{{ $r['def'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Assessment Areas Table --}}
            <div class="p-5">
                <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-3">Area/s of Assessment</p>
                <div class="border border-[#6B9080]/30 rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] text-white">
                                <th class="px-4 py-3 text-left font-black text-[10px] uppercase tracking-wider">Area / Assessment</th>
                                <th class="px-4 py-3 text-center font-black text-[10px] uppercase tracking-wider w-20">Self Score</th>
                                <th class="px-4 py-3 text-center font-black text-[10px] uppercase tracking-wider w-24">Superior Score</th>
                                <th class="px-4 py-3 text-left font-black text-[10px] uppercase tracking-wider w-56">Appraiser's Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assessmentAreas as $i => $area)
                            <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} border-b border-[#6B9080]/10">
                                {{-- Area description --}}
                                <td class="px-4 py-4 align-top">
                                    <p class="font-black text-slate-800 mb-1">{{ $area['no'] }}) {{ $area['title'] }}</p>
                                    <p class="text-[11px] text-slate-400 leading-relaxed italic">{{ $area['description'] }}</p>
                                </td>

                                {{-- Self Score (1–5 radio) --}}
                                <td class="px-3 py-4 text-center align-top border-l border-[#6B9080]/10">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">Self</p>
                                    <div class="flex flex-col items-center gap-1">
                                        @foreach([5,4,3,2,1] as $score)
                                        <label class="flex items-center gap-1 cursor-pointer group">
                                            <input type="radio"
                                                   name="self_area_{{ $area['no'] }}"
                                                   value="{{ $score }}"
                                                   class="w-3 h-3 accent-[#6B9080] cursor-pointer self-radio">
                                            <span class="text-[10px] font-bold text-slate-500 group-hover:text-[#6B9080]">{{ $score }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Superior Score (1–5 radio) --}}
                                <td class="px-3 py-4 text-center align-top border-l border-[#6B9080]/10">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">Superior</p>
                                    <div class="flex flex-col items-center gap-1">
                                        @foreach([5,4,3,2,1] as $score)
                                        <label class="flex items-center gap-1 cursor-pointer group">
                                            <input type="radio"
                                                   name="superior_area_{{ $area['no'] }}"
                                                   value="{{ $score }}"
                                                   class="w-3 h-3 accent-[#1a3d34] cursor-pointer superior-radio">
                                            <span class="text-[10px] font-bold text-slate-500 group-hover:text-[#1a3d34]">{{ $score }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Appraiser Comment --}}
                                <td class="px-4 py-4 align-top border-l border-[#6B9080]/10">
                                    <textarea rows="5" placeholder="Appraiser's comment…"
                                              class="w-full border border-[#6B9080]/30 rounded-lg px-3 py-2 text-[11px] text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition resize-none"></textarea>
                                </td>
                            </tr>
                            @endforeach

                            {{-- Summary row --}}
                            <tr class="bg-gradient-to-r from-[#1a3d34]/5 to-[#6B9080]/10 border-t-2 border-[#6B9080]/30">
                                <td class="px-4 py-4">
                                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-wider">No. of Areas Assessed</p>
                                    <p class="text-2xl font-black text-slate-800 mt-1">{{ count($assessmentAreas) }}</p>
                                    <p class="text-[9px] text-slate-400 mt-2 italic">
                                        Example: For % total, formula is<br>
                                        <span class="font-black text-slate-600">Total Score Section 3 ÷ {{ count($assessmentAreas) * 5 }} × 25</span>
                                    </p>
                                </td>
                                <td class="px-3 py-4 text-center border-l border-[#6B9080]/20">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Self Score</p>
                                    <span id="selfTotalDisplay" class="text-2xl font-black text-slate-300">—</span>
                                    <p class="text-[9px] text-slate-400 mt-1" id="selfPctDisplay"></p>
                                </td>
                                <td class="px-3 py-4 text-center border-l border-[#6B9080]/20">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Superior Score</p>
                                    <span id="superiorTotalDisplay" class="text-2xl font-black text-slate-300">—</span>
                                    <p class="text-[9px] text-slate-400 mt-1" id="superiorPctDisplay"></p>
                                </td>
                                <td class="border-l border-[#6B9080]/20"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══ SECTION 4 ══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/40 rounded-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] px-5 py-3">
                <p class="text-[11px] font-black text-white uppercase tracking-widest">Section 4 – Executive / Non-Executive : Performance Analysis</p>
            </div>

            <div class="p-6 space-y-6">

                {{-- A) Rating Summary --}}
                <div>
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-1">A) Rating</p>
                    <p class="text-[11px] text-slate-500 italic mb-4">This is a summary score of the Appraisal Review in Section 2 and Section 3.</p>

                    <div class="border border-[#6B9080]/30 rounded-xl overflow-hidden mb-5" id="section4Table">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-[#1a3d34] text-white">
                                    <th class="px-5 py-3 text-left font-black text-[10px] uppercase tracking-wider w-48"></th>
                                    <th class="px-5 py-3 text-center font-black text-[10px] uppercase tracking-wider">Self Score</th>
                                    <th class="px-5 py-3 text-center font-black text-[10px] uppercase tracking-wider">Appraiser</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white border-b border-[#6B9080]/10">
                                    <td class="px-5 py-4">
                                        <p class="font-black text-slate-700">Section 2</p>
                                        <p class="text-[9px] text-slate-400">KPI Performance</p>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="number" min="0" max="100" placeholder="—"
                                               class="w-20 text-center border border-[#6B9080]/40 rounded-lg px-2 py-1.5 text-sm font-black text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="number" min="0" max="100" placeholder="—"
                                               class="w-20 text-center border border-[#6B9080]/40 rounded-lg px-2 py-1.5 text-sm font-black text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                                    </td>
                                </tr>
                                <tr class="bg-slate-50/60 border-b border-[#6B9080]/10">
                                    <td class="px-5 py-4">
                                        <p class="font-black text-slate-700">Section 3</p>
                                        <p class="text-[9px] text-slate-400">Attitude & Competency</p>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="number" min="0" max="100" placeholder="—"
                                               class="w-20 text-center border border-[#6B9080]/40 rounded-lg px-2 py-1.5 text-sm font-black text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="number" min="0" max="100" placeholder="—"
                                               class="w-20 text-center border border-[#6B9080]/40 rounded-lg px-2 py-1.5 text-sm font-black text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition">
                                    </td>
                                </tr>
                                <tr class="bg-gradient-to-r from-[#1a3d34]/5 to-[#6B9080]/10">
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-black text-slate-900 uppercase tracking-wider">Rating</p>
                                        <p class="text-[9px] text-slate-400">Combined total</p>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span id="ratingTotalSelf" class="text-xl font-black text-slate-300">—</span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span id="ratingTotalAppraiser" class="text-xl font-black text-slate-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Scoring Matrix --}}
                    <div class="border border-[#6B9080]/30 rounded-xl overflow-hidden">
                        <div class="bg-[#1a3d34] px-5 py-2.5 text-center">
                            <p class="text-[11px] font-black text-white uppercase tracking-widest">Scoring Matrix</p>
                        </div>
                        <table class="w-full text-xs">
                            <tbody>
                                @php
                                $matrix = [
                                    ['range'=>'90 – 100', 'label'=>'Outstanding',        'bg'=>'bg-emerald-50', 'text'=>'text-emerald-700', 'badge'=>'bg-emerald-100'],
                                    ['range'=>'70 – 89',  'label'=>'Meets Expectations', 'bg'=>'bg-[#6B9080]/5','text'=>'text-[#1a3d34]',   'badge'=>'bg-[#6B9080]/15'],
                                    ['range'=>'50 – 69',  'label'=>'Below Average',      'bg'=>'bg-amber-50',   'text'=>'text-amber-700',   'badge'=>'bg-amber-100'],
                                    ['range'=>'1 – 49',   'label'=>'Unsatisfactory',     'bg'=>'bg-red-50',     'text'=>'text-red-700',     'badge'=>'bg-red-100'],
                                ];
                                @endphp
                                @foreach($matrix as $m)
                                <tr class="{{ $m['bg'] }} border-b border-[#6B9080]/10">
                                    <td class="px-5 py-3 text-center font-black text-slate-700 w-32">{{ $m['range'] }}</td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black {{ $m['badge'] }} {{ $m['text'] }}">{{ $m['label'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-t border-[#6B9080]/15"></div>

                {{-- B) Performance Analysis --}}
                <div>
                    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-1">B) Performance Analysis</p>
                    <p class="text-[11px] text-slate-500 italic mb-5">To be completed by Appraiser.</p>

                    <div class="space-y-4">
                        @foreach([
                            ['key'=>'strengths',        'label'=>'Strengths'],
                            ['key'=>'work_ethics',      'label'=>'Work Ethics / Attitude'],
                            ['key'=>'areas_improvement','label'=>'Areas Need Improvement'],
                            ['key'=>'training_required','label'=>'Training Required'],
                        ] as $field)
                        <div class="flex items-start gap-4">
                            <div class="w-2 h-2 rounded-full bg-[#6B9080] mt-3 shrink-0"></div>
                            <div class="flex-1">
                                <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1.5">{{ $field['label'] }}</label>
                                <input type="text" placeholder="Enter {{ strtolower($field['label']) }}…"
                                       class="w-full border-b border-[#6B9080]/40 bg-transparent py-2 text-sm text-slate-700 focus:outline-none focus:border-[#6B9080] transition">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-t border-[#6B9080]/15"></div>

                {{-- Appraiser Confirmation --}}
                <div class="space-y-6">
                    <div>
                        <p class="text-xs text-slate-600 italic leading-relaxed mb-6">
                            I hereby confirm that the foregoing appraisal is a fair and objective evaluation of the appraisee's performance during the period under review.
                        </p>
                        <div class="flex justify-end">
                            <div class="text-center w-72">
                                <div class="h-14 border-b-2 border-[#6B9080]/40 border-dashed mb-2"></div>
                                <p class="text-xs font-black text-slate-700">{{ $reportsToName !== '-' ? $reportsToName : '_______________' }}</p>
                                <p class="text-[9px] text-[#6B9080] font-semibold uppercase tracking-wider mt-1">Signature of the Appraiser – Manager / VP</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#6B9080]/15 pt-6">
                        <p class="text-xs text-slate-600 italic leading-relaxed mb-3">
                            I hereby confirm that I have read, understood and accept/disagree with the foregoing appraisal.
                            <span class="text-[#6B9080] font-semibold">(If you disagree please specify)</span>
                        </p>
                        <textarea rows="4" placeholder="Write your response here…"
                                  class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-3 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition resize-none mb-6"></textarea>
                        <div class="flex justify-end">
                            <div class="text-center w-72">
                                <div class="h-14 border-b-2 border-[#6B9080]/40 border-dashed mb-2"></div>
                                <p class="text-xs font-black text-slate-700">{{ $currentUserName }}</p>
                                <p class="text-[9px] text-[#6B9080] font-semibold uppercase tracking-wider mt-1">Signature of the Appraisee</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /p-6 --}}
        </div>{{-- /Section 4 --}}

        {{-- ══ SECTION 5 ══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/40 rounded-2xl overflow-hidden mb-2">
            <div class="bg-gradient-to-r from-[#1a3d34] to-[#2d5548] px-5 py-3">
                <p class="text-[11px] font-black text-white uppercase tracking-widest">Section 5 – Recommendations &amp; Decisions</p>
            </div>

            <div class="p-6 space-y-8">

                @php
                $sec5Blocks = [
                    [
                        'key'   => 'manager',
                        'label' => 'A) Promotability and Other Remarks and Recommendations by the Appraiser (Manager)',
                        'bold'  => true,
                    ],
                    [
                        'key'   => 'vp',
                        'label' => 'B) Remarks and/or Recommendations by VP',
                        'bold'  => true,
                    ],
                    [
                        'key'   => 'slt',
                        'label' => 'C) Remarks by SLT',
                        'bold'  => false,
                    ],
                ];
                @endphp

                @foreach($sec5Blocks as $i => $block)

                @if($i > 0)
                <div class="border-t border-[#6B9080]/15"></div>
                @endif

                <div>
                    <p class="text-xs font-black text-slate-700 uppercase tracking-wide mb-3">
                        {{ $block['label'] }}
                    </p>

                    {{-- Remarks textarea --}}
                    <textarea rows="5" placeholder="Write remarks here…"
                              class="w-full border border-[#6B9080]/40 rounded-xl px-4 py-3 text-sm text-slate-700 bg-white focus:outline-none focus:border-[#6B9080] transition resize-none mb-4"></textarea>

                    {{-- Checkboxes + Signature --}}
                    <div class="flex items-end justify-between gap-4 flex-wrap">

                        {{-- Checkboxes --}}
                        <div class="flex flex-col gap-2">
                            @foreach(['Confirmation', 'Salary Review', 'Promotion'] as $opt)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox"
                                       name="{{ $block['key'] }}_decision[]"
                                       value="{{ $opt }}"
                                       class="w-4 h-4 rounded border-[#6B9080] accent-[#6B9080] cursor-pointer">
                                <span class="text-xs font-black text-slate-700 uppercase tracking-wider group-hover:text-[#6B9080] transition">
                                    {{ $opt }}
                                </span>
                            </label>
                            @endforeach
                        </div>

                        {{-- Signature + Date --}}
                        <div class="flex flex-col items-end gap-3 min-w-[220px]">
                            <div class="w-full text-center">
                                <div class="h-12 border-b-2 border-[#6B9080]/40 border-dashed mb-1.5"></div>
                                <p class="text-[9px] font-black text-[#6B9080] uppercase tracking-widest">Signature</p>
                            </div>
                            <div class="flex items-center gap-2 w-full">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest shrink-0">Date</p>
                                <div class="flex-1 border-b border-[#6B9080]/40"></div>
                                <input type="date"
                                       class="border border-[#6B9080]/30 rounded-lg px-2 py-1 text-xs text-slate-600 bg-white focus:outline-none focus:border-[#6B9080] transition">
                            </div>
                        </div>
                    </div>
                </div>

                @endforeach

            </div>{{-- /p-6 --}}
        </div>{{-- /Section 5 --}}

    </div>{{-- /p-8 --}}
</div>{{-- /card --}}

</div>{{-- /px-4 --}}
</main>

<script>
const MAX_SCORE = {{ count($assessmentAreas) * 5 }};  // 12 × 5 = 60
const AREAS    = {{ count($assessmentAreas) }};

function scoreColor(total) {
    if (total === null) return 'text-2xl font-black text-slate-300';
    const pct = total / MAX_SCORE * 100;
    if (pct >= 80) return 'text-2xl font-black text-emerald-600';
    if (pct >= 60) return 'text-2xl font-black text-[#6B9080]';
    if (pct >= 40) return 'text-2xl font-black text-amber-500';
    return 'text-2xl font-black text-red-500';
}

function recalc(prefix, displayId, pctId) {
    const checked = document.querySelectorAll(`input[type=radio][name^="${prefix}_area_"]:checked`);
    const total   = checked.length === AREAS
        ? [...checked].reduce((s, r) => s + parseInt(r.value), 0)
        : null;
    const el  = document.getElementById(displayId);
    const pel = document.getElementById(pctId);
    el.textContent = total !== null ? total : '—';
    el.className   = scoreColor(total);
    pel.textContent = total !== null
        ? `${(total / MAX_SCORE * 25).toFixed(1)} / 25 pts`
        : `(${checked.length}/${AREAS} rated)`;
}

// Section 3 radio recalc
document.addEventListener('change', function (e) {
    if (!e.target.name) return;
    if (e.target.name.startsWith('self_area_'))     recalc('self',     'selfTotalDisplay',     'selfPctDisplay');
    if (e.target.name.startsWith('superior_area_')) recalc('superior', 'superiorTotalDisplay', 'superiorPctDisplay');
});

// Section 4 — sum Section 2 + Section 3 inputs into Rating row
function ratingColor(val) {
    if (val >= 90) return 'text-xl font-black text-emerald-600';
    if (val >= 70) return 'text-xl font-black text-[#6B9080]';
    if (val >= 50) return 'text-xl font-black text-amber-500';
    return 'text-xl font-black text-red-500';
}

function recalcRating() {
    const inputs = document.querySelectorAll('#section4Table input[type=number]');
    // inputs order: [s2-self, s2-appraiser, s3-self, s3-appraiser]
    const s2self  = parseFloat(inputs[0]?.value) || null;
    const s2app   = parseFloat(inputs[1]?.value) || null;
    const s3self  = parseFloat(inputs[2]?.value) || null;
    const s3app   = parseFloat(inputs[3]?.value) || null;

    const selfEl = document.getElementById('ratingTotalSelf');
    const appEl  = document.getElementById('ratingTotalAppraiser');

    if (s2self !== null && s3self !== null) {
        const total = s2self + s3self;
        selfEl.textContent = total.toFixed(1);
        selfEl.className   = ratingColor(total);
    } else {
        selfEl.textContent = '—';
        selfEl.className   = 'text-xl font-black text-slate-300';
    }

    if (s2app !== null && s3app !== null) {
        const total = s2app + s3app;
        appEl.textContent = total.toFixed(1);
        appEl.className   = ratingColor(total);
    } else {
        appEl.textContent = '—';
        appEl.className   = 'text-xl font-black text-slate-300';
    }
}

document.querySelectorAll('#section4Table input[type=number]').forEach(inp => {
    inp.addEventListener('input', recalcRating);
});
</script>
</body>
</html>
