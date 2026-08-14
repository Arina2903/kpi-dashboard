export const CATEGORY_STYLES: Record<string, { bg: string }> = {
    Financial: { bg: 'bg-emerald-700 text-white' },
    'Growth & Customer': { bg: 'bg-indigo-700 text-white' },
    Initiatives: { bg: 'bg-amber-600 text-white' },
    People: { bg: 'bg-pink-700 text-white' },
    Default: { bg: 'bg-slate-700 text-white' },
};

export function categoryStyleFor(category: string) {
    return CATEGORY_STYLES[category] ?? CATEGORY_STYLES.Default;
}
