import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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
            <Head title="Platform Login" />

            <div className="min-h-screen bg-slate-100 flex items-center justify-center p-6">
                <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg p-7">
                    <div className="mb-6">
                        <h1 className="text-base font-bold text-slate-900">Multi-Company KPI Platform</h1>
                        <p className="text-xs text-slate-500">Sign in to continue</p>
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
                            <label className="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:ring-2 focus:ring-slate-800 focus:outline-none"
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-[#06142f] py-3 text-sm font-semibold text-white hover:bg-[#0b1f49] transition disabled:opacity-60"
                        >
                            Sign in
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}