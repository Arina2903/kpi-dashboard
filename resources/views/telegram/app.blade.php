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

    // Same category order/colors, status labels, and achievement bands used on
    // the web dashboard (resources/views/kpi/my-department-kpi.blade.php), so
    // the Mini App matches the system rather than inventing its own palette.
    const CATEGORY_ORDER = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];

    const CATEGORY_COLORS = {
        'Financial':         { catPill: 'bg-emerald-700 text-white', subPill: 'bg-emerald-100 text-emerald-700', icon: '💰' },
        'Growth & Customer': { catPill: 'bg-indigo-700 text-white',  subPill: 'bg-indigo-100 text-indigo-700',   icon: '📈' },
        'Initiatives':       { catPill: 'bg-amber-600 text-white',   subPill: 'bg-amber-100 text-amber-700',     icon: '🚀' },
        'People':            { catPill: 'bg-pink-700 text-white',    subPill: 'bg-pink-100 text-pink-700',       icon: '👥' },
    };
    const DEFAULT_CATEGORY_COLOR = { catPill: 'bg-slate-600 text-white', subPill: 'bg-slate-100 text-slate-600', icon: '📌' };

    const STATUS_LABELS = {
        completed:   { label: 'Completed',   color: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
        on_track:    { label: 'On Track',    color: 'bg-[#F5EAE0] text-[#6B3F2A]',     dot: 'bg-[#6B3F2A]' },
        at_risk:     { label: 'At Risk',     color: 'bg-yellow-100 text-yellow-700',   dot: 'bg-yellow-500' },
        in_trouble:  { label: 'In Trouble',  color: 'bg-red-100 text-red-700',         dot: 'bg-red-500' },
        not_started: { label: 'Not Started', color: 'bg-slate-100 text-slate-500',     dot: 'bg-slate-400' },
    };

    function achvBadge(score) {
        if (score >= 90) return { label: 'Excellent', color: 'bg-emerald-100 text-emerald-700', bar: 'from-emerald-400 to-green-500' };
        if (score >= 75) return { label: 'Good',      color: 'bg-[#F5EAE0] text-[#6B3F2A]',     bar: 'from-[#8B5E4A] to-[#6B3F2A]' };
        if (score >= 50) return { label: 'Watch',     color: 'bg-yellow-100 text-yellow-700',   bar: 'from-yellow-400 to-amber-500' };
        return              { label: 'Critical', color: 'bg-red-100 text-red-700',       bar: 'from-red-400 to-rose-500' };
    }

    function setTopbar(title, showBack) {
        document.getElementById('topbarTitle').textContent = title;
        document.getElementById('backBtn').classList.toggle('hidden', !showBack);
    }

    function goHome() {
        window.location.href = '/telegram/app';
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
        renderMyKpis();
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

        const updateControl = isCurrent ? `
            <div class="mt-2.5 flex items-center gap-2">
                <input type="number" step="any" placeholder="e.g. 50 or -10" id="delta-${kpiId}"
                    class="flex-1 min-w-0 text-[12px] px-3 py-2 rounded-xl border-2 border-[#D9C4A0] bg-white outline-none focus:border-red-500">
                <button onclick="submitDelta('${kpiId}','${q.id}')" class="px-4 py-2 rounded-xl bg-[#16A34A] hover:bg-[#15803D] text-white text-[11px] font-black shrink-0 shadow-[0_4px_12px_rgba(22,163,74,.4)]">
                    Update
                </button>
            </div>
            <p class="text-[9px] text-slate-400 mt-1">How much did today add? Use a minus sign to reduce.</p>
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
        setTopbar('My KPIs', false);
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

        // Same grouping order as the web dashboard's category sections.
        const sorted = [...data.kpis].sort((a, b) => {
            const ai = CATEGORY_ORDER.indexOf(a.category); const bi = CATEGORY_ORDER.indexOf(b.category);
            return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
        });

        let lastCategory = null;
        let html = '';

        sorted.forEach(k => {
            if (k.category !== lastCategory) {
                const cat = CATEGORY_COLORS[k.category] || DEFAULT_CATEGORY_COLOR;
                html += `
                    <div class="flex items-center gap-2 ${lastCategory ? 'mt-5' : ''} mb-1 px-1">
                        <span class="text-[15px]">${cat.icon}</span>
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
                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                    <span class="px-2 py-0.5 rounded-full ${cat.catPill} text-[8px] font-black">${cat.icon} ${k.category || '-'}</span>
                    ${k.sub_category ? `<span class="px-2 py-0.5 rounded-full ${cat.subPill} text-[8px] font-black">${k.sub_category}</span>` : ''}
                    <span class="flex items-center gap-1 px-2 py-0.5 rounded-full ${sDef.color} text-[8px] font-black">
                        <span class="w-1.5 h-1.5 rounded-full ${sDef.dot}"></span>${sDef.label}
                    </span>
                </div>

                <p class="text-[14px] font-black text-slate-900 leading-snug">${k.kpi_title}</p>

                <div class="flex items-center justify-between mt-2">
                    <span class="px-2 py-0.5 rounded-full ${aBadge.color} text-[9px] font-black">${aBadge.label}</span>
                    <p class="text-[18px] font-black text-slate-900">${k.achievement_percentage}%</p>
                </div>

                <div class="w-full h-1.5 bg-[#EFE3C7] rounded-full mt-2 overflow-hidden">
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

        html += `
            <div class="pt-2 pb-6 text-center">
                <button onclick="confirmDisconnect()" class="text-[11px] text-slate-400 underline">Disconnect Telegram</button>
            </div>
        `;

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
            if (tg?.showPopup) tg.showPopup({ message: 'Updated! Your KPI actual has been refreshed. ✅' });
            renderMyKpis();
        } catch (e) {
            feedback.textContent = e.data?.message || "Couldn't update — please try again.";
            feedback.className = 'text-[10px] font-bold mt-1.5 text-red-600';
            feedback.classList.remove('hidden');
        }
    }

    boot();
</script>

</body>
</html>
