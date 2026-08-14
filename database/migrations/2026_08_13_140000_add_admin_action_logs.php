<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10 of the multi-company platform: an audit trail for Super Admin
 * actions that cross a company boundary — see the "Performix Platform
 * Blueprint" design doc, section 5. This is what requirement #3's "proper
 * audit trail" for Richworks support access actually needs, mirroring the
 * discipline the legacy `admin_view_as_logs` table already applies on the
 * Richworks-only side.
 *
 * No update/delete RLS policy is defined on purpose — a policy-less
 * operation is denied by default, so the log is append-only even to the
 * person who wrote a row into it.
 *
 * RLS reuses `auth_is_richworks_super_admin()` and `auth_current_user_id()`
 * (confirmed live on this project) rather than comparing against `auth.uid()`
 * directly — `actor_user_id` is populated from `LogsAdminActions` with
 * `users.id` (via `$platformUser['id']`), which is a different column from
 * `users.auth_user_id` that `auth.uid()` actually returns.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('admin_action_logs', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('actor_user_id');
            $table->string('action');
            $table->uuid('target_company_id')->nullable();
            $table->uuid('target_user_id')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('occurred_at')->default(DB::raw('now()'));

            $table->foreign('actor_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('target_company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('set null');

            $table->index('actor_user_id');
            $table->index('target_company_id');
            $table->index('occurred_at');
        });

        DB::connection('pgsql')->statement('alter table admin_action_logs enable row level security');

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy admin_action_logs_select on admin_action_logs for select
              using (auth_is_richworks_super_admin())
        SQL);

        // Anyone may write a row, but only as themselves — a caller can't
        // attribute an action to someone else's actor_user_id.
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy admin_action_logs_insert on admin_action_logs for insert
              with check (actor_user_id = auth_current_user_id())
        SQL);
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('admin_action_logs');
    }
};
