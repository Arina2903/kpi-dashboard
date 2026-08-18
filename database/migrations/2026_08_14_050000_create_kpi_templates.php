<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performix Platform Blueprint, Phase 2: reusable KPI templates (e.g. "Sales
 * Team", "Customer Service") the Center applies to speed up onboarding.
 * Deliberately has no `company_id` -- these are shared library definitions,
 * not tenant data, so they sit outside the RLS company-scoping pattern every
 * other table in this schema uses.
 *
 * `kpi_template_items` carries no foreign key back into a company's live
 * `kpi_categories`/`kpis` on purpose: applying a template is a copy
 * operation (a future KpiTemplateService writes new, independent
 * `kpi_categories`/`kpis` rows into the target company), never a live
 * reference. A shared FK from a company's KPI back to the template it came
 * from would mean editing the template later silently reshapes every
 * company that ever applied it -- exactly the cross-tenant leakage the
 * Performix spec's "do not create a shared KPI record" requirement warns
 * against.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('kpi_templates', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->text('name');
            $table->text('description')->nullable();
            $table->text('status')->default('active');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));

            $table->unique('name');
        });

        DB::connection('pgsql')->statement(<<<'SQL'
            alter table kpi_templates add constraint kpi_templates_status_check
              check (status in ('active', 'inactive'))
        SQL);

        Schema::connection('pgsql')->create('kpi_template_items', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('template_id');
            $table->text('category_name')->nullable();
            $table->text('name');
            $table->text('description')->nullable();
            $table->decimal('target')->nullable();
            $table->text('unit')->nullable();
            $table->text('frequency')->default('monthly');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('template_id')->references('id')->on('kpi_templates')->onDelete('cascade');
            $table->index('template_id');
        });

        DB::connection('pgsql')->statement('alter table kpi_templates enable row level security');
        DB::connection('pgsql')->statement('alter table kpi_template_items enable row level security');

        // Any signed-in platform user may browse the template library (a
        // Company Admin picking a template during their own onboarding needs
        // this just as much as the Center does) -- only the Center curates
        // it. `auth_current_user_id()` is null for anyone without a `users`
        // row at all, e.g. a request with no valid Supabase session.
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_templates_select on kpi_templates for select
              using (auth_current_user_id() is not null)
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_templates_write on kpi_templates for all
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin())
        SQL);

        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_template_items_select on kpi_template_items for select
              using (auth_current_user_id() is not null)
        SQL);
        DB::connection('pgsql')->statement(<<<'SQL'
            create policy kpi_template_items_write on kpi_template_items for all
              using (auth_is_richworks_super_admin())
              with check (auth_is_richworks_super_admin())
        SQL);
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('kpi_template_items');
        Schema::connection('pgsql')->dropIfExists('kpi_templates');
    }
};
