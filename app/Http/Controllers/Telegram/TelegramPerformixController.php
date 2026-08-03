<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Telegram\Concerns\ResolvesTelegramEmployee;
use App\Services\AiService;
use App\Services\SupabaseService;
use App\Services\TaskAccessPolicy;
use App\Services\TaskScoreCalculator;
use App\Services\TaskScoreService;
use Illuminate\Http\Request;

/**
 * Telegram WebView counterpart to App\Http\Controllers\PerformixInsightsController
 * — same underlying services (TaskScoreService/TaskAccessPolicy/AiService), just
 * resolved via the Telegram initData-verified employee_id/company_code instead
 * of a web session, matching how TelegramProjectTaskController mirrors
 * MiniAppTaskController.
 */
class TelegramPerformixController extends Controller
{
    use ResolvesTelegramEmployee;

    private function actorEmployee(SupabaseService $supabase, string $employeeId): ?array
    {
        return $supabase->first('employees', [
            'id' => 'eq.' . $employeeId,
            'select' => '*',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/tasks/score
    |--------------------------------------------------------------------------
    */
    public function myScore(Request $request, SupabaseService $supabase, TaskScoreService $scoreService)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'period' => 'nullable|in:daily,weekly,monthly',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $periodType = $validated['period'] ?? 'weekly';
        [$start, $end] = $scoreService->currentPeriodBounds($periodType);

        $result = $scoreService->scoreForPeriod($validated['employee_id'], $validated['company_code'], $periodType, $start, $end);

        return response()->json([
            'period_type' => $periodType,
            'period_start' => $start,
            'period_end' => $end,
            'score' => $result['score'],
            'status' => $result['status'],
            'breakdown' => $result['breakdown'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/summaries
    |--------------------------------------------------------------------------
    */
    public function summaries(Request $request, SupabaseService $supabase, TaskAccessPolicy $policy, TaskScoreService $scoreService)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'scope' => 'required|in:employee,team,department,company',
            'period' => 'required|in:daily,weekly,monthly',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $actor = $this->actorEmployee($supabase, $validated['employee_id']);

        if ($validated['scope'] !== 'employee' && (!$actor || !$policy->hasTeam($actor))) {
            return response()->json(['success' => false, 'message' => 'Not authorized for this scope.'], 403);
        }

        [$start, $end] = $scoreService->currentPeriodBounds($validated['period']);

        $summary = $supabase->first('ai_summaries', [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'scope' => 'eq.' . $validated['scope'],
            'period_type' => 'eq.' . $validated['period'],
            'period_start' => 'eq.' . $start,
            'select' => '*',
            'order' => 'generated_at.desc',
        ]);

        return response()->json([
            'period_start' => $start,
            'period_end' => $end,
            'summary' => $summary ? [
                'narrative' => $summary['summary_text'],
                'facts' => $summary['facts'],
                'model_version' => $summary['model_version'],
                'generated_at' => $summary['generated_at'],
            ] : null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/summaries/regenerate
    |--------------------------------------------------------------------------
    */
    public function regenerate(Request $request, SupabaseService $supabase, AiService $ai, TaskAccessPolicy $policy, TaskScoreService $scoreService)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'scope' => 'required|in:employee,team,department,company',
            'period' => 'required|in:daily,weekly,monthly',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $actor = $this->actorEmployee($supabase, $validated['employee_id']);
        $scope = $validated['scope'];
        $periodType = $validated['period'];

        if (!$actor || ($scope !== 'employee' && !$policy->hasTeam($actor))) {
            return response()->json(['success' => false, 'message' => 'Not authorized for this scope.'], 403);
        }

        [$start, $end] = $scoreService->currentPeriodBounds($periodType);
        $periodLabel = $start === $end ? $start : "{$start} to {$end}";

        if ($scope === 'employee') {
            $result = $scoreService->scoreForPeriod($actor['id'], $actor['company_code'], $periodType, $start, $end);
            $facts = [
                'score' => $result['score'],
                'status' => $result['status'],
                'scored_task_count' => $result['breakdown']['scored_task_count'] ?? 0,
                'completed_count' => $result['breakdown']['completed_count'] ?? 0,
                'overdue_count' => $result['breakdown']['overdue_count'] ?? 0,
                'blocked_count' => $result['breakdown']['blocked_count'] ?? 0,
                'on_time_pct' => $result['breakdown']['components']['on_time']['score'] ?? null,
                'update_consistency_pct' => $result['breakdown']['components']['update_consistency']['score'] ?? null,
            ];
            $subjectName = $actor['short_name'] ?? 'You';
        } else {
            $employeeIds = $policy->visibleEmployeeIds($actor);

            $employees = $supabase->get('employees', [
                'id' => 'in.(' . implode(',', $employeeIds) . ')',
                'select' => 'id,short_name',
            ]) ?? [];

            $snapshots = $supabase->get('task_score_snapshots', [
                'employee_id' => 'in.(' . implode(',', $employeeIds) . ')',
                'period_type' => 'eq.' . $periodType,
                'period_start' => 'eq.' . $start,
                'select' => 'employee_id,score',
            ]) ?? [];
            $snapshotByEmployee = collect($snapshots)->keyBy('employee_id');

            $calculator = new TaskScoreCalculator();
            $members = array_map(function ($e) use ($snapshotByEmployee, $calculator) {
                $score = $snapshotByEmployee->get($e['id'])['score'] ?? null;
                return [
                    'name' => $e['short_name'],
                    'score' => $score !== null ? (float) $score : null,
                    'status' => $calculator->statusForScore($score !== null ? (float) $score : null),
                ];
            }, $employees);

            $scored = array_filter($members, fn ($m) => $m['score'] !== null);
            $avgScore = count($scored) ? round(array_sum(array_column($scored, 'score')) / count($scored), 2) : null;

            $facts = [
                'score' => $avgScore,
                'status' => $calculator->statusForScore($avgScore),
                'scored_task_count' => count($scored),
                'completed_count' => null,
                'overdue_count' => count(array_filter($members, fn ($m) => $m['status'] === 'critical')),
                'blocked_count' => count(array_filter($members, fn ($m) => $m['status'] === 'at_risk')),
                'on_time_pct' => null,
                'update_consistency_pct' => null,
                'members' => $members,
            ];
            $subjectName = $actor['short_name'] ?? 'the team';
        }

        $narrativeResult = $ai->generateTaskSummary($subjectName, $scope, $periodType, $periodLabel, $facts);

        $previous = $supabase->first('ai_summaries', [
            'employee_id' => 'eq.' . $actor['id'],
            'scope' => 'eq.' . $scope,
            'period_type' => 'eq.' . $periodType,
            'period_start' => 'eq.' . $start,
            'select' => 'id',
            'order' => 'generated_at.desc',
        ]);

        $inserted = $supabase->insert('ai_summaries', [
            'employee_id' => $actor['id'],
            'scope' => $scope,
            'period_type' => $periodType,
            'period_start' => $start,
            'period_end' => $end,
            'summary_text' => $narrativeResult['narrative'],
            'facts' => $facts + ['recommendations' => $narrativeResult['recommendations']],
            'model_version' => $narrativeResult['model_version'],
            'regenerated_from_id' => $previous['id'] ?? null,
        ]);

        $summary = $inserted[0] ?? null;

        return response()->json([
            'summary' => $summary ? [
                'narrative' => $summary['summary_text'],
                'facts' => $summary['facts'],
                'model_version' => $summary['model_version'],
                'generated_at' => $summary['generated_at'],
            ] : null,
        ]);
    }
}
