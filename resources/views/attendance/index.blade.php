<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance Import</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', sans-serif; }
        .tbl th  { background:#1a3d34;color:#fff;padding:9px 11px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.09em;white-space:nowrap; }
        .tbl td  { padding:7px 10px;border-bottom:1px solid rgba(107,144,128,.12);font-size:11px;vertical-align:middle; }
        .tbl tbody tr:hover { background:rgba(107,144,128,.04); }
        .badge { display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:6px;font-size:10px;font-weight:800;padding:0 5px; }
        .leaf-input { width:48px;border:1.5px solid rgba(107,144,128,.3);border-radius:7px;padding:3px 5px;font-size:10px;font-weight:600;color:#334155;text-align:center;background:#fff;outline:none; }
        .leaf-input:focus { border-color:#6B9080; }
        .month-card { border-radius:12px;padding:10px 12px;border:1.5px solid;display:flex;flex-direction:column;gap:4px; }
        .month-card.imported { background:#edf7f1;border-color:#6B9080; }
        .month-card.empty    { background:#f8f9fa;border-color:#e2e8f0; }
        .month-card.future   { background:#f1f5f9;border-color:#e2e8f0;opacity:.45; }
        .prev-tbl th { background:#1a3d34;color:#fff;padding:7px 10px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap; }
        .prev-tbl td { padding:6px 10px;border-bottom:1px solid rgba(107,144,128,.1);font-size:10.5px;vertical-align:middle; }
        .prev-tbl tbody tr:hover { background:rgba(107,144,128,.04); }
        .dept-row td { background:#e4f0eb;padding:6px 12px; }
        @media print { .no-print { display:none!important; } body { background:#fff!important; } }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen">

{{-- Header --}}
<div class="sticky top-0 z-30 px-4 pt-4 pb-2 bg-[#f0f2f7] no-print">
    <div class="rounded-[18px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#2d5548] text-white px-6 py-4 shadow-xl flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div>
                <h1 class="text-base font-black">Attendance Import & Analysis</h1>
                <p class="text-white/65 text-[10px] mt-0.5">Multi-month import from Google Sheet · Preview before saving</p>
            </div>
        </div>
        @if(isset($results))
        <button onclick="window.print()" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl font-bold text-xs transition border border-white/20 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
        </button>
        @endif
    </div>
</div>

<div class="px-4 py-4 max-w-full">

@php
    $monthNames  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $mStatus     = $monthStatus ?? [];
    $sYear       = $statusYear ?? now()->year;
    $curMonth    = now()->month;
    $defCompany  = $defaultCompany ?? 'RCG';
    $defaultUrl  = $sheetUrl ?? 'https://docs.google.com/spreadsheets/d/1idaGU_UfJ7tyoQtB65muFC5jKGYXZRshqUxb6ig5BbQ/edit';
@endphp

{{-- ═══ IMPORT ALL MONTHS ══════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm p-6 mb-5 no-print">
    <div class="flex items-center gap-2 mb-4">
        <span class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest">Import All Months</span>
        <span class="text-[9px] bg-[#1a3d34]/10 text-[#1a3d34] font-bold px-2 py-0.5 rounded-full">{{ $sYear }}</span>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Company</p>
            <select id="all-company" class="w-full border border-[#6B9080]/30 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 bg-white outline-none focus:border-[#6B9080]">
                @foreach(['RCG','RGHB','RCT'] as $co)
                <option value="{{ $co }}" {{ $defCompany===$co?'selected':'' }}>{{ $co }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Year</p>
            <select id="all-year" class="w-full border border-[#6B9080]/30 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 bg-white outline-none focus:border-[#6B9080]">
                @foreach([2025,2026,2027] as $y)
                <option value="{{ $y }}" {{ $sYear==$y?'selected':'' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col justify-end">
            <button onclick="fetchAllPreview()" id="importAllBtn"
                class="bg-[#1a3d34] hover:bg-[#2d5548] text-white px-5 py-2.5 rounded-xl font-bold text-sm transition flex items-center gap-2 justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Load Preview
            </button>
        </div>
    </div>

    <div>
        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Google Sheet URL</p>
        <input type="url" id="all-sheet-url" placeholder="https://docs.google.com/spreadsheets/d/…"
            value="{{ $defaultUrl }}"
            class="w-full border border-[#6B9080]/30 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white outline-none focus:border-[#6B9080]">
        <p class="text-[9px] text-slate-400 mt-1.5">Sheet must have tabs named <strong>January, February, March …</strong> Set sharing to <strong>"Anyone with link can view"</strong>.</p>
    </div>

    {{-- Loading indicator --}}
    <div id="loadingBar" class="mt-4" style="display:none;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div class="w-4 h-4 border-2 border-[#6B9080] border-t-transparent rounded-full animate-spin" style="flex-shrink:0;"></div>
            <span id="loadingMsg" class="text-[11px] font-semibold text-[#1a3d34]">Fetching attendance data from all months…</span>
        </div>
    </div>
</div>

{{-- ═══ PREVIEW PANEL (shown after fetch) ════════════════════════════════ --}}
<div id="previewPanel" class="hidden mb-5">

    {{-- Preview header --}}
    <div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm p-5 mb-4 no-print">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-1">Preview — Review before saving</p>
                <p id="previewSubtitle" class="text-xs text-slate-500"></p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="document.getElementById('previewPanel').classList.add('hidden')"
                    class="border border-slate-200 text-slate-500 hover:bg-slate-50 px-4 py-2 rounded-xl font-bold text-xs transition">
                    Cancel
                </button>
                <button onclick="saveAllMonths()" id="saveAllBtn"
                    class="bg-[#1a3d34] hover:bg-[#2d5548] text-white px-6 py-2 rounded-xl font-black text-sm transition shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save All to Database
                </button>
            </div>
        </div>

        {{-- Month summary strip --}}
        <div id="previewMonthStrip" class="mt-4 grid grid-cols-7 gap-2"></div>
    </div>

    {{-- Per-month employee tables --}}
    <div id="previewMonthSections"></div>

    {{-- Bottom save button --}}
    <div class="flex justify-end mt-3 no-print">
        <button onclick="saveAllMonths()" class="bg-[#1a3d34] hover:bg-[#2d5548] text-white px-8 py-3 rounded-xl font-black text-sm transition shadow-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Confirm & Save All Months
        </button>
    </div>
</div>

{{-- ═══ MONTH STATUS GRID ══════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm p-6 mb-5 no-print">
    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-4">Import Status — {{ $sYear }}</p>
    <div class="grid grid-cols-6 gap-3">
        @for($mi = 1; $mi <= 12; $mi++)
        @php
            $isImported = isset($mStatus[$mi]);
            $isFuture   = ($sYear == now()->year && $mi > $curMonth) || ($sYear > now()->year);
            $cardClass  = $isFuture ? 'future' : ($isImported ? 'imported' : 'empty');
            $lastImport = $isImported ? \Carbon\Carbon::parse($mStatus[$mi])->format('d M H:i') : null;
        @endphp
        <div class="month-card {{ $cardClass }}" id="month-card-{{ $mi }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black {{ $isImported?'text-[#1a3d34]':($isFuture?'text-slate-300':'text-slate-400') }}">
                    {{ substr($monthNames[$mi-1], 0, 3) }}
                </span>
                <span class="text-[8px]" id="month-check-{{ $mi }}">{{ $isImported?'✓':($isFuture?'':'—') }}</span>
            </div>
            <div class="text-[8px] font-medium leading-tight {{ $isImported?'text-[#6B9080]':'text-slate-300' }}" id="month-ts-{{ $mi }}">
                {{ $isImported ? $lastImport : ($isFuture ? 'Future' : 'Not imported') }}
            </div>
        </div>
        @endfor
    </div>
</div>

{{-- ═══ SINGLE-MONTH PREVIEW ═══════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm p-6 mb-5 no-print">
    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-1">Single Month Preview</p>
    <p class="text-[9px] text-slate-400 mb-4">Preview one month with daily detail grid, enter MC / AL / Other, then save.</p>

    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('attendance.import') }}" id="importForm">
        @csrf
        <div class="grid grid-cols-4 gap-4 items-end">
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Company</p>
                <select name="company" class="w-full border border-[#6B9080]/30 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 bg-white outline-none focus:border-[#6B9080]">
                    @foreach(['RCG','RGHB','RCT'] as $co)
                    <option value="{{ $co }}" {{ (isset($company)&&$company===$co)||(!isset($company)&&$defCompany===$co)?'selected':'' }}>{{ $co }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Month</p>
                <select name="month" class="w-full border border-[#6B9080]/30 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 bg-white outline-none focus:border-[#6B9080]">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ (($defaultMonth??date('n'))==$m)?'selected':'' }}>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Year</p>
                <select name="year" class="w-full border border-[#6B9080]/30 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 bg-white outline-none focus:border-[#6B9080]">
                    @foreach([2025,2026,2027] as $y)
                    <option value="{{ $y }}" {{ (($defaultYear??date('Y'))==$y)?'selected':'' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-4 mt-1">
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Google Sheet URL</p>
                <div class="flex gap-2">
                    <input type="url" name="sheet_url" required placeholder="https://docs.google.com/spreadsheets/d/…"
                        value="{{ $defaultUrl }}"
                        class="flex-1 border border-[#6B9080]/30 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white outline-none focus:border-[#6B9080]">
                    <button type="submit" id="importBtn" class="bg-[#6B9080] hover:bg-[#5a7a6d] text-white px-6 py-2.5 rounded-xl font-bold text-sm transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Preview Month
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@isset($results)

{{-- Summary Cards --}}
@php
    $totalEmp   = count($results);
    $totalLate  = collect($results)->sum('late_count');
    $totalAbsent= collect($results)->sum('absent_days');
    $monthLabel = \Carbon\Carbon::create(null, $month)->format('F') . ' ' . $year;
@endphp

<div class="grid grid-cols-4 gap-3 mb-4 no-print">
    @foreach([
        ['label'=>'Staff','val'=>$totalEmp,'icon'=>'👤','color'=>'#1a3d34'],
        ['label'=>'Working Days','val'=>$totalWorkingDays,'icon'=>'📅','color'=>'#6B9080'],
        ['label'=>'Total Late','val'=>$totalLate.' incidents','icon'=>'⏰','color'=>'#d97706'],
        ['label'=>'Total Absent','val'=>$totalAbsent.' days','icon'=>'❌','color'=>'#dc2626'],
    ] as $card)
    <div class="bg-white rounded-2xl border border-[#6B9080]/20 px-5 py-4 flex items-center gap-3">
        <span class="text-2xl">{{ $card['icon'] }}</span>
        <div>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $card['label'] }}</p>
            <p class="text-xl font-black" style="color:{{ $card['color'] }}">{{ $card['val'] }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="flex items-center gap-3 mb-3 no-print">
    <span class="text-xs font-black text-[#1a3d34]">{{ $monthLabel }}</span>
    <span class="text-[9px] bg-[#1a3d34]/10 text-[#1a3d34] font-bold px-2 py-0.5 rounded-full">Single-month preview — enter MC / AL / Other Leave then Save</span>
</div>

{{-- Legend --}}
<div class="flex items-center gap-4 mb-3 no-print flex-wrap">
    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Legend:</p>
    @foreach([['bg-emerald-100 text-emerald-700','Present'],['bg-amber-100 text-amber-700','Late'],['bg-red-100 text-red-700','Absent'],['bg-blue-100 text-blue-700','MC'],['bg-purple-100 text-purple-700','AL'],['bg-slate-100 text-slate-500','PH/Weekend']] as [$cls,$lbl])
    <span class="badge {{ $cls }}">{{ $lbl }}</span>
    @endforeach
    <p class="text-[9px] text-slate-400 ml-auto">Late = clock-in after 8:30 AM</p>
</div>

{{-- Daily table --}}
<div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm overflow-hidden mb-5">
    <div class="overflow-x-auto">
    <table class="tbl w-full">
        <thead>
            <tr>
                <th class="text-left sticky left-0 bg-[#1a3d34]" style="min-width:160px;">Name</th>
                <th class="text-left" style="min-width:90px;">Dept</th>
                <th class="text-center">Work</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Late Dur.</th>
                <th class="text-center">MC<br><span class="font-normal text-[8px] normal-case tracking-normal opacity-70">key in</span></th>
                <th class="text-center">AL<br><span class="font-normal text-[8px] normal-case tracking-normal opacity-70">key in</span></th>
                <th class="text-center">Other<br><span class="font-normal text-[8px] normal-case tracking-normal opacity-70">key in</span></th>
                <th class="text-center">AWOL<br><span class="font-normal text-[8px] normal-case tracking-normal opacity-70">auto</span></th>
                @foreach($workingDays as $wd)
                <th class="text-center" style="min-width:32px;font-size:8px;padding:6px 3px;">
                    {{ \Carbon\Carbon::parse($wd)->format('d') }}<br>
                    <span class="font-normal opacity-70">{{ \Carbon\Carbon::parse($wd)->format('D') }}</span>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @php $prevDept = null; @endphp
        @foreach($results as $eid => $emp)
        @if($emp['department'] !== $prevDept)
        @php $prevDept = $emp['department']; @endphp
        <tr><td colspan="{{ 11 + count($workingDays) }}" class="bg-[#e4f0eb] py-2 px-4">
            <span class="text-[9px] font-black text-[#1a3d34] uppercase tracking-widest">▸ {{ $emp['department'] ?: 'Unassigned' }}</span>
        </td></tr>
        @endif
        <tr class="emp-row" data-eid="{{ $eid }}">
            <td class="sticky left-0 bg-white font-bold text-slate-800" style="min-width:160px;">
                {{ $emp['preferred_name'] ?: explode(' ',$emp['name'])[0] }}
                <div class="text-[9px] text-slate-400 font-normal">{{ $emp['internal_id'] }}</div>
            </td>
            <td class="text-[10px] text-slate-500">{{ $emp['department'] ?: '—' }}</td>
            <td class="text-center font-bold text-[#1a3d34]">{{ $emp['working_days'] }}</td>
            <td class="text-center"><span class="badge bg-emerald-100 text-emerald-700">{{ $emp['present_days'] }}</span></td>
            <td class="text-center">
                @if($emp['absent_days'] > 0)<span class="badge bg-red-100 text-red-700">{{ $emp['absent_days'] }}</span>
                @else<span class="text-slate-300">—</span>@endif
            </td>
            <td class="text-center">
                @if($emp['late_count'] > 0)<span class="badge bg-amber-100 text-amber-700">{{ $emp['late_count'] }}×</span>
                @else<span class="text-slate-300">—</span>@endif
            </td>
            <td class="text-center text-[10px] {{ $emp['total_late_minutes']>0?'text-amber-700 font-bold':'text-slate-300' }}">
                @if($emp['total_late_minutes'] > 0){{ floor($emp['total_late_minutes']/60) }}h {{ $emp['total_late_minutes']%60 }}m
                @else—@endif
            </td>
            <td class="text-center"><input type="number" min="0" max="{{ $emp['absent_days'] }}" value="0" class="leaf-input mc-input" data-eid="{{ $eid }}"></td>
            <td class="text-center"><input type="number" min="0" max="{{ $emp['absent_days'] }}" value="0" class="leaf-input al-input" data-eid="{{ $eid }}"></td>
            <td class="text-center"><input type="number" min="0" max="{{ $emp['absent_days'] }}" value="0" class="leaf-input other-input" data-eid="{{ $eid }}"></td>
            <td class="text-center awol-cell font-bold text-red-600" data-absent="{{ $emp['absent_days'] }}">{{ $emp['absent_days'] }}</td>
            @foreach($workingDays as $wd)
            @php $day = $emp['daily'][$wd] ?? ['status'=>'absent','clock_in'=>null,'is_late'=>false,'late_minutes'=>0]; @endphp
            <td class="text-center" style="padding:4px 2px;">
                @if($day['status']==='present')
                <div class="mx-auto w-7 h-7 rounded-lg flex items-center justify-center text-[8px] font-bold {{ $day['is_late']?'bg-amber-100 text-amber-700':'bg-emerald-100 text-emerald-700' }}"
                     title="{{ $day['clock_in'] }}{{ $day['is_late']?' · Late '.floor($day['late_minutes']/60).'h'.($day['late_minutes']%60).'m':'' }}">
                    {{ $day['clock_in'] }}
                </div>
                @else
                <div class="mx-auto w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center"><span class="text-red-300 text-xs">✕</span></div>
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="flex justify-between items-center mb-8 no-print">
    <p class="text-[11px] text-slate-400">After entering MC / AL / Other Leave days, click <strong>Save</strong> to store {{ $monthLabel }} data.</p>
    <button onclick="saveSingleMonth()" class="bg-[#1a3d34] hover:bg-[#2d5548] text-white px-8 py-3 rounded-xl font-black text-sm transition shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save {{ \Carbon\Carbon::create(null,$month)->format('F') }} Data
    </button>
</div>

<script>
const RAW_RESULTS = @json($results);
const MONTH       = {{ $month }};
const YEAR        = {{ $year }};
const COMPANY     = "{{ $company }}";
const SHEET_URL   = "{{ $sheetUrl ?? '' }}";
</script>

@endisset

</div>
</main>

<script>
// ── AWOL auto-compute (single month) ─────────────────────────────────────
document.querySelectorAll('.mc-input, .al-input, .other-input').forEach(function(inp) {
    inp.addEventListener('input', function() {
        var row   = document.querySelector('tr[data-eid="' + this.dataset.eid + '"]');
        var mc    = parseInt(row.querySelector('.mc-input').value)    || 0;
        var al    = parseInt(row.querySelector('.al-input').value)    || 0;
        var other = parseInt(row.querySelector('.other-input').value) || 0;
        var cell  = row.querySelector('.awol-cell');
        var awol  = Math.max(0, parseInt(cell.dataset.absent) - mc - al - other);
        cell.textContent = awol;
        cell.className = 'text-center awol-cell font-bold ' + (awol > 0 ? 'text-red-600' : 'text-emerald-600');
    });
});

// ── Single-month import loading state ────────────────────────────────────
document.getElementById('importForm')?.addEventListener('submit', function() {
    var btn = document.getElementById('importBtn');
    btn.textContent = 'Loading…';
    btn.disabled = true;
    btn.classList.add('opacity-70');
});

// ── Save single month ─────────────────────────────────────────────────────
function saveSingleMonth() {
    if (typeof RAW_RESULTS === 'undefined') return;
    var records = [];
    document.querySelectorAll('tr[data-eid]').forEach(function(row) {
        var eid = row.dataset.eid;
        var emp = RAW_RESULTS[eid];
        if (!emp) return;
        records.push({
            internal_id:        eid,
            db_employee_id:     emp.db_employee_id || null,
            working_days:       emp.working_days,
            present_days:       emp.present_days,
            absent_days:        emp.absent_days,
            late_count:         emp.late_count,
            total_late_minutes: emp.total_late_minutes,
            mc_days:            parseInt(row.querySelector('.mc-input')?.value)    || 0,
            al_days:            parseInt(row.querySelector('.al-input')?.value)    || 0,
            other_leave_days:   parseInt(row.querySelector('.other-input')?.value) || 0,
        });
    });
    var csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
    fetch("{{ route('attendance.save') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ records, month: MONTH, year: YEAR, company: COMPANY, sheet_url: SHEET_URL }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('✅ Saved ' + d.count + ' records.');
            markMonthImported(MONTH);
        } else alert('❌ Error saving. Please try again.');
    })
    .catch(() => alert('❌ Network error.'));
}

// ── Fetch all-month preview ───────────────────────────────────────────────
var PREVIEW_DATA = null;

function fetchAllPreview() {
    var url     = document.getElementById('all-sheet-url').value.trim();
    var company = document.getElementById('all-company').value;
    var year    = document.getElementById('all-year').value;
    var csrf    = document.querySelector('meta[name=csrf-token]')?.content || '';

    if (!url) { alert('Please enter the Google Sheet URL.'); return; }

    var btn = document.getElementById('importAllBtn');
    btn.disabled = true;
    btn.classList.add('opacity-60');
    document.getElementById('loadingBar').style.display = 'block';
    document.getElementById('previewPanel').classList.add('hidden');

    fetch("{{ route('attendance.import-all') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ sheet_url: url, company: company, year: parseInt(year) }),
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('loadingBar').style.display = 'none';
        btn.disabled = false;
        btn.classList.remove('opacity-60');

        if (!d.success) {
            alert('❌ ' + (d.error || 'Failed to fetch data.'));
            return;
        }

        PREVIEW_DATA = d;
        renderPreview(d);
    })
    .catch(() => {
        document.getElementById('loadingBar').style.display = 'none';
        btn.disabled = false;
        btn.classList.remove('opacity-60');
        alert('❌ Network error. Check URL and try again.');
    });
}

// ── Render preview panel ──────────────────────────────────────────────────
function renderPreview(data) {
    var months   = data.months;
    var year     = data.year;
    var company  = data.company;
    var mNames   = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var strip    = document.getElementById('previewMonthStrip');
    var sections = document.getElementById('previewMonthSections');
    strip.innerHTML = '';
    sections.innerHTML = '';

    var successCount = 0;
    var totalStaff   = 0;

    Object.entries(months).forEach(function([mn, mData]) {
        var mi = parseInt(mn);
        if (!mData.success) {
            strip.innerHTML += '<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center opacity-50">'
                + '<p class="text-[9px] font-black text-slate-400">' + mNames[mi-1].substring(0,3) + '</p>'
                + '<p class="text-[8px] text-red-400 mt-0.5">No data</p></div>';
            return;
        }
        successCount++;
        var staff = mData.employees.length;
        totalStaff = Math.max(totalStaff, staff);
        var totalLate   = mData.employees.reduce((s,e) => s + e.late_count, 0);
        var totalAbsent = mData.employees.reduce((s,e) => s + e.absent_days, 0);

        // Month strip card
        strip.innerHTML += '<div class="rounded-xl border border-[#6B9080] bg-[#edf7f1] px-3 py-2 text-center">'
            + '<p class="text-[9px] font-black text-[#1a3d34]">' + mNames[mi-1].substring(0,3) + '</p>'
            + '<p class="text-[8px] text-[#6B9080] font-bold mt-0.5">' + staff + ' staff</p>'
            + '<p class="text-[7px] text-amber-600 font-semibold">' + totalLate + ' late</p></div>';

        // Month section
        var rows = '';
        var prevDept = null;
        mData.employees.forEach(function(emp, idx) {
            if (emp.department !== prevDept) {
                prevDept = emp.department;
                rows += '<tr class="dept-row"><td colspan="9"><span style="font-size:9px;font-weight:900;color:#1a3d34;text-transform:uppercase;letter-spacing:.08em;">▸ '
                    + (emp.department || 'Unassigned') + '</span></td></tr>';
            }
            var lateDur = emp.total_late_minutes > 0
                ? (Math.floor(emp.total_late_minutes/60) + 'h ' + (emp.total_late_minutes%60) + 'm')
                : '—';
            rows += '<tr>'
                + '<td class="font-semibold text-slate-800" style="font-size:11px;">' + (emp.preferred_name || emp.name.split(' ')[0])
                    + '<div style="font-size:9px;color:#94a3b8;font-weight:400;">' + emp.internal_id + '</div></td>'
                + '<td style="text-align:center;"><span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:6px;font-size:10px;font-weight:800;padding:0 5px;background:#d1fae5;color:#065f46;">' + emp.present_days + '</span></td>'
                + '<td style="text-align:center;">' + (emp.late_count > 0 ? '<span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:6px;font-size:10px;font-weight:800;padding:0 5px;background:#fef3c7;color:#92400e;">' + emp.late_count + '×</span>' : '<span style="color:#cbd5e1;">—</span>') + '</td>'
                + '<td style="text-align:center;font-size:10px;font-weight:' + (emp.total_late_minutes>0?'700':'400') + ';color:' + (emp.total_late_minutes>0?'#b45309':'#cbd5e1') + ';">' + lateDur + '</td>'
                + '<td style="text-align:center;">' + (emp.absent_days > 0 ? '<span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:6px;font-size:10px;font-weight:800;padding:0 5px;background:#fee2e2;color:#991b1b;">' + emp.absent_days + '</span>' : '<span style="color:#cbd5e1;">—</span>') + '</td>'
                + '<td style="text-align:center;"><input type="number" min="0" value="0" class="leaf-input prev-mc" data-month="' + mi + '" data-eid="' + emp.internal_id + '" style="width:44px;"></td>'
                + '<td style="text-align:center;"><input type="number" min="0" value="0" class="leaf-input prev-al" data-month="' + mi + '" data-eid="' + emp.internal_id + '" style="width:44px;"></td>'
                + '<td style="text-align:center;"><input type="number" min="0" value="0" class="leaf-input prev-other" data-month="' + mi + '" data-eid="' + emp.internal_id + '" style="width:44px;"></td>'
                + '<td style="text-align:center;font-size:10px;font-weight:700;" class="prev-awol-' + mi + '-' + emp.internal_id.replace(/[^a-z0-9]/gi,'_') + '" data-absent="' + emp.absent_days + '">'
                    + emp.absent_days + '</td>'
                + '</tr>';
        });

        var sectionHtml = '<div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm overflow-hidden mb-3">'
            + '<div style="background:#1a3d34;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;">'
            + '<div style="display:flex;align-items:center;gap:10px;">'
            + '<span style="color:#fff;font-size:11px;font-weight:900;">' + mNames[mi-1] + ' ' + year + '</span>'
            + '<span style="background:rgba(255,255,255,.15);color:#fff;font-size:9px;font-weight:700;border-radius:6px;padding:2px 8px;">' + staff + ' employees · ' + mData.working_days + ' working days</span>'
            + '<span style="background:rgba(255,255,255,.15);color:#fbbf24;font-size:9px;font-weight:700;border-radius:6px;padding:2px 8px;">' + totalLate + ' late incidents</span>'
            + '</div>'
            + '<button onclick="this.closest(\'.bg-white\').querySelector(\'table\').closest(\'div\').classList.toggle(\'hidden\')" style="color:rgba(255,255,255,.7);font-size:10px;font-weight:600;">Toggle ▲▼</button>'
            + '</div>'
            + '<div class="overflow-x-auto">'
            + '<table class="prev-tbl w-full">'
            + '<thead><tr>'
            + '<th class="text-left" style="min-width:150px;">Name</th>'
            + '<th style="text-align:center;">Present</th>'
            + '<th style="text-align:center;">Late</th>'
            + '<th style="text-align:center;">Late Duration</th>'
            + '<th style="text-align:center;">Absent</th>'
            + '<th style="text-align:center;">MC<br><span style="font-size:8px;font-weight:400;text-transform:none;letter-spacing:0;">key in</span></th>'
            + '<th style="text-align:center;">AL<br><span style="font-size:8px;font-weight:400;text-transform:none;letter-spacing:0;">key in</span></th>'
            + '<th style="text-align:center;">Other<br><span style="font-size:8px;font-weight:400;text-transform:none;letter-spacing:0;">key in</span></th>'
            + '<th style="text-align:center;">AWOL<br><span style="font-size:8px;font-weight:400;text-transform:none;letter-spacing:0;">auto</span></th>'
            + '</tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table></div></div>';

        sections.innerHTML += sectionHtml;
    });

    document.getElementById('previewSubtitle').textContent =
        successCount + ' months loaded · ' + company + ' · ' + year + '  —  Enter MC / AL / Other Leave then click Save All.';
    document.getElementById('previewPanel').classList.remove('hidden');
    document.getElementById('previewPanel').scrollIntoView({ behavior: 'smooth' });
}

// ── Save all months ───────────────────────────────────────────────────────
function saveAllMonths() {
    if (!PREVIEW_DATA) return;

    var months  = PREVIEW_DATA.months;
    var payload = { year: PREVIEW_DATA.year, company: PREVIEW_DATA.company, months: {} };

    Object.entries(months).forEach(function([mn, mData]) {
        if (!mData.success) return;
        var mi = parseInt(mn);
        var employees = mData.employees.map(function(emp) {
            var mcEl    = document.querySelector('.prev-mc[data-month="' + mi + '"][data-eid="' + emp.internal_id + '"]');
            var alEl    = document.querySelector('.prev-al[data-month="' + mi + '"][data-eid="' + emp.internal_id + '"]');
            var otherEl = document.querySelector('.prev-other[data-month="' + mi + '"][data-eid="' + emp.internal_id + '"]');
            return Object.assign({}, emp, {
                mc_days:          parseInt(mcEl?.value)    || 0,
                al_days:          parseInt(alEl?.value)    || 0,
                other_leave_days: parseInt(otherEl?.value) || 0,
            });
        });
        payload.months[mi] = { employees: employees };
    });

    var btn  = document.getElementById('saveAllBtn');
    btn.textContent = 'Saving…';
    btn.disabled = true;

    var csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
    fetch("{{ route('attendance.save-all') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
        btn.textContent = 'Save All to Database';
        btn.disabled = false;
        if (d.success) {
            alert('✅ Saved ' + d.saved + ' records across all months!');
            document.getElementById('previewPanel').classList.add('hidden');
            // Update status grid
            Object.keys(payload.months).forEach(function(mi) {
                markMonthImported(parseInt(mi));
            });
        } else {
            alert('❌ Error saving. Please try again.');
        }
    })
    .catch(() => {
        btn.textContent = 'Save All to Database';
        btn.disabled = false;
        alert('❌ Network error.');
    });
}

// ── Update month card in status grid ─────────────────────────────────────
function markMonthImported(mi) {
    var card  = document.getElementById('month-card-' + mi);
    var check = document.getElementById('month-check-' + mi);
    var ts    = document.getElementById('month-ts-' + mi);
    if (!card) return;
    card.className = 'month-card imported';
    card.querySelector('span').classList.remove('text-slate-400','text-slate-300');
    card.querySelector('span').classList.add('text-[#1a3d34]');
    if (check) check.textContent = '✓';
    if (ts) {
        var now = new Date();
        var mNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        ts.textContent = now.getDate().toString().padStart(2,'0') + ' ' + mNames[now.getMonth()] + ' ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
        ts.classList.remove('text-slate-300');
        ts.classList.add('text-[#6B9080]');
    }
}
</script>

</body>
</html>
