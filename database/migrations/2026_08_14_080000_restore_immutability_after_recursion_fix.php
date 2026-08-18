<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes a real regression introduced by 2026_08_14_020000_fix_recursive_update_policies.php,
 * caught during a code-review pass: that migration correctly fixed the
 * "infinite recursion detected in policy" bug on `kpis`, `kpi_categories`,
 * `departments`, `department_users`, and `kpi_submissions` UPDATE policies,
 * but in removing the self-referencing `WITH CHECK` subqueries that caused
 * the recursion, it also removed the immutability guarantees those
 * subqueries happened to enforce — with no replacement. Confirmed against
 * 2026_08_12_000000_create_platform_foundation_schema.php: the original
 * (recursive, broken-for-everyone) policies checked
 * `company_id = (select ... where id = <this row>.id)` on kpis/kpi_categories/
 * departments, all three of company_id/department_id/kpi_id on
 * kpi_submissions, and department_id on department_users — none of that
 * survived the rewrite.
 *
 * Without it: a user who is `company_admin` of two different companies (a
 * valid, unremarkable schema state) could UPDATE a kpi/kpi_category/
 * department row they administer and move its `company_id` to the other
 * company they also administer — reassigning tenant data across the
 * boundary RLS exists specifically to make impossible. Same for
 * kpi_submissions: department_id/kpi_id could be repointed at an unrelated
 * department or KPI (even in a different company) as long as the row's
 * company_id happened to match a company the caller could still pass the
 * WITH CHECK for.
 *
 * A WITH CHECK clause can't reference an update's own OLD row without
 * re-triggering the exact recursion 2026_08_14_020000 fixed -- that's
 * fundamentally a job for a trigger (which sees OLD/NEW directly, no RLS
 * involved), not a policy. This mirrors the pattern this project already
 * established in 2026_08_13_130000_add_self_service_guardrail_triggers.php
 * for exactly this reason.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_company_id_change()
            returns trigger language plpgsql as $$
            begin
              if NEW.company_id <> OLD.company_id then
                raise exception 'company_id cannot be changed after creation.';
              end if;
              return NEW;
            end;
            $$
        SQL);

        foreach (['kpis', 'kpi_categories', 'departments'] as $table) {
            DB::connection('pgsql')->statement("drop trigger if exists trg_prevent_company_id_change on {$table}");
            DB::connection('pgsql')->statement(<<<SQL
                create trigger trg_prevent_company_id_change
                  before update on {$table}
                  for each row execute function prevent_company_id_change()
            SQL);
        }

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_kpi_submission_reassignment()
            returns trigger language plpgsql as $$
            begin
              if NEW.company_id <> OLD.company_id
                or NEW.department_id <> OLD.department_id
                or NEW.kpi_id <> OLD.kpi_id
              then
                raise exception 'company_id, department_id, and kpi_id cannot be changed on an existing submission.';
              end if;
              return NEW;
            end;
            $$
        SQL);

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_kpi_submission_reassignment on kpi_submissions');
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_kpi_submission_reassignment
              before update on kpi_submissions
              for each row execute function prevent_kpi_submission_reassignment()
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create or replace function prevent_department_users_reassignment()
            returns trigger language plpgsql as $$
            begin
              if NEW.department_id <> OLD.department_id then
                raise exception 'department_id cannot be changed on an existing membership — remove and re-add instead.';
              end if;
              return NEW;
            end;
            $$
        SQL);

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_department_users_reassignment on department_users');
        DB::connection('pgsql')->statement(<<<'SQL'
            create trigger trg_prevent_department_users_reassignment
              before update on department_users
              for each row execute function prevent_department_users_reassignment()
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_department_users_reassignment on department_users');
        DB::connection('pgsql')->statement('drop function if exists prevent_department_users_reassignment()');

        DB::connection('pgsql')->statement('drop trigger if exists trg_prevent_kpi_submission_reassignment on kpi_submissions');
        DB::connection('pgsql')->statement('drop function if exists prevent_kpi_submission_reassignment()');

        foreach (['kpis', 'kpi_categories', 'departments'] as $table) {
            DB::connection('pgsql')->statement("drop trigger if exists trg_prevent_company_id_change on {$table}");
        }
        DB::connection('pgsql')->statement('drop function if exists prevent_company_id_change()');
    }
};
