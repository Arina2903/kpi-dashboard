<?php

namespace App\Services;

/**
 * The 8:30 AM / 5:30 PM MYT task reminders (docs/performix-design.md §3.2,
 * §3.3) — deliberately separate from TelegramDigestService, which sends the
 * KPI quarter-actual quick-update digest at the same two times. Those two
 * concerns stay separate messages: this service never touches KPI actuals,
 * only telegram_project_tasks / task_reminders_log.
 *
 * Idempotency (docs/performix-design.md §6-R3): task_reminders_log's
 * UNIQUE(employee_id, reminder_type, task_date) is the actual correctness
 * guarantee. claimReminderSlot() does a SELECT-first fast path to avoid
 * noisy duplicate-key errors on the common "already sent" case, but a
 * concurrent/retried call is still safe because the insert is wrapped and
 * any conflict is treated as "someone else already claimed this slot."
 */
class TaskReminderService
{
    public function __construct(
        private SupabaseService $supabase,
        private TelegramService $telegram,
    ) {
    }

    private function todayMy(): string
    {
        return now('Asia/Kuala_Lumpur')->toDateString();
    }

    public function sendMorningReminders(): array
    {
        $stats = ['sent' => 0, 'skipped_has_tasks' => 0, 'skipped_already_sent' => 0];
        $today = $this->todayMy();

        foreach ($this->linkedEmployees() as $employee) {
            if (!$this->claimReminderSlot($employee['id'], 'morning', $today)) {
                $stats['skipped_already_sent']++;
                continue;
            }

            $existing = $this->supabase->first('telegram_project_tasks', [
                'assignee_employee_id' => 'eq.' . $employee['id'],
                'start_date' => 'eq.' . $today,
                'select' => 'id',
            ]);

            if ($existing) {
                $stats['skipped_has_tasks']++;
                continue;
            }

            $this->send(
                $employee['telegram_chat_id'],
                "🗒️ <b>Good morning!</b>\nWhat are you working on today? Create or review your tasks for today.",
                '🗒️ Open Daily Tasks',
                '/telegram/app?screen=tasks-today'
            );

            $stats['sent']++;
        }

        return $stats;
    }

    public function sendEveningReminders(): array
    {
        $stats = ['sent' => 0, 'skipped_no_open_tasks' => 0, 'skipped_already_sent' => 0];
        $today = $this->todayMy();

        foreach ($this->linkedEmployees() as $employee) {
            if (!$this->claimReminderSlot($employee['id'], 'evening', $today)) {
                $stats['skipped_already_sent']++;
                continue;
            }

            $openTasks = $this->supabase->get('telegram_project_tasks', [
                'assignee_employee_id' => 'eq.' . $employee['id'],
                'status' => 'in.(not_started,in_progress,blocked)',
                'or' => '(due_date.lte.' . $today . ',start_date.eq.' . $today . ')',
                'select' => 'id',
            ]) ?? [];

            if (empty($openTasks)) {
                $stats['skipped_no_open_tasks']++;
                continue;
            }

            $count = count($openTasks);

            $this->send(
                $employee['telegram_chat_id'],
                "📋 <b>End of day check-in</b>\nYou have {$count} open task" . ($count === 1 ? '' : 's') . " that need an update today.",
                '📋 Update My Tasks',
                '/telegram/app?screen=tasks-update'
            );

            $stats['sent']++;
        }

        return $stats;
    }

    /**
     * Every employee with a linked Telegram chat — same source of truth as
     * TelegramDigestService::broadcast(), joined through user_company_roles
     * to get the employee_id this service actually keys off.
     */
    private function linkedEmployees(): array
    {
        $users = $this->supabase->get('users', [
            'telegram_chat_id' => 'not.is.null',
            'select' => 'id,telegram_chat_id',
        ]) ?? [];

        if (empty($users)) {
            return [];
        }

        $userIds = array_column($users, 'id');
        $chatIdByUser = collect($users)->keyBy('id')->map(fn ($u) => $u['telegram_chat_id']);

        $roles = $this->supabase->get('user_company_roles', [
            'user_id' => 'in.(' . implode(',', $userIds) . ')',
            'is_active' => 'eq.true',
            'select' => 'user_id,employee_id',
        ]) ?? [];

        return collect($roles)
            ->map(fn ($r) => ['id' => $r['employee_id'], 'telegram_chat_id' => $chatIdByUser->get($r['user_id'])])
            ->filter(fn ($e) => !empty($e['id']) && !empty($e['telegram_chat_id']))
            ->unique('id')
            ->values()
            ->all();
    }

    private function claimReminderSlot(string $employeeId, string $reminderType, string $taskDate): bool
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

    private function send(string $chatId, string $text, string $buttonLabel, string $path): void
    {
        $appUrl = rtrim(env('APP_URL'), '/');

        try {
            $this->telegram->sendMessage(
                (int) $chatId,
                $text,
                $this->telegram->webAppButton($buttonLabel, $appUrl . $path)
            );
        } catch (\Throwable $e) {
            // individual send failures shouldn't stop the batch — same
            // tolerance as TelegramDigestService::broadcast().
        }
    }
}
