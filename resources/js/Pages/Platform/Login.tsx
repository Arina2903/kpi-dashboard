import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PasswordInput from '@/Components/PasswordInput';
import AuthCard from '@/Components/Platform/AuthCard';

const PLATFORM_PASSWORD_INPUT_CLASS =
    'w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:ring-2 focus:ring-slate-800 focus:outline-none pr-10';

interface PlatformLoginPageProps {
    flash: {
        error?: string | null;
        success?: string | null;
    };
    [key: string]: unknown;
}

export default function PlatformLogin() {
    const { flash } = usePage<PlatformLoginPageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/platform/login');
    };

    return (
        <>
            <Head title="Sign in" />

            <AuthCard title="Welcome back" description="Sign in to your KPI Platform">
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
                {errors.email && (
                    <div className="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        {errors.email}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:ring-2 focus:ring-slate-800 focus:outline-none"
                            required
                            autoFocus
                        />
                    </div>

                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="block text-sm font-medium text-slate-700">Password</label>
                            <Link href="/platform/forgot-password" className="text-xs font-medium text-slate-500 hover:underline">
                                Forgot password?
                            </Link>
                        </div>
                        <PasswordInput
                            name="password"
                            value={data.password}
                            onChange={(v) => setData('password', v)}
                            className={PLATFORM_PASSWORD_INPUT_CLASS}
                            iconHoverClassName="hover:text-slate-700"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-xl bg-brand-900 py-3 text-sm font-semibold text-white hover:bg-brand-800 transition disabled:opacity-60"
                    >
                        Sign in
                    </button>
                </form>
            </AuthCard>
        </>
    );
}
