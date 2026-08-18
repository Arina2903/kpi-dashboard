<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\ApprovalActionService;

class SupabaseService
{
    protected string $url;

    protected string $key;

    // These reference tables are never written to by the app (managed
    // directly in Supabase) and change extremely rarely, so a short cache
    // avoids re-fetching them on every single page load — every controller
    // re-reads "departments" for the sidebar/switcher on every request.
    private const CACHEABLE_TABLES = ['companies', 'departments', 'kpi_permissions'];

    private const CACHE_TTL_SECONDS = 180;

    /**
     * Every Platform table that carries `company_id` under the Core Platform
     * Rule (CLAUDE.md) — i.e. every table where a service_role read/write
     * bypasses Postgres RLS and can cross a tenant boundary. `companies`,
     * `users`, `kpi_templates`, `kpi_template_items`, and `admin_action_logs`
     * are deliberately absent: they're the rule's own documented exemptions,
     * and the three legitimate service_role uses in this codebase
     * (`CompanyController::storeAdmin`, `DepartmentController::storeUser`,
     * `UserCreationController::store`) all read back a freshly-created
     * `users` row the caller provably can't see yet under RLS — nothing
     * legitimate needs this client for a tenant-owned table.
     *
     * This exists because that boundary used to be enforced only by accident:
     * every current caller reaching one of these tables (legacy KPI/approval
     * controllers, the Telegram bot) is dead code today, gated behind either
     * `session('employee_uuid')` or a `telegram_user_id` column, and the
     * `employees`/`user_company_roles` tables those paths depend on don't
     * exist in production — so they error out before ever reaching Platform
     * data. That's incidental: the moment anyone "fixes" a missing column to
     * revive Telegram or ANIRA against real Platform data, this class would
     * otherwise start serving it with RLS switched off, silently. Blocking it
     * here makes "no service_role reads of tenant data" a property of the
     * client, not a fact that happens to be true today. Use
     * `SupabaseUserService` (the caller's own token) for Platform code, or
     * `AuthorizedDataScope` for assistant/bot contexts with no HTTP request
     * of their own to carry a token.
     */
    private const TENANT_OWNED_TABLES = [
        'company_users', 'department_users', 'departments', 'kpi_categories',
        'kpis', 'kpi_submissions', 'roles', 'notifications', 'kpi_access_grants',
        'platform_admin_assignments', 'import_batches', 'audit_logs', 'reports',
        'tasks', 'task_kpi_links',
    ];

    private function assertNotTenantOwned(string $table): void
    {
        if (in_array($table, self::TENANT_OWNED_TABLES, true)) {
            throw new \RuntimeException(
                "SupabaseService (service_role) refused a query against '{$table}' — this is a tenant-owned "
                . 'Platform table and a service_role read/write bypasses RLS entirely. Use SupabaseUserService '
                . "(the caller's own token) for Platform code, or AuthorizedDataScope for assistant/bot contexts."
            );
        }
    }

    public function __construct()
    {
        $this->url = rtrim(
            env('SUPABASE_URL'),
            '/'
        );

        $this->key = env(
            'SUPABASE_SERVICE_ROLE_KEY'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BASE REQUEST
    |--------------------------------------------------------------------------
    */

    private function request()
    {
        return Http::timeout(15)->connectTimeout(5)->withHeaders([

            'apikey' => $this->key,

            'Authorization' => 'Bearer ' . $this->key,

            'Content-Type' => 'application/json',

            'Accept' => 'application/json',

            'Prefer' => 'return=representation',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ENDPOINT
    |--------------------------------------------------------------------------
    */

    private function endpoint(
        string $table
    ){
        return $this->url . '/rest/v1/' . $table;
    }

    /*
    |--------------------------------------------------------------------------
    | GET
    |--------------------------------------------------------------------------
    */

    public function get(
        string $table,
        array $query = []
    ){

        if (in_array($table, self::CACHEABLE_TABLES, true)) {
            $cacheKey = 'supabase:' . $table . ':' . md5(json_encode($query));

            return Cache::remember(
                $cacheKey,
                self::CACHE_TTL_SECONDS,
                fn () => $this->fetch($table, $query)
            );
        }

        return $this->fetch($table, $query);
    }

    private function fetch(
        string $table,
        array $query
    ){
        $this->assertNotTenantOwned($table);

        return $this->request()

            ->get(
                $this->endpoint($table),
                $query
            )

            ->throw()

            ->json();
    }

    /*
    |--------------------------------------------------------------------------
    | GET MANY (concurrent)
    |--------------------------------------------------------------------------
    | Runs several independent GET requests over the wire in parallel instead
    | of one after another. Each Supabase REST call pays a full network
    | round-trip (commonly 300-700ms from this app to Supabase), so a
    | controller issuing N sequential calls pays N round-trips; this pays
    | roughly the cost of the single slowest one. Only use this for calls
    | that don't depend on each other's results — it does not change what
    | gets requested, just when the requests are sent.
    |
    | $requests: ['key' => ['table' => 'kpis', 'query' => [...]], ...]
    | Returns:   ['key' => <decoded json response>, ...] — same shape as
    |            calling get() for each entry one at a time.
    */

    public function getMany(array $requests): array
    {
        if (empty($requests)) {
            return [];
        }

        foreach ($requests as $req) {
            $this->assertNotTenantOwned($req['table']);
        }

        $headers = [
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Prefer'        => 'return=representation',
        ];

        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($requests, $headers) {
            $calls = [];
            foreach ($requests as $key => $req) {
                $calls[] = $pool->as($key)
                    ->timeout(15)
                    ->connectTimeout(5)
                    ->withHeaders($headers)
                    ->get($this->endpoint($req['table']), $req['query'] ?? []);
            }
            return $calls;
        });

        $results = [];
        foreach ($requests as $key => $req) {
            $response = $responses[$key];
            if ($response instanceof \Throwable) {
                throw $response;
            }
            $results[$key] = $response->throw()->json();
        }

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | FIRST
    |--------------------------------------------------------------------------
    */

    public function first(
        string $table,
        array $query = []
    ){
        $query['limit'] = 1;

        $result = $this->get(
            $table,
            $query
        );

        return $result[0] ?? null;
    }

    /**
     * Same as `first()`, but retries briefly — for rows written by an async
     * trigger (e.g. the `auth.users` -> `public.users` sync) that may not be
     * visible yet the instant the triggering call returns.
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

    /*
    |--------------------------------------------------------------------------
    | FIND BY ID
    |--------------------------------------------------------------------------
    */

    public function findById(
        string $table,
        string $id
    ){

        return $this->first(
            $table,
            [
                'id' => 'eq.' . $id
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    public function insert(
        string $table,
        array $data
    ){
        $this->assertNotTenantOwned($table);

        return $this->request()

            ->post(
                $this->endpoint($table),
                $data
            )

            ->throw()

            ->json();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        string $table,
        array $filters,
        array $data
    ){
        $this->assertNotTenantOwned($table);

        $query = http_build_query(
            $filters
        );

        return $this->request()

            ->patch(
                $this->endpoint($table) . '?' . $query,
                $data
            )

            ->throw()

            ->json();
    }

    /*
    |--------------------------------------------------------------------------
    | PATCH
    |--------------------------------------------------------------------------
    */

    public function patch(
        string $table,
        array $filters,
        array $data
    ){

        return $this->update(
            $table,
            $filters,
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | POST
    |--------------------------------------------------------------------------
    */

    public function post(
        string $table,
        array $data
    ){

        return $this->insert(
            $table,
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function delete(
        string $table,
        array $filters = []
    ){
        $this->assertNotTenantOwned($table);

        $url = $this->endpoint(
            $table
        );

        if(!empty($filters)){

            $url .= '?' . http_build_query(
                $filters
            );
        }

        return $this->request()

            ->delete($url)

            ->throw()

            ->json();
    }

        /*
    |--------------------------------------------------------------------------
    | UPLOAD TO STORAGE
    |--------------------------------------------------------------------------
    */

    public function uploadToStorage(string $bucket, string $path, string $contents, string $mimeType): string
    {
        $url = $this->url . '/storage/v1/object/' . $bucket . '/' . $path;

        $response = Http::timeout(30)->connectTimeout(10)->withHeaders([
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type'  => $mimeType,
            'x-upsert'      => 'true',
        ])->withBody($contents, $mimeType)->post($url);

        if (!$response->successful()) {
            throw new \RuntimeException('Supabase Storage upload failed: ' . $response->body());
        }

        return $this->url . '/storage/v1/object/public/' . $bucket . '/' . $path;
    }

    /*
    |--------------------------------------------------------------------------
    | SAFE PATCH
    |--------------------------------------------------------------------------
    */

    public function safePatch(
        string $table,
        array $filters,
        array $data
    ): bool
    {
        try {

            $this->patch(
                $table,
                $filters,
                $data
            );

            return true;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('safePatch failed', [
                'table' => $table, 'filters' => $filters, 'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAFE INSERT
    |--------------------------------------------------------------------------
    */

    public function safeInsert(
        string $table,
        array $data
    ): bool
    {
        try {

            $this->insert(
                $table,
                $data
            );

            return true;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('safeInsert failed', [
                'table' => $table, 'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPSERT (insert or update on conflict)
    |--------------------------------------------------------------------------
    */

    public function upsert(string $table, array $data, string $onConflict = 'id'): mixed
    {
        $this->assertNotTenantOwned($table);

        return Http::timeout(15)->connectTimeout(5)->withHeaders([
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Prefer'        => 'resolution=merge-duplicates,return=representation',
        ])->post(
            $this->endpoint($table) . '?on_conflict=' . $onConflict,
            $data
        )->throw()->json();
    }

    /*
    |--------------------------------------------------------------------------
    | SAFE DELETE
    |--------------------------------------------------------------------------
    */

    public function safeDelete(
        string $table,
        array $filters
    ): bool
    {
        try {
            $this->delete(
                $table,
                $filters
            );
            return true;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('safeDelete failed', [
                'table' => $table, 'filters' => $filters, 'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
