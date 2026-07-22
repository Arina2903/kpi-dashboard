<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>

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

    <div class="flex items-center justify-between">
        <a href="{{ route('profile') }}" class="text-[10px] text-slate-500 hover:text-slate-800">← Profile</a>
    </div>

    <div>
        <h1 class="text-lg font-black text-slate-900">Account Settings</h1>
        <p class="text-[12px] text-slate-500 mt-0.5">Notifications, email, and password — everything that isn't about who you are.</p>
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

    {{-- SECURITY --}}
    <div class="bg-white rounded-2xl soft-card border border-slate-200 overflow-hidden">
        <div class="h-1 bg-gradient-to-r from-[#1A0A0A] to-[#7A0019]"></div>
        <div class="p-5">
            <p class="text-[9px] uppercase tracking-widest font-black text-slate-400 mb-4">Account Security</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Change Email --}}
                <form method="POST" action="{{ route('profile.email.update') }}" class="space-y-2.5">
                    @csrf
                    <p class="text-[12px] font-black text-slate-800 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#6B9080]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/></svg>
                        Change Email
                    </p>
                    <input
                        type="email"
                        name="email"
                        placeholder="New email address"
                        required
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-[12px] focus:ring-2 focus:ring-[#6B9080]/40 focus:border-[#6B9080] focus:outline-none"
                    >
                    @include('partials.password-input', ['id' => 'curPwdForEmail', 'name' => 'current_password', 'placeholder' => 'Current password (to confirm)'])
                    <button type="submit" class="w-full text-[11px] font-black px-3 py-2.5 rounded-xl bg-[#1a3d34] text-white hover:bg-[#2d5548] transition">
                        Update Email
                    </button>
                </form>

                {{-- Change Password --}}
                <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-2.5">
                    @csrf
                    <p class="text-[12px] font-black text-slate-800 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#6B9080]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="9" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        Change Password
                    </p>
                    @include('partials.password-input', ['id' => 'curPwdForChange', 'name' => 'current_password', 'placeholder' => 'Current password'])
                    @include('partials.password-input', ['id' => 'newPwd', 'name' => 'password', 'placeholder' => 'New password (min 8 characters)', 'minlength' => 8])
                    @include('partials.password-input', ['id' => 'newPwdConfirm', 'name' => 'password_confirmation', 'placeholder' => 'Confirm new password', 'minlength' => 8])
                    <button type="submit" class="w-full text-[11px] font-black px-3 py-2.5 rounded-xl bg-[#1a3d34] text-white hover:bg-[#2d5548] transition">
                        Update Password
                    </button>
                </form>
            </div>

            <p class="text-[10px] text-slate-400 mt-4">
                Forgot your current password instead? <a href="{{ route('password.forgot') }}" class="font-semibold text-[#4a7c6b] hover:text-[#2d5548]">Reset it via email →</a>
            </p>
        </div>
    </div>

    @if(strtoupper(trim($user['department_code'] ?? '')) === 'BTS')
    {{-- BTS ADMIN --}}
    <a href="{{ route('admin.view-as') }}" class="block bg-white rounded-2xl soft-card border border-slate-200 overflow-hidden hover:border-[#6B9080] transition">
        <div class="p-5 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7s-8.27-2.94-9.54-7Z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[13px] font-black text-slate-900">BTS Admin — View As</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Open any employee's KPI dashboard directly for support.</p>
                </div>
            </div>
            <span class="text-slate-300 text-lg shrink-0">→</span>
        </div>
    </a>
    @endif

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
