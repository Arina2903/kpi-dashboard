<!DOCTYPE html>
<html>
<head>
    <title>Create Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">

<div class="bg-white w-full max-w-md rounded-[28px] shadow-xl p-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Create Your Password</h1>
        <p class="text-sm text-slate-500 mt-2">
            Untuk first-time login sahaja. Selepas ini, anda akan login guna email dan password.
        </p>
    </div>

    @if(session('error'))
        <div class="bg-red-50 text-red-700 p-4 rounded-2xl mb-5 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-50 text-green-700 p-4 rounded-2xl mb-5 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.first.submit') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold mb-2">Email Kerja</label>
            <input
                type="email"
                name="email"
                class="w-full border border-slate-200 bg-slate-50 px-4 py-3 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
            >
        </div>

        <div>
            <label class="block text-sm font-semibold mb-2">New Password</label>
            <input
                type="password"
                name="password"
                class="w-full border border-slate-200 bg-slate-50 px-4 py-3 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
            >
            <p class="text-xs text-slate-400 mt-2">
                Minimum 8 characters. Jangan guna password malas seperti 12345678. Itu bukan password, itu surrender.
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-2">Confirm Password</label>
            <input
                type="password"
                name="password_confirmation"
                class="w-full border border-slate-200 bg-slate-50 px-4 py-3 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
            >
        </div>

        <button class="w-full bg-[#06142f] hover:bg-[#0b1f49] text-white py-3 rounded-2xl font-bold">
            Create Password & Send Verification
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:underline">
            Back to login
        </a>
    </div>

</div>

</body>
</html>
