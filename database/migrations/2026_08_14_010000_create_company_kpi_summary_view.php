<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `Platform/DashboardController::index()` has queried this view since it was
 * written, wrapped in a try/catch that silently falls back to zeroed stats on
 * failure -- which is exactly what's been happening, since the view never
 * existed. Confirmed via a live schema check: no view by this name in
 * information_schema.views, on production.
 *
 * `security_invoker = true` (available since Postgres 15, confirmed live on
 * 17.6) makes the view evaluate its underlying table reads as the calling
 * role, not the view owner -- so a company_admin querying this view still
 * only gets their own company's row, the same as querying `companies`
 * directly. Without it, a security_definer-by-default view would bypass RLS
 * entirely and leak every company's stats to every caller, which is the one
 * mistake this whole platform's design otherwise avoids.
 *
 * achievement_pct per submission is value/target*100, matching the legacy
 * app's own achievement formula (KpiController::calculateAchievement()) for
 * the < 100% case; the Platform's kpis table has no stretch_target/base_target
 * split yet, so the >100% stretch-target branch doesn't apply here. Submissions
 * against a null or zero target are excluded rather than producing null/Inf.
 *
 * Runs against the `pgsql` connection, same as prior platform migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement(<<<'SQL'
            create view company_kpi_summary
            with (security_invoker = true)
            as
            select
                c.id as company_id,
                (select count(*) from departments d where d.company_id = c.id) as department_count,
                (select count(*) from company_users cu where cu.company_id = c.id and cu.status = 'active') as user_count,
                (select count(*) from kpis k where k.company_id = c.id) as kpi_count,
                (select count(*) from kpi_submissions ks where ks.company_id = c.id) as submission_count,
                (
                    select round(avg(ks.value / k.target * 100), 1)
                    from kpi_submissions ks
                    join kpis k on k.id = ks.kpi_id
                    where ks.company_id = c.id and k.target is not null and k.target <> 0
                ) as avg_achievement_pct
            from companies c
        SQL);
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('drop view if exists company_kpi_summary');
    }
};
