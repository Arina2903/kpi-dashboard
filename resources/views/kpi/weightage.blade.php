<!DOCTYPE html>
<html>
<head>
    <title>Manage Weightage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass { background: rgba(255,255,255,.9); backdrop-filter: blur(14px); }
        .card-hover { transition: .2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 18px 35px rgba(15,23,42,.08); }
        .weightage-input::-webkit-outer-spin-button,
        .weightage-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .weightage-input { -moz-appearance: textfield; }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">
<div class="p-6 space-y-6">

    <!-- HEADER -->
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 text-white p-6 shadow-xl">
        <a href="/kpi" class="text-sm text-blue-100 hover:text-white">← Back to KPI List</a>
        <h1 class="text-3xl font-bold mt-3">Manage Weightage</h1>
        <p class="text-blue-200 text-sm mt-1">{{ $fy ?? '' }} · {{ session('short_name') }}</p>
    </div>

    <!-- APPROVAL FLOW GUIDE -->
    @php
        $approverName = $approver['short_name'] ?? null;
        $approverRole = $approver['role'] ?? null;
    @endphp

    <div class="glass rounded-2xl border border-indigo-100 p-5 shadow-sm">
        <div class="flex flex-col xl:flex-row xl:items-start gap-5">

            <!-- FLOW STEPS -->
            <div class="flex-1">
                <p class="text-xs font-black uppercase text-indigo-500 mb-3">How Weightage Works</p>
                <div class="flex flex-wrap items-center gap-2">

                    <div class="flex items-center gap-2 bg-slate-100 rounded-xl px-3 py-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[10px] font-black flex items-center justify-center shrink-0">1</span>
                        <div>
                            <p class="text-xs font-black text-slate-800">Set Weightage</p>
                            <p class="text-[10px] text-slate-500">Assign % to each KPI</p>
                        </div>
                    </div>

                    <span class="text-slate-400 font-black">→</span>

                    <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                        <span class="w-6 h-6 rounded-full bg-amber-500 text-white text-[10px] font-black flex items-center justify-center shrink-0">2</span>
                        <div>
                            <p class="text-xs font-black text-slate-800">Submit for Approval</p>
                            <p class="text-[10px] text-slate-500">If changing existing %</p>
                        </div>
                    </div>

                    <span class="text-slate-400 font-black">→</span>

                    <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-[10px] font-black flex items-center justify-center shrink-0">3</span>
                        <div>
                            <p class="text-xs font-black text-slate-800">Boss Reviews</p>
                            <p class="text-[10px] text-slate-500">Approves or Rejects</p>
                        </div>
                    </div>

                    <span class="text-slate-400 font-black">→</span>

                    <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px] font-black flex items-center justify-center shrink-0">4</span>
                        <div>
                            <p class="text-xs font-black text-slate-800">Applied</p>
                            <p class="text-[10px] text-slate-500">Weightage updated</p>
                        </div>
                    </div>

                </div>

                <div class="mt-3 text-[11px] text-slate-600 space-y-1">
                    <p><span class="font-black text-emerald-700">New allocation</span> (0% → any%) — saves directly, no approval needed.</p>
                    <p><span class="font-black text-amber-700">Changing existing</span> (e.g. 30% → 25%) — requires boss approval with a reason.</p>
                </div>
            </div>

            <!-- APPROVER INFO -->
            <div class="xl:w-[220px] shrink-0">
                <div class="rounded-xl border {{ $approverName ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50' }} p-4">
                    <p class="text-[10px] uppercase font-black {{ $approverName ? 'text-blue-500' : 'text-slate-400' }}">
                        Your Approver
                    </p>
                    @if($approverName)
                        <p class="text-base font-black text-slate-900 mt-1">{{ $approverName }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $approverRole }}</p>
                        <p class="text-[10px] text-blue-600 mt-2">Approval requests go to this person</p>
                    @else
                        <p class="text-sm font-black text-slate-500 mt-1">No approver set</p>
                        <p class="text-[10px] text-slate-400 mt-1">Contact your administrator</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @php
        $currentEmployeeId = (string)($user['id'] ?? '');

        $myKpis = collect($kpis ?? [])->filter(function($item) use ($currentEmployeeId) {
            return (string)($item['employee_id'] ?? '') === $currentEmployeeId;
        });

        $calculateScore = function($item) {
            $quarters    = collect($item['quarters'] ?? []);
            $baseTotal   = 0;
            $actualTotal = 0;
            foreach (['Q1','Q2','Q3','Q4'] as $q) {
                $row          = $quarters->firstWhere('quarter', $q) ?? [];
                $baseTotal   += (float)($row['quarter_target'] ?? 0);
                $actualTotal += (float)($row['quarter_actual'] ?? 0);
            }
            if($baseTotal <= 0){
                $baseTotal   = (float)($item['base_target'] ?? 0);
                $actualTotal = (float)($item['actual_value'] ?? 0);
            }
            return $baseTotal > 0 ? round(($actualTotal / $baseTotal) * 100, 2) : 0;
        };

        $weightageTotal = round($myKpis->sum(fn($i) => (float)($i['weightage'] ?? 0)), 2);
        $remaining      = round(100 - $weightageTotal, 2);

        $individualPerformance = round($myKpis->sum(function($item) use ($calculateScore){
            return ($calculateScore($item) * (float)($item['weightage'] ?? 0)) / 100;
        }), 2);
    @endphp

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">
            <p class="text-xs uppercase font-black text-slate-400">My KPI</p>
            <h2 class="text-2xl font-black text-slate-900 mt-2">{{ $myKpis->count() }}</h2>
        </div>

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">
            <p class="text-xs uppercase font-black text-slate-400">Total Weightage</p>
            <h2 id="weightageTotalText"
                class="text-2xl font-black mt-2
                {{ $weightageTotal > 100 ? 'text-red-700' : ($weightageTotal == 100 ? 'text-emerald-700' : 'text-amber-700') }}">
                {{ number_format($weightageTotal, 2) }}%
            </h2>
            <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                <div id="weightageBar"
                     class="h-full rounded-full transition-all {{ $weightageTotal > 100 ? 'bg-red-500' : ($weightageTotal == 100 ? 'bg-emerald-500' : 'bg-amber-400') }}"
                     style="width: {{ min(100, $weightageTotal) }}%"></div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">
            <p class="text-xs uppercase font-black text-slate-400">Remaining</p>
            <h2 id="weightageRemainingText"
                class="text-2xl font-black mt-2
                {{ $remaining < 0 ? 'text-red-700' : ($remaining == 0 ? 'text-emerald-700' : 'text-amber-700') }}">
                {{ number_format($remaining, 2) }}%
            </h2>
        </div>

        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm">
            <p class="text-xs uppercase font-black text-slate-400">Individual Performance</p>
            <h2 class="text-2xl font-black text-blue-700 mt-2">{{ number_format($individualPerformance, 2) }}%</h2>
        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <div class="glass rounded-2xl p-4 border border-white/70 flex flex-wrap gap-3 items-center">

        <button type="button" onclick="balanceEmptyWeightage()"
            class="bg-amber-100 hover:bg-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-black">
            Balance Empty
        </button>

        <button type="button" onclick="confirmEqualizeWeightage()"
            class="bg-slate-800 hover:bg-slate-700 text-white px-5 py-3 rounded-xl text-sm font-black">
            Equalize All
        </button>

        <div class="ml-auto text-[11px] text-slate-500 text-right">
            <span id="totalStatus" class="font-black">
                @if($weightageTotal == 100) Total = 100% ✓
                @elseif($weightageTotal > 100) Over by {{ number_format($weightageTotal - 100, 2) }}%
                @else {{ number_format(100 - $weightageTotal, 2) }}% left to allocate
                @endif
            </span>
        </div>

    </div>

    <!-- KPI LIST -->
    <form id="weightageForm" method="POST" action="{{ route('weightage.bulk-update') }}">
        @csrf
        <div class="space-y-5">

            @php
                $categoryOrder  = ['Financial','Growth & Customer','Initiatives','People'];
                $categoryStyles = [
                    'Financial'         => ['bg' => 'bg-emerald-700 text-white', 'sub' => 'bg-emerald-50 text-emerald-800 border border-emerald-200'],
                    'Growth & Customer' => ['bg' => 'bg-indigo-700 text-white',  'sub' => 'bg-indigo-50 text-indigo-800 border border-indigo-200'],
                    'Initiatives'       => ['bg' => 'bg-amber-600 text-white',   'sub' => 'bg-amber-50 text-amber-800 border border-amber-200'],
                    'People'            => ['bg' => 'bg-pink-700 text-white',    'sub' => 'bg-pink-50 text-pink-800 border border-pink-200'],
                    'Default'           => ['bg' => 'bg-slate-700 text-white',   'sub' => 'bg-slate-50 text-slate-800 border border-slate-200'],
                ];
                $groupedKpis = collect();
                foreach($categoryOrder as $cat){
                    $items = $myKpis->where('category', $cat);
                    if($items->count()) $groupedKpis[$cat] = $items;
                }
                $pendingMap = $pendingWeightageRequests ?? [];
            @endphp

            @foreach($groupedKpis as $category => $categoryKpis)

                @php
                    $categoryWeightage = round($categoryKpis->sum(fn($i) => (float)($i['weightage'] ?? 0)), 2);
                    $style = $categoryStyles[$category] ?? $categoryStyles['Default'];
                @endphp

                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">

                    <!-- CATEGORY HEADER -->
                    <div class="px-4 py-3 {{ $style['bg'] }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-black tracking-wide uppercase">{{ $category ?: 'General' }}</h2>
                                <p class="text-xs text-white/70 mt-0.5">{{ count($categoryKpis) }} KPI</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-white/70 font-black">Category Weightage</p>
                                <h3 class="text-lg font-black text-white mt-0.5 category-total"
                                    data-category="{{ Str::slug($category) }}">
                                    {{ number_format($categoryWeightage, 2) }}%
                                </h3>
                            </div>
                        </div>
                    </div>

                    <!-- KPI ROWS -->
                    <div class="divide-y divide-slate-100">

                        @foreach($categoryKpis as $kpi)

                            @php
                                $kpiId      = $kpi['id'];
                                $isPending  = isset($pendingMap[$kpiId]);
                                $pendingReq = $pendingMap[$kpiId] ?? null;
                                $currentWt  = (float)($kpi['weightage'] ?? 0);
                                $isNew      = $currentWt <= 0;
                            @endphp

                            <div class="p-4 {{ $isPending ? 'bg-amber-50' : '' }}">
                                <div class="flex flex-col xl:flex-row xl:items-start gap-4">

                                    <!-- LEFT: KPI INFO -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase {{ $style['sub'] }}">
                                                {{ $kpi['sub_category'] ?? '-' }}
                                            </span>
                                            @if($isNew)
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-700">
                                                    New Allocation
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-600">
                                                    Current: {{ number_format($currentWt, 2) }}%
                                                </span>
                                            @endif
                                            @if($isPending)
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-200 text-amber-800">
                                                    ⏳ Pending Approval
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="text-sm font-bold text-slate-900 leading-snug">{{ $kpi['kpi_title'] }}</h3>
                                        <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ $kpi['kpi_description'] ?? '' }}</p>

                                        <!-- PENDING REQUEST DETAIL -->
                                        @if($isPending && $pendingReq)
                                            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px]">
                                                <p class="font-black text-amber-700 mb-1">Pending Approval Request</p>
                                                <div class="flex gap-4 text-slate-600">
                                                    <span>From: <b>{{ number_format((float)($pendingReq['old_weightage'] ?? 0), 2) }}%</b></span>
                                                    <span>→</span>
                                                    <span>To: <b class="text-amber-700">{{ number_format((float)($pendingReq['new_weightage'] ?? 0), 2) }}%</b></span>
                                                </div>
                                                <p class="text-slate-500 mt-1">
                                                    Submitted {{ \Carbon\Carbon::parse($pendingReq['created_at'])->diffForHumans() }}
                                                    @if($approverName) · Waiting for <b>{{ $approverName }}</b> @endif
                                                </p>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- RIGHT: INPUT & SAVE -->
                                    <div class="w-full xl:w-[200px] shrink-0">
                                        <div data-kpi-wrapper>

                                            <label class="text-[10px] uppercase font-black text-slate-400">Weightage %</label>

                                            <input
                                                type="number"
                                                name="weightages[{{ $kpiId }}]"
                                                min="0" max="100" step="0.01"
                                                value="{{ number_format($currentWt, 2, '.', '') }}"
                                                data-original="{{ number_format($currentWt, 2, '.', '') }}"
                                                data-kpi-id="{{ $kpiId }}"
                                                data-category="{{ Str::slug($category) }}"
                                                class="weightage-input w-full mt-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-black text-slate-900 transition focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400
                                                    {{ $isPending ? 'opacity-60 cursor-not-allowed' : '' }}"
                                                oninput="recalculateWeightage(this)"
                                                onkeydown="return event.key !== '-'"
                                                {{ $isPending ? 'disabled' : '' }}>

                                            <div class="mt-2 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="save-status text-[11px] font-bold text-slate-500 truncate">
                                                            {{ $isPending ? 'Pending Approval' : 'Synced' }}
                                                        </div>
                                                        <p class="text-[10px] text-slate-400">
                                                            {{ $isPending ? 'Input locked' : ($isNew ? 'Saves directly' : 'Needs approval') }}
                                                        </p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="save-kpi-btn rounded-lg px-2.5 py-1 text-[11px] font-black transition
                                                            {{ $isPending ? 'bg-amber-200 text-amber-700 cursor-not-allowed' : 'bg-slate-300 text-white' }}"
                                                        {{ $isPending ? 'disabled' : 'disabled' }}
                                                        onclick="saveSingleWeightage(this, '{{ $kpiId }}')">
                                                        {{ $isPending ? 'Waiting' : 'Save' }}
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

<!-- APPROVAL REQUEST MODAL -->
<div id="weightageApprovalModal" style="display:none;"
     class="fixed inset-0 z-[99999] bg-black/60 items-center justify-center p-6">
    <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl">

        <!-- MODAL HEADER -->
        <div class="bg-gradient-to-r from-amber-600 to-orange-600 rounded-t-3xl px-6 py-5 text-white">
            <h2 class="text-xl font-black">Weightage Change Request</h2>
            <p class="text-amber-100 text-sm mt-1">This change requires approval from your boss</p>
        </div>

        <div class="p-6 space-y-5">

            <!-- APPROVER BANNER -->
            @if($approverName)
            <div class="rounded-xl bg-blue-50 border border-blue-200 px-4 py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-600 text-white text-xs font-black flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($approverName, 0, 1)) }}
                </div>
                <div>
                    <p class="text-xs font-black text-blue-700">Request will be sent to</p>
                    <p class="text-sm font-black text-slate-900">{{ $approverName }} <span class="text-slate-500 font-normal">({{ $approverRole }})</span></p>
                </div>
            </div>
            @endif

            <!-- CHANGE SUMMARY -->
            <div id="weightageChangeSummary" class="rounded-xl bg-slate-50 border border-slate-200 p-4 grid grid-cols-2 gap-4"></div>

            <!-- REASON -->
            <div>
                <label class="text-xs font-black uppercase text-slate-600">Reason for Change</label>
                <p class="text-[10px] text-slate-400 mt-0.5 mb-2">Minimum 20 characters. Be specific — your boss needs context to approve.</p>
                <textarea id="weightageReason" rows="4"
                    class="w-full mt-1 border border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-amber-200 focus:border-amber-400"
                    placeholder="e.g. Shifting focus to Financial KPIs this quarter due to board priorities..."></textarea>
                <p id="reasonCharCount" class="text-[10px] text-slate-400 mt-1 text-right">0 / min 20 chars</p>
            </div>

            <!-- BUTTONS -->
            <div class="flex justify-end gap-3 pt-2">
                <button onclick="closeWeightageApprovalModal()"
                    class="px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-black text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button id="submitApprovalBtn" onclick="submitWeightageApproval()"
                    class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-black">
                    Submit Request
                </button>
            </div>

        </div>
    </div>
</div>

<script>

/* ------------------------------------------------------------------ */
/*  RECALCULATE                                                         */
/* ------------------------------------------------------------------ */

function recalculateWeightage(changedInput){
    const inputs = Array.from(document.querySelectorAll('.weightage-input:not([disabled])'));
    let total = 0;
    inputs.forEach(i => total += parseFloat(i.value || 0));
    total = Math.round(total * 100) / 100;

    const remaining = Math.max(0, 100 - total);
    const totalEl   = document.getElementById('weightageTotalText');
    const remainEl  = document.getElementById('weightageRemainingText');
    const barEl     = document.getElementById('weightageBar');
    const statusEl  = document.getElementById('totalStatus');

    totalEl.innerText  = total.toFixed(2) + '%';
    remainEl.innerText = remaining.toFixed(2) + '%';

    if(total > 100){
        totalEl.className  = 'text-2xl font-black mt-2 text-red-700';
        remainEl.className = 'text-2xl font-black mt-2 text-red-700';
        barEl.className    = 'h-full rounded-full transition-all bg-red-500';
        barEl.style.width  = '100%';
        statusEl.innerText = 'Over by ' + (total - 100).toFixed(2) + '%';
    } else if(total === 100){
        totalEl.className  = 'text-2xl font-black mt-2 text-emerald-700';
        remainEl.className = 'text-2xl font-black mt-2 text-emerald-700';
        barEl.className    = 'h-full rounded-full transition-all bg-emerald-500';
        barEl.style.width  = '100%';
        statusEl.innerText = 'Total = 100% ✓';
    } else {
        totalEl.className  = 'text-2xl font-black mt-2 text-amber-700';
        remainEl.className = 'text-2xl font-black mt-2 text-amber-700';
        barEl.className    = 'h-full rounded-full transition-all bg-amber-400';
        barEl.style.width  = total + '%';
        statusEl.innerText = remaining.toFixed(2) + '% left to allocate';
    }

    // category totals
    const catTotals = {};
    inputs.forEach(i => {
        const c = i.dataset.category;
        catTotals[c] = (catTotals[c] || 0) + parseFloat(i.value || 0);
    });
    document.querySelectorAll('.category-total').forEach(el => {
        el.innerText = (catTotals[el.dataset.category] || 0).toFixed(2) + '%';
    });

    // per-KPI save button state
    inputs.forEach(function(input){
        const wrapper    = input.closest('[data-kpi-wrapper]');
        const saveBtn    = wrapper.querySelector('.save-kpi-btn');
        const saveStatus = wrapper.querySelector('.save-status');
        const original   = parseFloat(input.dataset.original || 0);
        const value      = parseFloat(input.value || 0);
        const changed    = value !== original;

        if(total > 100){
            saveBtn.disabled   = true;
            saveBtn.className  = 'save-kpi-btn rounded-lg bg-red-200 text-red-700 px-2.5 py-1 text-[11px] font-black transition';
            saveBtn.innerText  = 'Save';
            saveStatus.innerText = 'Over 100%';
            saveStatus.className = 'save-status text-[11px] font-bold text-red-600 truncate';
            return;
        }

        if(changed){
            const isChange    = original > 0;
            saveBtn.disabled  = false;
            saveBtn.className = isChange
                ? 'save-kpi-btn rounded-lg bg-amber-500 hover:bg-amber-600 text-white px-2.5 py-1 text-[11px] font-black transition'
                : 'save-kpi-btn rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1 text-[11px] font-black transition';
            saveBtn.innerText    = isChange ? 'Request' : 'Save';
            saveStatus.innerText = isChange ? 'Needs Approval' : 'Ready to Save';
            saveStatus.className = isChange
                ? 'save-status text-[11px] font-bold text-amber-600 truncate'
                : 'save-status text-[11px] font-bold text-indigo-600 truncate';
        } else {
            saveBtn.disabled     = true;
            saveBtn.className    = 'save-kpi-btn rounded-lg bg-slate-300 text-white px-2.5 py-1 text-[11px] font-black transition';
            saveBtn.innerText    = 'Save';
            saveStatus.innerText = 'Synced';
            saveStatus.className = 'save-status text-[11px] font-bold text-slate-500 truncate';
        }
    });
}

/* ------------------------------------------------------------------ */
/*  SAVE SINGLE                                                         */
/* ------------------------------------------------------------------ */

function saveSingleWeightage(button, kpiId){
    const input    = document.querySelector('[data-kpi-id="' + kpiId + '"]');
    if(!input) return;
    const value    = parseFloat(input.value || 0);
    const original = parseFloat(input.dataset.original || 0);
    if(value === original) return;

    if(original > 0){
        openWeightageApprovalModal(kpiId, original, value);
        return;
    }

    // new allocation — direct save
    button.disabled  = true;
    button.innerText = 'Saving...';

    fetch('{{ route("weightage.bulk-update") }}', {
        method : 'POST',
        headers: {
            'Content-Type' : 'application/json',
            'X-CSRF-TOKEN' : '{{ csrf_token() }}',
            'Accept'       : 'application/json',
        },
        body: JSON.stringify({ weightages: { [kpiId]: value } })
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.success){
            input.dataset.original = value.toFixed(2);
            button.innerText       = 'Saved ✓';
            button.className       = 'save-kpi-btn rounded-lg bg-emerald-600 text-white px-2.5 py-1 text-[11px] font-black transition';
            setTimeout(function(){ recalculateWeightage(); }, 1200);
        } else {
            button.innerText = 'Failed';
            button.className = 'save-kpi-btn rounded-lg bg-red-500 text-white px-2.5 py-1 text-[11px] font-black transition';
            button.disabled  = false;
            alert(data.message || 'Save failed.');
        }
    })
    .catch(function(){
        button.innerText = 'Error';
        button.className = 'save-kpi-btn rounded-lg bg-red-500 text-white px-2.5 py-1 text-[11px] font-black transition';
        button.disabled  = false;
    });
}

/* ------------------------------------------------------------------ */
/*  EQUALIZE / BALANCE                                                  */
/* ------------------------------------------------------------------ */

function equalizeAllWeightage(){
    const inputs   = Array.from(document.querySelectorAll('.weightage-input:not([disabled])'));
    if(!inputs.length) return;
    const equal    = Math.floor((100 / inputs.length) * 100) / 100;
    let assigned   = 0;
    inputs.forEach(function(inp, i){
        if(i === inputs.length - 1){
            inp.value = (100 - assigned).toFixed(2);
        } else {
            inp.value = equal.toFixed(2);
            assigned += equal;
        }
    });
    recalculateWeightage();
}

function confirmEqualizeWeightage(){
    if(confirm('Equalize all KPI weightage? This will overwrite current allocation.')) equalizeAllWeightage();
}

function balanceEmptyWeightage(){
    const inputs  = Array.from(document.querySelectorAll('.weightage-input:not([disabled])'));
    const empties = inputs.filter(function(i){ return parseFloat(i.value || 0) <= 0; });
    if(!empties.length) return;
    const used    = inputs.reduce(function(s, i){ return empties.includes(i) ? s : s + parseFloat(i.value || 0); }, 0);
    const balance = Math.max(0, 100 - used);
    const share   = Math.floor((balance / empties.length) * 100) / 100;
    empties.forEach(function(inp, i){
        inp.value = i === empties.length - 1
            ? (balance - share * (empties.length - 1)).toFixed(2)
            : share.toFixed(2);
    });
    recalculateWeightage();
}

/* ------------------------------------------------------------------ */
/*  APPROVAL MODAL                                                      */
/* ------------------------------------------------------------------ */

var weightageApprovalData = null;

function openWeightageApprovalModal(kpiId, oldValue, newValue){
    weightageApprovalData = { kpiId: kpiId, oldValue: oldValue, newValue: newValue };

    var diff = newValue - oldValue;
    document.getElementById('weightageChangeSummary').innerHTML =
        '<div class="text-center">' +
            '<p class="text-[10px] uppercase font-black text-slate-400">Current Weightage</p>' +
            '<p class="text-2xl font-black text-slate-700 mt-1">' + oldValue.toFixed(2) + '%</p>' +
        '</div>' +
        '<div class="text-center">' +
            '<p class="text-[10px] uppercase font-black text-amber-500">Requested Weightage</p>' +
            '<p class="text-2xl font-black text-amber-700 mt-1">' + newValue.toFixed(2) + '%</p>' +
            '<p class="text-[10px] text-slate-500 mt-0.5">' + (diff >= 0 ? '+' : '') + diff.toFixed(2) + '%</p>' +
        '</div>';

    document.getElementById('weightageReason').value = '';
    document.getElementById('reasonCharCount').innerText = '0 / min 20 chars';
    document.getElementById('weightageApprovalModal').style.display = 'flex';
}

function closeWeightageApprovalModal(){
    document.getElementById('weightageApprovalModal').style.display = 'none';
}

document.getElementById('weightageReason').addEventListener('input', function(){
    document.getElementById('reasonCharCount').innerText = this.value.length + ' / min 20 chars';
});

function submitWeightageApproval(){
    var reason = document.getElementById('weightageReason').value;
    if(reason.trim().length < 20){
        alert('Please provide a reason with at least 20 characters.');
        return;
    }

    var btn = document.getElementById('submitApprovalBtn');
    btn.disabled  = true;
    btn.innerText = 'Submitting...';

    fetch('/kpi/' + weightageApprovalData.kpiId + '/request-weightage-change', {
        method : 'POST',
        headers: {
            'Content-Type' : 'application/json',
            'X-CSRF-TOKEN' : '{{ csrf_token() }}',
            'Accept'       : 'application/json',
        },
        body: JSON.stringify({
            old_weightage : weightageApprovalData.oldValue,
            new_weightage : weightageApprovalData.newValue,
            reason        : reason,
        })
    })
    .then(function(r){ return r.json(); })
    .then(function(result){
        if(result.success){
            closeWeightageApprovalModal();
            alert('Request submitted. ' + result.message);
            location.reload();
        } else {
            alert('Error: ' + result.message);
            btn.disabled  = false;
            btn.innerText = 'Submit Request';
        }
    })
    .catch(function(){
        alert('Network error. Please try again.');
        btn.disabled  = false;
        btn.innerText = 'Submit Request';
    });
}

/* ------------------------------------------------------------------ */
/*  INIT                                                                */
/* ------------------------------------------------------------------ */

recalculateWeightage();

</script>

</body>
</html>
