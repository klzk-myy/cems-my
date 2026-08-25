<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    public function forgetPosition(int|string|null $branchId, string $currencyCode): void
    {
        Cache::forget(CacheKeys::positionAvailable($branchId, $currencyCode));
    }

    public function forgetCustomer(int|string $customerId): void
    {
        Cache::forget(CacheKeys::customer($customerId));
    }

    public function forgetRate(string $currencyCode, ?int $branchId = null): void
    {
        Cache::forget(CacheKeys::rate($currencyCode, $branchId));
    }

    public function forgetAllRates(array $currencies, ?int $branchId = null): void
    {
        foreach ($currencies as $currency) {
            $this->forgetRate($currency, $branchId);
        }
    }

    public function forgetExchangeRates(?int $branchId = null): void
    {
        Cache::forget(CacheKeys::exchangeRates($branchId));
    }

    public function forgetWizardSession(string $sessionId): void
    {
        Cache::forget(CacheKeys::wizardSession($sessionId));
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
