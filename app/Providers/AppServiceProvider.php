<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerCarbonMacros();
    }

    /**
     * Register Carbon macros for working days calculations.
     * BNM compliance requires STR filing within 3 working days.
     */
    protected function registerCarbonMacros(): void
    {
        Carbon::macro('addWorkingDays', function (int $days) {
            $current = $this->copy();
            $added = 0;

            while ($added < $days) {
                $current->addDay();
                if (! $current->isWeekend()) {
                    $added++;
                }
            }

            return $current;
        });

        Carbon::macro('workingDaysUntil', function ($date) {
            $end = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
            $current = $this->copy();
            $workingDays = 0;

            while ($current->lessThan($end)) {
                $current->addDay();
                if (! $current->isWeekend()) {
                    $workingDays++;
                }
            }

            return $workingDays;
        });
    }
}
