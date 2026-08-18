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
            '*/rest/v1/kpi_templates*' => Http::response([['id' => 'k1']], 200),
            '*/rest/v1/employees*'     => Http::response([['id' => 'e1']], 200),
        ]);

        $service = new SupabaseService();

        $results = $service->getMany([
            'kpi_templates' => ['table' => 'kpi_templates', 'query' => ['select' => '*']],
            'employees'     => ['table' => 'employees', 'query' => ['select' => '*']],
        ]);

        $this->assertSame([['id' => 'k1']], $results['kpi_templates']);
        $this->assertSame([['id' => 'e1']], $results['employees']);

        Http::assertSentCount(2);
    }

    public function test_get_many_returns_empty_array_for_empty_input(): void
    {
        $service = new SupabaseService();

        $this->assertSame([], $service->getMany([]));
    }

    public function test_companies_are_cached_so_a_second_call_does_not_hit_the_network(): void
    {
        Cache::flush();

        Http::fake([
            '*/rest/v1/companies*' => Http::response([['code' => 'OPERATION', 'name' => 'Operation']], 200),
        ]);

        $service = new SupabaseService();
        $query   = ['code' => 'eq.OPERATION', 'select' => '*'];

        $first  = $service->get('companies', $query);
        $second = $service->get('companies', $query);

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_kpi_templates_are_never_cached_so_every_call_hits_the_network(): void
    {
        Cache::flush();

        Http::fake([
            '*/rest/v1/kpi_templates*' => Http::response([['id' => 'k1']], 200),
        ]);

        $service = new SupabaseService();
        $query   = ['status' => 'eq.active', 'select' => '*'];

        $service->get('kpi_templates', $query);
        $service->get('kpi_templates', $query);

        Http::assertSentCount(2);
    }

    public function test_different_company_filters_do_not_share_a_cache_entry(): void
    {
        Cache::flush();

        Http::fake([
            '*/rest/v1/companies*' => Http::sequence()
                ->push([['code' => 'OPERATION']], 200)
                ->push([['code' => 'MARKETING']], 200),
        ]);

        $service = new SupabaseService();

        $a = $service->get('companies', ['code' => 'eq.OPERATION', 'select' => '*']);
        $b = $service->get('companies', ['code' => 'eq.MARKETING', 'select' => '*']);

        $this->assertNotSame($a, $b);
        Http::assertSentCount(2);
    }
}
