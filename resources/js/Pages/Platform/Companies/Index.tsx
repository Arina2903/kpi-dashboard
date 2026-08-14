import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface Company {
    id: string;
    name: string;
    code: string;
    status: string;
}

interface AdminRow {
    company_id: string;
    users: { name: string; email: string };
}

interface CompaniesPageProps {
    companies: Company[];
    admins: AdminRow[];
    flash: { error?: string | null; success?: string | null };
    [key: string]: unknown;
}

function CreateCompanyForm() {
    const { data, setData, post, processing, reset } = useForm({ name: '', code: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/platform/companies', { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="flex items-end gap-3 mb-6">
            <div className="flex-1">
                <label className="block text-xs font-medium text-slate-600 mb-1">Company name</label>
                <input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Andalusia"
                    required
                />
            </div>
            <div className="w-40">
                <label className="block text-xs font-medium text-slate-600 mb-1">Code</label>
                <input
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="ANDALUSIA"
                    required
                />
            </div>
            <button
                type="submit"
                disabled={processing}
                className="rounded-lg bg-[#06142f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0b1f49] disabled:opacity-60"
            >
                Create company
            </button>
        </form>
    );
}

function InviteAdminForm({ companyId }: { companyId: string }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm({ name: '', email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/admins`, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <button onClick={() => setOpen(true)} className="text-xs font-semibold text-[#06142f] hover:underline">
                + Invite Company Admin
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="flex items-end gap-2 mt-2">
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
            <button
                type="submit"
                disabled={processing}
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

export default function CompaniesIndex({ companies, admins }: CompaniesPageProps) {
    const { flash } = usePage<CompaniesPageProps>().props;

    const adminsByCompany = admins.reduce<Record<string, AdminRow[]>>((acc, row) => {
        (acc[row.company_id] ??= []).push(row);
        return acc;
    }, {});

    return (
        <>
            <Head title="Manage Companies" />

            <div className="min-h-screen bg-slate-50 p-8">
                <div className="max-w-3xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-lg font-bold text-slate-900">Manage Companies</h1>
                        <Link href="/platform/dashboard" className="text-sm font-semibold text-[#06142f] hover:underline">
                            ← Back to dashboard
                        </Link>
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
                        <CreateCompanyForm />

                        <ul className="divide-y divide-slate-100">
                            {companies.map((company) => (
                                <li key={company.id} className="py-3">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-semibold text-slate-800">{company.name}</p>
                                            <p className="text-xs text-slate-400">{company.code}</p>
                                        </div>
                                        <span className="text-xs font-semibold text-emerald-600 uppercase">
                                            {company.status}
                                        </span>
                                    </div>

                                    <div className="mt-2">
                                        {(adminsByCompany[company.id] ?? []).map((row, i) => (
                                            <p key={i} className="text-xs text-slate-500">
                                                Admin: {row.users.name} ({row.users.email})
                                            </p>
                                        ))}
                                        <InviteAdminForm companyId={company.id} />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}