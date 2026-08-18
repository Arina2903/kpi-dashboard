<?php

namespace App\Http\Controllers;

use App\Services\QuarterOverrideService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * BTS-only "Quarter Control": lets BTS force a specific quarter open for
 * both KPI actual submission and the appraisal self-review/appraiser flow,
 * until a chosen deadline -- see QuarterOverrideService for what that
 * actually changes, and where it's read (KpiQuarterUpdateService,
 * KpiController::updateQuarterActual/enrichKpiRecord,
 * PerformanceController's window checks).
 *
 * Gated the same way every other BTS-only feature in this app is --
 * Controller::isBtsSession(), checked per-method (there's no BTS
 * middleware in this codebase) -- not by route middleware.
 */
class QuarterOverrideController extends Controller
{
    private const QUARTERS = ['Q1', 'Q2', 'Q3', 'Q4'];

    public function __construct(private QuarterOverrideService $overrides)
    {
    }

    private function ensureBts(): void
    {
        abort_unless($this->isBtsSession(), 403, 'BTS access only.');
    }

    private function currentFY(): string
    {
        return 'FY' . now()->year;
    }

    public function index(Request $request)
    {
        $this->ensureBts();

        $financialYear = $request->query('financial_year', $this->currentFY());
        $rows = collect($this->overrides->allForYear($financialYear))->keyBy('quarter');

        $quarters = collect(self::QUARTERS)->map(function (string $quarter) use ($rows) {
            $row = $rows->get($quarter);
            $openUntil = $row['open_until'] ?? null;
            $isActive = $openUntil && Carbon::parse($openUntil)->isFuture();

            return [
                'quarter' => $quarter,
                'is_active' => $isActive,
                'open_until' => $openUntil,
                'open_until_display' => $openUntil ? Carbon::parse($openUntil)->timezone('Asia/Kuala_Lumpur')->format('d M Y, g:i A') : null,
                'created_by_name' => $row['created_by_name'] ?? null,
            ];
        })->all();

        return view('admin.quarter-control', [
            'financialYear' => $financialYear,
            'quarters' => $quarters,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureBts();

        $validated = $request->validate([
            'financial_year' => 'nullable|string|max:20',
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
            'open_until' => 'required|date|after:now',
        ]);

        $financialYear = $validated['financial_year'] ?: $this->currentFY();
        $openUntil = Carbon::parse($validated['open_until'], 'Asia/Kuala_Lumpur');

        $this->overrides->setOverride(
            $financialYear,
            $validated['quarter'],
            $openUntil,
            session('employee_uuid'),
            session('employee.short_name') ?? session('short_name')
        );

        return back()->with('success', "{$validated['quarter']} ({$financialYear}) is now open until {$openUntil->format('d M Y, g:i A')}.");
    }

    public function destroy(Request $request, string $quarter)
    {
        $this->ensureBts();

        $quarter = strtoupper($quarter);
        abort_unless(in_array($quarter, self::QUARTERS, true), 404);

        $financialYear = $request->input('financial_year', $this->currentFY());
        $this->overrides->clearOverride($financialYear, $quarter);

        return back()->with('success', "{$quarter} ({$financialYear}) override closed.");
    }
}
