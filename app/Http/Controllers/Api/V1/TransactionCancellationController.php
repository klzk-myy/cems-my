<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Services\AccountingService;
use App\Services\ComplianceService;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionCancellationController extends Controller
{
    public function __construct(
        protected CurrencyPositionService $positionService,
        protected ComplianceService $complianceService,
        protected MathService $mathService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Cancel a transaction.
     *
     * State transitions:
     * - draft, pending_approval, approved, processing, failed, rejected -> cancelled
     * - completed -> reversed (not cancelled; creates refund transaction)
     */
    public function cancel(Request $request, int $transactionId): JsonResponse
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (! $this->canCancel(auth()->user(), $transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this transaction.',
            ], 403);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:1000',
        ]);

        if (! $this->canBeCancelled($transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'This transaction cannot be cancelled in its current state.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $originalTillId = $transaction->till_id ?? 'MAIN';
            $originalStatus = $transaction->status;

            // Completed transactions are reversed (not cancelled) and require a refund
            $isCompleted = $transaction->status->isCompleted();
            $refundTransaction = null;

            if ($isCompleted) {
                // Completed transactions get reversed with a refund
                $refundTransaction = $this->createRefundTransaction($transaction);
                $newStatus = TransactionStatus::Reversed;
            } else {
                // Non-completed transactions are simply cancelled
                $newStatus = TransactionStatus::Cancelled;
            }

            // Update status and increment version to prevent race conditions
            $transaction->status = $newStatus;
            $transaction->cancelled_at = now();
            $transaction->cancelled_by = auth()->id();
            $transaction->cancellation_reason = $validated['cancellation_reason'];
            $transaction->version = ($transaction->version ?? 0) + 1;
            $transaction->save();

            // Reverse stock position only for completed transactions
            if ($isCompleted) {
                $this->reverseStockPosition($transaction, $originalTillId);
                $this->createReversingJournalEntries($transaction);
            }

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => $isCompleted ? 'transaction_reversed' : 'transaction_cancelled',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'old_values' => ['status' => $originalStatus->value],
                'new_values' => [
                    'status' => $newStatus->value,
                    'refund_transaction_id' => $refundTransaction?->id,
                    'reason' => $validated['cancellation_reason'],
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            $message = $isCompleted
                ? 'Transaction reversed successfully. Refund transaction created.'
                : 'Transaction cancelled successfully.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'transaction' => $transaction->fresh(),
                    'refund_transaction' => $refundTransaction,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user can cancel transaction
     */
    protected function canCancel($user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Check if transaction can be cancelled (or reversed if completed)
     */
    protected function canBeCancelled(Transaction $transaction): bool
    {
        $status = $transaction->status;

        // Already in a final state that cannot be changed
        if ($status->isFinalized()) {
            return false;
        }

        // Already cancelled or reversed cannot be cancelled again
        if ($status->isCancelled() || $status->isReversed()) {
            return false;
        }

        // Already cancelled (indicated by cancelled_at being set even if status hasn't updated)
        if ($transaction->cancelled_at !== null) {
            return false;
        }

        // Cannot cancel a refund transaction
        if ($transaction->is_refund) {
            return false;
        }

        // Completed transactions can be reversed (within time window)
        if ($status->isCompleted()) {
            return $this->isWithinCancellationWindow($transaction);
        }

        // All other non-final states can be cancelled
        return true;
    }

    /**
     * Check if transaction is within the cancellation window
     */
    protected function isWithinCancellationWindow(Transaction $transaction): bool
    {
        $cancellationWindowHours = config('cems.transaction_cancellation_window_hours', 24);

        return $transaction->created_at->diffInHours(now()) <= $cancellationWindowHours;
    }

    protected function createRefundTransaction(Transaction $original): Transaction
    {
        $refundType = $original->type->opposite();
        $customer = Customer::findOrFail($original->customer_id);
        $amountLocal = $this->mathService->multiply(
            (string) $original->amount_foreign,
            (string) $original->rate
        );

        $holdCheck = $this->complianceService->requiresHold($amountLocal, $customer);

        $status = TransactionStatus::Completed;
        $holdReason = null;

        if ($holdCheck['requires_hold']) {
            if ($this->mathService->compare($amountLocal, '50000') >= 0) {
                $status = TransactionStatus::Pending;
                $holdReason = implode(', ', $holdCheck['reasons']);
            } else {
                $status = TransactionStatus::OnHold;
                $holdReason = implode(', ', $holdCheck['reasons']);
            }
        }

        return Transaction::create([
            'customer_id' => $original->customer_id,
            'user_id' => auth()->id(),
            'branch_id' => $original->branch_id,
            'till_id' => $original->till_id,
            'type' => $refundType,
            'currency_code' => $original->currency_code,
            'amount_foreign' => $original->amount_foreign,
            'amount_local' => $amountLocal,
            'rate' => $original->rate,
            'purpose' => 'Refund: '.$original->purpose,
            'source_of_funds' => 'Refund',
            'status' => $status,
            'cdd_level' => $original->cdd_level,
            'original_transaction_id' => $original->id,
            'is_refund' => true,
        ]);
    }

    protected function reverseStockPosition(Transaction $transaction, ?string $tillId = null): void
    {
        $reverseType = $transaction->type->opposite();
        $this->positionService->updatePosition(
            $transaction->currency_code,
            (string) $transaction->amount_foreign,
            (string) $transaction->rate,
            $reverseType->value,
            $tillId ?? $transaction->till_id ?? 'MAIN'
        );
    }

    protected function createReversingJournalEntries(Transaction $transaction): void
    {
        $entries = [];
        if ($transaction->type->isBuy()) {
            $entries = [
                [
                    'account_code' => \App\Enums\AccountCode::CASH_MYR->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Refund for cancelled transaction #{$transaction->id}",
                ],
                [
                    'account_code' => \App\Enums\AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Reversal: {$transaction->currency_code} refund",
                ],
            ];
        } else {
            $entries = [
                [
                    'account_code' => \App\Enums\AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Refund for cancelled transaction #{$transaction->id}",
                ],
                [
                    'account_code' => \App\Enums\AccountCode::CASH_MYR->value,
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Reversal: {$transaction->currency_code} refund",
                ],
            ];
        }

        $this->accountingService->createJournalEntry(
            $entries,
            'TransactionCancellation',
            $transaction->id,
            "Cancellation of Transaction #{$transaction->id}"
        );
    }
}
