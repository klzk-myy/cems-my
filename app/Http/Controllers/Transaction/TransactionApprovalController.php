<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Transaction\Concerns\TransactionAccounting;
use App\Enums\TransactionStatus;
use App\Models\SystemLog;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\TransactionConfirmation;
use App\Services\AccountingService;
use App\Services\ComplianceService;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use App\Services\TransactionMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionApprovalController extends Controller
{
    use TransactionAccounting;

    public function __construct(
        protected CurrencyPositionService $positionService,
        protected ComplianceService $complianceService,
        protected TransactionMonitoringService $monitoringService,
        protected MathService $mathService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Approve pending transaction
     */
    public function approve(Request $request, Transaction $transaction)
    {
        $this->requireManagerOrAdmin();

        if (! $transaction->status->isPending()) {
            return back()->with('error', 'Transaction is not pending approval.');
        }

        DB::beginTransaction();
        try {
            // Re-evaluate AML rules before approval
            // If high-priority flags are generated, keep transaction pending
            $amlResult = $this->monitoringService->monitorTransaction($transaction);
            $highPriorityFlags = array_filter($amlResult['flags'], function ($flag) {
                return $flag->flag_type->isHighPriority();
            });

            if (! empty($highPriorityFlags)) {
                DB::rollBack();
                $flagTypes = implode(', ', array_map(fn ($f) => $f->flag_type->label(), $highPriorityFlags));

                return back()->with('error', "Approval blocked: High-priority AML flags generated ({$flagTypes}). Transaction remains pending for compliance review.");
            }

            // Optimistic locking: Use version to prevent race conditions
            // If another manager approved between the status check and now, this will fail
            $updated = Transaction::where('id', $transaction->id)
                ->where('status', TransactionStatus::Pending)
                ->where('version', $transaction->version)
                ->update([
                    'status' => TransactionStatus::Completed,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'version' => DB::raw('version + 1'),
                ]);

            if (! $updated) {
                DB::rollBack();

                return back()->with('error', 'Transaction was already processed or modified by another user.');
            }

            // Refresh the model to get the updated version
            $transaction->refresh();

            $tillBalance = TillBalance::where('till_id', $transaction->till_id ?? 'MAIN')
                ->where('currency_code', $transaction->currency_code)
                ->whereDate('date', today())
                ->whereNull('closed_at')
                ->first();

            if ($tillBalance) {
                $this->positionService->updatePosition(
                    $transaction->currency_code,
                    (string) $transaction->amount_foreign,
                    (string) $transaction->rate,
                    $transaction->type->value,
                    $transaction->till_id ?? 'MAIN'
                );
                $this->updateTillBalance($tillBalance, $transaction->type->value,
                    (string) $transaction->amount_local,
                    (string) $transaction->amount_foreign
                );
            }

            $this->createAccountingEntries($transaction);

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_approved',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'new_values' => ['approved_by' => auth()->id()],
                'ip_address' => $request->ip(),
            ]);

            // Transaction monitoring is handled via TransactionCreated event
            DB::commit();

            // Dispatch event for async processing of monitoring
            \App\Events\TransactionCreated::dispatch($transaction);

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction approved and completed.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Approval failed: '.$e->getMessage());
        }
    }

    /**
     * Show confirmation page for large transactions (>= RM 50,000)
     */
    public function showConfirm(Transaction $transaction)
    {
        // Check if transaction requires confirmation (>= RM 50,000)
        if (! $this->requiresConfirmation($transaction)) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'This transaction does not require confirmation.');
        }

        // Check if there's already a pending confirmation
        $confirmation = TransactionConfirmation::where('transaction_id', $transaction->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (! $confirmation) {
            // Create a new confirmation request
            $confirmationToken = bin2hex(random_bytes(32));
            $confirmation = TransactionConfirmation::create([
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'confirmation_token' => $confirmationToken,
                'expires_at' => now()->addMinutes(30),
            ]);

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'confirmation_requested',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'new_values' => [
                    'confirmation_id' => $confirmation->id,
                    'amount_local' => $transaction->amount_local,
                ],
                'ip_address' => request()->ip(),
            ]);
        }

        $transaction->load(['customer', 'user']);

        return view('transactions.confirm', compact('transaction', 'confirmation'));
    }

    /**
     * Process transaction confirmation (manager approves large transaction)
     */
    public function confirm(Request $request, Transaction $transaction)
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager approval required for confirmation.');
        }

        if (! $this->requiresConfirmation($transaction)) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'This transaction does not require confirmation.');
        }

        $confirmation = TransactionConfirmation::where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->first();

        if (! $confirmation) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'No pending confirmation found.');
        }

        if ($confirmation->isExpired()) {
            $confirmation->markExpired();

            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Confirmation has expired. Please request a new confirmation.');
        }

        $validated = $request->validate([
            'confirmation_action' => 'required|in:confirm,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            if ($validated['confirmation_action'] === 'confirm') {
                $confirmation->markConfirmed(auth()->id(), $validated['notes'] ?? null);

                // Complete the transaction
                $updated = Transaction::where('id', $transaction->id)
                    ->whereIn('status', [TransactionStatus::Pending, TransactionStatus::OnHold])
                    ->update([
                        'status' => TransactionStatus::Completed,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                if (! $updated) {
                    DB::rollBack();

                    return back()->with('error', 'Transaction could not be completed. Status may have changed.');
                }

                $transaction->refresh();

                // Update positions and create accounting entries
                $tillBalance = TillBalance::where('till_id', $transaction->till_id ?? 'MAIN')
                    ->where('currency_code', $transaction->currency_code)
                    ->whereDate('date', today())
                    ->whereNull('closed_at')
                    ->first();

                if ($tillBalance) {
                    $this->positionService->updatePosition(
                        $transaction->currency_code,
                        (string) $transaction->amount_foreign,
                        (string) $transaction->rate,
                        $transaction->type->value,
                        $transaction->till_id ?? 'MAIN'
                    );
                    $this->updateTillBalance($tillBalance, $transaction->type->value,
                        (string) $transaction->amount_local,
                        (string) $transaction->amount_foreign
                    );
                }

                $this->createAccountingEntries($transaction);

                SystemLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'transaction_confirmed',
                    'entity_type' => 'Transaction',
                    'entity_id' => $transaction->id,
                    'new_values' => [
                        'confirmation_id' => $confirmation->id,
                        'confirmed_by' => auth()->id(),
                    ],
                    'ip_address' => $request->ip(),
                ]);

                DB::commit();

                // Dispatch event for async processing
                \App\Events\TransactionCreated::dispatch($transaction);

                return redirect()->route('transactions.show', $transaction)
                    ->with('success', 'Transaction confirmed and completed successfully.');

            } else {
                // Reject the transaction
                $confirmation->markRejected(auth()->id(), $validated['notes'] ?? null);

                $transaction->update([
                    'status' => TransactionStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancellation_reason' => 'Rejected during confirmation: '.($validated['notes'] ?? 'No reason provided'),
                ]);

                SystemLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'transaction_rejected',
                    'entity_type' => 'Transaction',
                    'entity_id' => $transaction->id,
                    'new_values' => [
                        'confirmation_id' => $confirmation->id,
                        'rejected_by' => auth()->id(),
                        'reason' => $validated['notes'] ?? 'No reason provided',
                    ],
                    'ip_address' => $request->ip(),
                ]);

                DB::commit();

                return redirect()->route('transactions.show', $transaction)
                    ->with('warning', 'Transaction has been rejected.');
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Confirmation failed: '.$e->getMessage());
        }
    }

    /**
     * Check if transaction requires manager confirmation (>= RM 50,000)
     */
    protected function requiresConfirmation(Transaction $transaction): bool
    {
        $threshold = config('cems.thresholds.str', '50000');

        return $this->mathService->compare($transaction->amount_local, $threshold) >= 0;
    }
}
