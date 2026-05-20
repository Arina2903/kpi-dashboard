<!DOCTYPE html>
<html>
<head>
    <title>RichWorks KPI Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">

    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg p-7">

        <div class="flex items-center gap-3 mb-6">
            <img src="/images/RCG-Logo.png" class="w-10 h-10 object-contain">

            <div>
                <h1 class="text-base font-bold text-slate-900">
                    RichWorks KPI
                </h1>
                <p class="text-xs text-slate-500">
                    Multi-Company Dashboard Access
                </p>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
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
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Email
                </label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:ring-2 focus:ring-slate-800 focus:outline-none"
                    placeholder="name@richworks.com"
                    required
                    autofocus
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Password
                </label>
                <input
                    type="password"
                    name="password"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:ring-2 focus:ring-slate-800 focus:outline-none"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button
                type="submit"
                class="w-full rounded-xl bg-[#06142f] py-3 text-sm font-semibold text-white hover:bg-[#0b1f49] transition"
            >
                Login
            </button>
        </form>

        <div class="mt-5 text-center">
            <p class="text-xs text-slate-400">
                Please contact HR if you do not have login access.
            </p>
        </div>

    </div>

</body>
</html>
