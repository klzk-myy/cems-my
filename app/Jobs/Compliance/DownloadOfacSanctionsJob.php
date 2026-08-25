<?php

namespace App\Jobs\Compliance;

use App\Models\SanctionList;
use App\Services\Compliance\SanctionsImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadOfacSanctionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public array $backoff = [30, 60, 120];

    public function handle(SanctionsImportService $service): void
    {
        $list = SanctionList::where('slug', 'ofac_sdn')->first();

        if (! $list) {
            Log::warning('DownloadOfacSanctionsJob: ofac_sdn list not found');

            return;
        }

        Log::info('DownloadOfacSanctionsJob: Starting OFAC SDN import', ['list_id' => $list->id]);
        $service->import($list, false);
        Log::info('DownloadOfacSanctionsJob: OFAC SDN import completed', ['list_id' => $list->id]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('DownloadOfacSanctionsJob: OFAC SDN import failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['sanctions', 'sanctions-import', 'ofac_sdn'];
    }
}
