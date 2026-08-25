<?php

namespace App\Console\Commands;

use App\Services\System\SystemAlertService;
use Illuminate\Console\Command;

class CleanupSystemAlertsCommand extends Command
{
    protected $signature = 'alert:cleanup
                            {--days=30 : Delete acknowledged alerts older than N days}';

    protected $description = 'Clean up old acknowledged system alerts';

    protected SystemAlertService $alertService;

    public function __construct(SystemAlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be a positive integer.');

            return 1;
        }

        $this->info("Cleaning up acknowledged alerts older than {$days} days...");

        $deleted = $this->alertService->cleanupOldAlerts($days);

        $this->info("Removed {$deleted} old alert(s).");

        return 0;
    }
}
