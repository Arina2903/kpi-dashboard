import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Badge, Card, EmptyState, PrimaryButton } from '@/Components/Platform/ui';
import { UploadIcon } from '@/Components/Platform/Icons';

interface Company {
    id: string;
    name: string;
    code: string;
}

interface Batch {
    id: string;
    filename: string;
    type: string;
    status: string;
    total_rows: number;
    successful_rows: number;
    failed_rows: number;
    created_at: string;
    completed_at: string | null;
}

interface ImportShowPageProps {
    company: Company;
    batches: Batch[];
    [key: string]: unknown;
}

const STATUS_TONE: Record<string, 'success' | 'info' | 'danger' | 'neutral'> = {
    completed: 'success',
    validated: 'info',
    failed: 'danger',
    pending: 'neutral',
};

function UploadForm({ companyId }: { companyId: string }) {
    const { data, setData, post, processing, errors } = useForm<{ type: string; file: File | null }>({
        type: 'workbook',
        file: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/import/preview`, { forceFormData: true });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">What are you importing?</label>
                <select value={data.type} onChange={(e) => setData('type', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="workbook">Full workbook (Departments + Employees + KPIs sheets)</option>
                    <option value="departments">Departments only</option>
                    <option value="employees">Employees only</option>
                    <option value="kpis">KPIs only</option>
                </select>
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">File (.xlsx or .csv)</label>
                <input
                    type="file"
                    accept=".xlsx,.csv"
                    onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    required
                />
                {errors.file && <p className="mt-1 text-xs text-red-600">{errors.file}</p>}
            </div>
            <PrimaryButton type="submit" disabled={processing || !data.file}>
                Preview import
            </PrimaryButton>
            <p className="text-xs text-slate-400">
                Nothing is written to the database yet — the next screen shows exactly what would be imported, with
                any row errors, before anything is committed.
            </p>
        </form>
    );
}

export default function ImportShow({ company, batches }: ImportShowPageProps) {
    return (
        <PlatformLayout title="Import Data" description="Bring in departments, employees, or KPIs from a spreadsheet — nothing commits until you review it." company={company} maxWidth="max-w-2xl">
            <Card title="Upload" className="mb-6">
                <UploadForm companyId={company.id} />
            </Card>

            <Card title="Import history">
                {batches.length === 0 ? (
                    <EmptyState icon={<UploadIcon className="w-10 h-10" />} title="No imports yet" description="Upload a spreadsheet above to get started." />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {batches.map((b) => (
                            <li key={b.id} className="py-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">{b.filename}</p>
                                        <p className="text-xs text-slate-400">
                                            {b.type} · {new Date(b.created_at).toLocaleString()}
                                        </p>
                                    </div>
                                    <Badge tone={STATUS_TONE[b.status] ?? 'neutral'}>{b.status}</Badge>
                                </div>
                                <p className="text-xs text-slate-500 mt-1 tabular-nums">
                                    {b.successful_rows} succeeded · {b.failed_rows} failed · {b.total_rows} total
                                </p>
                                {b.type === 'employees' && b.status !== 'completed' && (
                                    <Link href={`/platform/companies/${company.id}/import/${b.id}/users`} className="text-xs font-semibold text-brand-800 hover:underline">
                                        Create accounts →
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </PlatformLayout>
    );
}
