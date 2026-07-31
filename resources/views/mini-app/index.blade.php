<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', sans-serif; }
        .soft-card {
            box-shadow: 0 14px 26px -10px rgba(107,63,42,.28), 0 4px 10px rgba(107,63,42,.14), inset 0 1px 0 rgba(255,255,255,.7);
        }
        .soft-card-sm { box-shadow: 0 6px 14px -6px rgba(107,63,42,.22), inset 0 1px 0 rgba(255,255,255,.6); }
        .tap-card { transition: border-color .15s, background .15s; }
        .nav-btn { transition: all .15s; }
        .nav-btn.active { background: #fff; color: #6B3F2A; }
        .nav-btn:not(.active) { background: rgba(255,255,255,.14); color: rgba(255,255,255,.85); }
    </style>
</head>
<body class="bg-[#F5F5F3] min-h-screen">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen flex justify-center py-4 px-4">

@if(!$telegramLinked)

{{-- CONNECT GATE — Mini App reminders/adjustments only make sense once we    --}}
{{-- can reach the employee on Telegram, so the app itself is withheld until --}}
{{-- they link it (same connect/status endpoints as Account Settings).       --}}
<div class="w-full max-w-md bg-[#F5EEDC] rounded-[26px] overflow-hidden shadow-2xl flex flex-col" style="min-height: calc(100vh - 32px);">
    <div id="topbar" class="bg-[#6B3F2A] text-white px-4 py-3.5 shrink-0">
        <h1 class="text-[15px] font-black">Mini App</h1>
    </div>
    <div class="flex-1 p-6 flex flex-col items-center justify-center text-center gap-4">
        <div class="w-14 h-14 rounded-full bg-[#229ED9]/10 flex items-center justify-center">
            <svg viewBox="0 0 24 24" class="w-7 h-7" fill="#229ED9"><path d="M21.94 4.53a1.6 1.6 0 0 0-1.63-.27L2.98 10.98a1.53 1.53 0 0 0 .1 2.88l4.54 1.42 1.76 5.5c.14.44.5.72.94.72.03 0 .06 0 .1-.01.34-.03.63-.24.77-.55l2.15-3.9 4.5 3.3c.24.18.53.27.82.27.14 0 .29-.02.43-.07a1.5 1.5 0 0 0 1-1.1l3.03-13.7a1.6 1.6 0 0 0-.62-1.74Zm-3.35 2.68-8.03 7.28-.31 3.35-1.35-4.22 8.6-6.9c.2-.16.42.1.24.28l-6.9 6.24a.5.5 0 0 0-.15.3l-.2 2.13 8.6-9.7c.2-.23.5.03.33.24Z"/></svg>
        </div>
        <div>
            <p class="text-[14px] font-black text-slate-900">Connect Telegram to continue</p>
            <p id="tg-gate-text" class="text-[12px] text-slate-500 mt-1.5 leading-relaxed">The Mini App needs your Telegram account linked so we can send you reminders and updates.</p>
        </div>
        <button id="tg-gate-btn" type="button" onclick="connectTelegramGate()" class="w-full text-[12px] font-black px-4 py-3 rounded-xl bg-[#6B9080] text-white hover:bg-[#5a7a6d] transition">
            Connect Telegram
        </button>
    </div>
</div>

<script>
    const TG_CSRF = '{{ csrf_token() }}';
    let tgGatePollTimer = null;

    async function refreshTelegramGateStatus() {
        const res = await fetch('{{ route("settings.telegram.status") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.linked) {
            if (tgGatePollTimer) { clearInterval(tgGatePollTimer); tgGatePollTimer = null; }
            document.getElementById('tg-gate-text').textContent = 'Connected! Loading your Mini App…';
            window.location.reload();
        }
    }

    async function connectTelegramGate() {
        const res = await fetch('{{ route("settings.telegram.connect") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': TG_CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        window.open(data.deep_link, '_blank');
        document.getElementById('tg-gate-text').textContent = 'Waiting for confirmation in Telegram…';
        document.getElementById('tg-gate-btn').textContent = 'Reconnect';

        let attempts = 0;
        if (tgGatePollTimer) clearInterval(tgGatePollTimer);
        tgGatePollTimer = setInterval(async () => {
            attempts++;
            await refreshTelegramGateStatus();
            if (attempts >= 40) { clearInterval(tgGatePollTimer); tgGatePollTimer = null; } // ~2 min at 3s
        }, 3000);
    }
</script>

@else

<div class="w-full max-w-md bg-[#F5EEDC] rounded-[26px] overflow-hidden shadow-2xl flex flex-col" style="min-height: calc(100vh - 32px);">

    <div id="topbar" class="bg-[#6B3F2A] text-white px-4 py-3.5 shrink-0">
        <h1 class="text-[15px] font-black mb-2.5">Mini App</h1>
        <div class="flex items-center gap-1.5">
            <button id="tab-kpis" onclick="switchTab('kpis')" class="nav-btn active flex-1 py-2 rounded-xl text-[11px] font-black relative">
                My KPIs
                <span id="kpi-alert-badge" class="hidden absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] rounded-full bg-red-500 text-white text-[9px] font-black flex items-center justify-center px-1 shadow-lg shadow-red-500/30"></span>
            </button>
            <button id="tab-todo" onclick="switchTab('todo')" class="nav-btn flex-1 py-2 rounded-xl text-[11px] font-black">To-Do</button>
            <button id="tab-score" onclick="switchTab('score')" class="nav-btn flex-1 py-2 rounded-xl text-[11px] font-black">Score</button>
        </div>
    </div>

    <div id="toast" class="hidden mx-4 mt-3 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-semibold"></div>

    <div id="app" class="flex-1 p-4 space-y-3">
        <p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>
    </div>
</div>

@endif

</main>

<script>
const _csrfToken = '{{ csrf_token() }}';

async function api(path, opts = {}) {
    const res = await fetch('/mini-app/api' + path, {
        ...opts,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrfToken, ...(opts.headers || {}) },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(data.message || 'Request failed');
        err.status = res.status; err.data = data;
        throw err;
    }
    return data;
}

function showToast(message) {
    const t = document.getElementById('toast');
    t.textContent = message;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
}

function formatUnit(value, unit) {
    const n = Number(value || 0);
    if (unit === 'currency') return 'RM ' + n.toLocaleString(undefined, { maximumFractionDigits: 0 });
    if (unit === 'percentage') return n.toLocaleString(undefined, { maximumFractionDigits: 2 }) + '%';
    return n.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

// Same category order/colors, status labels, and achievement bands used on
// the web dashboard (resources/views/dashboard.blade.php,
// kpi/my-department-kpi.blade.php) and mirrored 1:1 by the Telegram Mini
// App — kept identical here so a KPI's status/score reads the same
// everywhere in the system, not a mini-app-only interpretation.
const CATEGORY_ORDER = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];
const CATEGORY_COLORS = {
    'Financial':         { catPill: 'bg-emerald-700 text-white', subPill: 'bg-emerald-100 text-emerald-700' },
    'Growth & Customer': { catPill: 'bg-indigo-700 text-white',  subPill: 'bg-indigo-100 text-indigo-700' },
    'Initiatives':       { catPill: 'bg-amber-600 text-white',   subPill: 'bg-amber-100 text-amber-700' },
    'People':            { catPill: 'bg-pink-700 text-white',    subPill: 'bg-pink-100 text-pink-700' },
};
const DEFAULT_CATEGORY_COLOR = { catPill: 'bg-slate-600 text-white', subPill: 'bg-slate-100 text-slate-600' };

function sortByCategoryAndSub(items) {
    return [...items].sort((a, b) => {
        const ai = CATEGORY_ORDER.indexOf(a.category); const bi = CATEGORY_ORDER.indexOf(b.category);
        const catDiff = (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
        if (catDiff !== 0) return catDiff;
        return (a.sub_category || '').localeCompare(b.sub_category || '');
    });
}

const STATUS_LABELS = {
    completed:   { label: 'Completed',   color: 'bg-emerald-100 text-emerald-700' },
    on_track:    { label: 'On Track',    color: 'bg-[#F5EAE0] text-[#6B3F2A]' },
    at_risk:     { label: 'At Risk',     color: 'bg-yellow-100 text-yellow-700' },
    in_trouble:  { label: 'In Trouble',  color: 'bg-red-100 text-red-700' },
    not_started: { label: 'Not Started', color: 'bg-slate-100 text-slate-500' },
};

function achvBadge(score) {
    if (score >= 90) return { label: 'Excellent', color: 'bg-emerald-100 text-emerald-700', bar: 'from-emerald-400 to-green-500', ring: '#10B981' };
    if (score >= 75) return { label: 'Good',      color: 'bg-[#F5EAE0] text-[#6B3F2A]',     bar: 'from-[#8B5E4A] to-[#6B3F2A]', ring: '#6B3F2A' };
    if (score >= 50) return { label: 'Watch',     color: 'bg-yellow-100 text-yellow-700',   bar: 'from-yellow-400 to-amber-500', ring: '#F59E0B' };
    return              { label: 'Critical', color: 'bg-red-100 text-red-700',       bar: 'from-red-400 to-rose-500', ring: '#EF4444' };
}

// A circular "at a glance" ring of a KPI's achievement score — same visual
// language as the Telegram Mini App, instead of just printing a number.
function progressRing(scoreRaw) {
    const badge = achvBadge(scoreRaw);
    const score = Math.max(0, Math.min(100, scoreRaw));
    const r = 24, c = 2 * Math.PI * r;
    const offset = c - (score / 100) * c;
    return `
        <svg width="56" height="56" viewBox="0 0 60 60" class="shrink-0">
            <circle cx="30" cy="30" r="${r}" fill="none" stroke="#EFE3C7" stroke-width="6"/>
            <circle cx="30" cy="30" r="${r}" fill="none" stroke="${badge.ring}" stroke-width="6"
                stroke-linecap="round" stroke-dasharray="${c}" stroke-dashoffset="${offset}"
                transform="rotate(-90 30 30)"/>
            <text x="30" y="35" text-anchor="middle" font-size="12" font-weight="900" fill="#1e293b">${Math.round(scoreRaw)}%</text>
        </svg>
    `;
}

function card(inner, extra = '') {
    return `<div class="bg-[#FFFCF4] rounded-2xl soft-card border-2 border-[#D9C4A0] p-4 ${extra}">${inner}</div>`;
}

// Red counter on the "My KPIs" tab — same visual language as the sidebar's
// notification bell badge — counting distinct KPIs that need attention
// (not logged today, or status at_risk/in_trouble), so it's visible from
// whichever tab the user is currently on.
function updateKpiAlertBadge(count) {
    const badge = document.getElementById('kpi-alert-badge');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count > 9 ? '9+' : count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

let currentTab = 'kpis';
function switchTab(tab) {
    currentTab = tab;
    ['kpis', 'todo', 'score'].forEach(t => document.getElementById('tab-' + t).classList.toggle('active', t === tab));
    if (tab === 'kpis') renderMyKpis();
    if (tab === 'todo') renderTodo();
    if (tab === 'score') renderScore('monthly');
}

/* ---------------------------------------------------------------- */
/* MY KPIS + REMINDER BANNER                                         */
/* Value mapping is pulled straight from MiniAppController — same     */
/* kpis/kpi_quarters rows, same achievement/status fields the rest    */
/* of the system uses, nothing computed independently here.           */
/* ---------------------------------------------------------------- */

async function renderMyKpis() {
    const app = document.getElementById('app');
    app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>`;

    let openData, summaryData;
    try {
        [openData, summaryData] = await Promise.all([api('/kpis/open'), api('/kpis/summary')]);
    } catch (e) {
        app.innerHTML = card(`<p class="text-[13px] text-slate-600 text-center py-6">Could not load your KPIs.</p>`);
        return;
    }

    const notLogged = (openData.kpis || []).filter(k => !k.already_logged_today);
    const warningKpiIds = (summaryData.kpis || [])
        .filter(k => ['at_risk', 'in_trouble'].includes(k.status))
        .map(k => k.kpi_id);
    const alertKpiIds = new Set([...notLogged.map(k => k.kpi_id), ...warningKpiIds]);
    updateKpiAlertBadge(alertKpiIds.size);

    const banner = notLogged.length ? `
        <div class="rounded-2xl bg-red-50 border-2 border-red-300 px-4 py-3">
            <p class="text-[12px] font-black text-red-700">⏰ Reminder — ${notLogged.length} KPI(s) not updated today</p>
            <p class="text-[11px] text-red-600 mt-1">${notLogged.map(k => k.kpi_title).join(', ')}</p>
        </div>
    ` : `
        <div class="rounded-2xl bg-emerald-50 border-2 border-emerald-300 px-4 py-3">
            <p class="text-[12px] font-black text-emerald-700">All caught up — every open KPI updated today.</p>
        </div>
    `;

    if (!summaryData.kpis.length) {
        app.innerHTML = banner + card(`<p class="text-[13px] text-slate-600 text-center py-6 mt-3">No KPIs found for this financial year.</p>`);
        return;
    }

    window.__quarterActuals = {};
    summaryData.kpis.forEach(k => (k.quarters || []).forEach(q => { window.__quarterActuals[q.id] = q.actual; }));

    const sorted = sortByCategoryAndSub(summaryData.kpis);
    let lastCategory = null;
    let html = banner;

    sorted.forEach(k => {
        if (k.category !== lastCategory) {
            const cat = CATEGORY_COLORS[k.category] || DEFAULT_CATEGORY_COLOR;
            html += `
                <div class="flex items-center gap-2 mt-4 mb-1 px-1">
                    <p class="text-[11px] font-black uppercase tracking-wide text-[#6B3F2A]">${k.category || 'Other'}</p>
                </div>
            `;
            lastCategory = k.category;
        }

        const cat = CATEGORY_COLORS[k.category] || DEFAULT_CATEGORY_COLOR;
        const sDef = STATUS_LABELS[k.status] || STATUS_LABELS.not_started;
        const aBadge = achvBadge(k.achievement_percentage);
        const pct = Math.max(0, Math.min(100, k.achievement_percentage));
        const annualTarget = (k.quarters || []).reduce((sum, q) => sum + (Number(q.target) || 0), 0);
        const quarterRows = (k.quarters || []).map(q => quarterRow(k.kpi_id, q, k.unit)).join('');

        html += card(`
            <div class="flex items-start gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5 mb-2">
                        <span class="px-2 py-0.5 rounded-full ${cat.catPill} text-[8px] font-black">${k.category || '-'}</span>
                        ${k.sub_category ? `<span class="px-2 py-0.5 rounded-full ${cat.subPill} text-[8px] font-black">${k.sub_category}</span>` : ''}
                        <span class="px-2 py-0.5 rounded-full ${sDef.color} text-[8px] font-black">${sDef.label}</span>
                    </div>
                    <p class="text-[14px] font-black text-slate-900 leading-snug">${k.kpi_title}</p>
                    <span class="inline-block mt-2 px-2 py-0.5 rounded-full ${aBadge.color} text-[9px] font-black">${aBadge.label}</span>
                </div>
                ${progressRing(k.achievement_percentage)}
            </div>
            <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-3 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r ${aBadge.bar}" style="width:${pct}%"></div>
            </div>
            <div class="flex items-center justify-between mt-1.5">
                <p class="text-[10px] text-slate-500 font-bold">Overall (Full Year)</p>
                <p class="text-[11px] text-slate-700 font-black">${formatUnit(k.actual_value, k.unit)} / ${formatUnit(annualTarget, k.unit)}</p>
            </div>
            <div class="mt-3 pt-3 border-t-2 border-dashed border-[#E3D2B0]">
                <p class="text-[9px] uppercase tracking-wide text-slate-400 font-black mb-2">By Quarter</p>
                <div class="space-y-1.5">${quarterRows || '<p class="text-[10px] text-slate-400">No quarters set up yet.</p>'}</div>
            </div>
        `) + '<div class="h-2"></div>';
    });

    app.innerHTML = html;
}

function quarterLabel(state) {
    if (state === 'current') return { text: '✏️ Update here', cls: 'bg-red-100 text-red-700' };
    if (state === 'ended') return { text: '🔒 Done', cls: 'bg-slate-100 text-slate-500' };
    return { text: '🔒 Upcoming', cls: 'bg-slate-100 text-slate-400' };
}

function quarterRow(kpiId, q, unit) {
    const isCurrent = q.state === 'current';
    const badge = achvBadge(q.achievement_percentage);
    const barPct = Math.max(0, Math.min(100, q.achievement_percentage));
    const label = quarterLabel(q.state);
    const hint = `How much did today add? Use a minus sign to reduce.`;

    const updateControl = isCurrent ? `
        <div class="mt-2.5 flex items-center gap-2">
            <input type="number" step="any" placeholder="e.g. 50 or -10" id="delta-${kpiId}"
                class="flex-1 min-w-0 text-[12px] px-3 py-2 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
            <button onclick="submitDelta('${kpiId}','${q.id}')" class="px-4 py-2 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[11px] font-black shrink-0 shadow-[0_4px_12px_rgba(22,163,74,.4)]">
                Update
            </button>
        </div>
        <p class="text-[9px] text-slate-400 mt-1">${hint}</p>
        <p id="feedback-${kpiId}" class="hidden text-[10px] font-bold mt-1.5"></p>
    ` : '';

    return `
        <div class="rounded-xl px-3 py-2.5 soft-card-sm ${isCurrent ? 'bg-red-50 border-2 border-red-500' : 'bg-[#FBF4E6] border-2 border-[#E3D2B0]'}">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-black ${isCurrent ? 'text-red-700' : 'text-slate-600'}">${q.quarter}</p>
                <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${label.cls}">${label.text}</span>
            </div>
            <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-2 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r ${badge.bar}" style="width:${barPct}%"></div>
            </div>
            <div class="flex items-center justify-between mt-1.5">
                <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(q.target, unit)}</span></p>
                <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(q.actual, unit)}</span></p>
                <p class="text-[10px] font-black ${isCurrent ? 'text-red-700' : 'text-slate-500'}">${q.achievement_percentage}%</p>
            </div>
            ${updateControl}
        </div>
    `;
}

async function submitDelta(kpiId, quarterId) {
    const input = document.getElementById(`delta-${kpiId}`);
    const feedback = document.getElementById(`feedback-${kpiId}`);
    const raw = input.value.trim();

    if (raw === '' || isNaN(Number(raw)) || Number(raw) === 0) {
        showToast('Enter an amount first, e.g. 50 or -10.');
        return;
    }

    const delta = Number(raw);
    const currentActual = window.__quarterActuals?.[quarterId] ?? 0;

    if (delta < 0 && currentActual + delta < 0) {
        feedback.textContent = `Can't reduce — this quarter's actual is only ${currentActual}.`;
        feedback.className = 'text-[10px] font-bold mt-1.5 text-red-600';
        feedback.classList.remove('hidden');
        return;
    }

    feedback.classList.add('hidden');

    try {
        await api(`/kpis/${kpiId}/quarters/${quarterId}/adjust`, { method: 'POST', body: JSON.stringify({ delta }) });
        showToast('Updated! Your KPI actual has been refreshed.');
        renderMyKpis();
    } catch (e) {
        feedback.textContent = e.data?.message || "Couldn't update — please try again.";
        feedback.className = 'text-[10px] font-bold mt-1.5 text-red-600';
        feedback.classList.remove('hidden');
    }
}

/* ---------------------------------------------------------------- */
/* TO-DO LIST — a personal to-do list separate from KPI actuals. A    */
/* task can optionally be tied to a KPI purely for visibility — doing */
/* so never changes that KPI's official actual (only the "My KPIs"    */
/* update box does that). Full CRUD: create, edit, log progress,      */
/* delete — each action notifies you (in-app + Telegram if linked).   */
/* ---------------------------------------------------------------- */

async function renderTodo() {
    const app = document.getElementById('app');
    app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading your to-dos…</p>`;

    let data;
    try {
        data = await api('/tasks');
    } catch (e) {
        app.innerHTML = card(`<p class="text-[13px] text-slate-600 text-center py-6">Could not load your to-dos.</p>`);
        return;
    }

    window.__myTasks = data.tasks || [];

    const header = `
        <button onclick="renderNewTaskForm()" class="w-full py-3 rounded-2xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[12px] font-black shadow-[0_6px_16px_rgba(22,163,74,.35)]">
            ➕ New Task
        </button>
    `;

    if (!window.__myTasks.length) {
        app.innerHTML = header + `<div class="mt-3">${card(`<p class="text-[13px] text-slate-600 text-center py-6">No to-dos yet — tap "New Task" to start your list.</p>`)}</div>`;
        return;
    }

    const rows = window.__myTasks.map(t => taskCard(t)).join('<div class="h-2"></div>');
    app.innerHTML = header + `<div class="mt-3">${rows}</div>`;
}

function taskCard(t) {
    const pct = t.target > 0 ? Math.max(0, Math.min(100, (t.actual / t.target) * 100)) : 0;
    const badge = achvBadge(pct);
    const kpiChips = (t.linked_kpis || []).length
        ? `<div class="flex flex-wrap gap-1.5 mt-2">${t.linked_kpis.map(k => `<span class="px-2 py-0.5 rounded-full bg-[#CCE3DE] text-[#1a3d34] text-[8px] font-black">${k.kpi_title}</span>`).join('')}</div>`
        : '';

    return card(`
        <div class="flex items-center justify-between gap-2">
            <p class="text-[13px] font-black text-slate-900 leading-snug min-w-0">${t.title}</p>
            <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full shrink-0 ${t.status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                ${t.status === 'done' ? 'Done' : 'In Progress'}
            </span>
        </div>
        <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-2 overflow-hidden">
            <div class="h-full rounded-full bg-gradient-to-r ${badge.bar}" style="width:${pct}%"></div>
        </div>
        <div class="flex items-center justify-between mt-1.5">
            <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(t.target, t.unit)}</span></p>
            <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(t.actual, t.unit)}</span></p>
            <p class="text-[10px] font-black text-slate-700">${pct.toFixed(0)}%</p>
        </div>
        ${kpiChips}
        <div class="flex items-center gap-2 mt-3">
            <button onclick="renderTaskProgress('${t.id}')" class="flex-1 py-2 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[11px] font-black">Update</button>
            <button onclick="renderEditTask('${t.id}')" class="flex-1 py-2 rounded-xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[11px] font-black">✏️ Edit</button>
            <button onclick="confirmDeleteTask('${t.id}')" class="px-3 py-2 rounded-xl bg-white border-2 border-red-300 text-red-600 text-[11px] font-black">🗑️</button>
        </div>
    `);
}

function taskFormFields(t) {
    return `
        <p class="text-[10px] font-bold text-slate-600 mb-1">Task title</p>
        <input type="text" id="taskTitleInput" value="${t?.title || ''}" placeholder="e.g. Follow up with client"
            class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">

        <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Unit</p>
        <select id="taskUnitInput" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
            <option value="number" ${t?.unit === 'number' ? 'selected' : ''}>Number</option>
            <option value="currency" ${t?.unit === 'currency' ? 'selected' : ''}>Currency (RM)</option>
            <option value="percentage" ${t?.unit === 'percentage' ? 'selected' : ''}>Percentage (%)</option>
        </select>

        <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Target</p>
        <input type="number" step="any" min="0" id="taskTargetInput" value="${t?.target ?? ''}" placeholder="e.g. 10"
            class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
    `;
}

function renderNewTaskForm() {
    document.getElementById('app').innerHTML = card(`
        <p class="text-[14px] font-black text-slate-900 mb-3">New Task</p>
        ${taskFormFields(null)}
        <p class="text-[10px] text-slate-400 mt-3">Personal to-do — doesn't affect any KPI unless you link one later from Edit.</p>
        <div class="flex items-center gap-2 mt-4">
            <button onclick="renderTodo()" class="flex-1 py-2.5 rounded-xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[12px] font-black">Cancel</button>
            <button onclick="saveNewTask()" class="flex-1 py-2.5 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[12px] font-black">Save Task</button>
        </div>
        <p id="taskFormFeedback" class="hidden text-[10px] font-bold text-red-600 mt-2 text-center"></p>
    `);
}

async function saveNewTask() {
    const feedback = document.getElementById('taskFormFeedback');
    const title = document.getElementById('taskTitleInput').value.trim();
    const unit = document.getElementById('taskUnitInput').value;
    const target = document.getElementById('taskTargetInput').value;

    if (!title || target === '' || isNaN(Number(target)) || Number(target) < 0) {
        feedback.textContent = 'Enter a task title and a valid target.';
        feedback.classList.remove('hidden');
        return;
    }

    try {
        await api('/tasks', { method: 'POST', body: JSON.stringify({ title, unit, target: Number(target) }) });
        showToast('Task saved!');
        renderTodo();
    } catch (e) {
        feedback.textContent = e.data?.message || "Couldn't save — please try again.";
        feedback.classList.remove('hidden');
    }
}

function renderEditTask(taskId) {
    const t = (window.__myTasks || []).find(x => x.id === taskId);
    if (!t) { renderTodo(); return; }

    document.getElementById('app').innerHTML = card(`
        <p class="text-[14px] font-black text-slate-900 mb-3">Edit Task</p>
        ${taskFormFields(t)}
        <div class="flex items-center gap-2 mt-4">
            <button onclick="renderTodo()" class="flex-1 py-2.5 rounded-xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[12px] font-black">Cancel</button>
            <button onclick="saveEditTask('${taskId}')" class="flex-1 py-2.5 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[12px] font-black">Save Changes</button>
        </div>
        <p id="taskFormFeedback" class="hidden text-[10px] font-bold text-red-600 mt-2 text-center"></p>
    `);
}

async function saveEditTask(taskId) {
    const feedback = document.getElementById('taskFormFeedback');
    const title = document.getElementById('taskTitleInput').value.trim();
    const unit = document.getElementById('taskUnitInput').value;
    const target = document.getElementById('taskTargetInput').value;

    if (!title || target === '' || isNaN(Number(target)) || Number(target) < 0) {
        feedback.textContent = 'Enter a task title and a valid target.';
        feedback.classList.remove('hidden');
        return;
    }

    try {
        await api(`/tasks/${taskId}`, { method: 'PATCH', body: JSON.stringify({ title, unit, target: Number(target) }) });
        showToast('Task updated!');
        renderTodo();
    } catch (e) {
        feedback.textContent = e.data?.message || "Couldn't save — please try again.";
        feedback.classList.remove('hidden');
    }
}

function renderTaskProgress(taskId) {
    const t = (window.__myTasks || []).find(x => x.id === taskId);
    if (!t) { renderTodo(); return; }

    const pct = t.target > 0 ? Math.max(0, Math.min(100, (t.actual / t.target) * 100)) : 0;
    const badge = achvBadge(pct);

    document.getElementById('app').innerHTML = card(`
        <p class="text-[14px] font-black text-slate-900">${t.title}</p>
        <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-3 overflow-hidden">
            <div class="h-full rounded-full bg-gradient-to-r ${badge.bar}" style="width:${pct}%"></div>
        </div>
        <div class="flex items-center justify-between mt-1.5">
            <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(t.target, t.unit)}</span></p>
            <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(t.actual, t.unit)}</span></p>
            <p class="text-[10px] font-black text-slate-700">${pct.toFixed(0)}%</p>
        </div>
        <p class="text-[10px] font-bold text-slate-600 mt-4 mb-1">How much did today add?</p>
        <div class="flex items-center gap-2">
            <input type="number" step="any" placeholder="e.g. 5 or -1" id="taskDeltaInput"
                class="flex-1 min-w-0 text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
            <button onclick="submitTaskProgress('${t.id}')" class="px-5 py-2.5 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[12px] font-black shrink-0 shadow-[0_4px_12px_rgba(22,163,74,.4)]">Update</button>
        </div>
        <p class="text-[9px] text-slate-400 mt-1">Use a minus sign to reduce. This updates the task only.</p>
        <button onclick="renderTodo()" class="w-full mt-4 py-2 rounded-xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[12px] font-black">← Back</button>
        <p id="taskProgressFeedback" class="hidden text-[10px] font-bold mt-2 text-center"></p>
    `);
}

async function submitTaskProgress(taskId) {
    const t = (window.__myTasks || []).find(x => x.id === taskId);
    const input = document.getElementById('taskDeltaInput');
    const feedback = document.getElementById('taskProgressFeedback');
    const raw = input.value.trim();

    if (raw === '' || isNaN(Number(raw)) || Number(raw) === 0) {
        showToast('Enter an amount first, e.g. 5 or -1.');
        return;
    }

    const delta = Number(raw);
    if (delta < 0 && (Number(t.actual) + delta) < 0) {
        feedback.textContent = `Can't reduce — this task's actual is only ${t.actual}.`;
        feedback.className = 'text-[10px] font-bold mt-2 text-red-600';
        feedback.classList.remove('hidden');
        return;
    }

    feedback.classList.add('hidden');

    try {
        await api(`/tasks/${taskId}/progress`, { method: 'POST', body: JSON.stringify({ delta }) });
        showToast('Task updated!');
        renderTodo();
    } catch (e) {
        feedback.textContent = e.data?.message || "Couldn't update — please try again.";
        feedback.className = 'text-[10px] font-bold mt-2 text-red-600';
        feedback.classList.remove('hidden');
    }
}

function confirmDeleteTask(taskId) {
    const t = (window.__myTasks || []).find(x => x.id === taskId);
    if (!t) return;
    if (!confirm(`Delete "${t.title}"? This can't be undone.`)) return;

    api(`/tasks/${taskId}`, { method: 'DELETE' })
        .then(() => { showToast('Task deleted.'); renderTodo(); })
        .catch(e => showToast(e.data?.message || "Couldn't delete — please try again."));
}

/* ---------------------------------------------------------------- */
/* MONTHLY SCORE — AI-generated score + narrative, read-only, already */
/* generated on a schedule (TelegramReviewService). Defaults to        */
/* "monthly" here since that's the primary ask, but weekly/quarterly   */
/* are one tap away, same as the Telegram version.                     */
/* ---------------------------------------------------------------- */

const REVIEW_PERIODS = [
    { key: 'weekly', label: 'Weekly' },
    { key: 'monthly', label: 'Monthly' },
    { key: 'quarterly', label: 'Quarterly' },
];

function reviewBand(score) {
    if (score >= 90) return { label: 'Excellent', cls: 'text-emerald-800 border-emerald-700' };
    if (score >= 75) return { label: 'Good', cls: 'text-slate-700 border-slate-400' };
    if (score >= 50) return { label: 'Needs Attention', cls: 'text-amber-800 border-amber-700' };
    return { label: 'At Risk', cls: 'text-rose-800 border-rose-700' };
}

function reviewEmptyNote(periodType) {
    if (periodType === 'weekly') return 'Generated every Sunday evening, covering the previous 7 days.';
    if (periodType === 'monthly') return 'Generated on the 1st of each month, covering the month before.';
    return 'Generated automatically when one of your KPI quarters closes.';
}

function reviewCard(r) {
    const band = reviewBand(r.score);
    return card(`
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">${r.period_label}</p>
        <p class="text-[28px] font-black text-slate-900 mt-1 leading-none">${Math.round(r.score)}<span class="text-[13px] font-bold text-slate-400">/100</span></p>
        <span class="inline-block mt-2 px-2 py-0.5 rounded-full border text-[9px] font-bold ${band.cls}">${band.label}</span>
        <p class="text-[12px] text-slate-600 leading-relaxed mt-3 pt-3 border-t border-slate-200">${r.narrative}</p>
    `);
}

async function renderScore(periodType) {
    periodType = periodType || 'monthly';
    const app = document.getElementById('app');

    const tabs = `
        <div class="flex items-center gap-1 border-b-2 border-slate-200 mb-4">
            ${REVIEW_PERIODS.map(p => `
                <button onclick="renderScore('${p.key}')"
                    class="flex-1 pb-2.5 text-[12px] font-bold ${p.key === periodType ? 'text-slate-900 border-b-2 border-slate-900 -mb-[2px]' : 'text-slate-400'}">
                    ${p.label}
                </button>
            `).join('')}
        </div>
    `;

    app.innerHTML = tabs + `<p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>`;

    let data;
    try {
        data = await api(`/reviews?period=${periodType}`);
    } catch (e) {
        app.innerHTML = tabs + card(`<p class="text-[13px] text-slate-600 text-center py-6">Could not load your review.</p>`);
        return;
    }

    window.__reviewHistory = data.history || [];

    if (!data.latest) {
        app.innerHTML = tabs + card(`
            <p class="text-[13px] font-bold text-slate-900 mb-1.5">No review yet</p>
            <p class="text-[12px] text-slate-500 leading-relaxed">${reviewEmptyNote(periodType)}</p>
        `);
        return;
    }

    const historyRows = window.__reviewHistory.length ? `
        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-bold mt-4 mb-1.5 px-1">Previous periods</p>
        <div>${window.__reviewHistory.map((r, i) => `
            <button onclick="renderReviewDetail(${i})" class="w-full text-left tap-card">
                ${card(`<div class="flex items-center justify-between gap-2"><p class="text-[12px] font-bold text-slate-700">${r.period_label}</p><p class="text-[12px] font-black text-slate-500">${Math.round(r.score)}/100</p></div>`, 'hover:border-slate-400')}
            </button>
        `).join('<div class="h-1.5"></div>')}</div>
    ` : '';

    app.innerHTML = tabs + reviewCard(data.latest) + historyRows;
}

function renderReviewDetail(index) {
    const r = (window.__reviewHistory || [])[index];
    if (!r) { renderScore('monthly'); return; }
    document.getElementById('app').innerHTML = reviewCard(r);
}

if (document.getElementById('tab-kpis')) {
    switchTab('kpis');
}
</script>

</body>
</html>
