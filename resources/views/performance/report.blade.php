<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Evaluation Report · {{ $qLabel }} · {{ $currentFinancialYear }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', sans-serif; }

        .doc-card { box-shadow: 0 8px 40px rgba(15,23,42,.10); }

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

        .part-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: .10em;
            color: #6B9080; margin-bottom: 14px;
        }
        .part-label::after { content: ''; flex: 1; height: 1px; background: rgba(107,144,128,.25); }

        .f-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: #94a3b8; margin-bottom: 3px; }
        .f-val   { font-size: 13px; font-weight: 600; color: #1e293b; text-transform: uppercase; }

        /* Part D — optional date with calendar icon */
        .partd-wrap { position: relative; display: inline-flex; align-items: center; gap: 2px; vertical-align: middle; }
        .partd-cal  { color: #6B9080; cursor: pointer; display: inline-flex; align-items: center; padding: 2px 4px; border-radius: 4px; transition: color .15s, background .15s; }
        .partd-cal:hover { color: #4a7c6b; background: rgba(107,144,128,.15); }
        .partd-val  { font-size: 11px; font-weight: 600; color: #1e293b; display: none; border-bottom: 1.5px solid rgba(107,144,128,.40); padding-bottom: 1px; }
        .partd-clr  { display: none; font-size: 14px; line-height: 1; color: #94a3b8; cursor: pointer; background: none; border: none; padding: 0 2px; vertical-align: middle; }
        .partd-clr:hover { color: #ef4444; }

        .f-input {
            width: 100%; border: none; border-bottom: 1.5px solid rgba(107,144,128,.30);
            padding: 5px 0; font-size: 13px; font-weight: 600; color: #1e293b;
            background: transparent; outline: none; transition: border-color .15s;
            text-transform: uppercase;
        }
        .f-input:focus { border-bottom-color: #6B9080; }

        .f-area {
            width: 100%; min-height: 90px;
            border: 1.5px solid rgba(107,144,128,.30); border-radius: 10px;
            padding: 10px 14px; font-size: 12px; color: #334155;
            background: white; outline: none; resize: vertical; transition: border-color .15s;
            line-height: 1.6;
        }
        .f-area:focus { border-color: #6B9080; }

        .n-input {
            width: 60px; text-align: center;
            border: 1.5px solid rgba(107,144,128,.30); border-radius: 8px;
            padding: 4px; font-size: 11px; font-weight: 600; color: #334155;
            background: white; outline: none; transition: border-color .15s;
        }
        .n-input:focus { border-color: #6B9080; }

        .t-input {
            width: 100%; border: none; border-bottom: 1px solid rgba(107,144,128,.25);
            padding: 4px 0; font-size: 11px; color: #334155;
            background: transparent; outline: none; transition: border-color .15s;
        }
        .t-input:focus { border-bottom-color: #6B9080; }
        .t-input::placeholder { color: #cbd5e1; }

        .doc-tbl { width: 100%; font-size: 11px; border-collapse: collapse; }
        .doc-tbl th {
            background: #1a3d34; color: #fff; padding: 10px 12px;
            font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .09em;
        }
        .doc-tbl th.l { text-align: left; }
        .doc-tbl th.c { text-align: center; }
        .doc-tbl td { padding: 8px 12px; border-bottom: 1px solid rgba(107,144,128,.10); vertical-align: middle; }
        .doc-tbl tbody tr:last-child td { border-bottom: none; }

        .cat-hdr td    { background: #1a3d34; color: #fff; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .14em; padding: 8px 16px; }
        .subcat-hdr td { background: rgba(107,144,128,.08); color: #2d5548; font-size: 10px; font-weight: 700; padding: 7px 16px 7px 22px; border-bottom: 1px solid rgba(107,144,128,.18); letter-spacing: .03em; }
        .q-tag { display:inline-block; font-size:8px; font-weight:900; color:#1a3d34; background:rgba(107,144,128,.15); border:1px solid rgba(107,144,128,.35); border-radius:4px; padding:1px 6px; letter-spacing:.06em; text-transform:uppercase; vertical-align:middle; flex-shrink:0; }

        .sc-great { color: #059669; }
        .sc-good  { color: #6B9080; }
        .sc-warn  { color: #d97706; }
        .sc-poor  { color: #dc2626; }
        .sc-none  { color: #cbd5e1; }

        .sig-line { border-bottom: 1.5px dashed rgba(107,144,128,.40); height: 44px; margin-bottom: 6px; }

        /* Rating pills */
        .rating-group { display: flex; gap: 4px; }
        .rating-group input[type=radio] { display: none; }
        .rating-group label {
            width: 28px; height: 28px; border-radius: 50%;
            border: 1.5px solid rgba(107,144,128,.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #64748b;
            cursor: pointer; transition: all .15s;
        }
        .rating-group input[type=radio]:checked + label {
            background: #1a3d34; border-color: #1a3d34; color: #fff;
        }
        .rating-group label:hover { border-color: #6B9080; color: #6B9080; }

        /* ── Print table header (hidden on screen) ─────────── */
        #print-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        #print-thead { display: none; }

        /* ── Print ─────────────────────────────────────────── */
        @media print {
            .partd-cal, .partd-clr { display: none !important; }

            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            @page { size: A4 portrait; margin: 10mm 10mm 12mm; }

            #print-table { width: 100% !important; border-collapse: collapse !important; }
            #print-thead { display: table-header-group !important; }
            #print-thead td { padding: 4mm 0 3mm; background: white !important; }
            #doc-hdr { display: none !important; }

            #sidebar, #sidebarCloseBtn, .no-print, .sticky { display: none !important; }
            #mainContent { margin-left: 0 !important; }
            body { background: #f0f2f7 !important; }
            .px-4 { padding-left: 4px !important; padding-right: 4px !important; }
            .pt-3 { padding-top: 0 !important; }
            .pb-10 { padding-bottom: 0 !important; }

            .doc-card { box-shadow: none !important; border: 1px solid #6B9080 !important; border-radius: 12px !important; }
            .sec-bar { background: linear-gradient(90deg, #1a3d34, #2d5548) !important; }
            .doc-tbl th { background: #1a3d34 !important; color: #fff !important; }
            .part-label { color: #6B9080 !important; }
            .h-\[3px\] { background: linear-gradient(to right, #1a3d34, #6B9080, #A4C3B2) !important; }
            .cat-hdr td    { background: #1a3d34 !important; color: #fff !important; }
            .subcat-hdr td { background: rgba(107,144,128,.08) !important; }

            .rating-group input[type=radio]:checked + label {
                background: #1a3d34 !important; border-color: #1a3d34 !important; color: #fff !important;
            }

            .border.border-\[#6B9080\]\/25.rounded-xl { page-break-inside: avoid; }
            tr, p { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen">

{{-- Sticky header --}}
<div class="sticky top-0 z-30 px-4 pt-4 pb-2 bg-[#f0f2f7] no-print">
    <div class="rounded-[18px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#2d5548] text-white px-6 py-4 shadow-xl flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center">
                <span class="text-sm font-black">{{ $qLabel }}</span>
            </div>
            <div>
                <h1 class="text-base font-black leading-tight">Complete Performance Evaluation Report</h1>
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

{{-- Print table: thead repeats logo+Q2 on every page --}}
@php $phLogoMap=['RCG'=>'images/RCG-Logo-black.png','RGHB'=>'images/RGHB-Logo.png','RCT'=>'images/RCT-Logo.png']; $phLogo=$phLogoMap[session('company_code')]??null; @endphp
<table id="print-table">
<thead id="print-thead">
<tr><td>
    <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
        @if($phLogo)<img src="{{ asset($phLogo) }}" alt="Logo" style="height:40px;object-fit:contain;display:block">
        @else<span style="font-size:12px;font-weight:900;color:#1a3d34">{{ session('company_display_name') }}</span>@endif
        <div style="display:flex;flex-direction:column;align-items:center;gap:2px">
            <div style="width:40px;height:40px;border-radius:9px;background:linear-gradient(135deg,#1a3d34,#6B9080);display:flex;align-items:center;justify-content:center">
                <span style="font-size:14px;font-weight:900;color:white;line-height:1">{{ $qLabel }}</span>
            </div>
            <span style="font-size:7px;font-weight:700;color:#6B9080;letter-spacing:.12em;text-transform:uppercase">{{ $currentFinancialYear }}</span>
        </div>
    </div>
</td></tr>
</thead>
<tbody>
<tr><td style="padding:0">

<div class="px-4 pb-10 pt-3">
<div class="max-w-5xl mx-auto">

<div class="bg-white rounded-2xl overflow-hidden doc-card border border-[#6B9080]/25">

    <div class="h-[3px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#A4C3B2]"></div>

    <div class="px-10 py-8">

        {{-- Doc header (hidden in print — replaced by print-thead) --}}
        <div id="doc-hdr" class="flex items-center justify-between mb-7 pb-6 border-b border-slate-100">
            @php $logoMap=['RCG'=>'images/RCG-Logo-black.png','RGHB'=>'images/RGHB-Logo.png','RCT'=>'images/RCT-Logo.png']; $logo=$logoMap[session('company_code')]??null; @endphp
            @if($logo)<img src="{{ asset(ltrim($logo,'/')) }}" alt="Logo" class="h-12 object-contain">
            @else<p class="text-xl font-black text-[#1a3d34]">{{ session('company_display_name') }}</p>@endif
            <div class="flex flex-col items-center gap-0.5">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#1a3d34] to-[#6B9080] flex items-center justify-center shadow-lg">
                    <span class="text-xl font-black text-white">{{ $qLabel }}</span>
                </div>
                <span class="text-[9px] font-bold text-[#6B9080] uppercase tracking-widest">{{ $currentFinancialYear }}</span>
            </div>
        </div>

        {{-- Title --}}
        <div class="text-center mb-7">
            <p class="text-[9px] font-semibold text-slate-400 uppercase tracking-[.22em] mb-3">— Private &amp; Confidential —</p>
            <h2 class="text-lg font-black text-[#1a3d34] uppercase tracking-[.06em] mb-1">Executive / Non-Executive Performance Appraisal</h2>
            <div class="flex items-center justify-center gap-2 mt-2">
                <span class="h-px w-12 bg-[#6B9080]/30"></span>
                <span class="text-[10px] font-semibold text-[#6B9080] uppercase tracking-widest">Complete Report · Quarter {{ $displayQuarter }} · {{ $currentFinancialYear }}</span>
                <span class="h-px w-12 bg-[#6B9080]/30"></span>
            </div>
        </div>

        {{-- Purpose of Review --}}
        <div class="border border-[#6B9080]/25 rounded-xl mb-6 overflow-hidden">
            <div class="bg-[#6B9080]/8 border-b border-[#6B9080]/20 px-5 py-2.5">
                <p class="text-[9px] font-black text-[#6B9080] uppercase tracking-[.14em]">Purpose of Review</p>
            </div>
            <div class="px-5 py-4 flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-5">
                    @foreach([['id'=>'por_confirmation','label'=>'Confirmation'],['id'=>'por_quarterly','label'=>'Quarterly Review'],['id'=>'por_others','label'=>'Others']] as $opt)
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <span class="w-4 h-4 rounded border-2 border-[#6B9080]/50 flex items-center justify-center"><input type="checkbox" {{ $opt['id']==='por_quarterly'?'checked':'' }} class="sr-only peer"><span class="w-2.5 h-2.5 rounded-sm bg-[#6B9080] hidden peer-checked:block"></span></span>
                        <span class="text-[11px] font-semibold text-slate-700">{{ $opt['label'] }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="flex-1 min-w-48">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Please specify (if Others)</p>
                    <input type="text" placeholder="Describe purpose…" class="f-input">
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Year / Period</p>
                    <p class="text-base font-black text-[#1a3d34]">{{ now()->year }} <span class="text-[#6B9080]">/</span> {{ $qLabel }}</p>
                </div>
            </div>
        </div>

        {{-- Employee strip --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-7">
            <table class="w-full text-xs">
                <tbody>
                @php $fields=[['label'=>'Name','value'=>$currentUserName],['label'=>'Current Position','value'=>$userPosition],['label'=>'Reporting To (Appraiser)','value'=>$reportsToName],['label'=>'Department / Division','value'=>$departmentName],['label'=>'Year / Period Under Review','value'=>$currentFinancialYear.' / '.$qLabel]]; @endphp
                @foreach($fields as $i => $f)
                <tr class="{{ $i%2===0?'bg-white':'bg-slate-50/60' }} {{ $i<count($fields)-1?'border-b border-[#6B9080]/12':'' }}">
                    <td class="px-5 py-3 w-52 border-r border-[#6B9080]/12"><span class="f-label">{{ $f['label'] }}</span></td>
                    <td class="px-5 py-3"><span class="f-val">{{ $f['value'] }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 1 — EMPLOYEE PARTICULARS
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar"><div class="sec-num">1</div><span class="sec-title">To Be Completed by Employee Under Review</span></div>
            <div class="px-6 py-6 space-y-7">

                <div>
                    <div class="part-label">Part A &nbsp;·&nbsp; Employee's Particulars</div>
                    <div class="grid grid-cols-2 gap-x-10 gap-y-5">
                        <div><p class="f-label">Name</p><p class="f-val">{{ $currentUserName }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                        <div><p class="f-label">Start Date</p><input type="text" value="{{ $joinDate !== '—' ? $joinDate : '' }}" placeholder="DD MMM YYYY" class="f-input"></div>
                        <div><p class="f-label">Current Position</p><p class="f-val">{{ $userPosition }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                        <div><p class="f-label">Department / Division</p><p class="f-val">{{ $departmentName }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                        <div><p class="f-label">Date Joined</p><p class="f-val">{{ $joinDate }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                        <div><p class="f-label">Months / Years of Service</p><p class="f-val">{{ $tenure }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-6">
                        @php
                            $partAFields = [
                                'Medical Leave (Days)'   => $attendanceYTD['mc_days'],
                                'Emergency Leave (Days)' => $attendanceYTD['other_leave_days'],
                                'No. of Lateness'        => $attendanceYTD['late_count'],
                            ];
                        @endphp
                        @foreach($partAFields as $lf => $lv)
                        <div>
                            <p class="f-label">{{ $lf }}</p>
                            <p class="f-val" style="padding:5px 0;border-bottom:1.5px solid rgba(107,144,128,.30);max-width:80px;">{{ $attendanceYTD['has_data'] ? $lv : '0' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <div class="part-label">Part B &nbsp;·&nbsp; Summary of Duties &amp; Achievements</div>
                    <p class="text-[11px] text-slate-400 italic mb-3">Summarize present duties and indicate if any key tasks set for the year / period have been achieved.</p>
                    <textarea class="f-area" placeholder="Write your summary here…" rows="4"></textarea>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <div class="part-label">Part C &nbsp;·&nbsp; Key Tasks for Forthcoming Period</div>
                    <p class="text-[11px] text-slate-400 italic mb-3"><em>[Where applicable]</em> List what you see as your key tasks for the forthcoming year / period.</p>
                    <textarea class="f-area" placeholder="List your key tasks…" rows="4"></textarea>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <div class="part-label">Part D &nbsp;·&nbsp; Appraiser Confirmation</div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
                        <p class="text-[11px] text-slate-500 italic leading-relaxed mb-4">
                            I hereby confirm that the above information provided by the appraisee is correct and that the appraisee has been directly reporting to me since
                            <span class="partd-wrap mx-1">
                                <span id="partd-val" class="partd-val"></span>
                                <span id="partd-cal" class="partd-cal" onclick="openPartDPicker()" title="Select date">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </span>
                                <button type="button" id="partd-clr" class="partd-clr" onclick="clearPartDDate()" title="Clear date">×</button>
                                <input type="date" id="partd-date" style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;" onchange="setPartDDate(this.value)">
                            </span>.
                        </p>
                        <div class="grid grid-cols-2 gap-6">
                            <div><p class="f-label">Appraiser Name</p><input type="text" value="{{ $reportsToName !== '-' ? $reportsToName : '' }}" placeholder="Full name" class="f-input"></div>
                            <div><p class="f-label">Designation</p><input type="text" value="{{ $reportsToPosition !== '-' ? $reportsToPosition : '' }}" placeholder="Job title" class="f-input"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 2 — OKR / KPI
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar"><div class="sec-num">2</div><span class="sec-title">OKR / KPI Quarterly Performance Review &nbsp;·&nbsp; {{ $qLabel }}</span></div>

            @if(empty($kpis))
            <div class="p-10 text-center"><p class="text-slate-400 text-sm">No KPIs found for {{ $currentFinancialYear }}.</p></div>
            @else
            <div class="overflow-x-auto">
            <table class="doc-tbl" id="sec2Table" style="min-width:700px;">
                <thead>
                    <tr>
                        <th class="c" style="width:44px;">No.</th>
                        <th class="l">Initiative</th>
                        <th class="c" style="width:68px;">A<br><span style="font-weight:500;text-transform:none;font-size:8px;">Actual</span></th>
                        <th class="c" style="width:68px;">B<br><span style="font-weight:500;text-transform:none;font-size:8px;">Target</span></th>
                        <th class="c" style="width:72px;">C · Score<br><span style="font-weight:400;text-transform:none;font-size:8px;">(A÷B)×5</span></th>
                        <th class="c" style="width:72px;">D · Self<br><span style="font-weight:400;text-transform:none;font-size:8px;">Pro-Rated</span></th>
                        <th class="c" style="width:72px;">Appraiser<br><span style="font-weight:400;text-transform:none;font-size:8px;">Score</span></th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $sec2Grouped = [];
                    foreach ($kpis as $kpi) {
                        $cat = $kpi['category'] ?? 'Uncategorized';
                        $sub = $kpi['sub_category'] ?? 'General';
                        $sec2Grouped[$cat][$sub][] = $kpi;
                    }
                    $subCatNo = 0;
                @endphp
                @foreach($sec2Grouped as $catName => $subCats)
                <tr class="cat-hdr"><td colspan="7">{{ $catName }}</td></tr>
                @foreach($subCats as $subName => $subKpis)
                @php $subCatNo++; $subItemNo = 0; @endphp
                <tr class="subcat-hdr"><td colspan="7">{{ $subName }}</td></tr>
                @foreach($subKpis as $kpi)
                @php
                    $subItemNo++;
                    $qData  = ($allQuarters[$kpi['id']] ?? [])[$qLabel] ?? null;
                    $qTitle = $qData['quarter_title'] ?? '';
                    $qAct   = isset($qData['quarter_actual']) ? (float)$qData['quarter_actual'] : '';
                    $qTgt   = isset($qData['quarter_target']) ? (float)$qData['quarter_target'] : (float)($kpi['base_target'] ?? '');
                @endphp
                <tr class="{{ $subItemNo%2===0?'':'bg-slate-50/40' }} sec2-row">
                    <td class="text-center text-[10px] font-bold text-[#1a3d34]">{{ $subCatNo }}.{{ $subItemNo }}</td>
                    <td style="padding:8px 12px;">
                        <div style="font-size:10px;font-weight:700;color:#1a3d34;margin-bottom:3px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <span>{{ $kpi['kpi_title'] }}</span>
                            <span style="flex-shrink:0;font-size:8px;font-weight:900;color:#6B9080;background:#fff;border:1px solid rgba(107,144,128,.25);padding:2px 7px;border-radius:999px;text-transform:uppercase;letter-spacing:.08em;white-space:nowrap;">{{ $kpi['weightage']??'—' }}% weight</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span class="q-tag">{{ $qLabel }}</span>
                            <input type="text" value="{{ $qTitle }}" placeholder="Describe initiative for {{ $qLabel }}…" class="t-input">
                        </div>
                    </td>
                    <td class="text-center"><input type="number" step="any" min="0" value="{{ $qAct !== '' ? $qAct : '' }}" placeholder="—" class="n-input sec2-actual"></td>
                    <td class="text-center"><input type="number" step="any" min="0" value="{{ $qTgt !== '' ? $qTgt : '' }}" placeholder="—" class="n-input sec2-target"></td>
                    <td class="text-center"><span class="sec2-score font-black text-sm sc-none">—</span></td>
                    <td class="text-center"><input type="number" step="0.1" min="0" max="5" placeholder="—" class="n-input"></td>
                    <td class="text-center"><input type="number" step="0.1" min="0" max="5" placeholder="—" class="n-input"></td>
                </tr>
                @endforeach
                @endforeach
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:rgba(26,61,52,.06);">
                        <td colspan="4" class="text-right font-black text-xs text-[#1a3d34] uppercase tracking-wide px-4 py-3">Total Score Section 2</td>
                        <td class="text-center py-3"><span id="sec2Total" class="font-black text-base sc-none">—</span></td>
                        <td class="text-center"><span id="sec2SelfPct" class="text-xs font-bold text-slate-400">—</span></td>
                        <td class="text-center"><span id="sec2AppPct" class="text-xs font-bold text-slate-400">—</span></td>
                    </tr>
                    <tr style="background:rgba(26,61,52,.03);">
                        <td colspan="4" class="text-right text-[9px] font-bold text-slate-400 uppercase tracking-wide px-4 py-2">% Total (Score ÷ 30 × 70)</td>
                        <td colspan="3" class="text-center"><span id="sec2Pct" class="text-sm font-black text-slate-400">—</span></td>
                    </tr>
                </tfoot>
            </table>
            </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 3 — ATTITUDE & COMPETENCY
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar"><div class="sec-num">3</div><span class="sec-title">Attitude &amp; Competency Assessment</span></div>

            <div class="px-6 pt-5 pb-4 border-b border-slate-100">
                <p class="text-[9px] font-black text-[#6B9080] uppercase tracking-widest mb-3">Rating Scale</p>
                <div class="flex gap-2 flex-wrap">
                    @php $ratingLegend=[
                        ['score'=>5,'cat'=>'Outstanding',   'def'=>'Exceptional performance, high initiative, sound judgement.',          'bg'=>'bg-emerald-50','border'=>'border-emerald-200','text'=>'text-emerald-700','num'=>'bg-emerald-500'],
                        ['score'=>4,'cat'=>'Above Average', 'def'=>'Consistently meets all requirements, exceeds in major aspects.',      'bg'=>'bg-[#6B9080]/5','border'=>'border-[#6B9080]/25','text'=>'text-[#1a3d34]','num'=>'bg-[#6B9080]'],
                        ['score'=>3,'cat'=>'Average',       'def'=>'Meets the normal requirements of the position.',                      'bg'=>'bg-slate-50','border'=>'border-slate-200','text'=>'text-slate-600','num'=>'bg-slate-400'],
                        ['score'=>2,'cat'=>'Below Average', 'def'=>'Below expectations, requires improvement and remedial steps.',        'bg'=>'bg-amber-50','border'=>'border-amber-200','text'=>'text-amber-700','num'=>'bg-amber-400'],
                        ['score'=>1,'cat'=>'Unsatisfactory','def'=>'Inadequate; counselling or appropriate action required.',             'bg'=>'bg-red-50','border'=>'border-red-200','text'=>'text-red-700','num'=>'bg-red-500'],
                    ]; @endphp
                    @foreach($ratingLegend as $r)
                    <div class="flex-1 min-w-36 {{ $r['bg'] }} border {{ $r['border'] }} rounded-xl p-3">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-6 h-6 {{ $r['num'] }} rounded-lg flex items-center justify-center text-white text-xs font-black flex-shrink-0">{{ $r['score'] }}</span>
                            <span class="text-[10px] font-black {{ $r['text'] }} uppercase tracking-wide">{{ $r['cat'] }}</span>
                        </div>
                        <p class="text-[9px] text-slate-400 leading-relaxed">{{ $r['def'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
            <table class="doc-tbl" style="min-width:680px;">
                <thead>
                    <tr>
                        <th class="l" style="width:40%;">Area / Assessment</th>
                        <th class="c" style="width:140px;">Self Rating<br><span style="font-weight:400;text-transform:none;font-size:8px;">Pick 1 – 5</span></th>
                        <th class="c" style="width:140px;">Superior Rating<br><span style="font-weight:400;text-transform:none;font-size:8px;">Pick 1 – 5</span></th>
                        <th class="l">Appraiser's Comment</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($assessmentAreas as $i => $area)
                <tr class="{{ $i%2===0?'bg-white':'bg-slate-50/50' }}" style="border-bottom:1px solid rgba(107,144,128,.10);">
                    <td style="padding:14px 16px;">
                        <p style="font-size:11px;font-weight:800;color:#1e293b;margin-bottom:3px;">{{ $area['no'] }}) {{ $area['title'] }}</p>
                        <p style="font-size:10px;color:#94a3b8;line-height:1.55;font-style:italic;">{{ $area['description'] }}</p>
                    </td>
                    <td style="padding:10px 8px;vertical-align:top;" class="text-center">
                        <p style="font-size:8px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Self</p>
                        <div class="rating-group justify-center">
                            @foreach([1,2,3,4,5] as $sc)<input type="radio" id="s_{{ $area['no'] }}_{{ $sc }}" name="self_{{ $area['no'] }}" value="{{ $sc }}"><label for="s_{{ $area['no'] }}_{{ $sc }}">{{ $sc }}</label>@endforeach
                        </div>
                    </td>
                    <td style="padding:10px 8px;vertical-align:top;" class="text-center">
                        <p style="font-size:8px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Superior</p>
                        <div class="rating-group justify-center">
                            @foreach([1,2,3,4,5] as $sc)<input type="radio" id="sup_{{ $area['no'] }}_{{ $sc }}" name="sup_{{ $area['no'] }}" value="{{ $sc }}"><label for="sup_{{ $area['no'] }}_{{ $sc }}">{{ $sc }}</label>@endforeach
                        </div>
                    </td>
                    <td style="padding:10px 12px;vertical-align:top;"><input type="text" placeholder="Comment…" class="t-input" style="margin-top:6px;"></td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:rgba(26,61,52,.06);">
                        <td style="padding:10px 16px;"><span style="font-size:9px;font-weight:800;color:#6B9080;text-transform:uppercase;letter-spacing:.08em;">No. of Areas Assessed: 12 &nbsp;·&nbsp; Formula: Total ÷ 60 × 25</span></td>
                        <td class="text-center py-3"><span id="s3Self" class="font-black text-base sc-none">—</span></td>
                        <td class="text-center"><span id="s3Sup" class="font-black text-base sc-none">—</span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 4 — ATTENDANCE
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar"><div class="sec-num">4</div><span class="sec-title">Attendance Record</span></div>
            <div class="px-6 py-6">
                @if($attendanceSummary['has_data'])
                    <p class="text-[11px] text-slate-400 italic mb-5">
                        Attendance data from HR import — {{ implode(', ', $attendanceSummary['months']) }}.
                    </p>
                    <div class="grid grid-cols-4 gap-4 mb-5">
                        @foreach([
                            ['label' => 'Working Days',      'value' => $attendanceSummary['working_days'],      'color' => 'text-slate-700'],
                            ['label' => 'Days Present',      'value' => $attendanceSummary['present_days'],      'color' => 'text-emerald-700'],
                            ['label' => 'Days Absent',       'value' => $attendanceSummary['absent_days'],       'color' => 'text-red-600'],
                            ['label' => 'Late Incidents',    'value' => $attendanceSummary['late_count'],        'color' => 'text-amber-600'],
                            ['label' => 'Medical Leave',     'value' => $attendanceSummary['mc_days'],           'color' => 'text-blue-600'],
                            ['label' => 'Annual Leave',      'value' => $attendanceSummary['al_days'],           'color' => 'text-violet-600'],
                            ['label' => 'Other Leave',       'value' => $attendanceSummary['other_leave_days'],  'color' => 'text-slate-500'],
                            ['label' => 'AWOL',              'value' => $attendanceSummary['awol'],              'color' => $attendanceSummary['awol'] > 0 ? 'text-red-700 font-black' : 'text-slate-400'],
                        ] as $af)
                        <div class="border border-[#6B9080]/20 rounded-xl px-4 py-3 bg-slate-50/60 text-center">
                            <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">{{ $af['label'] }}</p>
                            <p class="text-2xl font-black {{ $af['color'] }}">{{ $af['value'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($attendanceSummary['total_late_minutes'] > 0)
                    <p class="text-[11px] text-amber-600 mb-4">
                        Total late time: {{ intdiv($attendanceSummary['total_late_minutes'], 60) }}h {{ $attendanceSummary['total_late_minutes'] % 60 }}min across all late incidents.
                    </p>
                    @endif
                @else
                    <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-5">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[12px] text-slate-500">Attendance data for {{ $qLabel }} has not been imported yet. HR will upload the data via <strong>Import &amp; Analysis</strong>.</p>
                    </div>
                @endif
                <div>
                    <p class="f-label mb-2">Remarks</p>
                    <textarea rows="3" placeholder="Enter attendance remarks or notes…" class="f-area"></textarea>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 5 — CULTURE & VALUES
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar"><div class="sec-num">5</div><span class="sec-title">Culture &amp; Values Assessment</span></div>
            <div class="px-6 py-6">
                <p class="text-[11px] text-slate-400 italic mb-5">Rate how consistently the employee demonstrates the company's core values.</p>
                <table class="doc-tbl mb-5">
                    <thead>
                        <tr>
                            <th class="l">Core Value</th>
                            <th class="c" style="width:140px;">Self Rating<br><span style="font-weight:400;text-transform:none;font-size:8px;">Pick 1 – 5</span></th>
                            <th class="c" style="width:140px;">Appraiser Rating<br><span style="font-weight:400;text-transform:none;font-size:8px;">Pick 1 – 5</span></th>
                            <th class="l">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                    @php $cultureValues=['Integrity & Honesty','Teamwork & Collaboration','Customer Focus','Innovation & Creativity','Accountability & Ownership','Continuous Learning']; @endphp
                    @foreach($cultureValues as $ci => $cv)
                    <tr class="{{ $ci%2===0?'bg-white':'bg-slate-50/50' }}" style="border-bottom:1px solid rgba(107,144,128,.10);">
                        <td style="padding:12px 16px;font-size:12px;font-weight:700;color:#1e293b;">{{ $cv }}</td>
                        <td class="text-center" style="padding:10px 8px;">
                            <div class="rating-group justify-center">
                                @foreach([1,2,3,4,5] as $sc)<input type="radio" id="cv_s_{{ $ci }}_{{ $sc }}" name="cv_self_{{ $ci }}" value="{{ $sc }}"><label for="cv_s_{{ $ci }}_{{ $sc }}">{{ $sc }}</label>@endforeach
                            </div>
                        </td>
                        <td class="text-center" style="padding:10px 8px;">
                            <div class="rating-group justify-center">
                                @foreach([1,2,3,4,5] as $sc)<input type="radio" id="cv_a_{{ $ci }}_{{ $sc }}" name="cv_app_{{ $ci }}" value="{{ $sc }}"><label for="cv_a_{{ $ci }}_{{ $sc }}">{{ $sc }}</label>@endforeach
                            </div>
                        </td>
                        <td style="padding:10px 12px;"><input type="text" placeholder="Remarks…" class="t-input"></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                <div>
                    <p class="f-label mb-2">Overall Culture Remarks</p>
                    <textarea rows="3" placeholder="Enter overall remarks on culture and values…" class="f-area"></textarea>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 6 — PERFORMANCE SUMMARY
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar"><div class="sec-num">6</div><span class="sec-title">Performance Summary &amp; Analysis</span></div>
            <div class="px-6 py-6 space-y-6">

                <div>
                    <div class="part-label">A &nbsp;·&nbsp; Rating Summary</div>
                    <p class="text-[11px] text-slate-400 italic mb-5">Combined score from all sections of this appraisal review.</p>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden">
                            <table class="doc-tbl">
                                <thead><tr><th class="l">Section</th><th class="c" style="width:100px;">Self Score</th><th class="c" style="width:100px;">Appraiser</th></tr></thead>
                                <tbody>
                                    <tr class="bg-white" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 2</p><p style="font-size:9px;color:#94a3b8;">KPI Performance (70%)</p></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                    </tr>
                                    <tr class="bg-slate-50/50" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 3</p><p style="font-size:9px;color:#94a3b8;">Attitude &amp; Competency (25%)</p></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                    </tr>
                                    <tr class="bg-white" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 4</p><p style="font-size:9px;color:#94a3b8;">Attendance</p></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                    </tr>
                                    <tr class="bg-slate-50/50" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 5</p><p style="font-size:9px;color:#94a3b8;">Culture &amp; Values (5%)</p></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                        <td class="text-center"><input type="number" min="0" max="100" placeholder="—" class="n-input s6-input"></td>
                                    </tr>
                                    <tr style="background:linear-gradient(90deg,rgba(26,61,52,.06),rgba(107,144,128,.04));">
                                        <td style="padding:12px 14px;"><p style="font-size:12px;font-weight:900;color:#1a3d34;text-transform:uppercase;letter-spacing:.05em;">Total Rating</p><p style="font-size:9px;color:#94a3b8;">Combined score</p></td>
                                        <td class="text-center" style="padding:12px;"><span id="s6SelfTotal" style="font-size:20px;font-weight:900;color:#cbd5e1;">—</span></td>
                                        <td class="text-center" style="padding:12px;"><span id="s6AppTotal" style="font-size:20px;font-weight:900;color:#cbd5e1;">—</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <p class="f-label mb-3">Scoring Matrix</p>
                            <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden">
                                <div class="bg-[#1a3d34] px-4 py-2 text-center"><span style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.85);">Performance Grade</span></div>
                                @php $matrix=[['range'=>'90 – 100','label'=>'Outstanding','bg'=>'bg-emerald-50','border'=>'border-emerald-100','text'=>'text-emerald-700','badge'=>'bg-emerald-100'],['range'=>'70 – 89','label'=>'Meets Expectations','bg'=>'bg-[#6B9080]/5','border'=>'border-[#6B9080]/15','text'=>'text-[#1a3d34]','badge'=>'bg-[#6B9080]/15'],['range'=>'50 – 69','label'=>'Below Average','bg'=>'bg-amber-50','border'=>'border-amber-100','text'=>'text-amber-700','badge'=>'bg-amber-100'],['range'=>'1 – 49','label'=>'Unsatisfactory','bg'=>'bg-red-50','border'=>'border-red-100','text'=>'text-red-700','badge'=>'bg-red-100']]; @endphp
                                @foreach($matrix as $m)
                                <div class="flex items-center justify-between {{ $m['bg'] }} border-b {{ $m['border'] }} px-4 py-2.5">
                                    <span style="font-size:11px;font-weight:800;color:#475569;">{{ $m['range'] }}</span>
                                    <span class="text-[10px] font-black px-3 py-1 rounded-full {{ $m['badge'] }} {{ $m['text'] }}">{{ $m['label'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <div class="part-label">B &nbsp;·&nbsp; Performance Analysis</div>
                    <p class="text-[11px] text-slate-400 italic mb-5">To be completed by Appraiser.</p>
                    <div class="grid grid-cols-2 gap-5">
                        @foreach(['Strengths','Work Ethics / Attitude','Areas Need Improvement','Training Required'] as $pf)
                        <div>
                            <div class="flex items-center gap-2 mb-2"><span class="w-1.5 h-1.5 rounded-full bg-[#6B9080] flex-shrink-0"></span><p class="f-label">{{ $pf }}</p></div>
                            <input type="text" placeholder="Enter here…" class="f-input">
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <p class="text-[11px] text-slate-500 italic leading-relaxed mb-6">I hereby confirm that the foregoing appraisal is a fair and objective evaluation of the appraisee's performance during the period under review.</p>
                    <div class="flex justify-end">
                        <div class="text-center w-64">
                            <div class="sig-line mx-2"></div>
                            <p class="text-xs font-bold text-slate-700">{{ $reportsToName !== '-' ? $reportsToName : '_______________' }}</p>
                            <p class="f-label mt-1">Signature of Appraiser – Manager / VP</p>
                            <p class="text-[9px] text-slate-400 mt-2">Date: _______________</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <p class="text-[11px] text-slate-500 italic leading-relaxed mb-3">I hereby confirm that I have read, understood and accept/disagree with the foregoing appraisal. <span class="text-[#6B9080] font-semibold not-italic">(If you disagree please specify below)</span></p>
                    <textarea rows="4" placeholder="Write your response here…" class="f-area mb-6"></textarea>
                    <div class="flex justify-end">
                        <div class="text-center w-64">
                            <div class="sig-line mx-2"></div>
                            <p class="text-xs font-bold text-slate-700">{{ $currentUserName }}</p>
                            <p class="f-label mt-1">Signature of Appraisee</p>
                            <p class="text-[9px] text-slate-400 mt-2">Date: _______________</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 7 — RECOMMENDATIONS & DECISIONS
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-2">
            <div class="sec-bar"><div class="sec-num">7</div><span class="sec-title">Recommendations &amp; Decisions</span></div>
            <div class="px-6 py-6 space-y-7">
                @php $sec7=[['key'=>'manager','label'=>'A','title'=>'Promotability and Other Remarks and Recommendations by the Appraiser (Manager)'],['key'=>'vp','label'=>'B','title'=>'Remarks and/or Recommendations by VP'],['key'=>'slt','label'=>'C','title'=>'Remarks by SLT']]; @endphp
                @foreach($sec7 as $idx => $blk)
                @if($idx>0)<div class="border-t border-dashed border-[#6B9080]/20 pt-7"></div>@endif
                <div>
                    <div class="part-label">{{ $blk['label'] }} &nbsp;·&nbsp; {{ $blk['title'] }}</div>
                    <textarea rows="4" placeholder="Write remarks here…" class="f-area mb-5"></textarea>
                    <div class="flex items-end justify-between gap-6 flex-wrap">
                        <div class="flex items-center gap-6">
                            @foreach(['Confirmation','Salary Review','Promotion'] as $opt)
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <span class="w-4 h-4 rounded border-2 border-[#6B9080]/40 flex items-center justify-center"><input type="checkbox" class="sr-only peer"><span class="w-2.5 h-2.5 rounded-sm bg-[#6B9080] hidden peer-checked:block"></span></span>
                                <span class="text-[11px] font-semibold text-slate-700">{{ $opt }}</span>
                            </label>
                            @endforeach
                        </div>
                        <div class="text-center min-w-56">
                            <div class="sig-line mx-2"></div>
                            <p class="f-label mt-1">Signature</p>
                            <div class="flex items-center gap-2 mt-2 justify-center">
                                <span class="f-label">Date</span>
                                <input type="date" class="border border-[#6B9080]/25 rounded-lg px-2 py-1 text-xs text-slate-600 bg-white outline-none focus:border-[#6B9080] transition">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- /px-10 py-8 --}}
</div>{{-- /doc-card --}}

</div>
</div>{{-- /px-4 --}}
</td></tr>
</tbody>
</table>
</main>

<script>
(function(){
    // Section 2 OKR score calc
    function sc(v,mx){ if(v>=mx*.9)return'sc-great'; if(v>=mx*.7)return'sc-good'; if(v>=mx*.5)return'sc-warn'; return'sc-poor'; }
    document.getElementById('sec2Table')?.addEventListener('input',function(e){
        if(!e.target.classList.contains('sec2-actual')&&!e.target.classList.contains('sec2-target'))return;
        const row=e.target.closest('tr');
        const a=parseFloat(row.querySelector('.sec2-actual')?.value),b=parseFloat(row.querySelector('.sec2-target')?.value);
        const scoreEl=row.querySelector('.sec2-score');
        if(!scoreEl)return;
        if(!isNaN(a)&&!isNaN(b)&&b>0){const s=(a/b)*5;scoreEl.textContent=s.toFixed(2);scoreEl.className='sec2-score font-black text-sm '+sc(s,5);}
        else{scoreEl.textContent='—';scoreEl.className='sec2-score font-black text-sm sc-none';}
    });

    // Part D optional date
    window.openPartDPicker = function() {
        var inp = document.getElementById('partd-date');
        try { inp.showPicker(); } catch(e) { inp.click(); }
    };
    window.setPartDDate = function(val) {
        var valEl = document.getElementById('partd-val');
        var calEl = document.getElementById('partd-cal');
        var clrEl = document.getElementById('partd-clr');
        if (val) {
            var d = new Date(val + 'T00:00:00');
            valEl.textContent = d.toLocaleDateString('en-GB', {day:'2-digit', month:'long', year:'numeric'});
            valEl.style.display = 'inline';
            calEl.style.display = 'none';
            clrEl.style.display = 'inline';
        } else {
            valEl.style.display = 'none';
            calEl.style.display = 'inline-flex';
            clrEl.style.display = 'none';
        }
    };
    window.clearPartDDate = function() {
        document.getElementById('partd-date').value = '';
        window.setPartDDate('');
    };

    // Section 6 live sum
    document.querySelectorAll('.s6-input').forEach(function(inp){
        inp.addEventListener('input',function(){
            const inputs=[...document.querySelectorAll('.s6-input')];
            const selfVals=inputs.filter((_,i)=>i%2===0).map(i=>parseFloat(i.value)).filter(v=>!isNaN(v));
            const appVals=inputs.filter((_,i)=>i%2!==0).map(i=>parseFloat(i.value)).filter(v=>!isNaN(v));
            const selfEl=document.getElementById('s6SelfTotal'),appEl=document.getElementById('s6AppTotal');
            if(selfVals.length){const t=selfVals.reduce((a,b)=>a+b,0);selfEl.textContent=t.toFixed(1);selfEl.style='font-size:20px;font-weight:900;color:'+(t>=90?'#059669':t>=70?'#6B9080':t>=50?'#d97706':'#dc2626');}
            else{selfEl.textContent='—';selfEl.style='font-size:20px;font-weight:900;color:#cbd5e1';}
            if(appVals.length){const t=appVals.reduce((a,b)=>a+b,0);appEl.textContent=t.toFixed(1);appEl.style='font-size:20px;font-weight:900;color:'+(t>=90?'#059669':t>=70?'#6B9080':t>=50?'#d97706':'#dc2626');}
            else{appEl.textContent='—';appEl.style='font-size:20px;font-weight:900;color:#cbd5e1';}
        });
    });
})();
</script>
</body>
</html>
