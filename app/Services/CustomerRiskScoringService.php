<?php

namespace App\Services;

use App\Enums\RiskTrend;
use App\Events\RiskScoreUpdated;
use App\Models\Customer;
use App\Models\HighRiskCountry;
use App\Models\RiskScoreSnapshot;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class CustomerRiskScoringService
{
    public function __construct(
        protected SanctionScreeningService $sanctionService,
        protected ComplianceService $complianceService,
    ) {}

    /**
     * Calculate and store risk score snapshot for a customer.
     */
    public function calculateAndSnapshot(int $customerId): RiskScoreSnapshot
    {
        $customer = Customer::findOrFail($customerId);

        $scores = $this->calculateRiskScores($customer);
        $previousSnapshots = $this->getRecentSnapshots($customerId);
        $trend = RiskScoreSnapshot::calculateTrend($previousSnapshots->toArray());
        $factors = $this->extractRiskFactors($customer, $scores);

        $snapshot = RiskScoreSnapshot::create([
            'customer_id' => $customerId,
            'snapshot_date' => today(),
            'overall_score' => $scores['overall'],
            'velocity_score' => $scores['velocity'],
            'structuring_score' => $scores['structuring'],
            'geographic_score' => $scores['geographic'],
            'amount_score' => $scores['amount'],
            'trend' => $trend,
            'factors' => $factors,
            'next_screening_date' => $this->calculateNextScreeningDate($scores['overall']),
        ]);

        event(new RiskScoreUpdated($snapshot));

        return $snapshot;
    }

    /**
     * Calculate all risk sub-scores for a customer.
     */
    public function calculateRiskScores(Customer $customer): array
    {
        $transactions = $this->getRecentTransactions($customer->id);

        return [
            'velocity' => $this->calculateVelocityScore($transactions),
            'structuring' => $this->calculateStructuringScore($transactions),
            'geographic' => $this->calculateGeographicScore($customer),
            'amount' => $this->calculateAmountScore($transactions, $customer),
            'overall' => 0,
        ];
    }

    /**
     * Perform full rescreening of a customer.
     */
    public function rescreenCustomer(int $customerId): array
    {
        $customer = Customer::findOrFail($customerId);

        $sanctionResult = $this->sanctionService->screenCustomer($customer);

        $previousSnapshot = RiskScoreSnapshot::where('customer_id', $customerId)
            ->latest()
            ->first();

        $newSnapshot = $this->calculateAndSnapshot($customerId);

        $scoreChange = $previousSnapshot
            ? abs($newSnapshot->overall_score - $previousSnapshot->overall_score)
            : $newSnapshot->overall_score;

        return [
            'customer_id' => $customerId,
            'sanction_match' => $sanctionResult['is_match'] ?? false,
            'previous_score' => $previousSnapshot?->overall_score,
            'new_score' => $newSnapshot->overall_score,
            'score_change' => $scoreChange,
            'significant_change' => $scoreChange >= 20,
            'snapshot' => $newSnapshot,
        ];
    }

    /**
     * Get high-risk customers needing attention.
     */
    public function getHighRiskCustomers(int $threshold = 60): Collection
    {
        return Customer::whereHas('riskScoreSnapshots', function ($query) use ($threshold) {
            $query->where('overall_score', '>=', $threshold)
                ->where('snapshot_date', today());
        })->with('latestRiskSnapshot')->get();
    }

    /**
     * Get customers needing rescreening.
     */
    public function getCustomersNeedingRescreening(): Collection
    {
        return Customer::whereHas('riskScoreSnapshots', function ($query) {
            $query->needsRescreening();
        })->with('latestRiskSnapshot')->get();
    }

    /**
     * Get risk trend for a customer over time.
     */
    public function getRiskTrend(int $customerId, int $months = 6): array
    {
        $snapshots = RiskScoreSnapshot::where('customer_id', $customerId)
            ->where('snapshot_date', '>=', now()->subMonths($months))
            ->orderBy('snapshot_date')
            ->get();

        return [
            'customer_id' => $customerId,
            'period' => $months . ' months',
            'data_points' => $snapshots->count(),
            'current_score' => $snapshots->last()?->overall_score,
            'trend' => $snapshots->last()?->trend,
            'snapshots' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->toDateString(),
                'score' => $s->overall_score,
                'trend' => $s->trend->value,
            ])->toArray(),
        ];
    }

    /**
     * Get dashboard summary statistics.
     */
    public function getDashboardSummary(): array
    {
        $todaySnapshots = RiskScoreSnapshot::where('snapshot_date', today())->get();

        return [
            'total_scored_today' => $todaySnapshots->count(),
            'critical_risk' => $todaySnapshots->where('overall_score', '>=', 80)->count(),
            'high_risk' => $todaySnapshots->whereBetween('overall_score', [60, 79])->count(),
            'medium_risk' => $todaySnapshots->whereBetween('overall_score', [30, 59])->count(),
            'low_risk' => $todaySnapshots->where('overall_score', '<', 30)->count(),
            'deteriorating_trend' => $todaySnapshots->where('trend', RiskTrend::Deteriorating)->count(),
            'needs_rescreening' => $this->getCustomersNeedingRescreening()->count(),
        ];
    }

    protected function getRecentTransactions(int $customerId): Collection
    {
        return Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', now()->subDays(90))
            ->where('status', 'Completed')
            ->get();
    }

    protected function getRecentSnapshots(int $customerId): Collection
    {
        return RiskScoreSnapshot::where('customer_id', $customerId)
            ->latest()
            ->take(3)
            ->get();
    }

    protected function calculateVelocityScore(Collection $transactions): int
    {
        $score = 0;

        $dailyAmounts = $transactions->groupBy(fn($t) => $t->created_at->format('Y-m-d'))
            ->map(fn($day) => $day->sum('amount_local'));

        foreach ($dailyAmounts as $date => $amount) {
            if ((float) $amount >= 50000) {
                $score += 30;
            } elseif ((float) $amount >= 30000) {
                $score += 20;
            } elseif ((float) $amount >= 10000) {
                $score += 10;
            }
        }

        return min($score, 40);
    }

    protected function calculateStructuringScore(Collection $transactions): int
    {
        $score = 0;

        $subThreshold = $transactions->filter(fn($t) => (float) $t->amount_local < 50000);
        $hourlyGroups = $subThreshold->groupBy(fn($t) => $t->created_at->format('Y-m-d H'));

        foreach ($hourlyGroups as $hour => $txns) {
            if ($txns->count() >= 3) {
                $score += 25;
            } elseif ($txns->count() >= 2) {
                $score += 10;
            }
        }

        return min($score, 30);
    }

    protected function calculateGeographicScore(Customer $customer): int
    {
        $score = 0;

        $highRiskCountries = HighRiskCountry::pluck('country_code')->toArray();

        if (in_array($customer->nationality, $highRiskCountries)) {
            $score += 30;
        }

        $recentCountries = $customer->transactions()
            ->where('created_at', '>=', now()->subDays(90))
            ->pluck('counterparty_country')
            ->filter()
            ->unique();

        foreach ($recentCountries as $country) {
            if (in_array($country, $highRiskCountries)) {
                $score += 15;
            }
        }

        return min($score, 40);
    }

    protected function calculateAmountScore(Collection $transactions, Customer $customer): int
    {
        $score = 0;

        $maxTransaction = $transactions->max('amount_local') ?? 0;

        if ((float) $maxTransaction >= 50000) {
            $score += 30;
        } elseif ((float) $maxTransaction >= 30000) {
            $score += 20;
        } elseif ((float) $maxTransaction >= 10000) {
            $score += 10;
        }

        $monthlyVolume = $transactions->where('created_at', '>=', now()->subDays(30))
            ->sum('amount_local');

        if ($customer->annual_volume_estimate) {
            $expectedMonthly = (float) $customer->annual_volume_estimate / 12;
            if ((float) $monthlyVolume > 2 * $expectedMonthly) {
                $score += 10;
            }
        }

        return min($score, 30);
    }

    protected function extractRiskFactors(Customer $customer, array $scores): array
    {
        $factors = [];

        if ($scores['velocity'] >= 20) {
            $factors[] = 'High velocity transactions detected';
        }

        if ($scores['structuring'] >= 15) {
            $factors[] = 'Potential structuring patterns identified';
        }

        if ($scores['geographic'] >= 20) {
            $factors[] = 'High-risk country involvement';
        }

        if ($scores['amount'] >= 20) {
            $factors[] = 'Large transaction amounts';
        }

        if ($customer->is_pep) {
            $factors[] = 'PEP customer';
        }

        if ($customer->is_sanctioned) {
            $factors[] = 'Sanctions match';
        }

        return $factors;
    }

    protected function calculateNextScreeningDate(int $overallScore): \DateTime
    {
        $days = match (true) {
            $overallScore >= 80 => 30,
            $overallScore >= 60 => 60,
            $overallScore >= 30 => 90,
            default => 180,
        };

        return now()->addDays($days);
    }
}