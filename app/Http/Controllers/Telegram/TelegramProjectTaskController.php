<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Telegram\Concerns\ResolvesTelegramEmployee;
use App\Services\KpiQuarterUpdateService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class TelegramProjectTaskController extends Controller
{
    use ResolvesTelegramEmployee;

    private function todayMy(): string
    {
        return now('Asia/Kuala_Lumpur')->toDateString();
    }

    private function nowMy(): string
    {
        return now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/projects
    |--------------------------------------------------------------------------
    */
    public function listProjects(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $projects = $supabase->get('telegram_projects', [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'select' => '*',
            'order' => 'created_at.desc',
        ]) ?? [];

        return response()->json(['projects' => $projects]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/projects
    |--------------------------------------------------------------------------
    */
    public function createProject(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'name' => 'required|string|max:120',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $inserted = $supabase->insert('telegram_projects', [
            'employee_id' => $validated['employee_id'],
            'company_code' => $validated['company_code'],
            'name' => trim($validated['name']),
        ]);

        return response()->json(['project' => $inserted[0] ?? null]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/project-tasks
    |--------------------------------------------------------------------------
    | Lists tasks for the employee, each with its project name and linked
    | KPIs — the "collect tasks with their project" hub screen.
    */
    public function listTasks(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'project_id' => 'nullable|string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $filters = [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'select' => '*',
            'order' => 'created_at.desc',
        ];

        if (!empty($validated['project_id'])) {
            $filters['project_id'] = 'eq.' . $validated['project_id'];
        }

        $tasks = $supabase->get('telegram_project_tasks', $filters) ?? [];

        if (empty($tasks)) {
            return response()->json(['tasks' => []]);
        }

        $projectIds = array_unique(array_column($tasks, 'project_id'));
        $projects = $supabase->get('telegram_projects', [
            'id' => 'in.(' . implode(',', $projectIds) . ')',
            'select' => 'id,name',
        ]) ?? [];
        $projectMap = collect($projects)->keyBy('id');

        $taskIds = array_column($tasks, 'id');
        $links = $supabase->get('telegram_project_task_kpi_links', [
            'task_id' => 'in.(' . implode(',', $taskIds) . ')',
            'select' => '*',
        ]) ?? [];

        $kpiIds = array_unique(array_column($links, 'kpi_id'));
        $kpis = empty($kpiIds) ? [] : ($supabase->get('kpis', [
            'id' => 'in.(' . implode(',', $kpiIds) . ')',
            'select' => 'id,kpi_title,unit,category',
        ]) ?? []);
        $kpiMap = collect($kpis)->keyBy('id');

        $linksByTask = collect($links)->groupBy('task_id');

        $result = array_map(function ($task) use ($projectMap, $linksByTask, $kpiMap) {
            $linkedKpis = $linksByTask->get($task['id'], collect())->map(function ($link) use ($kpiMap) {
                $kpi = $kpiMap->get($link['kpi_id']);
                return $kpi ? ['kpi_id' => $kpi['id'], 'kpi_title' => $kpi['kpi_title'], 'category' => $kpi['category'] ?? null] : null;
            })->filter()->values();

            return [
                'id' => $task['id'],
                'project_id' => $task['project_id'],
                'project_name' => $projectMap->get($task['project_id'])['name'] ?? 'Untitled Project',
                'title' => $task['title'],
                'unit' => $task['unit'],
                'target' => (float) $task['target'],
                'actual' => (float) $task['actual'],
                'status' => $task['status'],
                'linked_kpis' => $linkedKpis,
            ];
        }, $tasks);

        return response()->json(['tasks' => $result]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/project-tasks
    |--------------------------------------------------------------------------
    */
    public function createTask(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'project_id' => 'required|string',
            'title' => 'required|string|max:200',
            'unit' => 'required|in:number,currency,percentage',
            'target' => 'required|numeric|min:0',
            // Every task must belong to at least one KPI — linking is the
            // last step of the create flow, not an optional afterthought.
            'kpi_ids' => 'required|array|min:1',
            'kpi_ids.*' => 'string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $project = $supabase->first('telegram_projects', [
            'id' => 'eq.' . $validated['project_id'],
            'employee_id' => 'eq.' . $validated['employee_id'],
            'select' => 'id',
        ]);

        if (empty($project)) {
            return response()->json(['success' => false, 'message' => 'Project not found.'], 404);
        }

        $kpiIds = array_unique($validated['kpi_ids']);

        $kpis = $supabase->get('kpis', [
            'id' => 'in.(' . implode(',', $kpiIds) . ')',
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'select' => 'id,unit',
        ]) ?? [];

        if (count($kpis) !== count($kpiIds)) {
            return response()->json(['success' => false, 'message' => 'One or more KPIs were not found.'], 404);
        }

        $mismatched = collect($kpis)->first(fn($k) => $k['unit'] !== $validated['unit']);
        if ($mismatched) {
            return response()->json(['success' => false, 'message' => "Unit mismatch — this task is in \"{$validated['unit']}\", but a selected KPI isn't."], 422);
        }

        $inserted = $supabase->insert('telegram_project_tasks', [
            'project_id' => $validated['project_id'],
            'employee_id' => $validated['employee_id'],
            'company_code' => $validated['company_code'],
            'title' => trim($validated['title']),
            'unit' => $validated['unit'],
            'target' => (float) $validated['target'],
        ]);

        $task = $inserted[0] ?? null;

        if ($task) {
            foreach ($kpiIds as $kpiId) {
                $supabase->insert('telegram_project_task_kpi_links', [
                    'task_id' => $task['id'],
                    'kpi_id' => $kpiId,
                ]);
            }
        }

        return response()->json(['task' => $task]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/project-tasks/kpi-options
    |--------------------------------------------------------------------------
    | KPIs eligible to link a task to: same employee/company, matching unit,
    | and currently has an open quarter (otherwise an update couldn't land
    | anywhere right now).
    */
    public function kpiOptions(Request $request, SupabaseService $supabase, KpiQuarterUpdateService $quarterService)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'unit' => 'required|in:number,currency,percentage',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $fy = 'FY' . now('Asia/Kuala_Lumpur')->year;
        $today = $this->todayMy();

        $kpis = $supabase->get('kpis', [
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'financial_year' => 'eq.' . $fy,
            'unit' => 'eq.' . $validated['unit'],
            'select' => '*',
        ]) ?? [];

        $options = [];
        foreach ($kpis as $kpi) {
            if ($quarterService->findOpenQuarter($kpi['id'], $today)) {
                $options[] = [
                    'kpi_id' => $kpi['id'],
                    'kpi_title' => $kpi['kpi_title'],
                    'category' => $kpi['category'] ?? null,
                ];
            }
        }

        return response()->json(['kpis' => $options]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/project-tasks/{id}/link-kpis
    |--------------------------------------------------------------------------
    | Replaces the set of KPIs this task feeds into. Every kpi_id must belong
    | to the same employee/company and share the task's unit. At least one
    | KPI must remain linked — a task can never end up orphaned.
    */
    public function linkKpis(Request $request, SupabaseService $supabase, string $id)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
            'kpi_ids' => 'required|array|min:1',
            'kpi_ids.*' => 'string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $task = $supabase->first('telegram_project_tasks', [
            'id' => 'eq.' . $id,
            'employee_id' => 'eq.' . $validated['employee_id'],
            'select' => '*',
        ]);

        if (empty($task)) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $kpiIds = array_unique($validated['kpi_ids'] ?? []);

        if (!empty($kpiIds)) {
            $kpis = $supabase->get('kpis', [
                'id' => 'in.(' . implode(',', $kpiIds) . ')',
                'employee_id' => 'eq.' . $validated['employee_id'],
                'company_code' => 'eq.' . $validated['company_code'],
                'select' => 'id,unit',
            ]) ?? [];

            if (count($kpis) !== count($kpiIds)) {
                return response()->json(['success' => false, 'message' => 'One or more KPIs were not found.'], 404);
            }

            $mismatched = collect($kpis)->first(fn($k) => $k['unit'] !== $task['unit']);
            if ($mismatched) {
                return response()->json(['success' => false, 'message' => "Unit mismatch — this task is in \"{$task['unit']}\", but a selected KPI isn't."], 422);
            }
        }

        $supabase->delete('telegram_project_task_kpi_links', ['task_id' => 'eq.' . $id]);

        foreach ($kpiIds as $kpiId) {
            $supabase->insert('telegram_project_task_kpi_links', [
                'task_id' => $id,
                'kpi_id' => $kpiId,
            ]);
        }

        return response()->json(['success' => true, 'linked_count' => count($kpiIds)]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/telegram/project-tasks/{id}/progress
    |--------------------------------------------------------------------------
    | Adds $delta to the task's own actual ONLY — this does not touch any
    | linked KPI's quarter_actual (a KPI's official actual is only ever
    | changed via the "My KPIs" inline Update box / adjustQuarter). Each
    | update is also logged to telegram_project_task_updates so a KPI's
    | linked tasks can show a "what was updated, and when" history.
    */
    public function updateProgress(Request $request, SupabaseService $supabase, string $id)
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

        $task = $supabase->first('telegram_project_tasks', [
            'id' => 'eq.' . $id,
            'employee_id' => 'eq.' . $validated['employee_id'],
            'select' => '*',
        ]);

        if (empty($task)) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $delta = (float) $validated['delta'];
        $liveActual = (float) ($task['actual'] ?? 0);
        $newActual = $liveActual + $delta;

        if ($newActual < 0) {
            return response()->json([
                'success' => false,
                'message' => "Can't reduce — this task's actual is only {$liveActual}.",
            ], 422);
        }

        $supabase->safePatch('telegram_project_tasks', ['id' => 'eq.' . $id], [
            'actual' => $newActual,
            'status' => $newActual >= (float) $task['target'] && (float) $task['target'] > 0 ? 'done' : 'in_progress',
            'updated_at' => $this->nowMy(),
        ]);

        $supabase->safeInsert('telegram_project_task_updates', [
            'task_id' => $id,
            'delta' => $delta,
            'new_actual' => $newActual,
        ]);

        return response()->json([
            'success' => true,
            'task_actual' => $newActual,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/telegram/kpis/{kpiId}/task-history
    |--------------------------------------------------------------------------
    | Every task linked to this KPI, each with its timestamped update log —
    | "list what tasks are under that KPI, like a history."
    */
    public function kpiTaskHistory(Request $request, SupabaseService $supabase, string $kpiId)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'company_code' => 'required|string',
        ]);

        $this->resolveContext($request, $supabase, $validated['employee_id'], $validated['company_code']);

        $kpi = $supabase->first('kpis', [
            'id' => 'eq.' . $kpiId,
            'employee_id' => 'eq.' . $validated['employee_id'],
            'company_code' => 'eq.' . $validated['company_code'],
            'select' => 'id,kpi_title',
        ]);

        if (empty($kpi)) {
            return response()->json(['success' => false, 'message' => 'KPI not found.'], 404);
        }

        $links = $supabase->get('telegram_project_task_kpi_links', [
            'kpi_id' => 'eq.' . $kpiId,
            'select' => '*',
        ]) ?? [];

        if (empty($links)) {
            return response()->json(['kpi_title' => $kpi['kpi_title'], 'tasks' => []]);
        }

        $taskIds = array_column($links, 'task_id');
        $tasks = $supabase->get('telegram_project_tasks', [
            'id' => 'in.(' . implode(',', $taskIds) . ')',
            'select' => '*',
            'order' => 'created_at.desc',
        ]) ?? [];

        if (empty($tasks)) {
            return response()->json(['kpi_title' => $kpi['kpi_title'], 'tasks' => []]);
        }

        $projectIds = array_unique(array_column($tasks, 'project_id'));
        $projects = $supabase->get('telegram_projects', [
            'id' => 'in.(' . implode(',', $projectIds) . ')',
            'select' => 'id,name',
        ]) ?? [];
        $projectMap = collect($projects)->keyBy('id');

        $updates = $supabase->get('telegram_project_task_updates', [
            'task_id' => 'in.(' . implode(',', $taskIds) . ')',
            'select' => '*',
            'order' => 'created_at.desc',
        ]) ?? [];
        $updatesByTask = collect($updates)->groupBy('task_id');

        $result = array_map(function ($task) use ($projectMap, $updatesByTask) {
            return [
                'id' => $task['id'],
                'title' => $task['title'],
                'project_name' => $projectMap->get($task['project_id'])['name'] ?? 'Untitled Project',
                'unit' => $task['unit'],
                'target' => (float) $task['target'],
                'actual' => (float) $task['actual'],
                'status' => $task['status'],
                'updates' => $updatesByTask->get($task['id'], collect())->map(fn($u) => [
                    'delta' => (float) $u['delta'],
                    'new_actual' => (float) $u['new_actual'],
                    'created_at' => $u['created_at'],
                ])->values(),
            ];
        }, $tasks);

        return response()->json(['kpi_title' => $kpi['kpi_title'], 'tasks' => $result]);
    }
}
