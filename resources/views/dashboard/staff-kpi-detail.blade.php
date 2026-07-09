<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $kpi['kpi_title'] ?? 'KPI' }} — {{ $staff['short_name'] ?? $staff['full_name'] ?? 'Staff' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
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
    $avgStyle = $scoreStyle($average);
    $quartersByLabel = collect($quarters)->keyBy('quarter');

    // Same category colour language as "My KPI" / staff KPI list
    $categoryThemes = [
        'Financial'         => ['catPill'=>'bg-emerald-700 text-white','subPill'=>'bg-emerald-100 text-emerald-700'],
        'Growth & Customer' => ['catPill'=>'bg-indigo-700 text-white', 'subPill'=>'bg-indigo-100 text-indigo-700'],
        'Initiatives'       => ['catPill'=>'bg-amber-600 text-white',  'subPill'=>'bg-amber-100 text-amber-700'],
        'People'            => ['catPill'=>'bg-pink-700 text-white',   'subPill'=>'bg-pink-100 text-pink-700'],
    ];
    $categoryThemeDefault = ['catPill'=>'bg-slate-600 text-white','subPill'=>'bg-slate-100 text-slate-600'];
    $ctheme = $categoryThemes[$kpi['category'] ?? ''] ?? $categoryThemeDefault;
@endphp

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">
<div class="px-4 pt-4 pb-10">

    {{-- Breadcrumb / back --}}
    <a href="{{ route('dashboard.staff.kpis', $staff['id']) }}" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-400 hover:text-[#6B9080] transition mb-3">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to {{ $staff['short_name'] ?? $staff['full_name'] ?? 'Staff' }}'s KPIs
    </a>

    {{-- KPI header card --}}
    <div class="bg-white rounded-2xl overflow-hidden soft-card border border-[#6B9080] mb-4">
        <div class="h-1 bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#A4C3B2]"></div>
        <div class="p-5 flex flex-wrap items-center gap-5">
            <div class="flex-1 min-w-[240px]">
                <h1 class="text-base font-black text-slate-900 leading-snug">{{ $kpi['kpi_title'] ?? 'Untitled KPI' }}</h1>
                <p class="text-xs text-slate-500 mt-1">{{ $staff['full_name'] ?? $staff['short_name'] ?? 'Unknown' }} · {{ $currentFinancialYear }}</p>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span class="px-2 py-0.5 rounded-lg {{ $ctheme['catPill'] }} text-[10px] font-black">{{ $kpi['category'] ?? '-' }}</span>
                    <span class="px-2 py-0.5 rounded-lg {{ $ctheme['subPill'] }} text-[10px] font-black">{{ $kpi['sub_category'] ?? '-' }}</span>
                    <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[10px] font-black border border-indigo-100">Weightage {{ number_format($kpi['weightage'] ?? 0, 1) }}%</span>
                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-black">Unit: {{ $kpi['unit'] ?? '-' }}</span>
                </div>
            </div>
            <div class="text-center px-6 py-3 rounded-2xl {{ $avgStyle['badge'] }} border">
                <p class="text-[9px] font-black uppercase tracking-widest opacity-70">Average Progress</p>
                <p class="text-2xl font-black {{ $avgStyle['text'] }}">{{ number_format($average, 1) }}%</p>
                <p class="text-[9px] font-bold opacity-60 mt-0.5">across quarters with data</p>
            </div>
        </div>
    </div>

    {{-- Quarterly breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach(['Q1','Q2','Q3','Q4'] as $ql)
            @php
                $row    = $quartersByLabel->get($ql);
                $target = $row['quarter_target'] ?? null;
                $actual = $row['quarter_actual'] ?? null;
                $pct    = $row['progress_pct'] ?? 0;
                $qstyle = $scoreStyle($pct);
                $hasData = $row !== null && ((float)($actual ?? 0) > 0 || (float)($target ?? 0) > 0);
            @endphp
            <div class="bg-white rounded-2xl border border-[#6B9080] soft-card p-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-xs font-black text-slate-900">{{ $ql }}</h3>
                    @if($row)
                        <span class="px-2 py-0.5 rounded-lg text-[8px] font-black {{ $qstyle['badge'] }} border">{{ $hasData ? $qstyle['label'] : 'No Data' }}</span>
                    @else
                        <span class="px-2 py-0.5 rounded-lg text-[8px] font-black bg-slate-100 text-slate-400 border border-slate-200">Not Planned</span>
                    @endif
                </div>
                @if($row && !empty($row['quarter_title']))
                    <p class="text-[10px] text-slate-400 mb-3 truncate" title="{{ $row['quarter_title'] }}">{{ $row['quarter_title'] }}</p>
                @else
                    <div class="mb-3"></div>
                @endif

                @if($row)
                    <div class="space-y-2 mb-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-black text-slate-400 uppercase">Target</span>
                            <span class="text-xs font-black text-slate-700">{{ number_format((float)($target ?? 0), 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-black text-slate-400 uppercase">Actual</span>
                            <span class="text-xs font-black text-slate-700">{{ number_format((float)($actual ?? 0), 2) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $qstyle['bar'] }}" style="width:{{ min($pct,100) }}%"></div>
                        </div>
                        <span class="text-[10px] font-black {{ $qstyle['text'] }} shrink-0">{{ number_format($pct, 1) }}%</span>
                    </div>
                    @if(!empty($row['remark']))
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Remark</p>
                            <p class="text-[10px] text-slate-600 leading-relaxed">{{ $row['remark'] }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-[10px] text-slate-300 italic">This quarter hasn't been set up yet.</p>
                @endif
            </div>
        @endforeach
    </div>

</div>
</main>
</body>
</html>
