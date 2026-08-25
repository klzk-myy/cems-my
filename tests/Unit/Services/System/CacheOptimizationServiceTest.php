<?php

namespace Tests\Unit\Services\System;

use App\Services\System\CacheOptimizationService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheOptimizationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_remember_caches_value_and_tracks_miss(): void
    {
        $service = new CacheOptimizationService;
        $key = 'test_cache_key';

        $result = $service->remember($key, 3600, ['test'], fn () => 'cached_value');

        $this->assertSame('cached_value', $result);
        $this->assertSame([
            'hits' => 0,
            'misses' => 1,
        ], $service->getStats());
    }

    public function test_remember_returns_cached_value_on_hit(): void
    {
        $service = new CacheOptimizationService;
        $key = 'test_cache_key';

        $service->remember($key, 3600, ['test'], fn () => 'first_value');
        $result = $service->remember($key, 3600, ['test'], fn () => 'should_not_call');

        $this->assertSame('first_value', $result);
        $this->assertSame([
            'hits' => 1,
            'misses' => 1,
        ], $service->getStats());
    }

    public function test_reset_stats_clears_counts(): void
    {
        $service = new CacheOptimizationService;
        $service->remember('k1', 3600, ['t'], fn () => 'v');
        $service->remember('k2', 3600, ['t'], fn () => 'v2');

        $service->resetStats();

        $this->assertSame([
            'hits' => 0,
            'misses' => 0,
        ], $service->getStats());
    }

    public function test_put_stats_stores_in_cache(): void
    {
        $service = new CacheOptimizationService;
        $service->remember('k1', 3600, ['t'], fn () => 'v');

        $service->putStats(now()->addHour());

        $this->assertNotNull(Cache::get('dashboard_cache_stats'));
    }
}
