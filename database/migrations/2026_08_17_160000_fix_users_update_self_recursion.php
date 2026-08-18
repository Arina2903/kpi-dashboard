<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes a real bug found while walking through the app as an actual
 * authenticated user, trying to link a Telegram account: `users_update_self`
 * (from the ORIGINAL foundational migration, never touched since) enforces
 * "you may update your own row, but not your own `role`" via a self-
 * referencing `WITH CHECK` subquery:
 *
 *     with check (auth_user_id = auth.uid() and role = (select u2.role from users u2 where u2.id = users.id))
 *
 * This is the exact recursion shape `2026_08_14_020000_fix_recursive_update_policies`
 * already fixed on `kpis`/`kpi_categories`/`departments`/`kpi_submissions`/
 * `department_users` — a policy on table X that queries table X again inside
 * its own check. `users_update_self` was never included in that fix (it
 * wasn't broken YET, because nothing in the app had ever actually run a
 * self-UPDATE on `users` through PostgREST before now — every existing
 * "change your own account" flow goes through Supabase Auth's own
 * `/auth/v1/user` endpoint via `SupabaseAuthService::setPassword()`, not a
 * `users` table UPDATE). `PlatformTelegramLinkService::generateCode()` is the
 * first code in this codebase to do that, and it failed immediately with
 * Postgres error 42P17: "infinite recursion detected in policy for relation
 * users."
 *
 * Fixed the same way as the earlier five tables: replace the self-referencing
 * subquery with a `BEFORE UPDATE` trigger, which can compare OLD vs NEW
 * without re-triggering RLS. The trigger only blocks a ROLE change when the
 * row being updated is the CALLER'S OWN (`NEW.auth_user_id = auth.uid()`) —
 * `auth.uid()` is null for service-role calls and differs from `NEW.auth_user_id`
 * whenever a Super Admin updates someone ELSE's role (PlatformAdminController's
 * promote flow, BootstrapSuperAdmin), so neither of those legitimate paths is
 * affected — only genuine self-privilege-escalation is blocked, same
 * guarantee as before, just expressed without recursion.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists users_update_self on users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_update_self on users for update
              using (auth_user_id = auth.uid())
              with check (auth_user_id = auth.uid())
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_self_role_change()
            returns trigger
            language plpgsql
            security definer
            set search_path = public
            as $function$
            begin
                if NEW.role is distinct from OLD.role and NEW.auth_user_id = auth.uid() then
                    raise exception 'Cannot change your own role.';
                end if;
                return NEW;
            end;
            $function$
        SQL);

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_self_role_change on users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_self_role_change
              before update on users
              for each row
              execute function prevent_self_role_change()
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_self_role_change on users');
        DB::connection('pgsql')->statement('drop function if exists prevent_self_role_change()');

        DB::connection('pgsql')->statement('drop policy if exists users_update_self on users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_update_self on users for update
              using (auth_user_id = auth.uid())
              with check (auth_user_id = auth.uid() and role = (select u2.role from users u2 where u2.id = users.id))
        SQL);
    }
};
