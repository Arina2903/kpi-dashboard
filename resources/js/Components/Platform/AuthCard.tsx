import { ReactNode } from 'react';

/**
 * Shared shell for the three pages someone sees before they're ever
 * authenticated (Login, Forgot Password, Set Password) — no sidebar makes
 * sense here (there's nothing to navigate to yet), but they should still
 * look like the same product as everything behind them, not three
 * independently-styled forms that happen to sit next to each other in the
 * codebase.
 */
export default function AuthCard({ title, description, children, footer }: { title: string; description?: string; children: ReactNode; footer?: ReactNode }) {
    return (
        <div className="min-h-screen bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 flex items-center justify-center p-6">
            <div className="w-full max-w-sm">
                <div className="flex items-center justify-center gap-2.5 mb-6">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-base font-bold text-white">P</span>
                    <span className="text-white font-bold text-lg">Performix</span>
                </div>

                <div className="bg-white rounded-2xl shadow-xl p-7">
                    <div className="mb-6">
                        <h1 className="text-base font-bold text-slate-900">{title}</h1>
                        {description && <p className="text-xs text-slate-500 mt-1">{description}</p>}
                    </div>
                    {children}
                </div>

                {footer && <div className="mt-5 text-center text-xs text-slate-300">{footer}</div>}
            </div>
        </div>
    );
}
