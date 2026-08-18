import { router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Card, PrimaryButton } from '@/Components/Platform/ui';

interface Company {
    id: string;
    name: string;
}

interface Batch {
    id: string;
    filename: string;
    status: string;
}

interface StagedRow {
    employee_code: string | null;
    name: string;
    email: string;
    department_code: string;
    position: string | null;
    manager: string | null;
    join_date: string | null;
    status: string;
    _error?: string;
}

interface CreateUsersPageProps {
    company: Company;
    batch: Batch;
    pending: StagedRow[];
    totalStaged: number;
    [key: string]: unknown;
}

export default function CreateUsers({ company, batch, pending, totalStaged }: CreateUsersPageProps) {
    const [selected, setSelected] = useState<Set<string>>(new Set(pending.map((r) => r.email.toLowerCase())));
    const [processing, setProcessing] = useState(false);

    const toggle = (email: string) => {
        const next = new Set(selected);
        const key = email.toLowerCase();
        if (next.has(key)) {
            next.delete(key);
        } else {
            next.add(key);
        }
        setSelected(next);
    };

    const toggleAll = () => {
        setSelected(selected.size === pending.length ? new Set() : new Set(pending.map((r) => r.email.toLowerCase())));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(`/platform/companies/${company.id}/import/${batch.id}/users`, { emails: Array.from(selected) }, { onFinish: () => setProcessing(false) });
    };

    const alreadyCreated = totalStaged - pending.length;

    return (
        <PlatformLayout title="Create User Accounts" description={batch.filename} company={company} maxWidth="max-w-2xl">
            {alreadyCreated > 0 && (
                <p className="text-xs text-slate-400 mb-4">
                    {alreadyCreated} of {totalStaged} employees already have accounts from an earlier run.
                </p>
            )}

            <form onSubmit={submit}>
                <Card className="mb-6">
                    {pending.length === 0 ? (
                        <p className="text-sm text-emerald-600 font-semibold">All {totalStaged} staged employees have accounts.</p>
                    ) : (
                        <>
                            <div className="flex items-center justify-between mb-3">
                                <label className="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                    <input type="checkbox" checked={selected.size === pending.length} onChange={toggleAll} />
                                    Select all ({pending.length})
                                </label>
                                <p className="text-xs text-slate-400">{selected.size} selected</p>
                            </div>

                            <ul className="divide-y divide-slate-100">
                                {pending.map((row) => (
                                    <li key={row.email} className="py-2 flex items-start gap-3">
                                        <input type="checkbox" className="mt-1" checked={selected.has(row.email.toLowerCase())} onChange={() => toggle(row.email)} />
                                        <div className="flex-1">
                                            <p className="text-sm font-semibold text-slate-800">
                                                {row.name} <span className="font-normal text-slate-400">({row.email})</span>
                                            </p>
                                            <p className="text-xs text-slate-400">
                                                {row.department_code}
                                                {row.position ? ` · ${row.position}` : ''}
                                            </p>
                                            {row._error && <p className="text-xs text-red-600 mt-0.5">⚠ {row._error}</p>}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </Card>

                {pending.length > 0 && (
                    <PrimaryButton type="submit" disabled={processing || selected.size === 0} className="px-5 py-2.5">
                        Create {selected.size} account{selected.size === 1 ? '' : 's'} &amp; send invites
                    </PrimaryButton>
                )}
            </form>
        </PlatformLayout>
    );
}
