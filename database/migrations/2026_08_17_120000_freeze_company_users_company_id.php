<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Closes a gap the Core Platform Rule conformance check
 * (database/rls-tests/tenant_isolation_rule_check.sql, section 6) surfaced:
 * `company_users.company_id` has never had an immutability trigger, unlike
 * every other tenant-owned table. Unlike the gap 2026_08_14_080000 fixed,
 * this one isn't a regression from the recursion fix — it traces back to
 * `2026_08_12_000000_create_platform_foundation_schema.php`'s own original
 * `company_users_update` policy, which only ever checked
 * `auth_role_in_company(company_id) = 'company_admin'` on the new row, never
 * OLD vs NEW. It was carried forward unchanged through
 * 2026_08_14_090000 and 2026_08_17_110000's rewrites of that same policy.
 *
 * Without this: someone who administers two different companies (a company
 * or platform admin with membership/assignment in both — an unremarkable
 * schema state) could UPDATE a `company_users` row they can already see and
 * repoint its `company_id` at the other company they also administer,
 * transferring a user's company membership across the tenant boundary RLS
 * exists to make impossible. `prevent_company_id_change()` already exists
 * (created in 2026_08_14_080000, reused by 2026_08_17_100000 and _110000) —
 * this migration is only the one missing trigger attachment, not new logic.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_company_id_change on company_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_company_id_change
              before update on company_users
              for each row execute function prevent_company_id_change()
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_company_id_change on company_users');
    }
};
