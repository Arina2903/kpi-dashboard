<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://ui-avatars.com">
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

    <div class="flex items-center justify-between">
        <a href="/dashboard" class="text-[10px] text-slate-500 hover:text-slate-800">← Dashboard</a>
        <a href="{{ route('settings') }}" class="inline-flex items-center gap-1.5 text-[11px] font-black px-3 py-1.5 rounded-xl bg-[#1a3d34] text-white hover:bg-[#2d5548] transition">
            ⚙️ Account Settings
        </a>
    </div>

    @if(session('success'))
    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-[12px] font-semibold text-emerald-700">
        ✓ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-[12px] font-semibold text-red-700">
        {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-[12px] font-semibold text-red-700">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- HEADER --}}
    <div class="bg-white rounded-2xl overflow-hidden soft-card border border-[#6B9080]">
        <div class="h-1 bg-gradient-to-r from-[#1A0A0A] to-[#7A0019]"></div>
        <div class="p-5 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full overflow-hidden shrink-0 ring-2 ring-[#6B9080]/40">
                <img
                    src="https://ui-avatars.com/api/?name={{ urlencode($user['short_name'] ?? $user['full_name'] ?? 'User') }}&background=6B9080&color=fff&size=64"
                    class="w-full h-full object-cover"
                    alt="Avatar"
                />
            </div>
            <div class="min-w-0">
                <h1 class="text-lg font-black text-slate-900 leading-tight truncate">
                    {{ $user['full_name'] ?? $user['short_name'] ?? '-' }}
                </h1>
                <p class="text-[12px] text-slate-500 mt-0.5">{{ $user['position'] ?? '-' }}</p>
                <div class="flex flex-wrap gap-1.5 mt-2">
                    <span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full bg-[#CCE3DE] text-[#1a3d34]">
                        {{ $user['role'] ?? '-' }}
                    </span>
                    <span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                        {{ $department['name'] ?? $user['department_code'] ?? '-' }}
                    </span>
                    <span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                        {{ session('company_display_name') ?? $user['company_code'] ?? '-' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- DETAILS --}}
    <div class="bg-white rounded-2xl soft-card border border-slate-200 p-5">
        <p class="text-[9px] uppercase tracking-widest font-black text-slate-400 mb-3">Employee Details</p>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <dt class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Employee ID</dt>
                <dd class="text-[13px] font-semibold text-slate-800 mt-0.5">{{ $user['employee_id'] ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Email</dt>
                <dd class="text-[13px] font-semibold text-slate-800 mt-0.5 truncate">{{ $user['email'] ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Department</dt>
                <dd class="text-[13px] font-semibold text-slate-800 mt-0.5">{{ $department['name'] ?? $user['department_code'] ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Company</dt>
                <dd class="text-[13px] font-semibold text-slate-800 mt-0.5">{{ session('company_display_name') ?? $user['company_code'] ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Reports To</dt>
                <dd class="text-[13px] font-semibold text-slate-800 mt-0.5">
                    {{ $manager['short_name'] ?? $manager['full_name'] ?? '-' }}
                    @if(!empty($manager['position']))
                        <span class="text-slate-400 font-normal">· {{ $manager['position'] }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Join Date</dt>
                <dd class="text-[13px] font-semibold text-slate-800 mt-0.5">
                    {{ !empty($user['join_date']) ? \Carbon\Carbon::parse($user['join_date'])->format('d M Y') : '-' }}
                </dd>
            </div>
        </dl>
    </div>

    <div class="text-center pt-2">
        <a href="{{ route('settings') }}" class="text-[11px] font-semibold text-[#4a7c6b] hover:text-[#2d5548]">
            Looking for Telegram, email, or password settings? Go to Account Settings →
        </a>
    </div>

</div>
</main>

</body>
</html>
