<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Deliberately queries `companies` with no filter at all — RLS is what
     * decides whether one row comes back or all of them. A Company Admin
     * seeing only their own company here (not because this controller
     * filtered it, but because Postgres did) is the actual proof that
     * isolation works.
     */
    public function index(Request $request)
    {
        $platformUser = $request->attributes->get('platformUser');

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $companies = $supabase->get('companies', ['select' => '*']);

        // `company_kpi_summary` is a Postgres view with `security_invoker`,
        // so this returns exactly the same set of companies RLS already
        // scoped `companies` to above — the aggregation happens in Postgres,
        // never by summing kpi_submissions rows here.
        try {
            $summaries = $supabase->get('company_kpi_summary', ['select' => '*']);
        } catch (\Throwable) {
            $summaries = [];
        }
        $summariesByCompany = collect($summaries)->keyBy('company_id');

        $companiesWithStats = collect($companies)->map(function ($company) use ($summariesByCompany) {
            $summary = $summariesByCompany->get($company['id']);

            return $company + [
                'department_count' => $summary['department_count'] ?? 0,
                'user_count' => $summary['user_count'] ?? 0,
                'kpi_count' => $summary['kpi_count'] ?? 0,
                'submission_count' => $summary['submission_count'] ?? 0,
                'avg_achievement_pct' => $summary['avg_achievement_pct'] ?? null,
            ];
        })->values();

        return Inertia::render('Platform/Dashboard', [
            'me' => $platformUser,
            'visibleCompanies' => $companiesWithStats,
        ]);
    }
}