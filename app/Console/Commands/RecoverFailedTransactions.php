<?php

namespace App\Console\Commands;

use App\Services\Transaction\TransactionRecoveryService;
use Illuminate\Console\Command;

/**
 * Process pending transaction recoveries.
 *
 * Sweeps Failed transactions and either dispatches a retry job (exponential
 * backoff) or moves them to the dead letter queue once max retries are hit.
 * This is the scheduled counterpart to the inline recovery dispatched when a
 * booking failure is first recorded.
 */
class RecoverFailedTransactions extends Command
{
    protected $signature = 'transactions:recover';

    protected $description = 'Retry or DLQ transactions that failed during booking';

    public function __construct(
        protected TransactionRecoveryService $recoveryService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $stats = $this->recoveryService->processPendingRecoveries();

        $this->info(sprintf(
            'Transaction recovery complete: %d total, %d retried, %d moved to DLQ, %d not ready.',
            $stats['total'],
            $stats['retried'],
            $stats['moved_to_dlq'],
            $stats['not_ready']
        ));

        return Command::SUCCESS;
    }
}
