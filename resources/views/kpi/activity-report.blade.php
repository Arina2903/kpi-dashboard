<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Activity Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            #mainContent { margin-left: 0 !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-[#F5F5F3]">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300 bg-[#F5F5F3]">
<div class="p-4 space-y-4">

    {{-- HEADER --}}
    <div class="no-print rounded-[18px] bg-gradient-to-r from-[#1A0A0A] to-[#7A0019] text-white px-6 py-5 shadow-xl flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('activity-log') }}" class="text-[11px] text-[#D4AF37] hover:text-white transition">← Activity Log</a>
            <h1 class="text-2xl font-black tracking-tight mt-1">My Activity Report</h1>
            <p class="text-white/70 text-xs mt-1">
                {{ $user['short_name'] ?? $user['full_name'] ?? '-' }} · {{ $user['role'] }} · {{ $user['department_code'] }} · {{ $fy }}
            </p>
        </div>
        <button onclick="window.print()" class="bg-[#D4AF37] hover:bg-[#c19c2f] text-[#1a1a1a] px-4 py-2.5 rounded-xl shadow font-black text-xs transition">
            🖨️ Print / Save as PDF
        </button>
    </div>

    {{-- PRINT-ONLY LETTERHEAD --}}
    <div class="hidden print:block mb-4">
        <h1 class="text-xl font-black text-[#7A0019]">My Activity Report</h1>
        <p class="text-xs text-slate-500">
            {{ $user['short_name'] ?? $user['full_name'] ?? '-' }} · {{ $user['role'] }} · {{ $user['department_code'] }} · {{ $fy }}
            · Generated {{ now()->format('d M Y, h:i A') }}
        </p>
    </div>

    @php
        $typeMeta = [
            'kpi_created'          => ['label' => 'KPI Created',          'bg' => '#FBF5EF', 'text' => '#6B3F2A'],
            'kpi_edited'           => ['label' => 'KPI Edited',           'bg' => '#EEF2FF', 'text' => '#4338CA'],
            'update_submitted'     => ['label' => 'Update Submitted',     'bg' => '#FEF3C7', 'text' => '#B45309'],
            'update_approved'      => ['label' => 'Update Approved',      'bg' => '#D1FAE5', 'text' => '#047857'],
            'update_rejected'      => ['label' => 'Update Rejected',      'bg' => '#FEE2E2', 'text' => '#DC2626'],
            'completion_submitted' => ['label' => 'Completion Submitted', 'bg' => '#F3E8FF', 'text' => '#7C3AED'],
            'delete_requested'     => ['label' => 'Delete Requested',     'bg' => '#FFE4E6', 'text' => '#BE123C'],
        ];
        $typeCounts = $logs->groupBy('type')->map->count();
    @endphp

    {{-- SUMMARY --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] border-t-[3px] border-t-[#D4AF37] shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Summary</p>
                <p class="text-sm text-slate-600 mt-0.5">Total {{ $logs->count() }} activities recorded for {{ $fy }}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
            @foreach($typeMeta as $key => $meta)
                <div class="rounded-xl px-3 py-2.5 text-center" style="background:{{ $meta['bg'] }};">
                    <p class="text-lg font-black" style="color:{{ $meta['text'] }};">{{ $typeCounts[$key] ?? 0 }}</p>
                    <p class="text-[9px] font-bold uppercase tracking-wide mt-0.5" style="color:{{ $meta['text'] }};">{{ $meta['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- REPORT TABLE (view only) --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] border-t-[3px] border-t-[#D4AF37] shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
            <p class="text-[11px] font-black text-slate-800">Full Activity Record</p>
            <p class="text-[9px] text-slate-400 mt-0.5">Read-only — every action recorded under your name, newest first</p>
        </div>

        @if($logs->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-slate-500 font-semibold">No activity recorded for {{ $fy }}.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-[9px] uppercase tracking-wider text-slate-500 font-black border-b border-[#E5E7EB]">
                            <th class="px-4 py-2.5">Date</th>
                            <th class="px-4 py-2.5">Time</th>
                            <th class="px-4 py-2.5">Activity</th>
                            <th class="px-4 py-2.5">KPI</th>
                            <th class="px-4 py-2.5">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($logs as $log)
                            @php $meta = $typeMeta[$log['type']] ?? ['label' => $log['label'], 'bg' => '#F1F5F9', 'text' => '#64748B']; @endphp
                            <tr>
                                <td class="px-4 py-2.5 text-[11px] text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($log['at'])->format('d M Y') }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($log['at'])->format('h:i A') }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full" style="background:{{ $meta['bg'] }};color:{{ $meta['text'] }};">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-800 max-w-[220px] truncate">{{ $log['kpi_title'] }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-500">{{ $log['detail'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
</main>

</body>
</html>
