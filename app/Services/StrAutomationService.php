<?php

namespace App\Services;

use App\Enums\ComplianceFlagType;
use App\Enums\StrStatus;
use App\Events\StrDraftGenerated;
use App\Models\ComplianceCase;
use App\Models\StrDraft;
use App\Models\StrReport;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StrAutomationService
{
    public function __construct(
        protected ComplianceService $complianceService,
    ) {}

    /**
     * Generate an STR draft from a case.
     */
    public function generateFromCase(ComplianceCase $case): StrDraft
    {
        $alerts = $case->alerts()->with(['flaggedTransaction.transaction'])->get();
        $customer = $case->customer;
        $transactionIds = $this->extractTransactionIds($alerts);
        $transactions = $this->getTransactions($transactionIds);

        $narrative = $this->generateNarrative($alerts, $transactions);
        $suspectedActivity = $this->identifySuspectedActivity($alerts);
        $confidenceScore = $this->calculateConfidenceScore($alerts, $transactions);
        $filingDeadline = $this->calculateFilingDeadline($case);

        $strDraft = StrDraft::create([
            'case_id' => $case->id,
            'alert_ids' => $alerts->pluck('id')->toArray(),
            'customer_id' => $customer->id,
            'transaction_ids' => $transactionIds,
            'narrative' => $narrative,
            'suspected_activity' => $suspectedActivity,
            'confidence_score' => $confidenceScore,
            'filing_deadline' => $filingDeadline,
            'status' => StrStatus::Draft,
            'created_by' => auth()->id(),
        ]);

        event(new StrDraftGenerated($strDraft));

        return $strDraft;
    }

    /**
     * Generate narrative from alerts and transactions.
     */
    public function suggestNarrative(array $alertTypes, array $transactionPatterns): string
    {
        $narrativeParts = [];

        $hasVelocity = in_array(ComplianceFlagType::Velocity, $alertTypes);
        $hasStructuring = in_array(ComplianceFlagType::Structuring, $alertTypes);
        $hasHighAmount = in_array(ComplianceFlagType::LargeAmount, $alertTypes);
        $hasHighRiskCountry = in_array(ComplianceFlagType::HighRiskCountry, $alertTypes);
        $hasSanctionMatch = in_array(ComplianceFlagType::SanctionMatch, $alertTypes);

        if ($hasVelocity && isset($transactionPatterns['total_amount'])) {
            $narrativeParts[] = sprintf(
                'Multiple transactions aggregating to RM %s within a 24-hour period were detected, exceeding the established threshold for velocity monitoring.',
                number_format($transactionPatterns['total_amount'], 2)
            );
        }

        if ($hasStructuring && isset($transactionPatterns['sub_threshold_count'])) {
            $narrativeParts[] = sprintf(
                'Suspected structuring: %d transactions under RM 50,000 were conducted within a short time frame, potentially to avoid regulatory reporting thresholds.',
                $transactionPatterns['sub_threshold_count']
            );
        }

        if ($hasHighAmount && isset($transactionPatterns['max_amount'])) {
            $narrativeParts[] = sprintf(
                'A large transaction of RM %s was identified, requiring enhanced scrutiny under BNM AML/CFT requirements.',
                number_format($transactionPatterns['max_amount'], 2)
            );
        }

        if ($hasHighRiskCountry) {
            $narrativeParts[] = 'Transaction involves a customer from or has connections to a high-risk country as designated under BNM AML/CFT guidelines.';
        }

        if ($hasSanctionMatch) {
            $narrativeParts[] = 'The customer or transaction counterpart matches entries on applicable sanctions watchlists (UNSCR, MOHA, or internal list), warranting immediate escalation.';
        }

        if (empty($narrativeParts)) {
            $narrativeParts[] = 'An unusual pattern of transactions was observed that does not conform to the customer\'s typical profile and transaction behavior.';
        }

        return implode(' ', $narrativeParts);
    }

    /**
     * Convert STR draft to formal StrReport.
     */
    public function convertToStrReport(StrDraft $strDraft): StrReport
    {
        if (! $strDraft->canConvert()) {
            throw new \RuntimeException('STR draft does not meet conversion criteria');
        }

        $customer = $strDraft->customer;
        $transactions = $this->getTransactions($strDraft->transaction_ids ?? []);

        $strReport = StrReport::create([
            'customer_id' => $customer->id,
            'case_reference' => $strDraft->case?->case_number,
            'report_date' => now(),
            'suspicion_date' => $strDraft->created_at,
            'filing_deadline' => $strDraft->filing_deadline,
            'status' => StrStatus::Draft,
            'transaction_ids' => $strDraft->transaction_ids,
            'amount_range' => $this->calculateAmountRange($transactions),
            'narrative' => $strDraft->narrative,
            'suspected_activity' => $strDraft->suspected_activity,
            'prepared_by' => $strDraft->created_by,
        ]);

        $strDraft->update([
            'converted_to_str_id' => $strReport->id,
            'status' => StrStatus::Submitted,
        ]);

        return $strReport;
    }

    /**
     * Get all overdue STR drafts.
     */
    public function getOverdueDrafts(): Collection
    {
        return StrDraft::overdue()->get();
    }

    /**
     * Get filing deadline summary.
     */
    public function getFilingDeadlineSummary(): array
    {
        $pendingDrafts = StrDraft::pending()->get();

        return [
            'total_pending' => $pendingDrafts->count(),
            'overdue' => $pendingDrafts->filter(fn ($d) => $d->isOverdue())->count(),
            'due_24h' => $pendingDrafts->filter(fn ($d) => $d->filing_deadline && $d->filing_deadline->diffInHours(now()) <= 24)->count(),
            'due_48h' => $pendingDrafts->filter(fn ($d) => $d->filing_deadline && $d->filing_deadline->diffInHours(now()) <= 48)->count(),
        ];
    }

    protected function extractTransactionIds(Collection $alerts): array
    {
        $ids = [];
        foreach ($alerts as $alert) {
            if ($alert->flaggedTransaction?->transaction_id) {
                $ids[] = $alert->flaggedTransaction->transaction_id;
            }
        }

        return array_unique($ids);
    }

    protected function getTransactions(array $ids): Collection
    {
        return Transaction::whereIn('id', $ids)->get();
    }

    protected function generateNarrative(Collection $alerts, Collection $transactions): string
    {
        $alertTypes = $alerts->pluck('type')->toArray();
        $patterns = $this->extractTransactionPatterns($transactions);

        return $this->suggestNarrative($alertTypes, $patterns);
    }

    protected function extractTransactionPatterns(Collection $transactions): array
    {
        if ($transactions->isEmpty()) {
            return [];
        }

        return [
            'total_amount' => $transactions->sum('amount_local'),
            'max_amount' => $transactions->max('amount_local'),
            'sub_threshold_count' => $transactions->where('amount_local', '<', 50000)->count(),
            'avg_amount' => $transactions->avg('amount_local'),
            'currency_codes' => $transactions->pluck('currency_code')->unique()->toArray(),
        ];
    }

    protected function identifySuspectedActivity(Collection $alerts): string
    {
        $flagTypes = $alerts->pluck('type')->unique();

        $activities = [];
        foreach ($flagTypes as $flagType) {
            $activities[] = match ($flagType) {
                ComplianceFlagType::Velocity => 'Velocity/rapid movement of funds',
                ComplianceFlagType::Structuring => 'Potential structuring to avoid reporting',
                ComplianceFlagType::LargeAmount => 'Large transaction exceeding threshold',
                ComplianceFlagType::SanctionMatch => 'Sanctions watchlist match',
                ComplianceFlagType::HighRiskCountry => 'High-risk country involvement',
                ComplianceFlagType::UnusualPattern => 'Unusual transaction pattern',
                default => 'Compliance concern requiring review',
            };
        }

        return implode('; ', $activities);
    }

    protected function calculateConfidenceScore(Collection $alerts, Collection $transactions): int
    {
        $score = 50;

        if ($alerts->count() >= 3) {
            $score += 15;
        } elseif ($alerts->count() >= 2) {
            $score += 10;
        }

        $hasMultipleTypes = $alerts->pluck('type')->unique()->count() > 1;
        if ($hasMultipleTypes) {
            $score += 15;
        }

        $maxAmount = $transactions->max('amount_local') ?? 0;
        if ($maxAmount >= 50000) {
            $score += 10;
        } elseif ($maxAmount >= 30000) {
            $score += 5;
        }

        $hasSanction = $alerts->contains(fn ($a) => $a->type === ComplianceFlagType::SanctionMatch);
        if ($hasSanction) {
            $score += 20;
        }

        return min($score, 100);
    }

    protected function calculateFilingDeadline(ComplianceCase $case): Carbon
    {
        return Carbon::parse($case->created_at)
            ->addWorkingDays(ComplianceService::STR_FILING_DEADLINE_DAYS);
    }

    protected function calculateAmountRange(Collection $transactions): string
    {
        if ($transactions->isEmpty()) {
            return 'N/A';
        }

        $min = $transactions->min('amount_local');
        $max = $transactions->max('amount_local');

        if ($min == $max) {
            return sprintf('RM %s', number_format($min, 2));
        }

        return sprintf('RM %s - RM %s', number_format($min, 2), number_format($max, 2));
    }
}
