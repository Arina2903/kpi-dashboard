const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/** 'YYYY-MM-DD' grouping key, matching Carbon::parse($at)->format('Y-m-d'). */
export function dateKey(iso: string): string {
    const d = new Date(iso);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** 'd M Y' e.g. "06 Aug 2026". */
export function formatDate(iso: string): string {
    const d = new Date(iso);
    return `${String(d.getDate()).padStart(2, '0')} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
}

/** 'h:i A' e.g. "02:45 PM". */
export function formatTime(iso: string): string {
    const d = new Date(iso);
    let hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
}

export function isToday(iso: string): boolean {
    return dateKey(iso) === dateKey(new Date().toISOString());
}

export function isYesterday(iso: string): boolean {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    return dateKey(iso) === dateKey(yesterday.toISOString());
}

/** Matches the Blade view's Today/Yesterday/'d M Y' date-divider label. */
export function dateDividerLabel(iso: string): string {
    if (isToday(iso)) return 'Today';
    if (isYesterday(iso)) return 'Yesterday';
    return formatDate(iso);
}
