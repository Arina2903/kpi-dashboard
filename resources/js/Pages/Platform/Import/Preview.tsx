import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Card, PrimaryButton } from '@/Components/Platform/ui';

interface RowError {
    row: number | null;
    message: string;
}

interface SectionResult {
    valid: Array<Record<string, unknown>>;
    errors: RowError[];
}

interface Company {
    id: string;
    name: string;
}

interface ImportPreviewPageProps {
    company: Company;
    token: string;
    filename: string;
    result: {
        departments?: SectionResult;
        employees?: SectionResult;
        kpis?: SectionResult;
    };
    [key: string]: unknown;
}

const SECTION_LABEL: Record<string, string> = {
    departments: 'Departments',
    employees: 'Employees',
    kpis: 'KPIs',
};

function SectionCard({ sectionKey, section }: { sectionKey: string; section: SectionResult }) {
    return (
        <Card className="mb-4">
            <div className="flex items-center justify-between mb-3">
                <h2 className="text-sm font-bold text-slate-700">{SECTION_LABEL[sectionKey] ?? sectionKey}</h2>
                <p className="text-xs tabular-nums">
                    <span className="font-semibold text-emerald-600">{section.valid.length} valid</span>
                    {section.errors.length > 0 && <span className="font-semibold text-amber-600 ml-2">{section.errors.length} errors</span>}
                </p>
            </div>

            {section.errors.length > 0 && (
                <div className="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 mb-3">
                    {section.errors.map((e, i) => (
                        <p key={i} className="text-xs text-amber-700">
                            {e.row !== null ? `Row ${e.row}: ` : ''}
                            {e.message}
                        </p>
                    ))}
                </div>
            )}

            {section.valid.length === 0 ? (
                <p className="text-xs text-slate-400">Nothing here will be imported.</p>
            ) : (
                <p className="text-xs text-slate-400">
                    {section.valid.length} row{section.valid.length === 1 ? '' : 's'} ready to import.
                </p>
            )}
        </Card>
    );
}

export default function ImportPreview({ company, token, filename, result }: ImportPreviewPageProps) {
    const { post, processing } = useForm({ token });

    const confirm: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/platform/companies/${company.id}/import/confirm`);
    };

    const sections = Object.entries(result) as Array<[string, SectionResult]>;
    const totalValid = sections.reduce((sum, [, s]) => sum + s.valid.length, 0);
    const totalErrors = sections.reduce((sum, [, s]) => sum + s.errors.length, 0);

    return (
        <PlatformLayout title="Import Preview" description={filename} company={company} maxWidth="max-w-2xl">
            <Card className="mb-6">
                <p className="text-sm text-slate-600">
                    <span className="font-semibold text-emerald-600 tabular-nums">{totalValid} valid</span> ·{' '}
                    <span className="font-semibold text-amber-600 tabular-nums">{totalErrors} errors</span> across {sections.length} sheet
                    {sections.length === 1 ? '' : 's'}. Nothing has been written to the database yet.
                </p>
            </Card>

            {sections.map(([key, section]) => (
                <SectionCard key={key} sectionKey={key} section={section} />
            ))}

            <form onSubmit={confirm} className="flex items-center gap-3">
                <PrimaryButton type="submit" disabled={processing || totalValid === 0} className="px-5 py-2.5">
                    Confirm import
                </PrimaryButton>
                <Link href={`/platform/companies/${company.id}/import`} className="text-sm font-semibold text-slate-500 hover:underline">
                    Cancel
                </Link>
            </form>
        </PlatformLayout>
    );
}
