<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Appraisal · {{ $qLabel }} · {{ $currentFinancialYear }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', sans-serif; }

        /* ── Document card ─────────────────────────────────── */
        .doc-card { box-shadow: 0 8px 40px rgba(15,23,42,.10); }

        /* ── Section header bar ───────────────────────────── */
        .sec-bar {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 24px;
            background: linear-gradient(90deg, #1a3d34, #2d5548);
        }
        .sec-num {
            width: 26px; height: 26px; border-radius: 50%;
            background: rgba(255,255,255,.18); border: 1.5px solid rgba(255,255,255,.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 900; color: #fff; flex-shrink: 0;
        }
        .sec-title { font-size: 11px; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: .12em; }

        /* ── Part label ───────────────────────────────────── */
        .part-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: .10em;
            color: #6B9080; margin-bottom: 14px;
        }
        .part-label::after { content: ''; flex: 1; height: 1px; background: rgba(107,144,128,.25); }

        /* ── Field ────────────────────────────────────────── */
        .f-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: #94a3b8; margin-bottom: 3px; }
        .f-val   { font-size: 13px; font-weight: 600; color: #1e293b; }

        .f-input {
            width: 100%; border: none; border-bottom: 1.5px solid rgba(107,144,128,.30);
            padding: 5px 0; font-size: 13px; font-weight: 500; color: #334155;
            background: transparent; outline: none; transition: border-color .15s;
        }
        .f-input:focus { border-bottom-color: #6B9080; }
        .f-input.ro    { color: #475569; cursor: default; }

        .f-box {
            width: 100%;
            border: 1.5px solid rgba(107,144,128,.30); border-radius: 10px;
            padding: 9px 14px; font-size: 13px; color: #334155;
            background: white; outline: none; transition: border-color .15s;
        }
        .f-box:focus { border-color: #6B9080; }
        .f-box.ro { background: #f8fafc; color: #475569; cursor: default; }

        /* ── Textarea ─────────────────────────────────────── */
        .f-area {
            width: 100%; min-height: 110px;
            border: 1.5px solid rgba(107,144,128,.30); border-radius: 10px;
            padding: 10px 14px; font-size: 12px; color: #334155;
            background: white; outline: none; resize: vertical; transition: border-color .15s;
            line-height: 1.6;
        }
        .f-area:focus { border-color: #6B9080; }

        /* ── Number input (small, centered) ──────────────── */
        .n-input {
            width: 60px; text-align: center;
            border: 1.5px solid rgba(107,144,128,.30); border-radius: 8px;
            padding: 4px 4px; font-size: 11px; font-weight: 600; color: #334155;
            background: white; outline: none; transition: border-color .15s;
        }
        .n-input:focus { border-color: #6B9080; }

        /* ── Inline text input (table) ────────────────────── */
        .t-input {
            width: 100%; border: none; border-bottom: 1px solid rgba(107,144,128,.25);
            padding: 4px 0; font-size: 11px; color: #334155;
            background: transparent; outline: none; transition: border-color .15s;
        }
        .t-input:focus { border-bottom-color: #6B9080; }
        .t-input::placeholder { color: #cbd5e1; }

        /* ── Table ────────────────────────────────────────── */
        .doc-tbl { width: 100%; font-size: 11px; border-collapse: collapse; }
        .doc-tbl th {
            background: #1a3d34; color: #fff; padding: 10px 12px;
            font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .09em;
        }
        .doc-tbl th.l { text-align: left; }
        .doc-tbl th.c { text-align: center; }
        .doc-tbl td { padding: 8px 12px; border-bottom: 1px solid rgba(107,144,128,.10); vertical-align: middle; }
        .doc-tbl tbody tr:last-child td { border-bottom: none; }

        /* ── OKR group header ─────────────────────────────── */
        .okr-hdr td { background: rgba(107,144,128,.10); padding: 10px 12px; border-bottom: 1px solid rgba(107,144,128,.2); }

        /* ── Score color ──────────────────────────────────── */
        .sc-great  { color: #059669; }
        .sc-good   { color: #6B9080; }
        .sc-warn   { color: #d97706; }
        .sc-poor   { color: #dc2626; }
        .sc-none   { color: #cbd5e1; }

        /* ── Sig line ─────────────────────────────────────── */
        .sig-line { border-bottom: 1.5px dashed rgba(107,144,128,.40); height: 44px; margin-bottom: 6px; }

        /* ── Print ────────────────────────────────────────── */
        @media print {
            #sidebar, #sidebarCloseBtn, .no-print { display: none !important; }
            #mainContent { margin-left: 0 !important; }
            .doc-card { box-shadow: none !important; }
            body { background: white !important; }
            .px-4 { padding-left: 0 !important; padding-right: 0 !important; }
        }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen">

{{-- ── Sticky page header ─────────────────────────────────────────────── --}}
<div class="sticky top-0 z-30 px-4 pt-4 pb-2 bg-[#f0f2f7] no-print">
    <div class="rounded-[18px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#2d5548] text-white px-6 py-4 shadow-xl flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center">
                <span class="text-sm font-black">{{ $qLabel }}</span>
            </div>
            <div>
                <h1 class="text-base font-black leading-tight">KPI Performance Appraisal</h1>
                <p class="text-white/65 text-[10px] mt-0.5">{{ $currentUserName }} · {{ $userPosition }} · {{ $departmentName }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($isWindowOpen)
            <span class="flex items-center gap-1.5 text-[10px] font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Window Open
            </span>
            @else
            <span class="flex items-center gap-1.5 text-[10px] font-bold bg-amber-500/15 text-amber-200 border border-amber-400/30 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> {{ $windowStart }} → {{ $windowEnd }}
            </span>
            @endif
            <button onclick="window.print()" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl font-bold text-xs transition border border-white/20 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / PDF
            </button>
        </div>
    </div>
</div>

<div class="px-4 pb-10 pt-3">
<div class="max-w-5xl mx-auto">

{{-- ── Document card ───────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl overflow-hidden doc-card border border-[#6B9080]/25">

    {{-- Accent strip --}}
    <div class="h-[3px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#A4C3B2]"></div>

    <div class="px-10 py-8">

        {{-- ── Doc header ──────────────────────────────────────────────── --}}
        <div class="flex items-start justify-between mb-7 pb-6 border-b border-slate-100">
            <div>
                @php $logoMap = ['RCG'=>'images/RCG-Logo.png','RGHB'=>'images/RGHB-Logo.png','RCT'=>'images/RCT-Logo.png']; $logo = $logoMap[session('company_code')] ?? null; @endphp
                @if($logo)
                <img src="{{ asset(ltrim($logo,'/')) }}" alt="Logo" class="h-10 object-contain mb-2">
                @else
                <p class="text-xl font-black text-[#1a3d34]">{{ session('company_display_name') }}</p>
                @endif
                <p class="text-[9px] text-slate-400 uppercase tracking-[.18em]">Accelerating Your Business Success</p>
            </div>
            <div class="flex flex-col items-end gap-1">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#1a3d34] to-[#6B9080] flex items-center justify-center shadow-lg">
                    <span class="text-2xl font-black text-white">{{ $qLabel }}</span>
                </div>
                <span class="text-[9px] font-bold text-[#6B9080] uppercase tracking-widest">{{ $currentFinancialYear }}</span>
            </div>
        </div>

        {{-- ── Document title ───────────────────────────────────────────── --}}
        <div class="text-center mb-7">
            <p class="text-[9px] font-semibold text-slate-400 uppercase tracking-[.22em] mb-3">— Private &amp; Confidential —</p>
            <h2 class="text-lg font-black text-[#1a3d34] uppercase tracking-[.06em] mb-1">
                Executive / Non-Executive Performance Appraisal
            </h2>
            <div class="flex items-center justify-center gap-2 mt-2">
                <span class="h-px w-12 bg-[#6B9080]/30"></span>
                <span class="text-[10px] font-semibold text-[#6B9080] uppercase tracking-widest">KPI · Quarter {{ $displayQuarter }} · {{ $currentFinancialYear }}</span>
                <span class="h-px w-12 bg-[#6B9080]/30"></span>
            </div>
        </div>

        {{-- ── Purpose of Review ────────────────────────────────────────── --}}
        <div class="border border-[#6B9080]/25 rounded-xl mb-6 overflow-hidden">
            <div class="bg-[#6B9080]/8 border-b border-[#6B9080]/20 px-5 py-2.5">
                <p class="text-[9px] font-black text-[#6B9080] uppercase tracking-[.14em]">Purpose of Review</p>
            </div>
            <div class="px-5 py-4 flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-5">
                    @foreach([
                        ['id'=>'por_confirmation',     'label'=>'Confirmation'],
                        ['id'=>'por_quarterly_review', 'label'=>'Quarterly Review'],
                        ['id'=>'por_others',           'label'=>'Others'],
                    ] as $opt)
                    <label class="flex items-center gap-2 cursor-pointer group select-none">
                        <span class="w-4 h-4 rounded border-2 border-[#6B9080]/50 flex items-center justify-center relative">
                            <input type="checkbox" id="{{ $opt['id'] }}" value="{{ $opt['label'] }}"
                                   {{ $opt['id'] === 'por_quarterly_review' ? 'checked' : '' }}
                                   class="sr-only peer">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#6B9080] hidden peer-checked:block"></span>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-[#6B9080] transition">{{ $opt['label'] }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="h-px flex-1 bg-slate-100 hidden md:block"></div>
                <div class="flex-1 min-w-48">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Please specify (if Others)</p>
                    <input type="text" placeholder="Describe purpose…" class="f-input" style="border-bottom-color: rgba(107,144,128,.3);">
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Year / Period</p>
                    <p class="text-base font-black text-[#1a3d34]">{{ now()->year }} <span class="text-[#6B9080]">/</span> {{ $qLabel }}</p>
                </div>
            </div>
        </div>

        {{-- ── Employee particulars strip ───────────────────────────────── --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-7">
            <table class="w-full text-xs">
                <tbody>
                    @php $fields = [
                        ['label'=>'Name',                     'value'=>$currentUserName],
                        ['label'=>'Current Position',          'value'=>$userPosition],
                        ['label'=>'Reporting To (Appraiser)',  'value'=>$reportsToName],
                        ['label'=>'Department / Division',     'value'=>$departmentName],
                        ['label'=>'Year / Period Under Review','value'=>$currentFinancialYear . ' / ' . $qLabel],
                    ]; @endphp
                    @foreach($fields as $i => $f)
                    <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} {{ $i < count($fields)-1 ? 'border-b border-[#6B9080]/12' : '' }}">
                        <td class="px-5 py-3 w-52 border-r border-[#6B9080]/12">
                            <span class="f-label">{{ $f['label'] }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="f-val">{{ $f['value'] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             SECTION 1
        ═══════════════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar">
                <div class="sec-num">1</div>
                <span class="sec-title">To Be Completed by Employee Under Review</span>
            </div>

            <div class="px-6 py-6 space-y-7">

                {{-- PART A --}}
                <div>
                    <div class="part-label">Part A &nbsp;·&nbsp; Employee's Particulars</div>

                    <div class="grid grid-cols-2 gap-x-10 gap-y-5">
                        {{-- Name --}}
                        <div>
                            <p class="f-label">Name</p>
                            <p class="f-val">{{ $currentUserName }}</p>
                            <div class="mt-1 h-px bg-slate-200"></div>
                        </div>
                        {{-- Start Date --}}
                        <div>
                            <p class="f-label">Start Date</p>
                            <input type="text"
                                   value="{{ isset($user['start_date']) ? \Carbon\Carbon::parse($user['start_date'])->format('d M Y') : (isset($user['created_at']) ? \Carbon\Carbon::parse($user['created_at'])->format('d M Y') : '') }}"
                                   placeholder="DD MMM YYYY" class="f-input">
                        </div>
                        {{-- Position --}}
                        <div>
                            <p class="f-label">Current Position</p>
                            <p class="f-val">{{ $userPosition }}</p>
                            <div class="mt-1 h-px bg-slate-200"></div>
                        </div>
                        {{-- Dept --}}
                        <div>
                            <p class="f-label">Department / Division</p>
                            <p class="f-val">{{ $departmentName }}</p>
                            <div class="mt-1 h-px bg-slate-200"></div>
                        </div>
                        {{-- Date Joined --}}
                        <div>
                            <p class="f-label">Date Joined</p>
                            <p class="f-val">{{ $joinDate }}</p>
                            <div class="mt-1 h-px bg-slate-200"></div>
                        </div>
                        {{-- Tenure --}}
                        <div>
                            <p class="f-label">Months / Years of Service</p>
                            <p class="f-val">{{ $tenure }}</p>
                            <div class="mt-1 h-px bg-slate-200"></div>
                        </div>
                    </div>

                    {{-- Leave / Lateness --}}
                    <div class="mt-5 grid grid-cols-3 gap-6">
                        @foreach([
                            ['label'=>'Medical Leave (Days)'],
                            ['label'=>'Emergency Leave (Days)'],
                            ['label'=>'No. of Lateness'],
                        ] as $lf)
                        <div>
                            <p class="f-label">{{ $lf['label'] }}</p>
                            <input type="number" min="0" placeholder="0" class="f-input" style="max-width:80px;">
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                {{-- PART B --}}
                <div>
                    <div class="part-label">Part B &nbsp;·&nbsp; Summary of Duties &amp; Achievements</div>
                    <p class="text-[11px] text-slate-400 italic mb-3">Summarize present duties and indicate if any key tasks set for the year / period have been achieved.</p>
                    <textarea class="f-area" placeholder="Write your summary here…" rows="5"></textarea>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                {{-- PART C --}}
                <div>
                    <div class="part-label">Part C &nbsp;·&nbsp; Key Tasks for Forthcoming Period</div>
                    <p class="text-[11px] text-slate-400 italic mb-3"><em>[Where applicable]</em> List what you see as your key tasks for the forthcoming year / period.</p>
                    <textarea class="f-area" placeholder="List your key tasks…" rows="5"></textarea>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                {{-- PART D --}}
                <div>
                    <div class="part-label">Part D &nbsp;·&nbsp; Appraiser Confirmation</div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
                        <p class="text-[11px] text-slate-500 italic leading-relaxed mb-5">
                            I hereby confirm that the above information provided by the appraisee is correct and that the appraisee has been directly reporting to me since
                            <input type="text" placeholder="DD MMM YYYY" class="f-input inline-block" style="width:130px; display:inline; border-bottom-color: rgba(107,144,128,.4);">.
                        </p>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="f-label">Appraiser Name</p>
                                <input type="text" value="{{ $reportsToName !== '-' ? $reportsToName : '' }}" placeholder="Full name" class="f-input">
                            </div>
                            <div>
                                <p class="f-label">Designation</p>
                                <input type="text" placeholder="Job title" class="f-input">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             SECTION 2
        ═══════════════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-7">
            <div class="sec-bar">
                <div class="sec-num">2</div>
                <span class="sec-title">OKR / KPI Quarterly Performance Review &nbsp;·&nbsp; {{ $qLabel }}</span>
            </div>

            @if(empty($kpis))
            <div class="p-10 text-center">
                <p class="text-slate-400 text-sm">No KPIs found for {{ $currentFinancialYear }}.</p>
            </div>
            @else
            <div class="overflow-x-auto">
            <table class="doc-tbl" id="sec2Table" style="min-width:760px;">
                <thead>
                    <tr>
                        <th class="c" style="width:36px;">No.</th>
                        <th class="l" style="width:160px;">OKR / KPI</th>
                        <th class="c" style="width:36px;">Sub</th>
                        <th class="l">Initiative</th>
                        <th class="c" style="width:68px;">A<br><span style="font-weight:500;text-transform:none;font-size:8px;">Actual</span></th>
                        <th class="c" style="width:68px;">B<br><span style="font-weight:500;text-transform:none;font-size:8px;">Target</span></th>
                        <th class="c" style="width:72px;">C · Score<br><span style="font-weight:400;text-transform:none;font-size:8px;">(A÷B)×5</span></th>
                        <th class="c" style="width:72px;">D · Self<br><span style="font-weight:400;text-transform:none;font-size:8px;">Pro-Rated</span></th>
                        <th class="c" style="width:72px;">Appraiser<br><span style="font-weight:400;text-transform:none;font-size:8px;">Score</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($kpis as $ki => $kpi)
                @php
                    $qs       = $quarterScores[$kpi['id']] ?? null;
                    $dbAct    = isset($qs['quarter_actual']) ? (float)$qs['quarter_actual'] : '';
                    $dbTgt    = isset($qs['quarter_target']) ? (float)$qs['quarter_target'] : (float)($kpi['base_target'] ?? '');
                    $kpiNo    = $ki + 1;
                @endphp
                {{-- OKR group row --}}
                <tr class="okr-hdr">
                    <td class="text-center font-black text-[#1a3d34] text-xs">{{ $kpiNo }}</td>
                    <td colspan="2">
                        <p class="text-[9px] font-black text-[#6B9080] uppercase tracking-wider">OKR / KPI</p>
                        <p class="text-xs font-bold text-slate-800 leading-snug mt-0.5">{{ $kpi['kpi_title'] }}</p>
                        @if(!empty($kpi['sub_category']))<p class="text-[9px] text-slate-400 mt-0.5">{{ $kpi['sub_category'] }}</p>@endif
                    </td>
                    <td colspan="6" class="text-right">
                        <span class="inline-block text-[9px] font-black text-[#6B9080] bg-white border border-[#6B9080]/30 px-2.5 py-1 rounded-full uppercase tracking-wider">
                            {{ $kpi['weightage'] ?? '—' }}% weight &nbsp;·&nbsp; {{ $kpi['category'] ?? '' }}
                        </span>
                    </td>
                </tr>
                {{-- 4 initiative rows --}}
                @for($s = 1; $s <= 4; $s++)
                <tr class="{{ $s % 2 === 0 ? '' : 'bg-slate-50/40' }} sec2-row" data-kpi="{{ $kpiNo }}">
                    <td></td>
                    <td></td>
                    <td class="text-center text-[10px] font-bold text-slate-400">{{ $kpiNo }}.{{ $s }}</td>
                    <td><input type="text" placeholder="Describe initiative {{ $kpiNo }}.{{ $s }}…" class="t-input"></td>
                    <td class="text-center"><input type="number" step="any" min="0" value="{{ $s===1 && $dbAct!=='' ? $dbAct : '' }}" placeholder="—" class="n-input sec2-actual"></td>
                    <td class="text-center"><input type="number" step="any" min="0" value="{{ $s===1 && $dbTgt!=='' ? $dbTgt : '' }}" placeholder="—" class="n-input sec2-target"></td>
                    <td class="text-center"><span class="sec2-score font-black text-sm sc-none">—</span></td>
                    <td class="text-center"><input type="number" step="0.1" min="0" max="5" placeholder="—" class="n-input"></td>
                    <td class="text-center"><input type="number" step="0.1" min="0" max="5" placeholder="—" class="n-input"></td>
                </tr>
                @endfor
                @endforeach

                {{-- Total row --}}
                <tr style="background:linear-gradient(90deg,#1a3d34,#2d5548);">
                    <td colspan="6" class="text-right py-3 px-4">
                        <span style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.8);">Total Score Section 2</span>
                    </td>
                    <td class="text-center py-3"><span id="s2Total" class="text-base font-black text-white">—</span></td>
                    <td class="text-center py-3"><span id="s2SelfTotal" class="text-base font-black text-white">—</span></td>
                    <td class="text-center py-3"><span id="s2AppTotal" class="text-base font-black text-white">—</span></td>
                </tr>
                {{-- % row --}}
                <tr style="background:rgba(107,144,128,.06);">
                    <td colspan="6" class="text-right py-3 px-4">
                        <span style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.10em;color:#6B9080;">% Total (Score ÷ {{ count($kpis)*5 }} × 70)</span>
                    </td>
                    <td class="text-center py-3"><span id="s2Pct" class="text-sm font-black sc-none">—</span></td>
                    <td colspan="2" class="py-2 px-3">
                        <p style="font-size:9px;color:#94a3b8;font-style:italic;line-height:1.5;">
                            e.g. A (actual)=60, B (target)=100<br>C = (60÷100)×5 = <strong>3.00</strong><br>% = Total ÷ {{ count($kpis)*5 }} × 70
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
            </div>
            @endif
        </div>

        {{-- ── Signatures ───────────────────────────────────────────────── --}}
        <div class="grid grid-cols-3 gap-8 pt-7 border-t border-slate-100">
            @foreach([
                ['role'=>'Employee',     'name'=>$currentUserName],
                ['role'=>'Reporting To', 'name'=>$reportsToName],
                ['role'=>'HR Verified',  'name'=>''],
            ] as $sig)
            <div class="text-center">
                <div class="sig-line mx-4"></div>
                <p class="text-xs font-bold text-slate-700 mt-1">{{ $sig['name'] ?: '_______________' }}</p>
                <p class="text-[9px] font-black text-[#6B9080] uppercase tracking-widest mt-1">{{ $sig['role'] }}</p>
                <p class="text-[9px] text-slate-400 mt-2">Date: _______________</p>
            </div>
            @endforeach
        </div>

    </div>{{-- /px-10 py-8 --}}
</div>{{-- /doc-card --}}

</div>
</div>{{-- /px-4 --}}
</main>

<script>
(function () {
    const KPI_COUNT = {{ count($kpis) }};
    const MAX = KPI_COUNT * 5;

    function cls(v) {
        if (v === null) return 'sec2-score font-black text-sm sc-none';
        if (v >= 4)    return 'sec2-score font-black text-sm sc-great';
        if (v >= 3)    return 'sec2-score font-black text-sm sc-good';
        if (v >= 2)    return 'sec2-score font-black text-sm sc-warn';
        return 'sec2-score font-black text-sm sc-poor';
    }
    function pCls(v) {
        if (v >= 56) return 'text-sm font-black sc-great';
        if (v >= 42) return 'text-sm font-black sc-good';
        if (v >= 28) return 'text-sm font-black sc-warn';
        return 'text-sm font-black sc-poor';
    }

    function recalc() {
        const rows = document.querySelectorAll('#sec2Table tr.sec2-row');
        let totC = 0, cntC = 0, totS = 0, cntS = 0, totA = 0, cntA = 0;

        rows.forEach(row => {
            const a  = parseFloat(row.querySelector('.sec2-actual')?.value);
            const b  = parseFloat(row.querySelector('.sec2-target')?.value);
            const el = row.querySelector('.sec2-score');
            if (!isNaN(a) && !isNaN(b) && b > 0 && el) {
                const c = Math.min((a / b) * 5, 5);
                el.textContent = c.toFixed(2); el.className = cls(c);
                totC += c; cntC++;
            } else if (el) { el.textContent = '—'; el.className = cls(null); }

            const ni = row.querySelectorAll('.n-input');
            const sv = parseFloat(ni[2]?.value), av = parseFloat(ni[3]?.value);
            if (!isNaN(sv)) { totS += sv; cntS++; }
            if (!isNaN(av)) { totA += av; cntA++; }
        });

        document.getElementById('s2Total').textContent     = cntC ? totC.toFixed(2) : '—';
        document.getElementById('s2SelfTotal').textContent = cntS ? totS.toFixed(2) : '—';
        document.getElementById('s2AppTotal').textContent  = cntA ? totA.toFixed(2) : '—';

        const pEl = document.getElementById('s2Pct');
        if (cntC) {
            const p = (totC / MAX * 70).toFixed(1);
            pEl.textContent = p + '%';
            pEl.className = pCls(parseFloat(p));
        } else { pEl.textContent = '—'; pEl.className = 'text-sm font-black sc-none'; }
    }

    document.getElementById('sec2Table')?.addEventListener('input', recalc);
    recalc();
})();
</script>
</body>
</html>
