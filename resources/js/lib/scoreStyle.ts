export interface ScoreStyle {
    bar: string;
    text: string;
    badge: string;
    label: string;
    hex: string;
}

export function scoreStyle(s: number): ScoreStyle {
    const score = Number(s);
    if (score <= 25) {
        return { bar: 'bg-red-600', text: 'text-red-700', badge: 'bg-red-50 text-red-700 border-red-100', label: 'Critical', hex: '#ef4444' };
    }
    if (score <= 50) {
        return {
            bar: 'bg-gradient-to-r from-red-600 to-orange-500',
            text: 'text-orange-700',
            badge: 'bg-orange-50 text-orange-700 border-orange-100',
            label: 'Risk',
            hex: '#f97316',
        };
    }
    if (score <= 75) {
        return {
            bar: 'bg-gradient-to-r from-orange-500 to-yellow-400',
            text: 'text-amber-700',
            badge: 'bg-amber-50 text-amber-700 border-amber-100',
            label: 'Watch',
            hex: '#f59e0b',
        };
    }
    if (score <= 100) {
        return {
            bar: 'bg-gradient-to-r from-yellow-400 to-emerald-600',
            text: 'text-emerald-700',
            badge: 'bg-emerald-50 text-emerald-800 border-emerald-100',
            label: 'Good',
            hex: '#10b981',
        };
    }
    return { bar: 'bg-emerald-700', text: 'text-emerald-800', badge: 'bg-emerald-50 text-emerald-800 border-emerald-100', label: 'Exceeded', hex: '#059669' };
}

export function scoreHex(v: number): string {
    if (v <= 25) return '#ef4444';
    if (v <= 50) return '#f97316';
    if (v <= 75) return '#f59e0b';
    if (v <= 100) return '#10b981';
    return '#059669';
}
