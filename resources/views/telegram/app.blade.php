<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>KPI Mini App</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .soft-card { box-shadow: 0 6px 20px rgba(15,23,42,.06); }
        .tap-card { transition: border-color .15s, background .15s; }
        .sticky-bottom { position: sticky; bottom: 0; padding-bottom: env(safe-area-inset-bottom, 12px); }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

<div class="max-w-md mx-auto min-h-screen flex flex-col">
    <div id="topbar" class="bg-[#0d2218] text-white px-4 py-3.5 flex items-center gap-3 shrink-0">
        <button id="backBtn" onclick="goHome()" class="hidden text-white/80 text-lg leading-none">←</button>
        <h1 id="topbarTitle" class="text-[15px] font-black">KPI Mini App</h1>
    </div>

    <div id="toast" class="hidden mx-4 mt-3 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-semibold"></div>

    <div id="app" class="flex-1 p-4 space-y-3">
        <p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>
    </div>
</div>

<script>
    const tg = window.Telegram?.WebApp;
    tg?.ready();
    tg?.expand();

    const initData = tg?.initData || '';
    const params = new URLSearchParams(window.location.search);
    const deepLinkScreen = params.get('screen') || 'home';

    const state = {
        employeeId: sessionStorage.getItem('tg_employee_id') || null,
        companyCode: sessionStorage.getItem('tg_company_code') || null,
        screen: 'home',
    };

    function showToast(message) {
        const t = document.getElementById('toast');
        t.textContent = message;
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 4000);
    }

    async function api(path, opts = {}) {
        const res = await fetch('/api/telegram' + path, {
            ...opts,
            headers: {
                'Content-Type': 'application/json',
                'X-Telegram-Init-Data': initData,
                ...(opts.headers || {}),
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const err = new Error(data.message || 'Request failed');
            err.status = res.status;
            err.data = data;
            throw err;
        }
        return data;
    }

    function formatUnit(value, unit) {
        const n = Number(value || 0);
        if (unit === 'currency') return 'RM ' + n.toLocaleString(undefined, { maximumFractionDigits: 0 });
        if (unit === 'percentage') return n.toLocaleString(undefined, { maximumFractionDigits: 2 }) + '%';
        return n.toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    const STATUS_COLORS = {
        completed: 'bg-emerald-100 text-emerald-700',
        on_track: 'bg-emerald-100 text-emerald-700',
        at_risk: 'bg-amber-100 text-amber-700',
        in_trouble: 'bg-red-100 text-red-700',
        not_started: 'bg-slate-100 text-slate-500',
    };

    function setTopbar(title, showBack) {
        document.getElementById('topbarTitle').textContent = title;
        document.getElementById('backBtn').classList.toggle('hidden', !showBack);
    }

    function goHome() {
        window.location.href = '/telegram/app';
    }

    function card(inner, extraClasses = '') {
        return `<div class="bg-white rounded-2xl soft-card border border-slate-200 p-4 ${extraClasses}">${inner}</div>`;
    }

    /* ---------------------------------------------------------------- */
    /* BOOT                                                              */
    /* ---------------------------------------------------------------- */

    async function boot() {
        let status;
        try {
            status = await api('/link/status');
        } catch (e) {
            renderError('Something went wrong. Pull to refresh and try again.');
            return;
        }

        if (!status.linked) {
            renderNotLinked();
            return;
        }

        const dashboards = status.dashboards || [];

        if (dashboards.length === 0) {
            renderError('No active dashboard found for your account. Please contact your admin.');
            return;
        }

        if (dashboards.length === 1 || (state.employeeId && dashboards.some(d => d.employee_id === state.employeeId))) {
            if (!state.employeeId) selectDashboard(dashboards[0], false);
            routeToScreen();
            return;
        }

        renderChooseDashboard(dashboards);
    }

    function selectDashboard(d, thenRoute = true) {
        state.employeeId = d.employee_id;
        state.companyCode = d.company_code;
        sessionStorage.setItem('tg_employee_id', d.employee_id);
        sessionStorage.setItem('tg_company_code', d.company_code);
        if (thenRoute) routeToScreen();
    }

    function routeToScreen() {
        if (deepLinkScreen === 'morning') return renderMorning();
        if (deepLinkScreen === 'evening') return renderEvening();
        if (deepLinkScreen === 'summary') return renderSummary();
        renderHome();
    }

    /* ---------------------------------------------------------------- */
    /* SCREENS                                                           */
    /* ---------------------------------------------------------------- */

    function renderError(message) {
        setTopbar('KPI Mini App', false);
        document.getElementById('app').innerHTML = card(`
            <p class="text-[13px] text-slate-600 text-center py-6">${message}</p>
        `);
    }

    function renderNotLinked() {
        setTopbar('Not Connected', false);
        document.getElementById('app').innerHTML = card(`
            <p class="text-[13px] font-black text-slate-900 mb-1">Not connected yet</p>
            <p class="text-[12px] text-slate-500 leading-relaxed">
                Open the KPI Dashboard on the web, go to <b>My Profile</b>, and tap
                <b>Connect Telegram</b> to link this account.
            </p>
        `);
    }

    function renderChooseDashboard(dashboards) {
        setTopbar('Choose Dashboard', false);
        const rows = dashboards.map(d => `
            <button onclick='pickDashboard(${JSON.stringify(d)})' class="w-full text-left tap-card ${card(`
                <p class="text-[13px] font-black text-slate-900">${d.company_display_name}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">${d.short_name} · <span class="uppercase font-semibold">${d.role || ''}</span></p>
            `, 'hover:border-[#6B9080]')}</button>
        `).join('');
        document.getElementById('app').innerHTML = `<div class="space-y-2">${rows}</div>`;
    }

    function pickDashboard(d) {
        selectDashboard(d, true);
    }

    function renderHome() {
        setTopbar('KPI Mini App', false);
        document.getElementById('app').innerHTML = `
            <div class="space-y-2">
                <button onclick="window.location.href='/telegram/app?screen=morning'" class="w-full text-left ${card(`
                    <div class="flex items-center justify-between">
                        <div><p class="text-[13px] font-black">📝 Set Today's To-Do</p><p class="text-[11px] text-slate-500 mt-0.5">Pick KPIs to work on today</p></div>
                        <span class="text-slate-300">›</span>
                    </div>
                `)}</button>
                <button onclick="window.location.href='/telegram/app?screen=evening'" class="w-full text-left ${card(`
                    <div class="flex items-center justify-between">
                        <div><p class="text-[13px] font-black">📈 Update Today's Progress</p><p class="text-[11px] text-slate-500 mt-0.5">Log what you got done</p></div>
                        <span class="text-slate-300">›</span>
                    </div>
                `)}</button>
                <button onclick="window.location.href='/telegram/app?screen=summary'" class="w-full text-left ${card(`
                    <div class="flex items-center justify-between">
                        <div><p class="text-[13px] font-black">📊 My KPI Summary</p><p class="text-[11px] text-slate-500 mt-0.5">Target vs actual, by quarter</p></div>
                        <span class="text-slate-300">›</span>
                    </div>
                `)}</button>
                <div class="pt-4 text-center">
                    <button onclick="confirmDisconnect()" class="text-[11px] text-slate-400 underline">Disconnect Telegram</button>
                </div>
            </div>
        `;
    }

    async function confirmDisconnect() {
        const doDisconnect = () => api('/link/disconnect', { method: 'POST' }).then(() => tg?.close());
        if (tg?.showConfirm) {
            tg.showConfirm('Disconnect Telegram from your KPI account?', (ok) => { if (ok) doDisconnect(); });
        } else if (confirm('Disconnect Telegram from your KPI account?')) {
            doDisconnect();
        }
    }

    async function renderMorning() {
        setTopbar("Set Today's To-Do", true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading your KPIs…</p>`;

        let data;
        try {
            data = await api(`/kpis/open?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load your KPIs. Please try again.');
            return;
        }

        if (!data.kpis.length) {
            app.innerHTML = card(`
                <p class="text-[13px] text-slate-600 text-center py-6">
                    You have no open KPIs for the current quarter.<br>Check the web dashboard.
                </p>
            `);
            return;
        }

        const selected = {};

        app.innerHTML = `
            <div id="kpiList" class="space-y-2 pb-24"></div>
            <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto sticky-bottom bg-gradient-to-t from-[#f0f2f7] via-[#f0f2f7] px-4 pt-3">
                <button id="saveTasksBtn" disabled onclick="saveTasks()" class="w-full py-3 rounded-2xl bg-slate-300 text-white text-[13px] font-black transition">
                    Save Today's Tasks
                </button>
            </div>
        `;

        const list = document.getElementById('kpiList');

        data.kpis.forEach(k => {
            const wrap = document.createElement('div');
            const isSelected = k.already_planned_today;
            if (isSelected) selected[k.kpi_id] = { kpi_quarter_id: k.kpi_quarter_id, planned_target: k.planned_target, planned_note: k.planned_note || '' };

            wrap.innerHTML = `
                <div class="tap-card bg-white rounded-2xl soft-card border ${isSelected ? 'border-[#6B9080] bg-[#6B9080]/5' : 'border-slate-200'} p-4" data-kpi="${k.kpi_id}">
                    <button class="w-full text-left" onclick="toggleKpi('${k.kpi_id}')">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-[13px] font-black text-slate-900">${k.kpi_title}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">${k.category || ''} · ${k.quarter}</p>
                            </div>
                            ${k.already_planned_today ? '<span class="text-[9px] font-black px-2 py-1 rounded-full bg-[#CCE3DE] text-[#1a3d34] shrink-0">✓ Planned</span>' : ''}
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2">${formatUnit(k.quarter_actual, k.unit)} / ${formatUnit(k.quarter_target, k.unit)}</p>
                    </button>
                    <div class="task-inputs ${isSelected ? '' : 'hidden'} mt-3 space-y-2">
                        <input type="number" min="0" step="any" placeholder="Today's target (${k.unit})" value="${k.planned_target ?? ''}"
                            oninput="updateTaskField('${k.kpi_id}', 'planned_target', this.value)"
                            class="w-full text-[12px] px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-[#6B9080]">
                        <input type="text" placeholder="Note (optional)" value="${(k.planned_note || '').replace(/"/g, '&quot;')}"
                            oninput="updateTaskField('${k.kpi_id}', 'planned_note', this.value)"
                            class="w-full text-[12px] px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-[#6B9080]">
                    </div>
                </div>
            `;
            list.appendChild(wrap);
        });

        window.__morningKpis = data.kpis;
        window.__selectedTasks = selected;
        refreshSaveButton();
    }

    function toggleKpi(kpiId) {
        const el = document.querySelector(`[data-kpi="${kpiId}"]`);
        const inputs = el.querySelector('.task-inputs');
        const isNowSelected = inputs.classList.contains('hidden');

        inputs.classList.toggle('hidden');
        el.classList.toggle('border-[#6B9080]', isNowSelected);
        el.classList.toggle('bg-[#6B9080]/5', isNowSelected);
        el.classList.toggle('border-slate-200', !isNowSelected);

        if (isNowSelected) {
            const kpi = window.__morningKpis.find(k => k.kpi_id === kpiId);
            window.__selectedTasks[kpiId] = { kpi_quarter_id: kpi.kpi_quarter_id, planned_target: kpi.planned_target || '', planned_note: kpi.planned_note || '' };
        } else {
            delete window.__selectedTasks[kpiId];
        }
        refreshSaveButton();
    }

    function updateTaskField(kpiId, field, value) {
        if (!window.__selectedTasks[kpiId]) return;
        window.__selectedTasks[kpiId][field] = value;
    }

    function refreshSaveButton() {
        const btn = document.getElementById('saveTasksBtn');
        const count = Object.keys(window.__selectedTasks || {}).length;
        btn.disabled = count === 0;
        btn.className = `w-full py-3 rounded-2xl text-white text-[13px] font-black transition ${count === 0 ? 'bg-slate-300' : 'bg-[#6B9080] hover:bg-[#5a7a6d]'}`;
    }

    async function saveTasks() {
        const tasks = Object.entries(window.__selectedTasks)
            .filter(([, t]) => t.planned_target !== '' && t.planned_target !== null)
            .map(([kpi_id, t]) => ({
                kpi_id, kpi_quarter_id: t.kpi_quarter_id,
                planned_target: Number(t.planned_target), planned_note: t.planned_note || null,
            }));

        if (!tasks.length) return;

        try {
            await api('/tasks', {
                method: 'POST',
                body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode, tasks }),
            });
            if (tg?.showPopup) tg.showPopup({ message: "Today's tasks saved! ✅" });
            goHome();
        } catch (e) {
            showToast("Couldn't save — please try again.");
        }
    }

    async function renderEvening() {
        setTopbar('Update Progress', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading today's tasks…</p>`;

        let data;
        try {
            data = await api(`/tasks/today?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load today\'s tasks. Please try again.');
            return;
        }

        if (!data.tasks.length) {
            app.innerHTML = card(`
                <p class="text-[13px] text-slate-600 text-center mb-3">You didn't set any tasks this morning.</p>
                <button onclick="window.location.href='/telegram/app?screen=morning'" class="w-full py-2.5 rounded-xl bg-[#6B9080] text-white text-[12px] font-black">
                    Set tasks now
                </button>
            `);
            return;
        }

        app.innerHTML = `
            <div id="taskList" class="space-y-2 pb-24"></div>
            <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto sticky-bottom bg-gradient-to-t from-[#f0f2f7] via-[#f0f2f7] px-4 pt-3">
                <button onclick="submitAllProgress()" class="w-full py-3 rounded-2xl bg-[#6B9080] hover:bg-[#5a7a6d] text-white text-[13px] font-black transition">
                    Submit Progress
                </button>
            </div>
        `;

        const list = document.getElementById('taskList');
        data.tasks.forEach(t => {
            const wrap = document.createElement('div');
            wrap.innerHTML = `
                <div class="bg-white rounded-2xl soft-card border border-slate-200 p-4" data-task="${t.id}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[13px] font-black text-slate-900">${t.kpi_title}</p>
                            ${t.planned_note ? `<p class="text-[10px] text-slate-500 mt-0.5">${t.planned_note}</p>` : ''}
                            <p class="text-[10px] text-slate-500 mt-1">Planned: ${formatUnit(t.planned_target, t.unit)}</p>
                        </div>
                        <span class="task-status-pill text-[9px] font-black px-2 py-1 rounded-full shrink-0 ${t.status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                            ${t.status === 'done' ? '✓ Done' : 'Pending'}
                        </span>
                    </div>
                    <input type="number" min="0" step="any" placeholder="Progress today (${t.unit})" value="${t.progress_value ?? ''}"
                        oninput="window.__eveningInputs['${t.id}']=this.value"
                        class="task-progress-input w-full text-[12px] px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-[#6B9080] mt-3">
                    <p class="task-error hidden text-[10px] text-red-600 font-semibold mt-2"></p>
                </div>
            `;
            list.appendChild(wrap);
        });

        window.__eveningInputs = {};
    }

    async function submitAllProgress() {
        const cards = document.querySelectorAll('[data-task]');
        let allOk = true;

        for (const el of cards) {
            const taskId = el.getAttribute('data-task');
            const value = window.__eveningInputs[taskId];
            if (value === undefined || value === '') continue;

            const errorEl = el.querySelector('.task-error');
            errorEl.classList.add('hidden');

            try {
                await api(`/tasks/${taskId}/progress`, {
                    method: 'POST',
                    body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode, progress_value: Number(value) }),
                });
                const pill = el.querySelector('.task-status-pill');
                pill.className = 'task-status-pill text-[9px] font-black px-2 py-1 rounded-full shrink-0 bg-emerald-100 text-emerald-700';
                pill.textContent = '✓ Done';
            } catch (e) {
                allOk = false;
                errorEl.textContent = e.data?.message || "Couldn't update — try again.";
                errorEl.classList.remove('hidden');
            }
        }

        if (allOk) {
            if (tg?.showPopup) tg.showPopup({ message: 'Progress updated! Your KPI actuals have been refreshed. ✅' });
            renderSummary(true);
        }
    }

    function quarterLabel(state) {
        if (state === 'current') return { text: '🟢 Updating now', cls: 'bg-[#CCE3DE] text-[#1a3d34]' };
        if (state === 'ended') return { text: '✓ Done', cls: 'bg-slate-100 text-slate-500' };
        return { text: 'Upcoming', cls: 'bg-slate-100 text-slate-400' };
    }

    function quarterRow(q, unit) {
        const barPct = Math.max(0, Math.min(100, q.achievement_percentage));
        const isCurrent = q.state === 'current';
        const label = quarterLabel(q.state);

        return `
            <div class="rounded-xl px-3 py-2.5 ${isCurrent ? 'bg-[#6B9080]/8 border-2 border-[#6B9080]' : 'bg-slate-50 border border-slate-100'}">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-black ${isCurrent ? 'text-[#1a3d34]' : 'text-slate-600'}">${q.quarter}</p>
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${label.cls}">${label.text}</span>
                </div>
                <div class="w-full h-1.5 bg-slate-200 rounded-full mt-2 overflow-hidden">
                    <div class="h-full rounded-full ${isCurrent ? 'bg-[#6B9080]' : 'bg-slate-400'}" style="width:${barPct}%"></div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(q.target, unit)}</span></p>
                    <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(q.actual, unit)}</span></p>
                    <p class="text-[10px] font-black ${isCurrent ? 'text-[#1a3d34]' : 'text-slate-500'}">${q.achievement_percentage}%</p>
                </div>
            </div>
        `;
    }

    async function renderSummary(justUpdated = false) {
        setTopbar('My KPI Summary', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>`;

        let data;
        try {
            data = await api(`/kpis/summary?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load your KPI summary.');
            return;
        }

        if (!data.kpis.length) {
            app.innerHTML = card(`<p class="text-[13px] text-slate-600 text-center py-6">No KPIs found for this financial year.</p>`);
            return;
        }

        const banner = justUpdated ? `
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-3 py-2.5 text-[11px] font-bold text-emerald-700 text-center">
                ✅ Your actual has been updated below — synced to the system instantly.
            </div>
        ` : '';

        const cards = data.kpis.map(k => {
            const pct = Math.max(0, Math.min(100, k.achievement_percentage));
            const statusClass = STATUS_COLORS[k.status] || STATUS_COLORS.not_started;
            const quarterRows = (k.quarters || []).map(q => quarterRow(q, k.unit)).join('');

            return card(`
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[9px] uppercase tracking-wide text-slate-400 font-black">KPI</p>
                        <p class="text-[14px] font-black text-slate-900 leading-snug">${k.kpi_title}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">${k.category || ''}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[18px] font-black text-slate-900">${k.achievement_percentage}%</p>
                        <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${statusClass}">${(k.status || '').replace('_', ' ')}</span>
                    </div>
                </div>

                <div class="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
                    <div class="h-full rounded-full ${statusClass.split(' ')[0].replace('100', '400')}" style="width:${pct}%"></div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <p class="text-[10px] text-slate-500 font-bold">Overall (Full Year)</p>
                    <p class="text-[11px] text-slate-700 font-black">${formatUnit(k.actual_value, k.unit)} / ${formatUnit(k.base_target, k.unit)}</p>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100">
                    <p class="text-[9px] uppercase tracking-wide text-slate-400 font-black mb-2">By Quarter</p>
                    <div class="space-y-1.5">${quarterRows || '<p class="text-[10px] text-slate-400">No quarters set up yet.</p>'}</div>
                </div>
            `);
        }).join('<div class="h-2"></div>');

        app.innerHTML = banner + cards;
    }

    boot();
</script>

</body>
</html>
