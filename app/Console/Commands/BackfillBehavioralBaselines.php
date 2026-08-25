<?php

namespace App\Console\Commands;

use App\Jobs\Compliance\ComputeBehavioralBaselineJob;
use App\Models\Customer;
use Illuminate\Console\Command;

class BackfillBehavioralBaselines extends Command
{
    protected $signature = 'customer:baseline-backfill {--chunk=500}';

    protected $description = 'Compute behavioral baselines for all active customers (backfill)';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $count = 0;

        Customer::where('is_active', true)
            ->select('id')
            ->chunkById($chunk, function ($customers) use (&$count) {
                foreach ($customers as $customer) {
                    ComputeBehavioralBaselineJob::dispatchSync($customer->id);
                    $count++;
                }
                $this->line("Dispatched baseline computation for {$count} customers...");
            });

        $this->info("Done. {$count} customer baselines computed.");

        return 0;
    }
}
