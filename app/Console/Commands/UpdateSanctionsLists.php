<?php

namespace App\Console\Commands;

use App\Jobs\ImportSanctionsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class UpdateSanctionsLists extends Command
{
    protected $signature = 'sanctions:update
                            {--source= : Update specific source (un, moha)}
                            {--sync : Run synchronously instead of dispatching to queue}';

    protected $description = 'Download and update sanctions lists from configured sources';

    /**
     * CLI source key => SanctionList slug (config/sanctions.php source key).
     *
     * Only sources configured in config/sanctions.php are supported. The
     * legacy file-download jobs (UN/OFAC/EU XML/CSV) referenced source keys
     * that were never configured and methods that were never implemented, so
     * this command routes through the OpenSanctions import pipeline instead.
     */
    protected array $sourceJobs = [
        'un' => 'un_consolidated',
        'moha' => 'moha_malaysia',
    ];

    public function handle(): int
    {
        $source = $this->option('source');
        $sync = (bool) $this->option('sync');

        if ($source) {
            return $this->updateSingleSource($source, $sync);
        }

        return $this->updateAllSources($sync);
    }

    protected function updateSingleSource(string $source, bool $sync): int
    {
        if (! isset($this->sourceJobs[$source])) {
            $this->error("Invalid source: {$source}");
            $this->line('Valid sources: '.implode(', ', array_keys($this->sourceJobs)));

            return Command::FAILURE;
        }

        $slug = $this->sourceJobs[$source];

        $this->info("Dispatching {$source} sanctions update job...");

        $job = new ImportSanctionsJob(null, $slug);

        $this->dispatchOrSync($job, $sync);

        $this->info("Job dispatched for {$source}.");
        $this->line('Run "php artisan sanctions:status" to check status.');

        return Command::SUCCESS;
    }

    protected function updateAllSources(bool $sync): int
    {
        $this->info('Dispatching sanctions list update jobs...');
        $this->newLine();

        foreach ($this->sourceJobs as $key => $slug) {
            $config = config("sanctions.sources.{$slug}");

            if (! $config || ! ($config['enabled'] ?? true)) {
                $this->warn("  [SKIP] {$key}: Disabled in configuration");

                continue;
            }

            if (empty($config['url'])) {
                $this->warn("  [SKIP] {$key}: No URL configured");

                continue;
            }

            if ($sync) {
                $this->line("  [SYNC] {$key}: Running...");
                try {
                    $this->dispatchOrSync(new ImportSanctionsJob(null, $slug), $sync);
                    $this->info("  [DONE] {$key}: Completed");
                } catch (\Exception $e) {
                    $this->error("  [FAIL] {$key}: {$e->getMessage()}");
                }
            } else {
                $this->dispatchOrSync(new ImportSanctionsJob(null, $slug), $sync);
                $this->info("  [QUEUE] {$key}: Dispatched");
            }
        }

        $this->newLine();
        $this->info('All enabled sanctions update jobs dispatched.');
        $this->line('Run "php artisan sanctions:status" to check status.');
        $this->line('Check "storage/logs/laravel.log" for detailed progress.');

        return Command::SUCCESS;
    }

    protected function dispatchOrSync(ImportSanctionsJob $job, bool $sync): void
    {
        if ($sync) {
            Bus::dispatchSync($job);

            return;
        }

        Bus::dispatch($job);
    }
}
