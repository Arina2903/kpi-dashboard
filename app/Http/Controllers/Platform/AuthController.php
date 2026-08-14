<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Platform/Login');
    }

    public function submitLogin(Request $request, SupabaseAuthService $auth)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $session = $auth->signIn($request->email, $request->password);
        } catch (\Throwable) {
            return back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        session([
            'platform_access_token' => $session['access_token'],
            'platform_refresh_token' => $session['refresh_token'] ?? null,
        ]);

        return redirect()->route('platform.dashboard');
    }

    public function logout(Request $request, SupabaseAuthService $auth)
    {
        $token = session('platform_access_token');

        if ($token) {
            try {
                $auth->signOut($token);
            } catch (\Throwable) {
                // Best-effort — the local session is cleared regardless below.
            }
        }

        session()->forget(['platform_access_token', 'platform_refresh_token']);

        return redirect()
            ->route('platform.login')
            ->with('success', 'You have been logged out.');
    }
}