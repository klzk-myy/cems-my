<?php

namespace Tests\Unit\Services\Compliance;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Compliance\Monitors\StructuringMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuringMonitorTest extends TestCase
{
    use RefreshDatabase;

    private StructuringMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new StructuringMonitor(new MathService);
    }

    public function test_no_finding_when_no_structuring_pattern(): void
    {
        $customer = Customer::factory()->create();

        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '5000',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_when_structuring_detected(): void
    {
        $customer = Customer::factory()->create();

        // 3 transactions of RM 2,800 each within 1 hour
        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'customer_id' => $customer->id,
                'amount_local' => '2800',
                'created_at' => now()->subMinutes(60 - ($i * 15)),
            ]);
        }

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('High', $findings[0]['severity']);
        $this->assertEquals($customer->id, $findings[0]['subject_id']);
        $this->assertEquals(3, $findings[0]['details']['transaction_count']);
        $this->assertCount(3, $findings[0]['details']['transaction_ids']);
    }
}
