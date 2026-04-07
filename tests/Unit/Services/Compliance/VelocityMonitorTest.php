<?php

namespace Tests\Unit\Services\Compliance;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Compliance\Monitors\VelocityMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VelocityMonitorTest extends TestCase
{
    use RefreshDatabase;

    private VelocityMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new VelocityMonitor(new MathService());
    }

    public function test_no_finding_when_under_threshold(): void
    {
        $customer = Customer::factory()->create();

        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '20000',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_when_approaching_threshold(): void
    {
        $customer = Customer::factory()->create();

        // 4 transactions of RM 12,000 each = RM 48,000 (approaching threshold)
        for ($i = 0; $i < 4; $i++) {
            Transaction::factory()->create([
                'customer_id' => $customer->id,
                'amount_local' => '12000',
                'created_at' => now()->subMinutes($i * 5),
            ]);
        }

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('Medium', $findings[0]['severity']);
        $this->assertEquals($customer->id, $findings[0]['subject_id']);
        $this->assertTrue($findings[0]['details']['approaching_threshold']);
    }

    public function test_generates_high_finding_when_exceeding_threshold(): void
    {
        $customer = Customer::factory()->create();

        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '55000',
            'created_at' => now(),
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('High', $findings[0]['severity']);
        $this->assertEquals($customer->id, $findings[0]['subject_id']);
    }
}
