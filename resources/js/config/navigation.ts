export interface NavItem {
    label: string;
    href: string;
    match: string | string[];
    icon: string;
    badge?: 'unreadNotifications';
    sltOnly?: boolean;
    titanOnly?: boolean;
}

export interface NavSection {
    title: string;
    items: NavItem[];
    hrOnly?: boolean;
    btsOnly?: boolean;
}

// Ported 1:1 from partials/sidebar.blade.php's $navSections array. Routes are
// hardcoded static paths (no Ziggy yet, consistent with the Phase 3 decision)
// rather than route() calls.
export const navSections: NavSection[] = [
    {
        title: 'Overview',
        items: [
            { label: 'Main Dashboard', href: '/dashboard', match: 'dashboard*', icon: 'dashboard' },
            { label: 'Performix', href: '/mini-app', match: 'mini-app*', icon: 'task' },
            { label: 'Notifications', href: '/notifications', match: 'notifications*', icon: 'bell', badge: 'unreadNotifications' },
            { label: 'Job Description', href: '/job-description', match: 'job-description*', icon: 'jobdesc' },
            { label: 'SLT Dashboard', href: '/slt-dashboard', match: 'slt-dashboard*', icon: 'analytics', sltOnly: true },
        ],
    },
    {
        title: 'KPI Work',
        items: [
            { label: 'Create New KPI', href: '/kpi/create', match: ['kpi/create'], icon: 'plus' },
            { label: 'View My KPI', href: '/kpi', match: ['kpi', 'kpi/*/edit'], icon: 'list' },
            { label: 'Manage Weightage', href: '/weightage', match: ['weightage', 'weightage/*'], icon: 'weightage' },
            { label: 'My Department KPI', href: '/my-department-kpi', match: 'my-department-kpi*', icon: 'department' },
            { label: 'Target Linkages', href: '/linkages', match: 'linkages*', icon: 'linkage' },
            { label: 'Titan KPI', href: '/titan-kpi', match: 'titan-kpi*', icon: 'report', titanOnly: true },
        ],
    },
    {
        title: 'Monitoring',
        items: [
            { label: 'User Activity Log', href: '/activity-log', match: 'activity-log*', icon: 'activity' },
        ],
    },
    {
        title: 'Attendance',
        hrOnly: true,
        items: [
            { label: 'Import & Analysis', href: '/attendance', match: 'attendance*', icon: 'attendance' },
        ],
    },
    {
        title: 'Performance Evaluation',
        items: [
            { label: 'Q1 Evaluation', href: '/performance/report/q1', match: 'performance/report/q1*', icon: 'report' },
            { label: 'Q2 Evaluation', href: '/performance/report/q2', match: 'performance/report/q2*', icon: 'report' },
            { label: 'Q3 Evaluation', href: '/performance/report/q3', match: 'performance/report/q3*', icon: 'report' },
            { label: 'Q4 Evaluation', href: '/performance/report/q4', match: 'performance/report/q4*', icon: 'report' },
        ],
    },
    {
        title: 'Admin Setup',
        btsOnly: true,
        items: [
            { label: 'View As (Employee KPI)', href: '/admin/view-as', match: 'admin/view-as*', icon: 'users' },
        ],
    },
];

function escapeRegExp(value: string): string {
    return value.replace(/[.+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Mirrors Laravel's Request::is() wildcard semantics: '*' matches any run of
 * characters, matched against the path with no leading slash and no
 * query/hash.
 */
export function matchesRoute(pattern: string, currentUrl: string): boolean {
    const path = currentUrl.replace(/^\//, '').split(/[?#]/)[0];
    const regex = new RegExp('^' + pattern.split('*').map(escapeRegExp).join('.*') + '$');
    return regex.test(path);
}

export function isNavItemActive(item: NavItem, currentUrl: string): boolean {
    const patterns = Array.isArray(item.match) ? item.match : [item.match];
    return patterns.some((pattern) => matchesRoute(pattern, currentUrl));
}
