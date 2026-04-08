<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Enums\AccountCode;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Customer;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Services\AccountingService;
use App\Services\ComplianceService;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
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
     * Show cancellation confirmation form
     */
    public function showCancel(Transaction $transaction)
    {
        if (! $this->canCancel(auth()->user(), $transaction)) {
            abort(403, 'Unauthorized to cancel this transaction.');
        }

        if (! $transaction->isRefundable()) {
            return back()->with('error', 'This transaction cannot be cancelled.');
        }

        return view('transactions.cancel', compact('transaction'));
    }

    /**
     * Process transaction cancellation
     */
    public function cancel(Request $request, Transaction $transaction)
    {
        if (! $this->canCancel(auth()->user(), $transaction)) {
            abort(403, 'Unauthorized to cancel this transaction.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:1000',
            'confirm_understanding' => 'required|accepted',
        ]);

        if (! $transaction->isRefundable()) {
            return back()->with('error', 'This transaction cannot be cancelled.');
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

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction cancelled successfully. Refund transaction created.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Cancellation failed: '.$e->getMessage());
        }
    }

    /**
     * Check if user can cancel transaction
     *
     * All transaction cancellations require manager or admin approval.
     * This enforces segregation of duties - no user should be able to
     * cancel their own transactions without supervisory approval.
     */
    protected function canCancel($user, Transaction $transaction): bool
    {
        // Managers and admins can cancel any transaction
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Tellers cannot cancel any transactions - requires manager approval
        // This enforces segregation of duties
        return false;
    }

    /**
     * Create refund transaction
     */
    protected function createRefundTransaction(Transaction $original): Transaction
    {
        $refundType = $original->type->opposite();
        $customer = Customer::findOrFail($original->customer_id);
        $amountLocal = $this->mathService->multiply(
            (string) $original->amount_foreign,
            (string) $original->rate
        );

        // Evaluate compliance for refund transaction
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

        // Log compliance decision for refund audit trail
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'refund_compliance_check',
            'entity_type' => 'Transaction',
            'entity_id' => null,
            'new_values' => [
                'original_transaction_id' => $original->id,
                'amount_local' => $amountLocal,
                'status' => $status->value,
                'hold_reason' => $holdReason,
                'compliance_reasons' => $holdCheck['reasons'],
            ],
        ]);

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

    /**
     * Reverse stock position
     */
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

    /**
     * Create reversing journal entries
     */
    protected function createReversingJournalEntries(Transaction $transaction): void
    {
        $entries = [];
        if ($transaction->type->isBuy()) {
            $entries = [
                [
                    'account_code' => AccountCode::CASH_MYR->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Refund for cancelled transaction #{$transaction->id}",
                ],
                [
                    'account_code' => AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Reversal: {$transaction->currency_code} refund",
                ],
            ];
        } else {
            $entries = [
                [
                    'account_code' => AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Refund for cancelled transaction #{$transaction->id}",
                ],
                [
                    'account_code' => AccountCode::CASH_MYR->value,
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
