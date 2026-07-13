<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    private function currentUser(SupabaseService $supabase): array
    {
        $employees = $supabase->get('employees', [
            'id'        => 'eq.' . session('employee_uuid'),
            'is_active' => 'eq.true',
            'select'    => '*',
        ]);

        if (empty($employees)) {
            session()->flush();
            abort(403, 'Employee not found.');
        }

        return $employees[0];
    }

    private function sidebarData(SupabaseService $supabase, array $user): array
    {
        $departments = $supabase->get('departments', [
            'company_code' => 'eq.' . $user['company_code'],
            'select'       => '*',
            'order'        => 'name.asc',
        ]) ?? [];

        $role                   = strtoupper(trim($user['role'] ?? ''));
        $canSwitchDepartment    = $role === 'SLT';
        $selectedDepartmentCode = session('selected_department_code') ?? $user['department_code'] ?? null;

        $department = null;
        if ($selectedDepartmentCode) {
            $res        = $supabase->get('departments', ['code' => 'eq.' . $selectedDepartmentCode, 'select' => '*']);
            $department = $res[0] ?? null;
        }

        $pendingApprovalCount = count($supabase->get('kpi_update_approvals', [
            'approver_id' => 'eq.' . $user['id'],
            'status'      => 'eq.pending',
            'select'      => 'id',
        ]) ?? []);

        return compact('departments', 'department', 'canSwitchDepartment', 'selectedDepartmentCode', 'pendingApprovalCount');
    }

    public function index(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $manager = null;
        if (!empty($user['reports_to_id'])) {
            $res      = $supabase->get('employees', ['id' => 'eq.' . $user['reports_to_id'], 'select' => 'short_name,full_name,position']);
            $manager  = $res[0] ?? null;
        }

        return view('profile', array_merge([
            'user'    => $user,
            'manager' => $manager,
        ], $this->sidebarData($supabase, $user)));
    }

    public function connectTelegram(Request $request, SupabaseService $supabase)
    {
        $code = Str::upper(Str::random(8));

        $supabase->safePatch('users', ['id' => 'eq.' . session('user_uuid')], [
            'telegram_link_code' => $code,
            'telegram_link_code_expires_at' => now()->addMinutes(5)->toIso8601String(),
        ]);

        return response()->json([
            'code' => $code,
            'deep_link' => 'https://t.me/' . env('TELEGRAM_BOT_USERNAME') . '?start=' . $code,
        ]);
    }

    public function telegramStatus(Request $request, SupabaseService $supabase)
    {
        $user = $supabase->first('users', [
            'id' => 'eq.' . session('user_uuid'),
            'select' => 'telegram_username,telegram_linked_at',
        ]);

        return response()->json([
            'linked' => !empty($user['telegram_linked_at']),
            'username' => $user['telegram_username'] ?? null,
            'linked_at' => $user['telegram_linked_at'] ?? null,
        ]);
    }

    public function updateEmail(Request $request, SupabaseService $supabase)
    {
        $request->validate([
            'email'            => 'required|email',
            'current_password' => 'required|string',
        ]);

        $authUser = $supabase->first('users', ['id' => 'eq.' . session('user_uuid'), 'select' => '*']);

        if (!$authUser || !$this->currentPasswordMatches($request->current_password, $authUser)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $existing = $supabase->first('users', ['email' => 'eq.' . $request->email, 'select' => 'id']);
        if ($existing && $existing['id'] !== $authUser['id']) {
            return back()->with('error', 'That email is already used by another account.');
        }

        $supabase->update('users', ['id' => 'eq.' . $authUser['id']], ['email' => $request->email]);
        $supabase->safePatch('employees', ['id' => 'eq.' . session('employee_uuid')], ['email' => $request->email]);

        session(['user_email' => $request->email]);

        return back()->with('success', 'Email updated successfully.');
    }

    public function updatePassword(Request $request, SupabaseService $supabase)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'          => 'required|string|min:8|confirmed',
        ]);

        $authUser = $supabase->first('users', ['id' => 'eq.' . session('user_uuid'), 'select' => '*']);

        if (!$authUser || !$this->currentPasswordMatches($request->current_password, $authUser)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $supabase->update('users', ['id' => 'eq.' . $authUser['id']], [
            'password_hash' => Hash::make($request->password),
        ]);

        session(['using_default_password' => false]);

        return back()->with('success', 'Password updated successfully.');
    }

    // Mirrors AuthController's login check: accounts with no password_hash yet
    // are still gated by the shared default password until they set their own.
    private function currentPasswordMatches(string $inputPassword, array $authUser): bool
    {
        if (empty($authUser['password_hash'])) {
            return $inputPassword === 'Richworks';
        }

        return Hash::check($inputPassword, $authUser['password_hash']);
    }
}
