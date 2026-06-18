<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        input:focus, select:focus { outline: none; border-color: #6B3F2A; box-shadow: 0 0 0 3px rgba(107,63,42,.12); }
    </style>
</head>
<body class="bg-[#f4f7fb]">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300 bg-[#f4f7fb]">
<div class="p-4 space-y-4">

    {{-- HEADER --}}
    <div class="rounded-[18px] bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#a8cec6] text-white px-5 py-3.5 shadow-xl flex items-center justify-between gap-4">
        <div>
            <a href="/dashboard" class="text-[10px] text-blue-100 hover:text-white">← Dashboard</a>
            <h1 class="text-xl font-bold mt-1">User Activity Log</h1>
            <p class="text-white/70 text-[10px] mt-0.5">
                {{ $user['short_name'] ?? $user['full_name'] ?? '-' }} · {{ $user['role'] }} · {{ $user['department_code'] }} · {{ $fy }}
            </p>
        </div>
        <div class="text-right">
            <p class="text-[10px] text-blue-200">Total Events</p>
            <p class="text-2xl font-black">{{ $logs->count() }}</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    @php
        $types = [
            ''                    => 'All Activities',
            'kpi_created'         => 'KPI Created',
            'kpi_edited'          => 'KPI Edited',
            'update_submitted'    => 'Update Submitted',
            'update_approved'     => 'Update Approved',
            'update_rejected'     => 'Update Rejected',
            'completion_submitted'=> 'Completion Submitted',
            'delete_requested'    => 'Delete Requested',
        ];
        $typeColors = [
            'kpi_created'         => 'bg-[#F5EAE0] text-[#6B3F2A] border-[#A07060]',
            'kpi_edited'          => 'bg-indigo-100 text-indigo-700 border-indigo-300',
            'update_submitted'    => 'bg-amber-100 text-amber-700 border-amber-300',
            'update_approved'     => 'bg-green-100 text-green-700 border-green-300',
            'update_rejected'     => 'bg-red-100 text-red-700 border-red-300',
            'completion_submitted'=> 'bg-purple-100 text-purple-700 border-purple-300',
            'delete_requested'    => 'bg-rose-100 text-rose-700 border-rose-300',
        ];
        $dotColors = [
            'blue'   => 'bg-[#6B3F2A]',
            'indigo' => 'bg-indigo-500',
            'amber'  => 'bg-amber-500',
            'green'  => 'bg-emerald-500',
            'red'    => 'bg-red-500',
            'purple' => 'bg-purple-500',
            'rose'   => 'bg-rose-500',
        ];
        $badgeBg = [
            'blue'   => 'bg-[#F5EAE0] text-[#6B3F2A]',
            'indigo' => 'bg-indigo-100 text-indigo-700',
            'amber'  => 'bg-amber-100 text-amber-800',
            'green'  => 'bg-emerald-100 text-emerald-700',
            'red'    => 'bg-red-100 text-red-700',
            'purple' => 'bg-purple-100 text-purple-700',
            'rose'   => 'bg-rose-100 text-rose-700',
        ];
    @endphp

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3">
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold mr-1">Filter:</span>
            @foreach($types as $typeKey => $typeLabel)
                <a href="{{ route('activity-log') }}{{ $typeKey ? '?type=' . $typeKey : '' }}"
                   class="text-[11px] px-3 py-1 rounded-full border font-semibold transition
                   {{ $typeFilter === $typeKey
                       ? 'bg-[#6B3F2A] text-white border-[#6B3F2A]'
                       : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                    {{ $typeLabel }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- TIMELINE --}}
    @if($logs->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-16 text-center">
            <div class="text-4xl mb-3">📋</div>
            <p class="text-slate-500 font-semibold">No activity found</p>
            <p class="text-slate-400 text-xs mt-1">
                {{ $typeFilter ? 'No events of this type yet.' : 'No activity recorded yet for ' . $fy . '.' }}
            </p>
        </div>
    @else
        @php
            $grouped = $logs->groupBy(fn($l) => \Carbon\Carbon::parse($l['at'])->format('Y-m-d'));
        @endphp

        <div class="space-y-4">
            @foreach($grouped as $date => $dayLogs)
                @php
                    $dateLabel = \Carbon\Carbon::parse($date)->isToday()
                        ? 'Today'
                        : (\Carbon\Carbon::parse($date)->isYesterday()
                            ? 'Yesterday'
                            : \Carbon\Carbon::parse($date)->format('d M Y'));
                @endphp

                <div>
                    {{-- Date divider --}}
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2">{{ $dateLabel }}</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    {{-- Log entries for this day --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
                        @foreach($dayLogs as $log)
                            @php
                                $dot   = $dotColors[$log['color']] ?? 'bg-slate-400';
                                $badge = $badgeBg[$log['color']] ?? 'bg-slate-100 text-slate-600';
                                $time  = \Carbon\Carbon::parse($log['at'])->format('h:i A');
                            @endphp
                            <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition">
                                {{-- Dot --}}
                                <div class="mt-1.5 shrink-0 w-2.5 h-2.5 rounded-full {{ $dot }}"></div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full {{ $badge }}">
                                            {{ $log['label'] }}
                                        </span>
                                        <span class="text-[11px] font-semibold text-slate-800 truncate">
                                            {{ $log['kpi_title'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 truncate">
                                        {{ $log['detail'] }}
                                    </p>
                                </div>

                                {{-- Right side: who + time --}}
                                <div class="shrink-0 text-right">
                                    <p class="text-[11px] font-semibold text-slate-700">{{ $log['who'] }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $time }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Summary stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @php
                $typeCounts = $logs->groupBy('type')->map->count();
                $statCards = [
                    ['kpi_created',          'KPIs Created',    'blue',   '📝'],
                    ['kpi_edited',           'KPIs Edited',     'indigo', '✏️'],
                    ['update_submitted',     'Updates Sent',    'amber',  '📤'],
                    ['update_approved',      'Approved',        'green',  '✅'],
                    ['update_rejected',      'Rejected',        'red',    '❌'],
                    ['completion_submitted', 'Completions',     'purple', '🏁'],
                    ['delete_requested',     'Delete Requests', 'rose',   '🗑️'],
                ];
                $nonZero = array_filter($statCards, fn($s) => ($typeCounts[$s[0]] ?? 0) > 0);
            @endphp
            @foreach($nonZero as $stat)
                @php [$sType, $sLabel, $sColor, $sIcon] = $stat; @endphp
                <a href="{{ route('activity-log') }}?type={{ $sType }}"
                   class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3 hover:shadow-md transition flex items-center gap-3">
                    <span class="text-xl">{{ $sIcon }}</span>
                    <div>
                        <p class="text-[10px] text-slate-500">{{ $sLabel }}</p>
                        <p class="text-lg font-black text-slate-800">{{ $typeCounts[$sType] ?? 0 }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>
</main>

</body>
</html>
