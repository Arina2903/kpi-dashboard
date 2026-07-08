<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appraise Team · {{ $currentFinancialYear }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>*, body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">

@include('partials.sidebar')

<main class="ml-[230px] p-8">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
            <a href="/performance/report/q2" class="text-slate-400 hover:text-slate-600 text-xs font-semibold">← Back</a>
        </div>
        <h1 class="text-2xl font-black text-slate-800">Appraise Team</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $currentFinancialYear }} · Review and complete appraisals for your direct reports.</p>
    </div>

    @if(empty($subordinates))
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
        <div class="text-4xl mb-3">👥</div>
        <p class="text-slate-500 font-semibold">No direct reports found.</p>
        <p class="text-slate-400 text-sm mt-1">Employees who have you set as their reports-to will appear here.</p>
    </div>
    @else

    {{-- Table per quarter --}}
    @foreach($quarters as $q)
    @php
        $hasAny = false;
        foreach ($subordinates as $sub) {
            if (!empty($reportMap[$sub['id']][$q])) { $hasAny = true; break; }
        }
    @endphp

    <div class="mb-8">
        <div class="flex items-center gap-3 mb-3">
            <span class="text-xs font-black uppercase tracking-widest text-white bg-[#1a3d34] px-3 py-1 rounded-full">{{ $q }}</span>
            <span class="text-xs text-slate-400 font-semibold">{{ $currentFinancialYear }}</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#1a3d34] text-white text-[11px] uppercase tracking-widest">
                        <th class="text-left px-5 py-3 font-black">Name</th>
                        <th class="text-left px-5 py-3 font-black">Position</th>
                        <th class="text-left px-5 py-3 font-black">Department</th>
                        <th class="text-center px-5 py-3 font-black">Status</th>
                        <th class="text-center px-5 py-3 font-black">Last Updated</th>
                        <th class="text-center px-5 py-3 font-black">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($subordinates as $sub)
                    @php
                        $report = $reportMap[$sub['id']][$q] ?? null;
                        $status = $report['status'] ?? 'not_started';
                        $updatedAt = $report['updated_at'] ?? null;
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-4">
                            <p class="font-bold text-slate-800 text-sm">{{ $sub['full_name'] ?? $sub['short_name'] ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-4 text-slate-500 text-xs">{{ $sub['position'] ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-500 text-xs">{{ $sub['department_code'] ?? '—' }}</td>
                        <td class="px-5 py-4 text-center">
                            @if($status === 'completed')
                            <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 text-[10px] font-black px-2.5 py-1 rounded-full">✓ Completed</span>
                            @elseif($status === 'appraised')
                            <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 text-[10px] font-black px-2.5 py-1 rounded-full">✍ Awaiting Appraisee Signature</span>
                            @elseif($status === 'submitted')
                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-[10px] font-black px-2.5 py-1 rounded-full">↑ Submitted</span>
                            @elseif($status === 'draft')
                            <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 text-[10px] font-black px-2.5 py-1 rounded-full">✎ Draft</span>
                            @else
                            <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 text-[10px] font-black px-2.5 py-1 rounded-full">— Not Started</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center text-[11px] text-slate-400">
                            @if($updatedAt)
                                {{ \Carbon\Carbon::parse($updatedAt)->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($status === 'submitted')
                            <a href="/performance/appraise/{{ $sub['id'] }}/{{ strtolower($q) }}"
                               class="inline-flex items-center gap-1 bg-[#1a3d34] hover:bg-[#2d5548] text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition">
                                Review &amp; Appraise →
                            </a>
                            @elseif($status === 'appraised' || $status === 'completed')
                            <a href="/performance/appraise/{{ $sub['id'] }}/{{ strtolower($q) }}"
                               class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[11px] font-bold px-3 py-1.5 rounded-lg transition">
                                View
                            </a>
                            @else
                            <span class="text-slate-300 text-[11px]">Waiting for submission</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    @endif

</main>
</body>
</html>
