<?php

namespace App\Services;

class KpiQuarterUpdateService
{
    public function __construct(private SupabaseService $supabase)
    {
    }

    private function nowMy(): string
    {
        return now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString();
    }

    public function calculateAchievement($baseTarget, $stretchTarget, $actualValue): float
    {
        $base = max(0, (float) ($baseTarget ?? 0));
        $stretch = max($base, (float) ($stretchTarget ?? 0));
        $actual = max(0, (float) ($actualValue ?? 0));

        if ($base <= 0) {
            return 0;
        }

        if ($actual <= $base) {
            return round(($actual / $base) * 100, 2);
        }

        if ($stretch > $base) {
            return round(min(200, 100 + (($actual - $base) / ($stretch - $base)) * 100), 2);
        }

        return 100;
    }

    /**
     * Finds a KPI's currently-open quarter (today falls between its start
     * and end dates), or null if none is open right now.
     */
    public function findOpenQuarter(string $kpiId, string $today): ?array
    {
        $quarters = $this->supabase->get('kpi_quarters', [
            'kpi_id' => 'eq.' . $kpiId,
            'select' => '*',
        ]) ?? [];

        foreach ($quarters as $q) {
            if (!empty($q['start_date']) && !empty($q['end_date']) && $q['start_date'] <= $today && $q['end_date'] >= $today) {
                return $q;
            }
        }

        return null;
    }

    /**
     * Writes a quarter's new actual, recomputes the KPI's annual actual_value
     * and achievement_percentage from all quarters, and persists both.
     */
    public function applyQuarterActualChange(array $kpi, array $quarter, float $newQuarterActual): array
    {
        $this->supabase->safePatch('kpi_quarters', ['id' => 'eq.' . $quarter['id']], [
            'quarter_actual' => $newQuarterActual,
            'updated_at' => $this->nowMy(),
        ]);

        $allQuarters = $this->supabase->get('kpi_quarters', [
            'kpi_id' => 'eq.' . $kpi['id'],
            'select' => '*',
        ]) ?? [];

        $totalActual = collect($allQuarters)->sum(function ($q) use ($quarter, $newQuarterActual) {
            return (float) ($q['id'] === $quarter['id'] ? $newQuarterActual : ($q['quarter_actual'] ?? 0));
        });

        $achievement = $this->calculateAchievement($kpi['base_target'] ?? 0, $kpi['stretch_target'] ?? 0, $totalActual);

        $this->supabase->safePatch('kpis', ['id' => 'eq.' . $kpi['id']], [
            'actual_value' => $totalActual,
            'achievement_percentage' => $achievement,
            'updated_at' => $this->nowMy(),
        ]);

        return [
            'quarter_id' => $quarter['id'],
            'quarter_actual' => $newQuarterActual,
            'actual_value' => $totalActual,
            'achievement_percentage' => $achievement,
        ];
    }

    /**
     * Applies $delta to a KPI's currently-open quarter. Returns an array with
     * an 'error' key if there's no open quarter or the delta would take the
     * quarter's actual below 0.
     */
    public function applyDeltaToOpenQuarter(array $kpi, float $delta, string $today): array
    {
        $quarter = $this->findOpenQuarter($kpi['id'], $today);

        if (!$quarter) {
            return ['error' => 'No open quarter right now for "' . $kpi['kpi_title'] . '".'];
        }

        $liveActual = (float) ($quarter['quarter_actual'] ?? 0);
        $newQuarterActual = $liveActual + $delta;

        if ($newQuarterActual < 0) {
            return ['error' => "Can't reduce — {$quarter['quarter']}'s actual for \"{$kpi['kpi_title']}\" is only {$liveActual}."];
        }

        return $this->applyQuarterActualChange($kpi, $quarter, $newQuarterActual);
    }
}
