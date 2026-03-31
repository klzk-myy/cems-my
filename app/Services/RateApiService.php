<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RateApiService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $cacheDuration = 60; // seconds

    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate_api.key');
        $this->baseUrl = 'https://api.exchangerate-api.com/v4';
    }

    public function fetchLatestRates(): array
    {
        return Cache::remember('exchange_rates', $this->cacheDuration, function () {
            $response = Http::get("{$this->baseUrl}/latest/MYR");

            if (!$response->successful()) {
                throw new \RuntimeException('Failed to fetch exchange rates: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['rates'])) {
                throw new \RuntimeException('Invalid API response format');
            }

            return $this->processRates($data['rates'], $data['time_last_updated'] ?? time());
        });
    }

    protected function processRates(array $rates, $timestamp): array
    {
        $processed = [];
        $currencies = ['USD', 'EUR', 'GBP', 'SGD', 'AUD', 'CAD', 'CHF', 'JPY'];

        foreach ($currencies as $currency) {
            if (isset($rates[$currency])) {
                $rate = $rates[$currency];

                // Add spread for buy/sell
                $spread = 0.02; // 2% spread

                $processed[$currency] = [
                    'buy' => $this->roundRate($rate * (1 - $spread / 2)),
                    'sell' => $this->roundRate($rate * (1 + $spread / 2)),
                    'mid' => $this->roundRate($rate),
                    'timestamp' => $timestamp,
                ];
            }
        }

        return $processed;
    }

    protected function roundRate(float $rate): float
    {
        return round($rate, 6);
    }

    public function getRateForCurrency(string $currency): ?array
    {
        $rates = $this->fetchLatestRates();
        return $rates[$currency] ?? null;
    }

    public function clearCache(): void
    {
        Cache::forget('exchange_rates');
    }
}
