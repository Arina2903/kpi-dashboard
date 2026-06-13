<!DOCTYPE html>
<html>
<head>
    <title>Select Company — RCG KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .brand-panel { background: radial-gradient(circle at top left,rgba(59,130,246,.16),transparent 30%), radial-gradient(circle at bottom right,rgba(20,184,166,.13),transparent 34%), linear-gradient(135deg,#06142f 0%,#0b1f45 52%,#020617 100%); }
    </style>
</head>

<body class="min-h-screen bg-[#f0f2f7] flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        {{-- Header card --}}
        <div class="brand-panel rounded-[20px] text-white px-6 py-6 shadow-xl mb-4">
            <div class="flex items-center gap-3 mb-1">
                <img src="/images/RCG-Logo.png" class="w-9 h-9 object-contain rounded-lg bg-white/10 p-1">
                <div>
                    <h1 class="text-base font-black">Select Company</h1>
                    <p class="text-[11px] text-blue-300">Choose which company dashboard to access</p>
                </div>
            </div>
            @if(session('user_name'))
            <p class="text-[10px] text-slate-400 mt-3">Logged in as <span class="text-white font-bold">{{ session('user_name') }}</span></p>
            @endif
        </div>

        {{-- Error --}}
        @if(session('error'))
            <div class="mb-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Company cards --}}
        <div class="space-y-3">
            @forelse($dashboards as $dashboard)
                <form method="POST" action="{{ route('dashboard.select') }}">
                    @csrf
                    <input type="hidden" name="employee_uuid" value="{{ $dashboard['employee_uuid'] }}">
                    <input type="hidden" name="company_code" value="{{ $dashboard['company_code'] }}">

                    <button type="submit" class="w-full text-left bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-300 transition-all group">
                        <div class="p-4 flex items-center gap-4">

                            {{-- Company logo / initials --}}
                            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 overflow-hidden border border-slate-200">
                                @if(!empty($dashboard['company_logo']) && $dashboard['company_logo'] !== '/images/default-logo.png')
                                    <img src="{{ $dashboard['company_logo'] }}" class="w-full h-full object-contain p-1">
                                @else
                                    <span class="text-sm font-black text-slate-600">{{ strtoupper(substr($dashboard['company_code'],0,2)) }}</span>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black text-slate-900 truncate">{{ $dashboard['company_display_name'] ?? $dashboard['company_name'] ?? $dashboard['company_code'] }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $dashboard['full_name'] ?? $dashboard['short_name'] ?? 'Employee' }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-black border border-indigo-100">{{ $dashboard['role'] }}</span>
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">{{ $dashboard['department_code'] }}</span>
                                    @if($dashboard['is_default'] ?? false)
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-100">Default</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <div class="text-slate-300 group-hover:text-blue-500 transition shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </button>
                </form>
            @empty
                <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    No company access found for this account.
                </div>
            @endforelse
        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
            @csrf
            <button class="text-xs text-slate-400 hover:text-slate-700 transition">← Back to login</button>
        </form>

    </div>

</body>
</html>
