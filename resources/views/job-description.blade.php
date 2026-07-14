<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Description</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .soft-card { box-shadow: 0 8px 30px rgba(15,23,42,.07); }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">
<div class="p-4 max-w-3xl mx-auto space-y-4">

    <a href="/dashboard" class="text-[10px] text-slate-500 hover:text-slate-800">← Dashboard</a>

    {{-- HEADER --}}
    <div class="bg-white rounded-2xl overflow-hidden soft-card border border-[#6B9080]">
        <div class="h-1 bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#A4C3B2]"></div>
        <div class="p-5 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#CCE3DE] flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-[#1a3d34]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="7" width="18" height="13" rx="2"/>
                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <path d="M3 12h18M10 12v2h4v-2"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[9px] uppercase tracking-widest font-black text-slate-400">Job Description</p>
                <h1 class="text-lg font-black text-slate-900 leading-tight truncate mt-0.5">
                    {{ $jobDescription['job_title'] ?? $user['position'] ?? 'Not yet titled' }}
                </h1>
                <p class="text-[12px] text-slate-500 mt-0.5">
                    {{ $user['full_name'] ?? $user['short_name'] ?? '-' }}
                </p>
                <div class="flex flex-wrap gap-1.5 mt-2">
                    <span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full bg-[#CCE3DE] text-[#1a3d34]">
                        {{ $user['role'] ?? '-' }}
                    </span>
                    <span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                        {{ $department['name'] ?? $user['department_code'] ?? '-' }}
                    </span>
                    @if(!empty($jobDescription['updated_at']))
                    <span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                        Updated {{ \Carbon\Carbon::parse($jobDescription['updated_at'])->format('d M Y') }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(empty($jobDescription))

    {{-- EMPTY STATE --}}
    <div class="bg-white rounded-2xl soft-card border border-slate-200 p-8 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="7" width="18" height="13" rx="2"/>
                <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                <path d="M3 12h18M10 12v2h4v-2"/>
            </svg>
        </div>
        <p class="text-[13px] font-black text-slate-800">No job description on file yet</p>
        <p class="text-[12px] text-slate-500 mt-1 max-w-sm mx-auto">
            Your job description hasn't been set up yet. Please contact HR or your department admin to have it added.
        </p>
    </div>

    @else

        @if(!empty($jobDescription['summary']))
        {{-- SUMMARY --}}
        <div class="bg-white rounded-2xl soft-card border border-slate-200 p-5">
            <p class="text-[9px] uppercase tracking-widest font-black text-slate-400 mb-3">Overview</p>
            <p class="text-[13px] text-slate-700 leading-relaxed whitespace-pre-line">
                {{ $jobDescription['summary'] }}
            </p>
        </div>
        @endif

        @if(!empty($jobDescription['responsibilities']))
        {{-- RESPONSIBILITIES --}}
        <div class="bg-white rounded-2xl soft-card border border-slate-200 p-5">
            <p class="text-[9px] uppercase tracking-widest font-black text-slate-400 mb-3">Key Responsibilities</p>
            <ol class="space-y-2.5">
                @foreach($jobDescription['responsibilities'] as $i => $item)
                <li class="flex items-start gap-3">
                    <span class="shrink-0 w-5 h-5 rounded-full bg-[#6B9080]/15 text-[#1a3d34] text-[10px] font-black flex items-center justify-center mt-0.5">
                        {{ $i + 1 }}
                    </span>
                    <span class="text-[13px] text-slate-700 leading-relaxed">{{ $item }}</span>
                </li>
                @endforeach
            </ol>
        </div>
        @endif

        @if(!empty($jobDescription['requirements']))
        {{-- REQUIREMENTS --}}
        <div class="bg-white rounded-2xl soft-card border border-slate-200 p-5">
            <p class="text-[9px] uppercase tracking-widest font-black text-slate-400 mb-3">Requirements &amp; Qualifications</p>
            <ul class="space-y-2.5">
                @foreach($jobDescription['requirements'] as $item)
                <li class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-[#6B9080] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>
                    <span class="text-[13px] text-slate-700 leading-relaxed">{{ $item }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- REPORTING LINE --}}
        @if(!empty($jobDescription['reporting_line']) || $manager)
        <div class="bg-white rounded-2xl soft-card border border-slate-200 p-5">
            <p class="text-[9px] uppercase tracking-widest font-black text-slate-400 mb-3">Reporting Line</p>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#CCE3DE] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-[#1a3d34]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[13px] font-semibold text-slate-800">
                        {{ $jobDescription['reporting_line'] ?? ($manager['short_name'] ?? $manager['full_name'] ?? '-') }}
                    </p>
                    @if(empty($jobDescription['reporting_line']) && !empty($manager['position']))
                    <p class="text-[11px] text-slate-500">{{ $manager['position'] }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

    @endif

</div>
</main>

</body>
</html>
