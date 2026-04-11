<?php

namespace Tests\Unit\Services\Compliance;

use App\Models\Compliance\CustomerRiskProfile;
use App\Models\Customer;
use App\Services\Compliance\RiskScoringEngine;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskScoringEngineTest extends TestCase
{
    use RefreshDatabase;

    private RiskScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RiskScoringEngine(new MathService);
    }

    public function test_calculates_base_score_for_new_customer(): void
    {
        // Create a new customer with no risk factors
        $customer = Customer::factory()->create([
            'nationality' => 'Malaysia',
            'pep_status' => false,
            'sanction_hit' => false,
        ]);

        // Should get base score of 20 for new customer
        $score = $this->engine->calculateScore($customer->id);

        $this->assertEquals(20, $score);
    }

    public function test_returns_factors_with_contributions(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Malaysia',
            'pep_status' => false,
            'sanction_hit' => false,
        ]);

        $result = $this->engine->calculateScoreWithFactors($customer->id);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('tier', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertIsArray($result['factors']);
    }

    public function test_updates_or_creates_profile(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Malaysia',
            'pep_status' => false,
            'sanction_hit' => false,
        ]);

        // First recalculation should create profile
        $profile = $this->engine->recalculateForCustomer($customer->id);

        $this->assertInstanceOf(CustomerRiskProfile::class, $profile);
        $this->assertEquals($customer->id, $profile->customer_id);
        $this->assertEquals(20, $profile->risk_score);
        $this->assertEquals('Low', $profile->risk_tier);

        // Second recalculation should update existing profile
        $updatedProfile = $this->engine->recalculateForCustomer($customer->id);

        $this->assertEquals($profile->id, $updatedProfile->id);
        $this->assertEquals(20, $updatedProfile->risk_score);
    }

    public function test_respects_locked_score(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Malaysia',
            'pep_status' => false,
            'sanction_hit' => false,
        ]);

        // Create and lock a profile
        $profile = CustomerRiskProfile::createForCustomer($customer->id, 50);
        $profile->lock(1, 'Manual review in progress');

        // Recalculation should return locked profile unchanged
        $result = $this->engine->recalculateForCustomer($customer->id);

        $this->assertEquals(50, $result->risk_score);
        $this->assertEquals('Medium', $result->risk_tier);
    }
}
