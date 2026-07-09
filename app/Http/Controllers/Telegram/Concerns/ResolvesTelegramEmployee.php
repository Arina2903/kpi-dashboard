<?php

namespace App\Http\Controllers\Telegram\Concerns;

use App\Services\SupabaseService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

trait ResolvesTelegramEmployee
{
    /**
     * Resolves and authorizes the (user, employee, company) context for a Telegram
     * Mini App request. The client holds employee_id/company_code in JS state and
     * sends them on every call, so both must be re-verified against the verified
     * telegram_user_id on every request — never trusted as-is.
     *
     * @return array{user: array, employee_id: string, company_code: string}
     */
    private function resolveContext(Request $request, SupabaseService $supabase, string $employeeId, string $companyCode): array
    {
        $telegramUser = $request->attributes->get('telegram_user');

        $user = $supabase->first('users', [
            'telegram_user_id' => 'eq.' . $telegramUser['id'],
            'select' => '*',
        ]);

        if (empty($user)) {
            throw new HttpResponseException(response()->json(['success' => false, 'message' => 'Telegram account not linked.'], 401));
        }

        $roles = $supabase->get('user_company_roles', [
            'user_id' => 'eq.' . $user['id'],
            'employee_id' => 'eq.' . $employeeId,
            'company_code' => 'eq.' . $companyCode,
            'is_active' => 'eq.true',
            'select' => 'id',
        ]) ?? [];

        if (empty($roles)) {
            throw new HttpResponseException(response()->json(['success' => false, 'message' => 'Not authorized for this dashboard.'], 403));
        }

        return [
            'user' => $user,
            'employee_id' => $employeeId,
            'company_code' => $companyCode,
        ];
    }
}
