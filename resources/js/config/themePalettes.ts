export interface MainPalette {
    name: string;
    category: string;
    bg: string;
    card: string;
    accent: string;
    border: string;
}

export interface SidebarPalette {
    name: string;
    bg: string;
    accent: string;
    text: string;
}

export interface FontFamilyOption {
    key: string;
    label: string;
    fallback: string;
}

export interface FontSizeOption {
    key: 'sm' | 'md' | 'lg';
    label: string;
    zoom: number;
    hint: string;
}

export interface MainTheme {
    bg: string;
    card: string;
    border: string;
    accent: string;
    text: string;
    accent2: string;
}

export interface SidebarTheme {
    bg: string;
    accent: string;
    text: string;
}

export interface FontTheme {
    family: string;
    size: 'sm' | 'md' | 'lg';
}

export const DEFAULT_MAIN_THEME: MainTheme = { bg: '#F5F5F3', card: '#FFFFFF', border: '#6B9080', accent: '#D4AF37', text: '#0F172A', accent2: '#6B9080' };
export const DEFAULT_SIDEBAR_THEME: SidebarTheme = { bg: '#111111', accent: '#D4AF37', text: '#FFFFFF' };
export const DEFAULT_FONT_THEME: FontTheme = { family: 'Inter', size: 'md' };

export const SIDEBAR_PALETTES: SidebarPalette[] = [
    { name: 'Midnight Gold', bg: '#111111', accent: '#D4AF37', text: '#FFFFFF' },
    { name: 'Espresso Bronze', bg: '#2A1D16', accent: '#D4AF37', text: '#F5EFE6' },
    { name: 'Navy Ice', bg: '#0F1B2D', accent: '#38BDF8', text: '#E8F2FA' },
    { name: 'Forest Night', bg: '#0F231A', accent: '#34D399', text: '#E7F5EE' },
    { name: 'Plum Dusk', bg: '#1F1329', accent: '#C4B5FD', text: '#F1EAFB' },
    { name: 'Charcoal Steel', bg: '#1E2530', accent: '#94A3B8', text: '#F1F5F9' },
    { name: 'Wine Noir', bg: '#1A0A10', accent: '#E4572E', text: '#F7E9E5' },
    { name: 'Frost Light', bg: '#F5F5F3', accent: '#1A3D34', text: '#0F172A' },
];

export const MAIN_PALETTES: MainPalette[] = [
    { name: 'Classic Gold', category: 'Popular', bg: '#F5F5F3', card: '#FFFFFF', accent: '#D4AF37', border: '#6B9080' },
    { name: 'Ocean Blue', category: 'Popular', bg: '#EFF6FB', card: '#FFFFFF', accent: '#2563EB', border: '#93C5FD' },
    { name: 'Forest Green', category: 'Popular', bg: '#F3F7F1', card: '#FFFFFF', accent: '#15803D', border: '#86A98F' },
    { name: 'Slate Charcoal', category: 'Popular', bg: '#F2F3F5', card: '#FFFFFF', accent: '#334155', border: '#94A3B8' },

    { name: 'Sunset Coral', category: 'Vibrant', bg: '#FDF3F0', card: '#FFFFFF', accent: '#E4572E', border: '#F2A488' },
    { name: 'Berry Rose', category: 'Vibrant', bg: '#FDF2F6', card: '#FFFFFF', accent: '#BE185D', border: '#F0A8C4' },
    { name: 'Royal Purple', category: 'Vibrant', bg: '#F5F3FA', card: '#FFFFFF', accent: '#6D28D9', border: '#C4B5FD' },
    { name: 'Electric Teal', category: 'Vibrant', bg: '#EAFBFA', card: '#FFFFFF', accent: '#0D9488', border: '#5EEAD4' },
    { name: 'Coral Punch', category: 'Vibrant', bg: '#FFF4F1', card: '#FFFFFF', accent: '#FB5607', border: '#FFB4A2' },
    { name: 'Neon Lime', category: 'Vibrant', bg: '#F7FCEF', card: '#FFFFFF', accent: '#65A30D', border: '#BEF264' },

    { name: 'Warm Sand', category: 'Muted', bg: '#FBF6EE', card: '#FFFFFF', accent: '#B45309', border: '#D8B989' },
    { name: 'Dusty Lilac', category: 'Muted', bg: '#F8F5FB', card: '#FFFFFF', accent: '#9D8DF1', border: '#D8CFF5' },
    { name: 'Sage Mist', category: 'Muted', bg: '#F4F7F4', card: '#FFFFFF', accent: '#7C9A82', border: '#C8D5C9' },
    { name: 'Powder Blue', category: 'Muted', bg: '#F2F8FB', card: '#FFFFFF', accent: '#7CA9C9', border: '#C7E0ED' },
    { name: 'Blush Sand', category: 'Muted', bg: '#FBF3EF', card: '#FFFFFF', accent: '#D6A184', border: '#EAC9B4' },

    { name: 'Espresso Brown', category: 'Moody', bg: '#F7F3F0', card: '#FFFFFF', accent: '#4B2E1E', border: '#C9A88A' },
    { name: 'Charcoal Ink', category: 'Moody', bg: '#F1F2F4', card: '#FFFFFF', accent: '#1E293B', border: '#94A3B8' },
    { name: 'Onyx Steel', category: 'Moody', bg: '#F0F1F3', card: '#FFFFFF', accent: '#27272A', border: '#A1A1AA' },

    { name: 'Terracotta Clay', category: 'Earthy', bg: '#FBF2EC', card: '#FFFFFF', accent: '#C1662F', border: '#E3B28C' },
    { name: 'Olive Grove', category: 'Earthy', bg: '#F5F6EE', card: '#FFFFFF', accent: '#6B7A3A', border: '#C3CBA0' },
    { name: 'Clay & Sage', category: 'Earthy', bg: '#F6F3EE', card: '#FFFFFF', accent: '#A9764C', border: '#9CAF88' },

    { name: 'Arctic Ice', category: 'Cool', bg: '#F0F7FA', card: '#FFFFFF', accent: '#38BDF8', border: '#BAE6FD' },
    { name: 'Denim Wash', category: 'Cool', bg: '#EFF3F8', card: '#FFFFFF', accent: '#3B5B80', border: '#A8C0D8' },
    { name: 'Steel Blue', category: 'Cool', bg: '#EEF2F6', card: '#FFFFFF', accent: '#4A6FA5', border: '#9FB8D4' },
];

export const PALETTE_CATEGORIES = ['All', 'Popular', 'Vibrant', 'Muted', 'Moody', 'Earthy', 'Cool'];

export const FONT_FAMILIES: FontFamilyOption[] = [
    { key: 'Inter', label: 'Inter', fallback: 'sans-serif' },
    { key: 'Poppins', label: 'Poppins', fallback: 'sans-serif' },
    { key: 'Roboto', label: 'Roboto', fallback: 'sans-serif' },
    { key: 'Nunito', label: 'Nunito', fallback: 'sans-serif' },
    { key: 'Merriweather', label: 'Merriweather (Serif)', fallback: 'serif' },
    { key: 'Fira Code', label: 'Fira Code (Mono)', fallback: 'monospace' },
];

export const FONT_SIZES: FontSizeOption[] = [
    { key: 'sm', label: 'Compact', zoom: 0.9, hint: 'Fits more on screen — tighter text and spacing everywhere.' },
    { key: 'md', label: 'Default', zoom: 1, hint: "The size every page ships with today." },
    { key: 'lg', label: 'Comfortable', zoom: 1.15, hint: 'Larger text and spacing everywhere — easier to read.' },
];

export function swatchGradient(hex: string): string {
    return `linear-gradient(135deg, color-mix(in srgb, ${hex} 85%, white) 0%, ${hex} 55%, color-mix(in srgb, ${hex} 75%, black) 100%)`;
}
