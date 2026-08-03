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
        .soft-card {
            box-shadow: 0 14px 26px -10px rgba(107,63,42,.28), 0 4px 10px rgba(107,63,42,.14), inset 0 1px 0 rgba(255,255,255,.7);
        }
        .soft-card-sm {
            box-shadow: 0 6px 14px -6px rgba(107,63,42,.22), inset 0 1px 0 rgba(255,255,255,.6);
        }
        .tap-card { transition: border-color .15s, background .15s; }
        .sticky-bottom { position: sticky; bottom: 0; padding-bottom: env(safe-area-inset-bottom, 12px); }
    </style>
</head>
<body class="bg-[#F5EEDC] min-h-screen text-slate-900">

<div class="max-w-md mx-auto min-h-screen flex flex-col">
    <div id="topbar" class="bg-[#6B3F2A] text-white px-4 py-3.5 flex items-center gap-3 shrink-0">
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

    const BOT_USERNAME = '{{ $botUsername }}';
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

    function formatDateTime(iso) {
        return new Date(iso).toLocaleString('en-MY', {
            timeZone: 'Asia/Kuala_Lumpur', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
        });
    }

    // Same category order/colors, status labels, and achievement bands used on
    // the web dashboard (resources/views/kpi/my-department-kpi.blade.php), so
    // the Mini App matches the system rather than inventing its own palette.
    const CATEGORY_ORDER = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];

    const CATEGORY_COLORS = {
        'Financial':         { catPill: 'bg-emerald-700 text-white', subPill: 'bg-emerald-100 text-emerald-700' },
        'Growth & Customer': { catPill: 'bg-indigo-700 text-white',  subPill: 'bg-indigo-100 text-indigo-700' },
        'Initiatives':       { catPill: 'bg-amber-600 text-white',   subPill: 'bg-amber-100 text-amber-700' },
        'People':            { catPill: 'bg-pink-700 text-white',    subPill: 'bg-pink-100 text-pink-700' },
    };
    const DEFAULT_CATEGORY_COLOR = { catPill: 'bg-slate-600 text-white', subPill: 'bg-slate-100 text-slate-600' };

    // Shared ordering for every screen that lists KPIs, so every screen
    // groups the same way "My KPIs" does: category order first, then
    // sub-category alphabetically within it.
    function sortByCategoryAndSub(items) {
        return [...items].sort((a, b) => {
            const ai = CATEGORY_ORDER.indexOf(a.category); const bi = CATEGORY_ORDER.indexOf(b.category);
            const catDiff = (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
            if (catDiff !== 0) return catDiff;
            return (a.sub_category || '').localeCompare(b.sub_category || '');
        });
    }

    const STATUS_LABELS = {
        completed:   { label: 'Completed',   color: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
        on_track:    { label: 'On Track',    color: 'bg-[#F5EAE0] text-[#6B3F2A]',     dot: 'bg-[#6B3F2A]' },
        at_risk:     { label: 'At Risk',     color: 'bg-yellow-100 text-yellow-700',   dot: 'bg-yellow-500' },
        in_trouble:  { label: 'In Trouble',  color: 'bg-red-100 text-red-700',         dot: 'bg-red-500' },
        not_started: { label: 'Not Started', color: 'bg-slate-100 text-slate-500',     dot: 'bg-slate-400' },
    };

    // Task lifecycle (distinct from the KPI STATUS_LABELS above) and
    // priority pills, mirroring the web Mini App's Task Details screen.
    const TASK_STATUS_PILL = {
        not_started: { label: 'Not Started', color: 'bg-slate-100 text-slate-500' },
        in_progress: { label: 'In Progress', color: 'bg-amber-100 text-amber-700' },
        done:        { label: 'Done',        color: 'bg-emerald-100 text-emerald-700' },
        blocked:     { label: 'Blocked',     color: 'bg-red-100 text-red-700' },
        cancelled:   { label: 'Cancelled',   color: 'bg-slate-100 text-slate-400' },
    };
    const PRIORITY_LABELS = {
        low:      { label: 'Low',      color: 'bg-slate-100 text-slate-600' },
        medium:   { label: 'Medium',   color: 'bg-[#F5EAE0] text-[#6B3F2A]' },
        high:     { label: 'High',     color: 'bg-amber-100 text-amber-700' },
        critical: { label: 'Critical', color: 'bg-red-100 text-red-700' },
    };

    function dueDateBadge(dueDate) {
        if (!dueDate) return '';
        const isOverdue = dueDate < new Date().toISOString().slice(0, 10);
        return `<span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${isOverdue ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500'}">${isOverdue ? '⚠ ' : ''}Due ${dueDate}</span>`;
    }

    function achvBadge(score) {
        if (score >= 90) return { label: 'Excellent', color: 'bg-emerald-100 text-emerald-700', bar: 'from-emerald-400 to-green-500', ring: '#10B981' };
        if (score >= 75) return { label: 'Good',      color: 'bg-[#F5EAE0] text-[#6B3F2A]',     bar: 'from-[#8B5E4A] to-[#6B3F2A]', ring: '#6B3F2A' };
        if (score >= 50) return { label: 'Watch',     color: 'bg-yellow-100 text-yellow-700',   bar: 'from-yellow-400 to-amber-500', ring: '#F59E0B' };
        return              { label: 'Critical', color: 'bg-red-100 text-red-700',       bar: 'from-red-400 to-rose-500', ring: '#EF4444' };
    }

    // A circular "gambaran" (visual) of a KPI's achievement score, used on
    // each KPI card instead of just a number.
    function progressRing(scoreRaw) {
        const badge = achvBadge(scoreRaw);
        const score = Math.max(0, Math.min(100, scoreRaw));
        const r = 24, c = 2 * Math.PI * r;
        const offset = c - (score / 100) * c;
        return `
            <svg width="60" height="60" viewBox="0 0 60 60" class="shrink-0">
                <circle cx="30" cy="30" r="${r}" fill="none" stroke="#EFE3C7" stroke-width="6"/>
                <circle cx="30" cy="30" r="${r}" fill="none" stroke="${badge.ring}" stroke-width="6"
                    stroke-linecap="round" stroke-dasharray="${c}" stroke-dashoffset="${offset}"
                    transform="rotate(-90 30 30)"/>
                <text x="30" y="35" text-anchor="middle" font-size="13" font-weight="900" fill="#1e293b">${Math.round(scoreRaw)}%</text>
            </svg>
        `;
    }

    function setTopbar(title, showBack) {
        document.getElementById('topbarTitle').textContent = title;
        document.getElementById('backBtn').classList.toggle('hidden', !showBack);
    }

    function goHome() {
        renderThingsToDo();
    }

    function card(inner, extraClasses = '') {
        return `<div class="bg-[#FFFCF4] rounded-2xl soft-card border-2 border-[#D9C4A0] p-4 ${extraClasses}">${inner}</div>`;
    }

    /* ---------------------------------------------------------------- */
    /* BOOT                                                              */
    /* ---------------------------------------------------------------- */

    async function boot() {
        let status;
        try {
            status = await api('/link/status');
        } catch (e) {
            if (e.status === 401) {
                renderNotInTelegram();
            } else {
                renderError('Something went wrong. Pull to refresh and try again.');
            }
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
        applyTheme();
        renderThingsToDo();
    }

    // Mirrors the employee's Account Settings > Appearance colours here too —
    // this WebView has no Laravel session, so it can't just read the same CSS
    // vars the main site sets; it fetches them once over the Telegram-verified
    // API instead. Falls back silently to the Mini App's existing brown/cream
    // look if the fetch fails or nothing was ever customised.
    let _themeApplied = false;
    async function applyTheme() {
        if (_themeApplied) return;
        _themeApplied = true;
        try {
            const t = await api('/theme?employee_id=' + encodeURIComponent(state.employeeId) + '&company_code=' + encodeURIComponent(state.companyCode));
            if (!t.theme_bg && !t.theme_accent) return;
            const bg = t.theme_bg || '#F5EEDC';
            const accent = t.theme_accent || '#6B3F2A';
            const style = document.createElement('style');
            style.id = 'tg-theme-override';
            style.textContent = `
                body { background-color: ${bg} !important; }
                #topbar { background: linear-gradient(135deg, ${accent}, color-mix(in srgb, ${accent} 65%, black)) !important; }
                [class*="bg-[#6B3F2A]"] { background-color: ${accent} !important; }
                [class*="text-[#6B3F2A]"] { color: color-mix(in srgb, ${accent} 85%, black) !important; }
                [class*="border-[#6B3F2A]"] { border-color: ${accent} !important; }
                [class*="bg-[#F5EAE0]"], [class*="bg-[#F5EAE0]"] { background-color: color-mix(in srgb, ${accent} 12%, white) !important; }
                [class*="from-[#8B5E4A]"] { --tw-gradient-from: ${accent} !important; }
                [class*="to-[#6B3F2A]"] { --tw-gradient-to: color-mix(in srgb, ${accent} 70%, black) !important; }
            `;
            document.head.appendChild(style);
        } catch (e) {
            // Telegram account not linked to this endpoint's context, or a
            // transient error — keep the Mini App's default look, no toast.
        }
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

    function renderNotInTelegram() {
        setTopbar('KPI Mini App', false);
        const botLine = BOT_USERNAME ? ` Open <b>@${BOT_USERNAME}</b> in Telegram and` : ' Open the bot in Telegram and';
        document.getElementById('app').innerHTML = card(`
            <p class="text-[13px] font-black text-slate-900 mb-1">Open this from Telegram</p>
            <p class="text-[12px] text-slate-500 leading-relaxed">
                This page only works when opened inside the Telegram app.${botLine}
                tap the KPI Mini App button there.
            </p>
        `);
    }

    function renderChooseDashboard(dashboards) {
        setTopbar('Choose Dashboard', false);
        const rows = dashboards.map(d => `
            <button onclick='pickDashboard(${JSON.stringify(d)})' class="w-full text-left tap-card">
                ${card(`
                    <p class="text-[13px] font-black text-slate-900">${d.company_display_name}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">${d.short_name} · <span class="uppercase font-semibold">${d.role || ''}</span></p>
                `, 'hover:border-[#6B9080]')}
            </button>
        `).join('');
        document.getElementById('app').innerHTML = `<div class="space-y-2">${rows}</div>`;
    }

    function pickDashboard(d) {
        selectDashboard(d, true);
    }

    async function confirmDisconnect() {
        const doDisconnect = () => api('/link/disconnect', { method: 'POST' }).then(() => tg?.close());
        if (tg?.showConfirm) {
            tg.showConfirm('Disconnect Telegram from your KPI account?', (ok) => { if (ok) doDisconnect(); });
        } else if (confirm('Disconnect Telegram from your KPI account?')) {
            doDisconnect();
        }
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

    async function renderMyKpis() {
        setTopbar('My KPIs', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>`;

        let data;
        try {
            data = await api(`/kpis/summary?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load your KPIs.');
            return;
        }

        if (!data.kpis.length) {
            app.innerHTML = card(`<p class="text-[13px] text-slate-600 text-center py-6">No KPIs found for this financial year.</p>`);
            return;
        }

        // Track each quarter's live actual so submitDelta can block a decrease
        // below 0 client-side, without waiting on a round trip.
        window.__quarterActuals = {};
        data.kpis.forEach(k => (k.quarters || []).forEach(q => { window.__quarterActuals[q.id] = q.actual; }));

        const sorted = sortByCategoryAndSub(data.kpis);

        let lastCategory = null;
        let html = '';

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
                            <span class="flex items-center gap-1 px-2 py-0.5 rounded-full ${sDef.color} text-[8px] font-black">
                                <span class="w-1.5 h-1.5 rounded-full ${sDef.dot}"></span>${sDef.label}
                            </span>
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

                <button onclick='renderKpiTaskHistory(${JSON.stringify(k.kpi_id)}, ${JSON.stringify(k.kpi_title)})' class="w-full mt-3 py-2 rounded-xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[10px] font-black">
                    📜 Tasks & History
                </button>
            `) + '<div class="h-2"></div>';
        });

        app.innerHTML = html;
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
            await api(`/kpis/${kpiId}/quarters/${quarterId}/adjust`, {
                method: 'POST',
                body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode, delta }),
            });
            if (tg?.HapticFeedback) tg.HapticFeedback.notificationOccurred('success');
            if (tg?.showPopup) tg.showPopup({ message: 'Updated! Your KPI actual has been refreshed.' });
            renderMyKpis();
        } catch (e) {
            feedback.textContent = e.data?.message || "Couldn't update — please try again.";
            feedback.className = 'text-[10px] font-bold mt-1.5 text-red-600';
            feedback.classList.remove('hidden');
        }
    }

    /* ---------------------------------------------------------------- */
    /* THINGS TO DO — this is the app's home screen. A reusable,        */
    /* persistent to-do system: create a project, add tasks under it,   */
    /* pick which KPI(s) it feeds (mandatory, last step of creation),   */
    /* then update its progress any time (Daily Update). Every task     */
    /* card here is shown grouped under the KPI it belongs to, so this  */
    /* reads like a task manager built around your KPIs, not a         */
    /* separate to-do list bolted on the side. Updating a task's        */
    /* actual only changes that task's own actual — it does NOT touch  */
    /* any linked KPI's official quarter_actual (that stays a          */
    /* deliberate, separate action on the "My KPIs" screen).            */
    /* ---------------------------------------------------------------- */

    function taskCard(t, primaryKpiId) {
        const pct = t.target > 0 ? Math.max(0, Math.min(100, (t.actual / t.target) * 100)) : 0;
        const badge = achvBadge(pct);
        const statusPill = TASK_STATUS_PILL[t.status] || TASK_STATUS_PILL.not_started;
        const priorityPill = PRIORITY_LABELS[t.priority] || PRIORITY_LABELS.medium;
        const extraKpis = (t.linked_kpis || []).filter(k => k.kpi_id !== primaryKpiId);
        const extraChips = extraKpis.length
            ? `<div class="flex flex-wrap gap-1.5 mt-2">${extraKpis.map(k => `<span class="px-2 py-0.5 rounded-full bg-[#CCE3DE] text-[#1a3d34] text-[8px] font-black">${k.kpi_title}</span>`).join('')}</div>`
            : '';

        return card(`
            <div class="flex items-center justify-between gap-2">
                <p class="text-[13px] font-black text-slate-900 leading-snug min-w-0">${t.title}</p>
                <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full shrink-0 ${statusPill.color}">${statusPill.label}</span>
            </div>
            <p class="text-[9px] text-slate-400">📁 ${t.project_name}</p>
            <div class="flex flex-wrap gap-1.5 mt-2">
                <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${priorityPill.color}">${priorityPill.label}</span>
                ${dueDateBadge(t.due_date)}
            </div>
            <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-2 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r ${badge.bar}" style="width:${pct}%"></div>
            </div>
            <div class="flex items-center justify-between mt-1.5">
                <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(t.target, t.unit)}</span></p>
                <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(t.actual, t.unit)}</span></p>
                <p class="text-[10px] font-black text-slate-700">${pct.toFixed(0)}%</p>
            </div>
            ${extraChips}
            <div class="flex items-center gap-2 mt-3">
                <button onclick="renderTaskDetail('${t.id}')" class="flex-1 py-2 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[11px] font-black">
                    Details
                </button>
                <button onclick="renderLinkTaskKpis('${t.id}')" class="flex-1 py-2 rounded-xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[11px] font-black">
                    Edit KPIs
                </button>
            </div>
        `);
    }

    /* ---------------------------------------------------------------- */
    /* TASK SCORE — this week's precomputed-on-demand score, with an AI   */
    /* summary the user can generate/refresh. Mirrors the web Mini App's  */
    /* Task Score card.                                                    */
    /* ---------------------------------------------------------------- */

    function scoreStatusBand(status) {
        if (status === 'on_track') return { label: 'On Track', color: 'bg-emerald-100 text-emerald-700' };
        if (status === 'at_risk') return { label: 'At Risk', color: 'bg-amber-100 text-amber-700' };
        if (status === 'critical') return { label: 'Critical', color: 'bg-red-100 text-red-700' };
        return { label: 'Not enough data yet', color: 'bg-slate-100 text-slate-500' };
    }

    async function loadTaskScoreCard() {
        const el = document.getElementById('taskScoreCard');
        if (!el) return;

        let score;
        try {
            score = await api(`/tasks/score?employee_id=${state.employeeId}&company_code=${state.companyCode}&period=weekly`);
        } catch (e) {
            return;
        }

        const band = scoreStatusBand(score.status);

        el.innerHTML = card(`
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">This Week's Task Score</p>
                    <p class="text-[24px] font-black text-slate-900 leading-none mt-1">${score.score !== null ? Math.round(score.score) : '—'}<span class="text-[12px] font-bold text-slate-400">/100</span></p>
                    <span class="inline-block mt-1.5 px-2 py-0.5 rounded-full ${band.color} text-[9px] font-black">${band.label}</span>
                </div>
                <button onclick="toggleTaskSummary()" class="text-[10px] font-black text-[#6B3F2A] bg-[#F5EAE0] px-3 py-1.5 rounded-full shrink-0">✨ AI Summary</button>
            </div>
            <div id="taskSummaryBox" class="hidden mt-3 pt-3 border-t border-slate-200"></div>
        `);
    }

    let __summaryLoaded = false;
    async function toggleTaskSummary() {
        const box = document.getElementById('taskSummaryBox');
        box.classList.toggle('hidden');
        if (box.classList.contains('hidden') || __summaryLoaded) return;

        box.innerHTML = `<p class="text-[11px] text-slate-400">Loading…</p>`;

        try {
            const data = await api(`/summaries?employee_id=${state.employeeId}&company_code=${state.companyCode}&scope=employee&period=weekly`);
            if (data.summary) {
                box.innerHTML = summaryBlock(data.summary);
            } else {
                box.innerHTML = `
                    <p class="text-[11px] text-slate-500">No summary generated yet for this week.</p>
                    <button onclick="generateTaskSummary()" class="mt-2 px-3 py-1.5 rounded-lg bg-[#6B3F2A] hover:bg-[#5a341f] text-white text-[10px] font-black">Generate now</button>
                `;
            }
            __summaryLoaded = true;
        } catch (e) {
            box.innerHTML = `<p class="text-[11px] text-red-500">Could not load a summary right now.</p>`;
        }
    }

    function summaryBlock(summary) {
        const recs = (summary.facts?.recommendations || []).map(r => `<li class="text-[10px] text-slate-600 mt-1">• ${r}</li>`).join('');
        return `
            <p class="text-[11px] text-slate-700 leading-relaxed">${summary.narrative}</p>
            ${recs ? `<ul class="mt-2">${recs}</ul>` : ''}
            <button onclick="generateTaskSummary()" class="mt-2 text-[10px] font-bold text-[#6B3F2A]">↻ Regenerate</button>
        `;
    }

    async function generateTaskSummary() {
        const box = document.getElementById('taskSummaryBox');
        box.innerHTML = `<p class="text-[11px] text-slate-400">Generating…</p>`;
        try {
            const data = await api('/summaries/regenerate', {
                method: 'POST',
                body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode, scope: 'employee', period: 'weekly' }),
            });
            box.innerHTML = summaryBlock(data.summary);
        } catch (e) {
            box.innerHTML = `<p class="text-[11px] text-red-500">${e.data?.message || "Couldn't generate a summary right now."}</p>`;
        }
    }

    async function renderThingsToDo() {
        setTopbar('Things To Do', false);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading your to-dos…</p>`;

        let data;
        try {
            data = await api(`/project-tasks?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load your to-dos.');
            return;
        }

        window.__myTasks = data.tasks || [];

        const header = `
            <div class="flex items-center gap-2">
                <button onclick="renderMyKpis()" class="flex-1 py-3 rounded-2xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[12px] font-black">
                    My KPIs
                </button>
                <button onclick="renderPerformanceReview('weekly')" class="flex-1 py-3 rounded-2xl bg-white border-2 border-slate-300 text-slate-700 text-[12px] font-black">
                    Performance Review
                </button>
            </div>
            <button onclick="renderNewTaskPickProject()" class="w-full mt-2 py-3 rounded-2xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[12px] font-black shadow-[0_6px_16px_rgba(22,163,74,.35)]">
                ➕ New Task
            </button>
            <div id="taskScoreCard" class="mt-3"></div>
        `;

        loadTaskScoreCard();

        if (!window.__myTasks.length) {
            app.innerHTML = header + `<div class="mt-3">${card(`<p class="text-[13px] text-slate-600 text-center py-6">No to-dos yet. Every task you create is tied to a KPI — tap "New Task" to start tracking daily progress.</p>`)}</div>`;
            return;
        }

        // Group by each task's primary (first) linked KPI. Tasks saved before
        // linking was mandatory may have none yet — those land in a "Needs a
        // KPI" bucket up top instead of being silently dropped.
        const groups = [];
        const seen = {};
        window.__myTasks.forEach(t => {
            const primary = (t.linked_kpis || [])[0] || null;
            const key = primary ? primary.kpi_id : '__needs_kpi__';
            if (!seen[key]) {
                seen[key] = {
                    kpi_id: primary ? primary.kpi_id : null,
                    kpi_title: primary ? primary.kpi_title : 'Needs a KPI',
                    category: primary ? primary.category : null,
                    tasks: [],
                };
                groups.push(seen[key]);
            }
            seen[key].tasks.push(t);
        });

        const needsKpiGroup = groups.find(g => g.kpi_id === null);
        const kpiGroups = groups.filter(g => g.kpi_id !== null).sort((a, b) => {
            const ai = CATEGORY_ORDER.indexOf(a.category); const bi = CATEGORY_ORDER.indexOf(b.category);
            const catDiff = (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
            if (catDiff !== 0) return catDiff;
            return a.kpi_title.localeCompare(b.kpi_title);
        });

        let html = header;

        if (needsKpiGroup) {
            html += `
                <div class="flex items-center gap-2 mt-4 mb-1 px-1">
                    <span class="text-[15px]">⚠️</span>
                    <p class="text-[11px] font-black uppercase tracking-wide text-red-600">Needs a KPI</p>
                </div>
            `;
            needsKpiGroup.tasks.forEach(t => { html += taskCard(t, null) + '<div class="h-2"></div>'; });
        }

        let lastCategory = null;
        kpiGroups.forEach(group => {
            if (group.category !== lastCategory) {
                const cat = CATEGORY_COLORS[group.category] || DEFAULT_CATEGORY_COLOR;
                html += `
                    <div class="flex items-center gap-2 mt-4 mb-1 px-1">
                        <p class="text-[11px] font-black uppercase tracking-wide text-[#6B3F2A]">${group.category || 'Other'}</p>
                    </div>
                `;
                lastCategory = group.category;
            }

            html += `
                <button onclick='renderKpiTaskHistory(${JSON.stringify(group.kpi_id)}, ${JSON.stringify(group.kpi_title)})' class="w-full text-left px-1 mb-1.5">
                    <p class="text-[12px] font-black text-slate-700">${group.kpi_title} <span class="text-slate-300 font-bold">›</span></p>
                </button>
            `;

            group.tasks.forEach(t => { html += taskCard(t, group.kpi_id) + '<div class="h-2"></div>'; });
        });

        html += `
            <div class="pt-2 pb-6 text-center">
                <button onclick="confirmDisconnect()" class="text-[11px] text-slate-400 underline">Disconnect Telegram</button>
            </div>
        `;

        app.innerHTML = html;
    }

    async function renderNewTaskPickProject() {
        setTopbar('New Task — Project', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading projects…</p>`;

        let data;
        try {
            data = await api(`/projects?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load your projects.');
            return;
        }

        window.__myProjects = data.projects || [];

        const newProjectBox = `
            <div class="mb-3">
                ${card(`
                    <p class="text-[10px] font-bold text-slate-600 mb-1">Create a new project</p>
                    <div class="flex items-center gap-2">
                        <input type="text" id="newProjectName" placeholder="e.g. Ramadan Campaign"
                            class="flex-1 min-w-0 text-[12px] px-3 py-2 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                        <button onclick="createProjectQuick()" class="px-4 py-2 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[11px] font-black shrink-0">
                            Create
                        </button>
                    </div>
                    <p id="newProjectFeedback" class="hidden text-[10px] font-bold text-red-600 mt-1.5"></p>
                `)}
            </div>
        `;

        if (!window.__myProjects.length) {
            app.innerHTML = newProjectBox + card(`<p class="text-[12px] text-slate-500 text-center py-4">No existing projects yet — create one above.</p>`);
            return;
        }

        const rows = window.__myProjects.map(p => `
            <button onclick='renderNewTaskForm(${JSON.stringify(p.id)}, ${JSON.stringify(p.name)})' class="w-full text-left tap-card">
                ${card(`
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[13px] font-black text-slate-900">📁 ${p.name}</p>
                        <span class="text-slate-300 shrink-0">›</span>
                    </div>
                `, 'hover:border-red-400')}
            </button>
        `).join('<div class="h-1.5"></div>');

        app.innerHTML = newProjectBox + `<p class="text-[9px] uppercase tracking-wide text-slate-400 font-black mb-1.5 px-1">Or pick an existing project</p><div>${rows}</div>`;
    }

    async function createProjectQuick() {
        const input = document.getElementById('newProjectName');
        const feedback = document.getElementById('newProjectFeedback');
        const name = input.value.trim();

        if (!name) {
            feedback.textContent = 'Enter a project name first.';
            feedback.classList.remove('hidden');
            return;
        }

        try {
            const data = await api('/projects', {
                method: 'POST',
                body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode, name }),
            });
            renderNewTaskForm(data.project.id, data.project.name);
        } catch (e) {
            feedback.textContent = e.data?.message || "Couldn't create — please try again.";
            feedback.classList.remove('hidden');
        }
    }

    function renderNewTaskForm(projectId, projectName) {
        setTopbar('New Task', true);
        document.getElementById('app').innerHTML = card(`
            <p class="text-[10px] font-bold text-slate-400">📁 ${projectName}</p>
            <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Task title</p>
            <input type="text" id="taskTitleInput" placeholder="e.g. Call 5 new leads"
                class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">

            <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Description <span class="text-slate-400 font-normal">(optional)</span></p>
            <textarea id="taskDescriptionInput" rows="2" placeholder="Any extra context…"
                class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500 resize-none"></textarea>

            <div class="grid grid-cols-2 gap-2 mt-3">
                <div>
                    <p class="text-[10px] font-bold text-slate-600 mb-1">Priority</p>
                    <select id="taskPriorityInput" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                        ${Object.entries(PRIORITY_LABELS).map(([key, p]) => `<option value="${key}" ${key === 'medium' ? 'selected' : ''}>${p.label}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-600 mb-1">Due date <span class="text-slate-400 font-normal">(optional)</span></p>
                    <input type="date" id="taskDueDateInput" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                </div>
            </div>

            <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Unit</p>
            <select id="taskUnitInput" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                <option value="number">Number</option>
                <option value="currency">Currency (RM)</option>
                <option value="percentage">Percentage (%)</option>
            </select>

            <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Target</p>
            <input type="number" step="any" min="0" id="taskTargetInput" placeholder="e.g. 50"
                class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">

            <button onclick='goToPickKpis(${JSON.stringify(projectId)}, ${JSON.stringify(projectName)})' class="w-full mt-4 py-3 rounded-2xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[13px] font-black shadow-[0_6px_16px_rgba(22,163,74,.35)]">
                Next: Pick a KPI →
            </button>
            <p id="newTaskFeedback" class="hidden text-[10px] font-bold text-red-600 mt-2 text-center"></p>
        `);
    }

    function goToPickKpis(projectId, projectName) {
        const feedback = document.getElementById('newTaskFeedback');
        const title = document.getElementById('taskTitleInput').value.trim();
        const description = document.getElementById('taskDescriptionInput').value.trim() || null;
        const priority = document.getElementById('taskPriorityInput').value;
        const dueDate = document.getElementById('taskDueDateInput').value || null;
        const unit = document.getElementById('taskUnitInput').value;
        const target = document.getElementById('taskTargetInput').value;

        if (!title || target === '' || isNaN(Number(target)) || Number(target) < 0) {
            feedback.textContent = 'Enter a task title and a valid target.';
            feedback.classList.remove('hidden');
            return;
        }

        renderNewTaskPickKpis(projectId, projectName, title, unit, Number(target), description, priority, dueDate);
    }

    // Last step of creating a task — picking the KPI(s) it belongs to is
    // mandatory, so a task can never exist without at least one.
    async function renderNewTaskPickKpis(projectId, projectName, title, unit, target, description, priority, dueDate) {
        setTopbar('New Task — Pick KPI(s)', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading matching KPIs…</p>`;

        let data;
        try {
            data = await api(`/project-tasks/kpi-options?employee_id=${state.employeeId}&company_code=${state.companyCode}&unit=${unit}`);
        } catch (e) {
            renderError('Could not load your KPIs.');
            return;
        }

        if (!data.kpis.length) {
            app.innerHTML = card(`<p class="text-[13px] text-slate-600 text-center py-6">No open KPIs with a matching "${unit}" unit right now. Every task needs a KPI — try a different unit, or check back once a matching quarter is open.</p>`);
            return;
        }

        const sortedKpis = sortByCategoryAndSub(data.kpis);
        const rows = sortedKpis.map(k => {
            const cat = CATEGORY_COLORS[k.category] || DEFAULT_CATEGORY_COLOR;
            return `
                <label class="block cursor-pointer">
                    ${card(`
                        <div class="flex items-center gap-3">
                            <input type="checkbox" value="${k.kpi_id}" class="kpi-link-checkbox w-5 h-5 accent-[#16A34A] shrink-0">
                            <div class="min-w-0">
                                <span class="px-2 py-0.5 rounded-full ${cat.catPill} text-[8px] font-black">${k.category || '-'}</span>
                                <p class="text-[13px] font-black text-slate-900 mt-1">${k.kpi_title}</p>
                            </div>
                        </div>
                    `)}
                </label>
            `;
        }).join('<div class="h-1.5"></div>');

        app.innerHTML = `
            <p class="text-[10px] font-bold text-slate-400">📁 ${projectName}</p>
            <p class="text-[14px] font-black text-slate-900 mt-1 mb-3">${title}</p>
            <p class="text-[11px] text-slate-500 mb-2 px-1">Last step — tick at least one KPI this task feeds into.</p>
            <div>${rows}</div>
            <button onclick='saveNewTask(${JSON.stringify(projectId)}, ${JSON.stringify(title)}, ${JSON.stringify(unit)}, ${target}, ${JSON.stringify(description)}, ${JSON.stringify(priority)}, ${JSON.stringify(dueDate)})' class="w-full mt-4 py-3 rounded-2xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[13px] font-black shadow-[0_6px_16px_rgba(22,163,74,.35)]">
                Save Task
            </button>
            <p id="newTaskFeedback" class="hidden text-[10px] font-bold text-red-600 mt-2 text-center"></p>
        `;
    }

    async function saveNewTask(projectId, title, unit, target, description, priority, dueDate) {
        const feedback = document.getElementById('newTaskFeedback');
        const kpiIds = [...document.querySelectorAll('.kpi-link-checkbox:checked')].map(el => el.value);

        if (kpiIds.length === 0) {
            feedback.textContent = 'Tick at least one KPI — every task needs one.';
            feedback.classList.remove('hidden');
            return;
        }

        try {
            await api('/project-tasks', {
                method: 'POST',
                body: JSON.stringify({
                    employee_id: state.employeeId, company_code: state.companyCode,
                    project_id: projectId, title, unit, target: Number(target), kpi_ids: kpiIds,
                    description, priority, due_date: dueDate,
                }),
            });
            if (tg?.showPopup) tg.showPopup({ message: 'Task saved!' });
            renderThingsToDo();
        } catch (e) {
            feedback.textContent = e.data?.message || "Couldn't save — please try again.";
            feedback.classList.remove('hidden');
        }
    }

    /* ---------------------------------------------------------------- */
    /* TASK DETAILS — quick numeric update, the daily update (status/    */
    /* progress/blocked-note/reschedule), KPI alignment with an optional */
    /* AI suggestion, and the full update history. Mirrors the web Mini  */
    /* App's Task Details screen.                                        */
    /* ---------------------------------------------------------------- */

    function updateHistoryRow(u) {
        const when = (u.created_at || '').replace('T', ' ').slice(0, 16);
        const parts = [];
        if (u.status_at_update) parts.push(`marked <b>${(TASK_STATUS_PILL[u.status_at_update] || {}).label || u.status_at_update}</b>`);
        if (u.progress_at_update !== null && u.progress_at_update !== undefined) parts.push(`${u.progress_at_update}% progress`);
        if (Number(u.delta) !== 0) parts.push(`${u.delta >= 0 ? '+' : ''}${u.delta} added (now ${u.new_actual})`);
        if (u.note) parts.push(`note: "${u.note}"`);
        if (u.reschedule_reason) parts.push(`rescheduled: "${u.reschedule_reason}"`);

        return `
            <div class="py-2 border-b border-slate-100 last:border-0">
                <p class="text-[11px] text-slate-600 leading-relaxed">${parts.join(' · ') || 'Logged an update'}</p>
                <p class="text-[9px] text-slate-400 mt-0.5">${when}</p>
            </div>
        `;
    }

    async function renderTaskDetail(taskId) {
        setTopbar('Task Details', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>`;

        let data;
        try {
            data = await api(`/project-tasks/${taskId}?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load this task.');
            return;
        }

        window.__taskDetail = data.task;
        window.__taskUpdates = data.updates || [];

        const t = data.task;
        const pct = t.target > 0 ? Math.max(0, Math.min(100, (t.actual / t.target) * 100)) : 0;
        const badge = achvBadge(pct);
        const statusPill = TASK_STATUS_PILL[t.status] || TASK_STATUS_PILL.not_started;
        const priorityPill = PRIORITY_LABELS[t.priority] || PRIORITY_LABELS.medium;

        const kpiChips = (t.linked_kpis || []).length
            ? t.linked_kpis.map(k => `
                <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl bg-[#CCE3DE] mt-1.5">
                    <p class="text-[11px] font-black text-[#1a3d34] min-w-0">${k.kpi_title}${k.ai_suggested ? ' 🤖' : ''}</p>
                </div>
            `).join('')
            : `<p class="text-[11px] text-slate-400 mt-1.5">Not linked to a KPI yet.</p>`;

        app.innerHTML = `
            ${card(`
                <p class="text-[14px] font-black text-slate-900 leading-snug">${t.title}</p>
                ${t.description ? `<p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">${t.description}</p>` : ''}
                <div class="flex flex-wrap gap-1.5 mt-2">
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${statusPill.color}">${statusPill.label}</span>
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${priorityPill.color}">${priorityPill.label} priority</span>
                    ${dueDateBadge(t.due_date)}
                </div>
                <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-3 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r ${badge.bar}" style="width:${pct}%"></div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(t.target, t.unit)}</span></p>
                    <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(t.actual, t.unit)}</span></p>
                    <p class="text-[10px] font-black text-slate-700">${pct.toFixed(0)}%</p>
                </div>
            `)}

            <div class="h-2"></div>
            ${card(`
                <p class="text-[12px] font-black text-slate-900 mb-2">Quick number update</p>
                <div class="flex items-center gap-2">
                    <input type="number" step="any" placeholder="e.g. 50 or -10" id="taskDeltaInput"
                        class="flex-1 min-w-0 text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                    <button onclick="submitTaskProgress('${t.id}')" class="px-5 py-2.5 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[12px] font-black shrink-0">Add</button>
                </div>
                <p class="text-[9px] text-slate-400 mt-1">Use a minus sign to reduce. Adjusts the number only — not status.</p>
                <p id="taskProgressFeedback" class="hidden text-[10px] font-bold mt-2"></p>
            `)}

            <div class="h-2"></div>
            ${card(`
                <p class="text-[12px] font-black text-slate-900 mb-2">Daily update</p>
                <p class="text-[10px] font-bold text-slate-600 mb-1">Status</p>
                <select id="dailyStatusInput" onchange="toggleBlockedNote()" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                    ${Object.entries(TASK_STATUS_PILL).map(([key, s]) => `<option value="${key}" ${t.status === key ? 'selected' : ''}>${s.label}</option>`).join('')}
                </select>

                <p class="text-[10px] font-bold text-slate-600 mt-3 mb-1">Progress (%)</p>
                <input type="number" min="0" max="100" id="dailyProgressInput" value="${t.progress_percentage ?? 0}"
                    class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">

                <div id="blockedNoteWrap" class="${t.status === 'blocked' ? '' : 'hidden'} mt-3">
                    <p class="text-[10px] font-bold text-slate-600 mb-1">What's blocking it? <span class="text-red-500">*required</span></p>
                    <textarea id="dailyNoteInput" rows="2" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500 resize-none"></textarea>
                </div>

                <details class="mt-3">
                    <summary class="text-[11px] font-bold text-[#6B3F2A]">Reschedule (optional)</summary>
                    <div class="mt-2">
                        <input type="date" id="rescheduleToInput" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                        <textarea id="rescheduleReasonInput" rows="2" placeholder="Why the new date?" class="w-full text-[13px] px-3 py-2.5 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500 resize-none mt-2"></textarea>
                    </div>
                </details>

                <button onclick="submitDailyUpdate('${t.id}')" class="w-full mt-3 py-2.5 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[12px] font-black">Save Daily Update</button>
                <p id="dailyUpdateFeedback" class="hidden text-[10px] font-bold mt-2"></p>
            `)}

            <div class="h-2"></div>
            ${card(`
                <div class="flex items-center justify-between">
                    <p class="text-[12px] font-black text-slate-900">KPI alignment</p>
                    <button onclick="requestKpiSuggestion('${t.id}')" class="text-[10px] font-black text-[#6B3F2A] bg-[#F5EAE0] px-2.5 py-1 rounded-full">🤖 Suggest with AI</button>
                </div>
                ${kpiChips}
                <p id="kpiSuggestionBox" class="hidden mt-2"></p>
                <button onclick="renderLinkTaskKpis('${t.id}')" class="w-full mt-2 py-2 rounded-xl bg-white border-2 border-[#D9C4A0] text-[#6B3F2A] text-[11px] font-black">Edit KPI Links</button>
            `)}

            <div class="h-2"></div>
            ${card(`
                <p class="text-[12px] font-black text-slate-900 mb-1">History</p>
                <div>${window.__taskUpdates.length ? window.__taskUpdates.map(updateHistoryRow).join('') : '<p class="text-[11px] text-slate-400 py-2">No updates logged yet.</p>'}</div>
            `)}
        `;
    }

    function toggleBlockedNote() {
        const status = document.getElementById('dailyStatusInput').value;
        document.getElementById('blockedNoteWrap').classList.toggle('hidden', status !== 'blocked');
    }

    async function submitTaskProgress(taskId) {
        const t = window.__taskDetail;
        const input = document.getElementById('taskDeltaInput');
        const feedback = document.getElementById('taskProgressFeedback');
        const raw = input.value.trim();

        if (raw === '' || isNaN(Number(raw)) || Number(raw) === 0) {
            showToast('Enter an amount first, e.g. 50 or -10.');
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
            await api(`/project-tasks/${taskId}/progress`, {
                method: 'POST',
                body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode, delta }),
            });
            if (tg?.HapticFeedback) tg.HapticFeedback.notificationOccurred('success');
            if (tg?.showPopup) tg.showPopup({ message: 'Task updated!' });
            renderTaskDetail(taskId);
        } catch (e) {
            feedback.textContent = e.data?.message || "Couldn't update — please try again.";
            feedback.className = 'text-[10px] font-bold mt-2 text-red-600';
            feedback.classList.remove('hidden');
        }
    }

    async function submitDailyUpdate(taskId) {
        const feedback = document.getElementById('dailyUpdateFeedback');
        const status = document.getElementById('dailyStatusInput').value;
        const progress = document.getElementById('dailyProgressInput').value;
        const note = document.getElementById('dailyNoteInput')?.value.trim() || null;
        const rescheduleTo = document.getElementById('rescheduleToInput').value || null;
        const rescheduleReason = document.getElementById('rescheduleReasonInput').value.trim() || null;

        if (status === 'blocked' && !note) {
            feedback.textContent = "Tell us what's blocking this task.";
            feedback.classList.remove('hidden');
            return;
        }

        feedback.classList.add('hidden');

        try {
            await api(`/project-tasks/${taskId}/daily-update`, {
                method: 'POST',
                body: JSON.stringify({
                    employee_id: state.employeeId, company_code: state.companyCode,
                    status, progress: progress === '' ? null : Number(progress), note,
                    reschedule_to: rescheduleTo, reschedule_reason: rescheduleReason,
                }),
            });
            if (tg?.HapticFeedback) tg.HapticFeedback.notificationOccurred('success');
            if (tg?.showPopup) tg.showPopup({ message: 'Daily update saved!' });
            renderTaskDetail(taskId);
        } catch (e) {
            feedback.textContent = e.data?.message || "Couldn't save — please try again.";
            feedback.classList.remove('hidden');
        }
    }

    async function requestKpiSuggestion(taskId) {
        const box = document.getElementById('kpiSuggestionBox');
        box.classList.remove('hidden');
        box.innerHTML = `<span class="text-[10px] text-slate-400">Thinking…</span>`;

        try {
            const data = await api(`/project-tasks/${taskId}/kpi-suggestion`, {
                method: 'POST',
                body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode }),
            });
            if (!data.suggestion) {
                box.innerHTML = `<span class="text-[10px] text-slate-500">No confident match found among your KPIs.</span>`;
                return;
            }
            const s = data.suggestion;
            box.innerHTML = `
                <div class="px-3 py-2 rounded-xl bg-amber-50 border border-amber-200">
                    <p class="text-[11px] font-black text-amber-800">🤖 ${s.confidence}% confident</p>
                    <p class="text-[11px] text-amber-700 mt-0.5">${s.reason}</p>
                    <button onclick='applyKpiSuggestion(${JSON.stringify(taskId)}, ${JSON.stringify(s)})' class="mt-2 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-black">Link this KPI</button>
                </div>
            `;
        } catch (e) {
            box.innerHTML = `<span class="text-[10px] text-red-500">${e.data?.message || "Couldn't get a suggestion."}</span>`;
        }
    }

    async function applyKpiSuggestion(taskId, suggestion) {
        const existingIds = (window.__taskDetail.linked_kpis || []).map(k => k.kpi_id);
        try {
            await api(`/project-tasks/${taskId}/link-kpis`, {
                method: 'POST',
                body: JSON.stringify({
                    employee_id: state.employeeId, company_code: state.companyCode,
                    kpi_ids: [...existingIds, suggestion.kpi_id],
                    ai_suggested: true, ai_confidence: suggestion.confidence, ai_reason: suggestion.reason,
                }),
            });
            if (tg?.showPopup) tg.showPopup({ message: 'KPI linked!' });
            renderTaskDetail(taskId);
        } catch (e) {
            showToast(e.data?.message || "Couldn't link — please try again.");
        }
    }

    async function renderLinkTaskKpis(taskId) {
        const t = (window.__taskDetail && window.__taskDetail.id === taskId) ? window.__taskDetail : (window.__myTasks || []).find(x => x.id === taskId);
        if (!t) { renderThingsToDo(); return; }

        setTopbar('Edit KPI Links', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading KPIs…</p>`;

        let data;
        try {
            data = await api(`/project-tasks/kpi-options?employee_id=${state.employeeId}&company_code=${state.companyCode}&unit=${t.unit}`);
        } catch (e) {
            renderError('Could not load your KPIs.');
            return;
        }

        if (!data.kpis.length) {
            app.innerHTML = card(`<p class="text-[13px] text-slate-600 text-center py-6">No open KPIs with a matching "${t.unit}" unit right now.</p>`);
            return;
        }

        const linkedIds = new Set((t.linked_kpis || []).map(k => k.kpi_id));
        const sortedKpis = sortByCategoryAndSub(data.kpis);

        const rows = sortedKpis.map(k => {
            const cat = CATEGORY_COLORS[k.category] || DEFAULT_CATEGORY_COLOR;
            const checked = linkedIds.has(k.kpi_id) ? 'checked' : '';
            return `
                <label class="block cursor-pointer">
                    ${card(`
                        <div class="flex items-center gap-3">
                            <input type="checkbox" value="${k.kpi_id}" class="kpi-link-checkbox w-5 h-5 accent-[#16A34A] shrink-0" ${checked}>
                            <div class="min-w-0">
                                <span class="px-2 py-0.5 rounded-full ${cat.catPill} text-[8px] font-black">${k.category || '-'}</span>
                                <p class="text-[13px] font-black text-slate-900 mt-1">${k.kpi_title}</p>
                            </div>
                        </div>
                    `)}
                </label>
            `;
        }).join('<div class="h-1.5"></div>');

        app.innerHTML = `
            <p class="text-[11px] text-slate-500 mb-2 px-1">Task "<b>${t.title}</b>" (${t.unit}) — tick which KPI(s) to track this under (at least one required). Doesn't change either KPI's actual — this is for visibility in that KPI's Task History.</p>
            <div>${rows}</div>
            <button onclick="saveLinkKpis('${taskId}')" class="w-full mt-4 py-3 rounded-2xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[13px] font-black shadow-[0_6px_16px_rgba(22,163,74,.35)]">
                Save Links
            </button>
            <p id="linkKpisFeedback" class="hidden text-[10px] font-bold text-red-600 mt-2 text-center"></p>
        `;
    }

    async function saveLinkKpis(taskId) {
        const feedback = document.getElementById('linkKpisFeedback');
        const kpiIds = [...document.querySelectorAll('.kpi-link-checkbox:checked')].map(el => el.value);

        if (kpiIds.length === 0) {
            feedback.textContent = 'Every task needs at least one KPI — tick at least one.';
            feedback.classList.remove('hidden');
            return;
        }

        try {
            await api(`/project-tasks/${taskId}/link-kpis`, {
                method: 'POST',
                body: JSON.stringify({ employee_id: state.employeeId, company_code: state.companyCode, kpi_ids: kpiIds }),
            });
            if (tg?.showPopup) tg.showPopup({ message: 'Linked!' });
            renderThingsToDo();
        } catch (e) {
            feedback.textContent = e.data?.message || "Couldn't save — please try again.";
            feedback.classList.remove('hidden');
        }
    }

    async function renderKpiTaskHistory(kpiId, kpiTitle) {
        setTopbar('Tasks & History', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>`;

        let data;
        try {
            data = await api(`/kpis/${kpiId}/task-history?employee_id=${state.employeeId}&company_code=${state.companyCode}`);
        } catch (e) {
            renderError('Could not load task history.');
            return;
        }

        if (!data.tasks.length) {
            app.innerHTML = card(`
                <p class="text-[13px] font-black text-slate-900 mb-1">${kpiTitle}</p>
                <p class="text-[12px] text-slate-500 leading-relaxed mt-2">No tasks linked to this KPI yet. Go to My Tasks → Link to KPI to track one here.</p>
            `);
            return;
        }

        const taskCards = data.tasks.map(t => {
            const pct = t.target > 0 ? Math.max(0, Math.min(100, (t.actual / t.target) * 100)) : 0;
            const badge = achvBadge(pct);
            const historyRows = t.updates.length ? t.updates.map(u => `
                <div class="flex items-center justify-between py-1.5 border-b border-[#EFE3C7] last:border-0">
                    <p class="text-[10px] text-slate-500">${formatDateTime(u.created_at)}</p>
                    <p class="text-[10px] font-black ${u.delta >= 0 ? 'text-emerald-600' : 'text-red-600'}">${u.delta >= 0 ? '+' : ''}${formatUnit(u.delta, t.unit)}</p>
                    <p class="text-[10px] text-slate-400">→ ${formatUnit(u.new_actual, t.unit)}</p>
                </div>
            `).join('') : `<p class="text-[10px] text-slate-400 py-2">No updates logged yet.</p>`;

            return card(`
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[13px] font-black text-slate-900 leading-snug min-w-0">${t.title}</p>
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full shrink-0 ${t.status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                        ${t.status === 'done' ? 'Done' : 'In Progress'}
                    </span>
                </div>
                <p class="text-[10px] text-slate-400">📁 ${t.project_name}</p>
                <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-2 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r ${badge.bar}" style="width:${pct}%"></div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <p class="text-[10px] text-slate-500">Target: <span class="font-bold text-slate-700">${formatUnit(t.target, t.unit)}</span></p>
                    <p class="text-[10px] text-slate-500">Actual: <span class="font-bold text-slate-700">${formatUnit(t.actual, t.unit)}</span></p>
                    <p class="text-[10px] font-black text-slate-700">${pct.toFixed(0)}%</p>
                </div>
                <div class="mt-3 pt-3 border-t-2 border-dashed border-[#E3D2B0]">
                    <p class="text-[9px] uppercase tracking-wide text-slate-400 font-black mb-1">Update History</p>
                    ${historyRows}
                </div>
            `) + '<div class="h-2"></div>';
        }).join('');

        app.innerHTML = `<p class="text-[11px] text-slate-500 mb-2 px-1">Tasks tracked under "<b>${data.kpi_title}</b>"</p>` + taskCards;
    }

    /* ---------------------------------------------------------------- */
    /* PERFORMANCE REVIEW — AI-generated weekly/monthly/quarterly score  */
    /* and narrative, grounded in task activity + KPI standing. Reviews  */
    /* are generated on a schedule (see TelegramReviewService), not on   */
    /* demand — this screen only displays what already exists. Kept      */
    /* deliberately plain and text-led: no emoji, muted slate tones,     */
    /* since this reads as a formal record rather than a daily tool.     */
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

    function reviewHistorySection(history) {
        if (!history.length) return '';
        const rows = history.map((r, i) => `
            <button onclick="renderReviewDetail(${i})" class="w-full text-left tap-card">
                ${card(`
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[12px] font-bold text-slate-700">${r.period_label}</p>
                        <p class="text-[12px] font-black text-slate-500">${Math.round(r.score)}/100</p>
                    </div>
                `, 'hover:border-slate-400')}
            </button>
        `).join('<div class="h-1.5"></div>');

        return `
            <p class="text-[10px] uppercase tracking-wide text-slate-400 font-bold mt-4 mb-1.5 px-1">Previous periods</p>
            <div>${rows}</div>
        `;
    }

    async function renderPerformanceReview(periodType) {
        periodType = periodType || 'weekly';
        setTopbar('Performance Review', true);
        const app = document.getElementById('app');
        app.innerHTML = `<p class="text-center text-slate-400 text-[12px] mt-10">Loading…</p>`;

        const tabs = `
            <div class="flex items-center gap-1 border-b-2 border-slate-200 mb-4">
                ${REVIEW_PERIODS.map(p => `
                    <button onclick="renderPerformanceReview('${p.key}')"
                        class="flex-1 pb-2.5 text-[12px] font-bold ${p.key === periodType ? 'text-slate-900 border-b-2 border-slate-900 -mb-[2px]' : 'text-slate-400'}">
                        ${p.label}
                    </button>
                `).join('')}
            </div>
        `;

        let data;
        try {
            data = await api(`/reviews?employee_id=${state.employeeId}&company_code=${state.companyCode}&period=${periodType}`);
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

        app.innerHTML = tabs + reviewCard(data.latest) + reviewHistorySection(window.__reviewHistory);
    }

    function renderReviewDetail(index) {
        const r = (window.__reviewHistory || [])[index];
        if (!r) { renderPerformanceReview('weekly'); return; }
        setTopbar('Performance Review', true);
        document.getElementById('app').innerHTML = reviewCard(r);
    }

    boot();
</script>

</body>
</html>
