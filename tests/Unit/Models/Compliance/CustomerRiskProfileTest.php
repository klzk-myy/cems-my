<?php

namespace Tests\Unit\Models\Compliance;

use App\Models\Compliance\CustomerRiskProfile;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRiskProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_risk_profile(): void
    {
        $customer = Customer::factory()->create();

        $profile = CustomerRiskProfile::createForCustomer($customer->id, 20);

        $this->assertDatabaseHas('customer_risk_profiles', [
            'customer_id' => $customer->id,
            'risk_score' => 20,
            'risk_tier' => 'Low',
        ]);

        $this->assertEquals(20, $profile->risk_score);
        $this->assertEquals('Low', $profile->risk_tier);
    }

    public function test_risk_tier_correct_for_score_ranges(): void
    {
        // Score 20 -> Low (0-25)
        $lowProfile = CustomerRiskProfile::createForCustomer(Customer::factory()->create()->id, 20);
        $this->assertEquals('Low', $lowProfile->risk_tier);

        // Score 40 -> Medium (26-50)
        $mediumProfile = CustomerRiskProfile::createForCustomer(Customer::factory()->create()->id, 40);
        $this->assertEquals('Medium', $mediumProfile->risk_tier);

        // Score 65 -> High (51-75)
        $highProfile = CustomerRiskProfile::createForCustomer(Customer::factory()->create()->id, 65);
        $this->assertEquals('High', $highProfile->risk_tier);

        // Score 85 -> Critical (76-100)
        $criticalProfile = CustomerRiskProfile::createForCustomer(Customer::factory()->create()->id, 85);
        $this->assertEquals('Critical', $criticalProfile->risk_tier);
    }

    public function test_can_calculate_from_factors(): void
    {
        $customer = Customer::factory()->create();

        // Factors with contributions totaling 30 -> score 50 (base 20 + 30)
        $factors = [
            'contributions' => [
                ['factor' => 'pep_status', 'value' => 10],
                ['factor' => 'sanction_hit', 'value' => 20],
            ],
        ];

        $profile = CustomerRiskProfile::createFromFactors($customer->id, $factors);

        $this->assertEquals(50, $profile->risk_score);
        $this->assertEquals('Medium', $profile->risk_tier);
    }

    public function test_score_capped_at_100(): void
    {
        $customer = Customer::factory()->create();

        // Factors summing to more than 80 -> should be capped at 100
        $factors = [
            'contributions' => [
                ['factor' => 'pep_status', 'value' => 30],
                ['factor' => 'sanction_hit', 'value' => 30],
                ['factor' => 'high_risk_country', 'value' => 30],
            ],
        ];

        $profile = CustomerRiskProfile::createFromFactors($customer->id, $factors);

        $this->assertEquals(100, $profile->risk_score);
        $this->assertEquals('Critical', $profile->risk_tier);
    }

    public function test_can_lock_and_unlock_score(): void
    {
        $customer = Customer::factory()->create();
        $userId = 1;

        $profile = CustomerRiskProfile::createForCustomer($customer->id, 50);

        // Lock the profile
        $profile->lock($userId, 'Pending review');

        $this->assertTrue($profile->isLocked());
        $this->assertNotNull($profile->locked_until);
        $this->assertEquals($userId, $profile->locked_by);
        $this->assertEquals('Pending review', $profile->lock_reason);

        // Unlock the profile
        $profile->unlock();

        $this->assertFalse($profile->isLocked());
        $this->assertNull($profile->locked_until);
        $this->assertNull($profile->locked_by);
        $this->assertNull($profile->lock_reason);
    }

    public function test_is_locked_returns_true_when_within_lock_period(): void
    {
        $customer = Customer::factory()->create();

        $profile = CustomerRiskProfile::createForCustomer($customer->id, 50);

        // Lock until 1 hour from now
        $profile->lock(1, 'Test lock');
        $profile->locked_until = now()->addHour();
        $profile->save();

        $this->assertTrue($profile->isLocked());

        // Lock until 1 hour ago (expired)
        $profile->locked_until = now()->subHour();
        $profile->save();

        $this->assertFalse($profile->isLocked());
    }
}
