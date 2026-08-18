<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * The single write path for `admin_action_logs`, backing the comprehensive
 * audit system (requirement #8): who + what + when + company + affected
 * record + before/after, for every admin-shaped action across the
 * Platform — not just the narrow "Super Admin cross-company bypass" trail
 * Phase 2/10 originally built.
 *
 * Deliberately uses `SupabaseService` (service_role), not the caller's own
 * `SupabaseUserService` token, even though every other piece of new Platform
 * code in this codebase is built the other way around. Two real capture
 * points this system needs have no authenticated Postgres session to write
 * through at all: a failed login attempt (no Supabase session exists yet —
 * that's the whole point of it failing) and the Telegram webhook / cron
 * digest jobs (a bot/cron context, not a browser session). Rather than build
 * two different logging code paths — one via the caller's token for
 * in-request writes, one via service role for everything else — and risk
 * them drifting apart, every write goes through the one path that works
 * everywhere. This is safe specifically because `admin_action_logs` is
 * already one of the Core Platform Rule's own documented tenant-ownership
 * exemptions (CLAUDE.md): Center-level infrastructure, not tenant data, and
 * `SupabaseService::TENANT_OWNED_TABLES` already lets it through. Security
 * still lives where it belongs: reads go through `SupabaseUserService`/RLS
 * (see AuditLogController), so WHO may write a row is unrestricted
 * (append-only system log) but WHO may read one is exactly as tenant-scoped
 * as any other Platform data.
 */
class AuditLogService
{
    public function __construct(private SupabaseService $supabase)
    {
    }

    private const DEFAULTS = [
        'actor_user_id' => null,
        'actor_email' => null,
        'action' => null,
        'target_company_id' => null,
        'target_user_id' => null,
        'target_type' => null,
        'target_id' => null,
        'before' => null,
        'after' => null,
        'metadata' => [],
        'ip_address' => null,
        'user_agent' => null,
    ];

    /**
     * Not best-effort — a failed write propagates to the caller, which is
     * expected to catch it and tell the operator their action already
     * happened but wasn't logged (see CompanyController::store() for the
     * established pattern). Use this for deliberate, infrequent, admin-driven
     * mutations where a silent gap in the trail is worse than a visible
     * error.
     */
    public function record(array $fields): void
    {
        $this->supabase->insert('admin_action_logs', array_merge(self::DEFAULTS, $fields));
    }

    /**
     * For high-frequency or system-triggered events (login attempts, ANIRA
     * chat, Telegram digests/webhooks) where blocking the primary action on
     * a logging hiccup would be a worse failure mode than a rare gap in the
     * audit trail.
     */
    public function recordBestEffort(array $fields): void
    {
        try {
            $this->record($fields);
        } catch (\Throwable $e) {
            Log::error('Audit log write failed', ['fields' => $fields, 'error' => $e->getMessage()]);
        }
    }
}
