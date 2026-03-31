<?php

namespace App\Console\Commands;

use App\Services\RevaluationService;
use Illuminate\Console\Command;

class RunMonthlyRevaluation extends Command
{
    protected $signature = 'revaluation:run {--till=MAIN : Till ID to revalue}';

    protected $description = 'Run monthly currency revaluation';

    public function handle(RevaluationService $service)
    {
        $tillId = $this->option('till');
        $this->info("Starting revaluation for till: {$tillId}");

        $results = $service->runRevaluation(1, $tillId); // TODO: Get actual user ID

        $this->info("Revaluation completed!");
        $this->info("Positions revalued: {$results['positions_revalued']}");
        $this->info("Date: {$results['date']}");

        foreach ($results['entries'] as $entry) {
            $sign = $entry['gain_loss'] >= 0 ? '+' : '';
            $this->line("  {$entry['currency']}: {$sign}{$entry['gain_loss']}");
        }

        return 0;
    }
}
