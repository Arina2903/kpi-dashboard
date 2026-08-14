export interface BandMetaEntry {
    label: string;
    range: string;
    bg: string;
    text: string;
}

export const BAND_META: Record<string, BandMetaEntry> = {
    unsatisfactory: { label: 'Unsatisfactory', range: '1–49', bg: '#ED1C24', text: '#FFFFFF' },
    below_average: { label: 'Below Average', range: '50–69', bg: '#FF8C00', text: '#000000' },
    meets_expectations: { label: 'Meets Expectations', range: '70–89', bg: '#FFD700', text: '#000000' },
    outstanding: { label: 'Outstanding', range: '90–100', bg: '#00B050', text: '#FFFFFF' },
};

export interface StatusMetaEntry {
    label: string;
    bg: string;
    text: string;
}

export const STATUS_META: Record<string, StatusMetaEntry> = {
    not_submitted: { label: 'Not Submitted', bg: '#FEE2E2', text: '#DC2626' },
    pending: { label: 'Awaiting Appraisal', bg: '#F1F5F9', text: '#64748B' },
    awaiting_signoff: { label: 'Awaiting Sign-off', bg: '#FEF3C7', text: '#B45309' },
};

export const ROLE_GROUPS: Record<string, string> = {
    SLT: 'SLT',
    VP: 'VP',
    MANAGER: 'Manager',
    EXECUTIVE: 'Executive',
};

export const ROLE_BADGE_STYLE: Record<string, { background: string; color: string }> = {
    SLT: { background: '#F3E8FF', color: '#7C3AED' },
    VP: { background: '#F5EAE0', color: '#6B3F2A' },
    MANAGER: { background: '#E0E7FF', color: '#4338CA' },
    EXECUTIVE: { background: '#F1F5F9', color: '#475569' },
};

export const ROLE_BADGE_STYLE_DEFAULT = { background: '#F1F5F9', color: '#64748B' };

export interface StageMetaEntry {
    key: 'not_submitted' | 'pending' | 'awaiting_signoff' | 'completed';
    label: string;
    bg: string;
    soft: string;
    text: string;
    hint: string;
}

export const STAGE_META: StageMetaEntry[] = [
    { key: 'not_submitted', label: 'Not Submitted', bg: '#DC2626', soft: '#FEE2E2', text: '#DC2626', hint: "Staff hasn't started" },
    { key: 'pending', label: 'Awaiting Appraisal', bg: '#94A3B8', soft: '#F1F5F9', text: '#64748B', hint: 'Waiting on their boss' },
    { key: 'awaiting_signoff', label: 'Awaiting Sign-off', bg: '#F59E0B', soft: '#FEF3C7', text: '#B45309', hint: 'Waiting on staff to sign' },
    { key: 'completed', label: 'Completed', bg: '#10B981', soft: '#D1FAE5', text: '#047857', hint: 'Fully signed off & scored' },
];
