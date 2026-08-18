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

    /**
     * `$prefer` replaces the default `Prefer` value outright rather than
     * layering another `withHeaders()` call on top — chaining `withHeaders()`
     * with a repeated key doesn't override it, it appends, producing a
     * `Prefer: return=representation, return=minimal` header that PostgREST
     * doesn't interpret the way callers would expect (confirmed directly:
     * `Http::fake()` records both values in the sent header array when
     * chained). Building the full header set in one call is what avoids that.
     */
    private function request(string $prefer = 'return=representation')
    {
        return Http::timeout(15)->connectTimeout(5)->withHeaders([
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Prefer' => $prefer,
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

    /**
     * `$returnRepresentation = false` sends `Prefer: return=minimal`, which
     * skips PostgREST's implicit `RETURNING`. Use this for tables whose
     * SELECT policy calls a function that queries back into the SAME table
     * for the row just being inserted (e.g. `kpis_select`/`kpi_submissions_select`
     * both route through `auth_can_view_kpi()`, which does exactly that) — a
     * real, confirmed case where the RETURNING-clause's implicit SELECT check
     * fails for a non-Super-Admin caller even though the INSERT's own WITH
     * CHECK independently evaluates true, and even though the exact same
     * predicate evaluated directly (outside a RETURNING context, e.g. via
     * `rpc()`) also returns true. Every caller here that doesn't actually use
     * the returned row should prefer `false` over hitting that failure mode.
     */
    public function insert(string $table, array $data, bool $returnRepresentation = true)
    {
        $prefer = $returnRepresentation ? 'return=representation' : 'return=minimal';

        return $this->request($prefer)->post($this->endpoint($table), $data)->throw()->json();
    }

    /**
     * `$returnRepresentation = false` sends `Prefer: return=minimal`, for the
     * same reason `insert()` supports it: a SELECT policy that queries back
     * into the same table (e.g. `kpis_select` via `auth_can_view_kpi()`)
     * fails on the implicit RETURNING clause for a non-Super-Admin caller,
     * even though the UPDATE's own WITH CHECK independently evaluates true.
     */
    public function update(string $table, array $filters, array $data, bool $returnRepresentation = true)
    {
        $query = http_build_query($filters);
        $prefer = $returnRepresentation ? 'return=representation' : 'return=minimal';

        return $this->request($prefer)->patch($this->endpoint($table) . '?' . $query, $data)->throw()->json();
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

    /**
     * The auth_user_id (Supabase Auth's own `sub` claim) this client's token
     * belongs to — decoded directly from the JWT, no network round trip.
     *
     * This is the only reliable way to answer "which `users` row is mine."
     * RLS decides which rows are VISIBLE to a caller — for a Super Admin,
     * every row in the table; for a Company Admin, every user in their own
     * company — not which one IS the caller. A bare `->first('users', [...])`
     * with no filter picks whatever row PostgREST happens to return first out
     * of that visible set, which is only "yourself" by coincidence. Every
     * "who am I" lookup must filter explicitly on `auth_user_id = eq.<this>`.
     */
    public function currentAuthUserId(): string
    {
        $parts = explode('.', $this->accessToken);

        if (count($parts) !== 3) {
            throw new \RuntimeException("Access token is not a valid JWT; can't determine the caller's identity.");
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')) ?: '', true);

        if (!is_array($payload) || empty($payload['sub'])) {
            throw new \RuntimeException("Access token has no 'sub' claim; can't determine the caller's identity.");
        }

        return $payload['sub'];
    }
}