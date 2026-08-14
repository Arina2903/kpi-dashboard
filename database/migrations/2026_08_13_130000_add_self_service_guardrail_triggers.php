<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 9 of the multi-company platform: the guardrails self-service setup
 * needs that a single-tenant app never did — see the "Performix Platform
 * Blueprint" design doc, section 3. Without these, a Company Admin (or a
 * client's own IT staff editing tables directly in the Supabase dashboard)
 * can lock a company out of managing itself with no way back short of
 * Richworks fixing it by hand.
 *
 * Deliberately implemented as Postgres triggers rather than app-level
 * checks: RLS already means Postgres is the real enforcement point for
 * everything else in this platform, and unlike a check inside a Laravel
 * controller, a trigger also covers writes that don't go through the app at
 * all (a script, the Supabase table editor, a future endpoint nobody
 * remembers to add the check to).
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guardrail 1: a company can never end up with zero company_admins.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_zero_company_admins()
            returns trigger language plpgsql as $$
            declare
              remaining_admins integer;
            begin
              if TG_OP = 'DELETE' then
                if OLD.role <> 'company_admin' then
                  return OLD;
                end if;

                select count(*) into remaining_admins
                  from company_users
                  where company_id = OLD.company_id and role = 'company_admin' and user_id <> OLD.user_id;

                if remaining_admins = 0 then
                  raise exception 'Cannot remove the last Company Admin for this company.';
                end if;

                return OLD;
              end if;

              if OLD.role = 'company_admin' and (NEW.role <> 'company_admin' or NEW.company_id <> OLD.company_id) then
                select count(*) into remaining_admins
                  from company_users
                  where company_id = OLD.company_id and role = 'company_admin' and user_id <> OLD.user_id;

                if remaining_admins = 0 then
                  raise exception 'Cannot demote or move the last Company Admin for this company.';
                end if;
              end if;

              return NEW;
            end;
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_zero_company_admins
              before update or delete on company_users
              for each row execute function prevent_zero_company_admins()
        SQL);

        // Guardrail 2: a department can never be deleted while it still has
        // members — reassign or remove them first, so nobody's account
        // silently orphans.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_orphaned_department_deletion()
            returns trigger language plpgsql as $$
            declare
              member_count integer;
            begin
              select count(*) into member_count from department_users where department_id = OLD.id;

              if member_count > 0 then
                raise exception 'Cannot delete a department that still has members — reassign or remove them first.';
              end if;

              return OLD;
            end;
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_orphaned_department_deletion
              before delete on departments
              for each row execute function prevent_orphaned_department_deletion()
        SQL);

        // Guardrail 3: a department can never drop to zero roles. Phase 8's
        // auto-seeded "Member" role only covers department *creation* — this
        // closes the gap where someone deletes that seeded role afterward
        // while it's still unassigned (RoleController's FK-restrict guard
        // only fires once a role is actually held by someone).
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_last_role_deletion()
            returns trigger language plpgsql as $$
            declare
              remaining_roles integer;
            begin
              select count(*) into remaining_roles
                from roles
                where department_id = OLD.department_id and id <> OLD.id;

              if remaining_roles = 0 then
                raise exception 'A department must keep at least one role — add a replacement before removing the last one.';
              end if;

              return OLD;
            end;
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_last_role_deletion
              before delete on roles
              for each row execute function prevent_last_role_deletion()
        SQL);

        // Guardrail 4: the platform-wide equivalent of guardrail 1 — Richworks
        // itself can never end up with zero Super Admins. platform:bootstrap-super-admin
        // exists as a manual escape hatch precisely because there'd otherwise
        // be no way back from that state at all.
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_zero_super_admins()
            returns trigger language plpgsql as $$
            declare
              remaining_admins integer;
            begin
              if TG_OP = 'DELETE' then
                if OLD.role <> 'richworks_super_admin' then
                  return OLD;
                end if;

                select count(*) into remaining_admins
                  from users
                  where role = 'richworks_super_admin' and id <> OLD.id;

                if remaining_admins = 0 then
                  raise exception 'Cannot remove the last Richworks Super Admin.';
                end if;

                return OLD;
              end if;

              if OLD.role = 'richworks_super_admin' and NEW.role <> 'richworks_super_admin' then
                select count(*) into remaining_admins
                  from users
                  where role = 'richworks_super_admin' and id <> OLD.id;

                if remaining_admins = 0 then
                  raise exception 'Cannot demote the last Richworks Super Admin.';
                end if;
              end if;

              return NEW;
            end;
            $$
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_zero_super_admins
              before update or delete on users
              for each row execute function prevent_zero_super_admins()
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_zero_super_admins on users');
        DB::connection('pgsql')->statement('drop function if exists prevent_zero_super_admins()');

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_last_role_deletion on roles');
        DB::connection('pgsql')->statement('drop function if exists prevent_last_role_deletion()');

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_orphaned_department_deletion on departments');
        DB::connection('pgsql')->statement('drop function if exists prevent_orphaned_department_deletion()');

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_zero_company_admins on company_users');
        DB::connection('pgsql')->statement('drop function if exists prevent_zero_company_admins()');
    }
};
