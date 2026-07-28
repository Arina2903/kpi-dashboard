@php
    // Same progress-percentage colour coordinator used by kpi-detail-content.blade.php
    // and dashboard.blade.php, so every "progress %" surface in the app reads the same.
    $scoreStyle = function($s) {
        $s = (float)$s;
        if ($s <= 25)  return ['bar'=>'bg-red-600',                                     'text'=>'text-red-700',     'badge'=>'bg-red-50 text-red-700 border-red-100',        'label'=>'Critical'];
        if ($s <= 50)  return ['bar'=>'bg-gradient-to-r from-red-600 to-orange-500',    'text'=>'text-orange-700',  'badge'=>'bg-orange-50 text-orange-700 border-orange-100','label'=>'Risk'];
        if ($s <= 75)  return ['bar'=>'bg-gradient-to-r from-orange-500 to-yellow-400', 'text'=>'text-amber-700',   'badge'=>'bg-amber-50 text-amber-700 border-amber-100',  'label'=>'Watch'];
        if ($s <= 100) return ['bar'=>'bg-gradient-to-r from-yellow-400 to-emerald-600','text'=>'text-emerald-700', 'badge'=>'bg-emerald-50 text-emerald-700 border-emerald-100','label'=>'Good'];
        return                 ['bar'=>'bg-emerald-700',                                'text'=>'text-emerald-800', 'badge'=>'bg-emerald-50 text-emerald-800 border-emerald-100','label'=>'Exceeded'];
    };
    $overallStyle = $scoreStyle($overall);

    // Same category colour language as kpi-detail-content.blade.php / "My KPI".
    $categoryThemes = [
        'Financial'         => ['catPill'=>'bg-emerald-700 text-white','subPill'=>'bg-emerald-100 text-emerald-700'],
        'Growth & Customer' => ['catPill'=>'bg-indigo-700 text-white', 'subPill'=>'bg-indigo-100 text-indigo-700'],
        'Initiatives'       => ['catPill'=>'bg-amber-600 text-white',  'subPill'=>'bg-amber-100 text-amber-700'],
        'People'            => ['catPill'=>'bg-pink-700 text-white',   'subPill'=>'bg-pink-100 text-pink-700'],
    ];
    $categoryThemeDefault = ['catPill'=>'bg-slate-600 text-white','subPill'=>'bg-slate-100 text-slate-600'];

    // Same status badge colours as performance/report.blade.php's own header banner.
    $statusStyle = match($status ?? 'draft') {
        'submitted' => ['badge'=>'bg-blue-50 text-blue-700 border-blue-100',       'label'=>'✓ Submitted · Awaiting Appraiser'],
        'appraised' => ['badge'=>'bg-amber-50 text-amber-700 border-amber-100',    'label'=>'✍ Appraised · Signature Needed'],
        'completed' => ['badge'=>'bg-emerald-50 text-emerald-700 border-emerald-100','label'=>'✓ Completed'],
        default     => ['badge'=>'bg-slate-100 text-slate-500 border-slate-200',   'label'=>'Draft'],
    };
@endphp

{{-- Header card --}}
<div class="bg-white rounded-2xl overflow-hidden soft-card border border-[#6B9080] mb-4">
    <div class="h-1 bg-gradient-to-r from-[#1A0A0A] to-[#7A0019]"></div>
    <div class="p-5 flex flex-wrap items-center gap-5">
        <div class="flex-1 min-w-[240px]">
            <h1 class="text-base font-black text-slate-900 leading-snug">{{ $staff['full_name'] ?? $staff['short_name'] ?? 'Unknown' }}</h1>
            <p class="text-xs text-slate-500 mt-1">{{ $department['name'] ?? $staff['department_code'] ?? '—' }} · {{ $quarter }} {{ $currentFinancialYear }}</p>
            <div class="flex items-center gap-2 mt-2 flex-wrap">
                <span class="px-2 py-0.5 rounded-lg {{ $statusStyle['badge'] }} border text-[10px] font-black">{{ $statusStyle['label'] }}</span>
            </div>
        </div>
        <div class="text-center px-6 py-3 rounded-2xl {{ $overallStyle['badge'] }} border">
            <p class="text-[9px] font-black uppercase tracking-widest opacity-70">Overall Progress</p>
            <p class="text-2xl font-black {{ $overallStyle['text'] }}">{{ number_format($overall, 1) }}%</p>
            <p class="text-[9px] font-bold opacity-60 mt-0.5">{{ $quarter }} · weighted across KPIs with data</p>
        </div>
    </div>
</div>

{{-- KPI rows for this quarter --}}
<div class="bg-white rounded-2xl border border-[#6B9080] soft-card overflow-hidden">
    @forelse($rows as $row)
        @php
            $ctheme = $categoryThemes[$row['category'] ?? ''] ?? $categoryThemeDefault;
            $rstyle = $scoreStyle($row['progress_pct']);
        @endphp
        <div class="p-4 flex flex-wrap items-center gap-3 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
            <div class="flex-1 min-w-[200px]">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="px-2 py-0.5 rounded-lg {{ $ctheme['catPill'] }} text-[9px] font-black">{{ $row['category'] ?? '-' }}</span>
                    <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[9px] font-black border border-indigo-100">{{ number_format($row['weightage'] ?? 0, 1) }}% weight</span>
                </div>
                <p class="text-[12px] font-bold text-slate-800 leading-snug">{{ $row['kpi_title'] ?? 'Untitled KPI' }}</p>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <div class="text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase">Target</p>
                    <p class="text-xs font-black text-slate-700">{{ number_format($row['target'], 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase">Actual</p>
                    <p class="text-xs font-black text-slate-700">{{ number_format($row['actual'], 2) }}</p>
                </div>
                <div class="w-28">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $rstyle['bar'] }}" style="width:{{ min($row['progress_pct'],100) }}%"></div>
                        </div>
                        <span class="text-[10px] font-black {{ $rstyle['text'] }} shrink-0">{{ $row['has_data'] ? number_format($row['progress_pct'], 1).'%' : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-[11px] text-slate-400 italic p-5">No KPIs found for this quarter.</p>
    @endforelse
</div>

<div class="mt-4 flex justify-end">
    <a href="{{ $fullReportUrl }}" class="inline-flex items-center gap-1.5 text-[11px] font-black text-white bg-[#1a3d34] hover:bg-[#132a24] rounded-xl px-4 py-2.5 transition">
        Open Full Appraisal Report →
    </a>
</div>
