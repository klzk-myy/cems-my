<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\RiskScoreSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerIndexPerformanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function latest_risk_snapshot_returns_most_recent_snapshot(): void
    {
        $customer = Customer::factory()->create();
        RiskScoreSnapshot::factory()->create([
            'customer_id' => $customer->id,
            'snapshot_date' => now()->subDays(5),
            'overall_score' => 10,
        ]);
        $latest = RiskScoreSnapshot::factory()->create([
            'customer_id' => $customer->id,
            'snapshot_date' => now(),
            'overall_score' => 90,
        ]);

        $found = $customer->fresh()->latestRiskSnapshot;

        $this->assertNotNull($found);
        $this->assertEquals($latest->id, $found->id);
        $this->assertEquals(90, $found->overall_score);
    }
}
