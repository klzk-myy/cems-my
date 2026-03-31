<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use Tests\TestCase;

class CurrencyPositionServiceTest extends TestCase
{
    protected CurrencyPositionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyPositionService(new MathService());

        // Create test currency
        Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ]);
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

        // Expected: 1500 USD @ avg 4.566667
        $this->assertEquals('1500.0000', $position->balance);
        $this->assertEquals('4.566667', $position->avg_cost_rate);
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
}
