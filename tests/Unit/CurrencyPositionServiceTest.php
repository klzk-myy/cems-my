<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyPositionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CurrencyPositionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyPositionService(new MathService);

        // Create test currency using firstOrCreate to avoid duplicates
        Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
        );
    }

    public function test_creates_position_on_first_buy()
    {
        $position = $this->service->updatePosition('USD', '1000', '4.50', 'Buy');
        $this->assertEquals('1000.0000', $position->balance);
        $this->assertEquals('4.500000', $position->avg_cost_rate);
    }

    public function test_updates_position_on_additional_buy()
    {
        // First buy: 1000 USD @ 4.50
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');

        // Second buy: 500 USD @ 4.70
        $position = $this->service->updatePosition('USD', '500', '4.70', 'Buy');

        // Expected: 1500 USD @ avg 4.566666
        $this->assertEquals('1500.0000', $position->balance);
        $this->assertEqualsWithDelta(4.566666, (float) $position->avg_cost_rate, 0.00001);
    }

    public function test_decreases_position_on_sell()
    {
        // Setup: 1000 USD
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');

        // Sell 300 USD
        $position = $this->service->updatePosition('USD', '300', '4.70', 'Sell');

        // Expected: 700 USD, avg cost unchanged
        $this->assertEquals('700.0000', $position->balance);
        $this->assertEquals('4.500000', $position->avg_cost_rate);
    }

    public function test_gets_position_by_currency()
    {
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');
        $position = $this->service->getPosition('USD');
        $this->assertNotNull($position);
        $this->assertEquals('USD', $position->currency_code);
    }

    public function test_throws_exception_when_selling_more_than_balance()
    {
        // Setup: 1000 USD
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');

        // Try to sell 1500 USD (more than balance)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->service->updatePosition('USD', '1500', '4.70', 'Sell');
    }

    public function test_throws_exception_when_selling_with_zero_balance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot sell: Position is empty or negative');

        $this->service->updatePosition('USD', '100', '4.50', 'Sell');
    }

    public function test_throws_exception_when_selling_exact_balance()
    {
        // Setup: 500 USD
        $this->service->updatePosition('USD', '500', '4.50', 'Buy');

        // Sell exactly 500 USD - should succeed
        $position = $this->service->updatePosition('USD', '500', '4.70', 'Sell');
        $this->assertEquals('0.0000', $position->balance);
    }

    public function test_position_balance_never_negative()
    {
        // Setup: 1000 USD
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');

        // Try to sell 2000 USD
        try {
            $this->service->updatePosition('USD', '2000', '4.70', 'Sell');
        } catch (\InvalidArgumentException $e) {
            // Expected exception
        }

        // Verify position still has positive balance
        $position = CurrencyPosition::where('currency_code', 'USD')->first();
        $this->assertNotNull($position);
        $this->assertGreaterThanOrEqual(0, (float) $position->balance);
    }

    public function test_balance_prevention_error_message_includes_available_amount()
    {
        // Setup: 500 USD
        $this->service->updatePosition('USD', '500', '4.50', 'Buy');

        // Try to sell 1000 USD
        try {
            $this->service->updatePosition('USD', '1000', '4.70', 'Sell');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('500', $e->getMessage());
            $this->assertStringContainsString('1000', $e->getMessage());
        }
    }

    public function test_allows_partial_sell_within_balance()
    {
        // Setup: 1000 USD
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');

        // Sell 300 USD
        $position = $this->service->updatePosition('USD', '300', '4.70', 'Sell');

        $this->assertEquals('700.0000', $position->balance);
    }

    public function test_multiple_sells_cannot_exceed_total_balance()
    {
        // Setup: 1000 USD
        $this->service->updatePosition('USD', '1000', '4.50', 'Buy');

        // First sell: 400 USD
        $this->service->updatePosition('USD', '400', '4.70', 'Sell');

        // Second sell: 400 USD
        $this->service->updatePosition('USD', '400', '4.70', 'Sell');

        // Third sell: 300 USD (would exceed remaining 200)
        $this->expectException(\InvalidArgumentException::class);
        $this->service->updatePosition('USD', '300', '4.70', 'Sell');
    }
}
