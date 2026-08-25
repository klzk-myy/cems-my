<?php

namespace App\Services\Risk;

use App\Models\Customer;
use App\Models\HighRiskCountry;
use App\Services\ThresholdService;

class GeographicRiskService
{
    public function __construct(
        protected ThresholdService $thresholdService,
    ) {}

    /**
     * Calculate geographic risk score for a customer.
     *
     * Considers:
     * - Customer nationality (high-country weight points if high-risk)
     * - Transaction counterparty countries (recent-travel weight per unique high-risk country in 90 days)
     *
     * @return int Risk score (0-40)
     */
    public function calculateScore(Customer $customer): int
    {
        $score = 0;

        $highRiskCountries = HighRiskCountry::countryCodes();

        if ($this->isHighRiskCountry($customer->nationality, $highRiskCountries)) {
            $score += $this->thresholdService->getGeographicHighCountryWeight();
        }

        $recentCountries = $customer->transactions()
            ->where('created_at', '>=', now()->subDays(90))
            ->whereNotNull('counterparty_country')
            ->pluck('counterparty_country')
            ->filter()
            ->unique();

        foreach ($recentCountries as $country) {
            if (in_array($country, $highRiskCountries, true)) {
                $score += $this->thresholdService->getGeographicRecentTravelWeight();
            }
        }

        return min($score, 40);
    }

    /**
     * Check if a country is high-risk.
     *
     * @param  string|null  $countryCode  ISO 3-letter country code
     * @param  array  $highRiskCountries  List of high-risk country codes
     */
    public function isHighRiskCountry(?string $countryCode, ?array $highRiskCountries = null): bool
    {
        if (! $countryCode) {
            return false;
        }

        if ($highRiskCountries === null) {
            $highRiskCountries = HighRiskCountry::countryCodes();
        }

        return in_array($countryCode, $highRiskCountries, true);
    }

    /**
     * Get geographic risk tier for a customer.
     *
     * @return string 'low', 'medium', or 'high'
     */
    public function getRiskTier(Customer $customer): string
    {
        $score = $this->calculateScore($customer);

        $highWeight = $this->thresholdService->getGeographicHighCountryWeight();
        $travelWeight = $this->thresholdService->getGeographicRecentTravelWeight();

        if ($score >= $highWeight) {
            return 'high';
        }

        if ($score >= $travelWeight) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get all high-risk countries.
     *
     * @return array<string> Array of ISO 3-letter country codes
     */
    public function getHighRiskCountries(): array
    {
        return HighRiskCountry::countryCodes();
    }
}
