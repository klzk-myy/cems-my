<?php

namespace Tests\Unit;

use App\Services\RateApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RateApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RateApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateApiService;
        Cache::flush();
    }

    public function test_fetches_and_caches_rates()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 0.212,
                    'EUR' => 0.195,
                    'GBP' => 0.168,
                ],
                'time_last_updated' => time(),
            ], 200),
        ]);

        $rates = $this->service->fetchLatestRates();

        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('buy', $rates['USD']);
        $this->assertArrayHasKey('sell', $rates['USD']);
        $this->assertGreaterThan($rates['USD']['buy'], $rates['USD']['sell']);

        // Verify caching
        $this->assertTrue(Cache::has('exchange_rates'));
    }

    public function test_gets_rate_for_specific_currency()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['USD' => 0.212],
                'time_last_updated' => time(),
            ], 200),
        ]);

        $rate = $this->service->getRateForCurrency('USD');

        $this->assertIsArray($rate);
        $this->assertArrayHasKey('buy', $rate);
        $this->assertArrayHasKey('sell', $rate);
    }

    public function test_returns_null_for_unknown_currency()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['USD' => 0.212],
                'time_last_updated' => time(),
            ], 200),
        ]);

        $rate = $this->service->getRateForCurrency('XYZ');

        $this->assertNull($rate);
    }

    public function test_throws_exception_on_api_failure()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response('Error', 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch exchange rates');

        $this->service->fetchLatestRates();
    }
}
