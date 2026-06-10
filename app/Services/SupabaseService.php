<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\ApprovalActionService;

class SupabaseService
{
    protected string $url;

    protected string $key;

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
        return Http::withHeaders([

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
