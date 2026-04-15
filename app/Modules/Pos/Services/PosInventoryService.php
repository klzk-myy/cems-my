<?php

namespace App\Modules\Pos\Services;

use App\Models\CurrencyPosition;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use Illuminate\Support\Facades\Cache;

class PosInventoryService
{
    protected CurrencyPositionService $positionService;

    protected MathService $mathService;

    public function __construct(CurrencyPositionService $positionService, MathService $mathService)
    {
        $this->positionService = $positionService;
        $this->mathService = $mathService;
    }

    public function getInventoryByCounter(string $counterId): array
    {
        $cacheKey = "pos:inventory:counter:{$counterId}";

        return Cache::remember($cacheKey, 300, function () use ($counterId) {
            $positions = CurrencyPosition::where('till_id', $counterId)->with('currency')->get();

            return $positions->map(function ($position) {
                return [
                    'currency_code' => $position->currency_code,
                    'currency_name' => $position->currency->name ?? 'Unknown',
                    'balance' => $position->balance,
                    'avg_cost_rate' => $position->avg_cost_rate,
                    'status' => $this->getStockStatus($position->balance),
                ];
            })->toArray();
        });
    }

    public function getAggregateInventory(): array
    {
        $cacheKey = 'pos:inventory:aggregate';

        $positions = CurrencyPosition::with('currency')->get()->groupBy('currency_code');
        $inventory = [];

        foreach ($positions as $currencyCode => $currencyPositions) {
            $totalBalance = '0';
            $totalValue = '0';

            foreach ($currencyPositions as $position) {
                $avgCost = $position->avg_cost_rate ?? '0';
                $totalBalance = $this->mathService->add($totalBalance, $position->balance);
                $value = $this->mathService->multiply($position->balance, $avgCost);
                $totalValue = $this->mathService->add($totalValue, $value);
            }

            $inventory[$currencyCode] = [
                'currency_code' => $currencyCode,
                'currency_name' => $currencyPositions->first()->currency->name ?? 'Unknown',
                'total_balance' => $totalBalance,
                'total_value_myr' => $totalValue,
                'status' => $this->getStockStatus($totalBalance),
            ];
        }

        return array_values($inventory);
    }

    public function getLowStockCurrencies(float $threshold = 10000.00): array
    {
        $inventory = $this->getAggregateInventory();

        $lowStock = array_filter($inventory, function ($item) use ($threshold) {
            return $this->mathService->compare($item['total_balance'], (string) $threshold) < 0;
        });

        return array_values($lowStock);
    }

    public function calculateEodVariance(array $physicalCounts): array
    {
        $variances = [];

        foreach ($physicalCounts as $count) {
            $currencyCode = $count['currency_code'];
            $physicalAmount = $count['amount'];
            $counterId = $count['counter_id'];

            $position = CurrencyPosition::where('till_id', $counterId)
                ->where('currency_code', $currencyCode)
                ->first();

            if ($position === null) {
                continue;
            }

            $expectedBalance = $position->balance;
            $variance = $this->mathService->subtract((string) $physicalAmount, $expectedBalance);

            $variances[] = [
                'currency_code' => $currencyCode,
                'counter_id' => $counterId,
                'expected_balance' => $expectedBalance,
                'physical_count' => (string) $physicalAmount,
                'variance' => $variance,
                'status' => $this->getVarianceStatus($variance),
            ];
        }

        return $variances;
    }

    protected function getStockStatus(string $balance): string
    {
        $balanceFloat = floatval($balance);
        if ($balanceFloat < 10000) {
            return 'low';
        } elseif ($balanceFloat < 25000) {
            return 'medium';
        }

        return 'normal';
    }

    protected function getVarianceStatus(string $variance): string
    {
        $varianceAbs = abs(floatval($variance));
        if ($varianceAbs >= config('pos.eod_variance_red', 500)) {
            return 'red';
        } elseif ($varianceAbs >= config('pos.eod_variance_yellow', 100)) {
            return 'yellow';
        }

        return 'green';
    }

    public function invalidateCache(?string $counterId = null): void
    {
        if ($counterId) {
            Cache::forget("pos:inventory:counter:{$counterId}");
        }
        Cache::forget('pos:inventory:aggregate');
    }
}
