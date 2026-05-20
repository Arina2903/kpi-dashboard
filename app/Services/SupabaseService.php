<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(env('SUPABASE_URL'), '/');
        $this->key = env('SUPABASE_SERVICE_ROLE_KEY');
    }

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

    public function get(string $table, array $query = [])
    {
        return $this->request()
            ->get($this->url . '/rest/v1/' . $table, $query)
            ->throw()
            ->json();
    }

    public function insert(string $table, array $data)
    {
        return $this->request()
            ->post($this->url . '/rest/v1/' . $table, $data)
            ->throw()
            ->json();
    }

    public function update(string $table, array $filters, array $data)
    {
        $query = http_build_query($filters);

        return $this->request()
            ->patch($this->url . '/rest/v1/' . $table . '?' . $query, $data)
            ->throw()
            ->json();
    }

    public function patch(string $table, string $id, array $data)
    {
        return $this->update($table, [
            'id' => 'eq.' . $id,
        ], $data);
    }

    public function delete(string $table, array $query = [])
    {
        $url = "{$this->url}/rest/v1/{$table}";

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->delete($url);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }
}
