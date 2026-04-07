<?php

namespace Tests\Unit;

use App\Services\MathService;
use Tests\TestCase;

class MathServiceTest extends TestCase
{
    protected MathService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MathService;
    }

    public function test_basic_arithmetic_operations()
    {
        $this->assertEquals('5.000000', $this->service->add('2', '3'));
        $this->assertEquals('3.000000', $this->service->subtract('5', '2'));
        $this->assertEquals('6.000000', $this->service->multiply('2', '3'));
        $this->assertEquals('2.500000', $this->service->divide('5', '2'));
    }

    public function test_calculate_average_cost()
    {
        // Old: 1000 USD @ 4.50 = 4500 MYR cost
        // New: 500 USD @ 4.70 = 2350 MYR cost
        // Total: 1500 USD @ avg 4.566666 (truncated to 6 decimal places)
        // Note: BCMath truncates rather than rounds, so 4.56666666... becomes 4.566666
        $result = $this->service->calculateAverageCost(
            '1000', // old balance
            '4.50', // old avg cost
            '500', // transaction amount
            '4.70' // transaction rate
        );
        $this->assertEquals('4.566666', $result);
    }

    public function test_calculate_revaluation_pnl()
    {
        // Position: 1000 USD
        // Old rate: 4.50, New rate: 4.70
        // Gain: 1000 * (4.70 - 4.50) = 200 MYR
        $result = $this->service->calculateRevaluationPnl('1000', '4.50', '4.70');
        $this->assertEquals('200.000000', $result);
    }

    public function test_calculate_transaction_amount()
    {
        // Buy 100 USD @ 4.70 = 470 MYR
        $result = $this->service->calculateTransactionAmount('100', '4.70');
        $this->assertEquals('470.000000', $result);
    }

    public function test_compare_values()
    {
        $this->assertEquals(1, $this->service->compare('5', '3'));
        $this->assertEquals(-1, $this->service->compare('3', '5'));
        $this->assertEquals(0, $this->service->compare('5', '5'));
    }

    public function test_division_by_zero_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Division by zero');
        $this->service->divide('10', '0');
    }
}
