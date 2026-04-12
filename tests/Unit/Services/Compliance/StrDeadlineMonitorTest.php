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
        $this->monitor = new StrDeadlineMonitor(new MathService);
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
            'str_no' => 'STR-'.now()->format('YmdHis'),
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
        // Use fixed dates to ensure reliable weekday calculations
        // Set "now" to Wednesday, flag created Monday (2 weekdays ago)
        // Deadline = Monday + 3 weekdays = Thursday
        // Warning starts = Thursday - 1 weekday = Wednesday (today)
        \Carbon\Carbon::setTestNow('2026-04-15'); // Wednesday
        $flagCreatedAt = '2026-04-13'; // Monday

        $flag = FlaggedTransaction::factory()->open()->create([
            'flag_type' => 'Structuring',
            'created_at' => $flagCreatedAt,
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('High', $findings[0]['severity']);
        $this->assertEquals($flag->id, $findings[0]['details']['flag_id']);
        $this->assertArrayHasKey('days_remaining', $findings[0]['details']);

        \Carbon\Carbon::setTestNow(null); // Reset
    }
}
