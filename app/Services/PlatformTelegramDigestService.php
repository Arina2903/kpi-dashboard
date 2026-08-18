<?php

namespace App\Services;

/**
 * The tenant-aware replacement for the legacy `TelegramDigestService`, whose
 * `broadcast()` sends the identical message to every `telegram_chat_id` in
 * the (dead) legacy `users` table with no company, role, or status check at
 * all. This is the concrete shape of the leak CLAUDE.md's Telegram security
 * model calls out: "Employee A from Company A must never accidentally
 * receive Company B's KPI reminder/data."
 *
 * Every message here is built from `TelegramAuthorizedScope::forTelegramUserId()`
 * — a suspended/deactivated user, or one whose only company is suspended or
 * archived, resolves to no scope or an empty company list and is silently
 * skipped, not sent a generic reminder. Nothing is cached between runs, so a
 * suspension applied five minutes before the next digest job takes effect
 * immediately, with no separate "sync" step required.
 */
class PlatformTelegramDigestService
{
    public function __construct(
        private SupabaseService $supabase,
        private SupabaseAuthService $authService,
        private TelegramService $telegram,
        private AuditLogService $auditLog,
    ) {
    }

    public function sendMorning(): array
    {
        return $this->broadcast(
            'telegram_digest_morning',
            fn (array $context) => $this->buildMorningText($context),
            '📝 Open KPI Dashboard'
        );
    }

    public function sendEvening(): array
    {
        return $this->broadcast(
            'telegram_digest_evening',
            fn (array $context) => $this->buildEveningText($context),
            '📈 Update My Progress'
        );
    }

    /**
     * @return array{sent: int, skipped: int}
     */
    private function broadcast(string $action, \Closure $buildText, string $buttonLabel): array
    {
        $linkedUsers = $this->supabase->get('users', [
            'telegram_user_id' => 'not.is.null',
            'select' => 'id,telegram_user_id,telegram_chat_id',
        ]) ?? [];

        $sent = 0;
        $skipped = 0;

        foreach ($linkedUsers as $linked) {
            $scope = TelegramAuthorizedScope::forTelegramUserId(
                $this->supabase,
                $this->authService,
                $linked['telegram_user_id']
            );

            // Unlinked-by-suspension, deactivated, or session mint failure —
            // all treated identically: send nothing.
            if (!$scope) {
                $skipped++;
                continue;
            }

            $context = $scope->assistantContext();

            // RLS already excludes suspended/archived companies from this
            // list (see auth_company_ids()) — an active user whose only
            // company was just suspended lands here with nothing to report.
            if (empty($context['companies'])) {
                $skipped++;
                continue;
            }

            try {
                $this->telegram->sendMessage(
                    (int) $linked['telegram_chat_id'],
                    $buildText($context),
                    $this->telegram->webAppButton($buttonLabel, rtrim(env('APP_URL'), '/') . '/platform/dashboard')
                );
                $sent++;
            } catch (\Throwable) {
                // Individual send failures are already logged inside
                // TelegramService; keep broadcasting to everyone else.
                $skipped++;
            }

            usleep(50000);
        }

        // One summary row per run, not one per recipient — a per-recipient
        // row would multiply `admin_action_logs` by however many people are
        // linked on every single cron tick, for an event with no company or
        // admin-actor context that would make individual rows useful to
        // browse. `sent`/`skipped` in metadata is what a "did the digest
        // actually run" audit question needs; recordBestEffort() so a
        // logging hiccup never fails the cron job itself.
        $this->auditLog->recordBestEffort([
            'action' => $action,
            'target_type' => 'telegram_digest',
            'metadata' => ['sent' => $sent, 'skipped' => $skipped, 'recipients_considered' => count($linkedUsers)],
        ]);

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    private function buildMorningText(array $context): string
    {
        $companyNames = collect($context['companies'])->pluck('name')->join(', ');
        $kpiCount = count($context['kpis']);

        return "📝 <b>Good morning!</b>\n"
            . "You have {$kpiCount} KPI(s) visible to you at {$companyNames}. "
            . 'Open your dashboard to review today\'s targets.';
    }

    private function buildEveningText(array $context): string
    {
        $submissionCount = count($context['submissions']);

        return "📈 <b>How did today go?</b>\n"
            . "You've logged {$submissionCount} submission(s) recently. "
            . 'Open your dashboard to add today\'s update.';
    }
}
