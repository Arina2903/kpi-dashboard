<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Generates AI-scored weekly / monthly / quarterly performance reviews for
 * every Telegram-linked employee dashboard, grounded in that employee's
 * actual task activity (telegram_project_task_updates) and current KPI
 * standing. Called from TelegramCronController via Supabase pg_cron — see
 * database/telegram_review_cron.sql.
 */
class TelegramReviewService
{
    public function __construct(
        private SupabaseService $supabase,
        private AiService $ai,
        private TelegramService $telegram,
    ) {
    }

    private function todayMy(): Carbon
    {
        return now('Asia/Kuala_Lumpur')->startOfDay();
    }

    /**
     * Generates reviews for the given period type across every linked
     * dashboard, storing each and pushing a Telegram notification. Returns
     * the number of reviews generated.
     */
    public function generate(string $periodType): int
    {
        $dashboards = $this->linkedDashboards();
        $generated = 0;

        foreach ($dashboards as $dashboard) {
            $periods = $this->periodsFor($periodType, $dashboard['employee_id']);

            foreach ($periods as $period) {
                if ($this->reviewExists($dashboard['employee_id'], $periodType, $period['start'])) {
                    continue;
                }

                $stats = $this->gatherStats(
                    $dashboard['employee_id'],
                    $dashboard['company_code'],
                    $period['start'],
                    $period['end']
                );

                try {
                    $result = $this->ai->generatePerformanceReview(
                        $dashboard['employee_name'],
                        $periodType,
                        $period['label'],
                        $stats
                    );
                } catch (\Throwable $e) {
                    continue;
                }

                try {
                    $review = $this->supabase->insert('telegram_ai_reviews', [
                        'employee_id' => $dashboard['employee_id'],
                        'company_code' => $dashboard['company_code'],
                        'period_type' => $periodType,
                        'period_label' => $period['label'],
                        'period_start' => $period['start'],
                        'period_end' => $period['end'],
                        'score' => (float) ($result['score'] ?? 0),
                        'narrative' => $result['narrative'] ?? '',
                        'stats' => json_encode($stats),
                        'generated_at' => now()->toISOString(),
                    ]);
                } catch (\Throwable $e) {
                    continue;
                }

                $generated++;

                if (!empty($review[0]) && !empty($dashboard['chat_id'])) {
                    $this->notify($dashboard, $periodType, $period['label'], $review[0]['id']);
                }
            }
        }

        return $generated;
    }

    /**
     * Every (user, employee, company) dashboard with a linked Telegram chat —
     * same shape as TelegramLinkController::getDashboards, but flattened for
     * bulk iteration here.
     */
    private function linkedDashboards(): array
    {
        $users = $this->supabase->get('users', [
            'telegram_chat_id' => 'not.is.null',
            'select' => 'id,telegram_chat_id',
        ]) ?? [];

        if (empty($users)) {
            return [];
        }

        $usersById = collect($users)->keyBy('id');
        $userIds = array_column($users, 'id');

        $roles = $this->supabase->get('user_company_roles', [
            'user_id' => 'in.(' . implode(',', $userIds) . ')',
            'is_active' => 'eq.true',
            'select' => '*',
        ]) ?? [];

        $dashboards = [];

        foreach ($roles as $role) {
            $employee = $this->supabase->first('employees', [
                'id' => 'eq.' . $role['employee_id'],
                'is_active' => 'eq.true',
                'select' => 'id,short_name,full_name',
            ]);

            if (empty($employee)) {
                continue;
            }

            $user = $usersById->get($role['user_id']);

            $dashboards[] = [
                'employee_id' => $employee['id'],
                'employee_name' => $employee['full_name'] ?? $employee['short_name'] ?? 'Employee',
                'company_code' => $role['company_code'],
                'chat_id' => $user['telegram_chat_id'] ?? null,
            ];
        }

        return $dashboards;
    }

    /**
     * Returns the period(s) to generate for a dashboard. Weekly and monthly
     * are a single fixed period (the cron only fires on the right day).
     * Quarterly can be zero or more — each KPI quarter that ended yesterday,
     * grouped by quarter label, since quarter dates are set per-KPI rather
     * than on one company-wide calendar.
     */
    private function periodsFor(string $periodType, string $employeeId): array
    {
        $today = $this->todayMy();

        if ($periodType === 'weekly') {
            $end = $today->copy();
            $start = $end->copy()->subDays(6);
            return [[
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => 'Week of ' . $start->format('d M') . ' – ' . $end->format('d M Y'),
            ]];
        }

        if ($periodType === 'monthly') {
            $end = $today->copy()->subDay()->endOfMonth()->startOfDay();
            $start = $end->copy()->startOfMonth();
            return [[
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $end->format('F Y'),
            ]];
        }

        // quarterly — each KPI quarter that ended yesterday, found via a
        // two-step lookup since PostgREST filters can't nest a sub-select.
        $yesterday = $today->copy()->subDay()->toDateString();

        $kpiIds = array_column($this->supabase->get('kpis', [
            'employee_id' => 'eq.' . $employeeId,
            'select' => 'id',
        ]) ?? [], 'id');

        if (empty($kpiIds)) {
            return [];
        }

        $quarters = $this->supabase->get('kpi_quarters', [
            'kpi_id' => 'in.(' . implode(',', $kpiIds) . ')',
            'end_date' => 'eq.' . $yesterday,
            'select' => '*',
        ]) ?? [];

        if (empty($quarters)) {
            return [];
        }

        $byLabel = collect($quarters)->groupBy('quarter');

        return $byLabel->map(function ($rows, $label) {
            $starts = collect($rows)->pluck('start_date')->filter();
            $ends = collect($rows)->pluck('end_date')->filter();

            return [
                'start' => $starts->min(),
                'end' => $ends->max(),
                'label' => $label,
            ];
        })->values()->all();
    }

    private function reviewExists(string $employeeId, string $periodType, string $periodStart): bool
    {
        $existing = $this->supabase->get('telegram_ai_reviews', [
            'employee_id' => 'eq.' . $employeeId,
            'period_type' => 'eq.' . $periodType,
            'period_start' => 'eq.' . $periodStart,
            'select' => 'id',
        ]) ?? [];

        return !empty($existing);
    }

    private function gatherStats(string $employeeId, string $companyCode, string $periodStart, string $periodEnd): array
    {
        $fy = 'FY' . Carbon::parse($periodEnd)->year;

        $kpis = $this->supabase->get('kpis', [
            'employee_id' => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'financial_year' => 'eq.' . $fy,
            'select' => 'kpi_title,category,achievement_percentage,status',
        ]) ?? [];

        $kpiSummaries = array_map(fn($k) => [
            'kpi_title' => $k['kpi_title'],
            'category' => $k['category'] ?? 'Uncategorized',
            'achievement_percentage' => (float) ($k['achievement_percentage'] ?? 0),
            'status' => $k['status'] ?? 'not_started',
        ], $kpis);

        $tasks = $this->supabase->get('telegram_project_tasks', [
            'employee_id' => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'select' => 'id,title,unit,target,actual,status',
        ]) ?? [];

        $taskIds = array_column($tasks, 'id');

        $updates = empty($taskIds) ? [] : ($this->supabase->get('telegram_project_task_updates', [
            'task_id' => 'in.(' . implode(',', $taskIds) . ')',
            'select' => 'task_id,delta,created_at',
        ]) ?? []);

        $periodUpdates = array_filter($updates, function ($u) use ($periodStart, $periodEnd) {
            $day = substr($u['created_at'], 0, 10);
            return $day >= $periodStart && $day <= $periodEnd;
        });

        $activeDays = count(array_unique(array_map(fn($u) => substr($u['created_at'], 0, 10), $periodUpdates)));
        $totalDays = Carbon::parse($periodStart)->diffInDays(Carbon::parse($periodEnd)) + 1;

        $deltaByTask = [];
        foreach ($periodUpdates as $u) {
            $deltaByTask[$u['task_id']] = ($deltaByTask[$u['task_id']] ?? 0) + (float) $u['delta'];
        }

        $taskSummaries = [];
        foreach ($tasks as $t) {
            if (!isset($deltaByTask[$t['id']])) {
                continue;
            }
            $taskSummaries[] = [
                'title' => $t['title'],
                'unit' => $t['unit'],
                'target' => (float) $t['target'],
                'actual' => (float) $t['actual'],
                'status' => $t['status'],
                'delta_in_period' => $deltaByTask[$t['id']],
            ];
        }

        return [
            'active_days' => $activeDays,
            'total_days' => $totalDays,
            'tasks' => $taskSummaries,
            'kpis' => $kpiSummaries,
        ];
    }

    private function notify(array $dashboard, string $periodType, string $periodLabel, string $reviewId): void
    {
        $periodWord = match ($periodType) {
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => 'Quarterly',
        };

        $appUrl = rtrim(env('APP_URL'), '/');

        $this->telegram->sendMessage(
            (int) $dashboard['chat_id'],
            "<b>{$periodWord} performance review ready</b>\n{$periodLabel} — your review is available in the Mini App.",
            $this->telegram->webAppButton('View Review', $appUrl . '/telegram/app?screen=review')
        );

        $this->supabase->safePatch('telegram_ai_reviews', ['id' => 'eq.' . $reviewId], [
            'notified_at' => now()->toISOString(),
        ]);
    }
}
