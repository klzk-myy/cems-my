<?php

namespace App\Services\Compliance;

use App\Services\Compliance\Monitors\BaseMonitor;
use App\Services\MathService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Monitoring Engine
 *
 * Orchestrates all compliance monitors and aggregates their findings.
 */
class MonitoringEngine
{
    /** @var array<string> */
    protected array $monitors = [];

    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    /**
     * Register a monitor class.
     */
    public function registerMonitor(string $monitorClass): void
    {
        if (! in_array($monitorClass, $this->monitors, true)) {
            $this->monitors[] = $monitorClass;
        }
    }

    /**
     * Get registered monitor classes.
     *
     * @return array<string>
     */
    public function getRegisteredMonitors(): array
    {
        return $this->monitors;
    }

    /**
     * Get instance of a specific monitor.
     */
    public function getMonitor(string $monitorClass): BaseMonitor
    {
        return new $monitorClass($this->mathService);
    }

    /**
     * Run all registered monitors.
     *
     * @return Collection Collection of ComplianceFinding models
     */
    public function runAll(): Collection
    {
        $results = collect();
        foreach ($this->monitors as $monitorClass) {
            $monitor = $this->getMonitor($monitorClass);
            try {
                $findings = $monitor->execute();
                Log::info("Monitor {$monitorClass} generated " . count($findings) . " findings");
                $results = $results->merge($findings);
            } catch (\Throwable $e) {
                Log::error("Monitor {$monitorClass} failed: " . $e->getMessage());
            }
        }
        return $results;
    }

    /**
     * Run a specific monitor by class.
     *
     * @return Collection Collection of ComplianceFinding models
     */
    public function runMonitor(string $monitorClass): Collection
    {
        $monitor = $this->getMonitor($monitorClass);
        try {
            $findings = $monitor->execute();
            Log::info("Monitor {$monitorClass} generated " . count($findings) . " findings");
            return collect($findings);
        } catch (\Throwable $e) {
            Log::error("Monitor {$monitorClass} failed: " . $e->getMessage());
            return collect();
        }
    }
}
