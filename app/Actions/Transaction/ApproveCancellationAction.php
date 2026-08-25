<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Transaction\TransactionCancellationService;
use Illuminate\Support\Facades\Log;

class ApproveCancellationAction
{
    public function __construct(
        protected TransactionCancellationService $cancellationService
    ) {}

    public function execute(Transaction $transaction, User $approver, ?string $reason): CancellationActionResult
    {
        if (! $transaction->status->isPendingCancellation()) {
            return CancellationActionResult::error('This transaction is not pending cancellation.');
        }

        try {
            $result = $this->cancellationService->approveCancellation($transaction, $approver, $reason);
        } catch (\Exception $e) {
            Log::error('Cancellation approval failed', [
                'transaction_id' => $transaction->id,
                'user_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);

            return CancellationActionResult::error('Approval failed. Please try again.');
        }

        if ($result) {
            return CancellationActionResult::success(
                message: 'Cancellation approved. Transaction has been cancelled.',
                transaction: $transaction
            );
        }

        return CancellationActionResult::error('Failed to approve cancellation. Please try again.');
    }
}
