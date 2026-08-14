import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useEffect, useState } from 'react';

interface Company {
    id: string;
    name: string;
    code: string;
}

interface Department {
    id: string;
    name: string;
    code: string;
    status: string;
}

interface MemberRow {
    department_id: string;
    role: string;
    role_id: string | null;
    users: { name: string; email: string };
}

interface RoleRow {
    id: string;
    department_id: string;
    label: string;
    rank: number;
}

interface DepartmentsPageProps {
    company: Company;
    departments: Department[];
    members: MemberRow[];
    roles: RoleRow[];
    flash: { error?: string | null; success?: string | null };
    [key: string]: unknown;
}

function CreateDepartmentForm({ companyId }: { companyId: string }) {
    const { data, setData, post, processing, reset } = useForm({ name: '', code: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/departments`, { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="flex items-end gap-3 mb-6">
            <div className="flex-1">
                <label className="block text-xs font-medium text-slate-600 mb-1">Department name</label>
                <input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Finance"
                    required
                />
            </div>
            <div className="w-40">
                <label className="block text-xs font-medium text-slate-600 mb-1">Code</label>
                <input
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="FIN"
                    required
                />
            </div>
            <button
                type="submit"
                disabled={processing}
                className="rounded-lg bg-[#06142f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0b1f49] disabled:opacity-60"
            >
                Create department
            </button>
        </form>
    );
}

function InviteDepartmentUserForm({
    companyId,
    departmentId,
    roles,
}: {
    companyId: string;
    departmentId: string;
    roles: RoleRow[];
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        name: '',
        email: '',
        role: 'department_user',
        role_id: roles[0]?.id ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/departments/${departmentId}/users`, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    // A role added via RolesManager after this form already mounted (e.g.
    // the department's very first role) wouldn't otherwise be picked up,
    // since useForm only sets its initial value once.
    useEffect(() => {
        if (!roles.some((r) => r.id === data.role_id)) {
            setData('role_id', roles[0]?.id ?? '');
        }
    }, [roles]);

    if (!open) {
        return (
            <button onClick={() => setOpen(true)} className="text-xs font-semibold text-[#06142f] hover:underline">
                + Add person
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="flex items-end gap-2 mt-2 flex-wrap">
            <input
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                placeholder="Full name"
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs"
                required
            />
            <input
                type="email"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                placeholder="Email"
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs"
                required
            />
            <select
                value={data.role_id}
                onChange={(e) => setData('role_id', e.target.value)}
                className="rounded-lg border border-slate-300 px-2 py-1.5 text-xs"
                title="Position — this company's own department role"
                required
            >
                {roles.length === 0 && <option value="">No roles yet — add one below first</option>}
                {roles.map((r) => (
                    <option key={r.id} value={r.id}>
                        {r.label}
                    </option>
                ))}
            </select>
            <select
                value={data.role}
                onChange={(e) => setData('role', e.target.value)}
                className="rounded-lg border border-slate-300 px-2 py-1.5 text-xs"
                title="Access level — what they can do in this admin console"
            >
                <option value="department_user">Department User</option>
                <option value="department_admin">Department Admin</option>
            </select>
            <button
                type="submit"
                disabled={processing || roles.length === 0}
                className="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
            >
                Send
            </button>
            <button type="button" onClick={() => setOpen(false)} className="text-xs text-slate-400">
                Cancel
            </button>
        </form>
    );
}

function RolesManager({
    companyId,
    departmentId,
    roles,
}: {
    companyId: string;
    departmentId: string;
    roles: RoleRow[];
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm({ label: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/departments/${departmentId}/roles`, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const removeRole = (roleId: string, label: string) => {
        if (!confirm(`Remove the "${label}" role? This only works if no one currently holds it.`)) {
            return;
        }
        router.delete(`/platform/companies/${companyId}/departments/${departmentId}/roles/${roleId}`);
    };

    return (
        <div className="mt-2 flex flex-wrap items-center gap-1.5">
            <span className="text-xs text-slate-400 mr-1">Roles:</span>
            {roles.map((r) => (
                <span
                    key={r.id}
                    className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600"
                >
                    {r.label}
                    <button
                        onClick={() => removeRole(r.id, r.label)}
                        className="text-slate-400 hover:text-red-500"
                        title={`Remove ${r.label}`}
                    >
                        ×
                    </button>
                </span>
            ))}
            {open ? (
                <form onSubmit={submit} className="inline-flex items-center gap-1.5">
                    <input
                        value={data.label}
                        onChange={(e) => setData('label', e.target.value)}
                        placeholder="e.g. Lead"
                        className="rounded-full border border-slate-300 px-2.5 py-0.5 text-xs w-24"
                        autoFocus
                        required
                    />
                    <button type="submit" disabled={processing} className="text-xs font-semibold text-[#06142f]">
                        Add
                    </button>
                    <button type="button" onClick={() => setOpen(false)} className="text-xs text-slate-400">
                        ✕
                    </button>
                </form>
            ) : (
                <button onClick={() => setOpen(true)} className="text-xs font-semibold text-[#06142f] hover:underline">
                    + Add role
                </button>
            )}
        </div>
    );
}

export default function DepartmentsIndex({ company, departments, members, roles }: DepartmentsPageProps) {
    const { flash } = usePage<DepartmentsPageProps>().props;

    const membersByDepartment = members.reduce<Record<string, MemberRow[]>>((acc, row) => {
        (acc[row.department_id] ??= []).push(row);
        return acc;
    }, {});

    const rolesByDepartment = roles.reduce<Record<string, RoleRow[]>>((acc, row) => {
        (acc[row.department_id] ??= []).push(row);
        return acc;
    }, {});

    return (
        <>
            <Head title={`${company.name} — Departments`} />

            <div className="min-h-screen bg-slate-50 p-8">
                <div className="max-w-3xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-lg font-bold text-slate-900">{company.name} — Departments</h1>
                        <div className="flex items-center gap-4">
                            <Link
                                href={`/platform/companies/${company.id}/kpis`}
                                className="text-sm font-semibold text-[#06142f] hover:underline"
                            >
                                View KPIs
                            </Link>
                            <Link href="/platform/dashboard" className="text-sm font-semibold text-[#06142f] hover:underline">
                                ← Back to dashboard
                            </Link>
                        </div>
                    </div>

                    {flash.error && (
                        <div className="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            {flash.error}
                        </div>
                    )}
                    {flash.success && (
                        <div className="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <CreateDepartmentForm companyId={company.id} />

                        {departments.length === 0 ? (
                            <p className="text-sm text-slate-400">No departments yet.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {departments.map((department) => (
                                    <li key={department.id} className="py-3">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-semibold text-slate-800">{department.name}</p>
                                                <p className="text-xs text-slate-400">{department.code}</p>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <Link
                                                    href={`/platform/companies/${company.id}/departments/${department.id}/submissions`}
                                                    className="text-xs font-semibold text-[#06142f] hover:underline"
                                                >
                                                    KPI submissions
                                                </Link>
                                                <span className="text-xs font-semibold text-emerald-600 uppercase">
                                                    {department.status}
                                                </span>
                                            </div>
                                        </div>

                                        <RolesManager
                                            companyId={company.id}
                                            departmentId={department.id}
                                            roles={rolesByDepartment[department.id] ?? []}
                                        />

                                        <div className="mt-2">
                                            {(membersByDepartment[department.id] ?? []).map((row, i) => (
                                                <p key={i} className="text-xs text-slate-500">
                                                    {row.role === 'department_admin' ? 'Admin' : 'User'}: {row.users.name} (
                                                    {row.users.email})
                                                </p>
                                            ))}
                                            <InviteDepartmentUserForm
                                                companyId={company.id}
                                                departmentId={department.id}
                                                roles={rolesByDepartment[department.id] ?? []}
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