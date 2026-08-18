<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Performix Platform Blueprint, Phase 7: Employees imported via Excel have
 * nowhere to land as data until a real Supabase Auth account exists for
 * each one (there is no `employees` staging table in this schema — people
 * are modeled directly as `users` + `company_users` + `department_users`,
 * see the Blueprint's §1 reconciliation). Account creation is deliberately
 * its own later step (Phase 8, "Create Accounts" — spec section 17: "After
 * employees are imported, provide: Create User Accounts" as a distinct,
 * selectable action), so a validated Employees import needs somewhere to
 * sit in between: this column.
 *
 * Left null for `departments`/`kpis` import batches, which commit directly
 * into their real tables with nothing left to stage.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('alter table import_batches add column rows jsonb');
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('alter table import_batches drop column if exists rows');
    }
};
