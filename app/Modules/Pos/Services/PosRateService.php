<?php

namespace App\Modules\Pos\Services;

use App\Modules\Pos\Models\PosDailyRate;
use App\Services\MathService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PosRateService
{
    protected MathService $mathService;

    protected int $cacheTtl;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
        $this->cacheTtl = config('pos.rate_cache_ttl', 3600);
    }

    public function getTodayRates(): ?array
    {
        $cacheKey = 'pos:rates:today';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $rates = PosDailyRate::forDate(today())->active()->get();

            if ($rates->isEmpty()) {
                return null;
            }

            return $rates->mapWithKeys(function ($rate) {
                return [
                    $rate->currency_code => [
                        'buy' => $this->mathService->add($rate->buy_rate, '0'),
                        'sell' => $this->mathService->add($rate->sell_rate, '0'),
                        'mid' => $this->mathService->add($rate->mid_rate, '0'),
                    ],
                ];
            })->toArray();
        });
    }

    public function getRatesForDate(string $date): ?array
    {
        $rates = PosDailyRate::forDate($date)->active()->get();

        if ($rates->isEmpty()) {
            return null;
        }

        return $rates->mapWithKeys(function ($rate) {
            return [
                $rate->currency_code => [
                    'buy' => $this->mathService->add($rate->buy_rate, '0'),
                    'sell' => $this->mathService->add($rate->sell_rate, '0'),
                    'mid' => $this->mathService->add($rate->mid_rate, '0'),
                ],
            ];
        })->toArray();
    }

    public function setDailyRates(array $rates, int $userId): bool
    {
        try {
            foreach ($rates as $currencyCode => $rateData) {
                PosDailyRate::updateOrCreate(
                    ['rate_date' => today()->toDateString(), 'currency_code' => $currencyCode],
                    [
                        'buy_rate' => $rateData['buy'],
                        'sell_rate' => $rateData['sell'],
                        'mid_rate' => $rateData['mid'],
                        'is_active' => true,
                        'created_by' => $userId,
                    ]
                );
            }

            Cache::forget('pos:rates:today');

            Log::info('POS daily rates set', [
                'user_id' => $userId,
                'date' => today()->toDateString(),
                'currencies' => array_keys($rates),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set POS daily rates', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function copyPreviousDayRates(): ?array
    {
        $yesterday = Carbon::yesterday()->toDateString();

        return $this->getRatesForDate($yesterday);
    }

    public function getRateHistory(int $days = 7): array
    {
        $history = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $rates = $this->getRatesForDate($date);

            if ($rates !== null) {
                $history[$date] = $rates;
            }
        }

        return $history;
    }

    public function getRateForCurrency(string $currencyCode, ?string $date = null): ?array
    {
        $date = $date ?? today()->toDateString();

        $rate = PosDailyRate::forDate($date)
            ->forCurrency($currencyCode)
            ->active()
            ->first();

        if ($rate === null) {
            return null;
        }

        return [
            'buy' => $this->mathService->add($rate->buy_rate, '0'),
            'sell' => $this->mathService->add($rate->sell_rate, '0'),
            'mid' => $this->mathService->add($rate->mid_rate, '0'),
        ];
    }

    public function invalidateCache(): void
    {
        Cache::forget('pos:rates:today');
    }
}
