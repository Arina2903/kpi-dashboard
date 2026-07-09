<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $staff['short_name'] ?? $staff['full_name'] ?? 'Staff' }} — KPI Overview</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://ui-avatars.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .soft-card { box-shadow: 0 8px 30px rgba(15,23,42,.07); }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

@include('partials.sidebar')

@php
    $scoreStyle = function($s) {
        $s = (float)$s;
        if ($s <= 25)  return ['bar'=>'bg-red-600',                                     'text'=>'text-red-700',     'badge'=>'bg-red-50 text-red-700 border-red-100',        'label'=>'Critical'];
        if ($s <= 50)  return ['bar'=>'bg-gradient-to-r from-red-600 to-orange-500',    'text'=>'text-orange-700',  'badge'=>'bg-orange-50 text-orange-700 border-orange-100','label'=>'Risk'];
        if ($s <= 75)  return ['bar'=>'bg-gradient-to-r from-orange-500 to-yellow-400', 'text'=>'text-amber-700',   'badge'=>'bg-amber-50 text-amber-700 border-amber-100',  'label'=>'Watch'];
        if ($s <= 100) return ['bar'=>'bg-gradient-to-r from-yellow-400 to-emerald-600','text'=>'text-emerald-700', 'badge'=>'bg-emerald-50 text-emerald-700 border-emerald-100','label'=>'Good'];
        return                 ['bar'=>'bg-emerald-700',                                'text'=>'text-emerald-800', 'badge'=>'bg-emerald-50 text-emerald-800 border-emerald-100','label'=>'Exceeded'];
    };
    $overallStyle = $scoreStyle($weightedScore);
@endphp

<main class="pl-[230px] transition-all duration-300">
<div class="px-4 pt-4 pb-10">

    {{-- Breadcrumb / back --}}
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-400 hover:text-[#6B9080] transition mb-3">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Dashboard
    </a>

    {{-- Staff header card --}}
    <div class="bg-white rounded-2xl overflow-hidden soft-card border border-[#6B9080] mb-4">
        <div class="h-1 bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#A4C3B2]"></div>
        <div class="p-5 flex flex-wrap items-center gap-5">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-slate-200 shrink-0">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($staff['short_name'] ?? $staff['full_name'] ?? 'U') }}&background=1a3d34&color=fff&size=56" class="w-full h-full"/>
            </div>
            <div class="flex-1 min-w-[200px]">
                <h1 class="text-lg font-black text-slate-900">{{ $staff['full_name'] ?? $staff['short_name'] ?? 'Unknown' }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">{{ $staff['position'] ?? $staff['role'] ?? '-' }} · {{ $departmentName }} · {{ $currentFinancialYear }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[10px] font-black border border-indigo-100">{{ strtoupper($staff['role'] ?? '-') }}</span>
                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-black">{{ count($kpis) }} KPIs</span>
                </div>
            </div>
            <div class="text-center px-6 py-3 rounded-2xl {{ $overallStyle['badge'] }} border">
                <p class="text-[9px] font-black uppercase tracking-widest opacity-70">Overall Score</p>
                <p class="text-2xl font-black {{ $overallStyle['text'] }}">{{ number_format($weightedScore, 1) }}%</p>
                <p class="text-[9px] font-bold opacity-60 mt-0.5">{{ $totalWeight }}% weightage assigned</p>
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    @if(count($kpis) === 0)
        <div class="bg-white rounded-2xl border border-[#6B9080] soft-card p-10 text-center">
            <p class="text-sm font-black text-slate-400">No KPIs found for this staff in {{ $currentFinancialYear }}.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            @foreach($kpis as $kpi)
                @php $kstyle = $scoreStyle($kpi['progress_pct']); @endphp
                <a href="{{ route('dashboard.staff.kpi.detail', [$staff['id'], $kpi['id']]) }}"
                   class="block bg-white rounded-2xl border border-[#6B9080] soft-card p-4 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="text-xs font-black text-slate-900 leading-snug line-clamp-2">{{ $kpi['kpi_title'] ?? 'Untitled KPI' }}</h3>
                        <span class="shrink-0 px-2 py-0.5 rounded-lg text-[9px] font-black {{ $kstyle['badge'] }} border">{{ $kstyle['label'] }}</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mb-3">{{ $kpi['category'] ?? '-' }} · {{ $kpi['sub_category'] ?? '-' }}</p>

                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div>
                            <p class="text-[8px] font-black text-slate-400 uppercase">Target</p>
                            <p class="text-xs font-black text-slate-700">{{ number_format($kpi['display_target'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-[8px] font-black text-slate-400 uppercase">Actual</p>
                            <p class="text-xs font-black text-slate-700">{{ number_format($kpi['display_actual'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-[8px] font-black text-slate-400 uppercase">Weightage</p>
                            <p class="text-xs font-black text-slate-700">{{ number_format($kpi['weightage'] ?? 0, 1) }}%</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $kstyle['bar'] }}" style="width:{{ min($kpi['progress_pct'],100) }}%"></div>
                        </div>
                        <span class="text-[10px] font-black {{ $kstyle['text'] }} shrink-0">{{ number_format($kpi['progress_pct'], 1) }}%</span>
                    </div>
                    <p class="text-[9px] text-slate-400 mt-2">{{ $kpi['quarters_filled'] }}/4 quarters updated</p>
                </a>
            @endforeach
        </div>
    @endif

</div>
</main>
</body>
</html>
