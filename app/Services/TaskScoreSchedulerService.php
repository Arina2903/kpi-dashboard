<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * The weekly (Friday 5:30 PM MYT) and monthly (last business weekday, 5:30
 * PM MYT) batch jobs from docs/performix-design.md §3.4/§3.5: precompute
 * every active employee's Task Score snapshot, then generate their AI
 * summary — plus a team/department/company-scope summary for anyone with
 * a team (docs/performix-design.md §2 role mapping: MANAGER -> department,
 * VP -> team, SLT -> company).
 *
 * Scoring (pass 1) always re-runs — TaskScoreService's upsert makes that
 * safe and cheap. AI summary generation (pass 2) is the part that costs
 * real OpenAI calls, so it's gated by task_reminders_log the same way the
 * morning/evening reminders are (docs/performix-design.md §6-R3) — a
 * retried cron invocation must not re-bill for summaries already made.
 *
 * "Last business weekday of the month" has no clean crontab expression, so
 * runMonthly() is designed to be triggered daily in the last few days of
 * the month and no-ops on any day that isn't actually the last business
 * day — the day-of-month logic lives here, in app code, not in cron syntax.
 */
class TaskScoreSchedulerService
{
    public function __construct(
        private SupabaseService $supabase,
        private TaskScoreService $scoreService,
        private TaskAccessPolicy $policy,
        private AiService $ai,
    ) {
    }

    public function runWeekly(): array
    {
        [$start, $end] = $this->scoreService->currentPeriodBounds('weekly');

        return $this->run('weekly', $start, $end);
    }

    public function runMonthly(): array
    {
        $today = Carbon::now('Asia/Kuala_Lumpur');
        $holidays = $this->publicHolidays();

        if (!$this->isLastBusinessDayOfMonth($today, $holidays)) {
            return ['skipped' => true, 'reason' => 'not the last business day of the month'];
        }

        [$start, $end] = $this->scoreService->currentPeriodBounds('monthly');

        return $this->run('monthly', $start, $end);
    }

    private function run(string $periodType, string $start, string $end): array
    {
        $stats = ['employees_scored' => 0, 'summaries_generated' => 0, 'skipped_already_run' => 0];

        $employees = $this->supabase->get('employees', [
            'is_active' => 'eq.true',
            'select' => 'id,role,short_name,company_code',
        ]) ?? [];

        // Pass 1 — score everyone. Always re-run; the upsert in
        // TaskScoreService makes re-scoring the same period harmless.
        foreach ($employees as $employee) {
            $this->scoreService->scoreForPeriod($employee['id'], $employee['company_code'], $periodType, $start, $end);
            $stats['employees_scored']++;
        }

        // Pass 2 — AI summaries, reading the now-fully-populated snapshots
        // from pass 1 so team/department/company aggregates are complete
        // regardless of employee ordering.
        foreach ($employees as $employee) {
            if (!$this->claimSlot($employee['id'], $periodType, $start)) {
                $stats['skipped_already_run']++;
                continue;
            }

            $this->generateSummary($employee, 'employee', $periodType, $start, $end);
            $stats['summaries_generated']++;

            if ($this->policy->hasTeam($employee)) {
                $scope = match (strtoupper(trim($employee['role'] ?? ''))) {
                    'MANAGER' => 'department',
                    'SLT' => 'company',
                    default => 'team',
                };
                $this->generateSummary($employee, $scope, $periodType, $start, $end);
                $stats['summaries_generated']++;
            }
        }

        return $stats;
    }

    private function generateSummary(array $employee, string $scope, string $periodType, string $start, string $end): void
    {
        $periodLabel = $start === $end ? $start : "{$start} to {$end}";

        if ($scope === 'employee') {
            $snapshot = $this->supabase->first('task_score_snapshots', [
                'employee_id' => 'eq.' . $employee['id'],
                'period_type' => 'eq.' . $periodType,
                'period_start' => 'eq.' . $start,
                'select' => 'score,breakdown',
            ]);

            $breakdown = $snapshot['breakdown'] ?? [];
            $facts = [
                'score' => $snapshot['score'] ?? null,
                'status' => (new TaskScoreCalculator())->statusForScore(isset($snapshot['score']) ? (float) $snapshot['score'] : null),
                'scored_task_count' => $breakdown['scored_task_count'] ?? 0,
                'completed_count' => $breakdown['completed_count'] ?? 0,
                'overdue_count' => $breakdown['overdue_count'] ?? 0,
                'blocked_count' => $breakdown['blocked_count'] ?? 0,
                'on_time_pct' => $breakdown['components']['on_time']['score'] ?? null,
                'update_consistency_pct' => $breakdown['components']['update_consistency']['score'] ?? null,
            ];
            $subjectName = $employee['short_name'] ?? 'You';
        } else {
            $employeeIds = $this->policy->visibleEmployeeIds($employee);

            $members = $this->supabase->get('employees', [
                'id' => 'in.(' . implode(',', $employeeIds) . ')',
                'select' => 'id,short_name',
            ]) ?? [];

            $snapshots = $this->supabase->get('task_score_snapshots', [
                'employee_id' => 'in.(' . implode(',', $employeeIds) . ')',
                'period_type' => 'eq.' . $periodType,
                'period_start' => 'eq.' . $start,
                'select' => 'employee_id,score',
            ]) ?? [];
            $snapshotByEmployee = collect($snapshots)->keyBy('employee_id');

            $calculator = new TaskScoreCalculator();
            $memberFacts = array_map(function ($m) use ($snapshotByEmployee, $calculator) {
                $score = $snapshotByEmployee->get($m['id'])['score'] ?? null;
                return [
                    'name' => $m['short_name'],
                    'score' => $score !== null ? (float) $score : null,
                    'status' => $calculator->statusForScore($score !== null ? (float) $score : null),
                ];
            }, $members);

            $scored = array_filter($memberFacts, fn ($m) => $m['score'] !== null);
            $avgScore = count($scored) ? round(array_sum(array_column($scored, 'score')) / count($scored), 2) : null;

            $facts = [
                'score' => $avgScore,
                'status' => $calculator->statusForScore($avgScore),
                'scored_task_count' => count($scored),
                'completed_count' => null,
                'overdue_count' => count(array_filter($memberFacts, fn ($m) => $m['status'] === 'critical')),
                'blocked_count' => count(array_filter($memberFacts, fn ($m) => $m['status'] === 'at_risk')),
                'on_time_pct' => null,
                'update_consistency_pct' => null,
                'members' => $memberFacts,
            ];
            $subjectName = $employee['short_name'] ?? 'the team';
        }

        $narrativeResult = $this->ai->generateTaskSummary($subjectName, $scope, $periodType, $periodLabel, $facts);

        $previous = $this->supabase->first('ai_summaries', [
            'employee_id' => 'eq.' . $employee['id'],
            'scope' => 'eq.' . $scope,
            'period_type' => 'eq.' . $periodType,
            'period_start' => 'eq.' . $start,
            'select' => 'id',
            'order' => 'generated_at.desc',
        ]);

        $this->supabase->safeInsert('ai_summaries', [
            'employee_id' => $employee['id'],
            'scope' => $scope,
            'period_type' => $periodType,
            'period_start' => $start,
            'period_end' => $end,
            'summary_text' => $narrativeResult['narrative'],
            'facts' => $facts + ['recommendations' => $narrativeResult['recommendations']],
            'model_version' => $narrativeResult['model_version'],
            'regenerated_from_id' => $previous['id'] ?? null,
        ]);
    }

    private function claimSlot(string $employeeId, string $reminderType, string $taskDate): bool
    {
        $existing = $this->supabase->first('task_reminders_log', [
            'employee_id' => 'eq.' . $employeeId,
            'reminder_type' => 'eq.' . $reminderType,
            'task_date' => 'eq.' . $taskDate,
            'select' => 'id',
        ]);

        if ($existing) {
            return false;
        }

        try {
            $this->supabase->insert('task_reminders_log', [
                'employee_id' => $employeeId,
                'reminder_type' => $reminderType,
                'task_date' => $taskDate,
            ]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function publicHolidays(): array
    {
        return array_column($this->supabase->get('public_holidays', ['select' => 'holiday_date']) ?? [], 'holiday_date');
    }

    private function isBusinessDay(Carbon $date, array $holidays): bool
    {
        return !in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)
            && !in_array($date->toDateString(), $holidays, true);
    }

    private function isLastBusinessDayOfMonth(Carbon $today, array $holidays): bool
    {
        if (!$this->isBusinessDay($today, $holidays)) {
            return false;
        }

        $probe = $today->copy()->addDay();
        while ($probe->month === $today->month) {
            if ($this->isBusinessDay($probe, $holidays)) {
                return false;
            }
            $probe->addDay();
        }

        return true;
    }
}
