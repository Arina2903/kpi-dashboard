import { router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Badge, Card, EmptyState, InfoTooltip, PrimaryButton, SecondaryButton } from '@/Components/Platform/ui';
import { ChecklistIcon, PlusIcon } from '@/Components/Platform/Icons';

interface Company {
    id: string;
    name: string;
    code: string;
}

interface Person {
    name: string;
    email: string;
}

interface Task {
    id: string;
    title: string;
    description: string | null;
    status: 'open' | 'in_progress' | 'done' | 'cancelled';
    priority: 'low' | 'medium' | 'high';
    due_date: string | null;
    assignee_user_id: string | null;
    created_by: string;
    assignee: Person | null;
    creator: Person | null;
}

interface TaskKpiLink {
    id: string;
    task_id: string;
    kpi_id: string;
    kpis: { name: string } | null;
}

interface Kpi {
    id: string;
    name: string;
}

interface Member {
    user_id: string;
    users: Person;
}

interface TasksPageProps {
    company: Company;
    tasks: Task[];
    links: TaskKpiLink[];
    kpis: Kpi[];
    members: Member[];
    [key: string]: unknown;
}

const STATUS_LABEL: Record<Task['status'], string> = {
    open: 'Open',
    in_progress: 'In progress',
    done: 'Done',
    cancelled: 'Cancelled',
};

const STATUS_TONE: Record<Task['status'], 'neutral' | 'info' | 'success' | 'danger'> = {
    open: 'neutral',
    in_progress: 'info',
    done: 'success',
    cancelled: 'danger',
};

const PRIORITY_TONE: Record<Task['priority'], 'neutral' | 'warning' | 'danger'> = {
    low: 'neutral',
    medium: 'warning',
    high: 'danger',
};

function KpiCheckboxList({ kpis, selected, onToggle }: { kpis: Kpi[]; selected: string[]; onToggle: (kpiId: string) => void }) {
    if (kpis.length === 0) {
        return <p className="text-xs text-slate-400">No KPIs exist for this company yet.</p>;
    }

    return (
        <div className="max-h-40 overflow-y-auto rounded-lg border border-slate-200 p-2 space-y-1">
            {kpis.map((kpi) => (
                <label key={kpi.id} className="flex items-center gap-2 text-xs text-slate-700 px-1 py-0.5 rounded hover:bg-slate-50">
                    <input type="checkbox" checked={selected.includes(kpi.id)} onChange={() => onToggle(kpi.id)} />
                    {kpi.name}
                </label>
            ))}
        </div>
    );
}

function CreateTaskPanel({ companyId, kpis, members }: { companyId: string; kpis: Kpi[]; members: Member[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        title: '',
        description: '',
        priority: 'medium',
        due_date: '',
        assignee_user_id: '',
        kpi_ids: [] as string[],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${companyId}/tasks`, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const toggleKpi = (kpiId: string) => {
        setData('kpi_ids', data.kpi_ids.includes(kpiId) ? data.kpi_ids.filter((id) => id !== kpiId) : [...data.kpi_ids, kpiId]);
    };

    if (!open) {
        return (
            <PrimaryButton onClick={() => setOpen(true)} className="mb-5 inline-flex items-center gap-1.5">
                <PlusIcon className="w-4 h-4" /> New task
            </PrimaryButton>
        );
    }

    return (
        <form onSubmit={submit} className="grid grid-cols-2 gap-3 mb-5 bg-slate-50 rounded-xl p-4">
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">Task title</label>
                <input
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Follow up with client on renewal"
                    required
                />
            </div>
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">Description</label>
                <textarea
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    rows={2}
                />
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Priority</label>
                <select value={data.priority} onChange={(e) => setData('priority', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Due date</label>
                <input
                    type="date"
                    value={data.due_date}
                    onChange={(e) => setData('due_date', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />
            </div>
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">Assign to</label>
                <select
                    value={data.assignee_user_id}
                    onChange={(e) => setData('assignee_user_id', e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="">Unassigned</option>
                    {members.map((m) => (
                        <option key={m.user_id} value={m.user_id}>
                            {m.users.name} ({m.users.email})
                        </option>
                    ))}
                </select>
            </div>
            <div className="col-span-2">
                <label className="flex items-center gap-1.5 text-xs font-medium text-slate-600 mb-1">
                    Link to KPI(s) — optional
                    <InfoTooltip text="Linking is for visibility only — it never changes a KPI's value." />
                </label>
                <KpiCheckboxList kpis={kpis} selected={data.kpi_ids} onToggle={toggleKpi} />
            </div>
            <div className="col-span-2 flex items-center gap-2">
                <PrimaryButton type="submit" disabled={processing}>
                    Create task
                </PrimaryButton>
                <button type="button" onClick={() => setOpen(false)} className="text-sm text-slate-400">
                    Cancel
                </button>
            </div>
        </form>
    );
}

function EditTaskForm({ companyId, task, onDone }: { companyId: string; task: Task; onDone: () => void }) {
    const { data, setData, patch, processing } = useForm({
        title: task.title,
        description: task.description ?? '',
        status: task.status,
        priority: task.priority,
        due_date: task.due_date ?? '',
        assignee_user_id: task.assignee_user_id ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/platform/companies/${companyId}/tasks/${task.id}`, { onSuccess: onDone });
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-2 gap-3 mt-3 mb-2 bg-slate-50 rounded-xl p-4">
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">Task title</label>
                <input value={data.title} onChange={(e) => setData('title', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required />
            </div>
            <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1">Description</label>
                <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" rows={2} />
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select value={data.status} onChange={(e) => setData('status', e.target.value as Task['status'])} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="open">Open</option>
                    <option value="in_progress">In progress</option>
                    <option value="done">Done</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Priority</label>
                <select value={data.priority} onChange={(e) => setData('priority', e.target.value as Task['priority'])} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div className="col-span-2 flex items-center gap-2">
                <PrimaryButton type="submit" disabled={processing}>
                    Save changes
                </PrimaryButton>
                <button type="button" onClick={onDone} className="text-sm text-slate-400">
                    Cancel
                </button>
            </div>
        </form>
    );
}

function EditKpiLinksForm({ companyId, task, kpis, linkedKpiIds, onDone }: { companyId: string; task: Task; kpis: Kpi[]; linkedKpiIds: string[]; onDone: () => void }) {
    const [selected, setSelected] = useState<string[]>(linkedKpiIds);
    const [processing, setProcessing] = useState(false);

    const toggleKpi = (kpiId: string) => {
        setSelected((prev) => (prev.includes(kpiId) ? prev.filter((id) => id !== kpiId) : [...prev, kpiId]));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.put(
            `/platform/companies/${companyId}/tasks/${task.id}/kpi-links`,
            { kpi_ids: selected },
            { onFinish: () => setProcessing(false), onSuccess: onDone },
        );
    };

    return (
        <form onSubmit={submit} className="mt-3 bg-slate-50 rounded-xl p-4">
            <KpiCheckboxList kpis={kpis} selected={selected} onToggle={toggleKpi} />
            <div className="flex items-center gap-2 mt-3">
                <PrimaryButton type="submit" disabled={processing}>
                    Save links
                </PrimaryButton>
                <button type="button" onClick={onDone} className="text-sm text-slate-400">
                    Cancel
                </button>
            </div>
        </form>
    );
}

function TaskRow({ task, company, kpis, links }: { task: Task; company: Company; kpis: Kpi[]; links: TaskKpiLink[] }) {
    const [editing, setEditing] = useState(false);
    const [editingLinks, setEditingLinks] = useState(false);
    const taskLinks = links.filter((l) => l.task_id === task.id);

    const destroy = () => {
        if (confirm(`Delete "${task.title}"? This cannot be undone.`)) {
            router.delete(`/platform/companies/${company.id}/tasks/${task.id}`);
        }
    };

    return (
        <li className="py-4">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="text-sm font-bold text-slate-800">{task.title}</p>
                    {task.description && <p className="text-xs text-slate-500 mt-0.5">{task.description}</p>}
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                        <Badge tone={STATUS_TONE[task.status]}>{STATUS_LABEL[task.status]}</Badge>
                        <Badge tone={PRIORITY_TONE[task.priority]}>{task.priority}</Badge>
                        {task.due_date && <span className="text-xs text-slate-400">Due {task.due_date}</span>}
                        <span className="text-xs text-slate-400">{task.assignee ? `Assigned to ${task.assignee.name}` : 'Unassigned'}</span>
                    </div>
                    {taskLinks.length > 0 && (
                        <div className="flex flex-wrap items-center gap-1.5 mt-2">
                            {taskLinks.map((l) => (
                                <Badge key={l.id} tone="brand">
                                    {l.kpis?.name ?? 'KPI'}
                                </Badge>
                            ))}
                        </div>
                    )}
                </div>
                <div className="flex-none flex items-center gap-3">
                    <button onClick={() => setEditingLinks((v) => !v)} className="text-xs font-semibold text-brand-800 hover:underline">
                        {editingLinks ? 'Close links' : 'Edit KPI links'}
                    </button>
                    <button onClick={() => setEditing((v) => !v)} className="text-xs font-semibold text-brand-800 hover:underline">
                        {editing ? 'Close' : 'Edit'}
                    </button>
                    <button onClick={destroy} className="text-xs font-semibold text-red-600 hover:underline">
                        Delete
                    </button>
                </div>
            </div>
            {editing && <EditTaskForm companyId={company.id} task={task} onDone={() => setEditing(false)} />}
            {editingLinks && (
                <EditKpiLinksForm
                    companyId={company.id}
                    task={task}
                    kpis={kpis}
                    linkedKpiIds={taskLinks.map((l) => l.kpi_id)}
                    onDone={() => setEditingLinks(false)}
                />
            )}
        </li>
    );
}

export default function TasksIndex({ company, tasks, links, kpis, members }: TasksPageProps) {
    return (
        <PlatformLayout
            title="Tasks"
            description="Day-to-day work for this company, optionally linked to a KPI for visibility — linking never changes a KPI's value."
            company={company}
        >
            <Card>
                <div className="flex flex-wrap items-center justify-between gap-3 mb-2">
                    <CreateTaskPanel companyId={company.id} kpis={kpis} members={members} />
                </div>

                {tasks.length === 0 ? (
                    <EmptyState
                        icon={<ChecklistIcon className="w-10 h-10" />}
                        title="No tasks yet"
                        description="Create one above to start tracking day-to-day work, optionally aligned to a KPI."
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {tasks.map((task) => (
                            <TaskRow key={task.id} task={task} company={company} kpis={kpis} links={links} />
                        ))}
                    </ul>
                )}
            </Card>
        </PlatformLayout>
    );
}
