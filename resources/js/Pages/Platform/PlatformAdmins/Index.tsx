import { router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Card, EmptyState, PrimaryButton } from '@/Components/Platform/ui';
import { AdjustmentsIcon } from '@/Components/Platform/Icons';

interface Company {
    id: string;
    name: string;
    code: string;
}

interface Assignment {
    id: string;
    user_id: string;
    company_id: string;
    created_at: string;
    users: { name: string; email: string };
    companies: { name: string; code: string };
}

interface PlatformAdminsPageProps {
    companies: Company[];
    assignments: Assignment[];
    flash: { error?: string | null; success?: string | null };
    [key: string]: unknown;
}

function GrantAccessForm({ companies }: { companies: Company[] }) {
    const { data, setData, post, processing, reset } = useForm<{ email: string; company_ids: string[] }>({
        email: '',
        company_ids: [],
    });

    const toggleCompany = (id: string) => {
        setData('company_ids', data.company_ids.includes(id) ? data.company_ids.filter((c) => c !== id) : [...data.company_ids, id]);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/platform/admins', { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="mb-6 bg-slate-50 rounded-xl p-4">
            <div className="mb-3">
                <label className="block text-xs font-medium text-slate-600 mb-1">Account email</label>
                <input
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="person@company.com"
                    required
                />
                <p className="text-xs text-slate-400 mt-1">
                    Must already have a Performix account (from some company's invite flow) — this grants platform reach, it doesn't
                    create the identity.
                </p>
            </div>

            <div className="mb-3">
                <label className="block text-xs font-medium text-slate-600 mb-1">Companies they may operate</label>
                <div className="max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2">
                    {companies.map((c) => (
                        <label key={c.id} className="flex items-center gap-2 py-1 text-sm text-slate-700">
                            <input type="checkbox" checked={data.company_ids.includes(c.id)} onChange={() => toggleCompany(c.id)} />
                            {c.name} <span className="text-xs text-slate-400">({c.code})</span>
                        </label>
                    ))}
                </div>
            </div>

            <PrimaryButton type="submit" disabled={processing || data.company_ids.length === 0}>
                Grant access
            </PrimaryButton>
        </form>
    );
}

export default function PlatformAdminsIndex({ companies, assignments }: PlatformAdminsPageProps) {
    const [confirmingDemote, setConfirmingDemote] = useState<string | null>(null);

    const byUser = assignments.reduce<Record<string, { user: Assignment['users']; rows: Assignment[] }>>((acc, row) => {
        (acc[row.user_id] ??= { user: row.users, rows: [] }).rows.push(row);
        return acc;
    }, {});

    const revoke = (id: string) => router.delete(`/platform/admins/${id}`);

    const demote = (userId: string) => {
        router.post(`/platform/admins/${userId}/demote`);
        setConfirmingDemote(null);
    };

    return (
        <PlatformLayout title="Platform Admins" description="A Platform Admin has no reach of their own — only the companies explicitly assigned here.">
            <Card>
                <GrantAccessForm companies={companies} />

                {Object.keys(byUser).length === 0 ? (
                    <EmptyState icon={<AdjustmentsIcon className="w-10 h-10" />} title="No Platform Admins yet" description="Grant someone access to specific companies above." />
                ) : (
                    <ul className="divide-y divide-slate-100">
                                {Object.entries(byUser).map(([userId, { user, rows }]) => (
                                    <li key={userId} className="py-3">
                                        <div className="flex items-center justify-between mb-2">
                                            <div>
                                                <p className="text-sm font-semibold text-slate-800">{user.name}</p>
                                                <p className="text-xs text-slate-400">{user.email}</p>
                                            </div>
                                            {confirmingDemote === userId ? (
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs text-red-600">Revoke all access and demote to member?</span>
                                                    <button
                                                        onClick={() => demote(userId)}
                                                        className="text-xs font-semibold text-red-600 hover:underline"
                                                    >
                                                        Confirm
                                                    </button>
                                                    <button
                                                        onClick={() => setConfirmingDemote(null)}
                                                        className="text-xs text-slate-400"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            ) : (
                                                <button
                                                    onClick={() => setConfirmingDemote(userId)}
                                                    className="text-xs font-semibold text-red-600 hover:underline"
                                                >
                                                    Demote to member
                                                </button>
                                            )}
                                        </div>
                                        <ul className="space-y-1">
                                            {rows.map((row) => (
                                                <li key={row.id} className="flex items-center justify-between text-xs">
                                                    <span className="text-slate-600">
                                                        {row.companies.name} <span className="text-slate-400">({row.companies.code})</span>
                                                    </span>
                                                    <button onClick={() => revoke(row.id)} className="font-semibold text-red-600 hover:underline">
                                                        Revoke
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
        </PlatformLayout>
    );
}
