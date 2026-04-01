<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run revaluation at 23:59 on the last day of each month
        $schedule->command('revaluation:run')
            ->lastDayOfMonth()
            ->at('23:59')
            ->emailOutputTo('accounting@cems.my');

        // Daily MSB(2) report at 00:05 for previous day
        $schedule->command('report:msb2')
            ->dailyAt('00:05');

        // Weekly trial balance backup
        $schedule->command('report:trial-balance')
            ->weekly()
            ->sundays()
            ->at('01:00');

        // Monthly cleanup
        $schedule->command('reports:cleanup --days=90')
            ->monthly()
            ->onFirstOfMonth()
            ->at('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
