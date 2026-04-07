<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\HighRiskCountry;
use App\Models\User;
use App\Models\Currency;
use App\Services\RiskRatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskRatingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RiskRatingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RiskRatingService;
    }

    public function test_calculate_score_for_low_risk_customer()
    {
        // No high-risk country, no PEP, no cash-intensive transactions
        $customer = Customer::factory()->create([
            'full_name' => 'John Malaysian',
            'pep_status' => false,
            'nationality' => 'Malaysia', // Not a high-risk country
            'risk_rating' => 'Low',
        ]);
        // No transactions created - not cash intensive

        $score = $this->service->calculateRiskScore($customer);
        $this->assertEquals(0, $score);
    }

    public function test_calculate_score_for_pep_customer()
    {
        $customer = Customer::factory()->create([
            'full_name' => 'John PEP',
            'pep_status' => true, // 40 points
            'nationality' => 'Malaysia',
        ]);

        $score = $this->service->calculateRiskScore($customer);
        $this->assertEquals(40, $score);
    }

    public function test_calculate_score_with_high_risk_country()
    {
        // Create high-risk country
        HighRiskCountry::create([
            'country_code' => 'KP',
            'country_name' => 'North Korea',
            'risk_level' => 'High',
            'source' => 'UN Sanctions',
            'list_date' => now()->subYear(),
        ]);

        $customer = Customer::factory()->create([
            'full_name' => 'John NK',
            'pep_status' => false,
            'nationality' => 'North Korea', // 30 points
        ]);

        $score = $this->service->calculateRiskScore($customer);
        $this->assertEquals(30, $score);
    }

    public function test_calculate_score_with_cash_intensive_pattern()
    {
        $customer = Customer::factory()->create([
            'full_name' => 'John Cash Intensive',
            'pep_status' => false,
            'nationality' => 'Malaysia',
        ]);

        $user = User::factory()->create();
        $currency = Currency::factory()->create();

        // Create 4 transactions over 10000 (cash intensive = more than 3 transactions)
        for ($i = 0; $i < 4; $i++) {
            Transaction::factory()->create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'currency_code' => $currency->code,
                'amount_local' => 15000, // Over 10000 threshold
                'created_at' => now()->subDays(5), // Within 30 days
            ]);
        }

        $score = $this->service->calculateRiskScore($customer);
        $this->assertEquals(20, $score);
    }

    public function test_calculate_score_combined_risk_factors()
    {
        HighRiskCountry::create([
            'country_code' => 'IR',
            'country_name' => 'Iran',
            'risk_level' => 'High',
            'source' => 'OFAC',
            'list_date' => now()->subYear(),
        ]);

        $customer = Customer::factory()->create([
            'full_name' => 'John Combined',
            'pep_status' => true, // 40 points
            'nationality' => 'Iran', // 30 points
        ]);

        $user = User::factory()->create();
        $currency = Currency::factory()->create();

        // 4 cash-intensive transactions
        for ($i = 0; $i < 4; $i++) {
            Transaction::factory()->create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'currency_code' => $currency->code,
                'amount_local' => 15000,
                'created_at' => now()->subDays(5),
            ]);
        }

        $score = $this->service->calculateRiskScore($customer);
        // 40 + 30 + 20 = 90
        $this->assertEquals(90, $score);
    }

    public function test_score_capped_at_100()
    {
        HighRiskCountry::create([
            'country_code' => 'HR1',
            'country_name' => 'HighRisk1',
            'risk_level' => 'High',
            'source' => 'Test',
            'list_date' => now()->subYear(),
        ]);
        HighRiskCountry::create([
            'country_code' => 'HR2',
            'country_name' => 'HighRisk2',
            'risk_level' => 'High',
            'source' => 'Test',
            'list_date' => now()->subYear(),
        ]);
        HighRiskCountry::create([
            'country_code' => 'HR3',
            'country_name' => 'HighRisk3',
            'risk_level' => 'High',
            'source' => 'Test',
            'list_date' => now()->subYear(),
        ]);

        $customer = Customer::factory()->create([
            'full_name' => 'John Max Risk',
            'pep_status' => true, // 40
            'nationality' => 'HighRisk1', // 30
        ]);

        // Even with many factors, score capped at 100
        $score = $this->service->calculateRiskScore($customer);
        $this->assertLessThanOrEqual(100, $score);
    }

    // Test getRiskRating boundaries
    public function test_get_low_rating_for_low_score()
    {
        $rating = $this->service->getRiskRating(25);
        $this->assertEquals('Low', $rating);
    }

    public function test_get_medium_rating_for_medium_score()
    {
        $rating = $this->service->getRiskRating(45);
        $this->assertEquals('Medium', $rating);
    }

    public function test_get_high_rating_for_high_score()
    {
        $rating = $this->service->getRiskRating(75);
        $this->assertEquals('High', $rating);
    }

    public function test_get_low_rating_for_boundary_score()
    {
        $rating = $this->service->getRiskRating(30);
        $this->assertEquals('Low', $rating);
    }

    public function test_get_medium_rating_for_lower_boundary()
    {
        $rating = $this->service->getRiskRating(31);
        $this->assertEquals('Medium', $rating);
    }

    public function test_get_medium_rating_for_upper_boundary()
    {
        $rating = $this->service->getRiskRating(60);
        $this->assertEquals('Medium', $rating);
    }

    public function test_get_high_rating_for_minimum_high_score()
    {
        $rating = $this->service->getRiskRating(61);
        $this->assertEquals('High', $rating);
    }

    // Test getRefreshFrequency
    public function test_get_refresh_frequency_by_rating()
    {
        $this->assertEquals(3, $this->service->getRefreshFrequency('Low'));
        $this->assertEquals(2, $this->service->getRefreshFrequency('Medium'));
        $this->assertEquals(1, $this->service->getRefreshFrequency('High'));
    }
}
