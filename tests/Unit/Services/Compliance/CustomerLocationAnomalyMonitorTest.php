<?php

namespace Tests\Unit\Services\Compliance;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Compliance\Monitors\CustomerLocationAnomalyMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLocationAnomalyMonitorTest extends TestCase
{
    use RefreshDatabase;

    private CustomerLocationAnomalyMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new CustomerLocationAnomalyMonitor(new MathService());
    }

    public function test_no_findings_for_malaysian_customers(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Malaysian',
        ]);

        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '15000',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_no_findings_for_foreign_customers_with_no_transactions(): void
    {
        Customer::factory()->create([
            'nationality' => 'Singaporean',
            'is_active' => true,
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_no_findings_for_foreign_customers_with_low_value_transactions(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Singaporean',
        ]);

        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '5000', // Below HIGH_VALUE_THRESHOLD of 10000
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_for_multiple_currencies(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Singaporean',
        ]);

        // Multiple transactions in different currencies
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'USD',
            'amount_local' => '15000',
            'created_at' => now(),
        ]);
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'EUR',
            'amount_local' => '12000',
            'created_at' => now()->subHours(2),
        ]);
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'GBP',
            'amount_local' => '11000',
            'created_at' => now()->subHours(4),
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('Low', $findings[0]['severity']);
        $this->assertEquals($customer->id, $findings[0]['subject_id']);
        $this->assertEquals('Location_Anomaly', $findings[0]['finding_type']);
    }

    public function test_generates_finding_for_high_transaction_frequency(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Singaporean',
        ]);

        // 5+ high-value transactions
        for ($i = 0; $i < 5; $i++) {
            Transaction::factory()->create([
                'customer_id' => $customer->id,
                'amount_local' => '15000',
                'created_at' => now()->subMinutes($i * 10),
            ]);
        }

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals(5, $findings[0]['details']['transaction_count']);
    }
}
