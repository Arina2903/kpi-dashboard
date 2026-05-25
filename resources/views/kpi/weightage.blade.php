<!DOCTYPE html>
<html>
<head>
    <title>Manage Weightage</title>

    <script src="https://cdn.tailwindcss.com"></script>

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

        <p class="text-blue-100 text-sm mt-1">
            Allocate and divide KPI weightage for your own KPI only.
        </p>

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

        <div class="glass rounded-3xl p-5 border border-white/70">

            <p class="text-xs uppercase font-bold text-slate-400">
                My KPI
            </p>

            <h2 class="text-3xl font-black text-slate-900 mt-2">
                {{ $myKpis->count() }}
            </h2>

        </div>

        <div class="glass rounded-3xl p-5 border border-white/70">

            <p class="text-xs uppercase font-bold text-slate-400">
                Total Weightage
            </p>

            <h2 id="weightageTotalText"
                class="text-3xl font-black
                {{ $weightageTotal > 100 ? 'text-red-700' : ($weightageTotal == 100 ? 'text-emerald-700' : 'text-amber-700') }}">

                {{ number_format($weightageTotal, 2) }}%

            </h2>

        </div>

        <div class="glass rounded-3xl p-5 border border-white/70">

            <p class="text-xs uppercase font-bold text-slate-400">
                Remaining
            </p>

            <h2 id="weightageRemainingText"
                class="text-3xl font-black
                {{ $remaining < 0 ? 'text-red-700' : ($remaining == 0 ? 'text-emerald-700' : 'text-amber-700') }}">

                {{ number_format($remaining, 2) }}%

            </h2>

        </div>

        <div class="glass rounded-3xl p-5 border border-white/70">

            <p class="text-xs uppercase font-bold text-slate-400">
                Individual Performance
            </p>

            <h2 id="individualPerformanceText"
                class="text-3xl font-black text-blue-700">

                {{ number_format($individualPerformance, 2) }}%

            </h2>

        </div>

    </div>

    <!-- ACTION -->

    <div class="glass rounded-3xl p-5 border border-white/70 flex flex-wrap gap-3">

        <button
            type="button"
            onclick="balanceEmptyWeightage()"
            class="bg-amber-100 hover:bg-amber-200 text-amber-800 px-5 py-3 rounded-2xl text-sm font-black">

            Balance Empty

        </button>

        <button
            type="button"
            onclick="confirmEqualizeWeightage()"
            class="bg-slate-900 hover:bg-slate-700 text-white px-5 py-3 rounded-2xl text-sm font-black">

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

                @endphp

                <!-- CATEGORY -->

                <div class="glass rounded-3xl border border-white/70 overflow-hidden">

                    <!-- CATEGORY HEADER -->

                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">

                        <div class="flex items-center justify-between">

                            <div>

                                <h2 class="text-lg font-black text-slate-900">
                                    {{ $category ?: 'General' }}
                                </h2>

                                <p class="text-xs text-slate-500 mt-1">
                                    {{ count($categoryKpis) }} KPI
                                </p>

                            </div>

                            <div class="text-right">

                                <p class="text-[10px] uppercase text-slate-400 font-black">
                                    Category Weightage
                                </p>

                                <h3
                                    class="text-2xl font-black text-indigo-700 mt-1 category-total"
                                    data-category="{{ Str::slug($category) }}">
                                    {{ number_format($categoryWeightage,2) }}%
                                </h3>

                            </div>

                        </div>

                    </div>

                    <!-- KPI -->

                    <div class="divide-y divide-slate-100">

                        @foreach($categoryKpis as $kpi)

                            @php

                                $score = $calculateScore($kpi);

                                $weightedScore =
                                    ($score * (float)($kpi['weightage'] ?? 0))
                                    / 100;

                            @endphp

                            <div class="p-5">

                                <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-center">

                                    <!-- LEFT -->

                                    <div class="xl:col-span-7">

                                        <div class="flex flex-wrap gap-2 mb-3">

                                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-black">
                                                {{ $kpi['sub_category'] ?? '-' }}
                                            </span>

                                        </div>

                                        <h3 class="text-lg font-black text-slate-900 leading-snug">
                                            {{ $kpi['kpi_title'] }}
                                        </h3>

                                        <p class="text-sm text-slate-500 mt-2">
                                            {{ $kpi['kpi_description'] ?? '-' }}
                                        </p>

                                        <div class="flex flex-wrap gap-2 mt-4">

                                            <div class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                                                KPI Score:
                                                {{ number_format($score,2) }}%
                                            </div>

                                            <div class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-black weighted-score">
                                                Weighted:
                                                {{ number_format($weightedScore,2) }}%
                                            </div>

                                        </div>

                                    </div>

                                    <!-- RIGHT -->

                                    <div class="xl:col-span-5">

                                        <div class="grid grid-cols-2 gap-4">

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
                                                    data-score="{{ $score }}"
                                                    class="weightage-input w-full mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-2xl font-black text-slate-900"
                                                    data-category="{{ Str::slug($category) }}"
                                                    oninput="recalculateWeightage()"
                                                    onkeydown="return event.key !== '-'">

                                            </div>

                                            <div class="flex items-end">

                                                <div class="w-full bg-slate-100 rounded-2xl p-4">

                                                    <p class="text-[10px] uppercase font-black text-slate-400">
                                                        Impact
                                                    </p>

                                                    <h3 class="impact-score text-2xl font-black text-indigo-700 mt-2">
                                                        {{ number_format($weightedScore,2) }}%
                                                    </h3>

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

        <!-- FLOATING SAVE -->

        <div class="fixed bottom-6 right-6 z-50">

            <div class="glass rounded-3xl border border-white/70 shadow-2xl p-5 w-[320px]">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-[10px] uppercase font-black text-slate-400">
                            Total Weightage
                        </p>

                        <h2
                            id="floatingTotalWeightage"
                            class="text-3xl font-black text-slate-900 mt-1">

                            {{ number_format($weightageTotal,2) }}%

                        </h2>

                    </div>

                    <div
                        id="floatingStatus"
                        class="px-3 py-2 rounded-2xl bg-amber-100 text-amber-700 text-xs font-black">

                        Incomplete

                    </div>

                </div>

                <div class="mt-4 h-3 rounded-full bg-slate-100 overflow-hidden">

                    <div
                        id="floatingProgressBar"
                        class="h-3 rounded-full bg-gradient-to-r from-indigo-500 to-blue-600"
                        style="width: {{ min(100,$weightageTotal) }}%">
                    </div>

                </div>

                <button
                    id="saveWeightageBtn"
                    type="submit"
                    disabled
                    class="mt-5 w-full rounded-2xl bg-slate-300 text-white py-4 font-black transition">

                    Save Weightage

                </button>

            </div>

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

function recalculateWeightage(){

    const inputs =
        Array.from(
            document.querySelectorAll('.weightage-input')
        );

    let total = 0;

    inputs.forEach(input => {

        const value =
            parseFloat(input.value || 0);

        total += value;

        const score =
            parseFloat(input.dataset.score || 0);

        const weighted =
            (score * value) / 100;

        const container =
            input.closest('.grid');

        if(container){

            const impact =
                container.querySelector('.impact-score');

            if(impact){

                impact.innerText =
                    weighted.toFixed(2) + '%';
            }
        }

    });

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

        const total =
            categoryTotals[category] || 0;

        el.innerText =
            total.toFixed(2) + '%';

    });

    total =
        Math.round(total * 100) / 100;

    const totalText =
        document.getElementById(
            'floatingTotalWeightage'
        );

    const totalText =
        'text-red-700',
        'text-amber-700',
        'text-emerald-700'
    );

    totalText.classList.remove(

    'text-red-700',

    'text-amber-700',

    'text-emerald-700'

);

if(total === 100){

    totalText.classList.add(
        'text-emerald-700'
    );

}else if(total > 100){

    totalText.classList.add(
        'text-red-700'
    );

}else{

    totalText.classList.add(
        'text-amber-700'
    );
}

    if(total === 100){

        totalText.classList.add(
            'text-emerald-700'
        );

    }else if(total > 100){

        totalText.classList.add(
            'text-red-700'
        );

    }else{

        totalText.classList.add(
            'text-amber-700'
        );
    }

    const progress =
        document.getElementById(
            'floatingProgressBar'
        );

    const status =
        document.getElementById(
            'floatingStatus'
        );

    const saveBtn =
        document.getElementById(
            'saveWeightageBtn'
        );

    totalText.innerText =
        total.toFixed(2) + '%';

    progress.style.width =
        Math.min(100,total) + '%';

    if(total === 100){

        status.innerText = 'Ready';

        status.className =
            'px-3 py-2 rounded-2xl bg-green-100 text-green-700 text-xs font-black';

        saveBtn.disabled = false;

        saveBtn.className =
            'mt-5 w-full rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white py-4 font-black transition';

    }else if(total > 100){

        status.innerText = 'Exceeded';

        status.className =
            'px-3 py-2 rounded-2xl bg-red-100 text-red-700 text-xs font-black';

        saveBtn.disabled = true;

        saveBtn.className =
            'mt-5 w-full rounded-2xl bg-slate-300 text-white py-4 font-black transition';

    }else{

        status.innerText = 'Incomplete';

        status.className =
            'px-3 py-2 rounded-2xl bg-amber-100 text-amber-700 text-xs font-black';

        saveBtn.disabled = true;

        saveBtn.className =
            'mt-5 w-full rounded-2xl bg-slate-300 text-white py-4 font-black transition';
    }
}

recalculateWeightage();

document
    .getElementById('weightageForm')
    .addEventListener('submit', function(){

    const btn =
        document.getElementById(
            'saveWeightageBtn'
        );

    btn.disabled = true;

    btn.innerText = 'Saving...';

    btn.classList.remove(
        'bg-indigo-600',
        'hover:bg-indigo-700'
    );

    btn.classList.add(
        'bg-slate-400'
    );

});

</script>

</body>
</html>
