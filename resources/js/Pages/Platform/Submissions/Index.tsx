import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Department {
    id: string;
    name: string;
    code: string;
    company_id: string;
}

interface Kpi {
    id: string;
    name: string;
    target: number | null;
    unit: string | null;
    frequency: string;
}

interface Submission {
    id: string;
    value: number;
    submission_date: string;
    notes: string | null;
    kpis: { name: string; unit: string | null; target: number | null };
    users: { name: string };
}

interface SubmissionsPageProps {
    department: Department;
    kpis: Kpi[];
    submissions: Submission[];
    canSubmit: boolean;
    flash: { error?: string | null; success?: string | null };
    [key: string]: unknown;
}

function SubmitForm({ companyId, departmentId, kpis }: { companyId: string; departmentId: string; kpis: Kpi[] }) {
    const today = new Date().toISOString().slice(0, 10);
    const { data, setData, post, processing, reset } = useForm({
        kpi_id: kpis[0]?.id ?? '',
        value: '',
        submission_date: today,
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/departments/${departmentId}/submissions`, {
            onSuccess: () => setData('value', ''),
        });
    };

    if (kpis.length === 0) {
        return <p className="text-sm text-slate-400 mb-6">No active KPIs to report against yet.</p>;
    }

    return (
        <form onSubmit={submit} className="grid grid-cols-2 gap-3 mb-6 bg-slate-50 rounded-xl p-4">
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">KPI</label>
                <select
                    value={data.kpi_id}
                    onChange={(e) => setData('kpi_id', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    {kpis.map((k) => (
                        <option key={k.id} value={k.id}>
                            {k.name} {k.target !== null ? `(target ${k.target}${k.unit ?? ''})` : ''}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Value</label>
                <input
                    value={data.value}
                    onChange={(e) => setData('value', e.target.value)}
                    type="number"
                    step="any"
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    required
                />
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Date</label>
                <input
                    value={data.submission_date}
                    onChange={(e) => setData('submission_date', e.target.value)}
                    type="date"
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    required
                />
            </div>
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">Notes (optional)</label>
                <input
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />
            </div>
            <div className="col-span-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-[#06142f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0b1f49] disabled:opacity-60"
                >
                    Submit
                </button>
            </div>
        </form>
    );
}

export default function SubmissionsIndex({ department, kpis, submissions, canSubmit }: SubmissionsPageProps) {
    const { flash } = usePage<SubmissionsPageProps>().props;

    return (
        <>
            <Head title={`${department.name} — KPI Submissions`} />

            <div className="min-h-screen bg-slate-50 p-8">
                <div className="max-w-3xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-lg font-bold text-slate-900">{department.name} — KPI Submissions</h1>
                        <Link
                            href={`/platform/companies/${department.company_id}/departments`}
                            className="text-sm font-semibold text-[#06142f] hover:underline"
                        >
                            ← Back to departments
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
                        {canSubmit ? (
                            <SubmitForm companyId={department.company_id} departmentId={department.id} kpis={kpis} />
                        ) : (
                            <p className="text-xs text-slate-400 mb-4">
                                You can view this department's submissions but are not assigned to it, so you cannot submit.
                            </p>
                        )}

                        {submissions.length === 0 ? (
                            <p className="text-sm text-slate-400">No submissions yet.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {submissions.map((s) => (
                                    <li key={s.id} className="py-3 flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-semibold text-slate-800">
                                                {s.kpis.name}: {s.value}
                                                {s.kpis.unit ?? ''}
                                                {s.kpis.target !== null && (
                                                    <span className="text-xs text-slate-400 ml-2">
                                                        (target {s.kpis.target}
                                                        {s.kpis.unit ?? ''})
                                                    </span>
                                                )}
                                            </p>
                                            <p className="text-xs text-slate-400">
                                                {s.submission_date} · by {s.users.name}
                                                {s.notes ? ` · ${s.notes}` : ''}
                                            </p>
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
