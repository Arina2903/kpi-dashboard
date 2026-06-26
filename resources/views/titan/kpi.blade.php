<!DOCTYPE html>
<html>
<head>
    <title>Titan KPI Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #sidebar, #sidebar * { font-family: 'Inter', sans-serif; }
        .month-row:nth-child(even) { background: rgba(248,250,252,.7); }
        .score-bar { height: 4px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .score-fill { height: 100%; border-radius: 999px; transition: width .4s ease; }
        @media print {
            #sidebar, #syncBtn, .no-print { display: none !important; }
            #mainContent { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#f0f4f8]">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300 bg-[#f0f4f8]">
<div class="p-6 space-y-5">

    {{-- ── HEADER ─────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-gradient-to-r from-[#06142f] via-[#0a2342] to-[#0e3460] text-white px-8 py-7 shadow-xl">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div>
                <a href="{{ route('kpi.my-department-kpi') }}" class="text-xs text-blue-300 hover:text-white font-semibold">← My Department KPI</a>
                <h1 class="text-3xl font-black mt-3 tracking-tight">Titan KPI Dashboard</h1>
                <p class="text-white/60 text-xs mt-1 font-medium">Financial Performance · RCG · {{ $fy }}</p>
            </div>
            <div class="flex flex-wrap gap-3 items-center">
                <div class="bg-white/10 rounded-2xl px-5 py-3 text-center min-w-[80px]">
                    <p class="text-[9px] text-blue-200 uppercase font-black tracking-wider">Staff</p>
                    <h3 class="text-2xl font-black mt-1">{{ count($allStaff) }}</h3>
                </div>
                <div class="bg-white/10 rounded-2xl px-5 py-3 text-center min-w-[80px]">
                    <p class="text-[9px] text-blue-200 uppercase font-black tracking-wider">KPIs</p>
                    <h3 class="text-2xl font-black mt-1">2</h3>
                </div>
                @if($isManager)
                <button id="syncBtn" onclick="syncFromSheet()"
                    class="no-print flex items-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-black px-5 py-3 rounded-2xl shadow-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync from Google Sheet
                </button>
                @endif
            </div>
        </div>

        {{-- Guide for executive --}}
        @if(!$isManager)
        <div class="mt-5 bg-white/10 border border-white/20 rounded-xl px-5 py-3 flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-300 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-[12px] text-blue-100">Your KPI performance is automatically synced from the collection tracking sheet by your manager. <strong>Actual</strong> = total collected · <strong>Base Target</strong> = potential revenue from all your clients.</p>
        </div>
        @endif
    </div>

    {{-- ── LAST SYNC INFO ──────────────────────────────────────────────── --}}
    @php
        $lastSynced = null;
        foreach ($monthlyData as $empData) {
            foreach ($empData as $kpiData) {
                foreach ($kpiData as $row) {
                    if (!$lastSynced || $row['synced_at'] > $lastSynced) $lastSynced = $row['synced_at'];
                }
            }
        }
    @endphp
    @if($lastSynced)
    <p class="text-[11px] text-slate-400 font-medium px-1">
        Last synced: {{ \Carbon\Carbon::parse($lastSynced)->format('d M Y, h:i A') }}
    </p>
    @endif

    {{-- ── STAFF TABS (manager sees all; executive sees only self) ─────── --}}
    @if($isManager && count($viewStaff) > 1)
    <div class="flex flex-wrap gap-2 no-print" id="staffTabs">
        <button onclick="switchStaff('all')" id="tab-all"
            class="staff-tab active-tab px-4 py-2 rounded-xl text-xs font-black border-2 border-[#06142f] bg-[#06142f] text-white transition">
            All Staff
        </button>
        @foreach($viewStaff as $emp)
        <button onclick="switchStaff('{{ $emp['id'] }}')" id="tab-{{ $emp['id'] }}"
            class="staff-tab px-4 py-2 rounded-xl text-xs font-black border-2 border-slate-200 text-slate-600 hover:border-[#06142f] hover:text-[#06142f] transition">
            {{ $emp['short_name'] ?? $emp['employee_id'] }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- ── KPI TABLES PER EMPLOYEE ────────────────────────────────────── --}}
    @php
        $kpiDefs = \App\Http\Controllers\TitanKpiController::KPIS;
        $months  = \App\Http\Controllers\TitanKpiController::MONTHS;
        $currentMonth = (int) now()->format('n');
    @endphp

    @foreach($viewStaff as $emp)
    @php
        $empData = $monthlyData[$emp['id']] ?? [];

        // Compute YTD totals for summary cards
        $ytdRevActual = $ytdRevTarget = $ytdRetActual = $ytdRetTarget = 0;
        $ytdFinalScore = 0; $scoreCount = 0;
        foreach (['revenue','retention'] as $kk) {
            foreach ($empData[$kk] ?? [] as $mn => $r) {
                if ($kk === 'revenue') { $ytdRevActual += $r['actual']; $ytdRevTarget += $r['base_target']; }
                else                  { $ytdRetActual += $r['actual']; $ytdRetTarget += $r['base_target']; }
                $sc = $r['base_target'] > 0 ? min(100, ($r['actual'] / $r['base_target']) * 100) : 0;
                $ytdFinalScore += $sc * $r['weightage'] / 100;
                $scoreCount++;
            }
        }
        $ytdRevScore = $ytdRevTarget > 0 ? min(100, ($ytdRevActual / $ytdRevTarget) * 100) : 0;
        $ytdRetScore = $ytdRetTarget > 0 ? min(100, ($ytdRetActual / $ytdRetTarget) * 100) : 0;
    @endphp

    <div class="staff-block bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden"
         data-emp="{{ $emp['id'] }}">

        {{-- Employee header --}}
        <div class="px-6 py-4 bg-gradient-to-r from-[#06142f] to-[#0a2342] flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center text-white font-black text-sm">
                    {{ strtoupper(substr($emp['short_name'] ?? 'X', 0, 1)) }}
                </div>
                <div>
                    <p class="text-white font-black text-sm">{{ $emp['short_name'] ?? $emp['employee_id'] }}</p>
                    <p class="text-blue-300 text-[10px] font-semibold uppercase tracking-wide">{{ $emp['role'] }}</p>
                </div>
            </div>
            <div class="flex gap-4 text-right">
                <div>
                    <p class="text-[9px] text-blue-300 uppercase font-black tracking-wide">YTD Collection</p>
                    <p class="text-white font-black text-sm">RM {{ number_format($ytdRevActual) }}</p>
                </div>
                <div>
                    <p class="text-[9px] text-blue-300 uppercase font-black tracking-wide">YTD Retention</p>
                    <p class="text-white font-black text-sm">{{ $ytdRetActual }} / {{ $ytdRetTarget }}</p>
                </div>
            </div>
        </div>

        {{-- KPI table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
                <thead>
                    <tr class="bg-[#06142f] text-white">
                        <th class="px-4 py-2.5 text-left font-black text-[10px] uppercase tracking-wider w-16">Category</th>
                        <th class="px-3 py-2.5 text-left font-black text-[10px] uppercase tracking-wider w-10">#</th>
                        <th class="px-4 py-2.5 text-left font-black text-[10px] uppercase tracking-wider">KPI / Month</th>
                        <th class="px-4 py-2.5 text-right font-black text-[10px] uppercase tracking-wider w-32">Actual</th>
                        <th class="px-4 py-2.5 text-right font-black text-[10px] uppercase tracking-wider w-32">Base Target</th>
                        <th class="px-4 py-2.5 text-right font-black text-[10px] uppercase tracking-wider w-24">Score (%)</th>
                        <th class="px-4 py-2.5 text-right font-black text-[10px] uppercase tracking-wider w-28">Weightage (%)</th>
                        <th class="px-4 py-2.5 text-right font-black text-[10px] uppercase tracking-wider w-28">Final Score</th>
                    </tr>
                </thead>
                <tbody>

                    {{-- Category header row: Financial (60%) --}}
                    <tr class="bg-[#0f2d5a]/8 border-b border-slate-200">
                        <td rowspan="{{ count($kpiDefs) * count($months) + count($kpiDefs) + 1 }}"
                            class="px-4 py-3 align-middle text-center border-r border-slate-200 bg-[#0f2d5a]/5">
                            <div class="writing-vertical font-black text-[11px] text-[#06142f] uppercase tracking-wide"
                                 style="writing-mode:vertical-rl;transform:rotate(180deg);white-space:nowrap">
                                Financial (60%)
                            </div>
                        </td>
                        <td colspan="7" class="px-4 py-2 bg-[#0f2d5a]/5">
                            <span class="text-[10px] text-slate-400 italic">Category: Financial</span>
                        </td>
                    </tr>

                    @foreach($kpiDefs as $kpiKey => $kpiDef)

                    {{-- KPI title row --}}
                    <tr class="border-b border-slate-300 bg-slate-50">
                        <td class="px-3 py-2 text-slate-700 font-black text-[11px] align-middle">{{ $kpiDef['no'] }}</td>
                        <td colspan="6" class="px-4 py-3">
                            <p class="font-black text-slate-800 text-[13px]">{{ $kpiDef['title'] }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5 italic">{{ $kpiDef['desc'] }}</p>
                        </td>
                    </tr>

                    {{-- Month rows --}}
                    @foreach($months as $monthNum => $monthName)
                    @php
                        $row       = $empData[$kpiKey][$monthNum] ?? null;
                        $actual    = (float)($row['actual']      ?? 0);
                        $base      = (float)($row['base_target'] ?? 0);
                        $weightage = (float)($row['weightage']   ?? 10);
                        $score     = $base > 0 ? min(100, round($actual / $base * 100, 1)) : 0;
                        $final     = round($score * $weightage / 100, 2);
                        $isFuture  = $monthNum > $currentMonth;
                        $hasData   = $row !== null && ($actual > 0 || $base > 0);
                        $scoreColor = $score >= 100 ? 'text-emerald-600' : ($score >= 80 ? 'text-blue-600' : ($score >= 50 ? 'text-amber-600' : 'text-red-500'));
                        $fillColor  = $score >= 100 ? 'bg-emerald-500' : ($score >= 80 ? 'bg-blue-500' : ($score >= 50 ? 'bg-amber-500' : 'bg-red-400'));
                    @endphp
                    <tr class="month-row border-b border-slate-100 {{ $isFuture && !$hasData ? 'opacity-40' : '' }}">
                        <td class="px-3 py-2 text-slate-400 text-[10px]"></td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full {{ $hasData ? 'bg-emerald-500' : ($isFuture ? 'bg-slate-200' : 'bg-amber-400') }} shrink-0"></span>
                                <span class="font-semibold text-slate-700">{{ $monthName }}</span>
                                @if($monthNum === $currentMonth)
                                <span class="text-[9px] bg-blue-100 text-blue-700 font-black px-1.5 py-0.5 rounded-full">Current</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-right font-black text-slate-800">
                            @if($hasData)
                                @if($kpiKey === 'revenue') RM {{ number_format($actual) }}
                                @else {{ number_format($actual) }}
                                @endif
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right text-slate-600">
                            @if($hasData)
                                @if($kpiKey === 'revenue') RM {{ number_format($base) }}
                                @else {{ number_format($base) }}
                                @endif
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @if($hasData)
                            <div>
                                <span class="font-black {{ $scoreColor }}">{{ $score }}%</span>
                                <div class="score-bar mt-1 w-16 ml-auto">
                                    <div class="score-fill {{ $fillColor }}" style="width:{{ $score }}%"></div>
                                </div>
                            </div>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right text-slate-600 font-semibold">{{ $weightage }}%</td>
                        <td class="px-4 py-2.5 text-right">
                            @if($hasData)
                                <span class="font-black {{ $scoreColor }}">{{ $final }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    @endforeach

                    {{-- YTD total row --}}
                    <tr class="bg-[#06142f] text-white border-t-2 border-[#06142f]">
                        <td colspan="2" class="px-4 py-3 font-black text-[11px] uppercase tracking-wider text-blue-200">YTD Total</td>
                        <td class="px-4 py-3 text-right font-black">RM {{ number_format($ytdRevActual) }}</td>
                        <td class="px-4 py-3 text-right font-black text-blue-200">RM {{ number_format($ytdRevTarget) }}</td>
                        <td class="px-4 py-3 text-right font-black">
                            {{ $ytdRevTarget > 0 ? round($ytdRevActual/$ytdRevTarget*100,1) : 0 }}%
                        </td>
                        <td class="px-4 py-3 text-right text-blue-200 font-semibold">—</td>
                        <td class="px-4 py-3 text-right font-black text-emerald-300">
                            {{ round($ytdFinalScore, 2) }}
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    @if(empty($viewStaff))
    <div class="bg-white rounded-2xl p-10 text-center shadow-sm">
        <p class="text-slate-400 text-sm">No staff data found for Titan department.</p>
    </div>
    @endif

</div>
</main>

<script>
// ── Staff tab switching ─────────────────────────────────────────────────────
function switchStaff(empId) {
    const blocks = document.querySelectorAll('.staff-block');
    const tabs   = document.querySelectorAll('.staff-tab');

    if (empId === 'all') {
        blocks.forEach(b => b.classList.remove('hidden'));
    } else {
        blocks.forEach(b => {
            b.classList.toggle('hidden', b.dataset.emp !== empId);
        });
    }

    tabs.forEach(t => {
        const active = t.id === 'tab-' + empId;
        t.classList.toggle('active-tab', active);
        t.classList.toggle('border-[#06142f]', active);
        t.classList.toggle('bg-[#06142f]', active);
        t.classList.toggle('text-white', active);
        t.classList.toggle('border-slate-200', !active);
        t.classList.toggle('text-slate-600', !active);
    });
}

// ── Sync from Google Sheet ──────────────────────────────────────────────────
async function syncFromSheet() {
    const btn = document.getElementById('syncBtn');
    if (!confirm('Sync latest data from the Google Sheet for all Titan staff?\n\nThis will update Actual and Base Target for all months.')) return;

    btn.disabled = true;
    btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Syncing…`;

    try {
        const res  = await fetch('{{ route("titan-kpi.sync") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body:    JSON.stringify({}),
        });
        const data = await res.json();
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error ?? 'Unknown'));
        }
    } catch (e) {
        alert('Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Sync from Google Sheet`;
    }
}
</script>

</body>
</html>
