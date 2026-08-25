<?php

namespace App\Listeners;

use App\Events\ReportGenerated;
use Illuminate\Support\Facades\Log;

class ReportGeneratedListener
{
    public function __invoke(ReportGenerated $event): void
    {
        Log::info('Report generated', [
            'report_run_id' => $event->reportRun->id,
            'report_type' => $event->reportRun->report_type ?? null,
        ]);
    }
}
