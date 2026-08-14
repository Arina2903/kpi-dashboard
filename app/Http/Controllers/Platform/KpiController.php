<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\LogsAdminActions;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * KPI categories and KPI definitions, scoped to one company at a time.
 * Viewing is open to any member of the company (department users need to
 * know what KPIs apply to them before Phase 7 adds submissions); creating is
 * restricted to Company Admins — enforced twice, here for a clean redirect
 * and in `kpi_categories_insert`/`kpis_insert` for the real guarantee.
 */
class KpiController extends Controller
{
    use LogsAdminActions;

    private function ensureCompanyMember(Request $request, string $company): void
    {
        $platformUser = $request->attributes->get('platformUser');

        if ($platformUser['is_super_admin'] ?? false) {
            return;
        }

        $isMember = collect($platformUser['company_memberships'] ?? [])
            ->contains(fn ($m) => $m['company_id'] === $company);

        abort_unless($isMember, 403, 'You are not a member of this company.');
    }

    private function ensureCompanyAdmin(Request $request, string $company): void
    {
        $platformUser = $request->attributes->get('platformUser');

        if ($platformUser['is_super_admin'] ?? false) {
            return;
        }

        $isCompanyAdmin = collect($platformUser['company_memberships'] ?? [])
            ->contains(fn ($m) => $m['company_id'] === $company && $m['role'] === 'company_admin');

        abort_unless($isCompanyAdmin, 403, 'You are not an admin of this company.');
    }

    public function index(Request $request, string $company)
    {
        $this->ensureCompanyMember($request, $company);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $companyRow = $supabase->first('companies', [
            'id' => 'eq.' . $company,
            'select' => 'id,name,code',
        ]);

        $categories = $supabase->get('kpi_categories', [
            'company_id' => 'eq.' . $company,
            'select' => '*',
            'order' => 'name.asc',
        ]);

        $kpis = $supabase->get('kpis', [
            'company_id' => 'eq.' . $company,
            'select' => '*,kpi_categories(name)',
            'order' => 'created_at.desc',
        ]);

        return Inertia::render('Platform/Kpis/Index', [
            'company' => $companyRow,
            'categories' => $categories,
            'kpis' => $kpis,
        ]);
    }

    public function storeCategory(Request $request, string $company)
    {
        $this->ensureCompanyAdmin($request, $company);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $supabase->insert('kpi_categories', [
                'company_id' => $company,
                'name' => $request->name,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create category: ' . $e->getMessage());
        }

        if ($logFailure = $this->logIfSuperAdmin($request, 'create_kpi_category', $company, ['name' => $request->name])) {
            return $logFailure;
        }

        return back()->with('success', 'Category "' . $request->name . '" created.');
    }

    public function store(Request $request, string $company)
    {
        $this->ensureCompanyAdmin($request, $company);

        $request->validate([
            'category_id' => 'nullable|uuid',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,custom',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $supabase->insert('kpis', [
                'company_id' => $company,
                'category_id' => $request->category_id ?: null,
                'name' => $request->name,
                'description' => $request->description,
                'target' => $request->target,
                'unit' => $request->unit,
                'frequency' => $request->frequency,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create KPI: ' . $e->getMessage());
        }

        if ($logFailure = $this->logIfSuperAdmin($request, 'create_kpi', $company, ['name' => $request->name])) {
            return $logFailure;
        }

        return back()->with('success', 'KPI "' . $request->name . '" created.');
    }
}
