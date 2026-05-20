<!DOCTYPE html>
<html>
<head>
    <title>Choose Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg p-7">

        <div class="flex items-center gap-3 mb-6">
            <img src="/images/RCG-Logo.png" class="w-10 h-10 object-contain">

            <div>
                <h1 class="text-base font-bold text-slate-900">
                    Choose Dashboard
                </h1>
                <p class="text-xs text-slate-500">
                    Select which company dashboard you want to view
                </p>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="space-y-3">

            @forelse($dashboards as $dashboard)
                <form method="POST" action="{{ route('dashboard.select') }}">
                    @csrf

                    <input type="hidden" name="employee_uuid" value="{{ $dashboard['employee_uuid'] }}">
                    <input type="hidden" name="company_code" value="{{ $dashboard['company_code'] }}">

                    <button
                        type="submit"
                        class="w-full text-left rounded-2xl border border-slate-200 p-4 hover:bg-slate-50 hover:border-slate-400 transition"
                    >
                        <div class="flex items-start justify-between gap-4">

                            <div>
                                <div class="text-sm font-bold text-slate-900">
                                    {{ $dashboard['company_name'] ?? $dashboard['company_code'] }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $dashboard['full_name'] ?? $dashboard['short_name'] ?? 'Employee' }}
                                </div>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $dashboard['role'] }}
                                    </span>

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $dashboard['department_code'] }}
                                    </span>

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $dashboard['employee_id'] }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-xs text-slate-400 pt-1">
                                Select →
                            </div>

                        </div>
                    </button>
                </form>
            @empty
                <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    No dashboard access found for this account.
                </div>
            @endforelse

        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-5">
            @csrf
            <button class="text-xs text-slate-400 hover:text-slate-700">
                Logout
            </button>
        </form>

    </div>

</body>
</html>
