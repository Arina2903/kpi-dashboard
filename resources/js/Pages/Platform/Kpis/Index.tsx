import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface Company {
    id: string;
    name: string;
    code: string;
}

interface Category {
    id: string;
    name: string;
}

interface Kpi {
    id: string;
    name: string;
    description: string | null;
    target: number | null;
    unit: string | null;
    frequency: string;
    status: string;
    kpi_categories: { name: string } | null;
}

interface KpisPageProps {
    company: Company;
    categories: Category[];
    kpis: Kpi[];
    flash: { error?: string | null; success?: string | null };
    [key: string]: unknown;
}

function CreateCategoryForm({ companyId }: { companyId: string }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm({ name: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/kpi-categories`, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <button onClick={() => setOpen(true)} className="text-xs font-semibold text-[#06142f] hover:underline mb-4">
                + Add category
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="flex items-end gap-2 mb-4">
            <input
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                placeholder="Category name"
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs"
                required
                autoFocus
            />
            <button
                type="submit"
                disabled={processing}
                className="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
            >
                Save
            </button>
            <button type="button" onClick={() => setOpen(false)} className="text-xs text-slate-400">
                Cancel
            </button>
        </form>
    );
}

function CreateKpiForm({ companyId, categories }: { companyId: string; categories: Category[] }) {
    const { data, setData, post, processing, reset } = useForm({
        category_id: '',
        name: '',
        description: '',
        target: '',
        unit: '',
        frequency: 'monthly',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/kpis`, { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-2 gap-3 mb-6 bg-slate-50 rounded-xl p-4">
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">KPI name</label>
                <input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Customer Satisfaction Score"
                    required
                />
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Category</label>
                <select
                    value={data.category_id}
                    onChange={(e) => setData('category_id', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="">None</option>
                    {categories.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Frequency</label>
                <select
                    value={data.frequency}
                    onChange={(e) => setData('frequency', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Target</label>
                <input
                    value={data.target}
                    onChange={(e) => setData('target', e.target.value)}
                    type="number"
                    step="any"
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="90"
                />
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Unit</label>
                <input
                    value={data.unit}
                    onChange={(e) => setData('unit', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="%"
                />
            </div>
            <div className="col-span-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-[#06142f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0b1f49] disabled:opacity-60"
                >
                    Create KPI
                </button>
            </div>
        </form>
    );
}

export default function KpisIndex({ company, categories, kpis }: KpisPageProps) {
    const { flash } = usePage<KpisPageProps>().props;

    return (
        <>
            <Head title={`${company.name} — KPIs`} />

            <div className="min-h-screen bg-slate-50 p-8">
                <div className="max-w-3xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-lg font-bold text-slate-900">{company.name} — KPIs</h1>
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
                        <CreateCategoryForm companyId={company.id} />
                        <CreateKpiForm companyId={company.id} categories={categories} />

                        {kpis.length === 0 ? (
                            <p className="text-sm text-slate-400">No KPIs yet.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {kpis.map((kpi) => (
                                    <li key={kpi.id} className="py-3 flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-semibold text-slate-800">{kpi.name}</p>
                                            <p className="text-xs text-slate-400">
                                                {kpi.kpi_categories?.name ?? 'Uncategorized'} · {kpi.frequency}
                                                {kpi.target !== null ? ` · target ${kpi.target}${kpi.unit ?? ''}` : ''}
                                            </p>
                                        </div>
                                        <span className="text-xs font-semibold text-emerald-600 uppercase">{kpi.status}</span>
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
