<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * BTS-only mechanism to force a specific quarter (Q1-Q4) open for both KPI
 * actual submission and the appraisal self-review/appraiser flow, until a
 * chosen deadline -- regardless of that quarter's own normal start/end
 * dates or the appraisal system's hardcoded submission windows. See
 * QuarterOverrideController for the BTS-gated UI that writes these.
 *
 * One row per (financial_year, quarter) in the `quarter_overrides` table
 * (database/sql/create_quarter_overrides.sql) -- setting a new override for
 * the same quarter replaces the previous deadline via upsert.
 */
class QuarterOverrideService
{
    public function __construct(private SupabaseService $supabase)
    {
    }

    /**
     * The active override row for this quarter, or null if none exists or
     * its deadline has already passed. A quarter with a past-deadline
     * override is simply closed again -- no cleanup job needed, this is
     * checked fresh on every request, same as the rest of this codebase's
     * date-range gates.
     */
    public function activeOverride(string $financialYear, string $quarter): ?array
    {
        $row = $this->supabase->first('quarter_overrides', [
            'financial_year' => 'eq.' . $financialYear,
            'quarter' => 'eq.' . $quarter,
            'select' => '*',
        ]);

        if (empty($row) || empty($row['open_until'])) {
            return null;
        }

        return Carbon::parse($row['open_until'])->isFuture() ? $row : null;
    }

    public function isActive(string $financialYear, string $quarter): bool
    {
        return $this->activeOverride($financialYear, $quarter) !== null;
    }

    /**
     * Every override row for a financial year (active or expired) -- used by
     * the BTS control page to show all four quarters' current state.
     */
    public function allForYear(string $financialYear): array
    {
        return $this->supabase->get('quarter_overrides', [
            'financial_year' => 'eq.' . $financialYear,
            'select' => '*',
        ]) ?? [];
    }

    public function setOverride(string $financialYear, string $quarter, Carbon $openUntil, ?string $createdBy, ?string $createdByName): array
    {
        $rows = $this->supabase->upsert('quarter_overrides', [
            'financial_year' => $financialYear,
            'quarter' => $quarter,
            'open_until' => $openUntil->toIso8601String(),
            'created_by' => $createdBy,
            'created_by_name' => $createdByName,
            'updated_at' => now()->toIso8601String(),
        ], 'financial_year,quarter');

        return $rows[0] ?? [];
    }

    public function clearOverride(string $financialYear, string $quarter): void
    {
        $this->supabase->delete('quarter_overrides', [
            'financial_year' => 'eq.' . $financialYear,
            'quarter' => 'eq.' . $quarter,
        ]);
    }
}
