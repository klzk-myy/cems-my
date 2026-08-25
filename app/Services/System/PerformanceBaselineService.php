<?php

namespace App\Services\System;

use App\Services\ThresholdService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PerformanceBaselineService
{
    protected const BASELINE_CACHE_KEY = 'performance_baseline';

    public function __construct(
        protected ThresholdService $thresholdService
    ) {}

    public function getBaseline(): array
    {
        $cached = Cache::get(self::BASELINE_CACHE_KEY, []);
        $baseline = is_array($cached) ? $cached : [];

        // Backfill ONLY missing keys so partially-populated cache entries
        // never leave compare*() reading undefined indexes. Present values
        // win; missing ones fall back to the configured thresholds.
        if (! array_key_exists('response_time_ms', $baseline)) {
            $baseline['response_time_ms'] = (int) $this->thresholdService->getResponseTimeWarning();
        }

        if (! array_key_exists('cache_hit_rate', $baseline)) {
            $baseline['cache_hit_rate'] = (float) $this->thresholdService->getCacheHitRateWarning();
        }

        if (! array_key_exists('queries_per_request', $baseline)) {
            $baseline['queries_per_request'] = (int) $this->thresholdService->getQueryTimeWarning();
        }

        return $baseline + ['memory_mb' => 128];
    }

    public function setBaseline(array $baseline): void
    {
        Cache::put(self::BASELINE_CACHE_KEY, $baseline, now()->addHours(24));
    }

    public function invalidate(): void
    {
        Cache::forget(self::BASELINE_CACHE_KEY);
    }

    public function updateBaselineMetric(string $key, mixed $value): void
    {
        $allowedKeys = ['response_time_ms', 'cache_hit_rate', 'queries_per_request', 'memory_mb', 'queue_processing_time'];
        if (! in_array($key, $allowedKeys, true)) {
            throw new \InvalidArgumentException("Invalid baseline metric key: {$key}");
        }

        $baseline = $this->getBaseline();
        $baseline[$key] = $value;
        $this->setBaseline($baseline);
    }

    public function compareResponseTime(float $currentMs): array
    {
        $baseline = $this->getBaseline();
        $threshold = (float) $this->thresholdService->getResponseTimeWarning();

        $variance = $baseline['response_time_ms'] > 0
            ? (($currentMs - $baseline['response_time_ms']) / $baseline['response_time_ms']) * 100
            : 0;

        return [
            'baseline' => $baseline['response_time_ms'],
            'current' => $currentMs,
            'variance_percent' => round($variance, 2),
            'exceeds_threshold' => $currentMs > $threshold,
            'status' => $variance > 20 ? 'degraded' : ($variance > 10 ? 'warning' : 'healthy'),
        ];
    }

    public function compareCacheHitRate(float $currentRate): array
    {
        $baseline = $this->getBaseline();
        $threshold = (float) $this->thresholdService->getCacheHitRateWarning();

        $variance = $baseline['cache_hit_rate'] > 0
            ? (($baseline['cache_hit_rate'] - $currentRate) / $baseline['cache_hit_rate']) * 100
            : 0;

        return [
            'baseline' => $baseline['cache_hit_rate'],
            'current' => $currentRate,
            'variance_percent' => round($variance, 2),
            'below_threshold' => $currentRate < $threshold,
            'status' => $variance > 20 ? 'degraded' : ($variance > 10 ? 'warning' : 'healthy'),
        ];
    }

    public function compareQueriesPerRequest(int $currentCount): array
    {
        $baseline = $this->getBaseline();

        $variance = $baseline['queries_per_request'] > 0
            ? (($currentCount - $baseline['queries_per_request']) / $baseline['queries_per_request']) * 100
            : 0;

        return [
            'baseline' => $baseline['queries_per_request'],
            'current' => $currentCount,
            'variance_percent' => round($variance, 2),
            'exceeds_baseline' => $currentCount > $baseline['queries_per_request'],
            'status' => $variance > 20 ? 'degraded' : ($variance > 10 ? 'warning' : 'healthy'),
        ];
    }

    public function isPerformanceHealthy(): bool
    {
        $baseline = $this->getBaseline();

        $responseTimeThreshold = (float) $this->thresholdService->getResponseTimeWarning();
        $cacheHitRateThreshold = (float) $this->thresholdService->getCacheHitRateWarning();

        $currentResponseTime = $this->getCurrentResponseTime();
        $currentCacheHitRate = $this->getCurrentCacheHitRate();

        if ($currentResponseTime > 0 && $currentCacheHitRate > 0) {
            return $currentResponseTime <= $responseTimeThreshold
                && $currentCacheHitRate >= $cacheHitRateThreshold;
        }

        Log::warning('Performance baseline check skipped - no real current data available', [
            'response_time' => $currentResponseTime,
            'cache_hit_rate' => $currentCacheHitRate,
        ]);

        return false;
    }

    public function getCurrentResponseTime(): float
    {
        return (float) Cache::get(CacheKeys::CurrentResponseTimeMs->value, 0);
    }

    public function getCurrentCacheHitRate(): float
    {
        return (float) Cache::get(CacheKeys::CurrentCacheHitRate->value, 0);
    }

    public function getPerformanceSummary(): array
    {
        $currentResponseTime = $this->getCurrentResponseTime();
        $currentCacheHitRate = $this->getCurrentCacheHitRate();

        return [
            'response_time' => $currentResponseTime > 0
                ? $this->compareResponseTime($currentResponseTime)
                : ['status' => 'unknown', 'current' => 0, 'baseline' => $this->getBaseline()['response_time_ms']],
            'cache_hit_rate' => $currentCacheHitRate > 0
                ? $this->compareCacheHitRate($currentCacheHitRate)
                : ['status' => 'unknown', 'current' => 0, 'baseline' => $this->getBaseline()['cache_hit_rate']],
            'is_healthy' => $this->isPerformanceHealthy(),
        ];
    }
}
