<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Telegram\Concerns\ResolvesTelegramEmployee;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class TelegramMiniAppController extends Controller
{
    use ResolvesTelegramEmployee;

    private function nowMy(): string
    {
        return now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString();
    }

    private function todayMy(): string
    {
        return now('Asia/Kuala_Lumpur')->toDateString();
    }

    private function calculateAchievement($baseTarget, $stretchTarget, $actualValue): float
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

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/kpis/open
    |--------------------------------------------------------------------------
    */
    public function openKpis(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $today = $this->todayMy();
        $fy = 'FY' . now('Asia/Kuala_Lumpur')->year;

        $kpis = $supabase->get('kpis', [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'financial_year' => 'eq.' . $fy,
            'select' => '*',
        ]) ?? [];

        if (empty($kpis)) {
            return response()->json(['date' => $today, 'kpis' => []]);
        }

        $kpiIds = array_column($kpis, 'id');

        $quarters = $supabase->get('kpi_quarters', [
            'kpi_id' => 'in.(' . implode(',', $kpiIds) . ')',
            'select' => '*',
        ]) ?? [];

        $quartersByKpi = collect($quarters)->groupBy('kpi_id');

        $existingTasks = $supabase->get('telegram_daily_tasks', [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'task_date' => 'eq.' . $today,
            'select' => '*',
        ]) ?? [];

        $taskByQuarter = collect($existingTasks)->keyBy('kpi_quarter_id');

        $result = [];

        foreach ($kpis as $kpi) {
            $openQuarter = collect($quartersByKpi->get($kpi['id'], []))
                ->first(fn($q) => !empty($q['start_date']) && !empty($q['end_date'])
                    && $q['start_date'] <= $today && $q['end_date'] >= $today);

            if (!$openQuarter) {
                continue;
            }

            $task = $taskByQuarter->get($openQuarter['id']);

            $result[] = [
                'kpi_id' => $kpi['id'],
                'kpi_quarter_id' => $openQuarter['id'],
                'kpi_title' => $kpi['kpi_title'],
                'category' => $kpi['category'] ?? null,
                'sub_category' => $kpi['sub_category'] ?? null,
                'unit' => $kpi['unit'],
                'quarter' => $openQuarter['quarter'],
                'quarter_target' => (float) ($openQuarter['quarter_target'] ?? 0),
                'quarter_actual' => (float) ($openQuarter['quarter_actual'] ?? 0),
                'already_planned_today' => (bool) $task,
                'existing_task_id' => $task['id'] ?? null,
                'planned_target' => $task['planned_target'] ?? null,
                'planned_note' => $task['planned_note'] ?? null,
            ];
        }

        return response()->json(['date' => $today, 'kpis' => $result]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/tasks
    |--------------------------------------------------------------------------
    */
    public function storeTasks(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'tasks' => 'required|array|min:1',
            'tasks.*.kpi_id' => 'required|string',
            'tasks.*.kpi_quarter_id' => 'required|string',
            'tasks.*.planned_target' => 'required|numeric|min:0',
            'tasks.*.planned_note' => 'nullable|string|max:500',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $today = $this->todayMy();
        $saved = [];
        $errors = [];

        foreach ($validated['tasks'] as $taskInput) {
            $kpi = $supabase->first('kpis', [
                'id' => 'eq.' . $taskInput['kpi_id'],
                'employee_id' => 'eq.' . $validated['employee_id'],
                'select' => '*',
            ]);

            if (empty($kpi)) {
                $errors[] = ['kpi_id' => $taskInput['kpi_id'], 'message' => 'KPI not found or not yours.'];
                continue;
            }

            $quarter = $supabase->first('kpi_quarters', [
                'id' => 'eq.' . $taskInput['kpi_quarter_id'],
                'kpi_id' => 'eq.' . $taskInput['kpi_id'],
                'select' => '*',
            ]);

            if (empty($quarter)) {
                $errors[] = ['kpi_id' => $taskInput['kpi_id'], 'message' => 'Quarter not found.'];
                continue;
            }

            if ($quarter['start_date'] > $today || $quarter['end_date'] < $today) {
                $errors[] = ['kpi_id' => $taskInput['kpi_id'], 'message' => 'This quarter is not currently open.'];
                continue;
            }

            $existing = $supabase->first('telegram_daily_tasks', [
                'employee_id' => 'eq.' . $validated['employee_id'],
                'kpi_quarter_id' => 'eq.' . $taskInput['kpi_quarter_id'],
                'task_date' => 'eq.' . $today,
                'select' => '*',
            ]);

            if ($existing) {
                $supabase->safePatch('telegram_daily_tasks', ['id' => 'eq.' . $existing['id']], [
                    'planned_target' => (float) $taskInput['planned_target'],
                    'planned_note' => $taskInput['planned_note'] ?? null,
                    'updated_at' => $this->nowMy(),
                ]);

                $saved[] = ['id' => $existing['id'], 'kpi_id' => $taskInput['kpi_id'], 'planned_target' => (float) $taskInput['planned_target']];
                continue;
            }

            $inserted = $supabase->insert('telegram_daily_tasks', [
                'employee_id' => $validated['employee_id'],
                'kpi_id' => $taskInput['kpi_id'],
                'kpi_quarter_id' => $taskInput['kpi_quarter_id'],
                'task_date' => $today,
                'unit' => $kpi['unit'],
                'planned_target' => (float) $taskInput['planned_target'],
                'planned_note' => $taskInput['planned_note'] ?? null,
                'baseline_actual' => (float) ($quarter['quarter_actual'] ?? 0),
                'status' => 'planned',
            ]);

            $saved[] = ['id' => $inserted[0]['id'] ?? null, 'kpi_id' => $taskInput['kpi_id'], 'planned_target' => (float) $taskInput['planned_target']];
        }

        return response()->json(['success' => empty($errors), 'tasks' => $saved, 'errors' => $errors]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/tasks/today
    |--------------------------------------------------------------------------
    */
    public function todayTasks(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $today = $this->todayMy();

        $tasks = $supabase->get('telegram_daily_tasks', [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'task_date' => 'eq.' . $today,
            'select' => '*',
            'order' => 'created_at.asc',
        ]) ?? [];

        if (empty($tasks)) {
            return response()->json(['date' => $today, 'tasks' => []]);
        }

        $kpiIds = array_unique(array_column($tasks, 'kpi_id'));
        $kpis = $supabase->get('kpis', [
            'id' => 'in.(' . implode(',', $kpiIds) . ')',
            'select' => 'id,kpi_title',
        ]) ?? [];
        $kpiMap = collect($kpis)->keyBy('id');

        $result = array_map(function ($task) use ($kpiMap) {
            return [
                'id' => $task['id'],
                'kpi_id' => $task['kpi_id'],
                'kpi_title' => $kpiMap->get($task['kpi_id'])['kpi_title'] ?? '-',
                'unit' => $task['unit'],
                'planned_target' => (float) $task['planned_target'],
                'planned_note' => $task['planned_note'],
                'progress_value' => isset($task['progress_value']) ? (float) $task['progress_value'] : null,
                'status' => $task['status'],
            ];
        }, $tasks);

        return response()->json(['date' => $today, 'tasks' => $result]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/tasks/{id}/progress
    |--------------------------------------------------------------------------
    */
    public function submitProgress(Request $request, SupabaseService $supabase, string $id)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'progress_value' => 'required|numeric|min:0',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $task = $supabase->first('telegram_daily_tasks', [
            'id' => 'eq.' . $id,
            'employee_id' => 'eq.' . $validated['employee_id'],
            'select' => '*',
        ]);

        if (empty($task)) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $quarter = $supabase->first('kpi_quarters', [
            'id' => 'eq.' . $task['kpi_quarter_id'],
            'select' => '*',
        ]);

        if (empty($quarter)) {
            return response()->json(['success' => false, 'message' => 'Quarter not found.'], 404);
        }

        $today = $this->todayMy();

        if ($quarter['start_date'] > $today || $quarter['end_date'] < $today) {
            return response()->json([
                'success' => false,
                'message' => 'This quarter has already ended. Please use the web dashboard\'s approval request flow to submit a retroactive update.',
            ], 422);
        }

        // Race-safety: compute the write as a delta off the LIVE quarter_actual
        // (not the frozen morning baseline), so a concurrent web-app edit made
        // after the morning snapshot is never clobbered, and resubmitting the
        // same progress value twice is a no-op (net delta = 0).
        $previousProgress = (float) ($task['progress_value'] ?? 0);
        $newProgress = (float) $validated['progress_value'];
        $delta = $newProgress - $previousProgress;

        $kpi = $supabase->first('kpis', ['id' => 'eq.' . $task['kpi_id'], 'select' => '*']);
        $liveQuarterActual = (float) ($quarter['quarter_actual'] ?? 0);
        $result = $this->applyQuarterActualChange($supabase, $kpi, $quarter, $liveQuarterActual + $delta);

        $supabase->safePatch('telegram_daily_tasks', ['id' => 'eq.' . $task['id']], [
            'progress_value' => $newProgress,
            'status' => 'done',
            'updated_at' => $this->nowMy(),
        ]);

        return response()->json(['success' => true] + $result);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/kpis/{kpiId}/quarters/{quarterId}/adjust
    |--------------------------------------------------------------------------
    | Directly increases or decreases a quarter's actual by $delta (no
    | pre-planned daily task required) — the fast path used by the "My KPIs"
    | screen's inline update control.
    */
    public function adjustQuarter(Request $request, SupabaseService $supabase, string $kpiId, string $quarterId)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'delta' => 'required|numeric',
        ]);

        if ((float) $validated['delta'] === 0.0) {
            return response()->json(['success' => false, 'message' => 'Enter a non-zero amount.'], 422);
        }

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $kpi = $supabase->first('kpis', [
            'id' => 'eq.' . $kpiId,
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'select' => '*',
        ]);

        if (empty($kpi)) {
            return response()->json(['success' => false, 'message' => 'KPI not found.'], 404);
        }

        $quarter = $supabase->first('kpi_quarters', [
            'id' => 'eq.' . $quarterId,
            'kpi_id' => 'eq.' . $kpiId,
            'select' => '*',
        ]);

        if (empty($quarter)) {
            return response()->json(['success' => false, 'message' => 'Quarter not found.'], 404);
        }

        $today = $this->todayMy();

        if ($quarter['start_date'] > $today || $quarter['end_date'] < $today) {
            return response()->json([
                'success' => false,
                'message' => 'This quarter is not currently open. Use the web dashboard\'s approval request flow for retroactive updates.',
            ], 422);
        }

        $liveQuarterActual = (float) ($quarter['quarter_actual'] ?? 0);
        $newQuarterActual = $liveQuarterActual + (float) $validated['delta'];

        if ($newQuarterActual < 0) {
            return response()->json([
                'success' => false,
                'message' => "Can't reduce — this quarter's actual is only " . $liveQuarterActual . '.',
            ], 422);
        }

        $result = $this->applyQuarterActualChange($supabase, $kpi, $quarter, $newQuarterActual);

        return response()->json(['success' => true] + $result);
    }

    /**
     * Writes a quarter's new actual, recomputes the KPI's annual actual_value
     * and achievement_percentage from all quarters, and persists both.
     */
    private function applyQuarterActualChange(SupabaseService $supabase, array $kpi, array $quarter, float $newQuarterActual): array
    {
        $supabase->safePatch('kpi_quarters', ['id' => 'eq.' . $quarter['id']], [
            'quarter_actual' => $newQuarterActual,
            'updated_at' => $this->nowMy(),
        ]);

        $allQuarters = $supabase->get('kpi_quarters', [
            'kpi_id' => 'eq.' . $kpi['id'],
            'select' => '*',
        ]) ?? [];

        $totalActual = collect($allQuarters)->sum(function ($q) use ($quarter, $newQuarterActual) {
            return (float) ($q['id'] === $quarter['id'] ? $newQuarterActual : ($q['quarter_actual'] ?? 0));
        });

        $achievement = $this->calculateAchievement($kpi['base_target'] ?? 0, $kpi['stretch_target'] ?? 0, $totalActual);

        $supabase->safePatch('kpis', ['id' => 'eq.' . $kpi['id']], [
            'actual_value' => $totalActual,
            'achievement_percentage' => $achievement,
            'updated_at' => $this->nowMy(),
        ]);

        return [
            'quarter_actual' => $newQuarterActual,
            'actual_value' => $totalActual,
            'achievement_percentage' => $achievement,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/kpis/summary
    |--------------------------------------------------------------------------
    */
    public function summary(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $fy = 'FY' . now('Asia/Kuala_Lumpur')->year;

        $kpis = $supabase->get('kpis', [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'financial_year' => 'eq.' . $fy,
            'select' => '*',
            'order' => 'created_at.asc',
        ]) ?? [];

        if (empty($kpis)) {
            return response()->json(['financial_year' => $fy, 'kpis' => []]);
        }

        $today = $this->todayMy();
        $kpiIds = array_column($kpis, 'id');

        $quarters = $supabase->get('kpi_quarters', [
            'kpi_id' => 'in.(' . implode(',', $kpiIds) . ')',
            'select' => '*',
            'order' => 'start_date.asc',
        ]) ?? [];

        $quartersByKpi = collect($quarters)->groupBy('kpi_id');

        $result = array_map(function ($kpi) use ($quartersByKpi, $today) {
            $kpiQuarters = $quartersByKpi->get($kpi['id'], collect())->map(function ($q) use ($today) {
                $target = (float) ($q['quarter_target'] ?? 0);
                $actual = (float) ($q['quarter_actual'] ?? 0);

                $state = 'upcoming';
                if (!empty($q['start_date']) && !empty($q['end_date'])) {
                    if ($q['end_date'] < $today) {
                        $state = 'ended';
                    } elseif ($q['start_date'] <= $today && $q['end_date'] >= $today) {
                        $state = 'current';
                    }
                }

                return [
                    'id' => $q['id'],
                    'quarter' => $q['quarter'],
                    'target' => $target,
                    'actual' => $actual,
                    'achievement_percentage' => $target > 0 ? round(($actual / $target) * 100, 2) : 0,
                    'state' => $state,
                ];
            })->values();

            return [
                'kpi_id' => $kpi['id'],
                'kpi_title' => $kpi['kpi_title'],
                'category' => $kpi['category'] ?? null,
                'sub_category' => $kpi['sub_category'] ?? null,
                'unit' => $kpi['unit'],
                'base_target' => (float) ($kpi['base_target'] ?? 0),
                'stretch_target' => (float) ($kpi['stretch_target'] ?? 0),
                'actual_value' => (float) ($kpi['actual_value'] ?? 0),
                'achievement_percentage' => (float) ($kpi['achievement_percentage'] ?? 0),
                'status' => $kpi['status'] ?? 'not_started',
                'quarters' => $kpiQuarters,
            ];
        }, $kpis);

        return response()->json(['financial_year' => $fy, 'kpis' => $result]);
    }
}
