<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Transaction\TransactionCancellationService;
use Illuminate\Support\Facades\Log;

class RequestCancellationAction
{
    public function __construct(
        protected TransactionCancellationService $cancellationService
    ) {}

    public function execute(Transaction $transaction, User $requester, string $reason): CancellationActionResult
    {
        if (! $this->cancellationService->canCancel($transaction)) {
            return CancellationActionResult::error('This transaction cannot be cancelled in its current state.');
        }

        try {
            $result = $this->cancellationService->requestCancellation($transaction, $requester, $reason);
        } catch (\Exception $e) {
            Log::error('Cancellation request failed', [
                'transaction_id' => $transaction->id,
                'user_id' => $requester->id,
                'error' => $e->getMessage(),
            ]);

            return CancellationActionResult::error('Cancellation failed. Please try again.');
        }

        if ($result) {
            return CancellationActionResult::success(
                message: 'Cancellation requested. Awaiting supervisor approval.',
                transaction: $transaction
            );
        }

        return CancellationActionResult::error(
            'Cancellation request failed. Please check your permissions or try again.'
        );
    }
}
