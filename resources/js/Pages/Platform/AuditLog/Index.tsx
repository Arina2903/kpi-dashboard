import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Card, EmptyState } from '@/Components/Platform/ui';
import { ShieldCheckIcon } from '@/Components/Platform/Icons';

interface PersonRef {
    id: string;
    name: string;
    email: string;
}

interface CompanyRef {
    id: string;
    name: string;
    code: string;
}

interface LogEntry {
    id: string;
    action: string;
    occurred_at: string;
    metadata: Record<string, unknown>;
    actor: PersonRef | null;
    actor_email: string | null;
    target_user: PersonRef | null;
    target_company: CompanyRef | null;
    target_type: string | null;
    target_id: string | null;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
}

interface AuditLogPageProps {
    logs: LogEntry[];
    company: CompanyRef | null;
    [key: string]: unknown;
}

const ACTION_LABEL: Record<string, string> = {
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
    create_role: 'Created role',
    delete_role: 'Deleted role',
    create_kpi_category: 'Created KPI category',
    create_kpi: 'Created KPI',
    update_kpi: 'Updated KPI',
    change_kpi_target: 'Changed KPI target',
    apply_kpi_template: 'Applied KPI template',
    grant_kpi_access: 'Granted KPI access',
    revoke_kpi_access: 'Revoked KPI access',
    import_data: 'Imported data',
    create_user_accounts: 'Created user accounts',
    grant_platform_admin: 'Granted Platform Admin',
    revoke_platform_admin_assignment: 'Revoked Platform Admin assignment',
    demote_platform_admin: 'Demoted Platform Admin',
    login: 'Logged in',
    login_failed: 'Failed login attempt',
    logout: 'Logged out',
    anira_chat: 'Used ANIRA chat',
    telegram_link_code_generated: 'Generated Telegram link code',
    telegram_link_completed: 'Linked Telegram account',
    telegram_link_failed: 'Telegram link attempt failed',
    telegram_disconnected: 'Disconnected Telegram',
    telegram_digest_morning: 'Sent morning Telegram digest',
    telegram_digest_evening: 'Sent evening Telegram digest',
    access_denied: 'Access denied',
    export_audit_log: 'Exported audit log',
    view_departments: 'Viewed departments (cross-company access)',
    view_kpis: 'Viewed KPIs (cross-company access)',
    view_submissions: 'Viewed submissions (cross-company access)',
};

function Diff({ before, after }: { before: Record<string, unknown> | null; after: Record<string, unknown> | null }) {
    if (!before && !after) {
        return null;
    }

    const keys = Array.from(new Set([...Object.keys(before ?? {}), ...Object.keys(after ?? {})]));
    const changed = keys.filter((k) => JSON.stringify(before?.[k]) !== JSON.stringify(after?.[k]));

    if (changed.length === 0) {
        return null;
    }

    return (
        <div className="mt-1 space-y-0.5">
            {changed.map((k) => (
                <p key={k} className="text-xs text-slate-500">
                    <span className="font-medium">{k}</span>:{' '}
                    <span className="text-red-500 line-through">{String(before?.[k] ?? '—')}</span>{' '}
                    <span className="text-emerald-600">→ {String(after?.[k] ?? '—')}</span>
                </p>
            ))}
        </div>
    );
}

function MetadataSummary({ metadata }: { metadata: Record<string, unknown> }) {
    const parts = Object.entries(metadata)
        .filter(([, v]) => v !== null && v !== undefined && v !== '')
        .map(([k, v]) => `${k}: ${Array.isArray(v) ? v.join(', ') : String(v)}`);

    if (parts.length === 0) {
        return null;
    }

    return <p className="text-xs text-slate-400">{parts.join(' · ')}</p>;
}

export default function AuditLogIndex({ logs, company }: AuditLogPageProps) {
    const exportHref = company
        ? `/platform/companies/${company.id}/audit-log/export`
        : '/platform/audit-log/export';

    return (
        <PlatformLayout
            title={company ? `Audit Log — ${company.name}` : 'Audit Log'}
            description={
                company
                    ? "Every logged action for this company — who did what, and when. Nothing here can be edited or deleted, so it's always trustworthy."
                    : "Every logged action across the Platform — who did what, and when. Nothing here can be edited or deleted, so it's always trustworthy."
            }
            company={company}
            actions={
                <a href={exportHref} className="text-sm font-semibold text-brand-800 hover:underline">
                    Export CSV
                </a>
            }
        >
            <Card>
                {logs.length === 0 ? (
                    <EmptyState icon={<ShieldCheckIcon className="w-10 h-10" />} title="Nothing logged yet" description="Actions taken here will show up as they happen." />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {logs.map((log) => (
                            <li key={log.id} className="py-3">
                                <div className="flex items-center justify-between">
                                    <p className="text-sm font-semibold text-slate-800">
                                        {ACTION_LABEL[log.action] ?? log.action}
                                        {log.target_type && (
                                            <span className="ml-2 text-xs font-normal text-slate-400">
                                                {log.target_type}
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-xs text-slate-400 tabular-nums">
                                        {new Date(log.occurred_at).toLocaleString()}
                                    </p>
                                </div>
                                <p className="text-xs text-slate-500">
                                    {log.actor
                                        ? `${log.actor.name} (${log.actor.email})`
                                        : log.actor_email
                                          ? `${log.actor_email} (unresolved)`
                                          : 'Unknown actor'}
                                    {log.target_company ? ` → ${log.target_company.name}` : ''}
                                    {log.target_user ? ` → ${log.target_user.name}` : ''}
                                </p>
                                <Diff before={log.before} after={log.after} />
                                <MetadataSummary metadata={log.metadata} />
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </PlatformLayout>
    );
}
