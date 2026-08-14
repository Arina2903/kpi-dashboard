import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface SetPasswordPageProps {
    email: string | null;
    flash: { error?: string | null; success?: string | null };
    errors: { password?: string };
    [key: string]: unknown;
}

export default function SetPassword({ email }: SetPasswordPageProps) {
    const { flash, errors } = usePage<SetPasswordPageProps>().props;
    const { data, setData, post, processing } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/platform/invite/set-password');
    };

    return (
        <>
            <Head title="Set Your Password" />

            <div className="min-h-screen bg-slate-100 flex items-center justify-center p-6">
                <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg p-7">
                    <div className="mb-6">
                        <h1 className="text-base font-bold text-slate-900">Welcome to Performix</h1>
                        <p className="text-xs text-slate-500">
                            {email ? `Set a password for ${email} to finish setting up your account.` : 'Set a password to finish setting up your account.'}
                        </p>
                    </div>

                    {flash.error && (
                        <div className="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            {flash.error}
                        </div>
                    )}
                    {errors.password && (
                        <div className="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            {errors.password}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">New password</label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:ring-2 focus:ring-slate-800 focus:outline-none"
                                minLength={8}
                                required
                                autoFocus
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                            <input
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:ring-2 focus:ring-slate-800 focus:outline-none"
                                minLength={8}
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-[#06142f] py-3 text-sm font-semibold text-white hover:bg-[#0b1f49] transition disabled:opacity-60"
                        >
                            Set password &amp; continue
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
