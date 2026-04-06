<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRateHistory;
use App\Services\RateApiService;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RateHistoryLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CurrencyFactory::resetCounter();
        Cache::flush();
    }

    public function test_rate_fetch_logs_to_history_table()
    {
        // The RateApiService logs rates for these currencies: USD, EUR, GBP, SGD, AUD, CAD, CHF, JPY
        // We need to create all these currencies in the database
        $currencies = ['USD', 'EUR', 'GBP', 'SGD', 'AUD', 'CAD', 'CHF', 'JPY'];
        foreach ($currencies as $code) {
            Currency::firstOrCreate(
                ['code' => $code],
                ['name' => $code, 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
            );
        }

        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 0.212,
                    'EUR' => 0.195,
                    'GBP' => 0.168,
                    'SGD' => 0.300,
                    'AUD' => 0.150,
                    'CAD' => 0.160,
                    'CHF' => 0.180,
                    'JPY' => 0.003,
                ],
                'time_last_updated' => time(),
            ], 200),
        ]);

        $service = new RateApiService;
        $service->fetchLatestRates();

        // Verify history entries were created for processed currencies
        $this->assertDatabaseHas('exchange_rate_histories', [
            'currency_code' => 'USD',
        ]);
        $this->assertDatabaseHas('exchange_rate_histories', [
            'currency_code' => 'EUR',
        ]);
        $this->assertDatabaseHas('exchange_rate_histories', [
            'currency_code' => 'GBP',
        ]);
    }

    public function test_rate_fetch_only_logs_once_per_day()
    {
        Currency::factory()->create(['code' => 'DLY']);

        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['DLY' => 0.212],
                'time_last_updated' => time(),
            ], 200),
        ]);

        $service = new RateApiService;

        // First fetch should create history
        $service->fetchLatestRates();

        $initialCount = ExchangeRateHistory::count();

        // Clear cache and fetch again
        Cache::flush();
        $service->fetchLatestRates();

        // Should not create duplicate entry for same day
        $this->assertEquals($initialCount, ExchangeRateHistory::count());
    }

    public function test_get_rate_trend_returns_correct_data()
    {
        Currency::factory()->create(['code' => 'TRD']);

        // Create historical entries
        for ($i = 30; $i >= 0; $i--) {
            ExchangeRateHistory::create([
                'currency_code' => 'TRD',
                'rate' => 4.500000 + ($i * 0.010000),
                'effective_date' => now()->subDays($i),
            ]);
        }

        $service = new RateApiService;
        $trend = $service->getRateTrend('TRD', 30);

        $this->assertArrayHasKey('currency', $trend);
        $this->assertArrayHasKey('days', $trend);
        $this->assertArrayHasKey('data', $trend);
        $this->assertArrayHasKey('trend', $trend);

        $this->assertEquals('TRD', $trend['currency']);
        $this->assertEquals(30, $trend['days']);
        $this->assertGreaterThanOrEqual(1, count($trend['data']));

        $this->assertArrayHasKey('start_rate', $trend['trend']);
        $this->assertArrayHasKey('end_rate', $trend['trend']);
        $this->assertArrayHasKey('change', $trend['trend']);
        $this->assertArrayHasKey('percent_change', $trend['trend']);
        $this->assertArrayHasKey('direction', $trend['trend']);
    }

    public function test_get_rate_trend_returns_empty_data_for_no_history()
    {
        $service = new RateApiService;
        $trend = $service->getRateTrend('XYZ', 30);

        $this->assertEquals('XYZ', $trend['currency']);
        $this->assertEquals(30, $trend['days']);
        $this->assertEmpty($trend['data']);
        $this->assertNull($trend['trend']);
    }
}
