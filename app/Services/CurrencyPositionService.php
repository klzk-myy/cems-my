<?php

namespace App\Services;

use App\Models\CurrencyPosition;
use Illuminate\Support\Facades\DB;

/**
 * Currency Position Service
 *
 * Manages foreign currency positions for currency exchange operations.
 * Tracks balances, calculates average costs, and monitors unrealized P&L.
 * Uses MathService for high-precision calculations to prevent floating-point errors.
 */
class CurrencyPositionService
{
    /**
     * Math service instance for high-precision calculations.
     */
    protected MathService $mathService;

    /**
     * Create a new CurrencyPositionService instance.
     *
     * @param  MathService  $mathService  Math service for high-precision calculations
     */
    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    /**
     * Update a currency position with a new transaction.
     *
     * Uses MathService for all high-precision calculations.
     * For 'Buy' transactions, increases position and recalculates average cost.
     * For 'Sell' transactions, decreases position (cost basis unchanged).
     *
     * @param  string  $currencyCode  Currency code (e.g., 'USD', 'EUR')
     * @param  string  $amount  Transaction amount as string
     * @param  string  $rate  Exchange rate for this transaction
     * @param  string  $type  Transaction type: 'Buy' or 'Sell'
     * @param  string  $tillId  Till identifier (default: 'MAIN')
     * @return CurrencyPosition Updated position model
     *
     * @throws \InvalidArgumentException If selling with insufficient or zero balance
     */
    public function updatePosition(
        string $currencyCode,
        string $amount,
        string $rate,
        string $type,
        string $tillId = 'MAIN'
    ): CurrencyPosition {
        return DB::transaction(function () use ($currencyCode, $amount, $rate, $type, $tillId) {
            // Lock the position row for update to prevent race conditions on concurrent sells
            $position = CurrencyPosition::where('currency_code', $currencyCode)
                ->where('till_id', $tillId)
                ->lockForUpdate()
                ->first();

            // Create position if it doesn't exist
            if ($position === null) {
                $position = new CurrencyPosition([
                    'currency_code' => $currencyCode,
                    'till_id' => $tillId,
                    'balance' => '0',
                    'avg_cost_rate' => $rate,
                    'last_valuation_rate' => $rate,
                ]);
                $position->save();
            }

            $oldBalance = $position->balance;
            $oldAvgCost = $position->avg_cost_rate;

            if ($type === 'Buy') {
                // Buying foreign currency - increase position
                $newBalance = $this->mathService->add($oldBalance, $amount);
                if ($this->mathService->compare($oldBalance, '0') > 0) {
                    $newAvgCost = $this->mathService->calculateAverageCost(
                        $oldBalance,
                        $oldAvgCost,
                        $amount,
                        $rate
                    );
                } else {
                    $newAvgCost = $rate;
                }
            } else {
                // Selling foreign currency - decrease position
                // Check for sufficient balance - prevent negative positions
                if ($this->mathService->compare($oldBalance, '0') <= 0) {
                    throw new \InvalidArgumentException(
                        'Cannot sell: Position is empty or negative'
                    );
                }
                if ($this->mathService->compare($oldBalance, $amount) < 0) {
                    throw new \InvalidArgumentException(
                        "Insufficient balance. Available: {$oldBalance}, Requested: {$amount}"
                    );
                }
                $newBalance = $this->mathService->subtract($oldBalance, $amount);
                $newAvgCost = $oldAvgCost; // Cost basis doesn't change on sale
            }

            $position->update([
                'balance' => $newBalance,
                'avg_cost_rate' => $newAvgCost,
                'last_valuation_rate' => $rate,
                'unrealized_pnl' => $this->mathService->calculateRevaluationPnl($newBalance, $newAvgCost, $rate),
                'last_valuation_at' => now(),
            ]);

            return $position->fresh();
        });
    }

    /**
     * Get a specific currency position.
     *
     * @param  string  $currencyCode  Currency code (e.g., 'USD', 'EUR')
     * @param  string  $tillId  Till identifier (default: 'MAIN')
     * @return CurrencyPosition|null Position model or null if not found
     */
    public function getPosition(string $currencyCode, string $tillId = 'MAIN'): ?CurrencyPosition
    {
        return CurrencyPosition::where('currency_code', $currencyCode)
            ->where('till_id', $tillId)
            ->first();
    }

    /**
     * Get all positions for a specific till.
     *
     * @param  string  $tillId  Till identifier (default: 'MAIN')
     * @return \Illuminate\Database\Eloquent\Collection Collection of position models
     */
    public function getAllPositions(string $tillId = 'MAIN'): \Illuminate\Database\Eloquent\Collection
    {
        return CurrencyPosition::where('till_id', $tillId)
            ->with('currency')
            ->get();
    }

    /**
     * Calculate total unrealized P&L across all positions for a till.
     *
     * Uses MathService for high-precision addition of position P&L values.
     *
     * @param  string  $tillId  Till identifier (default: 'MAIN')
     * @return string Total unrealized P&L as string
     */
    public function getTotalPnl(string $tillId = 'MAIN'): string
    {
        $positions = $this->getAllPositions($tillId);
        $totalUnrealized = '0';

        foreach ($positions as $position) {
            $totalUnrealized = $this->mathService->add($totalUnrealized, $position['unrealized_pnl'] ?? '0');
        }

        return $totalUnrealized;
    }
}
