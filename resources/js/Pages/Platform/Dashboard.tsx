import { Head, Link, router } from '@inertiajs/react';

interface Company {
    id: string;
    name: string;
    code: string;
    status: string;
    department_count: number;
    user_count: number;
    kpi_count: number;
    submission_count: number;
    avg_achievement_pct: number | null;
}

interface PlatformUser {
    id: string;
    name: string;
    email: string;
    is_super_admin: boolean;
    company_memberships: Array<{
        company_id: string;
        role: string;
        companies: { name: string; code: string };
    }>;
}

interface DashboardPageProps {
    me: PlatformUser;
    visibleCompanies: Company[];
    [key: string]: unknown;
}

function StatTile({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-xl bg-slate-50 px-4 py-3">
            <p className="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">{label}</p>
            <p className="text-xl font-bold text-slate-800 tabular-nums">{value}</p>
        </div>
    );
}

export default function PlatformDashboard({ me, visibleCompanies }: DashboardPageProps) {
    const logout = () => router.post('/platform/logout');

    const totals = visibleCompanies.reduce(
        (acc, c) => ({
            companies: acc.companies + 1,
            departments: acc.departments + c.department_count,
            users: acc.users + c.user_count,
            kpis: acc.kpis + c.kpi_count,
            submissions: acc.submissions + c.submission_count,
        }),
        { companies: 0, departments: 0, users: 0, kpis: 0, submissions: 0 },
    );

    return (
        <>
            <Head title="Platform Dashboard" />

            <div className="min-h-screen bg-slate-50 p-8">
                <div className="max-w-3xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-lg font-bold text-slate-900">Multi-Company KPI Platform</h1>
                            <p className="text-sm text-slate-500">
                                Signed in as {me.name} ({me.email}) —{' '}
                                {me.is_super_admin ? 'Richworks Super Admin' : 'Company user'}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            {me.is_super_admin && (
                                <Link
                                    href="/platform/companies"
                                    className="text-sm font-semibold text-[#06142f] hover:underline"
                                >
                                    Manage companies
                                </Link>
                            )}
                            {me.company_memberships
                                .filter((m) => m.role === 'company_admin')
                                .map((m) => (
                                    <Link
                                        key={m.company_id}
                                        href={`/platform/companies/${m.company_id}/departments`}
                                        className="text-sm font-semibold text-[#06142f] hover:underline"
                                    >
                                        Manage {m.companies.name} departments
                                    </Link>
                                ))}
                            {me.company_memberships.map((m) => (
                                <Link
                                    key={`kpis-${m.company_id}`}
                                    href={`/platform/companies/${m.company_id}/kpis`}
                                    className="text-sm font-semibold text-[#06142f] hover:underline"
                                >
                                    {m.companies.name} KPIs
                                </Link>
                            ))}
                            <button
                                onClick={logout}
                                className="text-sm font-semibold text-red-600 hover:underline"
                            >
                                Logout
                            </button>
                        </div>
                    </div>

                    {me.is_super_admin && (
                        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
                            <h2 className="text-sm font-bold text-slate-700 mb-3">Platform totals</h2>
                            <div className="grid grid-cols-5 gap-3">
                                <StatTile label="Companies" value={totals.companies} />
                                <StatTile label="Departments" value={totals.departments} />
                                <StatTile label="Users" value={totals.users} />
                                <StatTile label="KPIs" value={totals.kpis} />
                                <StatTile label="Submissions" value={totals.submissions} />
                            </div>
                        </div>
                    )}

                    <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h2 className="text-sm font-bold text-slate-700 mb-3">
                            Companies visible to you ({visibleCompanies.length})
                        </h2>
                        <p className="text-xs text-slate-400 mb-4">
                            This list — and every number below — is not filtered by this page. Postgres RLS decided
                            what came back, including inside the aggregation view. A Super Admin sees every company;
                            anyone else sees only their own.
                        </p>

                        {visibleCompanies.length === 0 ? (
                            <p className="text-sm text-slate-400">No companies visible.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {visibleCompanies.map((company) => (
                                    <li key={company.id} className="py-4">
                                        <div className="flex items-center justify-between mb-3">
                                            <div>
                                                <p className="text-sm font-semibold text-slate-800">{company.name}</p>
                                                <p className="text-xs text-slate-400">{company.code}</p>
                                            </div>
                                            <span className="text-xs font-semibold text-emerald-600 uppercase">
                                                {company.status}
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-5 gap-2">
                                            <StatTile label="Departments" value={company.department_count} />
                                            <StatTile label="Users" value={company.user_count} />
                                            <StatTile label="KPIs" value={company.kpi_count} />
                                            <StatTile label="Submissions" value={company.submission_count} />
                                            <StatTile
                                                label="Avg. achievement"
                                                value={
                                                    company.avg_achievement_pct !== null
                                                        ? `${company.avg_achievement_pct}%`
                                                        : '—'
                                                }
                                            />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
