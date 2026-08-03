<?php

namespace App\Services;

/**
 * Pure scoring function — no DB access, no Supabase, no session. Every rule
 * in docs/performix-design.md's "TASK SCORE" section is implemented here as
 * a deliberate, testable piece of arithmetic, not something callers can
 * accidentally bypass by shaping their input differently. TaskScoreService
 * is the thing that fetches real data and calls this; this class only ever
 * sees plain arrays so tests/Unit/TaskScoreCalculatorTest.php can pin down
 * the anti-gaming rules without touching Supabase.
 *
 * Anti-gaming rules and how they're structurally enforced:
 *   - "Do not reward users simply for creating more tasks" — every component
 *     is a ratio (weighted completed / weighted eligible), not a raw count,
 *     so adding uncompleted tasks only ever dilutes or penalizes, never
 *     inflates.
 *   - "Cancelled tasks do not improve scores" — cancelled tasks are dropped
 *     before any component is computed; they're invisible to the score,
 *     neither helping nor hurting.
 *   - "Repeated trivial tasks must not inflate scores" — completion/on-time
 *     are weighted by priority (PRIORITY_WEIGHT), and priority_impact caps
 *     out at the average priority weight of completed work, so an all-"low"
 *     task mix can never reach the same ceiling as one that includes real
 *     priority work.
 *   - "Overdue high-priority tasks must reduce the score more heavily" — a
 *     separate, explicit overdue_penalty subtracted after the weighted
 *     average, scaled by OVERDUE_PENALTY per priority.
 *   - "Keep the full score calculation audit trail" — every component score,
 *     weight, the raw pre-penalty sum, and a formula_version are returned in
 *     breakdown for the caller to persist verbatim into
 *     task_score_snapshots.breakdown.
 */
class TaskScoreCalculator
{
    public const FORMULA_VERSION = 'v1';

    private const PRIORITY_WEIGHT = [
        'low' => 1.0,
        'medium' => 1.5,
        'high' => 2.0,
        'critical' => 3.0,
    ];

    private const MAX_PRIORITY_WEIGHT = 3.0;

    private const OVERDUE_PENALTY = [
        'low' => 1.0,
        'medium' => 2.0,
        'high' => 5.0,
        'critical' => 8.0,
    ];

    private const COMPONENT_WEIGHTS = [
        'completion_rate' => 40,
        'on_time' => 25,
        'update_consistency' => 20,
        'priority_impact' => 15,
    ];

    /**
     * @param array $tasks Each entry: ['status' => string, 'priority' => string,
     *   'due_date' => ?string ('Y-m-d'), 'completed_at' => ?string ('Y-m-d H:i:s')].
     *   completed_at is only meaningful when status === 'done' — the caller
     *   derives it from the telegram_project_task_updates row where
     *   status_at_update first became 'done'.
     * @param string $periodEnd 'Y-m-d' — tasks still open past this date are
     *   overdue for penalty purposes.
     * @param int $expectedUpdateDays business days elapsed in the period so
     *   far (caller computes this against public_holidays + weekends).
     * @param int $actualUpdateDays distinct days within the period on which
     *   the employee logged at least one task update.
     */
    public function calculate(
        array $tasks,
        string $periodEnd,
        int $expectedUpdateDays,
        int $actualUpdateDays
    ): array {

        $scored = array_values(array_filter(
            $tasks,
            fn ($t) => ($t['status'] ?? null) !== 'cancelled'
        ));

        $completedWeight = 0.0;
        $totalWeight = 0.0;
        $onTimeWeight = 0.0;
        $onTimeEligibleWeight = 0.0;
        $impactWeightSum = 0.0;
        $completedCount = 0;
        $overdueCount = 0;
        $blockedCount = 0;
        $overduePenalty = 0.0;

        foreach ($scored as $task) {
            $priority = $task['priority'] ?? 'medium';
            $weight = self::PRIORITY_WEIGHT[$priority] ?? self::PRIORITY_WEIGHT['medium'];
            $isDone = ($task['status'] ?? null) === 'done';

            $totalWeight += $weight;

            if (($task['status'] ?? null) === 'blocked') {
                $blockedCount++;
            }

            if ($isDone) {
                $completedWeight += $weight;
                $completedCount++;
                $impactWeightSum += $weight;

                if (!empty($task['due_date'])) {
                    $onTimeEligibleWeight += $weight;

                    if (!empty($task['completed_at']) && $task['completed_at'] <= $task['due_date'] . ' 23:59:59') {
                        $onTimeWeight += $weight;
                    }
                }

                continue;
            }

            if (!empty($task['due_date']) && $task['due_date'] < $periodEnd) {
                $overdueCount++;
                $overduePenalty += self::OVERDUE_PENALTY[$priority] ?? self::OVERDUE_PENALTY['medium'];
            }
        }

        $components = [
            'completion_rate' => $totalWeight > 0
                ? ['score' => $this->pct($completedWeight, $totalWeight), 'weight' => self::COMPONENT_WEIGHTS['completion_rate']]
                : null,

            'on_time' => $onTimeEligibleWeight > 0
                ? ['score' => $this->pct($onTimeWeight, $onTimeEligibleWeight), 'weight' => self::COMPONENT_WEIGHTS['on_time']]
                : null,

            'update_consistency' => $expectedUpdateDays > 0
                ? ['score' => $this->pct($actualUpdateDays, $expectedUpdateDays), 'weight' => self::COMPONENT_WEIGHTS['update_consistency']]
                : null,

            'priority_impact' => $completedCount > 0
                ? ['score' => $this->pct($impactWeightSum, $completedCount * self::MAX_PRIORITY_WEIGHT), 'weight' => self::COMPONENT_WEIGHTS['priority_impact']]
                : null,
        ];

        $applicable = array_filter($components, fn ($c) => $c !== null);

        $breakdown = [
            'formula_version' => self::FORMULA_VERSION,
            'components' => $components,
            'overdue_penalty' => round($overduePenalty, 2),
            'scored_task_count' => count($scored),
            'completed_count' => $completedCount,
            'overdue_count' => $overdueCount,
            'blocked_count' => $blockedCount,
        ];

        // completion_rate is null exactly when there were zero scored tasks
        // this period ($totalWeight only grows via a scored task). A lone
        // update_consistency signal from unrelated work must not carry a
        // period with nothing due to a real score — that's insufficient
        // data, not "on track".
        if ($components['completion_rate'] === null) {
            return [
                'score' => null,
                'status' => $this->statusForScore(null),
                'breakdown' => $breakdown + ['note' => 'Not enough data to score this period.'],
            ];
        }

        $totalApplicableWeight = array_sum(array_column($applicable, 'weight'));
        $weightedSum = 0.0;

        foreach ($applicable as $component) {
            $weightedSum += $component['score'] * ($component['weight'] / $totalApplicableWeight);
        }

        $finalScore = max(0.0, min(100.0, round($weightedSum - $overduePenalty, 2)));

        return [
            'score' => $finalScore,
            'status' => $this->statusForScore($finalScore),
            'breakdown' => $breakdown + ['pre_penalty_score' => round($weightedSum, 2)],
        ];
    }

    /**
     * On Track: 80-100, At Risk: 60-79, Critical: below 60 — per
     * docs/performix-design.md TASK SCORE section.
     */
    public function statusForScore(?float $score): string
    {
        if ($score === null) {
            return 'insufficient_data';
        }

        if ($score >= 80) {
            return 'on_track';
        }

        if ($score >= 60) {
            return 'at_risk';
        }

        return 'critical';
    }

    private function pct(float $numerator, float $denominator): float
    {
        return round(min(100.0, ($numerator / $denominator) * 100), 2);
    }
}
