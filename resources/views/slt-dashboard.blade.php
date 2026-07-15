<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLT Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .soft-card { box-shadow: 0 8px 30px rgba(15,23,42,.07); }
        .thin-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .thin-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .band-pill.active { outline: 2px solid #1e293b; outline-offset: 1px; }
    </style>
</head>
<body class="bg-[#F5F5F3] min-h-screen text-slate-900">

@include('partials.sidebar')

@php
    $bandMeta = [
        'unsatisfactory'      => ['label' => 'Unsatisfactory',     'range' => '1–49',   'bg' => '#ED1C24', 'text' => '#FFFFFF'],
        'below_average'       => ['label' => 'Below Average',      'range' => '50–69',  'bg' => '#FF8C00', 'text' => '#000000'],
        'meets_expectations'  => ['label' => 'Meets Expectations', 'range' => '70–89',  'bg' => '#FFD700', 'text' => '#000000'],
        'outstanding'         => ['label' => 'Outstanding',        'range' => '90–100', 'bg' => '#00B050', 'text' => '#FFFFFF'],
    ];
@endphp

<main id="mainContent" class="ml-[230px] min-h-screen">
<div class="px-4 pt-4 pb-4 space-y-3">

    {{-- ═══════ HEADER ═══════ --}}
    <div class="relative overflow-hidden rounded-[18px] bg-gradient-to-r from-[#1A0A0A] to-[#7A0019] text-white px-6 py-5 shadow-[0_10px_35px_rgba(122,0,25,0.45)] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#D4AF37] via-[#D4AF37] to-[#D4AF37]/10"></div>
        <div class="relative">
            <h1 class="text-xl font-black tracking-tight leading-tight">
                SLT Dashboard <span class="text-[#D4AF37]">| {{ $quarter }} {{ $currentFinancialYear }}</span>
            </h1>
            <p class="text-[11px] text-white/60 mt-1">{{ now()->timezone('Asia/Kuala_Lumpur')->format('d M Y') }} · Quarterly appraisal summary</p>
        </div>

        <form method="GET" action="{{ route('slt-dashboard') }}" class="relative flex flex-wrap items-center gap-2">
            <div class="flex flex-col">
                <label class="text-[9px] text-white/60 uppercase tracking-wide mb-0.5">Quarter</label>
                <select name="quarter" onchange="this.form.submit()" class="text-xs font-bold rounded-lg px-2.5 py-1.5 text-[#1a1a1a] bg-white border border-white/20">
                    @foreach(['Q1','Q2','Q3','Q4'] as $q)
                        <option value="{{ $q }}" @selected($quarter === $q)>{{ $q }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col">
                <label class="text-[9px] text-white/60 uppercase tracking-wide mb-0.5">Department</label>
                <select name="department" onchange="this.form.submit()" class="text-xs font-bold rounded-lg px-2.5 py-1.5 text-[#1a1a1a] bg-white border border-white/20">
                    <option value="ALL" @selected($deptFilter === 'ALL')>All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d['code'] }}" @selected($deptFilter === $d['code'])>{{ $d['name'] ?? $d['code'] }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    {{-- ═══════ STAT CARDS ═══════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

        <div class="bg-white rounded-2xl soft-card border border-[#E5E7EB] border-t-[3px] border-t-[#D4AF37] p-4">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Staff Engagement</p>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-slate-500 font-semibold">Total Staff</span>
                <span class="text-2xl font-black text-slate-900">{{ $totalStaff }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-semibold">Participation Rate</span>
                <span class="text-2xl font-black text-[#B8860B]">{{ $participationRate }}%</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl soft-card border border-[#E5E7EB] border-t-[3px] border-t-[#D4AF37] p-4">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Appraisal Completion</p>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-slate-500 font-semibold">Completed</span>
                <span class="text-2xl font-black text-emerald-600">{{ $completedCount }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-semibold">Not Complete</span>
                <span class="text-2xl font-black text-red-500">{{ $notCompleteCount }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl soft-card border border-[#E5E7EB] border-t-[3px] border-t-[#D4AF37] p-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Average Score</p>
                <p class="text-4xl font-black leading-none" style="color:{{ $averageBand['bg'] }};">{{ number_format($averageScore, 1) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">out of 100 · {{ $completedCount }} completed</p>
            </div>
            <span class="text-[10px] font-black px-3 py-1.5 rounded-full" style="background:{{ $averageBand['bg'] }};color:{{ $averageBand['text'] }};">
                {{ $averageBand['label'] }}
            </span>
        </div>

    </div>

    {{-- ═══════ SCORE DISTRIBUTION + STAFF LIST ═══════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-3">

        {{-- Distribution chart --}}
        <div class="xl:col-span-2 bg-white rounded-2xl soft-card border border-[#E5E7EB] border-t-[3px] border-t-[#D4AF37] p-4">
            <p class="text-[11px] font-black text-slate-800 mb-1">Performance Score Distribution</p>
            <p class="text-[9px] text-slate-400 mb-3">{{ $quarter }} {{ $currentFinancialYear }} · {{ $completedCount }} appraised staff</p>
            <div style="height:180px;position:relative;">
                <canvas id="chartBandDist"></canvas>
            </div>
            <div class="mt-4 space-y-1.5">
                @foreach($bandMeta as $key => $b)
                <button type="button" onclick="filterBand('{{ $key }}')" data-band="{{ $key }}"
                    class="band-pill w-full flex items-center justify-between px-3 py-1.5 rounded-lg text-[10px] font-black transition"
                    style="background:{{ $b['bg'] }}22;color:{{ $b['bg'] === '#FFD700' ? '#8a6d00' : $b['bg'] }};">
                    <span>{{ $b['label'] }} <span class="font-normal opacity-70">({{ $b['range'] }})</span></span>
                    <span>{{ $bandCounts[$key] }} staff</span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Staff list --}}
        <div class="xl:col-span-3 bg-white rounded-2xl soft-card border border-[#E5E7EB] border-t-[3px] border-t-[#D4AF37] overflow-hidden flex flex-col">
            <div class="p-4 pb-2 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-black text-slate-800">Staff List</p>
                    <p class="text-[9px] text-slate-400 mt-0.5" id="staffListSubtitle">All staff · {{ $quarter }} {{ $currentFinancialYear }}</p>
                </div>
                <div class="flex gap-1.5 flex-wrap justify-end">
                    <button type="button" onclick="filterBand('all')" data-band="all" class="band-pill active px-2.5 py-1 rounded-lg text-[9px] font-black bg-slate-100 text-slate-600">All ({{ $totalStaff }})</button>
                    <button type="button" onclick="filterBand('not_submitted')" data-band="not_submitted" class="band-pill px-2.5 py-1 rounded-lg text-[9px] font-black bg-slate-100 text-slate-600">Not Submitted ({{ count($notSubmitted) }})</button>
                    <button type="button" onclick="filterBand('pending')" data-band="pending" class="band-pill px-2.5 py-1 rounded-lg text-[9px] font-black bg-slate-100 text-slate-600">Awaiting Appraisal ({{ count($submittedPending) }})</button>
                </div>
            </div>
            <div class="overflow-y-auto overflow-x-auto thin-scroll flex-1" style="max-height:420px;">
                <table class="w-full min-w-[560px] text-left">
                    <thead class="sticky top-0 bg-white z-10">
                        <tr class="bg-slate-50 text-[9px] uppercase tracking-wider text-slate-500 font-black border-b border-[#E5E7EB]">
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Department</th>
                            <th class="px-3 py-2">Manager</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="staffListBody">
                        @foreach($bandMeta as $key => $b)
                            @foreach($bandStaff[$key] as $row)
                            <tr data-band="{{ $key }}" class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-[10px] font-bold text-slate-500">{{ $row['employee_id'] }}</td>
                                <td class="px-3 py-2 text-[11px] font-black text-slate-900">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-[10px] text-slate-500">{{ $row['department'] }}</td>
                                <td class="px-3 py-2 text-[10px] text-slate-500">{{ $row['manager'] }}</td>
                                <td class="px-3 py-2">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full" style="background:{{ $b['bg'] }}22;color:{{ $b['bg'] === '#FFD700' ? '#8a6d00' : $b['bg'] }};">{{ $b['label'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-right text-[11px] font-black" style="color:{{ $b['bg'] === '#FFD700' ? '#8a6d00' : $b['bg'] }};">{{ number_format($row['score'], 1) }}</td>
                            </tr>
                            @endforeach
                        @endforeach

                        @foreach($submittedPending as $row)
                            <tr data-band="pending" class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-[10px] font-bold text-slate-500">{{ $row['employee_id'] }}</td>
                                <td class="px-3 py-2 text-[11px] font-black text-slate-900">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-[10px] text-slate-500">{{ $row['department'] }}</td>
                                <td class="px-3 py-2 text-[10px] text-slate-500">{{ $row['manager'] }}</td>
                                <td class="px-3 py-2">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Awaiting Appraisal</span>
                                </td>
                                <td class="px-3 py-2 text-right text-[10px] text-slate-300">—</td>
                            </tr>
                        @endforeach

                        @foreach($notSubmitted as $row)
                            <tr data-band="not_submitted" class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-[10px] font-bold text-slate-500">{{ $row['employee_id'] }}</td>
                                <td class="px-3 py-2 text-[11px] font-black text-slate-900">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-[10px] text-slate-500">{{ $row['department'] }}</td>
                                <td class="px-3 py-2 text-[10px] text-slate-500">{{ $row['manager'] }}</td>
                                <td class="px-3 py-2">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-red-50 text-red-600">Not Submitted</span>
                                </td>
                                <td class="px-3 py-2 text-right text-[10px] text-slate-300">—</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const bandCounts = @json($bandCounts);
const bandMeta = @json($bandMeta);

new Chart(document.getElementById('chartBandDist'), {
    type: 'bar',
    data: {
        labels: Object.keys(bandMeta).map(k => bandMeta[k].label + ' (' + bandMeta[k].range + ')'),
        datasets: [{
            data: Object.keys(bandMeta).map(k => bandCounts[k]),
            backgroundColor: Object.keys(bandMeta).map(k => bandMeta[k].bg),
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: '#f1f5f9' } },
            y: { ticks: { font: { size: 10, weight: 'bold' } }, grid: { display: false } }
        }
    }
});

function filterBand(band) {
    document.querySelectorAll('.band-pill').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.band === band);
    });
    document.querySelectorAll('#staffListBody tr').forEach(function (tr) {
        tr.style.display = (band === 'all' || tr.dataset.band === band) ? '' : 'none';
    });
    var subtitle = document.getElementById('staffListSubtitle');
    if (band === 'all') {
        subtitle.textContent = 'All staff · {{ $quarter }} {{ $currentFinancialYear }}';
    } else {
        var label = bandMeta[band] ? bandMeta[band].label : (band === 'not_submitted' ? 'Not Submitted' : 'Awaiting Appraisal');
        subtitle.textContent = label + ' · {{ $quarter }} {{ $currentFinancialYear }}';
    }
}
</script>

</body>
</html>
