<?php

namespace Tests\Unit\Services\Compliance;

use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\StrReport;
use App\Models\User;
use App\Services\Compliance\Monitors\StrDeadlineMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrDeadlineMonitorTest extends TestCase
{
    use RefreshDatabase;

    private StrDeadlineMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new StrDeadlineMonitor(new MathService());
    }

    public function test_no_finding_when_flag_has_str_filed(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $flag = FlaggedTransaction::factory()->open()->create([
            'flag_type' => 'Structuring',
            'created_at' => now()->subDays(5),
            'customer_id' => $customer->id,
        ]);

        StrReport::create([
            'alert_id' => $flag->id,
            'str_no' => 'STR-' . now()->format('YmdHis'),
            'branch_id' => 1,
            'customer_id' => $customer->id,
            'transaction_ids' => [$flag->transaction_id],
            'reason' => 'Test STR',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_when_str_deadline_approaching(): void
    {
        // If today is Saturday April 11:
        // - 3 days ago = Wednesday April 8
        // - Deadline = Wednesday April 8 + 3 weekdays = Monday April 13
        // - Warning threshold = Monday April 13 - 1 weekday = Friday April 10
        // - Today Saturday April 11 is AFTER Friday April 10 (warning) but BEFORE Monday April 13 (deadline)
        $flag = FlaggedTransaction::factory()->open()->create([
            'flag_type' => 'Structuring',
            'created_at' => now()->subDays(3),
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('High', $findings[0]['severity']);
        $this->assertEquals($flag->id, $findings[0]['details']['flag_id']);
        $this->assertArrayHasKey('days_remaining', $findings[0]['details']);
    }
}