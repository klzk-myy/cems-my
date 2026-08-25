<?php

namespace App\Services\Compliance;

use App\Services\System\MathService;
use Illuminate\Support\Collection;

/**
 * Detects currency round-trip (sell-then-buy) patterns within a time window.
 *
 * Shared by PatternRiskService and CurrencyFlowMonitor so the detection
 * algorithm lives in one place. The inner sell x buy comparison is bounded
 * by only considering buys that fall strictly after each sell and within the
 * configured window, avoiding the previous unbounded O(sells x buys) scan.
 */
class RoundTripDetector
{
    public function __construct(
        protected MathService $mathService
    ) {}

    /**
     * Detect round-trip patterns in transactions.
     *
     * @param  Collection  $transactions  Transactions for a single customer
     * @param  int  $timeWindowHours  Window in which a buy must follow a sell
     * @param  string  $threshold  Minimum foreign-amount to flag
     * @return array<int, array<string, mixed>> Detected round-trip patterns
     */
    public function detect(Collection $transactions, int $timeWindowHours, string $threshold): array
    {
        $patterns = [];

        $byCurrency = $transactions->groupBy('currency_code');

        foreach ($byCurrency as $currencyCode => $currencyTxns) {
            $sells = $currencyTxns->filter(fn ($t) => $t->type->value === 'Sell')->values();
            $buys = $currencyTxns->filter(fn ($t) => $t->type->value === 'Buy')->values();

            if ($sells->isEmpty() || $buys->isEmpty()) {
                continue;
            }

            foreach ($sells as $sell) {
                $sellAt = $sell->created_at;
                $windowEnd = $sellAt->copy()->addHours($timeWindowHours);
                $sellForeign = ltrim((string) $sell->amount_foreign, '-');

                // Only buys strictly after the sell and within the window can pair.
                foreach ($buys as $buy) {
                    $buyAt = $buy->created_at;

                    if ($buyAt->lte($sellAt) || $buyAt->gt($windowEnd)) {
                        continue;
                    }

                    $hoursBetween = $sellAt->diffInHours($buyAt);

                    $buyForeign = ltrim((string) $buy->amount_foreign, '-');
                    $roundTripAmount = $this->mathService->compare($sellForeign, $buyForeign) <= 0
                        ? $sellForeign
                        : $buyForeign;

                    if ($this->mathService->compare($roundTripAmount, $threshold) < 0) {
                        continue;
                    }

                    $patterns[] = [
                        'currency' => $currencyCode,
                        'sell_transaction_id' => $sell->id,
                        'sell_amount_foreign' => (string) $sell->amount_foreign,
                        'sell_amount_local' => (string) $sell->amount_local,
                        'sell_at' => $sellAt->toDateTimeString(),
                        'buy_transaction_id' => $buy->id,
                        'buy_amount_foreign' => (string) $buy->amount_foreign,
                        'buy_amount_local' => (string) $buy->amount_local,
                        'buy_at' => $buyAt->toDateTimeString(),
                        'hours_between' => $hoursBetween,
                        'round_trip_foreign_amount' => $roundTripAmount,
                    ];
                }
            }
        }

        return $patterns;
    }
}
