<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ============ DAILY REPORTS ============

        // MSB(2) - Daily transaction summary (previous day)
        $schedule->command('report:msb2')
            ->dailyAt('00:05')
            ->appendOutputTo(storage_path('logs/report-msb2.log'));

        // Position Limit - Daily limit utilization check
        $schedule->command('report:position-limit')
            ->dailyAt('06:00')
            ->appendOutputTo(storage_path('logs/report-position-limit.log'));

        // ============ WEEKLY REPORTS ============

        // Trial Balance - Every Sunday at 01:00
        $schedule->command('report:trial-balance')
            ->weekly()
            ->sundays()
            ->at('01:00')
            ->appendOutputTo(storage_path('logs/report-trial-balance.log'));

        // ============ MONTHLY REPORTS (Run on 1st of each month) ============

        // LMCA - BNM Monthly Form (for previous month) - 1st of month at 00:30
        $schedule->command('report:lmca')
            ->cron('30 0 1 * *')
            ->appendOutputTo(storage_path('logs/report-lmca.log'));

        // LCTR - Cash Transaction Report (for previous month) - 1st of month at 00:45
        $schedule->command('report:lctr')
            ->cron('45 0 1 * *')
            ->appendOutputTo(storage_path('logs/report-lctr.log'));

        // Sanctions Rescreening (BNM monthly requirement) - 1st of month at 03:00
        $schedule->command('compliance:rescreen --days=30')
            ->cron('0 3 1 * *')
            ->appendOutputTo(storage_path('logs/compliance-rescreen.log'));

        // Cleanup old temp reports - 1st of month at 02:00
        $schedule->command('reports:cleanup --days=90')
            ->cron('0 2 1 * *')
            ->appendOutputTo(storage_path('logs/reports-cleanup.log'));

        // ============ QUARTERLY REPORTS ============

        // Quarterly Large Value Report - Run on 1st of months 4, 7, 10, 1 (Apr=4, Jul=7, Oct=10, Jan=1)
        $schedule->command('report:qlvr')
            ->cron('0 1 1 1,4,7,10 *')
            ->appendOutputTo(storage_path('logs/report-qlvr.log'));

        // ============ ANNUAL REPORTS ============

        // Report Archival (BNM requires 7-year retention) - January 1st at 04:00
        $schedule->command('reports:archive --months=12')
            ->cron('0 4 1 1 *')
            ->appendOutputTo(storage_path('logs/reports-archive.log'));

        // Revaluation at end of month - last day at 23:59
        $schedule->command('revaluation:run')
            ->cron('59 23 L * *')
            ->appendOutputTo(storage_path('logs/revaluation.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
