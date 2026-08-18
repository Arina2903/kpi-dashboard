<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes a real gap in 2026_08_14_060000_enforce_company_suspension_in_rls.php,
 * caught during a code-review pass: that migration correctly centralized
 * suspension enforcement inside `auth_company_ids()`/`auth_role_in_company()`/
 * `auth_department_ids()`, but `users_select`'s own "a Company Admin can see
 * everyone in their company" branch (2026_08_12_000000_create_platform_foundation_schema.php,
 * lines 308-321) is a raw self-join on `company_users` that never calls any
 * of those three functions and never checked `cu_self.status` either — so a
 * suspended company's own Company Admin (using a Supabase session/JWT they
 * already held before being suspended) could still read every user row in
 * their company: name, email, role, everyone. `CompanyController::suspend()`'s
 * own success message ("its users have lost access") wasn't true for this
 * one policy.
 *
 * Adds the same two conditions the three helper functions now enforce
 * elsewhere: the acting membership must still be active, and the company
 * itself must not be suspended/inactive.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists users_select on users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_select on users for select
              using (
                auth_is_richworks_super_admin()
                or auth_user_id = auth.uid()
                or exists (
                  select 1 from company_users cu_self
                  join company_users cu_target on cu_target.company_id = cu_self.company_id
                  join companies c on c.id = cu_self.company_id
                  where cu_self.user_id = auth_current_user_id()
                    and cu_target.user_id = users.id
                    and cu_self.role = 'company_admin'
                    and cu_self.status = 'active'
                    and c.status not in ('suspended', 'inactive')
                )
              )
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop policy if exists users_select on users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy users_select on users for select
              using (
                auth_is_richworks_super_admin()
                or auth_user_id = auth.uid()
                or exists (
                  select 1 from company_users cu_self
                  join company_users cu_target on cu_target.company_id = cu_self.company_id
                  where cu_self.user_id = auth_current_user_id()
                    and cu_target.user_id = users.id
                    and cu_self.role = 'company_admin'
                )
              )
        SQL);
    }
};
