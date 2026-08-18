import { router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Card, EmptyState, PrimaryButton } from '@/Components/Platform/ui';
import { DocumentDuplicateIcon } from '@/Components/Platform/Icons';

interface Template {
    id: string;
    name: string;
    description: string | null;
    status: string;
}

interface TemplateItem {
    id: string;
    template_id: string;
    category_name: string | null;
    name: string;
    description: string | null;
    target: number | null;
    unit: string | null;
    frequency: string;
}

interface KpiTemplatesPageProps {
    templates: Template[];
    items: TemplateItem[];
    flash: { error?: string | null; success?: string | null };
    [key: string]: unknown;
}

function CreateTemplateForm() {
    const { data, setData, post, processing, reset } = useForm({ name: '', description: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/platform/kpi-templates', { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="flex items-end gap-3 mb-6">
            <div className="flex-1">
                <label className="block text-xs font-medium text-slate-600 mb-1">Template name</label>
                <input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Sales Team Template"
                    required
                />
            </div>
            <div className="flex-1">
                <label className="block text-xs font-medium text-slate-600 mb-1">Description</label>
                <input
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Optional"
                />
            </div>
            <PrimaryButton type="submit" disabled={processing}>
                Create template
            </PrimaryButton>
        </form>
    );
}

function AddItemForm({ templateId }: { templateId: string }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        category_name: '',
        name: '',
        target: '',
        unit: '',
        frequency: 'monthly',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/kpi-templates/${templateId}/items`, {
            onSuccess: () => reset(),
        });
    };

    if (!open) {
        return (
            <button onClick={() => setOpen(true)} className="text-xs font-semibold text-brand-900 hover:underline">
                + Add item
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="flex items-end gap-2 mt-2 flex-wrap">
            <input
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                placeholder="KPI name"
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs"
                required
            />
            <input
                value={data.category_name}
                onChange={(e) => setData('category_name', e.target.value)}
                placeholder="Category (optional)"
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs"
            />
            <select
                value={data.frequency}
                onChange={(e) => setData('frequency', e.target.value)}
                className="rounded-lg border border-slate-300 px-2 py-1.5 text-xs"
            >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="custom">Custom</option>
            </select>
            <input
                value={data.target}
                onChange={(e) => setData('target', e.target.value)}
                type="number"
                step="any"
                placeholder="Target"
                className="w-24 rounded-lg border border-slate-300 px-3 py-1.5 text-xs"
            />
            <input
                value={data.unit}
                onChange={(e) => setData('unit', e.target.value)}
                placeholder="Unit"
                className="w-20 rounded-lg border border-slate-300 px-3 py-1.5 text-xs"
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

export default function KpiTemplatesIndex({ templates, items }: KpiTemplatesPageProps) {
    const itemsByTemplate = items.reduce<Record<string, TemplateItem[]>>((acc, item) => {
        (acc[item.template_id] ??= []).push(item);
        return acc;
    }, {});

    const deleteTemplate = (template: Template) => {
        if (confirm(`Delete the "${template.name}" template? This cannot be undone.`)) {
            router.delete(`/platform/kpi-templates/${template.id}`);
        }
    };

    const removeItem = (templateId: string, item: TemplateItem) => {
        router.delete(`/platform/kpi-templates/${templateId}/items/${item.id}`);
    };

    return (
        <PlatformLayout title="KPI Templates" description="Reusable KPI sets any company can apply — editing a template afterward never reshapes a company that already applied it.">
            <Card
                title="New template"
                description="Applying a template to a company copies its items into that company's own KPIs — editing a template afterward never changes a company that already applied it."
                className="mb-6"
            >
                <CreateTemplateForm />
            </Card>

            {templates.length === 0 ? (
                <Card>
                    <EmptyState icon={<DocumentDuplicateIcon className="w-10 h-10" />} title="No templates yet" description="Create one above to give every new company a head start." />
                </Card>
            ) : (
                    templates.map((template) => (
                            <div key={template.id} className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-4">
                                <div className="flex items-center justify-between mb-2">
                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">{template.name}</p>
                                        {template.description && (
                                            <p className="text-xs text-slate-400">{template.description}</p>
                                        )}
                                    </div>
                                    <button
                                        onClick={() => deleteTemplate(template)}
                                        className="text-xs font-semibold text-red-600 hover:underline"
                                    >
                                        Delete template
                                    </button>
                                </div>

                                <ul className="divide-y divide-slate-100 mb-2">
                                    {(itemsByTemplate[template.id] ?? []).map((item) => (
                                        <li key={item.id} className="py-2 flex items-center justify-between">
                                            <div>
                                                <p className="text-sm text-slate-700">{item.name}</p>
                                                <p className="text-xs text-slate-400">
                                                    {item.category_name ?? 'Uncategorized'} · {item.frequency}
                                                    {item.target !== null ? ` · target ${item.target}${item.unit ?? ''}` : ''}
                                                </p>
                                            </div>
                                            <button
                                                onClick={() => removeItem(template.id, item)}
                                                className="text-xs text-slate-400 hover:text-red-500"
                                            >
                                                Remove
                                            </button>
                                        </li>
                                    ))}
                                    {(itemsByTemplate[template.id] ?? []).length === 0 && (
                                        <li className="py-2 text-xs text-slate-400">No items yet.</li>
                                    )}
                                </ul>

                                <AddItemForm templateId={template.id} />
                            </div>
                        ))
            )}
        </PlatformLayout>
    );
}
