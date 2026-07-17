<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Creates in-app notifications (notifications table) and, when the recipient
 * has a linked Telegram account, pushes the same message there. Used when a
 * subordinate submits their Job Description or a quarterly appraisal
 * self-assessment, to tell their manager/VP/SLT chain it's ready for review.
 */
class NotificationService
{
    public function __construct(
        private SupabaseService $supabase,
        private TelegramService $telegram,
    ) {
    }

    /**
     * Walks reports_to_id up to 3 hops from the given employee — matching the
     * Manager / VP / SLT appraiser levels used in PerformanceController — and
     * returns the employee IDs found. Stops early if the chain is shorter or
     * loops back on itself.
     */
    public function appraiserChainFor(string $employeeId): array
    {
        $chain = [];
        $currentId = $employeeId;

        for ($i = 0; $i < 3; $i++) {
            $current = $this->supabase->first('employees', [
                'id'     => 'eq.' . $currentId,
                'select' => 'reports_to_id',
            ]);

            $parentId = $current['reports_to_id'] ?? null;
            if (empty($parentId) || in_array($parentId, $chain, true)) {
                break;
            }

            $chain[]   = $parentId;
            $currentId = $parentId;
        }

        return $chain;
    }

    /**
     * Creates a notification row per recipient and, if linked, sends them a
     * Telegram message. Each recipient is handled independently so one
     * failure (bad row, Telegram down) doesn't block the rest.
     */
    public function notify(
        array $recipientEmployeeIds,
        string $type,
        array $subject,
        string $title,
        ?string $message = null,
        ?string $link = null,
        ?string $quarter = null,
        ?string $financialYear = null
    ): void {
        $recipientEmployeeIds = array_values(array_unique(array_filter($recipientEmployeeIds)));

        foreach ($recipientEmployeeIds as $recipientId) {
            try {
                $this->supabase->insert('notifications', [
                    'recipient_employee_id' => $recipientId,
                    'type'                  => $type,
                    'subject_employee_id'   => $subject['id'] ?? null,
                    'subject_name'          => $subject['name'] ?? 'Someone',
                    'quarter'               => $quarter,
                    'financial_year'        => $financialYear,
                    'title'                 => $title,
                    'message'               => $message,
                    'link'                  => $link,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to create notification', ['recipient' => $recipientId, 'error' => $e->getMessage()]);
            }

            $this->sendTelegram($recipientId, $title, $message, $link);
        }
    }

    private function sendTelegram(string $recipientId, string $title, ?string $message, ?string $link): void
    {
        $chatId = $this->telegramChatIdFor($recipientId);
        if (!$chatId) {
            return;
        }

        $text = "<b>{$title}</b>";
        if ($message) {
            $text .= "\n" . $message;
        }

        $keyboard = $link ? [['text' => 'Open', 'url' => $link]] : null;

        try {
            $this->telegram->sendMessage($chatId, $text, $keyboard);
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram notification', ['recipient' => $recipientId, 'error' => $e->getMessage()]);
        }
    }

    private function telegramChatIdFor(string $employeeId): ?int
    {
        $role = $this->supabase->first('user_company_roles', [
            'employee_id' => 'eq.' . $employeeId,
            'is_active'   => 'eq.true',
            'select'      => 'user_id',
        ]);

        if (empty($role['user_id'])) {
            return null;
        }

        $user = $this->supabase->first('users', [
            'id'     => 'eq.' . $role['user_id'],
            'select' => 'telegram_chat_id',
        ]);

        return !empty($user['telegram_chat_id']) ? (int) $user['telegram_chat_id'] : null;
    }
}
