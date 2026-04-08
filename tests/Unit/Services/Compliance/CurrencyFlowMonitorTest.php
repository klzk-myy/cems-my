<?php

namespace Tests\Unit\Services\Compliance;

use App\Enums\TransactionType;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Compliance\Monitors\CurrencyFlowMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyFlowMonitorTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyFlowMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new CurrencyFlowMonitor(new MathService());
    }

    public function test_no_findings_with_single_transaction(): void
    {
        $customer = Customer::factory()->create();

        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_no_findings_without_round_trip(): void
    {
        $customer = Customer::factory()->create();

        // Buy USD then later Buy EUR - no round trip
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'created_at' => now(),
        ]);
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Buy,
            'currency_code' => 'EUR',
            'amount_foreign' => '800',
            'created_at' => now()->subHours(2),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_detects_round_trip_pattern(): void
    {
        $customer = Customer::factory()->create();

        // Sell USD then Buy USD within time window
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Sell,
            'currency_code' => 'USD',
            'amount_foreign' => '5000',
            'amount_local' => '22000',
            'created_at' => now()->subHours(2),
        ]);
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '5000',
            'amount_local' => '22000',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('Currency_Flow_Anomaly', $findings[0]['finding_type']);
        $this->assertEquals($customer->id, $findings[0]['subject_id']);
    }

    public function test_ignores_round_trip_below_threshold(): void
    {
        $customer = Customer::factory()->create();

        // Small round trip below threshold
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Sell,
            'currency_code' => 'USD',
            'amount_foreign' => '100', // Below ROUND_TRIP_THRESHOLD of 5000
            'created_at' => now()->subHours(2),
        ]);
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_ignores_round_trip_outside_time_window(): void
    {
        $customer = Customer::factory()->create();

        // Sell then Buy but beyond TIME_WINDOW_HOURS
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Sell,
            'currency_code' => 'USD',
            'amount_foreign' => '6000',
            'created_at' => now()->subHours(80), // Beyond 72 hour window
        ]);
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '6000',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }
}
