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
        .soft-card { box-shadow: 0 8px 24px -8px rgba(15,23,42,.12), 0 2px 8px rgba(15,23,42,.06); }
        .tab-btn { transition: all .15s; }
        .tab-btn.active { background: #1a3d34; color: #fff; }
        .tab-btn:not(.active) { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
    </style>
</head>
<body class="bg-[#F5F5F3] min-h-screen">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen">
    <div class="sticky top-0 z-30 px-4 pt-4 pb-2 bg-[#F5F5F3]">
        <div class="rounded-[18px] bg-gradient-to-r from-[#1A0A0A] to-[#7A0019] text-white px-6 py-4 shadow-xl flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center text-base">📱</div>
                <div>
                    <h1 class="text-base font-black leading-tight">Mini App</h1>
                    <p class="text-white/65 text-[10px] mt-0.5">Quick KPI updates, to-dos, and your monthly score</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <button id="tab-kpis" onclick="switchTab('kpis')" class="tab-btn active px-4 py-2 rounded-xl text-xs font-black">📊 My KPIs</button>
            <button id="tab-todo" onclick="switchTab('todo')" class="tab-btn px-4 py-2 rounded-xl text-xs font-black">✅ To-Do List</button>
            <button id="tab-score" onclick="switchTab('score')" class="tab-btn px-4 py-2 rounded-xl text-xs font-black">📈 Monthly Score</button>
        </div>
    </div>

    <div id="toast" class="hidden fixed top-4 right-4 z-50 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold shadow-lg"></div>

    <div id="app" class="px-4 pb-10 pt-3 max-w-3xl">
        <p class="text-center text-slate-400 text-xs mt-10">Loading…</p>
    </div>
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

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('en-MY', { timeZone: 'Asia/Kuala_Lumpur', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

const CATEGORY_ORDER = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];
const CATEGORY_COLORS = {
    'Financial':         { pill: 'bg-emerald-700 text-white', icon: '💰' },
    'Growth & Customer': { pill: 'bg-indigo-700 text-white',  icon: '📈' },
    'Initiatives':       { pill: 'bg-amber-600 text-white',   icon: '🚀' },
    'People':            { pill: 'bg-pink-700 text-white',    icon: '👥' },
};
const DEFAULT_CATEGORY = { pill: 'bg-slate-600 text-white', icon: '📌' };

function sortByCategory(items) {
    return [...items].sort((a, b) => {
        const ai = CATEGORY_ORDER.indexOf(a.category); const bi = CATEGORY_ORDER.indexOf(b.category);
        return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
    });
}

function achvBadge(score) {
    if (score >= 90) return { label: 'Excellent', color: 'bg-emerald-100 text-emerald-700', bar: 'from-emerald-400 to-green-500' };
    if (score >= 75) return { label: 'Good', color: 'bg-[#F5EAE0] text-[#6B3F2A]', bar: 'from-[#8B5E4A] to-[#6B3F2A]' };
    if (score >= 50) return { label: 'Watch', color: 'bg-amber-100 text-amber-700', bar: 'from-amber-400 to-amber-500' };
    return { label: 'Critical', color: 'bg-red-100 text-red-700', bar: 'from-red-400 to-rose-500' };
}

function card(inner, extra = '') {
    return `<div class="bg-white rounded-2xl soft-card border border-slate-200 p-4 ${extra}">${inner}</div>`;
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
/* ---------------------------------------------------------------- */

async function renderMyKpis() {
    const app = document.getElementById('app');
    app.innerHTML = `<p class="text-center text-slate-400 text-xs mt-10">Loading…</p>`;

    let openData, summaryData;
    try {
        [openData, summaryData] = await Promise.all([api('/kpis/open'), api('/kpis/summary')]);
    } catch (e) {
        app.innerHTML = card(`<p class="text-sm text-slate-600 text-center py-6">Could not load your KPIs.</p>`);
        return;
    }

    const notLogged = (openData.kpis || []).filter(k => !k.already_logged_today);
    const banner = notLogged.length ? `
        <div class="mb-3 rounded-2xl bg-amber-50 border border-amber-200 px-5 py-3.5">
            <p class="text-xs font-black text-amber-800">⏰ Reminder — ${notLogged.length} KPI(s) not updated today</p>
            <p class="text-[11px] text-amber-700 mt-1">${notLogged.map(k => k.kpi_title).join(', ')}</p>
        </div>
    ` : `
        <div class="mb-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-5 py-3.5">
            <p class="text-xs font-black text-emerald-800">✅ All caught up — every open KPI has been updated today.</p>
        </div>
    `;

    if (!summaryData.kpis.length) {
        app.innerHTML = banner + card(`<p class="text-sm text-slate-600 text-center py-6">No KPIs found for this financial year.</p>`);
        return;
    }

    window.__quarterActuals = {};
    summaryData.kpis.forEach(k => (k.quarters || []).forEach(q => { window.__quarterActuals[q.id] = q.actual; }));

    const sorted = sortByCategory(summaryData.kpis);
    let lastCategory = null;
    let html = banner;

    sorted.forEach(k => {
        if (k.category !== lastCategory) {
            const cat = CATEGORY_COLORS[k.category] || DEFAULT_CATEGORY;
            html += `<div class="flex items-center gap-2 mt-4 mb-1 px-1"><span class="text-sm">${cat.icon}</span><p class="text-[11px] font-black uppercase tracking-wide text-slate-500">${k.category || 'Other'}</p></div>`;
            lastCategory = k.category;
        }

        const cat = CATEGORY_COLORS[k.category] || DEFAULT_CATEGORY;
        const aBadge = achvBadge(k.achievement_percentage);
        const pct = Math.max(0, Math.min(100, k.achievement_percentage));
        const quarterRows = (k.quarters || []).map(q => quarterRow(k.kpi_id, q, k.unit)).join('');

        html += card(`
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="px-2 py-0.5 rounded-full ${cat.pill} text-[8px] font-black">${cat.icon} ${k.category || '-'}</span>
                    <p class="text-sm font-black text-slate-900 leading-snug mt-1.5">${k.kpi_title}</p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full ${aBadge.color} text-[9px] font-black">${aBadge.label}</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r ${aBadge.bar}" style="width:${pct}%"></div>
            </div>
            <div class="flex items-center justify-between mt-1.5">
                <p class="text-[10px] text-slate-500 font-bold">Overall (Full Year)</p>
                <p class="text-[11px] text-slate-700 font-black">${formatUnit(k.actual_value, k.unit)} · ${k.achievement_percentage}%</p>
            </div>
            <div class="mt-3 pt-3 border-t border-dashed border-slate-200 space-y-1.5">${quarterRows || '<p class="text-[10px] text-slate-400">No quarters set up yet.</p>'}</div>
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

    const updateControl = isCurrent ? `
        <div class="mt-2.5 flex items-center gap-2">
            <input type="number" step="any" placeholder="e.g. 50 or -10" id="delta-${kpiId}"
                class="flex-1 min-w-0 text-xs px-3 py-2 rounded-xl border border-slate-300 bg-white outline-none focus:border-red-500">
            <button onclick="submitDelta('${kpiId}','${q.id}')" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black shrink-0">Update</button>
        </div>
        <p id="feedback-${kpiId}" class="hidden text-[10px] font-bold mt-1.5"></p>
    ` : '';

    return `
        <div class="rounded-xl px-3 py-2.5 ${isCurrent ? 'bg-red-50 border border-red-300' : 'bg-slate-50 border border-slate-200'}">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-black ${isCurrent ? 'text-red-700' : 'text-slate-600'}">${q.quarter}</p>
                <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${label.cls}">${label.text}</span>
            </div>
            <div class="w-full h-1.5 bg-slate-200 rounded-full mt-2 overflow-hidden">
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
/* TO-DO LIST — personal tasks, NOT linked to KPI actuals unless you  */
/* explicitly tick a KPI to track it under (purely for visibility).   */
/* ---------------------------------------------------------------- */

async function renderTodo() {
    const app = document.getElementById('app');
    app.innerHTML = `<p class="text-center text-slate-400 text-xs mt-10">Loading your to-dos…</p>`;

    let data;
    try {
        data = await api('/tasks');
    } catch (e) {
        app.innerHTML = card(`<p class="text-sm text-slate-600 text-center py-6">Could not load your to-dos.</p>`);
        return;
    }

    window.__myTasks = data.tasks || [];

    const header = `
        <button onclick="renderNewTaskForm()" class="w-full py-3 rounded-2xl bg-[#1a3d34] hover:bg-[#123028] text-white text-xs font-black shadow">
            ➕ New Task
        </button>
    `;

    if (!window.__myTasks.length) {
        app.innerHTML = header + `<div class="mt-3">${card(`<p class="text-sm text-slate-600 text-center py-6">No to-dos yet. Tap "New Task" to start your list.</p>`)}</div>`;
        return;
    }

    const rows = window.__myTasks.map(t => taskCard(t)).join('<div class="h-2"></div>');
    app.innerHTML = header + `<div class="mt-3">${rows}</div>`;
}

function taskCard(t) {
    const pct = t.target > 0 ? Math.max(0, Math.min(100, (t.actual / t.target) * 100)) : 0;
    const badge = achvBadge(pct);
    const kpiChips = (t.linked_kpis || []).length
        ? `<div class="flex flex-wrap gap-1.5 mt-2">${t.linked_kpis.map(k => `<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[8px] font-black">🔗 ${k.kpi_title}</span>`).join('')}</div>`
        : '';

    return card(`
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-black text-slate-900 leading-snug min-w-0">${t.title}</p>
            <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full shrink-0 ${t.status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                ${t.status === 'done' ? '✓ Done' : 'In Progress'}
            </span>
        </div>
        <div class="w-full h-1.5 bg-slate-100 rounded-full mt-2 overflow-hidden">
            <div class="h-full rounded-full bg-gradient-to-r ${badge.bar}" style="width:${pct}%"></div>
        </div>
        <div class="flex items-center justify-between mt-1.5">
            <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(t.target, t.unit)}</span></p>
            <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(t.actual, t.unit)}</span></p>
            <p class="text-[10px] font-black text-slate-700">${pct.toFixed(0)}%</p>
        </div>
        ${kpiChips}
        <div class="flex items-center gap-2 mt-3">
            <button onclick="renderTaskProgress('${t.id}')" class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black">📝 Update</button>
            <button onclick="renderEditTask('${t.id}')" class="flex-1 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 text-[11px] font-black">✏️ Edit</button>
            <button onclick="confirmDeleteTask('${t.id}')" class="px-3 py-2 rounded-xl bg-white border border-red-300 text-red-600 text-[11px] font-black">🗑️</button>
        </div>
    `);
}

function taskFormFields(t) {
    return `
        <p class="text-[10px] font-bold text-slate-600 mb-1">Task title</p>
        <input type="text" id="taskTitleInput" value="${t?.title || ''}" placeholder="e.g. Follow up with client"
            class="w-full text-sm px-3 py-2.5 rounded-xl border border-slate-300 bg-white outline-none focus:border-[#1a3d34]">

        <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Unit</p>
        <select id="taskUnitInput" class="w-full text-sm px-3 py-2.5 rounded-xl border border-slate-300 bg-white outline-none focus:border-[#1a3d34]">
            <option value="number" ${t?.unit === 'number' ? 'selected' : ''}>Number</option>
            <option value="currency" ${t?.unit === 'currency' ? 'selected' : ''}>Currency (RM)</option>
            <option value="percentage" ${t?.unit === 'percentage' ? 'selected' : ''}>Percentage (%)</option>
        </select>

        <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Target</p>
        <input type="number" step="any" min="0" id="taskTargetInput" value="${t?.target ?? ''}" placeholder="e.g. 10"
            class="w-full text-sm px-3 py-2.5 rounded-xl border border-slate-300 bg-white outline-none focus:border-[#1a3d34]">
    `;
}

function renderNewTaskForm() {
    document.getElementById('app').innerHTML = card(`
        <p class="text-sm font-black text-slate-900 mb-3">New Task</p>
        ${taskFormFields(null)}
        <p class="text-[10px] text-slate-400 mt-3">This is a personal to-do — it does not affect any KPI unless you link one from Edit later.</p>
        <div class="flex items-center gap-2 mt-4">
            <button onclick="renderTodo()" class="flex-1 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-600 text-xs font-black">Cancel</button>
            <button onclick="saveNewTask()" class="flex-1 py-2.5 rounded-xl bg-[#1a3d34] hover:bg-[#123028] text-white text-xs font-black">Save Task</button>
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
        <p class="text-sm font-black text-slate-900 mb-3">Edit Task</p>
        ${taskFormFields(t)}
        <div class="flex items-center gap-2 mt-4">
            <button onclick="renderTodo()" class="flex-1 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-600 text-xs font-black">Cancel</button>
            <button onclick="saveEditTask('${taskId}')" class="flex-1 py-2.5 rounded-xl bg-[#1a3d34] hover:bg-[#123028] text-white text-xs font-black">Save Changes</button>
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
        <p class="text-sm font-black text-slate-900">${t.title}</p>
        <div class="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
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
                class="flex-1 min-w-0 text-sm px-3 py-2.5 rounded-xl border border-slate-300 bg-white outline-none focus:border-[#1a3d34]">
            <button onclick="submitTaskProgress('${t.id}')" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shrink-0">Update</button>
        </div>
        <p class="text-[9px] text-slate-400 mt-1">Use a minus sign to reduce. This updates the task only.</p>
        <button onclick="renderTodo()" class="w-full mt-4 py-2 rounded-xl bg-white border border-slate-300 text-slate-600 text-xs font-black">← Back</button>
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
/* MONTHLY SCORE — AI-generated review, read-only, pre-generated on   */
/* a schedule (see TelegramReviewService). Defaults to "monthly".     */
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
        <p class="text-3xl font-black text-slate-900 mt-1 leading-none">${Math.round(r.score)}<span class="text-sm font-bold text-slate-400">/100</span></p>
        <span class="inline-block mt-2 px-2 py-0.5 rounded-full border text-[9px] font-bold ${band.cls}">${band.label}</span>
        <p class="text-sm text-slate-600 leading-relaxed mt-3 pt-3 border-t border-slate-200">${r.narrative}</p>
    `);
}

async function renderScore(periodType) {
    periodType = periodType || 'monthly';
    const app = document.getElementById('app');

    const tabs = `
        <div class="flex items-center gap-1 border-b-2 border-slate-200 mb-4">
            ${REVIEW_PERIODS.map(p => `
                <button onclick="renderScore('${p.key}')"
                    class="flex-1 pb-2.5 text-xs font-bold ${p.key === periodType ? 'text-slate-900 border-b-2 border-slate-900 -mb-[2px]' : 'text-slate-400'}">
                    ${p.label}
                </button>
            `).join('')}
        </div>
    `;

    app.innerHTML = tabs + `<p class="text-center text-slate-400 text-xs mt-10">Loading…</p>`;

    let data;
    try {
        data = await api(`/reviews?period=${periodType}`);
    } catch (e) {
        app.innerHTML = tabs + card(`<p class="text-sm text-slate-600 text-center py-6">Could not load your review.</p>`);
        return;
    }

    window.__reviewHistory = data.history || [];

    if (!data.latest) {
        app.innerHTML = tabs + card(`
            <p class="text-sm font-bold text-slate-900 mb-1.5">No review yet</p>
            <p class="text-sm text-slate-500 leading-relaxed">${reviewEmptyNote(periodType)}</p>
        `);
        return;
    }

    const historyRows = window.__reviewHistory.length ? `
        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-bold mt-4 mb-1.5 px-1">Previous periods</p>
        <div>${window.__reviewHistory.map((r, i) => `
            <button onclick="renderReviewDetail(${i})" class="w-full text-left">
                ${card(`<div class="flex items-center justify-between gap-2"><p class="text-xs font-bold text-slate-700">${r.period_label}</p><p class="text-xs font-black text-slate-500">${Math.round(r.score)}/100</p></div>`, 'hover:border-slate-400')}
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

switchTab('kpis');
</script>

</body>
</html>
