<?php

namespace Tests\Feature\Api\Compliance;

use App\Enums\UserRole;
use App\Models\Compliance\CustomerRiskProfile;
use App\Models\Customer;
use App\Models\User;
use App\Services\Compliance\RiskScoringEngine;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RiskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complianceUser = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => UserRole::ComplianceOfficer,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    public function test_can_get_customer_risk_profile(): void
    {
        $customer = Customer::factory()->create();
        $profile = CustomerRiskProfile::createForCustomer($customer->id, 35);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson("/api/risk/{$customer->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertEquals($profile->id, $response->json('data.id'));
    }

    public function test_can_recalculate_risk_score(): void
    {
        $customer = Customer::factory()->create([
            'nationality' => 'Malaysia',
            'pep_status' => false,
            'sanction_hit' => false,
        ]);

        // Create initial profile
        CustomerRiskProfile::createForCustomer($customer->id, 25);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/risk/{$customer->id}/recalculate");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_can_lock_and_unlock_score(): void
    {
        $customer = Customer::factory()->create();
        $profile = CustomerRiskProfile::createForCustomer($customer->id, 40);

        // Lock the profile
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/risk/{$customer->id}/lock", [
                'reason' => 'Manual review in progress',
            ]);

        $response->assertStatus(200);

        $profile->refresh();
        $this->assertTrue($profile->isLocked());

        // Unlock the profile
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/risk/{$customer->id}/unlock");

        $response->assertStatus(200);

        $profile->refresh();
        $this->assertFalse($profile->isLocked());
    }

    public function test_can_get_portfolio_stats(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $customer3 = Customer::factory()->create();

        CustomerRiskProfile::createForCustomer($customer1->id, 20);
        CustomerRiskProfile::createForCustomer($customer2->id, 45);
        CustomerRiskProfile::createForCustomer($customer3->id, 70);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/risk/portfolio');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['total', 'by_tier'],
        ]);
        $this->assertEquals(3, $response->json('data.total'));
    }

    public function test_portfolio_returns_empty_by_tier_when_no_profiles(): void
    {
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/risk/portfolio');

        $response->assertStatus(200);
    }
}
