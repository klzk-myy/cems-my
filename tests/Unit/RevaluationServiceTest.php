<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\AuditService;
use App\Services\MathService;
use App\Services\RateApiService;
use App\Services\RevaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RevaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create needed currencies
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'GBP'], ['name' => 'British Pound', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'SGD'], ['name' => 'Singapore Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'AUD'], ['name' => 'Australian Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'CAD'], ['name' => 'Canadian Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'CHF'], ['name' => 'Swiss Franc', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'JPY'], ['name' => 'Japanese Yen', 'is_active' => true]);

        $mathService = new MathService;
        $rateApiService = new RateApiService;
        $accountingService = new AccountingService($mathService);
        $auditService = new AuditService;
        $this->service = new RevaluationService($mathService, $rateApiService, $accountingService, $auditService);

        // Seed currency
        Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'is_active' => true]
        );

        // Seed exchange rate
        ExchangeRate::create([
            'currency_code' => 'USD',
            'rate_buy' => 4.5000,
            'rate_sell' => 4.5200,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        // Seed position
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => 1000.0000,
            'avg_cost_rate' => 4.4000,
        ]);
    }

    public function test_run_revaluation_processes_all_positions()
    {
        $user = User::factory()->create();

        $result = $this->service->runRevaluation($user->id);

        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('till_id', $result);
        $this->assertArrayHasKey('positions_revalued', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertIsArray($result['entries']);
    }

    public function test_run_revaluation_returns_correct_structure()
    {
        $user = User::factory()->create();

        $result = $this->service->runRevaluation($user->id);

        $this->assertEquals('MAIN', $result['till_id']);
        $this->assertGreaterThanOrEqual(0, $result['positions_revalued']);
    }

    public function test_run_revaluation_with_specific_till()
    {
        $user = User::factory()->create();
        CurrencyPosition::create([
            'currency_code' => 'EUR',
            'till_id' => 'TILL_02',
            'balance' => 500.0000,
            'avg_cost_rate' => 5.0000,
        ]);

        $result = $this->service->runRevaluation($user->id, 'TILL_02');

        $this->assertEquals('TILL_02', $result['till_id']);
    }
}
