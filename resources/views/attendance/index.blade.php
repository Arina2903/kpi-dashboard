<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Import</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', sans-serif; }
        .tbl th  { background:#1a3d34;color:#fff;padding:9px 11px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.09em;white-space:nowrap; }
        .tbl td  { padding:7px 10px;border-bottom:1px solid rgba(107,144,128,.12);font-size:11px;vertical-align:middle; }
        .tbl tbody tr:hover { background:rgba(107,144,128,.04); }
        .badge { display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:6px;font-size:10px;font-weight:800;padding:0 5px; }
        .leaf-input { width:52px;border:1.5px solid rgba(107,144,128,.3);border-radius:7px;padding:3px 6px;font-size:11px;font-weight:600;color:#334155;text-align:center;background:#fff;outline:none; }
        .leaf-input:focus { border-color:#6B9080; }
        @media print {
            .no-print { display:none!important; }
            body { background:#fff!important; }
        }
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
                <p class="text-white/65 text-[10px] mt-0.5">Import from Google Sheet · Calculate lateness, absent, MC, AL</p>
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

{{-- Import Form --}}
<div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm p-6 mb-5 no-print">
    <p class="text-[10px] font-black text-[#6B9080] uppercase tracking-widest mb-4">Import Attendance Data</p>

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
                    <option value="{{ $co }}" {{ (isset($company)&&$company===$co)?'selected':'' }}>{{ $co }}</option>
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
                        value="{{ $sheetUrl ?? '' }}"
                        class="flex-1 border border-[#6B9080]/30 rounded-xl px-4 py-2.5 text-sm text-slate-700 bg-white outline-none focus:border-[#6B9080]">
                    <button type="submit" id="importBtn" class="bg-[#1a3d34] hover:bg-[#2d5548] text-white px-6 py-2.5 rounded-xl font-bold text-sm transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import & Calculate
                    </button>
                </div>
                <p class="text-[9px] text-slate-400 mt-1.5">Make sure the sheet is set to <strong>"Anyone with link can view"</strong>. Columns needed: Internal Id, First Name, Last Name, Preferred Name, Email, Clock In Time, Clock In Date.</p>
            </div>
        </div>
    </form>
</div>

@isset($results)

{{-- Summary Cards --}}
@php
    $totalEmp     = count($results);
    $totalPresent = collect($results)->avg('present_days');
    $totalLate    = collect($results)->sum('late_count');
    $totalAbsent  = collect($results)->sum('absent_days');
    $monthName    = \Carbon\Carbon::create(null, $month)->format('F');
@endphp

<div class="grid grid-cols-4 gap-3 mb-5 no-print">
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

{{-- Legend --}}
<div class="flex items-center gap-4 mb-3 no-print flex-wrap">
    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Legend:</p>
    @foreach([['bg-emerald-100 text-emerald-700','Present'],['bg-amber-100 text-amber-700','Late'],['bg-red-100 text-red-700','Absent'],['bg-blue-100 text-blue-700','MC'],['bg-purple-100 text-purple-700','AL'],['bg-slate-100 text-slate-500','PH/Weekend']] as [$cls,$lbl])
    <span class="badge {{ $cls }}">{{ $lbl }}</span>
    @endforeach
    <p class="text-[9px] text-slate-400 ml-auto">Working hours: 8:30 AM – 5:30 PM · Late = clock-in after 8:30</p>
</div>

{{-- Main Table --}}
<div class="bg-white rounded-2xl border border-[#6B9080]/25 shadow-sm overflow-hidden mb-5">
    <div class="overflow-x-auto">
    <table class="tbl w-full">
        <thead>
            <tr>
                <th class="text-left sticky left-0 bg-[#1a3d34]" style="min-width:160px;">Name</th>
                <th class="text-left" style="min-width:100px;">Dept</th>
                <th class="text-center">Work Days</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Late Duration</th>
                <th class="text-center">MC<br><span class="font-normal text-[8px] normal-case tracking-normal opacity-70">key in</span></th>
                <th class="text-center">AL<br><span class="font-normal text-[8px] normal-case tracking-normal opacity-70">key in</span></th>
                <th class="text-center">Other Leave<br><span class="font-normal text-[8px] normal-case tracking-normal opacity-70">key in</span></th>
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
        <tr>
            <td colspan="{{ 11 + count($workingDays) }}" class="bg-[#e4f0eb] py-2 px-4">
                <span class="text-[9px] font-black text-[#1a3d34] uppercase tracking-widest">▸ {{ $emp['department'] ?: 'Unassigned' }}</span>
            </td>
        </tr>
        @endif
        <tr class="emp-row" data-eid="{{ $eid }}">
            <td class="sticky left-0 bg-white font-bold text-slate-800" style="min-width:160px;">
                {{ $emp['preferred_name'] ?: explode(' ',$emp['name'])[0] }}
                <div class="text-[9px] text-slate-400 font-normal">{{ $emp['internal_id'] }}</div>
            </td>
            <td class="text-[10px] text-slate-500">{{ $emp['department'] ?: '—' }}</td>
            <td class="text-center font-bold text-[#1a3d34]">{{ $emp['working_days'] }}</td>
            <td class="text-center">
                <span class="badge bg-emerald-100 text-emerald-700">{{ $emp['present_days'] }}</span>
            </td>
            <td class="text-center">
                @if($emp['absent_days'] > 0)
                <span class="badge bg-red-100 text-red-700">{{ $emp['absent_days'] }}</span>
                @else
                <span class="text-slate-300">—</span>
                @endif
            </td>
            <td class="text-center">
                @if($emp['late_count'] > 0)
                <span class="badge bg-amber-100 text-amber-700">{{ $emp['late_count'] }}×</span>
                @else
                <span class="text-slate-300">—</span>
                @endif
            </td>
            <td class="text-center text-[10px] {{ $emp['total_late_minutes']>0?'text-amber-700 font-bold':'text-slate-300' }}">
                @if($emp['total_late_minutes'] > 0)
                {{ floor($emp['total_late_minutes']/60) }}h {{ $emp['total_late_minutes']%60 }}m
                @else —
                @endif
            </td>
            <td class="text-center">
                <input type="number" min="0" max="{{ $emp['absent_days'] }}" value="0"
                    class="leaf-input mc-input" data-eid="{{ $eid }}">
            </td>
            <td class="text-center">
                <input type="number" min="0" max="{{ $emp['absent_days'] }}" value="0"
                    class="leaf-input al-input" data-eid="{{ $eid }}">
            </td>
            <td class="text-center">
                <input type="number" min="0" max="{{ $emp['absent_days'] }}" value="0"
                    class="leaf-input other-input" data-eid="{{ $eid }}">
            </td>
            <td class="text-center awol-cell font-bold text-red-600" data-absent="{{ $emp['absent_days'] }}">
                {{ $emp['absent_days'] }}
            </td>
            {{-- Daily cells --}}
            @foreach($workingDays as $wd)
            @php $day = $emp['daily'][$wd] ?? ['status'=>'absent','clock_in'=>null,'is_late'=>false,'late_minutes'=>0]; @endphp
            <td class="text-center" style="padding:4px 2px;">
                @if($day['status']==='present')
                    <div class="mx-auto w-7 h-7 rounded-lg flex items-center justify-center text-[8px] font-bold {{ $day['is_late']?'bg-amber-100 text-amber-700':'bg-emerald-100 text-emerald-700' }}"
                         title="{{ $day['clock_in'] }}{{ $day['is_late']?' · Late '.floor($day['late_minutes']/60).'h'.($day['late_minutes']%60).'m':'' }}">
                        {{ $day['clock_in'] }}
                    </div>
                @else
                    <div class="mx-auto w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center" title="Absent">
                        <span class="text-red-300 text-xs">✕</span>
                    </div>
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

{{-- Save Button --}}
<div class="flex justify-between items-center mb-8 no-print">
    <p class="text-[11px] text-slate-400">After entering MC / AL / Other Leave days, click <strong>Save Finalized Data</strong> to store in the system.</p>
    <button onclick="saveAttendance()" class="bg-[#1a3d34] hover:bg-[#2d5548] text-white px-8 py-3 rounded-xl font-black text-sm transition shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Finalized Data
    </button>
</div>

{{-- Hidden data for JS --}}
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
// ── Auto-compute AWOL when MC/AL/Other changes ───────────────────────────
document.querySelectorAll('.mc-input, .al-input, .other-input').forEach(function(inp) {
    inp.addEventListener('input', function() {
        var eid   = this.dataset.eid;
        var row   = document.querySelector('tr[data-eid="' + eid + '"]');
        var mc    = parseInt(row.querySelector('.mc-input').value)    || 0;
        var al    = parseInt(row.querySelector('.al-input').value)    || 0;
        var other = parseInt(row.querySelector('.other-input').value) || 0;
        var cell  = row.querySelector('.awol-cell');
        var absent = parseInt(cell.dataset.absent) || 0;
        var awol  = Math.max(0, absent - mc - al - other);
        cell.textContent = awol;
        cell.className = 'text-center awol-cell font-bold ' + (awol > 0 ? 'text-red-600' : 'text-emerald-600');
    });
});

// ── Import button loading state ───────────────────────────────────────────
document.getElementById('importForm')?.addEventListener('submit', function() {
    var btn = document.getElementById('importBtn');
    btn.textContent = 'Importing…';
    btn.disabled = true;
    btn.classList.add('opacity-70');
});

// ── Save finalized data ───────────────────────────────────────────────────
function saveAttendance() {
    if (typeof RAW_RESULTS === 'undefined') return;

    var records = [];
    document.querySelectorAll('tr[data-eid]').forEach(function(row) {
        var eid = row.dataset.eid;
        var emp = RAW_RESULTS[eid];
        if (!emp) return;
        var mc    = parseInt(row.querySelector('.mc-input')?.value)    || 0;
        var al    = parseInt(row.querySelector('.al-input')?.value)    || 0;
        var other = parseInt(row.querySelector('.other-input')?.value) || 0;
        records.push({
            internal_id:        eid,
            db_employee_id:     emp.db_employee_id || null,
            working_days:       emp.working_days,
            present_days:       emp.present_days,
            absent_days:        emp.absent_days,
            late_count:         emp.late_count,
            total_late_minutes: emp.total_late_minutes,
            mc_days:            mc,
            al_days:            al,
            other_leave_days:   other,
        });
    });

    fetch("{{ route('attendance.save') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
        },
        body: JSON.stringify({
            records:   records,
            month:     MONTH,
            year:      YEAR,
            company:   COMPANY,
            sheet_url: SHEET_URL,
        }),
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            alert('✅ Saved ' + d.count + ' records successfully!');
        } else {
            alert('❌ Error saving data. Please try again.');
        }
    })
    .catch(function() { alert('❌ Network error. Please try again.'); });
}
</script>

<meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>
