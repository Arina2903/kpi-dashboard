import { useState } from 'react';

interface PasswordInputProps {
    name: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    minLength?: number;
    required?: boolean;
    /** Overrides the input's own styling — defaults to this component's original (legacy-app) look so existing callers are unaffected. */
    className?: string;
    /** Overrides the show/hide icon button's hover color to match the caller's own accent. */
    iconHoverClassName?: string;
    autoFocus?: boolean;
}

export default function PasswordInput({
    name,
    value,
    onChange,
    placeholder,
    minLength,
    required = true,
    className,
    iconHoverClassName,
    autoFocus,
}: PasswordInputProps) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative">
            <input
                type={visible ? 'text' : 'password'}
                name={name}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder ?? ''}
                minLength={minLength}
                required={required}
                autoFocus={autoFocus}
                className={
                    className ??
                    'w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-[12px] focus:ring-2 focus:ring-[#6B9080]/40 focus:border-[#6B9080] focus:outline-none pr-10'
                }
            />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                className={`absolute right-0 top-0 h-full px-3 flex items-center text-slate-400 transition ${iconHoverClassName ?? 'hover:text-[#1a3d34]'}`}
                aria-label="Show password"
                tabIndex={-1}
            >
                {visible ? (
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                        />
                    </svg>
                ) : (
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                        />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                )}
            </button>
        </div>
    );
}
