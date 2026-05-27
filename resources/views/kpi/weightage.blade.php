<!DOCTYPE html>
<html>
<head>
    <title>Manage Weightage</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=line-clamp"></script>

    <style>
        .glass{
            background:rgba(255,255,255,.9);
            backdrop-filter:blur(14px);
        }

        .card-hover{
            transition:.2s ease;
        }

        .card-hover:hover{
            transform:translateY(-2px);
            box-shadow:0 18px 35px rgba(15,23,42,.08);
        }

        .weightage-input::-webkit-outer-spin-button,
        .weightage-input::-webkit-inner-spin-button{
            -webkit-appearance:none;
            margin:0;
        }

        .weightage-input{
            -moz-appearance:textfield;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">

<div class="p-6 space-y-6">

    <!-- HEADER -->

    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white p-6 shadow-xl">

        <a href="/kpi"
           class="text-sm text-blue-100 hover:text-white">
            ← Back to KPI List
        </a>

        <h1 class="text-3xl font-bold mt-3">
            Manage Weightage
        </h1>

    </div>

    @php

        $currentEmployeeId = (string) ($user['id'] ?? '');

        $myKpis = collect($kpis ?? [])->filter(function ($item) use (
            $currentEmployeeId
        ) {

            $kpiEmployeeId = (string) ($item['employee_id'] ?? '');

            /*
                STRICT PERSONAL KPI ONLY

                ONLY calculate KPI that belong to this exact employee_id.
                Do not include:
                - created_by
                - manager created KPI
                - VP created KPI
                - subordinate KPI
                - department KPI
            */

            return $kpiEmployeeId === $currentEmployeeId;

        });

        $calculateScore = function ($item) {

            $quarters = collect($item['quarters'] ?? []);

            $baseTotal = 0;
            $actualTotal = 0;

            foreach (['Q1','Q2','Q3','Q4'] as $quarter) {

                $row = $quarters->firstWhere('quarter', $quarter) ?? [];

                $baseTotal += (float) ($row['quarter_target'] ?? 0);
                $actualTotal += (float) ($row['quarter_actual'] ?? 0);
            }

            if ($baseTotal <= 0) {

                $baseTotal = (float) ($item['base_target'] ?? 0);
                $actualTotal = (float) ($item['actual_value'] ?? 0);
            }

            return $baseTotal > 0
                ? round(($actualTotal / $baseTotal) * 100, 2)
                : 0;
        };

        $weightageTotal = round(
            $myKpis->sum(fn($item) => (float) ($item['weightage'] ?? 0)),
            2
        );

        $remaining = round(100 - $weightageTotal, 2);

        $individualPerformance = round(
            $myKpis->sum(function($item) use ($calculateScore){

                $score = $calculateScore($item);
                $weightage = (float) ($item['weightage'] ?? 0);

                return ($score * $weightage) / 100;

            }),
            2
        );

    @endphp

    <!-- SUMMARY -->

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">

            <p class="text-xs uppercase font-bold text-slate-400">
                My KPI
            </p>

            <h2 class="text-2xl font-black text-slate-900 mt-2">
                {{ $myKpis->count() }}
            </h2>

        </div>

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">

            <p class="text-xs uppercase font-bold text-slate-400">
                Total Weightage
            </p>

            <h2 id="weightageTotalText"
                class="text-2xl font-black
                {{ $weightageTotal > 100 ? 'text-red-700' : ($weightageTotal == 100 ? 'text-emerald-700' : 'text-amber-700') }}">

                {{ number_format($weightageTotal, 2) }}%

            </h2>

        </div>

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">

            <p class="text-xs uppercase font-bold text-slate-400">
                Remaining
            </p>

            <h2 id="weightageRemainingText"
                class="text-2xl font-black
                {{ $remaining < 0 ? 'text-red-700' : ($remaining == 0 ? 'text-emerald-700' : 'text-amber-700') }}">

                {{ number_format($remaining, 2) }}%

            </h2>

        </div>

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">

            <p class="text-xs uppercase font-bold text-slate-400">
                Individual Performance
            </p>

            <h2 id="individualPerformanceText"
                class="text-2xl font-black text-blue-700">

                {{ number_format($individualPerformance, 2) }}%

            </h2>

        </div>

    </div>

    <!-- ACTION -->

    <div class="glass rounded-3xl p-4 border border-white/70 flex flex-wrap gap-3">

        <button
            type="button"
            onclick="balanceEmptyWeightage()"
            class="bg-amber-100 hover:bg-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-black">

            Balance Empty

        </button>

        <button
            type="button"
            onclick="confirmEqualizeWeightage()"
            class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white px-5 py-3 rounded-xl text-sm font-black">

            Equalize All

        </button>

    </div>

    <!-- KPI WEIGHTAGE BOARD -->

    <form
        id="weightageForm"
        method="POST"
        action="{{ route('weightage.bulk-update') }}">

        @csrf

        <div class="space-y-5">

            @php

            $categoryOrder = [

                'Financial',
                'Growth & Customer',
                'Initiatives',
                'People'

            ];
                $categoryStyles = [

                    'Financial' => [
                        'bg' => 'bg-emerald-700 text-white',
                        'sub' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
                    ],

                    'Growth & Customer' => [
                        'bg' => 'bg-indigo-700 text-white',
                        'sub' => 'bg-indigo-50 text-indigo-800 border border-indigo-200',
                    ],

                    'Initiatives' => [
                        'bg' => 'bg-amber-600 text-white',
                        'sub' => 'bg-amber-50 text-amber-800 border border-amber-200',
                    ],

                    'People' => [
                        'bg' => 'bg-pink-700 text-white',
                        'sub' => 'bg-pink-50 text-pink-800 border border-pink-200',
                    ],

                    'Default' => [
                        'bg' => 'bg-slate-700 text-white',
                        'sub' => 'bg-slate-50 text-slate-800 border border-slate-200',
                    ],

                ];

            $groupedKpis = collect();

            foreach($categoryOrder as $category){

                $items = $myKpis
                    ->where('category', $category);

                if($items->count()){

                    $groupedKpis[$category] = $items;
                }
            }

            @endphp

            @foreach($groupedKpis as $category => $categoryKpis)

                @php

                    $categoryWeightage = round(
                        $categoryKpis->sum(fn($item)
                            => (float)($item['weightage'] ?? 0)),
                        2
                    );

                    $style =
                        $categoryStyles[$category]
                        ?? $categoryStyles['Default'];

                @endphp

                <!-- CATEGORY -->

                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">

                    <!-- CATEGORY HEADER -->

                    <div class="px-4 py-3 border-b border-slate-100 {{ $style['bg'] }}">
                        <div class="flex items-center justify-between">

                            <div>

                                <h2 class="text-sm font-black tracking-wide uppercase" tracking-wide uppercase">
                                    {{ $category ?: 'General' }}
                                </h2>

                                <p class="text-xs text-white/70 mt-1">
                                    {{ count($categoryKpis) }} KPI
                                </p>

                            </div>

                            <div class="text-right">

                                <p class="text-[10px] uppercase text-white/70 font-black">
                                    Category Weightage
                                </p>

                                <h3
                                    class="text-lg font-black text-white mt-1 category-total"
                                    data-category="{{ Str::slug($category) }}">
                                    {{ number_format($categoryWeightage,2) }}%
                                </h3>

                            </div>

                        </div>

                    </div>

                    <!-- KPI -->

                    <div class="divide-y divide-slate-100">

                        @foreach($categoryKpis as $kpi)

                            <div class="p-3">

                                <div class="flex flex-col xl:flex-row xl:items-start gap-3">

                                    <!-- LEFT -->

                                    <div class="flex-1 min-w-0">

                                        <div class="flex flex-wrap gap-2 mb-2">

                                            <span class="
                                                px-2.5 py-1 rounded-lg text-[10px]
                                                font-black uppercase tracking-wide
                                                {{ $style['sub'] }}
                                            ">
                                                {{ $kpi['sub_category'] ?? '-' }}
                                            </span>
                                        </div>

                                        <h3 class="text-sm font-bold text-slate-900 leading-snug">
                                            {{ $kpi['kpi_title'] }}
                                        </h3>

                                        <p class="text-[11px] text-slate-500 mt-0.5 leading-tight line-clamp-2">
                                            {{ $kpi['kpi_description'] ?? '-' }}
                                        </p>

                                    </div>

                                    <!-- RIGHT -->

                                    <div class="w-full xl:w-[220px] shrink-0">

                                        <div data-kpi-wrapper>

                                            <!-- WEIGHTAGE INPUT -->

                                            <div class="grid grid-cols-1 gap-2">

                                                <div>

                                                    <label class="text-[10px] uppercase font-black text-slate-400">
                                                        Weightage %
                                                    </label>

                                                    <input
                                                        type="number"
                                                        name="weightages[{{ $kpi['id'] }}]"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        value="{{ number_format((float)($kpi['weightage'] ?? 0),2,'.','') }}"
                                                        data-original="{{ number_format((float)($kpi['weightage'] ?? 0),2,'.','') }}"
                                                        data-kpi-id="{{ $kpi['id'] }}"
                                                        class="weightage-input w-full mt-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-black text-slate-900 transition focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400"
                                                        data-category="{{ Str::slug($category) }}"
                                                        oninput="recalculateWeightage(this)"
                                                        onkeydown="return event.key !== '-'">

                                                </div>

                                            </div>

                                            <div class="mt-2 rounded-xl border border-slate-100 bg-slate-50 px-2.5 py-2">

                                            <!-- ACTION FLOW -->

                                            <div class="flex items-center justify-between gap-2">

                                                <div class="min-w-0">

                                                    <div class="save-status text-[11px] font-bold text-slate-500 truncate">
                                                        Synced
                                                    </div>

                                                    <p class="text-[10px] text-slate-400">
                                                        Saved individually
                                                    </p>

                                                </div>

                                                <button
                                                    type="button"
                                                    class="save-kpi-btn rounded-lg bg-slate-300 text-white px-2.5 py-1 text-[11px] font-black transition"
                                                    disabled
                                                    onclick="saveSingleWeightage(this, '{{ $kpi['id'] }}')">

                                                    Save

                                                </button>

                                            </div>

                                        </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </div>

    </form>

</div>

</main>

<script>

function equalizeAllWeightage(){

    const inputs =
        Array.from(
            document.querySelectorAll('.weightage-input')
        );

    if(inputs.length <= 0) return;

    const equal =
        Math.floor((100 / inputs.length) * 100) / 100;

    let assigned = 0;

    inputs.forEach((input,index) => {

        if(index === inputs.length - 1){

            input.value =
                (100 - assigned).toFixed(2);

        }else{

            input.value = equal.toFixed(2);

            assigned += equal;
        }

    });

    recalculateWeightage();
}

function confirmEqualizeWeightage(){

    const proceed = confirm(
        'Equalize all KPI weightage? This will overwrite current allocation.'
    );

    if(!proceed) return;

    equalizeAllWeightage();
}

function balanceEmptyWeightage(){

    const inputs =
        Array.from(
            document.querySelectorAll('.weightage-input')
        );

    const emptyInputs =
        inputs.filter(input =>
            parseFloat(input.value || 0) <= 0
        );

    if(emptyInputs.length <= 0) return;

    let used = 0;

    inputs.forEach(input => {

        if(!emptyInputs.includes(input)){

            used += parseFloat(input.value || 0);
        }
    });

    const balance =
        Math.max(0, 100 - used);

    const share =
        Math.floor(
            (balance / emptyInputs.length) * 100
        ) / 100;

    emptyInputs.forEach((input,index) => {

        if(index === emptyInputs.length - 1){

            const usedShare =
                share * (emptyInputs.length - 1);

            input.value =
                (balance - usedShare).toFixed(2);

        }else{

            input.value = share.toFixed(2);
        }

    });

    recalculateWeightage();
}

function recalculateWeightage(changedInput = null){

    const inputs =
        Array.from(
            document.querySelectorAll('.weightage-input')
        );

    let total = 0;

    inputs.forEach(input => {

        total += parseFloat(input.value || 0);

    });

    total = Math.round(total * 100) / 100;

    /*
    |--------------------------------------------------------------------------
    | TOP SUMMARY
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        'weightageTotalText'
    ).innerText = total.toFixed(2) + '%';

    const remaining =
    Math.max(0, 100 - total);

    document.getElementById(
        'weightageRemainingText'
    ).innerText =
        remaining.toFixed(2) + '%';

    /*
    |--------------------------------------------------------------------------
    | CATEGORY TOTAL
    |--------------------------------------------------------------------------
    */

    const categoryTotals = {};

    inputs.forEach(input => {

        const category =
            input.dataset.category;

        const value =
            parseFloat(input.value || 0);

        if(!categoryTotals[category]){

            categoryTotals[category] = 0;
        }

        categoryTotals[category] += value;
    });

    document.querySelectorAll('.category-total')
        .forEach(el => {

        const category =
            el.dataset.category;

        el.innerText =
            (categoryTotals[category] || 0).toFixed(2) + '%';

    });

    /*
    |--------------------------------------------------------------------------
    | EACH KPI
    |--------------------------------------------------------------------------
    */

    inputs.forEach(input => {

        const value =
            parseFloat(input.value || 0);

        const wrapper =
            input.closest('[data-kpi-wrapper]');

        const saveBtn =
            wrapper.querySelector('.save-kpi-btn');

        const saveStatus =
            wrapper.querySelector('.save-status');


        const original =
            parseFloat(input.dataset.original || 0);

        const changed =
            value !== original;

        /*
        |--------------------------------------------------------------------------
        | EXCEEDED
        |--------------------------------------------------------------------------
        */

        if(total > 100){

            input.classList.add(
                'border-red-500',
                'ring-2',
                'ring-red-200'
            );

            saveBtn.disabled = true;

            saveBtn.className =
                'save-kpi-btn rounded-xl bg-red-200 text-red-700 px-3 py-2 text-xs font-black transition';

            saveStatus.innerText =
                'Total exceeds 100%';

            saveStatus.className =
                'save-status text-[13px] font-bold text-red-600 mt-1';

            return;
        }

        input.classList.remove(
            'border-red-500',
            'ring-2',
            'ring-red-200'
        );

        /*
        |--------------------------------------------------------------------------
        | CHANGED
        |--------------------------------------------------------------------------
        */

        if(changed){

            saveBtn.disabled = false;

            saveBtn.className =
                'save-kpi-btn rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 text-xs font-black transition';

            saveStatus.innerText =
                'Pending Save';

            saveStatus.className =
                'save-status text-[13px] font-bold text-amber-600 mt-1';

        }else{

            saveBtn.disabled = true;

            saveBtn.className =
                'save-kpi-btn rounded-xl bg-slate-300 text-white px-3 py-2 text-xs font-black transition';

            saveStatus.innerText =
                'Synced';

            saveStatus.className =
                'save-status text-[13px] font-bold text-slate-500 mt-1';
        }

    });

}

function saveSingleWeightage(button, kpiId){

    const input =
        document.querySelector(
            `[data-kpi-id="${kpiId}"]`
        );

    if(!input) return;

    const value =
        parseFloat(input.value || 0);

    button.disabled = true;

    button.innerText = 'Saving...';

    /*
    |--------------------------------------------------------------------------
    | SUBMIT ONLY THIS KPI
    |--------------------------------------------------------------------------
    */

    fetch('{{ route("weightage.bulk-update") }}', {

        method: 'POST',

        headers: {

            'Content-Type': 'application/json',

            'X-CSRF-TOKEN':
                '{{ csrf_token() }}',

            'Accept':
                'application/json'

        },

        body: JSON.stringify({

            weightages: {

                [kpiId]: value

            }

        })

    })

    .then(res => res.json())

    .then(data => {

        input.dataset.original =
            value.toFixed(2);

        recalculateWeightage();

        button.innerText =
            'Saved';

        button.className =
            'save-kpi-btn rounded-xl bg-emerald-600 text-white px-3 py-2 text-xs font-black transition';

        setTimeout(() => {

            recalculateWeightage();

        }, 1000);

    })

    .catch(() => {

        button.innerText =
            'Failed';

        button.className =
            'save-kpi-btn rounded-xl bg-red-600 text-white px-3 py-2 text-xs font-black transition';

    });

}

recalculateWeightage();

</script>

</body>
</html>
