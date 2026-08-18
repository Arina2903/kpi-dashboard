import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import AuthCard from '@/Components/Platform/AuthCard';

interface ForgotPasswordPageProps {
    flash: {
        error?: string | null;
        success?: string | null;
    };
    [key: string]: unknown;
}

export default function ForgotPassword() {
    const { flash } = usePage<ForgotPasswordPageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/platform/forgot-password');
    };

    return (
        <>
            <Head title="Reset Password" />

            <AuthCard
                title="Reset your password"
                description="Enter your email and we'll send you a link to choose a new password."
                footer={
                    <Link href="/platform/login" className="font-semibold text-white hover:underline">
                        Back to sign in
                    </Link>
                }
            >
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

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-xl bg-brand-900 py-3 text-sm font-semibold text-white hover:bg-brand-800 transition disabled:opacity-60"
                    >
                        Send reset link
                    </button>
                </form>
            </AuthCard>
        </>
    );
}
