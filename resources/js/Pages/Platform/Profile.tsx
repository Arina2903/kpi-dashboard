import { router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { FormEventHandler, useState } from 'react';
import PasswordInput from '@/Components/PasswordInput';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Badge, Card, PrimaryButton } from '@/Components/Platform/ui';

const PLATFORM_PASSWORD_INPUT_CLASS = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm pr-10';

interface PlatformUser {
    id: string;
    name: string;
    email: string;
    is_super_admin: boolean;
    company_memberships: Array<{
        company_id: string;
        role: string;
        companies: { name: string; code: string };
    }>;
}

interface TelegramLinkState {
    linked: boolean;
    username: string | null;
}

interface ProfilePageProps {
    me: PlatformUser;
    telegram: TelegramLinkState;
    flash: { error?: string | null; success?: string | null };
    [key: string]: unknown;
}

const ROLE_LABEL: Record<string, string> = {
    company_admin: 'Company Admin',
    department_admin: 'Department Admin',
    department_user: 'Department User',
};

function ChangePasswordForm() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/platform/profile/password', { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="space-y-4 max-w-sm">
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">New password</label>
                <PasswordInput
                    name="password"
                    value={data.password}
                    onChange={(v) => setData('password', v)}
                    minLength={8}
                    className={PLATFORM_PASSWORD_INPUT_CLASS}
                    iconHoverClassName="hover:text-slate-700"
                />
                {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
            </div>
            <div>
                <label className="block text-xs font-medium text-slate-600 mb-1">Confirm new password</label>
                <PasswordInput
                    name="password_confirmation"
                    value={data.password_confirmation}
                    onChange={(v) => setData('password_confirmation', v)}
                    minLength={8}
                    className={PLATFORM_PASSWORD_INPUT_CLASS}
                    iconHoverClassName="hover:text-slate-700"
                />
            </div>
            <PrimaryButton type="submit" disabled={processing}>
                Update password
            </PrimaryButton>
        </form>
    );
}

function TelegramSection({ telegram }: { telegram: TelegramLinkState }) {
    const [code, setCode] = useState<string | null>(null);
    const [deepLink, setDeepLink] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const generateCode = async () => {
        setLoading(true);
        try {
            const { data } = await axios.post('/platform/telegram/link-code');
            setCode(data.code);
            setDeepLink(data.bot_deep_link);
        } finally {
            setLoading(false);
        }
    };

    const disconnect = () => {
        router.post('/platform/telegram/disconnect', {}, { onSuccess: () => setCode(null) });
    };

    return (
        <Card title="Telegram" className="mb-6">
            {telegram.linked ? (
                <div>
                    <p className="text-sm text-slate-600 mb-3">
                        Connected as <span className="font-semibold text-slate-800">@{telegram.username}</span>. You'll
                        receive KPI reminders here, scoped to exactly what you're authorized to see.
                    </p>
                    <button onClick={disconnect} className="text-sm font-semibold text-red-600 hover:underline">
                        Disconnect Telegram
                    </button>
                </div>
            ) : (
                <div>
                    <p className="text-sm text-slate-500 mb-3">
                        Connect Telegram to get KPI reminders — every message is generated from your own authorized
                        data, never another company's.
                    </p>
                    {code && deepLink ? (
                        <div className="rounded-lg bg-slate-50 px-4 py-3 text-sm">
                            <p className="text-slate-600 mb-2">
                                Open Telegram and tap the link below (valid for 10 minutes):
                            </p>
                            <a href={deepLink} target="_blank" rel="noreferrer" className="font-semibold text-brand-900 hover:underline break-all">
                                {deepLink}
                            </a>
                        </div>
                    ) : (
                        <PrimaryButton onClick={generateCode} disabled={loading}>
                            {loading ? 'Generating…' : 'Connect Telegram'}
                        </PrimaryButton>
                    )}
                </div>
            )}
        </Card>
    );
}

export default function Profile({ me, telegram }: ProfilePageProps) {
    return (
        <PlatformLayout title="My Profile" description="Your account, your access level, and your notification preferences.">
            <Card title="Account" className="mb-6">
                <dl className="grid grid-cols-2 gap-y-2 text-sm max-w-sm">
                    <dt className="text-slate-400">Name</dt>
                    <dd className="text-slate-800 font-medium">{me.name}</dd>
                    <dt className="text-slate-400">Email</dt>
                    <dd className="text-slate-800 font-medium">{me.email}</dd>
                    <dt className="text-slate-400">Access level</dt>
                    <dd>
                        <Badge tone={me.is_super_admin ? 'brand' : 'neutral'}>
                            {me.is_super_admin ? 'Richworks Super Admin' : 'Company user'}
                        </Badge>
                    </dd>
                </dl>
            </Card>

            <TelegramSection telegram={telegram} />

            {!me.is_super_admin && (
                <Card title="Company memberships" className="mb-6">
                    {me.company_memberships.length === 0 ? (
                        <p className="text-sm text-slate-400">No company memberships.</p>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {me.company_memberships.map((m) => (
                                <li key={m.company_id} className="py-2 flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">{m.companies.name}</p>
                                        <p className="text-xs text-slate-400">{m.companies.code}</p>
                                    </div>
                                    <Badge>{ROLE_LABEL[m.role] ?? m.role}</Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            )}

            <Card title="Change password">
                <ChangePasswordForm />
            </Card>
        </PlatformLayout>
    );
}
