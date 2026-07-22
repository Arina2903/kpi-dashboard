<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupabaseServicePerformanceTest extends TestCase
{
    public function test_get_many_fetches_multiple_tables_and_returns_them_keyed(): void
    {
        Http::fake([
            '*/rest/v1/kpis*'      => Http::response([['id' => 'k1']], 200),
            '*/rest/v1/employees*' => Http::response([['id' => 'e1']], 200),
        ]);

        $service = new SupabaseService();

        $results = $service->getMany([
            'kpis'      => ['table' => 'kpis', 'query' => ['select' => '*']],
            'employees' => ['table' => 'employees', 'query' => ['select' => '*']],
        ]);

        $this->assertSame([['id' => 'k1']], $results['kpis']);
        $this->assertSame([['id' => 'e1']], $results['employees']);

        Http::assertSentCount(2);
    }

    public function test_get_many_returns_empty_array_for_empty_input(): void
    {
        $service = new SupabaseService();

        $this->assertSame([], $service->getMany([]));
    }

    public function test_departments_are_cached_so_a_second_call_does_not_hit_the_network(): void
    {
        Cache::flush();

        Http::fake([
            '*/rest/v1/departments*' => Http::response([['code' => 'OPERATION', 'name' => 'Operation']], 200),
        ]);

        $service = new SupabaseService();
        $query   = ['code' => 'eq.OPERATION', 'select' => '*'];

        $first  = $service->get('departments', $query);
        $second = $service->get('departments', $query);

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_kpis_are_never_cached_so_every_call_hits_the_network(): void
    {
        Cache::flush();

        Http::fake([
            '*/rest/v1/kpis*' => Http::response([['id' => 'k1']], 200),
        ]);

        $service = new SupabaseService();
        $query   = ['company_code' => 'eq.RCG', 'select' => '*'];

        $service->get('kpis', $query);
        $service->get('kpis', $query);

        Http::assertSentCount(2);
    }

    public function test_different_department_filters_do_not_share_a_cache_entry(): void
    {
        Cache::flush();

        Http::fake([
            '*/rest/v1/departments*' => Http::sequence()
                ->push([['code' => 'OPERATION']], 200)
                ->push([['code' => 'MARKETING']], 200),
        ]);

        $service = new SupabaseService();

        $a = $service->get('departments', ['code' => 'eq.OPERATION', 'select' => '*']);
        $b = $service->get('departments', ['code' => 'eq.MARKETING', 'select' => '*']);

        $this->assertNotSame($a, $b);
        Http::assertSentCount(2);
    }
}
