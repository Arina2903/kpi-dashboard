<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

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
        // BTS has cross-department admin/support access, same level as SLT.
        $canSwitchDepartment    = $role === 'SLT' || ($user['department_code'] ?? '') === 'BTS';
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

        $departmentCode = session('selected_department_code') ?? $user['department_code'] ?? null;
        $department = $departmentCode
            ? ($supabase->get('departments', ['code' => 'eq.' . $departmentCode, 'select' => '*'])[0] ?? null)
            : null;

        return Inertia::render('Profile', [
            'user'       => $user,
            'manager'    => $manager,
            'department' => $department,
        ]);
    }

    public function settings(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        return Inertia::render('Settings', [
            'user' => $user,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APPEARANCE THEME (Account Settings)
    |--------------------------------------------------------------------------
    */

    public function updateTheme(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'theme_bg'             => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_card'           => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_accent'         => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_accent2'        => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_border'         => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_text'           => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_sidebar_bg'     => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_sidebar_accent' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_sidebar_text'   => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_font_family'    => 'nullable|in:Inter,Poppins,Roboto,Nunito,Merriweather,Fira Code',
            'theme_font_size'      => 'nullable|in:sm,md,lg',
        ]);

        $payload = [
            'theme_bg'             => $validated['theme_bg']             ?? null,
            'theme_card'           => $validated['theme_card']           ?? null,
            'theme_accent'         => $validated['theme_accent']         ?? null,
            'theme_accent2'        => $validated['theme_accent2']        ?? null,
            'theme_border'         => $validated['theme_border']         ?? null,
            'theme_text'           => $validated['theme_text']           ?? null,
            'theme_sidebar_bg'     => $validated['theme_sidebar_bg']     ?? null,
            'theme_sidebar_accent' => $validated['theme_sidebar_accent'] ?? null,
            'theme_sidebar_text'   => $validated['theme_sidebar_text']   ?? null,
            'theme_font_family'    => $validated['theme_font_family']    ?? null,
            'theme_font_size'      => $validated['theme_font_size']      ?? null,
        ];

        $ok = $supabase->safePatch('employees', ['id' => 'eq.' . session('employee_uuid')], $payload);

        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Could not save theme. Please try again.'], 500);
        }

        // Reflect immediately — every page reads these same flat session keys
        // via partials/sidebar.blade.php, so the new theme applies on the very
        // next page load without needing to log out and back in.
        session($payload);

        return response()->json(['success' => true]);
    }

    public function updateSalutation(Request $request, SupabaseService $supabase)
    {
        $validated = $request->validate([
            'salutation' => 'nullable|in:Mr.,Mrs.,Ms.,Dr.',
        ]);

        $salutation = $validated['salutation'] ?? null;

        $supabase->safePatch('employees', ['id' => 'eq.' . session('employee_uuid')], [
            'salutation' => $salutation,
        ]);

        // Reflect immediately, same as updateTheme() — every page reads this
        // straight from the session, so no re-login needed to see it change.
        session(['salutation' => $salutation]);

        return back()->with('success', 'Display title updated.');
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
