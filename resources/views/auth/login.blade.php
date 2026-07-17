<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCG KPI Dashboard | Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .login-bg { background: radial-gradient(circle at top left, rgba(212,175,55,.10), transparent 32%), radial-gradient(circle at bottom right, rgba(200,16,46,.20), transparent 38%), linear-gradient(135deg, #06060a 0%, #1A0A0A 45%, #3a0212 78%, #0a0a0a 100%); }
    </style>
</head>

<body class="login-bg min-h-screen flex items-center justify-center p-6 relative overflow-hidden">

    <div class="pointer-events-none absolute -top-16 -left-16 w-72 h-72 rounded-full bg-[#D4AF37]/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-20 -right-10 w-80 h-80 rounded-full bg-[#C8102E]/25 blur-3xl"></div>

    <div class="relative w-full max-w-sm">

        <div class="bg-white rounded-2xl overflow-hidden shadow-[0_25px_70px_rgba(0,0,0,.55)] border-t-[3px] border-t-[#D4AF37]">
            <div class="px-8 pt-8 pb-6">

                <div class="flex flex-col items-center text-center mb-6">
                    <div class="w-20 h-20 rounded-2xl overflow-hidden ring-2 ring-[#D4AF37] shadow-lg mb-3">
                        <img src="{{ asset('images/AI-RCG.png') }}" alt="RCG" class="w-full h-full object-cover">
                    </div>
                    <h1 class="text-lg font-black text-slate-900 leading-tight">
                        RCG KPI Dashboard
                    </h1>
                    <p class="text-[10px] font-bold text-[#B8860B] uppercase tracking-[0.16em] mt-1">
                        Performance System
                    </p>
                </div>

                @if(session('error'))
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] focus:outline-none transition"
                            placeholder="name@richworks.com"
                            required
                            autofocus
                        >
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Password
                            </label>
                            <a href="{{ route('password.forgot') }}" class="text-[11px] font-bold text-[#7A0019] hover:text-[#C8102E] transition">
                                Forgot password?
                            </a>
                        </div>
                        <input
                            type="password"
                            name="password"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] focus:outline-none transition"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#D4AF37] hover:bg-[#c19c2f] py-3 text-sm font-black text-[#1a1a1a] transition shadow-md hover:-translate-y-0.5"
                    >
                        Login
                    </button>
                </form>
            </div>

            <div class="bg-gradient-to-r from-[#1A0A0A] to-[#7A0019] px-8 py-3.5 text-center">
                <p class="text-[10px] text-white/60 font-semibold">
                    Please contact HR if you do not have login access.
                </p>
            </div>
        </div>

    </div>

</body>
</html>
