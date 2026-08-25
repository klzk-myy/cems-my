<?php

namespace App\Jobs\Compliance;

use App\Services\Compliance\MonitoringEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunComplianceMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public function __construct(
        public string $monitorClass
    ) {}

    public function handle(MonitoringEngine $engine): void
    {
        Log::info("Running compliance monitor: {$this->monitorClass}");
        $engine->runMonitor($this->monitorClass);
        Log::info("Compliance monitor completed: {$this->monitorClass}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Compliance monitor permanently failed: {$this->monitorClass}", [
            'monitor' => $this->monitorClass,
            'exception' => $exception->getMessage(),
        ]);
    }
}
