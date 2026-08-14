export interface CategoryTheme {
    headerBg: string;
    icon: string;
    catPill: string;
    subPill: string;
    border: string;
}

export const CATEGORY_ORDER = ['Financial', 'Growth & Customer', 'Initiatives', 'People'];

export const CATEGORY_THEMES: Record<string, CategoryTheme> = {
    Financial: { headerBg: 'from-emerald-800 to-emerald-600', icon: '💰', catPill: 'bg-emerald-700 text-white', subPill: 'bg-emerald-100 text-emerald-700', border: 'border-l-emerald-500' },
    'Growth & Customer': { headerBg: 'from-indigo-800 to-indigo-600', icon: '📈', catPill: 'bg-indigo-700 text-white', subPill: 'bg-indigo-100 text-indigo-700', border: 'border-l-indigo-500' },
    Initiatives: { headerBg: 'from-amber-700 to-amber-500', icon: '🚀', catPill: 'bg-amber-600 text-white', subPill: 'bg-amber-100 text-amber-700', border: 'border-l-amber-500' },
    People: { headerBg: 'from-pink-800 to-pink-600', icon: '👥', catPill: 'bg-pink-700 text-white', subPill: 'bg-pink-100 text-pink-700', border: 'border-l-pink-500' },
};

export const CATEGORY_THEME_DEFAULT: CategoryTheme = {
    headerBg: 'from-slate-700 to-slate-600',
    icon: '📌',
    catPill: 'bg-slate-600 text-white',
    subPill: 'bg-slate-100 text-slate-600',
    border: 'border-l-slate-400',
};

export function categoryThemeFor(category: string): CategoryTheme {
    return CATEGORY_THEMES[category] ?? CATEGORY_THEME_DEFAULT;
}
