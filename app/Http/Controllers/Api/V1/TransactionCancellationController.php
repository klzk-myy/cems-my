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

        if (! $transaction->isRefundable()) {
            return response()->json([
                'success' => false,
                'message' => 'This transaction cannot be cancelled.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $originalTillId = $transaction->till_id ?? 'MAIN';
            $refundTransaction = $this->createRefundTransaction($transaction);

            $transaction->update([
                'status' => TransactionStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $validated['cancellation_reason'],
            ]);

            $this->reverseStockPosition($transaction, $originalTillId);
            $this->createReversingJournalEntries($transaction);

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_cancelled',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'old_values' => ['status' => TransactionStatus::Completed->value],
                'new_values' => [
                    'status' => TransactionStatus::Cancelled->value,
                    'refund_transaction_id' => $refundTransaction->id,
                    'reason' => $validated['cancellation_reason'],
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction cancelled successfully.',
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

    protected function canCancel($user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $user->isManager();
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
