<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * The web Mini App's "TTD" (to-do) list — a personal task tracker that is
 * deliberately NOT linked to KPI actuals (unlike the Telegram Mini App's
 * "Things To Do", which requires at least one linked KPI). Reuses the same
 * telegram_project_tasks / telegram_project_task_updates tables so nothing
 * new needed on the DB side; KPI linking here is optional, not mandatory.
 *
 * Every create/update/progress/delete pushes a notification via
 * NotificationService — which both logs an in-app notification row and, if
 * the employee has linked their Telegram account, pushes the same message
 * there. No bespoke Telegram-sending code needed for this.
 */
class MiniAppTaskController extends Controller
{
    private function nowMy(): string
    {
        return now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString();
    }

    private function employeeName(): string
    {
        return session('employee.short_name') ?? 'You';
    }

    /**
     * TTD tasks live under a project row (DB requires project_id NOT NULL),
     * but the web UI has no "projects" concept — every task is filed under
     * one auto-created "My To-Do List" project per employee, transparently.
     */
    private function defaultProjectId(SupabaseService $supabase, string $employeeId, string $companyCode): string
    {
        $project = $supabase->first('telegram_projects', [
            'employee_id' => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'name' => 'eq.My To-Do List',
            'select' => 'id',
        ]);

        if ($project) {
            return $project['id'];
        }

        $inserted = $supabase->insert('telegram_projects', [
            'employee_id' => $employeeId,
            'company_code' => $companyCode,
            'name' => 'My To-Do List',
        ]);

        return $inserted[0]['id'];
    }

    /*
    |--------------------------------------------------------------------------
    | GET /mini-app/api/tasks
    |--------------------------------------------------------------------------
    */
    public function index(Request $request, SupabaseService $supabase)
    {
        $employeeId = session('employee.id');
        $companyCode = session('employee.company_code');

        $tasks = $supabase->get('telegram_project_tasks', [
            'employee_id' => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'select' => '*',
            'order' => 'created_at.desc',
        ]) ?? [];

        if (empty($tasks)) {
            return response()->json(['tasks' => []]);
        }

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

        $result = array_map(function ($task) use ($linksByTask, $kpiMap) {
            $linkedKpis = $linksByTask->get($task['id'], collect())->map(function ($link) use ($kpiMap) {
                $kpi = $kpiMap->get($link['kpi_id']);
                return $kpi ? ['kpi_id' => $kpi['id'], 'kpi_title' => $kpi['kpi_title'], 'category' => $kpi['category'] ?? null] : null;
            })->filter()->values();

            return [
                'id' => $task['id'],
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
    | POST /mini-app/api/tasks
    |--------------------------------------------------------------------------
    | KPI linking is optional here — a TTD task can exist purely as a
    | personal to-do, with no effect on any KPI's actual.
    */
    public function store(Request $request, SupabaseService $supabase, NotificationService $notifications)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'unit' => 'required|in:number,currency,percentage',
            'target' => 'required|numeric|min:0',
            'kpi_ids' => 'nullable|array',
            'kpi_ids.*' => 'string',
        ]);

        $employeeId = session('employee.id');
        $companyCode = session('employee.company_code');

        $kpiIds = array_unique($validated['kpi_ids'] ?? []);

        if (!empty($kpiIds)) {
            $kpis = $supabase->get('kpis', [
                'id' => 'in.(' . implode(',', $kpiIds) . ')',
                'employee_id' => 'eq.' . $employeeId,
                'company_code' => 'eq.' . $companyCode,
                'select' => 'id,unit',
            ]) ?? [];

            if (count($kpis) !== count($kpiIds)) {
                return response()->json(['success' => false, 'message' => 'One or more KPIs were not found.'], 404);
            }

            $mismatched = collect($kpis)->first(fn($k) => $k['unit'] !== $validated['unit']);
            if ($mismatched) {
                return response()->json(['success' => false, 'message' => "Unit mismatch — this task is in \"{$validated['unit']}\", but a selected KPI isn't."], 422);
            }
        }

        $projectId = $this->defaultProjectId($supabase, $employeeId, $companyCode);

        $inserted = $supabase->insert('telegram_project_tasks', [
            'project_id' => $projectId,
            'employee_id' => $employeeId,
            'company_code' => $companyCode,
            'title' => trim($validated['title']),
            'unit' => $validated['unit'],
            'target' => (float) $validated['target'],
        ]);

        $task = $inserted[0] ?? null;

        if ($task && !empty($kpiIds)) {
            foreach ($kpiIds as $kpiId) {
                $supabase->insert('telegram_project_task_kpi_links', [
                    'task_id' => $task['id'],
                    'kpi_id' => $kpiId,
                ]);
            }
        }

        if ($task) {
            $notifications->notify(
                [$employeeId],
                'ttd_task_created',
                ['id' => $employeeId, 'name' => $this->employeeName()],
                'New to-do task created',
                "\"{$task['title']}\" — target " . (float) $validated['target'] . ' ' . $validated['unit'],
                route('mini-app')
            );
        }

        return response()->json(['task' => $task]);
    }

    /*
    |--------------------------------------------------------------------------
    | PATCH /mini-app/api/tasks/{id}
    |--------------------------------------------------------------------------
    | Edits the task's own details (title/target/unit) — the Telegram
    | controller never had this, it only ever adjusted progress.
    */
    public function update(Request $request, SupabaseService $supabase, NotificationService $notifications, string $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'unit' => 'required|in:number,currency,percentage',
            'target' => 'required|numeric|min:0',
        ]);

        $employeeId = session('employee.id');

        $task = $supabase->first('telegram_project_tasks', [
            'id' => 'eq.' . $id,
            'employee_id' => 'eq.' . $employeeId,
            'select' => '*',
        ]);

        if (empty($task)) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $supabase->safePatch('telegram_project_tasks', ['id' => 'eq.' . $id], [
            'title' => trim($validated['title']),
            'unit' => $validated['unit'],
            'target' => (float) $validated['target'],
            'status' => (float) $task['actual'] >= (float) $validated['target'] && (float) $validated['target'] > 0 ? 'done' : 'in_progress',
            'updated_at' => $this->nowMy(),
        ]);

        $notifications->notify(
            [$employeeId],
            'ttd_task_updated',
            ['id' => $employeeId, 'name' => $this->employeeName()],
            'To-do task updated',
            "\"{$validated['title']}\" details were updated.",
            route('mini-app')
        );

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /mini-app/api/tasks/{id}/progress
    |--------------------------------------------------------------------------
    | Adds $delta to the task's own actual ONLY — does not touch any linked
    | KPI's quarter_actual, matching the Telegram version's documented
    | behavior. Logged to telegram_project_task_updates for history.
    */
    public function progress(Request $request, SupabaseService $supabase, NotificationService $notifications, string $id)
    {
        $validated = $request->validate([
            'delta' => 'required|numeric',
        ]);

        if ((float) $validated['delta'] === 0.0) {
            return response()->json(['success' => false, 'message' => 'Enter a non-zero amount.'], 422);
        }

        $employeeId = session('employee.id');

        $task = $supabase->first('telegram_project_tasks', [
            'id' => 'eq.' . $id,
            'employee_id' => 'eq.' . $employeeId,
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

        $notifications->notify(
            [$employeeId],
            'ttd_task_progress',
            ['id' => $employeeId, 'name' => $this->employeeName()],
            'To-do progress logged',
            "\"{$task['title']}\": " . ($delta >= 0 ? '+' : '') . $delta . " added → now {$newActual}.",
            route('mini-app')
        );

        return response()->json(['success' => true, 'task_actual' => $newActual]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /mini-app/api/tasks/{id}
    |--------------------------------------------------------------------------
    | Hard delete — telegram_project_task_kpi_links and
    | telegram_project_task_updates both cascade on task_id (see
    | database/telegram_projects.sql / telegram_project_task_updates.sql),
    | so this is the only row that needs deleting directly.
    */
    public function destroy(Request $request, SupabaseService $supabase, NotificationService $notifications, string $id)
    {
        $employeeId = session('employee.id');

        $task = $supabase->first('telegram_project_tasks', [
            'id' => 'eq.' . $id,
            'employee_id' => 'eq.' . $employeeId,
            'select' => 'id,title',
        ]);

        if (empty($task)) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $deleted = $supabase->safeDelete('telegram_project_tasks', ['id' => 'eq.' . $id]);

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Could not delete task.'], 500);
        }

        $notifications->notify(
            [$employeeId],
            'ttd_task_deleted',
            ['id' => $employeeId, 'name' => $this->employeeName()],
            'To-do task deleted',
            "\"{$task['title']}\" was deleted.",
            route('mini-app')
        );

        return response()->json(['success' => true]);
    }
}
