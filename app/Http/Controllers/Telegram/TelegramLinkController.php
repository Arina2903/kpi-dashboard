<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class TelegramLinkController extends Controller
{
    public function status(Request $request, SupabaseService $supabase)
    {
        $telegramUser = $request->attributes->get('telegram_user');

        $user = $supabase->first('users', [
            'telegram_user_id' => 'eq.' . $telegramUser['id'],
            'select' => '*',
        ]);

        if (empty($user)) {
            return response()->json(['linked' => false]);
        }

        return response()->json([
            'linked' => true,
            'username' => $user['telegram_username'] ?? null,
            'dashboards' => $this->getDashboards($supabase, $user['id']),
        ]);
    }

    public function disconnect(Request $request, SupabaseService $supabase)
    {
        $telegramUser = $request->attributes->get('telegram_user');

        $user = $supabase->first('users', [
            'telegram_user_id' => 'eq.' . $telegramUser['id'],
            'select' => 'id',
        ]);

        if (empty($user)) {
            return response()->json(['success' => false, 'message' => 'Not linked.'], 404);
        }

        $supabase->safePatch('users', ['id' => 'eq.' . $user['id']], [
            'telegram_user_id' => null,
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_linked_at' => null,
        ]);

        return response()->json(['success' => true]);
    }

    private function getDashboards(SupabaseService $supabase, string $userId): array
    {
        $roles = $supabase->get('user_company_roles', [
            'user_id' => 'eq.' . $userId,
            'is_active' => 'eq.true',
            'select' => '*',
        ]) ?? [];

        $dashboards = [];

        foreach ($roles as $roleAccess) {
            $employee = $supabase->first('employees', [
                'id' => 'eq.' . $roleAccess['employee_id'],
                'is_active' => 'eq.true',
                'select' => '*',
            ]);

            if (empty($employee)) {
                continue;
            }

            $company = $supabase->first('companies', [
                'code' => 'eq.' . $roleAccess['company_code'],
                'select' => '*',
            ]);

            $dashboards[] = [
                'employee_id' => $employee['id'],
                'company_code' => $roleAccess['company_code'],
                'company_display_name' => $company['display_name'] ?? ($company['name'] ?? $roleAccess['company_code']),
                'short_name' => $employee['short_name'] ?? $employee['full_name'] ?? 'User',
                'role' => $employee['role'] ?? null,
            ];
        }

        return $dashboards;
    }
}
