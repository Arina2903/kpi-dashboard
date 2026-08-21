<?php

namespace App\Http\Controllers;

use App\Services\AppraiserDelegationService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * BTS-only: stand a Manager's own VP in as appraiser for that Manager's
 * Executives while the Manager is on long leave (see
 * AppraiserDelegationService for the resolution logic this feeds). Rendered
 * as a section on the Quarter Control page (QuarterOverrideController::index()
 * supplies the list data); this controller only ever handles the write side.
 *
 * Gated the same way every other BTS-only feature in this app is --
 * Controller::isBtsSession(), checked per-method (there's no BTS middleware
 * in this codebase) -- not by route middleware.
 */
class AppraiserDelegationController extends Controller
{
    public function __construct(private AppraiserDelegationService $delegations)
    {
    }

    private function ensureBts(): void
    {
        abort_unless($this->isBtsSession(), 403, 'BTS access only.');
    }

    public function store(Request $request, SupabaseService $supabase)
    {
        $this->ensureBts();

        $validated = $request->validate([
            'manager_id' => 'required|string',
            'reason'     => 'nullable|string|max:500',
        ]);

        $manager = $supabase->first('employees', [
            'id'        => 'eq.' . $validated['manager_id'],
            'is_active' => 'eq.true',
            'select'    => 'id,short_name,role,vp_id,reports_to_id',
        ]);

        if (!$manager) {
            return back()->with('error', 'Employee not found.');
        }

        if (strtoupper(trim($manager['role'] ?? '')) !== 'MANAGER') {
            return back()->with('error', "{$manager['short_name']} isn't a Manager — this feature only substitutes a VP for an absent Manager's own appraisals of their Executives.");
        }

        // Deliberately the manager's OWN resolved parent, never a value the
        // form could submit directly -- so the substitute is always someone
        // who'd already be next in that manager's real chain (still
        // "melalui role"), and a VP's own duty can never be delegated onward
        // through this same field (there's no case for it — see
        // AppraiserDelegationService's docblock).
        $delegateToId = $manager['vp_id'] ?? $manager['reports_to_id'] ?? null;

        if (!$delegateToId) {
            return back()->with('error', "{$manager['short_name']} has no VP on record (vp_id/reports_to_id both empty) — nothing to delegate to.");
        }

        $delegate = $supabase->first('employees', [
            'id'        => 'eq.' . $delegateToId,
            'is_active' => 'eq.true',
            'select'    => 'id,short_name',
        ]);

        if (!$delegate) {
            return back()->with('error', "{$manager['short_name']}'s recorded VP is missing or inactive — nothing to delegate to.");
        }

        try {
            $this->delegations->setDelegate(
                $manager['id'],
                $delegate['id'],
                $validated['reason'] ?? null,
                session('employee_uuid'),
                session('employee.short_name') ?? session('short_name')
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AppraiserDelegationController::store failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Could not save the delegation — the appraiser_delegations table may not exist yet. Run database/sql/create_appraiser_delegations.sql in Supabase first.');
        }

        return back()->with('success', "{$delegate['short_name']} will now appraise {$manager['short_name']}'s Executives while {$manager['short_name']} is away.");
    }

    public function destroy(Request $request, string $managerId)
    {
        $this->ensureBts();

        try {
            $this->delegations->clearDelegate($managerId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AppraiserDelegationController::destroy failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Could not end the delegation.');
        }

        return back()->with('success', 'Appraiser delegation ended — back to the normal reporting chain.');
    }
}
