import { Link } from '@inertiajs/react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Badge, Card, EmptyState, InfoTooltip, StatCard, StatusBadge } from '@/Components/Platform/ui';
import { BuildingIcon, RocketIcon, ShieldCheckIcon, UsersIcon } from '@/Components/Platform/Icons';

interface PlatformUser {
    id: string;
    name: string;
    email: string;
    is_super_admin: boolean;
}

interface Stats {
    total_companies: number;
    active_companies: number;
    suspended_companies: number;
    total_users: number;
}

interface OnboardingProgress {
    draft: number;
    onboarding: number;
    configuring: number;
    active: number;
    suspended: number;
    archived: number;
}

interface SystemHealth {
    database: 'reachable' | 'degraded' | 'unreachable';
    horizonUrl: string;
}

interface ActivityEntry {
    id: string;
    action: string;
    actor_email: string | null;
    target_company_id: string | null;
    target_company_name: string | null;
    target_type: string | null;
    occurred_at: string;
}

interface SecurityEntry {
    id: string;
    action: string;
    actor_email: string | null;
    metadata: Record<string, unknown>;
    occurred_at: string;
}

interface SecurityAlerts {
    loginFailed24h: number;
    accessDenied24h: number;
    recent: SecurityEntry[];
}

interface Company {
    id: string;
    name: string;
    code: string;
    status: string;
    onboarding_status: string;
    created_at: string;
}

interface PlatformOverviewPageProps {
    me: PlatformUser;
    stats: Stats;
    onboardingProgress: OnboardingProgress;
    systemHealth: SystemHealth;
    recentActivity: ActivityEntry[];
    securityAlerts: SecurityAlerts;
    companies: Company[];
    [key: string]: unknown;
}

const ACTION_LABEL: Record<string, string> = {
    login: 'Logged in',
    login_failed: 'Failed login attempt',
    logout: 'Logged out',
    access_denied: 'Access denied',
    create_company: 'Created company',
    invite_company_admin: 'Invited Company Admin',
    activate_company: 'Activated company',
    suspend_company: 'Suspended company',
    reactivate_company: 'Reactivated company',
    archive_company: 'Archived company',
    unarchive_company: 'Unarchived company',
    create_department: 'Created department',
    invite_department_user: 'Invited department member',
    update_user_role: 'Changed member role',
    suspend_user: 'Suspended member',
    reactivate_user: 'Reactivated member',
    create_kpi: 'Created KPI',
    update_kpi: 'Updated KPI',
    change_kpi_target: 'Changed KPI target',
    create_user_accounts: 'Created user accounts',
    import_data: 'Imported data',
    telegram_link_completed: 'Linked Telegram account',
    telegram_link_failed: 'Telegram link attempt failed',
};

const STATUS_LABEL: Record<keyof OnboardingProgress, string> = {
    draft: 'Draft',
    onboarding: 'Onboarding',
    configuring: 'Configuring',
    active: 'Active',
    suspended: 'Suspended',
    archived: 'Archived',
};

const STATUS_COLOR: Record<keyof OnboardingProgress, string> = {
    draft: 'bg-slate-300',
    onboarding: 'bg-sky-300',
    configuring: 'bg-sky-500',
    active: 'bg-emerald-500',
    suspended: 'bg-red-400',
    archived: 'bg-slate-500',
};

function OnboardingProgressBar({ progress }: { progress: OnboardingProgress }) {
    const total = Object.values(progress).reduce((a, b) => a + b, 0);
    const live = progress.active + progress.suspended + progress.archived;

    if (total === 0) {
        return <p className="text-sm text-slate-400">No companies yet.</p>;
    }

    return (
        <div>
            <p className="text-xs text-slate-500 mb-2">
                <span className="font-semibold text-slate-700">{live}</span> of {total} companies have gone live at least once.
            </p>
            <div className="flex h-2.5 rounded-full overflow-hidden bg-slate-100 mb-3">
                {(Object.keys(progress) as Array<keyof OnboardingProgress>).map((key) => {
                    const width = (progress[key] / total) * 100;
                    if (width === 0) return null;
                    return <div key={key} className={STATUS_COLOR[key]} style={{ width: `${width}%` }} title={STATUS_LABEL[key]} />;
                })}
            </div>
            <div className="grid grid-cols-3 gap-2 sm:grid-cols-6">
                {(Object.keys(progress) as Array<keyof OnboardingProgress>).map((key) => (
                    <div key={key} className="flex items-center gap-1.5 text-xs text-slate-500">
                        <span className={`h-2 w-2 rounded-full flex-none ${STATUS_COLOR[key]}`} />
                        <span className="font-semibold text-slate-700">{progress[key]}</span> {STATUS_LABEL[key]}
                    </div>
                ))}
            </div>
        </div>
    );
}

function ActivityRow({ label, actor, target, occurredAt, danger }: { label: string; actor: string | null; target?: string | null; occurredAt: string; danger?: boolean }) {
    return (
        <li className="py-2.5 text-xs">
            <div className="flex items-center justify-between gap-2">
                <span className={`font-semibold ${danger ? 'text-red-600' : 'text-slate-700'}`}>{label}</span>
                <span className="text-slate-400 tabular-nums flex-none">{new Date(occurredAt).toLocaleString()}</span>
            </div>
            <p className="text-slate-500">
                {actor ?? 'Unknown actor'}
                {target ? ` → ${target}` : ''}
            </p>
        </li>
    );
}

export default function PlatformOverview({ me, stats, onboardingProgress, systemHealth, recentActivity, securityAlerts, companies }: PlatformOverviewPageProps) {
    const dbTone: 'success' | 'warning' | 'danger' =
        systemHealth.database === 'reachable' ? 'success' : systemHealth.database === 'degraded' ? 'warning' : 'danger';

    return (
        <PlatformLayout title="Performix Platform" description={`Signed in as ${me.name} — Richworks Super Admin`}>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <StatCard label="Total Companies" value={stats.total_companies} icon={<BuildingIcon className="w-3.5 h-3.5" />} />
                <StatCard label="Active Companies" value={stats.active_companies} tone="success" icon={<BuildingIcon className="w-3.5 h-3.5" />} />
                <StatCard
                    label="Suspended Companies"
                    value={stats.suspended_companies}
                    tone={stats.suspended_companies > 0 ? 'danger' : 'default'}
                    icon={<ShieldCheckIcon className="w-3.5 h-3.5" />}
                />
                <StatCard label="Total Users" value={stats.total_users} icon={<UsersIcon className="w-3.5 h-3.5" />} />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <Card
                    title={
                        <span className="inline-flex items-center gap-1.5">
                            Onboarding Progress
                            <InfoTooltip text="Every company moves through Draft → Onboarding → Configuring before it can go Active. This shows how many are at each stage right now." />
                        </span>
                    }
                >
                    <OnboardingProgressBar progress={onboardingProgress} />
                </Card>

                <Card title="System Health">
                    <ul className="space-y-3 text-sm">
                        <li className="flex items-center justify-between">
                            <span className="text-slate-600">Database</span>
                            <Badge tone={dbTone}>{systemHealth.database}</Badge>
                        </li>
                        <li className="flex items-center justify-between">
                            <span className="text-slate-600">Background jobs</span>
                            <a href={systemHealth.horizonUrl} target="_blank" rel="noreferrer" className="font-semibold text-brand-800 hover:underline text-xs">
                                Open Horizon →
                            </a>
                        </li>
                    </ul>
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <Card
                    title="Recent Admin Activity"
                    actions={
                        <Link href="/platform/audit-log" className="text-xs font-semibold text-brand-800 hover:underline">
                            View full log →
                        </Link>
                    }
                >
                    {recentActivity.length === 0 ? (
                        <EmptyState title="Nothing logged yet" description="Actions taken across the Platform will show up here as they happen." />
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {recentActivity.map((entry) => (
                                <ActivityRow
                                    key={entry.id}
                                    label={ACTION_LABEL[entry.action] ?? entry.action}
                                    actor={entry.actor_email}
                                    target={entry.target_company_name}
                                    occurredAt={entry.occurred_at}
                                />
                            ))}
                        </ul>
                    )}
                </Card>

                <Card
                    title={
                        <span className="inline-flex items-center gap-1.5">
                            Security Alerts
                            <InfoTooltip text="Failed sign-ins and access-denied attempts from the last 24 hours — a sudden spike is worth investigating." />
                        </span>
                    }
                    actions={
                        <Link href="/platform/audit-log" className="text-xs font-semibold text-brand-800 hover:underline">
                            View full log →
                        </Link>
                    }
                >
                    <div className="grid grid-cols-2 gap-3 mb-4">
                        <StatCard label="Failed logins (24h)" value={securityAlerts.loginFailed24h} tone={securityAlerts.loginFailed24h > 0 ? 'danger' : 'default'} />
                        <StatCard label="Access denied (24h)" value={securityAlerts.accessDenied24h} tone={securityAlerts.accessDenied24h > 0 ? 'danger' : 'default'} />
                    </div>
                    {securityAlerts.recent.length === 0 ? (
                        <EmptyState title="No security events" description="You'll see failed logins and denied access attempts here as they happen." />
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {securityAlerts.recent.map((entry) => (
                                <ActivityRow key={entry.id} label={ACTION_LABEL[entry.action] ?? entry.action} actor={entry.actor_email} occurredAt={entry.occurred_at} danger />
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <Card title={`Companies (${companies.length})`} description="Click a company to enter its administration area.">
                {companies.length === 0 ? (
                    <EmptyState icon={<BuildingIcon className="w-10 h-10" />} title="No companies yet" description="Create your first company from the Companies page." />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {companies.map((company) => (
                            <li key={company.id} className="py-3.5">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <Link href={`/platform/companies/${company.id}/departments`} className="group">
                                        <p className="text-sm font-bold text-slate-800 group-hover:underline">{company.name}</p>
                                        <p className="text-xs text-slate-400">{company.code}</p>
                                    </Link>
                                    <div className="flex items-center gap-4">
                                        <Link href={`/platform/companies/${company.id}/onboarding`} className="text-xs font-semibold text-brand-800 hover:underline">
                                            Onboarding
                                        </Link>
                                        <Link href={`/platform/companies/${company.id}/kpis`} className="text-xs font-semibold text-brand-800 hover:underline">
                                            KPIs
                                        </Link>
                                        <Link href={`/platform/companies/${company.id}/audit-log`} className="text-xs font-semibold text-brand-800 hover:underline">
                                            Audit log
                                        </Link>
                                        <StatusBadge status={company.status} />
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </PlatformLayout>
    );
}
