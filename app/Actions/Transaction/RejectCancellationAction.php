<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Transaction\TransactionCancellationService;
use Illuminate\Support\Facades\Log;

class RejectCancellationAction
{
    public function __construct(
        protected TransactionCancellationService $cancellationService
    ) {}

    public function execute(Transaction $transaction, User $rejecter, string $reason): CancellationActionResult
    {
        if (! $transaction->status->isPendingCancellation()) {
            return CancellationActionResult::error('This transaction is not pending cancellation.');
        }

        $previousStatus = $transaction->status;

        try {
            $result = $this->cancellationService->rejectCancellation($transaction, $rejecter, $reason);
        } catch (\Exception $e) {
            Log::error('Cancellation rejection failed', [
                'transaction_id' => $transaction->id,
                'user_id' => $rejecter->id,
                'error' => $e->getMessage(),
            ]);

            return CancellationActionResult::error('Rejection failed. Please try again.');
        }

        if ($result) {
            return CancellationActionResult::success(
                message: 'Cancellation rejected. Transaction has been restored to its previous status.',
                transaction: $transaction,
                context: ['previous_status' => $previousStatus->value]
            );
        }

        return CancellationActionResult::error('Failed to reject cancellation. Transaction history may be corrupted.');
    }
}
