@include('partials.ai-chat-widget')

@if(session('admin_impersonating'))
<div class="no-print" style="position:fixed;top:0;left:230px;right:0;z-index:9997;background:linear-gradient(90deg,#7c3aed,#a78bfa);color:#fff;padding:8px 24px;display:flex;align-items:center;justify-content:center;gap:12px;font-size:12px;font-weight:700;box-shadow:0 2px 12px rgba(124,58,237,.35);">
    <span>👁 Viewing as <strong>{{ session('full_name') ?? session('short_name') ?? session('employee_name') }}</strong> — BTS Admin session</span>
    <form method="POST" action="{{ route('admin.view-as.stop') }}" class="inline">
        @csrf
        <button type="submit" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);padding:4px 12px;border-radius:8px;font-size:11px;font-weight:800;cursor:pointer;">
            Return to my account
        </button>
    </form>
</div>
<div style="height:36px;"></div>
@endif

<style>
    /* Remove text-selection cursor from all non-interactive elements */
    * { cursor: default; }
    a, button, [role="button"], label, select,
    .cursor-pointer, [onclick],
    input[type="submit"], input[type="button"],
    input[type="reset"], input[type="checkbox"],
    input[type="radio"] { cursor: pointer !important; }
    input:not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="checkbox"]):not([type="radio"]),
    textarea { cursor: text !important; }

    #sidebar, #sidebar * {
        font-family: 'Inter', sans-serif;
    }

    #sidebar.collapsed .sidebar-tooltip {
        display: block;
    }

    #sidebar:not(.collapsed) .sidebar-tooltip {
        display: none;
    }

    #sidebar.collapsed nav {
        overflow: visible;
    }

    .custom-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scroll::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.18);
        border-radius: 999px;
    }
</style>

<aside
    id="sidebar"
    class="fixed left-0 top-0 z-40 h-screen bg-[#111111] text-white
    border-r border-white/10 shadow-[4px_0_24px_rgba(0,0,0,0.30)]
    w-[230px] min-w-[230px] max-w-[230px]
    px-3 py-4 flex flex-col overflow-visible shrink-0 transition-all duration-300"
>

    <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#D4AF37] via-[#D4AF37] to-[#D4AF37]/10"></div>

    <button
        id="sidebarCloseBtn"
        type="button"
        onclick="event.stopPropagation(); toggleSidebar();"
        class="absolute top-4 right-3 z-[9999] w-7 h-7 flex items-center justify-center
        text-[#A4C3B2] bg-white/10 border border-white/20 rounded-full
        hover:bg-white/20 hover:text-white transition text-sm"
        aria-label="Close Sidebar"
    >
        ×
    </button>

    <!-- COMPANY AREA -->
    <button
        type="button"
        onclick="handleSidebarHeaderClick()"
        class="group w-full flex items-center gap-3 mb-5 shrink-0 pr-10 text-left
        hover:bg-white/10 rounded-xl p-2 transition relative"
        aria-label="Open Sidebar"
    >
        <div class="w-10 h-10 flex items-center justify-center shrink-0">
            @php
                $sidebarLogo = session('company_logo');
                if (!$sidebarLogo) {
                    $logoMap = ['RCG'=>'images/RCG-Logo.png','RGHB'=>'images/RGHB-Logo.png','RCT'=>'images/RCT-Logo.png'];
                    $sidebarLogo = $logoMap[session('company_code')] ?? null;
                }
            @endphp
            @if($sidebarLogo)
            <img
                src="{{ asset(ltrim($sidebarLogo, '/')) }}"
                alt="{{ session('company_display_name') ?: 'Company' }}"
                class="w-9 h-9 object-contain sidebar-logo bg-transparent"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
            />
            <span class="sidebar-logo w-9 h-9 rounded-full bg-[#071a12] text-white font-bold text-base items-center justify-center" style="display:none">
                {{ strtoupper(substr(session('company_code') ?: 'R', 0, 1)) }}
            </span>
            @else
            <span class="sidebar-logo w-9 h-9 rounded-full bg-[#071a12] text-white font-bold text-base flex items-center justify-center">
                {{ strtoupper(substr(session('company_code') ?: 'R', 0, 1)) }}
            </span>
            @endif
            <span class="sidebar-icon-only hidden text-white font-bold text-lg">
                ☰
            </span>
        </div>

        <div class="sidebar-text leading-tight text-left min-w-0">
            <h1 class="text-[12px] font-bold tracking-wide text-white leading-tight break-words">
                {!! nl2br(e(session('company_display_name') ?: 'RICHWORKS KPI')) !!}
            </h1>

            <p class="text-[9px] text-[#D4AF37] uppercase tracking-[0.14em] mt-1 font-semibold">
                Performance System
            </p>
        </div>

        <div class="sidebar-tooltip hidden absolute left-[70px] top-1/2 -translate-y-1/2
            bg-black text-white text-[10px] px-2 py-1 rounded-md
            opacity-0 group-hover:opacity-100 pointer-events-none transition
            whitespace-nowrap z-[9999] shadow-lg">
            Open Sidebar
        </div>
    </button>

    <!-- PROFILE -->
    <div class="h-px w-full shrink-0 bg-gradient-to-r from-[#D4AF37] to-transparent"></div>

    <a
        href="{{ route('profile') }}"
        class="group relative w-full flex items-center gap-3 my-2 shrink-0 pr-2 text-left
        {{ request()->is('profile') ? 'bg-gradient-to-r from-[#C8102E] to-[#7A0019] border-l-[3px] border-[#D4AF37]' : 'hover:bg-white/10' }}
        rounded-xl p-2 transition"
        aria-label="My Profile"
    >
        <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 ring-2 ring-[#D4AF37]/60">
            <img
                src="https://ui-avatars.com/api/?name={{ urlencode(session('short_name') ?: session('full_name') ?: session('employee_name') ?: 'User') }}&background=D4AF37&color=1a1a1a&size=36"
                class="w-full h-full object-cover"
                alt="Profile"
            />
        </div>

        <div class="sidebar-text leading-tight min-w-0">
            <p class="text-[12px] font-bold text-white truncate">
                {{ session('short_name') ?: session('full_name') ?: session('employee_name') ?: 'User' }}
            </p>
            <p class="text-[9px] text-[#A4C3B2] truncate mt-0.5">
                {{ session('position') ?: 'My Profile' }}
            </p>
        </div>

        <div class="sidebar-tooltip hidden absolute left-[58px] top-1/2 -translate-y-1/2
            bg-black text-white text-[10px] px-2 py-1 rounded-md
            opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150
            whitespace-nowrap z-[9999] shadow-lg">
            My Profile
        </div>
    </a>

    <div class="h-px w-full shrink-0 mb-4 bg-gradient-to-r from-transparent to-[#D4AF37]"></div>

    @php
        $navSections = [
            [
                'title' => 'Overview',
                'items' => [
                    [
                        'label' => 'Main Dashboard',
                        'href' => '/dashboard',
                        'match' => 'dashboard*',
                        'icon' => 'dashboard',
                    ],
                    [
                        'label' => 'Job Description',
                        'href' => route('job-description'),
                        'match' => 'job-description*',
                        'icon' => 'jobdesc',
                    ],
                ],
            ],
            [
                'title' => 'KPI Work',
                'items' => [
                    [
                        'label' => 'View My KPI',
                        'href' => '/kpi',
                        'match' => [
                            'kpi',
                            'kpi/*/edit'
                        ],
                        'icon' => 'list',
                    ],
                    [
                        'label' => 'Create New KPI',
                        'href' => '/kpi/create',
                        'match' => [
                            'kpi/create'
                        ],
                        'icon' => 'plus',
                    ],
                    [
                        'label' => 'My Department KPI',
                        'href' => route('kpi.my-department-kpi'),
                        'match' => 'my-department-kpi*',
                        'icon' => 'department',
                    ],
                    [
                        'label'     => 'Titan KPI',
                        'href'      => route('titan-kpi.index'),
                        'match'     => 'titan-kpi*',
                        'icon'      => 'report',
                        'titan_only' => true,
                    ],
                    [
                        'label' => 'Manage Weightage',
                        'href' => route('weightage'),
                        'match' => [
                            'weightage',
                            'weightage/*'
                        ],
                        'icon' => 'weightage',
                    ],
                ],
            ],
            [
                'title' => 'Monitoring',
                'items' => [

                    [
                        'label' => 'Approval',
                        'href' => '/approval',
                        'match' => 'approval*',
                        'icon' => 'approval',
                        'badge' => $pendingApprovalCount ?? 0,
                    ],

                    [
                        'label' => 'User Activity Log',
                        'href' => '/activity-log',
                        'match' => 'activity-log*',
                        'icon' => 'activity',
                    ],
                ],
            ],
            [
                'title'   => 'Attendance',
                'hr_only' => true,
                'items'   => [
                    [
                        'label' => 'Import & Analysis',
                        'href'  => '/attendance',
                        'match' => 'attendance*',
                        'icon'  => 'attendance',
                    ],
                ],
            ],
            [
                'title' => 'Performance Evaluation',
                'items' => [
                    ['label' => 'Q1 Evaluation', 'href' => '/performance/report/q1', 'match' => 'performance/report/q1*', 'icon' => 'report'],
                    ['label' => 'Q2 Evaluation', 'href' => '/performance/report/q2', 'match' => 'performance/report/q2*', 'icon' => 'report'],
                    ['label' => 'Q3 Evaluation', 'href' => '/performance/report/q3', 'match' => 'performance/report/q3*', 'icon' => 'report'],
                    ['label' => 'Q4 Evaluation', 'href' => '/performance/report/q4', 'match' => 'performance/report/q4*', 'icon' => 'report'],
                    ['label' => 'Appraise Team', 'href' => '/performance/appraise', 'match' => 'performance/appraise*', 'icon' => 'report', 'manager_vp_only' => true],
                ],
            ],
            [
                'title'    => 'Daily Execution',
                'bts_only' => true,
                'items'    => [
                    ['label' => 'Initiatives', 'href' => '/initiatives', 'match' => 'initiatives*', 'icon' => 'initiative'],
                    ['label' => 'Tasks',        'href' => '/tasks',       'match' => 'tasks*',       'icon' => 'task'],
                    ['label' => 'Calendar',     'href' => '/calendar',    'match' => 'calendar*',    'icon' => 'calendar'],
                ],
            ],
            [
                'title'    => 'Review & Insights',
                'bts_only' => true,
                'items'    => [
                    ['label' => 'Reports',     'href' => '/reports',     'match' => 'reports*',     'icon' => 'report'],
                    ['label' => 'Analytics',   'href' => '/analytics',   'match' => 'analytics*',   'icon' => 'analytics'],
                    ['label' => 'Leaderboard', 'href' => '/leaderboard', 'match' => 'leaderboard*', 'icon' => 'leaderboard'],
                ],
            ],
            [
                'title'    => 'Admin Setup',
                'bts_only' => true,
                'items'    => [
                    ['label' => 'Staff Access',    'href' => '/users-access', 'match' => 'users-access*', 'icon' => 'users'],
                    ['label' => 'System Settings', 'href' => '/settings',     'match' => 'settings*',     'icon' => 'settings'],
                    ['label' => 'View As (Employee KPI)', 'href' => route('admin.view-as'), 'match' => 'admin/view-as*', 'icon' => 'users'],
                ],
            ],
        ];
    @endphp

    <!-- NAVIGATION -->
    <nav class="flex-1 overflow-y-auto text-[12px] space-y-5 pr-1 min-h-0 custom-scroll">
        @php $isBts = session('department_code') === 'BTS'; @endphp
        @foreach($navSections as $section)
            @if(($section['hr_only'] ?? false) && !session('hr_access'))
                @continue
            @endif
            @if(($section['bts_only'] ?? false) && !$isBts)
                @continue
            @endif
            <div>
                <div class="sidebar-text flex items-center gap-2 mb-1 px-2">
                    <p class="text-[9px] text-[#D4AF37] font-semibold uppercase tracking-widest shrink-0">
                        {{ $section['title'] }}
                    </p>
                    <div class="h-px flex-1 bg-gradient-to-r from-[#D4AF37] to-transparent"></div>
                </div>

                <div class="space-y-1">
                    @foreach($section['items'] as $item)
                        @php
                            $hasTitanAccess = session('role') !== 'VP' && (
                                (session('company_code') === 'RCG'  && session('department_code') === 'TITAN') ||
                                (session('company_code') === 'RGHB' && session('department_code') === 'BTS')
                            );
                        @endphp
                        @if(($item['titan_only'] ?? false) && !$hasTitanAccess)
                            @continue
                        @endif
                        @if(($item['manager_vp_only'] ?? false) && !session('has_subordinates') && session('department_code') !== 'BTS')
                            @continue
                        @endif
                        @php
                            $isActive = false;

                            if(is_array($item['match'])){

                                foreach($item['match'] as $pattern){

                                    if(request()->is($pattern)){

                                        $isActive = true;
                                        break;
                                    }
                                }

                            }else{

                                $isActive = request()->is($item['match']);
                            }
                        @endphp

                        <a
                            href="{{ $item['href'] }}"
                            class="group relative flex items-center gap-3 px-3 py-2 rounded-xl transition
                            {{ $isActive
                                ? 'bg-gradient-to-r from-[#C8102E] to-[#7A0019] border-l-[3px] border-[#D4AF37] text-white font-black shadow-md'
                                : 'text-white/85 font-medium hover:bg-white/10 hover:text-white'
                            }}"
                        >
                            <span class="w-5 h-5 flex items-center justify-center shrink-0">
                                @include('partials.sidebar-icons', ['icon' => $item['icon']])
                            </span>

                            <div class="flex items-center justify-between w-full min-w-0 gap-2">

                                <span class="sidebar-text truncate">
                                    {{ $item['label'] }}
                                </span>

                                @if(($item['badge'] ?? 0) > 0)

                                    <span class="sidebar-text min-w-[20px] h-[20px]
                                        rounded-full bg-red-500 text-white text-[10px]
                                        font-black flex items-center justify-center
                                        px-1 shadow-lg shadow-red-500/30">

                                        {{ $item['badge'] }}

                                    </span>

                                @endif

                            </div>

                            <div class="sidebar-tooltip hidden absolute left-[58px] top-1/2 -translate-y-1/2
                                bg-black text-white text-[10px] px-2 py-1 rounded-md
                                opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150
                                whitespace-nowrap z-[9999] shadow-lg">
                                {{ $item['label'] }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <!-- SYSTEM ZONE -->
    <div class="sidebar-system mt-3 pt-3 border-t border-white/10 shrink-0">
        <p class="sidebar-text text-[9px] text-[#A4C3B2] font-semibold uppercase tracking-widest mb-2 px-1">
            Current View
        </p>

        <div class="sidebar-text border border-white/10 rounded-xl p-3 bg-white/8 mb-2" style="background:rgba(255,255,255,0.07)">
            @if($canSwitchDepartment ?? false)
                <p class="text-[10px] text-white mb-2 font-semibold">
                    Department View
                </p>

                <form method="POST" action="{{ route('switch.department') }}">
                    @csrf

                    <select
                        name="department_code"
                        onchange="this.form.submit()"
                        class="w-full rounded-lg px-2 py-2 text-[11px] text-white outline-none border border-white/20"
                        style="background:rgba(255,255,255,0.1)"
                    >
                        @foreach(($departments ?? []) as $dept)
                            <option
                                value="{{ $dept['code'] }}"
                                {{ (($selectedDepartmentCode ?? null) === $dept['code']) ? 'selected' : '' }}
                            >
                                {{ $dept['name'] }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @else
                <p class="text-[10px] text-white mb-1 font-semibold">
                    Department
                </p>

                <div class="text-[11px] text-white truncate">
                    {{ $department['name'] ?? $user['department_code'] ?? '-' }}
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                onclick="return confirm('You are about to logout. Continue?')"
                class="group relative w-full flex items-center gap-3 px-3 py-2 rounded-xl text-[11px] font-semibold
                bg-red-600 text-white border border-red-500
                hover:bg-red-700 hover:border-red-600 transition shadow-lg shadow-red-900/40"
            >
                <span class="w-5 h-5 flex items-center justify-center shrink-0">
                    @include('partials.sidebar-icons', ['icon' => 'logout'])
                </span>

                <span class="sidebar-text">
                    Logout
                </span>

                <div class="sidebar-tooltip hidden absolute left-[58px] top-1/2 -translate-y-1/2
                    bg-black text-white text-[10px] px-2 py-1 rounded-md
                    opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150
                    whitespace-nowrap z-[9999] shadow-lg">
                    Logout
                </div>
            </button>
        </form>
    </div>

</aside>

<script>
    function setSidebarState(collapsed) {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        if (!sidebar) return;

        const texts = sidebar.querySelectorAll('.sidebar-text');
        const iconOnly = sidebar.querySelectorAll('.sidebar-icon-only');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const logo = sidebar.querySelector('.sidebar-logo');
        const systemZone = sidebar.querySelector('.sidebar-system');

        sidebar.classList.toggle('collapsed', collapsed);

        sidebar.classList.toggle('w-[230px]', !collapsed);
        sidebar.classList.toggle('min-w-[230px]', !collapsed);
        sidebar.classList.toggle('max-w-[230px]', !collapsed);

        sidebar.classList.toggle('w-[64px]', collapsed);
        sidebar.classList.toggle('min-w-[64px]', collapsed);
        sidebar.classList.toggle('max-w-[64px]', collapsed);

        texts.forEach(item => {
            item.classList.toggle('hidden', collapsed);
        });

        iconOnly.forEach(item => {
            item.classList.toggle('hidden', !collapsed);
        });

        if (logo) {
            logo.classList.toggle('hidden', collapsed);
        }

        if (closeBtn) {
            closeBtn.classList.toggle('hidden', collapsed);
        }

        if (systemZone) {
            systemZone.classList.toggle('border-t', !collapsed);
            systemZone.classList.toggle('pt-3', !collapsed);
            systemZone.classList.toggle('mt-3', !collapsed);
        }

        if (mainContent) {
            mainContent.classList.toggle('ml-[230px]', !collapsed);
            mainContent.classList.toggle('ml-[64px]', collapsed);
        }

        localStorage.setItem('sidebarCollapsed', collapsed ? 'true' : 'false');
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        const isCollapsed = sidebar.classList.contains('collapsed');
        setSidebarState(!isCollapsed);
    }

    function handleSidebarHeaderClick() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        const isCollapsed = sidebar.classList.contains('collapsed');

        if (isCollapsed) {
            setSidebarState(false);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        setSidebarState(isCollapsed);
    });
</script>
