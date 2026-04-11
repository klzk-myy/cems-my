<?php

namespace Tests\Unit\Services\Compliance;

use App\Enums\ComplianceFlagType;
use App\Enums\FlagStatus;
use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\Transaction;
use App\Services\Compliance\Monitors\CounterfeitAlertMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterfeitAlertMonitorTest extends TestCase
{
    use RefreshDatabase;

    private CounterfeitAlertMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new CounterfeitAlertMonitor(new MathService);
    }

    public function test_no_findings_when_no_counterfeit_flags(): void
    {
        $customer = Customer::factory()->create();

        FlaggedTransaction::factory()->create([
            'customer_id' => $customer->id,
            'flag_type' => ComplianceFlagType::Velocity,
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_no_findings_with_old_counterfeit_flags(): void
    {
        $customer = Customer::factory()->create();

        FlaggedTransaction::factory()->create([
            'customer_id' => $customer->id,
            'flag_type' => ComplianceFlagType::CounterfeitCurrency,
            'created_at' => now()->subDays(60), // Beyond 30 day lookback
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_for_open_counterfeit_flag(): void
    {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '5000',
            'currency_code' => 'USD',
        ]);

        FlaggedTransaction::factory()->create([
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'flag_type' => ComplianceFlagType::CounterfeitCurrency,
            'status' => FlagStatus::Open,
            'created_at' => now()->subDays(5),
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('Counterfeit_Alert', $findings[0]['finding_type']);
        $this->assertEquals('Critical', $findings[0]['severity']);
        $this->assertEquals($transaction->id, $findings[0]['subject_id']);
    }

    public function test_generates_finding_for_under_review_counterfeit_flag(): void
    {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
        ]);

        FlaggedTransaction::factory()->create([
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'flag_type' => ComplianceFlagType::CounterfeitCurrency,
            'status' => FlagStatus::UnderReview,
            'created_at' => now()->subDays(10),
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
    }

    public function test_ignores_resolved_counterfeit_flags(): void
    {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
        ]);

        FlaggedTransaction::factory()->create([
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'flag_type' => ComplianceFlagType::CounterfeitCurrency,
            'status' => FlagStatus::Resolved,
            'created_at' => now()->subDays(5),
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }
}
