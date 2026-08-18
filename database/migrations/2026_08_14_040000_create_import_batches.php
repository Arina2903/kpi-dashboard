<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performix Platform Blueprint, Phase 2: import history for the Excel
 * onboarding pipeline (Upload -> Read -> Validate -> Preview -> Confirm ->
 * Import). `errors` holds the same row-level validation problems the Preview
 * step shows before commit, kept after the fact so the Center can review why
 * a batch had failures without re-running the import.
 *
 * No update policy beyond what the importing service itself needs (moving a
 * batch from pending -> validated/failed -> completed) and no delete policy
 * at all -- like `admin_action_logs`, this is meant to be a durable history,
 * not something even a Super Admin edits after the fact.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('company_id');
            $table->text('filename');
            $table->text('type');
            $table->text('status')->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->jsonb('errors')->nullable();
            $table->uuid('uploaded_by');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('completed_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->index('company_id');
            $table->index('uploaded_by');
        });

        DB::connection('pgsql')->statement(<<<'SQL'
            alter table import_batches add constraint import_batches_type_check
              check (type in ('employees', 'departments', 'kpis', 'workbook'))
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            alter table import_batches add constraint import_batches_status_check
              check (status in ('pending', 'validated', 'failed', 'completed'))
        SQL);

        DB::connection('pgsql')->statement('alter table import_batches enable row level security');

        // Mirrors the rest of the platform's company-scoped read access: a
        // Super Admin sees every batch, a Company Admin sees their own
        // company's import history (requirement: "Allow Center Admin to view
        // import history", extended to the company's own admin for
        // transparency into their own onboarding).
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy import_batches_select on import_batches for select
              using (auth_is_richworks_super_admin() or auth_role_in_company(company_id) = 'company_admin')
        SQL);

        // Only the Center runs imports (the onboarding wizard is Center-driven
        // per the Performix spec) -- and only ever attributed to whoever is
        // actually making the call, mirroring admin_action_logs_insert.
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy import_batches_insert on import_batches for insert
              with check (auth_is_richworks_super_admin() and uploaded_by = auth_current_user_id())
        SQL);

        // For the validate -> preview -> confirm pipeline advancing a batch's
        // own status/counts. No self-referencing subquery in WITH CHECK
        // (see the 2026_08_14_020000 recursion fix) -- the USING condition
        // alone already stops a batch from being reassigned to a company the
        // caller doesn't admin.
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy import_batches_update on import_batches for update
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin())
        SQL);
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('import_batches');
    }
};
