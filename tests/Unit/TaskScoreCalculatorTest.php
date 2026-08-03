<?php

namespace Tests\Unit;

use App\Services\TaskScoreCalculator;
use PHPUnit\Framework\TestCase;

class TaskScoreCalculatorTest extends TestCase
{
    private TaskScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TaskScoreCalculator();
    }

    public function test_no_tasks_returns_insufficient_data(): void
    {
        $result = $this->calculator->calculate([], '2026-08-14', 0, 0);

        $this->assertNull($result['score']);
        $this->assertSame('insufficient_data', $result['status']);
    }

    public function test_perfect_on_time_high_priority_week_scores_100(): void
    {
        $tasks = [
            ['status' => 'done', 'priority' => 'critical', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00'],
            ['status' => 'done', 'priority' => 'critical', 'due_date' => '2026-08-11', 'completed_at' => '2026-08-10 10:00:00'],
        ];

        $result = $this->calculator->calculate($tasks, '2026-08-14', 5, 5);

        $this->assertSame(100.0, $result['score']);
        $this->assertSame('on_track', $result['status']);
    }

    public function test_cancelled_tasks_do_not_affect_score(): void
    {
        $tasks = [
            ['status' => 'done', 'priority' => 'critical', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00'],
        ];

        $withCancelled = $tasks;
        $withCancelled[] = ['status' => 'cancelled', 'priority' => 'critical', 'due_date' => '2026-08-01', 'completed_at' => null];

        $baseline = $this->calculator->calculate($tasks, '2026-08-14', 5, 5);
        $withCancelledResult = $this->calculator->calculate($withCancelled, '2026-08-14', 5, 5);

        $this->assertSame($baseline['score'], $withCancelledResult['score']);
        $this->assertSame(1, $baseline['breakdown']['scored_task_count']);
        $this->assertSame(1, $withCancelledResult['breakdown']['scored_task_count']);
    }

    public function test_volume_of_completed_trivial_tasks_does_not_exceed_ratio_cap(): void
    {
        $twoTasks = [
            ['status' => 'done', 'priority' => 'low', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00'],
            ['status' => 'done', 'priority' => 'low', 'due_date' => '2026-08-11', 'completed_at' => '2026-08-10 10:00:00'],
        ];

        $twentyTasks = [];
        for ($i = 0; $i < 20; $i++) {
            $twentyTasks[] = ['status' => 'done', 'priority' => 'low', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00'];
        }

        $small = $this->calculator->calculate($twoTasks, '2026-08-14', 5, 5);
        $large = $this->calculator->calculate($twentyTasks, '2026-08-14', 5, 5);

        // Same ratio (100% of a low-priority-only mix), regardless of volume —
        // creating ten times as many trivial tasks doesn't inflate the score.
        $this->assertSame($small['score'], $large['score']);
    }

    public function test_all_low_priority_completions_score_below_all_critical_completions(): void
    {
        $lowMix = [
            ['status' => 'done', 'priority' => 'low', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00'],
            ['status' => 'done', 'priority' => 'low', 'due_date' => '2026-08-11', 'completed_at' => '2026-08-10 10:00:00'],
        ];
        $criticalMix = [
            ['status' => 'done', 'priority' => 'critical', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00'],
            ['status' => 'done', 'priority' => 'critical', 'due_date' => '2026-08-11', 'completed_at' => '2026-08-10 10:00:00'],
        ];

        $low = $this->calculator->calculate($lowMix, '2026-08-14', 5, 5);
        $critical = $this->calculator->calculate($criticalMix, '2026-08-14', 5, 5);

        $this->assertLessThan($critical['score'], $low['score']);
        $this->assertSame(100.0, $critical['score']);
    }

    public function test_overdue_critical_task_penalizes_more_than_overdue_low_task(): void
    {
        $baselineDone = ['status' => 'done', 'priority' => 'critical', 'due_date' => '2026-08-05', 'completed_at' => '2026-08-04 10:00:00'];

        $withLowOverdue = [$baselineDone, ['status' => 'in_progress', 'priority' => 'low', 'due_date' => '2026-08-01', 'completed_at' => null]];
        $withCriticalOverdue = [$baselineDone, ['status' => 'in_progress', 'priority' => 'critical', 'due_date' => '2026-08-01', 'completed_at' => null]];

        $lowOverdueResult = $this->calculator->calculate($withLowOverdue, '2026-08-14', 5, 5);
        $criticalOverdueResult = $this->calculator->calculate($withCriticalOverdue, '2026-08-14', 5, 5);

        $this->assertGreaterThan($lowOverdueResult['breakdown']['overdue_penalty'] * 2, $criticalOverdueResult['breakdown']['overdue_penalty']);
        $this->assertGreaterThan($criticalOverdueResult['score'], $lowOverdueResult['score']);
    }

    public function test_open_task_not_yet_past_due_incurs_no_penalty(): void
    {
        $tasks = [
            ['status' => 'done', 'priority' => 'critical', 'due_date' => '2026-08-05', 'completed_at' => '2026-08-04 10:00:00'],
            ['status' => 'in_progress', 'priority' => 'critical', 'due_date' => '2026-08-20', 'completed_at' => null],
        ];

        $result = $this->calculator->calculate($tasks, '2026-08-14', 5, 5);

        $this->assertSame(0.0, $result['breakdown']['overdue_penalty']);
    }

    public function test_on_time_component_ignores_tasks_without_due_date(): void
    {
        $tasks = [
            ['status' => 'done', 'priority' => 'medium', 'due_date' => null, 'completed_at' => '2026-08-04 10:00:00'],
        ];

        $result = $this->calculator->calculate($tasks, '2026-08-14', 0, 0);

        $this->assertNull($result['breakdown']['components']['on_time']);
        $this->assertNotNull($result['breakdown']['components']['completion_rate']);
        $this->assertSame(100.0, $result['breakdown']['components']['completion_rate']['score']);
    }

    public function test_update_consistency_ratio_caps_at_100(): void
    {
        $tasks = [
            ['status' => 'done', 'priority' => 'medium', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00'],
        ];

        // More update-days logged than business days expected shouldn't be
        // possible in practice, but the ratio must still clamp at 100.
        $result = $this->calculator->calculate($tasks, '2026-08-14', 5, 6);

        $this->assertSame(100.0, $result['breakdown']['components']['update_consistency']['score']);
    }

    public function test_breakdown_carries_formula_version_for_audit_trail(): void
    {
        $result = $this->calculator->calculate(
            [['status' => 'done', 'priority' => 'medium', 'due_date' => '2026-08-10', 'completed_at' => '2026-08-09 10:00:00']],
            '2026-08-14',
            5,
            5
        );

        $this->assertSame(TaskScoreCalculator::FORMULA_VERSION, $result['breakdown']['formula_version']);
        $this->assertArrayHasKey('scored_task_count', $result['breakdown']);
        $this->assertArrayHasKey('pre_penalty_score', $result['breakdown']);
    }

    public function test_status_thresholds_match_spec(): void
    {
        $this->assertSame('on_track', $this->calculator->statusForScore(80.0));
        $this->assertSame('on_track', $this->calculator->statusForScore(100.0));
        $this->assertSame('at_risk', $this->calculator->statusForScore(79.99));
        $this->assertSame('at_risk', $this->calculator->statusForScore(60.0));
        $this->assertSame('critical', $this->calculator->statusForScore(59.99));
        $this->assertSame('critical', $this->calculator->statusForScore(0.0));
        $this->assertSame('insufficient_data', $this->calculator->statusForScore(null));
    }

    public function test_zero_scored_tasks_is_insufficient_data_even_with_perfect_consistency(): void
    {
        // No tasks at all this period, but the employee logged an update on
        // something out of scope — a lone consistency signal must not carry
        // an empty period to a real "on track" score.
        $result = $this->calculator->calculate([], '2026-08-09', 1, 1);

        $this->assertNull($result['score']);
        $this->assertSame('insufficient_data', $result['status']);
        $this->assertNull($result['breakdown']['components']['completion_rate']);
        $this->assertNotNull($result['breakdown']['components']['update_consistency']);
    }

    public function test_breakdown_exposes_raw_counts_not_just_percentages(): void
    {
        $tasks = [
            ['status' => 'done', 'priority' => 'medium', 'due_date' => '2026-08-05', 'completed_at' => '2026-08-04 10:00:00'],
            ['status' => 'blocked', 'priority' => 'medium', 'due_date' => '2026-08-20', 'completed_at' => null],
            ['status' => 'in_progress', 'priority' => 'low', 'due_date' => '2026-08-01', 'completed_at' => null],
        ];

        $result = $this->calculator->calculate($tasks, '2026-08-14', 5, 5);

        $this->assertSame(1, $result['breakdown']['completed_count']);
        $this->assertSame(1, $result['breakdown']['blocked_count']);
        $this->assertSame(1, $result['breakdown']['overdue_count']);
    }

    public function test_score_never_goes_below_zero_under_heavy_penalty(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $tasks[] = ['status' => 'in_progress', 'priority' => 'critical', 'due_date' => '2026-08-01', 'completed_at' => null];
        }

        $result = $this->calculator->calculate($tasks, '2026-08-14', 5, 0);

        $this->assertSame(0.0, $result['score']);
        $this->assertSame('critical', $result['status']);
    }
}
