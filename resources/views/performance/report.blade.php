<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $qLabel }} Evaluation · {{ $currentFinancialYear }}</title>
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
                <h1 class="text-base font-black leading-tight">{{ $qLabel }} Performance Evaluation · {{ $currentFinancialYear }}</h1>
                <p class="text-white/65 text-[10px] mt-0.5">{{ $currentUserName }} · {{ $userPosition }} · {{ $departmentName }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($isAppraiserView ?? false)
            {{-- ── Appraiser buttons ── --}}
            <span class="flex items-center gap-1.5 text-[10px] font-bold bg-blue-500/20 text-blue-200 border border-blue-400/30 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span> Appraiser View · {{ $currentUserName }}
            </span>
            <button id="saveBtn" onclick="saveEvaluation('save')" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl font-bold text-xs transition border border-white/20 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Notes
            </button>
            <button id="appraiseBtn" onclick="confirmAppraised()" class="bg-emerald-500/30 hover:bg-emerald-500/50 text-emerald-100 px-4 py-2 rounded-xl font-bold text-xs transition border border-emerald-400/40 flex items-center gap-1.5">
                ✓ Mark as Appraised
            </button>
            @elseif(($status ?? 'draft') === 'submitted')
            {{-- ── Submitted — awaiting appraiser ── --}}
            <span class="flex items-center gap-1.5 text-[10px] font-bold bg-blue-500/20 text-blue-200 border border-blue-400/30 px-3 py-1.5 rounded-full">
                ✓ Submitted · Awaiting Appraiser
            </span>
            @elseif(($status ?? 'draft') === 'appraised')
            <span class="flex items-center gap-1.5 text-[10px] font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 px-3 py-1.5 rounded-full">
                ✓ Appraised
            </span>
            @elseif($isWindowOpen)
            {{-- ── Appraisee — window badge only; buttons are at page bottom ── --}}
            <span class="flex items-center gap-1.5 text-[10px] font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ⚠️ Window Open · Until {{ $windowEnd }}
            </span>
            @else
            <span class="flex items-center gap-1.5 text-[10px] font-bold bg-white/10 text-white/60 border border-white/15 px-3 py-1.5 rounded-full">
                🔒 Locked · {{ $windowStart }} – {{ $windowEnd }}
            </span>
            @endif
            <button onclick="window.print()" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl font-bold text-xs transition border border-white/20 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / PDF
            </button>
        </div>
    </div>
    {{-- Status banner --}}
    @if($isAppraiserView ?? false)
    <div class="mt-2 rounded-xl bg-blue-50 border border-blue-200 px-4 py-2 flex items-center gap-2 text-xs text-blue-700">
        👁 <span><strong>Appraiser view.</strong> Fill in Section 6B and Section 7, then mark as Appraised.</span>
        @if($submittedAt)<span class="ml-auto text-blue-500 font-medium">Submitted: {{ \Carbon\Carbon::parse($submittedAt)->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</span>@endif
    </div>
    @elseif(($status ?? 'draft') === 'submitted')
    <div class="mt-2 rounded-xl bg-blue-50 border border-blue-200 px-4 py-2 flex items-center gap-2 text-xs text-blue-700">
        ✓ <span><strong>Submitted.</strong> Awaiting appraiser review. Form is now read-only.</span>
        @if($submittedAt)<span class="ml-auto text-blue-500 font-medium">Submitted: {{ \Carbon\Carbon::parse($submittedAt)->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</span>@endif
    </div>
    @elseif(($status ?? 'draft') === 'appraised')
    <div class="mt-2 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2 flex items-center gap-2 text-xs text-emerald-700">
        ✓ <span><strong>Appraised.</strong> Your appraisal has been completed by your appraiser.</span>
        @if($submittedAt)<span class="ml-auto text-emerald-500 font-medium">Updated: {{ \Carbon\Carbon::parse($submittedAt)->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</span>@endif
    </div>
    @elseif($isWindowOpen)
    <div class="mt-2 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2 flex items-center gap-2 text-xs text-emerald-700">
        ⚠️ <span><strong>Evaluation window is open.</strong> Save draft anytime. Submit when ready — you cannot edit after submitting.</span>
        @if($submittedAt)<span class="ml-auto text-emerald-500 font-medium">Last saved: {{ \Carbon\Carbon::parse($submittedAt)->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</span>@endif
    </div>
    @else
    <div class="mt-2 rounded-xl bg-slate-100 border border-slate-200 px-4 py-2 flex items-center gap-2 text-xs text-slate-500">
        🔒 <span><strong>Read-only.</strong> Editing window: {{ $windowStart }} – {{ $windowEnd }}.</span>
        @if($submittedAt)<span class="ml-auto font-medium">Last saved: {{ \Carbon\Carbon::parse($submittedAt)->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</span>@endif
    </div>
    @endif
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
                        <span class="w-4 h-4 rounded border-2 border-[#6B9080]/50 flex items-center justify-center"><input type="checkbox" id="{{ $opt['id'] }}" name="{{ $opt['id'] }}" {{ $opt['id']==='por_quarterly'?'checked':'' }} class="sr-only peer"><span class="w-2.5 h-2.5 rounded-sm bg-[#6B9080] hidden peer-checked:block"></span></span>
                        <span class="text-[11px] font-semibold text-slate-700">{{ $opt['label'] }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="flex-1 min-w-48">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Please specify (if Others)</p>
                    <input type="text" name="por_other_text" placeholder="Describe purpose…" class="f-input">
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
                        <div><p class="f-label">Start Date</p><input type="text" name="start_date" value="{{ $joinDate !== '—' ? $joinDate : '' }}" placeholder="DD MMM YYYY" class="f-input"></div>
                        <div><p class="f-label">Current Position</p><p class="f-val">{{ $userPosition }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                        <div><p class="f-label">Department / Division</p><p class="f-val">{{ $departmentName }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                        <div><p class="f-label">Date Joined</p><p class="f-val">{{ $joinDate }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                        <div><p class="f-label">Months / Years of Service</p><p class="f-val">{{ $tenure }}</p><div class="mt-1 h-px bg-slate-200"></div></div>
                    </div>
                    @php
                        $ytdYear  = now()->year;
                        $ytdMonth = \Carbon\Carbon::create()->month(now()->month)->format('M');
                        $partAFields = [
                            'Medical Leave (Days)'   => $attendanceYTD['mc_days'],
                            'Emergency Leave (Days)' => $attendanceYTD['other_leave_days'],
                            'No. of Lateness'        => $attendanceYTD['late_count'],
                        ];
                    @endphp
                    <div class="mt-5">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                            <span style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.12em;color:#6B9080;">Annual Attendance</span>
                            <span style="font-size:9px;font-weight:700;color:#94a3b8;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:2px 8px;">Jan – {{ $ytdMonth }} {{ $ytdYear }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-6">
                        @foreach($partAFields as $lf => $lv)
                        <div>
                            <p class="f-label">{{ $lf }}</p>
                            <p class="f-val" style="padding:5px 0;pointer-events:none;user-select:none;-webkit-user-select:none;">{{ $attendanceYTD['has_data'] ? $lv : '0' }}</p>
                        </div>
                        @endforeach
                        </div>
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <div class="part-label">Part B &nbsp;·&nbsp; Summary of Duties &amp; Achievements</div>
                    <p class="text-[11px] text-slate-400 italic mb-3">Summarize present duties and indicate if any key tasks set for the year / period have been achieved.</p>
                    <textarea name="part_b" class="f-area" placeholder="Write your summary here…" rows="4"></textarea>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <div class="part-label">Part C &nbsp;·&nbsp; Key Tasks for Forthcoming Period</div>
                    <p class="text-[11px] text-slate-400 italic mb-3"><em>[Where applicable]</em> List what you see as your key tasks for the forthcoming year / period.</p>
                    <textarea name="part_c" class="f-area" placeholder="List your key tasks…" rows="4"></textarea>
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
                                <input type="date" id="partd-date" name="partd_date" style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;" onchange="setPartDDate(this.value)">
                            </span>.
                        </p>
                        <div class="grid grid-cols-2 gap-6">
                            <div><p class="f-label">Appraiser Name</p><input type="text" name="part_d_name" value="{{ $reportsToName !== '-' ? $reportsToName : '' }}" placeholder="Full name" class="f-input"></div>
                            <div><p class="f-label">Designation</p><input type="text" name="part_d_designation" value="{{ $reportsToPosition !== '-' ? $reportsToPosition : '' }}" placeholder="Job title" class="f-input"></div>
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
                        <th class="c" style="width:72px;">Appraiser<br><span style="font-weight:400;text-transform:none;font-size:8px;">Score</span></th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $categoryOrder = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];
                    $sec2Raw = [];
                    foreach ($kpis as $kpi) {
                        $cat = $kpi['category'] ?? 'Uncategorized';
                        $sub = $kpi['sub_category'] ?? 'General';
                        $sec2Raw[$cat][$sub][] = $kpi;
                    }
                    // Sort categories: defined order first, then alphabetical for any extras
                    $sec2Grouped = [];
                    foreach ($categoryOrder as $co) {
                        if (isset($sec2Raw[$co])) {
                            $sec2Grouped[$co] = $sec2Raw[$co];
                            unset($sec2Raw[$co]);
                        }
                    }
                    foreach ($sec2Raw as $co => $subs) { $sec2Grouped[$co] = $subs; }
                    // Sort sub-categories within each category alphabetically
                    foreach ($sec2Grouped as $cat => &$subs) { ksort($subs); }
                    unset($subs);
                    $subCatNo = 0;
                @endphp
                @foreach($sec2Grouped as $catName => $subCats)
                <tr class="cat-hdr"><td colspan="6">{{ $catName }}</td></tr>
                @foreach($subCats as $subName => $subKpis)
                @php $subCatNo++; $subItemNo = 0; @endphp
                <tr class="subcat-hdr"><td colspan="6">{{ $subName }}</td></tr>
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
                            <input type="text" name="kpi_title_{{ $kpi['id'] }}" value="{{ $qTitle }}" placeholder="Describe initiative for {{ $qLabel }}…" class="t-input">
                        </div>
                    </td>
                    <td class="text-center"><span class="sec2-actual font-bold text-sm text-slate-700" data-val="{{ $qAct !== '' ? $qAct : '' }}">{{ $qAct !== '' ? $qAct : '—' }}</span></td>
                    <td class="text-center"><span class="sec2-target font-bold text-sm text-slate-500" data-val="{{ $qTgt !== '' ? $qTgt : '' }}">{{ $qTgt !== '' ? $qTgt : '—' }}</span></td>
                    <td class="text-center">
                        <span class="sec2-score font-black text-sm sc-none">—</span>
                        <input type="hidden" name="kpi_self_{{ $kpi['id'] }}" data-wt="{{ $kpi['weightage'] ?? 0 }}" class="kpi-self-hidden">
                    </td>
                    <td class="text-center"><input type="number" name="kpi_app_{{ $kpi['id'] }}" data-wt="{{ $kpi['weightage'] ?? 0 }}" step="0.1" min="0" max="5" placeholder="—" class="n-input kpi-app-input" readonly style="pointer-events:none;opacity:0.55;background:#f8fafc;cursor:not-allowed;"></td>
                </tr>
                @endforeach
                @endforeach
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:rgba(26,61,52,.06);">
                        <td colspan="4" class="text-right font-black text-xs text-[#1a3d34] uppercase tracking-wide px-4 py-3">Total Score Section 2</td>
                        <td class="text-center py-3"><span id="sec2Total" class="font-black text-base sc-none">—</span></td>
                        <td class="text-center"><span id="sec2AppPct" class="text-xs font-bold text-slate-400">—</span></td>
                    </tr>
                    <tr style="background:rgba(26,61,52,.03);">
                        <td colspan="4" class="text-right text-[9px] font-bold text-slate-400 uppercase tracking-wide px-4 py-2">% Total (Score ÷ 30 × 70)</td>
                        <td colspan="2" class="text-center"><span id="sec2Pct" class="text-sm font-black text-slate-400">—</span></td>
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
                    <td style="padding:10px 8px;vertical-align:top;background:rgba(107,144,128,.03);" class="text-center">
                        <p style="font-size:8px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Superior</p>
                        <div class="rating-group justify-center" style="pointer-events:none;opacity:0.5;">
                            @foreach([1,2,3,4,5] as $sc)<input type="radio" id="sup_{{ $area['no'] }}_{{ $sc }}" name="sup_{{ $area['no'] }}" value="{{ $sc }}"><label for="sup_{{ $area['no'] }}_{{ $sc }}">{{ $sc }}</label>@endforeach
                        </div>
                    </td>
                    <td style="padding:10px 12px;vertical-align:top;background:rgba(107,144,128,.03);">
                        <input type="text" name="att_comment_{{ $area['no'] }}" placeholder="Filled by appraiser…" class="t-input" style="margin-top:6px;pointer-events:none;opacity:0.5;" readonly>
                    </td>
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
                            ['label' => 'Working Days',   'value' => $attendanceSummary['working_days'],     'color' => 'text-slate-700'],
                            ['label' => 'Days Present',   'value' => $attendanceSummary['present_days'],     'color' => 'text-emerald-700'],
                            ['label' => 'Days Absent',    'value' => $attendanceSummary['absent_days'],      'color' => 'text-red-600'],
                            ['label' => 'Late Incidents', 'value' => $attendanceSummary['late_count'],       'color' => 'text-amber-600'],
                            ['label' => 'Medical Leave',  'value' => $attendanceSummary['mc_days'],          'color' => 'text-blue-600'],
                            ['label' => 'Annual Leave',   'value' => $attendanceSummary['al_days'],          'color' => 'text-violet-600'],
                            ['label' => 'Other Leave',    'value' => $attendanceSummary['other_leave_days'], 'color' => 'text-slate-500'],
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

                {{-- ── Attendance Score Assessment ── --}}
                <div class="mt-5 overflow-hidden rounded-xl border border-[#6B9080]/25">
                    <div style="background:rgba(26,61,52,.06);padding:10px 16px;border-bottom:1px solid rgba(107,144,128,.15);display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:10px;font-weight:900;color:#1a3d34;text-transform:uppercase;letter-spacing:.1em;">Attendance Score Assessment · {{ $qLabel }}</span>
                        <span style="font-size:9px;font-weight:700;color:#6B9080;">Appraiser scored · Max 30 pts → 5% of overall</span>
                    </div>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:rgba(107,144,128,.08);border-bottom:1px solid rgba(107,144,128,.15);">
                                <th style="padding:8px 14px;text-align:left;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#6B9080;width:145px;">Category</th>
                                <th style="padding:8px 14px;text-align:left;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#6B9080;">Description</th>
                                <th style="padding:8px 14px;text-align:center;font-size:13px;font-weight:900;color:#059669;width:64px;">6</th>
                                <th style="padding:8px 14px;text-align:center;font-size:13px;font-weight:900;color:#d97706;width:64px;">3</th>
                                <th style="padding:8px 14px;text-align:center;font-size:13px;font-weight:900;color:#dc2626;width:64px;">1</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['id'=>'att_1','label'=>'Lateness',             'desc'=>'Arriving to work up to 30 minutes after start of official work hours.'],
                                ['id'=>'att_2','label'=>'Absent',               'desc'=>'No clock-in record.'],
                                ['id'=>'att_3','label'=>'Insufficient',         'desc'=>'Early departure — before end of official work hours.'],
                                ['id'=>'att_4','label'=>'EL / Unpaid Leave',    'desc'=>'Unplanned leave, especially delayed or unauthorised.'],
                                ['id'=>'att_5','label'=>'Disciplinary Matters', 'desc'=>'Issuance of Reminder, Warning, or Showcause Letters.'],
                            ] as $ai => $ac)
                            <tr style="border-bottom:1px solid rgba(107,144,128,.08);{{ $ai%2===0 ? 'background:#fff;' : 'background:rgba(107,144,128,.03);' }}">
                                <td style="padding:10px 14px;font-size:11px;font-weight:700;color:#1e293b;white-space:nowrap;">{{ $ac['label'] }}</td>
                                <td style="padding:10px 14px;font-size:11px;color:#64748b;">{{ $ac['desc'] }}</td>
                                @foreach([6, 3, 1] as $sc)
                                <td style="padding:10px;text-align:center;">
                                    <input type="radio" name="{{ $ac['id'] }}_score" value="{{ $sc }}"
                                        class="att-score-radio w-4 h-4 accent-[#1a3d34]"
                                        style="pointer-events:none;opacity:0.55;cursor:not-allowed;">
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:rgba(26,61,52,.06);border-top:1px solid rgba(107,144,128,.15);">
                                <td colspan="2" style="padding:10px 14px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#1a3d34;text-align:right;">Score Total</td>
                                <td colspan="3" style="padding:10px 14px;text-align:center;">
                                    <span id="att-score-display" style="font-size:15px;font-weight:900;color:#cbd5e1;">—</span>
                                    <span style="font-size:9px;font-weight:600;color:#94a3b8;margin-left:4px;">/ 30</span>
                                    <span style="margin-left:12px;font-size:9px;font-weight:600;color:#94a3b8;">= </span>
                                    <span id="att-score-pct" style="font-size:13px;font-weight:900;color:#cbd5e1;">—</span>
                                    <span style="font-size:9px;font-weight:600;color:#94a3b8;"> / 5 pts</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-5">
                    <p class="f-label mb-2">Remarks</p>
                    <textarea name="att_remarks" rows="3" placeholder="Enter attendance remarks or notes…" class="f-area"></textarea>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 5 — CULTURE & VALUES (Q4 only)
        ═══════════════════════════════════════════════════════ --}}
        @if($quarter === 'Q4')
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
                        <td style="padding:10px 12px;"><input type="text" name="cv_remark_{{ $ci }}" placeholder="Remarks…" class="t-input"></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                <div>
                    <p class="f-label mb-2">Overall Culture Remarks</p>
                    <textarea name="cv_overall" rows="3" placeholder="Enter overall remarks on culture and values…" class="f-area"></textarea>
                </div>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════
             SECTION 6 — PERFORMANCE SUMMARY
        ═══════════════════════════════════════════════════════ --}}
        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-6">
            <div class="sec-bar"><div class="sec-num">6</div><span class="sec-title">Performance Summary &amp; Analysis</span></div>
            <div class="px-6 py-6 space-y-6">

                <div>
                    <div class="part-label">A &nbsp;·&nbsp; Rating Summary</div>
                    <p class="text-[11px] text-slate-400 italic mb-5">Combined score from all sections of this appraisal review.</p>
                    <div class="grid grid-cols-2 gap-6 items-start">
                        <div class="border border-[#6B9080]/25 rounded-xl overflow-hidden">
                            <table class="doc-tbl">
                                <thead><tr><th class="l">Section</th><th class="c" style="width:100px;">Self Score</th><th class="c" style="width:100px;">Appraiser</th></tr></thead>
                                @php
                                    $s4ScoreVal = null;
                                    if (($attendanceSummary['has_data'] ?? false) && ($attendanceSummary['working_days'] ?? 0) > 0) {
                                        $s4ScoreVal = round($attendanceSummary['present_days'] / $attendanceSummary['working_days'] * 100, 1);
                                    }
                                @endphp
                                <tbody>
                                    <tr class="bg-white" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 2</p><p style="font-size:9px;color:#94a3b8;">KPI Performance (70%)</p></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s2_self" style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s2_self" id="hid_s6_s2_self"></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s2_app"  style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s2_app"  id="hid_s6_s2_app"></td>
                                    </tr>
                                    <tr class="bg-slate-50/50" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 3</p><p style="font-size:9px;color:#94a3b8;">Attitude &amp; Competency (25%)</p></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s3_self" style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s3_self" id="hid_s6_s3_self"></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s3_app"  style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s3_app"  id="hid_s6_s3_app"></td>
                                    </tr>
                                    <tr class="bg-white" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 4</p><p style="font-size:9px;color:#94a3b8;">Attendance (5%)</p></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s4_self" style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s4_self" id="hid_s6_s4_self"></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s4_app"  style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s4_app"  id="hid_s6_s4_app"></td>
                                    </tr>
                                    @if($quarter === 'Q4')
                                    <tr class="bg-slate-50/50" style="border-bottom:1px solid rgba(107,144,128,.10);">
                                        <td style="padding:12px 14px;"><p style="font-size:11px;font-weight:700;color:#334155;">Section 5</p><p style="font-size:9px;color:#94a3b8;">Culture &amp; Values (5%)</p></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s5_self" style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s5_self" id="hid_s6_s5_self"></td>
                                        <td class="text-center" style="padding:12px;"><span id="disp_s6_s5_app"  style="font-size:18px;font-weight:900;color:#cbd5e1;">—</span><input type="hidden" name="s6_s5_app"  id="hid_s6_s5_app"></td>
                                    </tr>
                                    @endif
                                    <tr style="background:linear-gradient(90deg,rgba(26,61,52,.06),rgba(107,144,128,.04));">
                                        <td style="padding:12px 14px;"><p style="font-size:12px;font-weight:900;color:#1a3d34;text-transform:uppercase;letter-spacing:.05em;">Total Rating</p><p style="font-size:9px;color:#94a3b8;">Combined score</p></td>
                                        <td class="text-center" style="padding:12px;"><span id="s6SelfTotal" style="font-size:20px;font-weight:900;color:#cbd5e1;">—</span></td>
                                        <td class="text-center" style="padding:12px;"><span id="s6AppTotal" style="font-size:20px;font-weight:900;color:#cbd5e1;">—</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Bell curve: right column, side-by-side with rating table --}}
                        <div>
                            <p class="f-label mb-3">Performance Distribution</p>
                            <div style="background:linear-gradient(180deg,#f7faf9 0%,#ffffff 100%);border-radius:16px;padding:20px 12px 12px;border:1px solid rgba(107,144,128,.15);">
                            <svg viewBox="0 0 1000 370" style="width:100%;display:block;overflow:visible;" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    {{-- Gaussian bell: σ=150, 5 equal zones of 200px, peak at x=500 y=40, baseline y=300 --}}
                                    <clipPath id="bc_clip">
                                        <path d="M 0,300 L 0,299 C 80,299 160,273 200,265 C 250,254 330,130 400,92 C 445,58 470,43 500,40 C 530,43 555,58 600,92 C 670,130 750,254 800,265 C 840,273 920,299 1000,299 L 1000,300 Z"/>
                                    </clipPath>
                                    <filter id="bc_shadow" x="-30%" y="-80%" width="160%" height="280%">
                                        <feDropShadow dx="0" dy="2" stdDeviation="5" flood-opacity="0.18"/>
                                    </filter>
                                </defs>
                                {{-- 5 equal zones of 200px each --}}
                                <rect x="0"   y="0" width="200" height="300" fill="#e85d04" clip-path="url(#bc_clip)"/>
                                <rect x="200" y="0" width="200" height="300" fill="#f97316" clip-path="url(#bc_clip)"/>
                                <rect x="400" y="0" width="200" height="300" fill="#fbbf24" clip-path="url(#bc_clip)"/>
                                <rect x="600" y="0" width="200" height="300" fill="#4ade80" clip-path="url(#bc_clip)"/>
                                <rect x="800" y="0" width="200" height="300" fill="#22c55e" clip-path="url(#bc_clip)"/>
                                {{-- Zone dividers --}}
                                <line x1="200" y1="250" x2="200" y2="300" stroke="rgba(255,255,255,.75)" stroke-width="2.5"/>
                                <line x1="400" y1="92"  x2="400" y2="300" stroke="rgba(255,255,255,.75)" stroke-width="2.5"/>
                                <line x1="600" y1="92"  x2="600" y2="300" stroke="rgba(255,255,255,.75)" stroke-width="2.5"/>
                                <line x1="800" y1="250" x2="800" y2="300" stroke="rgba(255,255,255,.75)" stroke-width="2.5"/>
                                {{-- Baseline --}}
                                <line x1="0" y1="300" x2="1000" y2="300" stroke="#e2e8f0" stroke-width="1"/>
                                {{-- Zone names --}}
                                <text x="100" y="317" text-anchor="middle" fill="#e85d04" style="font-size:12px;font-weight:800;">Unacceptable</text>
                                <text x="300" y="315" text-anchor="middle" fill="#c2410c" style="font-size:12px;font-weight:800;">Room for</text>
                                <text x="300" y="329" text-anchor="middle" fill="#c2410c" style="font-size:12px;font-weight:800;">Improvement</text>
                                <text x="500" y="317" text-anchor="middle" fill="#b45309" style="font-size:13px;font-weight:800;">Meets Expectations</text>
                                <text x="700" y="315" text-anchor="middle" fill="#16a34a" style="font-size:12px;font-weight:800;">Exceeds</text>
                                <text x="700" y="329" text-anchor="middle" fill="#16a34a" style="font-size:12px;font-weight:800;">Expectations</text>
                                <text x="900" y="317" text-anchor="middle" fill="#15803d" style="font-size:12px;font-weight:800;">Outstanding</text>
                                {{-- Score ranges --}}
                                <text x="100" y="349" text-anchor="middle" fill="#94a3b8" style="font-size:11px;font-weight:600;">1 – 34</text>
                                <text x="300" y="349" text-anchor="middle" fill="#94a3b8" style="font-size:11px;font-weight:600;">35 – 49</text>
                                <text x="500" y="349" text-anchor="middle" fill="#94a3b8" style="font-size:11px;font-weight:600;">50 – 79</text>
                                <text x="700" y="349" text-anchor="middle" fill="#94a3b8" style="font-size:11px;font-weight:600;">80 – 89</text>
                                <text x="900" y="349" text-anchor="middle" fill="#94a3b8" style="font-size:11px;font-weight:600;">90 – 100</text>
                                {{-- Indicator: vertical line + dot + floating score label --}}
                                <g id="bellIndicator" style="display:none;">
                                    <line id="bellLine" x1="500" y1="0" x2="500" y2="300" stroke="#1e293b" stroke-width="3" stroke-dasharray="8,5" stroke-linecap="round"/>
                                    <circle id="bellDot" cx="500" cy="100" r="9" fill="#1e293b" stroke="white" stroke-width="3"/>
                                    <rect id="bellBg" x="440" y="-58" width="120" height="48" rx="10" ry="10" fill="white" stroke="#1e293b" stroke-width="1.5" filter="url(#bc_shadow)"/>
                                    <text id="bellScoreNum" x="500" y="-25" text-anchor="middle" fill="#1e293b" style="font-size:22px;font-weight:900;"></text>
                                    <text id="bellGradeName" x="500" y="-11" text-anchor="middle" fill="#6B9080" style="font-size:10px;font-weight:700;"></text>
                                </g>
                            </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div id="sec6b">
                    <div class="flex items-center justify-between mb-1">
                        <div class="part-label">B &nbsp;·&nbsp; Performance Analysis</div>
                        <span style="font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.08em;display:flex;align-items:center;gap:4px;">🔒 APPRAISER ONLY</span>
                    </div>
                    <p class="text-[11px] text-slate-400 italic mb-5">To be completed by Appraiser. Read-only for appraisee.</p>
                    <div class="grid grid-cols-2 gap-5">
                        @foreach([['label'=>'Strengths','name'=>'s6_strengths'],['label'=>'Work Ethics / Attitude','name'=>'s6_ethics'],['label'=>'Areas Need Improvement','name'=>'s6_improvement'],['label'=>'Training Required','name'=>'s6_training']] as $pf)
                        <div>
                            <div class="flex items-center gap-2 mb-2"><span class="w-1.5 h-1.5 rounded-full bg-[#6B9080] flex-shrink-0"></span><p class="f-label">{{ $pf['label'] }}</p></div>
                            <input type="text" name="{{ $pf['name'] }}" placeholder="—" class="f-input" readonly style="pointer-events:none;opacity:0.55;background:#f8fafc;cursor:not-allowed;">
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div id="sec6b_sig">
                    <p class="text-[11px] text-slate-500 italic leading-relaxed mb-6">I hereby confirm that the foregoing appraisal is a fair and objective evaluation of the appraisee's performance during the period under review.</p>
                    <div class="flex justify-end">
                        <div style="width:280px;">
                            {{-- Appraiser signature — locked for appraisee --}}
                            <div class="sig-pad-wrap" data-sig-id="sig_appraiser" style="pointer-events:none;opacity:0.55;">
                                <div style="border:2px dashed rgba(107,144,128,.3);border-radius:10px;background:#f8fafc;position:relative;overflow:hidden;">
                                    <canvas class="sig-canvas" width="560" height="110" style="width:100%;height:110px;display:block;"></canvas>
                                    <div class="sig-hint" style="position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:11px;font-weight:600;">Appraiser signature</div>
                                </div>
                                <input type="hidden" name="sig_appraiser" class="sig-hidden">
                            </div>
                            <p class="text-xs font-bold text-slate-700 text-center mt-2">{{ $reportsToName !== '-' ? $reportsToName : '_______________' }}</p>
                            <p class="f-label text-center mt-1">Signature of Appraiser – Manager / VP</p>
                            <p class="text-[9px] text-slate-400 text-center mt-2">Date: _______________</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-dashed border-[#6B9080]/20"></div>

                <div>
                    <p class="text-[11px] text-slate-500 italic leading-relaxed mb-3">I hereby confirm that I have read, understood and accept/disagree with the foregoing appraisal. <span class="text-[#6B9080] font-semibold not-italic">(If you disagree please specify below)</span></p>
                    <textarea name="s6_response" rows="4" placeholder="Write your response here…" class="f-area mb-6"></textarea>
                    <div class="flex justify-end">
                        <div style="width:280px;">
                            {{-- Appraisee signature — editable --}}
                            <div class="sig-pad-wrap" data-sig-id="sig_appraisee">
                                <div style="border:2px dashed rgba(107,144,128,.5);border-radius:10px;background:#f9fafb;position:relative;overflow:hidden;cursor:crosshair;">
                                    <canvas class="sig-canvas" width="560" height="110" style="width:100%;height:110px;display:block;touch-action:none;"></canvas>
                                    <div class="sig-hint" style="position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:11px;font-weight:600;">✍&nbsp; Draw signature here</div>
                                </div>
                                <div style="display:flex;gap:6px;margin-top:6px;justify-content:center;">
                                    <button type="button" onclick="sigClear(this)" style="font-size:10px;padding:3px 12px;border-radius:6px;border:1px solid #e2e8f0;background:white;color:#64748b;cursor:pointer;font-weight:600;">Clear</button>
                                    <label style="font-size:10px;padding:3px 12px;border-radius:6px;border:1px solid #e2e8f0;background:white;color:#64748b;cursor:pointer;font-weight:600;display:inline-block;">
                                        📎 Upload<input type="file" accept="image/*" class="sr-only sig-file-input" onchange="sigUpload(this)">
                                    </label>
                                </div>
                                <input type="hidden" name="sig_appraisee" class="sig-hidden">
                            </div>
                            <p class="text-xs font-bold text-slate-700 text-center mt-2">{{ $currentUserName }}</p>
                            <p class="f-label text-center mt-1">Signature of Appraisee</p>
                            <p class="text-[9px] text-slate-400 text-center mt-2">Date: _______________</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 7 — RECOMMENDATIONS & DECISIONS
        ═══════════════════════════════════════════════════════ --}}
        <div id="sec7" class="border border-[#6B9080]/25 rounded-xl overflow-hidden mb-2">
            <div class="sec-bar"><div class="sec-num">7</div><span class="sec-title">Recommendations &amp; Decisions</span><span style="margin-left:auto;font-size:10px;font-weight:700;color:rgba(255,255,255,.55);letter-spacing:.08em;">🔒 APPRAISER ONLY</span></div>
            <div class="px-6 py-6 space-y-7">
                @php $sec7=[['key'=>'manager','label'=>'A','title'=>'Promotability and Other Remarks and Recommendations by the Appraiser (Manager)'],['key'=>'vp','label'=>'B','title'=>'Remarks and/or Recommendations by VP'],['key'=>'slt','label'=>'C','title'=>'Remarks by SLT']]; @endphp
                @foreach($sec7 as $idx => $blk)
                @if($idx>0)<div class="border-t border-dashed border-[#6B9080]/20 pt-7"></div>@endif
                <div>
                    <div class="part-label">{{ $blk['label'] }} &nbsp;·&nbsp; {{ $blk['title'] }}</div>
                    <textarea name="s7_{{ $blk['key'] }}_remarks" rows="4" placeholder="—" class="f-area mb-5" readonly style="pointer-events:none;opacity:0.55;background:#f8fafc;cursor:not-allowed;resize:none;"></textarea>
                    <div class="flex items-end justify-between gap-6 flex-wrap">
                        <div class="flex items-center gap-6">
                            @foreach(['confirmation','salary_review','promotion'] as $oi => $okey)
                            @php $olabel = ['Confirmation','Salary Review','Promotion'][$oi]; @endphp
                            <label class="flex items-center gap-2 select-none" style="pointer-events:none;opacity:0.55;cursor:not-allowed;">
                                <span class="w-4 h-4 rounded border-2 border-[#6B9080]/40 flex items-center justify-center"><input type="checkbox" name="s7_{{ $blk['key'] }}_{{ $okey }}" id="s7_{{ $blk['key'] }}_{{ $okey }}" class="sr-only peer"><span class="w-2.5 h-2.5 rounded-sm bg-[#6B9080] hidden peer-checked:block"></span></span>
                                <span class="text-[11px] font-semibold text-slate-700">{{ $olabel }}</span>
                            </label>
                            @endforeach
                        </div>
                        <div style="min-width:224px;">
                            <div class="sig-pad-wrap" data-sig-id="s7_{{ $blk['key'] }}_sig" style="pointer-events:none;opacity:0.55;">
                                <div style="border:2px dashed rgba(107,144,128,.3);border-radius:10px;background:#f8fafc;position:relative;overflow:hidden;">
                                    <canvas class="sig-canvas" width="448" height="90" style="width:100%;height:90px;display:block;"></canvas>
                                    <div class="sig-hint" style="position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:11px;font-weight:600;">Signature</div>
                                </div>
                                <input type="hidden" name="s7_{{ $blk['key'] }}_sig" class="sig-hidden">
                            </div>
                            <div class="flex items-center gap-2 mt-2 justify-center">
                                <span class="f-label">Date</span>
                                <input type="date" name="s7_{{ $blk['key'] }}_date" class="border border-[#6B9080]/25 rounded-lg px-2 py-1 text-xs text-slate-600 bg-white outline-none" readonly style="pointer-events:none;opacity:0.55;background:#f8fafc;cursor:not-allowed;">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- /px-10 py-8 --}}
</div>{{-- /doc-card --}}

@if(!($isAppraiserView ?? false) && $isWindowOpen && ($status ?? 'draft') === 'draft')
{{-- ── Fixed bottom action bar (hidden on print) ── --}}
<div class="no-print" style="position:fixed;bottom:0;left:230px;right:0;z-index:50;background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-top:1px solid rgba(107,144,128,.20);padding:14px 32px;display:flex;align-items:center;justify-content:flex-end;gap:10px;box-shadow:0 -4px 24px rgba(15,23,42,.08);">
    <span style="font-size:11px;font-weight:600;color:#94a3b8;margin-right:6px;">Evaluation window open until {{ $windowEnd }}</span>
    <button id="draftBtn" onclick="saveEvaluation('draft')" style="display:flex;align-items:center;gap:6px;padding:10px 20px;border-radius:12px;font-size:12px;font-weight:700;border:1.5px solid #e2e8f0;background:#fff;color:#475569;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#fff'">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Draft
    </button>
    <button id="submitBtn" onclick="confirmSubmit()" style="display:flex;align-items:center;gap:6px;padding:10px 22px;border-radius:12px;font-size:12px;font-weight:700;border:none;background:linear-gradient(135deg,#2d5548,#4a7c6b);color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(45,85,72,.30);transition:opacity .15s;" onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
        ↑ Submit to Appraiser
    </button>
</div>
{{-- Add bottom padding so content isn't hidden under the bar --}}
<div style="height:72px;"></div>
@endif

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
    window.calcAllSec2Scores = function() {
        document.querySelectorAll('.sec2-row').forEach(function(row) {
            var aEl = row.querySelector('.sec2-actual');
            var bEl = row.querySelector('.sec2-target');
            var scoreEl = row.querySelector('.sec2-score');
            var selfHid = row.querySelector('.kpi-self-hidden');
            if (!scoreEl) return;
            var a = parseFloat(aEl?.dataset?.val);
            var b = parseFloat(bEl?.dataset?.val);
            if (!isNaN(a) && !isNaN(b) && b > 0) {
                var s = Math.min((a / b) * 5, 5);
                scoreEl.textContent = s.toFixed(2);
                scoreEl.className = 'sec2-score font-black text-sm ' + sc(s, 5);
                if (selfHid) selfHid.value = s.toFixed(4);
            } else {
                scoreEl.textContent = '—';
                scoreEl.className = 'sec2-score font-black text-sm sc-none';
                if (selfHid) selfHid.value = '';
            }
        });
        updateS6();
    };
    calcAllSec2Scores();

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

    // Section 3 live sum (self + superior)
    function updateS3() {
        var selfTotal = 0, selfCount = 0, supTotal = 0, supCount = 0;
        for (var i = 1; i <= 12; i++) {
            var selfVal = document.querySelector('input[name="self_' + i + '"]:checked');
            var supVal  = document.querySelector('input[name="sup_'  + i + '"]:checked');
            if (selfVal) { selfTotal += parseInt(selfVal.value); selfCount++; }
            if (supVal)  { supTotal  += parseInt(supVal.value);  supCount++; }
        }
        var selfEl = document.getElementById('s3Self');
        var supEl  = document.getElementById('s3Sup');
        function scoreColor(v) { return v >= 50 ? '#059669' : v >= 40 ? '#6B9080' : v >= 30 ? '#d97706' : '#dc2626'; }
        if (selfEl) {
            if (selfCount > 0) { var s = (selfTotal / 60 * 25).toFixed(1); selfEl.textContent = s; selfEl.style.color = scoreColor(parseFloat(s)); }
            else { selfEl.textContent = '—'; selfEl.style.color = '#cbd5e1'; }
        }
        if (supEl) {
            if (supCount > 0) { var s2 = (supTotal / 60 * 25).toFixed(1); supEl.textContent = s2; supEl.style.color = scoreColor(parseFloat(s2)); }
            else { supEl.textContent = '—'; supEl.style.color = '#cbd5e1'; }
        }
    }
    document.querySelectorAll('input[name^="self_"], input[name^="sup_"]').forEach(function(r) {
        r.addEventListener('change', function(){ updateS3(); updateS6(); });
    });

    // ── Section 6 auto-compute ────────────────────────────────────────────────
    var _isQ4 = '{{ $quarter }}' === 'Q4';

    function calcAttScore() {
        var total = 0, count = 0;
        for (var i = 1; i <= 5; i++) {
            var checked = document.querySelector('input[name="att_' + i + '_score"]:checked');
            if (checked) { total += parseInt(checked.value); count++; }
        }
        var disp = document.getElementById('att-score-display');
        var pct  = document.getElementById('att-score-pct');
        if (count === 0) {
            if (disp) { disp.textContent = '—'; disp.style.color = '#cbd5e1'; }
            if (pct)  { pct.textContent  = '—'; pct.style.color  = '#cbd5e1'; }
            return null;
        }
        var score = total / 30 * 5;
        var clr = score >= 4 ? '#059669' : score >= 3 ? '#6B9080' : score >= 2 ? '#d97706' : '#dc2626';
        if (disp) { disp.textContent = total; disp.style.color = clr; }
        if (pct)  { pct.textContent  = score.toFixed(2); pct.style.color = clr; }
        return score;
    }

    function s6Clr(v){ return v>=90?'#15803d':v>=80?'#16a34a':v>=50?'#d97706':v>=35?'#ea6f00':'#e85d04'; }

    function scoreToX(s) {
        if (s <= 34)  return (s - 1)  / 33 * 200;
        if (s <= 49)  return 200 + (s - 35) / 14 * 200;
        if (s <= 79)  return 400 + (s - 50) / 29 * 200;
        if (s <= 89)  return 600 + (s - 80) / 9  * 200;
        return 800 + (s - 90) / 10 * 200;
    }
    function bellY(x) {
        return 300 - 260 * Math.exp(-(x - 500) * (x - 500) / 45000);
    }
    function updateGauge(score) {
        var g = document.getElementById('bellIndicator');
        if (!g) return;
        if (score === null || isNaN(score)) { g.style.display = 'none'; return; }
        var grade, clr;
        if (score >= 90)      { grade = 'Outstanding';          clr = '#15803d'; }
        else if (score >= 80) { grade = 'Exceeds Expectations'; clr = '#16a34a'; }
        else if (score >= 50) { grade = 'Meets Expectations';   clr = '#b45309'; }
        else if (score >= 35) { grade = 'Room for Improvement'; clr = '#c2410c'; }
        else                  { grade = 'Unacceptable';         clr = '#e85d04'; }
        var bx = scoreToX(score);
        var by = bellY(bx);
        var lx = Math.max(60, Math.min(940, bx));
        g.style.display = '';
        document.getElementById('bellLine').setAttribute('x1', bx);
        document.getElementById('bellLine').setAttribute('x2', bx);
        document.getElementById('bellDot').setAttribute('cx', bx);
        document.getElementById('bellDot').setAttribute('cy', by);
        document.getElementById('bellDot').setAttribute('fill', clr);
        document.getElementById('bellBg').setAttribute('x', lx - 60);
        document.getElementById('bellScoreNum').setAttribute('x', lx);
        document.getElementById('bellScoreNum').textContent = score.toFixed(1);
        document.getElementById('bellScoreNum').setAttribute('fill', clr);
        document.getElementById('bellGradeName').setAttribute('x', lx);
        document.getElementById('bellGradeName').textContent = grade;
    }
    function setS6(key, val) {
        var num = (val !== null && !isNaN(parseFloat(val))) ? parseFloat(val) : null;
        var d = document.getElementById('disp_' + key);
        var h = document.getElementById('hid_'  + key);
        if (d) { d.textContent = num !== null ? num.toFixed(1) : '—'; d.style.color = num !== null ? s6Clr(num) : '#cbd5e1'; }
        if (h) h.value = num !== null ? num : '';
    }
    function updateS6() {
        // S2: weighted sum of kpi_self / kpi_app → scale to 70
        var s2Self = 0, s2App = 0, s2Wt = 0;
        document.querySelectorAll('[name^="kpi_self_"]').forEach(function(el){
            var v = parseFloat(el.value), wt = parseFloat(el.dataset.wt||0);
            if (!isNaN(v) && wt > 0) { s2Self += v * wt; s2Wt += wt; }
        });
        document.querySelectorAll('[name^="kpi_app_"]').forEach(function(el){
            var v = parseFloat(el.value), wt = parseFloat(el.dataset.wt||0);
            if (!isNaN(v) && wt > 0) s2App += v * wt;
        });
        setS6('s6_s2_self', s2Wt > 0 ? (s2Self / s2Wt / 5 * 70) : null);
        setS6('s6_s2_app',  s2Wt > 0 ? (s2App  / s2Wt / 5 * 70) : null);

        // S3: from live s3Self / s3Sup spans
        var s3ST = document.getElementById('s3Self')?.textContent;
        var s3AT = document.getElementById('s3Sup')?.textContent;
        setS6('s6_s3_self', s3ST && s3ST !== '—' ? parseFloat(s3ST) : null);
        setS6('s6_s3_app',  s3AT && s3AT !== '—' ? parseFloat(s3AT) : null);

        // S4: attendance score from rubric table (appraiser scored, max 30 → scaled to 5)
        var s4 = calcAttScore();
        setS6('s6_s4_self', s4);
        setS6('s6_s4_app',  s4);

        // S5 (Q4): culture values radio sums → scale to 5
        if (_isQ4) {
            var cv5S = 0, cv5A = 0, cvN = 0;
            for (var ci = 0; ci <= 5; ci++) {
                var csvS = document.querySelector('input[name="cv_self_'+ci+'"]:checked');
                var csvA = document.querySelector('input[name="cv_app_'+ci+'"]:checked');
                if (csvS) { cv5S += parseInt(csvS.value); cvN++; }
                if (csvA)   cv5A += parseInt(csvA.value);
            }
            setS6('s6_s5_self', cvN > 0 ? (cv5S / 30 * 5) : null);
            setS6('s6_s5_app',  cvN > 0 ? (cv5A / 30 * 5) : null);
        }

        // Total
        var selfKeys = ['s6_s2_self','s6_s3_self','s6_s4_self'];
        var appKeys  = ['s6_s2_app', 's6_s3_app', 's6_s4_app'];
        if (_isQ4) { selfKeys.push('s6_s5_self'); appKeys.push('s6_s5_app'); }
        var sSum=0,sCnt=0,aSum=0,aCnt=0;
        selfKeys.forEach(function(k){ var v=parseFloat(document.getElementById('hid_'+k)?.value); if(!isNaN(v)){sSum+=v;sCnt++;} });
        appKeys.forEach(function(k){  var v=parseFloat(document.getElementById('hid_'+k)?.value); if(!isNaN(v)){aSum+=v;aCnt++;} });
        var sEl=document.getElementById('s6SelfTotal'), aEl=document.getElementById('s6AppTotal');
        if(sEl){sEl.textContent=sCnt>0?sSum.toFixed(1):'—';sEl.style.color=sCnt>0?s6Clr(sSum):'#cbd5e1';}
        if(aEl){aEl.textContent=aCnt>0?aSum.toFixed(1):'—';aEl.style.color=aCnt>0?s6Clr(aSum):'#cbd5e1';}
        updateGauge(sCnt > 0 ? sSum : null);
    }

    // Wire: Section 2 self/app inputs → updateS6
    document.querySelectorAll('[name^="kpi_self_"],[name^="kpi_app_"]').forEach(function(el){ el.addEventListener('input', updateS6); });
    // Wire: culture values radios (Q4) → updateS6
    document.querySelectorAll('[name^="cv_self_"],[name^="cv_app_"]').forEach(function(r){ r.addEventListener('change', updateS6); });
    // Wire: attendance score radios → updateS6
    document.querySelectorAll('.att-score-radio').forEach(function(r){ r.addEventListener('change', updateS6); });
})();
</script>

{{-- ─── Save / Restore / Lock ─────────────────────────────────────────────── --}}
<div id="toast" style="display:none;position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:9999;padding:10px 22px;border-radius:12px;font-size:12px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.18);transition:opacity .3s;"></div>

<script>
const _savedData       = @json($savedData ?? null);
const _quarter         = '{{ $quarter }}';
const _isWindowOpen    = @json($isWindowOpen ?? false);
const _status          = '{{ $status ?? "draft" }}';
const _isSubmitted     = _status === 'submitted' || _status === 'appraised';
const _isAppraiserView = @json($isAppraiserView ?? false);
const _saveUrl         = _isAppraiserView
    ? '{{ $appraiserSaveUrl ?? "" }}'
    : '{{ route('performance.report.save', $quarter) }}';
const _csrfToken       = '{{ csrf_token() }}';

// ── collect all named inputs into a plain object ─────────────────────────────
function collectFormData() {
    const data = {};
    document.querySelectorAll('[name]').forEach(function(el) {
        const n = el.name;
        if (!n) return;
        if (el.type === 'radio') {
            // only capture the checked radio; initialise to null so unchecked groups are still saved
            if (el.checked) data[n] = el.value;
            else if (!(n in data)) data[n] = null;
        } else if (el.type === 'checkbox') {
            data[n] = el.checked;
        } else {
            data[n] = el.value;
        }
    });
    return data;
}

// ── restore all named inputs from saved object ────────────────────────────────
function restoreFormData(saved) {
    if (!saved || typeof saved !== 'object') return;
    Object.keys(saved).forEach(function(n) {
        const els = document.querySelectorAll('[name="' + n + '"]');
        els.forEach(function(el) {
            if (el.type === 'radio') {
                el.checked = (saved[n] !== null && el.value == saved[n]);
                if (el.checked) el.dispatchEvent(new Event('change'));
            } else if (el.type === 'checkbox') {
                el.checked = !!saved[n];
                // trigger visual update for custom checkbox spans
                el.dispatchEvent(new Event('change'));
            } else {
                el.value = saved[n] ?? '';
                // restore Part D date display
                if (n === 'partd_date' && saved[n]) {
                    window.setPartDDate(saved[n]);
                }
            }
        });
    });
    // re-trigger S6 sum
    const firstS6 = document.querySelector('.s6-input');
    if (firstS6) firstS6.dispatchEvent(new Event('input'));
    // re-trigger S2 scores
    if (typeof calcAllSec2Scores === 'function') calcAllSec2Scores();
}

// ── lock all inputs when window is closed ─────────────────────────────────────
function lockForm() {
    const main = document.querySelector('main');
    if (!main) return;
    main.style.pointerEvents = 'none';
    main.style.userSelect    = 'none';
    main.style.opacity       = '0.7';
    const btn = document.getElementById('saveBtn');
    if (btn) { btn.disabled = true; btn.style.display = 'none'; }
}

// ── toast helper ──────────────────────────────────────────────────────────────
function showToast(msg, ok) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background   = ok ? '#059669' : '#dc2626';
    t.style.color        = '#fff';
    t.style.display      = 'block';
    t.style.opacity      = '1';
    setTimeout(function(){ t.style.opacity='0'; setTimeout(function(){ t.style.display='none'; },300); }, 3000);
}

// ── save ──────────────────────────────────────────────────────────────────────
window.saveEvaluation = function(action) {
    action = action || 'draft';
    if (!_isAppraiserView && !_isWindowOpen) { showToast('Evaluation window is closed.', false); return; }
    // Validate Section 3 only for appraisee submit
    if (!_isAppraiserView && action === 'submit') {
        var missing = [];
        for (var i = 1; i <= 12; i++) {
            if (!document.querySelector('input[name="self_' + i + '"]:checked')) missing.push(i);
        }
        if (missing.length > 0) {
            showToast('Section 3: Please rate all ' + missing.length + ' remaining area(s).', false);
            missing.forEach(function(n) {
                var row = document.querySelector('input[name="self_' + n + '"]')?.closest('tr');
                if (row) { row.style.background = 'rgba(239,68,68,.08)'; setTimeout(function(){ row.style.background = ''; }, 2500); }
            });
            return;
        }
    }
    const data = collectFormData();
    var activeBtn = document.getElementById(action === 'draft' ? 'draftBtn' : (action === 'save' ? 'saveBtn' : 'submitBtn'));
    if (!activeBtn) activeBtn = document.getElementById('saveBtn');
    if (activeBtn) activeBtn.disabled = true;
    fetch(_saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrfToken },
        body: JSON.stringify({ form_data: data, action: action }),
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if (res && res.success) {
            if (action === 'submit') {
                showToast('Submitted to appraiser ✓', true);
                setTimeout(function(){ location.reload(); }, 1200);
            } else if (action === 'appraised') {
                showToast('Marked as Appraised ✓', true);
                setTimeout(function(){ window.location.href = '/performance/appraise'; }, 1200);
            } else {
                showToast('Draft saved ✓', true);
            }
        } else {
            showToast(res.error || 'Save failed.', false);
        }
    })
    .catch(function(){ showToast('Network error.', false); })
    .finally(function(){ if (activeBtn) activeBtn.disabled = false; });
};

function confirmSubmit() {
    if (confirm('Submit this evaluation to your appraiser?\n\nYou will not be able to edit it after submitting.')) {
        saveEvaluation('submit');
    }
}

function confirmAppraised() {
    if (confirm('Mark this appraisal as complete?\n\nThis will lock the form and notify the appraisee.')) {
        saveEvaluation('appraised');
    }
}

function unlockAppraiserSections() {
    ['sec6b', 'sec6b_sig', 'sec7'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
        el.querySelectorAll('input:not([type="file"]):not([type="hidden"]), textarea, select').forEach(function(inp) {
            inp.removeAttribute('readonly');
            inp.style.pointerEvents = 'auto';
            inp.style.opacity = '';
            inp.style.background = '';
            inp.style.cursor = '';
            inp.style.resize = '';
        });
        el.querySelectorAll('label').forEach(function(lbl) {
            lbl.style.pointerEvents = 'auto';
            lbl.style.opacity = '';
            lbl.style.cursor = '';
        });
        el.querySelectorAll('.sig-pad-wrap').forEach(function(wrap) {
            wrap.style.pointerEvents = 'auto';
            wrap.style.opacity = '1';
            if (!wrap._sigInited) { sigInit(wrap); wrap._sigInited = true; }
        });
    });
    // Unlock appraiser score inputs in Section 2
    document.querySelectorAll('.kpi-app-input').forEach(function(inp) {
        inp.removeAttribute('readonly');
        inp.style.pointerEvents = 'auto';
        inp.style.opacity = '';
        inp.style.background = '';
        inp.style.cursor = '';
    });
    // Unlock attendance score radios
    document.querySelectorAll('.att-score-radio').forEach(function(inp) {
        inp.style.pointerEvents = 'auto';
        inp.style.opacity = '';
        inp.style.cursor = '';
    });
}

// ── signature pad ─────────────────────────────────────────────────────────────
function sigInit(wrap) {
    var canvas  = wrap.querySelector('.sig-canvas');
    var hint    = wrap.querySelector('.sig-hint');
    var hidden  = wrap.querySelector('.sig-hidden');
    if (!canvas) return;
    var ctx  = canvas.getContext('2d');
    var drawing = false;
    var lastX = 0, lastY = 0;

    function pt(e) {
        var r = canvas.getBoundingClientRect();
        var src = e.touches ? e.touches[0] : e;
        return {
            x: (src.clientX - r.left) * (canvas.width  / r.width),
            y: (src.clientY - r.top)  * (canvas.height / r.height)
        };
    }
    function startDraw(e) {
        e.preventDefault();
        drawing = true;
        var p = pt(e);
        lastX = p.x; lastY = p.y;
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
    }
    function draw(e) {
        if (!drawing) return;
        e.preventDefault();
        var p = pt(e);
        ctx.lineWidth   = 2.5;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
        ctx.strokeStyle = '#1e293b';
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        lastX = p.x; lastY = p.y;
    }
    function endDraw(e) {
        if (!drawing) return;
        drawing = false;
        if (hint) hint.style.display = 'none';
        if (hidden) hidden.value = canvas.toDataURL('image/png');
    }

    canvas.addEventListener('mousedown',  startDraw);
    canvas.addEventListener('mousemove',  draw);
    canvas.addEventListener('mouseup',    endDraw);
    canvas.addEventListener('mouseleave', endDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove',  draw,      { passive: false });
    canvas.addEventListener('touchend',   endDraw);
}

function sigClear(btn) {
    var wrap   = btn.closest('.sig-pad-wrap');
    var canvas = wrap.querySelector('.sig-canvas');
    var hint   = wrap.querySelector('.sig-hint');
    var hidden = wrap.querySelector('.sig-hidden');
    if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    if (hint)   hint.style.display = '';
    if (hidden) hidden.value = '';
}

function sigUpload(input) {
    var wrap   = input.closest('.sig-pad-wrap');
    var canvas = wrap.querySelector('.sig-canvas');
    var hint   = wrap.querySelector('.sig-hint');
    var hidden = wrap.querySelector('.sig-hidden');
    if (!input.files || !input.files[0] || !canvas) return;
    var reader = new FileReader();
    reader.onload = function(ev) {
        var img = new Image();
        img.onload = function() {
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            // fit image inside canvas preserving aspect ratio
            var scale = Math.min(canvas.width / img.width, canvas.height / img.height);
            var w = img.width * scale, h = img.height * scale;
            ctx.drawImage(img, (canvas.width - w) / 2, (canvas.height - h) / 2, w, h);
            if (hint)   hint.style.display = 'none';
            if (hidden) hidden.value = canvas.toDataURL('image/png');
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(input.files[0]);
    input.value = '';
}

function restoreAllSigs() {
    document.querySelectorAll('.sig-pad-wrap').forEach(function(wrap) {
        var hidden = wrap.querySelector('.sig-hidden');
        var canvas = wrap.querySelector('.sig-canvas');
        var hint   = wrap.querySelector('.sig-hint');
        if (!hidden || !canvas || !hidden.value) return;
        var img = new Image();
        img.onload = function() {
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            if (hint) hint.style.display = 'none';
        };
        img.src = hidden.value;
    });
}

// Auto-init editable signature pads (those without pointer-events:none on the wrap itself)
document.querySelectorAll('.sig-pad-wrap').forEach(function(wrap) {
    if (wrap.style.pointerEvents !== 'none') {
        sigInit(wrap);
    }
});

// ── on page load ──────────────────────────────────────────────────────────────
if (_savedData) { restoreFormData(_savedData); restoreAllSigs(); updateS3(); }
updateS6();
if (_isAppraiserView) {
    // Lock whole page then unlock appraiser sections
    var mainEl = document.querySelector('main');
    if (mainEl) { mainEl.style.pointerEvents = 'none'; mainEl.style.opacity = '0.85'; }
    unlockAppraiserSections();
} else if (!_isWindowOpen || _isSubmitted) {
    lockForm();
}
</script>
</body>
</html>
