<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Wraps Supabase Auth's own REST API. Sign-in and sign-out use the anon key,
 * same as any browser client would. `createUser()` uses the service_role key
 * and must only ever be called from server-side code — it is how a Richworks
 * Super Admin or Company Admin invites someone, never something the browser
 * calls directly.
 */
class SupabaseAuthService
{
    protected string $url;

    protected string $anonKey;

    protected string $serviceRoleKey;

    public function __construct()
    {
        $this->url = rtrim(env('SUPABASE_URL'), '/');
        $this->anonKey = env('SUPABASE_ANON_KEY');
        $this->serviceRoleKey = env('SUPABASE_SERVICE_ROLE_KEY');
    }

    /**
     * Sign in with email + password. Returns the Supabase Auth session
     * (access_token, refresh_token, expires_in, user) on success.
     */
    public function signIn(string $email, string $password): array
    {
        $response = Http::timeout(15)->withHeaders([
            'apikey' => $this->anonKey,
            'Content-Type' => 'application/json',
        ])->post($this->url . '/auth/v1/token?grant_type=password', [
            'email' => $email,
            'password' => $password,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Invalid email or password.');
        }

        return $response->json();
    }

    public function signOut(string $accessToken): void
    {
        Http::timeout(10)->withHeaders([
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post($this->url . '/auth/v1/logout');
    }

    /**
     * Admin-only: create a Supabase Auth user directly with a password the
     * caller already knows, pre-confirmed (no email step). This only remains
     * in use by `platform:bootstrap-super-admin` — a CLI operation run by
     * whoever holds the terminal, where there's no one to email a link to.
     * Every invite a Company/Department Admin sends through the app goes
     * through `generateInviteLink()` instead, never a password we generate
     * on their behalf.
     */
    public function createUser(string $email, string $password, array $metadata = []): array
    {
        $response = Http::timeout(15)->withHeaders([
            'apikey' => $this->serviceRoleKey,
            'Authorization' => 'Bearer ' . $this->serviceRoleKey,
            'Content-Type' => 'application/json',
        ])->post($this->url . '/auth/v1/admin/users', [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => $metadata,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to create user: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Admin-only: create (or reuse) a Supabase Auth user and mint an invite
     * token for them, without ever generating a password ourselves. The
     * response's `hashed_token` is what we email inside our own link — never
     * Supabase's own `action_link`, since that redirects with the session in
     * a URL fragment, which only client-side JS can read; we verify the
     * token server-side instead (see `verifyInviteToken()`), matching how
     * every other Supabase call in this app already works.
     *
     * Also returns the created/existing user's `id` synchronously, in the
     * same response — unlike `createUser()`, callers don't need to poll for
     * the `public.users` row's *identity* afterward, only for it to finish
     * being written (see `SupabaseUserService::firstEventually()`).
     */
    public function generateInviteLink(string $email, array $metadata = []): array
    {
        $response = Http::timeout(15)->withHeaders([
            'apikey' => $this->serviceRoleKey,
            'Authorization' => 'Bearer ' . $this->serviceRoleKey,
            'Content-Type' => 'application/json',
        ])->post($this->url . '/auth/v1/admin/generate_link', [
            'type' => 'invite',
            'email' => $email,
            'data' => $metadata,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to generate invite link: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Exchanges an invite (or recovery) token hash for a real Supabase Auth
     * session — the server-side equivalent of what clicking Supabase's own
     * `action_link` would do client-side. Uses the anon key, same as
     * `signIn()`, since this is exactly what an unauthenticated visitor
     * clicking an emailed link is allowed to do.
     */
    public function verifyInviteToken(string $tokenHash): array
    {
        $response = Http::timeout(15)->withHeaders([
            'apikey' => $this->anonKey,
            'Content-Type' => 'application/json',
        ])->post($this->url . '/auth/v1/verify', [
            'type' => 'invite',
            'token_hash' => $tokenHash,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('This invite link is invalid or has expired.');
        }

        return $response->json();
    }

    /**
     * Sets the password on the account behind the given access token — used
     * once, right after `verifyInviteToken()`, to let someone accepting an
     * invite choose their own password instead of us choosing one for them.
     */
    public function setPassword(string $accessToken, string $password): void
    {
        $response = Http::timeout(15)->withHeaders([
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->put($this->url . '/auth/v1/user', [
            'password' => $password,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to set password: ' . $response->body());
        }
    }
}