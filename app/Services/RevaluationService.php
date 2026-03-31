<?php

namespace App\Services;

use App\Models\CurrencyPosition;
use App\Models\RevaluationEntry;
use Illuminate\Support\Facades\DB;

class RevaluationService
{
    protected MathService $mathService;
    protected RateApiService $rateApiService;

    public function __construct(
        MathService $mathService,
        RateApiService $rateApiService
    ) {
        $this->mathService = $mathService;
        $this->rateApiService = $rateApiService;
    }

    public function runRevaluation(int $postedBy, ?string $tillId = null): array
    {
        $tillId = $tillId ?? 'MAIN';
        $revaluationDate = now()->toDateString();
        $results = [];

        $positions = CurrencyPosition::where('till_id', $tillId)
            ->where('balance', '!=', 0)
            ->get();

        foreach ($positions as $position) {
            $result = $this->revaluePosition($position, $revaluationDate, $postedBy);
            if ($result) {
                $results[] = $result;
            }
        }

        return [
            'date' => $revaluationDate,
            'till_id' => $tillId,
            'positions_revalued' => count($results),
            'entries' => $results,
        ];
    }

    protected function revaluePosition(CurrencyPosition $position, string $date, int $postedBy): ?array
    {
        $newRate = $this->getCurrentRate($position->currency_code);
        if (!$newRate) {
            return null;
        }

        $oldRate = $position->last_valuation_rate ?? $position->avg_cost_rate;
        $gainLoss = $this->mathService->calculateRevaluationPnl(
            $position->balance,
            $oldRate,
            $newRate
        );

        return DB::transaction(function () use ($position, $oldRate, $newRate, $gainLoss, $date, $postedBy) {
            // Create revaluation entry
            $entry = RevaluationEntry::create([
                'currency_code' => $position->currency_code,
                'till_id' => $position->till_id,
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'position_amount' => $position->balance,
                'gain_loss_amount' => $gainLoss,
                'revaluation_date' => $date,
                'posted_by' => $postedBy,
            ]);

            // Update position
            $cumulativePnl = $this->mathService->add(
                $position->unrealized_pnl ?? '0',
                $gainLoss
            );
            $position->update([
                'last_valuation_rate' => $newRate,
                'unrealized_pnl' => $cumulativePnl,
                'last_valuation_at' => now(),
            ]);

            return [
                'entry_id' => $entry->id,
                'currency' => $position->currency_code,
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'gain_loss' => $gainLoss,
            ];
        });
    }

    protected function getCurrentRate(string $currencyCode): ?string
    {
        $rate = $this->rateApiService->getRateForCurrency($currencyCode);
        if (!$rate) {
            return null;
        }

        // Use mid rate for revaluation
        return (string) $rate['mid'];
    }

    public function getRevaluationReport(string $date): array
    {
        $entries = RevaluationEntry::where('revaluation_date', $date)
            ->with(['currency', 'postedBy'])
            ->get();

        $totalGain = '0';
        $totalLoss = '0';

        foreach ($entries as $entry) {
            $amount = $entry->gain_loss_amount;
            if ($this->mathService->compare($amount, '0') >= 0) {
                $totalGain = $this->mathService->add($totalGain, $amount);
            } else {
                $totalLoss = $this->mathService->add($totalLoss, $amount);
            }
        }

        return [
            'date' => $date,
            'entries' => $entries,
            'total_gain' => $totalGain,
            'total_loss' => $totalLoss,
            'net_pnl' => $this->mathService->add($totalGain, $totalLoss),
        ];
    }
}
