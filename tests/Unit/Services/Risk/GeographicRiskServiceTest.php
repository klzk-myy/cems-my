<?php

namespace Tests\Unit\Services\Risk;

use App\Models\Customer;
use App\Models\HighRiskCountry;
use App\Services\Risk\GeographicRiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeographicRiskServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeographicRiskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GeographicRiskService::class);
    }

    #[Test]
    public function is_high_risk_country_returns_true_for_listed_country(): void
    {
        HighRiskCountry::factory()->create(['country_code' => 'XX', 'risk_level' => 'High']);

        $this->assertTrue($this->service->isHighRiskCountry('XX'));
    }

    #[Test]
    public function is_high_risk_country_returns_false_for_non_listed_country(): void
    {
        $this->assertFalse($this->service->isHighRiskCountry('MY'));
    }

    #[Test]
    public function is_high_risk_country_returns_false_for_null_country_code(): void
    {
        $this->assertFalse($this->service->isHighRiskCountry(null));
    }

    #[Test]
    public function calculate_score_returns_zero_for_low_risk_nationality(): void
    {
        $customer = Customer::factory()->create(['nationality' => 'MY']);

        $this->assertSame(0, $this->service->calculateScore($customer));
    }

    #[Test]
    public function calculate_score_returns_30_for_high_risk_nationality(): void
    {
        HighRiskCountry::factory()->create(['country_code' => 'YY', 'risk_level' => 'High']);

        $customer = Customer::factory()->create(['nationality' => 'YY']);

        $this->assertSame(30, $this->service->calculateScore($customer));
    }

    #[Test]
    public function get_risk_tier_returns_low_for_zero_score(): void
    {
        $customer = Customer::factory()->create(['nationality' => 'MY']);

        $this->assertEquals('low', $this->service->getRiskTier($customer));
    }

    #[Test]
    public function get_high_risk_countries_returns_array_of_codes(): void
    {
        HighRiskCountry::factory()->count(3)->create(['risk_level' => 'High']);

        $countries = $this->service->getHighRiskCountries();

        $this->assertIsArray($countries);
        $this->assertCount(3, $countries);
    }
}
