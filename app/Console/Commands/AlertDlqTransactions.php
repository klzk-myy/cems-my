<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\DlqTransactionAlertNotification;
use App\Services\System\SystemAlertService;
use App\Services\Transaction\TransactionRecoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Alert admins when transactions are stuck in the dead letter queue.
 *
 * Runs on the same cadence as transactions:recover so DLQ items are surfaced
 * promptly after they exhaust automatic retries. Only NEW DLQ transaction IDs
 * (not previously alerted) trigger a notification, so admins are not spammed
 * on every sweep while the same stuck items remain in the queue.
 */
class AlertDlqTransactions extends Command
{
    protected $signature = 'transactions:dlq-alert {--dry-run : Report without notifying}';

    protected $description = 'Alert admins about transactions stuck in the dead letter queue';

    /** Cache key storing the set of DLQ transaction IDs already alerted. */
    private const ALERTED_IDS_KEY = 'dlq_alerted_transaction_ids';

    public function __construct(
        protected TransactionRecoveryService $recoveryService,
        protected SystemAlertService $alertService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // The scheduler also runs this with withoutOverlapping()/onOneServer(),
        // but manual invocations can still race. Serialize the read-diff-write
        // of the alerted-IDs cache so two concurrent sweeps never both fire a
        // duplicate alert for the same transactions.
        $lock = Cache::lock(self::ALERTED_IDS_KEY, 30);

        if (! $lock->get()) {
            $this->info('Another DLQ alert sweep is already running; skipping.');

            return Command::SUCCESS;
        }

        try {
            return $this->executeSweep();
        } finally {
            $lock->release();
        }
    }

    private function executeSweep(): int
    {
        $transactions = $this->recoveryService->getDeadLetterQueueTransactions();

        if ($transactions->isEmpty()) {
            // Queue drained - clear the alerted set so a future DLQ entry re-alerts.
            Cache::forget(self::ALERTED_IDS_KEY);
            $this->info('No transactions in the dead letter queue.');

            return Command::SUCCESS;
        }

        $currentIds = $transactions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $alreadyAlerted = (array) Cache::get(self::ALERTED_IDS_KEY, []);
        $newIds = array_values(array_diff($currentIds, $alreadyAlerted));

        if ($newIds === []) {
            $this->info(sprintf(
                '%d DLQ transaction(s) already alerted; nothing new to surface.',
                count($currentIds)
            ));

            return Command::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                '[dry-run] Would alert admins about %d new DLQ transaction(s): %s',
                count($newIds),
                implode(', ', $newIds)
            ));

            return Command::SUCCESS;
        }

        $newTransactions = $transactions->whereIn('id', $newIds);

        $this->alertService->warning(
            sprintf('%d transaction(s) stuck in the dead letter queue require manual review.', count($newIds)),
            [
                'source' => 'transaction_dlq',
                'metadata' => [
                    'transaction_ids' => $newIds,
                    'total_dlq' => count($currentIds),
                    'action' => 'review_dlq',
                ],
            ]
        );

        // Eager-load preferences so via()/shouldSendEmail() resolve in memory
        // instead of issuing one preference query per admin.
        $admins = User::query()
            ->with('notificationPreferences')
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new DlqTransactionAlertNotification($newTransactions));
        }

        Cache::put(self::ALERTED_IDS_KEY, array_values(array_unique(array_merge($alreadyAlerted, $newIds))), now()->addDays(30));

        $this->info(sprintf(
            'Alerted %d admin(s) about %d new DLQ transaction(s).',
            $admins->count(),
            count($newIds)
        ));

        return Command::SUCCESS;
    }
}
