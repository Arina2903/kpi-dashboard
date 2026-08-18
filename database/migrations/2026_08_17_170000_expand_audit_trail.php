<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expands `admin_action_logs` from a narrow "Super Admin cross-company
 * bypass" trail (Phase 2/10) into the comprehensive audit system requirement
 * #8 asks for: who + what + when + company + affected record + before/after,
 * across every admin-shaped action in the Platform — not just the ones a
 * Super Admin does outside their own company, which was the only thing this
 * table ever captured before now.
 *
 * `actor_user_id` loses its NOT NULL — a failed login attempt (wrong
 * password, unknown email) has no resolvable `users` row to attribute it to,
 * but is exactly the kind of event a security-focused audit trail needs to
 * keep. `actor_email` carries identity in that case (and alongside
 * actor_user_id otherwise, so a later rename/deactivation doesn't erase who
 * it was at the time of the action).
 *
 * `target_type`/`target_id` generalize "affected record" beyond the two
 * existing FK columns (target_company_id/target_user_id) to KPIs, roles,
 * grants, import batches, etc. `before`/`after` are the literal requirement;
 * both nullable since a pure create, delete, or read-only "admin access"
 * entry only ever has one side.
 *
 * RLS: the existing Super-Admin-only SELECT policy is kept, and a second,
 * additive policy lets a company_admin read their OWN company's rows — this
 * is what makes the trail visible to the people who now generate most of it
 * (previously only Super Admin bypass actions were ever logged, so only a
 * Super Admin ever needed to read this table). Writes move to a service-role
 * path (see App\Services\AuditLogService) rather than the caller's own
 * token: several of the new capture points (a failed login, the Telegram
 * webhook, cron digests) have no authenticated Postgres session to write
 * through at all. That's safe specifically because `admin_action_logs` is
 * already one of the Core Platform Rule's own documented tenant-ownership
 * exemptions (CLAUDE.md) — Center-level infrastructure, not tenant data —
 * so this exercises an exemption that already existed, rather than opening a
 * new one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->table('admin_action_logs', function ($table) {
            $table->text('actor_email')->nullable();
            $table->text('target_type')->nullable();
            $table->uuid('target_id')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->text('ip_address')->nullable();
            $table->text('user_agent')->nullable();
        });

        DB::connection('pgsql')->statement('alter table admin_action_logs alter column actor_user_id drop not null');

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy admin_action_logs_select_company on admin_action_logs for select
              using (target_company_id is not null and auth_role_in_company(target_company_id) = 'company_admin')
        SQL);

        DB::connection('pgsql')->statement('create index if not exists admin_action_logs_target_type_idx on admin_action_logs (target_type)');
        DB::connection('pgsql')->statement('create index if not exists admin_action_logs_action_idx on admin_action_logs (action)');
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop index if exists admin_action_logs_action_idx');
        DB::connection('pgsql')->statement('drop index if exists admin_action_logs_target_type_idx');
        DB::connection('pgsql')->statement('drop policy if exists admin_action_logs_select_company on admin_action_logs');
        DB::connection('pgsql')->statement('alter table admin_action_logs alter column actor_user_id set not null');

        Schema::connection('pgsql')->table('admin_action_logs', function ($table) {
            $table->dropColumn(['actor_email', 'target_type', 'target_id', 'before', 'after', 'ip_address', 'user_agent']);
        });
    }
};
