<?php

namespace Tests\Unit;

use App\Events\RiskScoreUpdated;
use App\Listeners\ComplianceEventListener;
use App\Models\Alert;
use App\Models\Customer;
use App\Models\RiskScoreSnapshot;
use App\Services\CustomerRiskScoringService;
use App\Services\EddTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceEventListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_risk_score_updated_logs_to_audit(): void
    {
        $customer = Customer::factory()->create();
        $snapshot = RiskScoreSnapshot::create([
            'customer_id' => $customer->id,
            'previous_score' => 30,
            'previous_rating' => 'low_risk',
            'overall_score' => 55,
            'overall_rating_label' => 'medium_risk',
            'snapshot_date' => now()->toDateString(),
            'next_screening_date' => now()->addMonth(),
        ]);

        $listener = new ComplianceEventListener(
            app(CustomerRiskScoringService::class),
            app(EddTemplateService::class)
        );

        $listener->handleRiskScoreUpdated(new RiskScoreUpdated($snapshot));

        $this->assertDatabaseHas('system_logs', [
            'action' => 'risk_score_updated',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_handle_risk_score_updated_creates_alert_when_escalating_to_high(): void
    {
        $customer = Customer::factory()->create();
        $snapshot = RiskScoreSnapshot::create([
            'customer_id' => $customer->id,
            'previous_score' => 40,
            'previous_rating' => 'medium_risk',
            'overall_score' => 75,
            'overall_rating_label' => 'high_risk',
            'snapshot_date' => now()->toDateString(),
            'next_screening_date' => now()->addMonth(),
        ]);

        $listener = new ComplianceEventListener(
            app(CustomerRiskScoringService::class),
            app(EddTemplateService::class)
        );

        $listener->handleRiskScoreUpdated(new RiskScoreUpdated($snapshot));

        $this->assertDatabaseHas('alerts', [
            'customer_id' => $customer->id,
            'type' => 'Risk_Score_Escalation',
            'status' => 'Open',
        ]);
    }

    public function test_handle_risk_score_updated_does_not_alert_when_already_high(): void
    {
        $customer = Customer::factory()->create();
        $snapshot = RiskScoreSnapshot::create([
            'customer_id' => $customer->id,
            'previous_score' => 75,
            'previous_rating' => 'high_risk',
            'overall_score' => 85,
            'overall_rating_label' => 'critical_risk',
            'snapshot_date' => now()->toDateString(),
            'next_screening_date' => now()->addMonth(),
        ]);

        $listener = new ComplianceEventListener(
            app(CustomerRiskScoringService::class),
            app(EddTemplateService::class)
        );

        $listener->handleRiskScoreUpdated(new RiskScoreUpdated($snapshot));

        $this->assertDatabaseMissing('alerts', [
            'customer_id' => $customer->id,
            'type' => 'risk_score_escalation',
        ]);
    }
}
