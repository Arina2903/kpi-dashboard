<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Fetches real task data for one employee/period, shapes it into the plain
 * arrays TaskScoreCalculator expects, and persists the result into
 * task_score_snapshots (docs/performix-design.md §1, §6-R5 — scores are
 * precomputed by scheduled jobs, never calculated live on a dashboard load).
 *
 * All the actual scoring rules live in TaskScoreCalculator, which is unit
 * tested in isolation. This class only answers "what happened, in plain
 * data" — it has no scoring logic of its own.
 */
class TaskScoreService
{
    protected SupabaseService $supabase;
    protected TaskScoreCalculator $calculator;

    public function __construct(
        SupabaseService $supabase,
        TaskScoreCalculator $calculator
    ) {
        $this->supabase = $supabase;
        $this->calculator = $calculator;
    }

    /**
     * The [start, end] date range for "the current period" of a given type,
     * anchored on today (Asia/Kuala_Lumpur) — the one piece of period math
     * every caller needs (this service, the summary regenerate endpoint,
     * and the weekly/monthly cron jobs), so it lives in one place.
     */
    public function currentPeriodBounds(string $periodType): array
    {
        $today = Carbon::now('Asia/Kuala_Lumpur');

        return match ($periodType) {
            'weekly' => [$today->copy()->startOfWeek(Carbon::MONDAY)->toDateString(), $today->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()],
            'monthly' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            default => [$today->toDateString(), $today->toDateString()],
        };
    }

    /**
     * Computes and upserts the task_score_snapshots row for one employee's
     * period. Idempotent on (employee_id, period_type, period_start) — safe
     * to re-run for the same period (e.g. a scheduler retry, or an explicit
     * "recalculate" action).
     */
    public function scoreForPeriod(
        string $employeeId,
        string $companyCode,
        string $periodType,
        string $periodStart,
        string $periodEnd
    ): array {

        $tasks = $this->scorableTasks($employeeId, $companyCode, $periodStart, $periodEnd);
        [$expectedDays, $actualDays] = $this->updateConsistency($employeeId, $periodStart, $periodEnd);

        $result = $this->calculator->calculate($tasks, $periodEnd, $expectedDays, $actualDays);

        $this->persist($employeeId, $periodType, $periodStart, $periodEnd, $result);

        return $result;
    }

    /**
     * Every task assigned to this employee that's relevant to this period:
     * due within the period, undated but created within the period, or
     * still open and overdue from before the period (so overdue carryover
     * keeps being penalized until it's resolved, per docs/performix-design.md
     * TASK SCORE section).
     */
    private function scorableTasks(
        string $employeeId,
        string $companyCode,
        string $periodStart,
        string $periodEnd
    ): array {

        $tasks = $this->supabase->get('telegram_project_tasks', [
            'assignee_employee_id' => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'select' => '*',
        ]) ?? [];

        $relevant = array_values(array_filter($tasks, function ($task) use ($periodStart, $periodEnd) {
            $dueDate = $task['due_date'] ?? null;

            if ($dueDate) {
                if ($dueDate >= $periodStart && $dueDate <= $periodEnd) {
                    return true;
                }

                // Still-open overdue carryover from before this period.
                return !in_array($task['status'], ['done', 'cancelled'], true) && $dueDate < $periodEnd;
            }

            $createdDate = substr($task['created_at'] ?? '', 0, 10);

            return $createdDate >= $periodStart && $createdDate <= $periodEnd;
        }));

        if (empty($relevant)) {
            return [];
        }

        $taskIds = array_column($relevant, 'id');
        $updates = $this->supabase->get('telegram_project_task_updates', [
            'task_id' => 'in.(' . implode(',', $taskIds) . ')',
            'status_at_update' => 'eq.done',
            'select' => 'task_id,created_at',
            'order' => 'created_at.asc',
        ]) ?? [];

        // First time each task's status became 'done' — the completion
        // timestamp the on-time component judges against.
        $completedAtByTask = [];
        foreach ($updates as $update) {
            if (!isset($completedAtByTask[$update['task_id']])) {
                $completedAtByTask[$update['task_id']] = $update['created_at'];
            }
        }

        return array_map(function ($task) use ($completedAtByTask) {
            return [
                'status' => $task['status'],
                'priority' => $task['priority'] ?? 'medium',
                'due_date' => $task['due_date'] ?? null,
                'completed_at' => $task['status'] === 'done'
                    ? ($completedAtByTask[$task['id']] ?? $task['updated_at'] ?? null)
                    : null,
            ];
        }, $relevant);
    }

    /**
     * Expected business days (weekends + public_holidays excluded) from
     * periodStart up to whichever is earlier — periodEnd or today — plus the
     * distinct calendar days on which this employee logged at least one
     * task update, same window. Same weekday/holiday pattern already used
     * in AttendanceController for working-day calculations.
     */
    private function updateConsistency(
        string $employeeId,
        string $periodStart,
        string $periodEnd
    ): array {

        $today = now('Asia/Kuala_Lumpur')->toDateString();
        $effectiveEnd = min($periodEnd, $today);

        if ($effectiveEnd < $periodStart) {
            return [0, 0];
        }

        $holidays = array_column(
            $this->supabase->get('public_holidays', ['select' => 'holiday_date']) ?? [],
            'holiday_date'
        );

        $expectedDays = 0;
        for ($d = Carbon::parse($periodStart); $d->toDateString() <= $effectiveEnd; $d->addDay()) {
            $ds = $d->toDateString();
            if (!in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true) && !in_array($ds, $holidays, true)) {
                $expectedDays++;
            }
        }

        $updates = $this->supabase->get('telegram_project_task_updates', [
            'updated_by_employee_id' => 'eq.' . $employeeId,
            'created_at' => 'gte.' . $periodStart . ' 00:00:00',
            'select' => 'created_at',
        ]) ?? [];

        $actualDays = collect($updates)
            ->map(fn ($u) => substr($u['created_at'], 0, 10))
            ->filter(fn ($ds) => $ds >= $periodStart && $ds <= $effectiveEnd)
            ->unique()
            ->count();

        return [$expectedDays, $actualDays];
    }

    private function persist(
        string $employeeId,
        string $periodType,
        string $periodStart,
        string $periodEnd,
        array $result
    ): void {

        $existing = $this->supabase->first('task_score_snapshots', [
            'employee_id' => 'eq.' . $employeeId,
            'period_type' => 'eq.' . $periodType,
            'period_start' => 'eq.' . $periodStart,
            'select' => 'id',
        ]);

        $payload = [
            'employee_id' => $employeeId,
            'period_type' => $periodType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'score' => $result['score'],
            'breakdown' => $result['breakdown'] + ['status' => $result['status']],
            'calculated_at' => now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString(),
        ];

        if ($existing) {
            $this->supabase->safePatch('task_score_snapshots', ['id' => 'eq.' . $existing['id']], $payload);
        } else {
            $this->supabase->safeInsert('task_score_snapshots', $payload);
        }
    }
}
