<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * An RLS-respecting Supabase client. Every request carries the anon key plus
 * the signed-in user's own Supabase Auth access token — never service_role —
 * so `auth.uid()` inside Postgres resolves to that real user and every RLS
 * policy actually applies. This is the client every normal, non-privileged
 * database call in the multi-company platform should go through.
 */
class SupabaseUserService
{
    protected string $url;

    protected string $anonKey;

    protected string $accessToken;

    public function __construct(string $accessToken)
    {
        $this->url = rtrim(env('SUPABASE_URL'), '/');
        $this->anonKey = env('SUPABASE_ANON_KEY');
        $this->accessToken = $accessToken;
    }

    private function request()
    {
        return Http::timeout(15)->connectTimeout(5)->withHeaders([
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Prefer' => 'return=representation',
        ]);
    }

    private function endpoint(string $table)
    {
        return $this->url . '/rest/v1/' . $table;
    }

    public function get(string $table, array $query = [])
    {
        return $this->request()->get($this->endpoint($table), $query)->throw()->json();
    }

    public function first(string $table, array $query = [])
    {
        $query['limit'] = 1;
        $result = $this->get($table, $query);

        return $result[0] ?? null;
    }

    /**
     * Same as `first()`, but retries briefly. Use this right after creating a
     * Supabase Auth user, whose matching `users` row is written by an async
     * trigger — querying for it immediately can lose that race.
     */
    public function firstEventually(string $table, array $query = [], int $attempts = 10, int $delayMicroseconds = 300_000)
    {
        for ($i = 0; $i < $attempts; $i++) {
            $row = $this->first($table, $query);

            if ($row) {
                return $row;
            }

            usleep($delayMicroseconds);
        }

        return null;
    }

    public function insert(string $table, array $data)
    {
        return $this->request()->post($this->endpoint($table), $data)->throw()->json();
    }

    public function update(string $table, array $filters, array $data)
    {
        $query = http_build_query($filters);

        return $this->request()->patch($this->endpoint($table) . '?' . $query, $data)->throw()->json();
    }

    public function delete(string $table, array $filters = [])
    {
        $url = $this->endpoint($table);

        if (!empty($filters)) {
            $url .= '?' . http_build_query($filters);
        }

        return $this->request()->delete($url)->throw()->json();
    }

    /** Calls a Postgres function via PostgREST's RPC endpoint, e.g. the auth_*() helpers. */
    public function rpc(string $function, array $params = [])
    {
        return $this->request()->post($this->url . '/rest/v1/rpc/' . $function, $params)->throw()->json();
    }
}