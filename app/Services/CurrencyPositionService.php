<?php

namespace App\Services;

use App\Models\CurrencyPosition;
use Illuminate\Support\Facades\DB;

class CurrencyPositionService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function updatePosition(
        string $currencyCode,
        string $amount,
        string $rate,
        string $type,
        string $tillId = 'MAIN'
    ): CurrencyPosition {
        return DB::transaction(function () use ($currencyCode, $amount, $rate, $type, $tillId) {
            $position = CurrencyPosition::firstOrCreate(
                ['currency_code' => $currencyCode, 'till_id' => $tillId],
                [
                    'balance' => '0',
                    'avg_cost_rate' => $rate,
                    'last_valuation_rate' => $rate,
                ]
            );

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
            if ($this->mathService->compare($oldBalance, $amount) < 0) {
                throw new \InvalidArgumentException(
                    "Insufficient balance. Available: {$oldBalance}, Requested: {$amount}"
                );
            }
            if ($this->mathService->compare($oldBalance, '0') <= 0) {
                throw new \InvalidArgumentException(
                    "Cannot sell: Position is empty or negative. Balance: {$oldBalance}"
                );
            }
            $newBalance = $this->mathService->subtract($oldBalance, $amount);
            $newAvgCost = $oldAvgCost; // Cost basis doesn't change on sale
        }

            $position->update([
                'balance' => $newBalance,
                'avg_cost_rate' => $newAvgCost,
            ]);

            return $position->fresh();
        });
    }

    public function getPosition(string $currencyCode, string $tillId = 'MAIN'): ?CurrencyPosition
    {
        return CurrencyPosition::where('currency_code', $currencyCode)
            ->where('till_id', $tillId)
            ->first();
    }

    public function getAllPositions(string $tillId = 'MAIN'): \Illuminate\Database\Eloquent\Collection
    {
        return CurrencyPosition::where('till_id', $tillId)
            ->with('currency')
            ->get();
    }

    public function getTotalPnl(string $tillId = 'MAIN'): float
    {
        $positions = $this->getAllPositions($tillId);
        $totalUnrealized = 0.0;

        foreach ($positions as $position) {
            $totalUnrealized += (float) $position['unrealized_pnl'];
        }

        return $totalUnrealized;
    }
}
