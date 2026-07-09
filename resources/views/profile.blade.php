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

    <a href="/dashboard" class="text-[10px] text-slate-500 hover:text-slate-800">← Dashboard</a>

    {{-- HEADER --}}
    <div class="bg-white rounded-2xl overflow-hidden soft-card border border-[#6B9080]">
        <div class="h-1 bg-gradient-to-r from-[#1a3d34] via-[#6B9080] to-[#A4C3B2]"></div>
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

    {{-- TELEGRAM --}}
    <div class="bg-white rounded-2xl soft-card border border-slate-200 p-5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-full bg-[#229ED9]/10 flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="#229ED9"><path d="M21.94 4.53a1.6 1.6 0 0 0-1.63-.27L2.98 10.98a1.53 1.53 0 0 0 .1 2.88l4.54 1.42 1.76 5.5c.14.44.5.72.94.72.03 0 .06 0 .1-.01.34-.03.63-.24.77-.55l2.15-3.9 4.5 3.3c.24.18.53.27.82.27.14 0 .29-.02.43-.07a1.5 1.5 0 0 0 1-1.1l3.03-13.7a1.6 1.6 0 0 0-.62-1.74Zm-3.35 2.68-8.03 7.28-.31 3.35-1.35-4.22 8.6-6.9c.2-.16.42.1.24.28l-6.9 6.24a.5.5 0 0 0-.15.3l-.2 2.13 8.6-9.7c.2-.23.5.03.33.24Z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[13px] font-black text-slate-900">Telegram Notifications</p>
                    <p id="tg-status-text" class="text-[11px] text-slate-500 mt-0.5">Checking status…</p>
                </div>
            </div>
            <button
                id="tg-connect-btn"
                type="button"
                onclick="connectTelegram()"
                class="text-[11px] font-black px-3 py-2 rounded-xl bg-[#6B9080] text-white hover:bg-[#5a7a6d] transition shrink-0"
            >
                Connect Telegram
            </button>
        </div>
    </div>

</div>
</main>

<script>
    const TG_CSRF = '{{ csrf_token() }}';
    let tgPollTimer = null;

    async function refreshTelegramStatus() {
        try {
            const res = await fetch('{{ route("profile.telegram.status") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();

            const statusText = document.getElementById('tg-status-text');
            const connectBtn = document.getElementById('tg-connect-btn');

            if (data.linked) {
                statusText.textContent = 'Connected' + (data.username ? ' as @' + data.username : '');
                statusText.className = 'text-[11px] text-emerald-600 font-semibold mt-0.5';
                connectBtn.textContent = 'Reconnect';
                if (tgPollTimer) { clearInterval(tgPollTimer); tgPollTimer = null; }
            } else {
                statusText.textContent = 'Not connected — link your Telegram to get daily KPI reminders.';
                statusText.className = 'text-[11px] text-slate-500 mt-0.5';
                connectBtn.textContent = 'Connect Telegram';
            }
        } catch (e) {
            // silent — leave "Checking status…" as-is on transient failure
        }
    }

    async function connectTelegram() {
        const res = await fetch('{{ route("profile.telegram.connect") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': TG_CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        window.open(data.deep_link, '_blank');

        document.getElementById('tg-status-text').textContent = 'Waiting for confirmation in Telegram…';

        let attempts = 0;
        if (tgPollTimer) clearInterval(tgPollTimer);
        tgPollTimer = setInterval(async () => {
            attempts++;
            await refreshTelegramStatus();
            if (attempts >= 40) { clearInterval(tgPollTimer); tgPollTimer = null; } // ~2 min at 3s
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', refreshTelegramStatus);
</script>

</body>
</html>
