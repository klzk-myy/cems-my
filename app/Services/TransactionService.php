<?php

namespace App\Services;

use App\Enums\ComplianceFlagType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransactionApproved;
use App\Events\TransactionCreated;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Transaction Service
 *
 * Handles core transaction creation logic for both web and API controllers.
 * Ensures BCMath precision for all monetary calculations and compliance checks.
 */
class TransactionService
{
    public function __construct(
        protected MathService $mathService,
        protected ComplianceService $complianceService,
        protected CurrencyPositionService $positionService,
        protected AccountingService $accountingService,
        protected AuditService $auditService,
        protected TransactionMonitoringService $monitoringService,
        protected CtosReportService $ctosReportService,
    ) {}

    /**
     * Create a new transaction with full validation and compliance checks.
     *
     * @param  array  $data  Validated transaction data
     * @param  int|null  $userId  User creating the transaction (null for API context)
     * @param  string|null  $ipAddress  IP address for audit logging
     *
     * @throws \Exception If transaction creation fails
     */
    public function createTransaction(array $data, ?int $userId = null, ?string $ipAddress = null): Transaction
    {
        $userId = $userId ?? auth()->id();
        $ipAddress = $ipAddress ?? request()->ip();

        // Validate IP address format
        if ($ipAddress && ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address format.');
        }

        // Verify till is open for this currency
        $tillBalance = TillBalance::where('till_id', $data['till_id'])
            ->where('currency_code', $data['currency_code'])
            ->whereDate('date', today())
            ->whereNull('closed_at')
            ->first();

        if (! $tillBalance) {
            throw new \InvalidArgumentException('Till is not open for this currency. Please open the till first.');
        }

        // Get customer and calculate amounts
        $customer = Customer::find($data['customer_id']);
        $amountForeign = (string) $data['amount_foreign'];
        $rate = (string) $data['rate'];
        $amountLocal = $this->mathService->multiply($amountForeign, $rate);

        // Determine CDD level
        $cddLevel = $this->complianceService->determineCDDLevel($amountLocal, $customer);
        $holdCheck = $this->complianceService->requiresHold($amountLocal, $customer);

        // Log CDD decision
        $cddTriggers = [];
        if ($customer->pep_status) {
            $cddTriggers[] = 'PEP customer';
        }
        if ($this->mathService->compare($amountLocal, '50000') >= 0) {
            $cddTriggers[] = 'Large amount >= RM 50,000';
        } elseif ($this->mathService->compare($amountLocal, '3000') >= 0) {
            $cddTriggers[] = 'Standard amount >= RM 3,000';
        }
        if ($customer->risk_rating === 'High') {
            $cddTriggers[] = 'High risk customer';
        }

        $this->auditService->logWithSeverity(
            'cdd_decision',
            [
                'user_id' => $userId,
                'entity_type' => 'Transaction',
                'new_values' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->full_name,
                    'cdd_level' => $cddLevel->value,
                    'triggers' => $cddTriggers,
                    'amount_local' => $amountLocal,
                ],
            ],
            'INFO'
        );

        // Determine initial status
        $status = TransactionStatus::Completed;
        $holdReason = null;
        $approvedBy = null;

        if ($holdCheck['requires_hold']) {
            if ($this->mathService->compare($amountLocal, '50000') >= 0) {
                $status = TransactionStatus::Pending;
                $holdReason = ComplianceFlagType::EddRequired->label().': '.implode(', ', $holdCheck['reasons']);
            } else {
                $status = TransactionStatus::OnHold;
                $holdReason = implode(', ', $holdCheck['reasons']);
            }
        }

        return DB::transaction(function () use ($data, $userId, $tillBalance, $amountForeign, $rate, $amountLocal, $cddLevel, $status, $holdReason, $approvedBy) {
            // Check for duplicate transaction via idempotency key (inside transaction to prevent race)
            if (! empty($data['idempotency_key'])) {
                $existingByKey = Transaction::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existingByKey) {
                    return $existingByKey;
                }
            }

            // Check for recent similar transaction (potential double-submit)
            $recentWindow = now()->subSeconds(30);
            $recentAmount = Transaction::where('user_id', $userId)
                ->where('created_at', '>=', $recentWindow)
                ->where('amount_foreign', $data['amount_foreign'])
                ->where('currency_code', $data['currency_code'])
                ->where('type', $data['type'])
                ->first();

            if ($recentAmount) {
                $this->auditService->logWithSeverity(
                    'potential_duplicate_detected',
                    [
                        'user_id' => $userId,
                        'entity_type' => 'Transaction',
                        'entity_id' => $recentAmount->id,
                        'description' => "Similar transaction {$recentAmount->id} found within 30 seconds",
                    ],
                    'WARNING'
                );

                throw new \InvalidArgumentException('Potential duplicate transaction detected. Please wait 30 seconds before submitting again or check your recent transactions.');
            }

            // For Sell transactions, verify sufficient stock WITH LOCK to prevent race conditions
            if ($data['type'] === TransactionType::Sell->value) {
                $position = $this->positionService->getPositionWithLock($data['currency_code'], $data['till_id']);
                if (! $position || $this->mathService->compare($position->balance, $amountForeign) < 0) {
                    $availableBalance = $position ? $position->balance : '0';
                    throw new \InvalidArgumentException("Insufficient stock. Available: {$availableBalance} {$data['currency_code']}");
                }
            }
            $transaction = Transaction::create([
                'customer_id' => $data['customer_id'],
                'user_id' => $userId,
                'branch_id' => $tillBalance->branch_id,
                'till_id' => $data['till_id'],
                'type' => $data['type'],
                'currency_code' => $data['currency_code'],
                'amount_foreign' => $amountForeign,
                'amount_local' => $amountLocal,
                'rate' => $rate,
                'purpose' => $data['purpose'],
                'source_of_funds' => $data['source_of_funds'],
                'status' => $status,
                'hold_reason' => $holdReason,
                'approved_by' => $approvedBy,
                'cdd_level' => $cddLevel,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'version' => 0,
            ]);

            // If completed, update positions, till balance, and create accounting entries
            if ($status === TransactionStatus::Completed) {
                $this->positionService->updatePosition(
                    $data['currency_code'],
                    $amountForeign,
                    $rate,
                    $data['type'],
                    $data['till_id']
                );
                $this->updateTillBalance($tillBalance, $data['type'], $amountLocal, $amountForeign);
                $this->createAccountingEntries($transaction);
            }

            $this->auditService->logWithSeverity(
                'transaction_created',
                [
                    'user_id' => $userId,
                    'entity_type' => 'Transaction',
                    'entity_id' => $transaction->id,
                    'new_values' => [
                        'type' => $transaction->type,
                        'amount_local' => $transaction->amount_local,
                        'amount_foreign' => $transaction->amount_foreign,
                        'currency' => $transaction->currency_code,
                        'status' => $transaction->status,
                        'cdd_level' => $cddLevel,
                    ],
                ],
                'INFO'
            );

            // Generate CTOS report if transaction qualifies (>= RM 10,000 cash transaction)
            if ($this->ctosReportService->qualifiesForCtos($transaction)) {
                $this->ctosReportService->createFromTransaction($transaction, $userId);
            }

            // Dispatch event for async processing
            Event::dispatch(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    /**
     * Update till balance after transaction.
     * Uses lockForUpdate to prevent race conditions on concurrent transactions.
     */
    protected function updateTillBalance(TillBalance $tillBalance, string $type, string $amountLocal, string $amountForeign): void
    {
        // Re-fetch with lock to prevent race conditions
        $lockedBalance = TillBalance::where('id', $tillBalance->id)
            ->lockForUpdate()
            ->first();

        $currentTotal = $lockedBalance->transaction_total ?? '0';
        $foreignTotal = $lockedBalance->foreign_total ?? '0';

        if ($type === TransactionType::Buy->value) {
            $lockedBalance->update([
                'transaction_total' => $this->mathService->add($currentTotal, $amountLocal),
                'foreign_total' => $this->mathService->add($foreignTotal, $amountForeign),
            ]);
        } else {
            $lockedBalance->update([
                'transaction_total' => $this->mathService->add($currentTotal, $amountLocal),
                'foreign_total' => $this->mathService->subtract($foreignTotal, $amountForeign),
            ]);
        }
    }

    /**
     * Create accounting journal entries for transaction.
     */
    protected function createAccountingEntries(Transaction $transaction): void
    {
        $entries = [];

        if ($transaction->type->isBuy()) {
            $entries = [
                [
                    'account_code' => \App\Enums\AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Buy {$transaction->amount_foreign} {$transaction->currency_code} @ {$transaction->rate}",
                ],
                [
                    'account_code' => \App\Enums\AccountCode::CASH_MYR->value,
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Payment for {$transaction->currency_code} purchase",
                ],
            ];
        } else {
            $position = $this->positionService->getPosition($transaction->currency_code, $transaction->till_id);
            $avgCost = $position ? $position->avg_cost_rate : $transaction->rate;
            $costBasis = $this->mathService->multiply((string) $transaction->amount_foreign, $avgCost);
            $revenue = $this->mathService->subtract((string) $transaction->amount_local, $costBasis);
            $isGain = $this->mathService->compare($revenue, '0') >= 0;

            $entries = [
                [
                    'account_code' => \App\Enums\AccountCode::CASH_MYR->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Sale of {$transaction->amount_foreign} {$transaction->currency_code}",
                ],
                [
                    'account_code' => \App\Enums\AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => '0',
                    'credit' => $costBasis,
                    'description' => "Cost of {$transaction->currency_code} sold",
                ],
            ];

            if ($isGain) {
                $entries[] = [
                    'account_code' => \App\Enums\AccountCode::FOREX_TRADING_REVENUE->value,
                    'debit' => '0',
                    'credit' => $revenue,
                    'description' => "Gain on {$transaction->currency_code} sale",
                ];
            } else {
                $entries[] = [
                    'account_code' => \App\Enums\AccountCode::FOREX_LOSS->value,
                    'debit' => $this->mathService->multiply($revenue, '-1'),
                    'credit' => '0',
                    'description' => "Loss on {$transaction->currency_code} sale",
                ];
            }
        }

        $this->accountingService->createJournalEntry(
            $entries,
            'Transaction',
            $transaction->id,
            "Transaction #{$transaction->id} - {$transaction->type->value} {$transaction->currency_code}"
        );
    }

    /**
     * Approve a pending transaction and complete its side effects.
     *
     * This method handles the full approval workflow for transactions that were
     * created with 'Pending' status (typically >= RM 50,000).
     *
     * @param  Transaction  $transaction  The pending transaction to approve
     * @param  int  $approverId  The user ID of the manager/admin approving
     * @param  string|null  $ipAddress  IP address for audit logging
     * @return array{success: bool, message: string, transaction?: Transaction}
     *
     * @throws \InvalidArgumentException If transaction is not pending
     * @throws \RuntimeException If transaction was already processed
     */
    public function approveTransaction(Transaction $transaction, int $approverId, ?string $ipAddress = null): array
    {
        $ipAddress = $ipAddress ?? request()->ip();

        // Validate IP address format
        if ($ipAddress && ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address format.');
        }

        // Validate transaction is in pending status
        if ($transaction->status !== TransactionStatus::Pending) {
            throw new \InvalidArgumentException(
                'Transaction is not pending approval. Current status: '.$transaction->status->label()
            );
        }

        // Re-run compliance monitoring before approval
        // If high-priority AML flags are generated, approval is blocked
        $amlResult = $this->monitoringService->monitorTransaction($transaction);
        $highPriorityFlags = array_filter(
            $amlResult['flags'],
            fn ($flag) => $flag->flag_type->isHighPriority()
        );

        if (! empty($highPriorityFlags)) {
            $flagTypes = implode(', ', array_map(
                fn ($f) => $f->flag_type->label(),
                $highPriorityFlags
            ));

            $this->auditService->logWithSeverity(
                'transaction_approval_blocked',
                [
                    'user_id' => $approverId,
                    'entity_type' => 'Transaction',
                    'entity_id' => $transaction->id,
                    'new_values' => [
                        'reason' => 'High-priority AML flags',
                        'flags' => $flagTypes,
                    ],
                ],
                'WARNING'
            );

            return [
                'success' => false,
                'message' => "Approval blocked: High-priority AML flags generated ({$flagTypes}). Transaction remains pending for compliance review.",
            ];
        }

        return DB::transaction(function () use ($transaction, $approverId, $amlResult) {
            // Optimistic locking: Prevent race conditions
            $updated = Transaction::where('id', $transaction->id)
                ->where('status', TransactionStatus::Pending)
                ->where('version', $transaction->version)
                ->update([
                    'status' => TransactionStatus::Completed,
                    'approved_by' => $approverId,
                    'approved_at' => now(),
                    'version' => DB::raw('version + 1'),
                ]);

            if (! $updated) {
                throw new \RuntimeException(
                    'Transaction was already processed or modified by another user.'
                );
            }

            // Refresh the model to get updated version
            $transaction->refresh();

            // Get the till balance for today
            $tillBalance = TillBalance::where('till_id', $transaction->till_id ?? 'MAIN')
                ->where('currency_code', $transaction->currency_code)
                ->whereDate('date', today())
                ->whereNull('closed_at')
                ->first();

            if (! $tillBalance) {
                throw new \RuntimeException(
                    'Till balance not found for today. Cannot complete transaction.'
                );
            }

            // Execute position and till balance updates
            $this->positionService->updatePosition(
                $transaction->currency_code,
                (string) $transaction->amount_foreign,
                (string) $transaction->rate,
                $transaction->type->value,
                $transaction->till_id ?? 'MAIN'
            );
            $this->updateTillBalance(
                $tillBalance,
                $transaction->type->value,
                (string) $transaction->amount_local,
                (string) $transaction->amount_foreign
            );

            // Create double-entry accounting journal entries
            $this->createAccountingEntries($transaction);

            // Audit logging for the approval action
            $this->auditService->logTransaction('transaction_approved', $transaction->id, [
                'old' => [
                    'status' => TransactionStatus::Pending->value,
                    'approved_by' => null,
                ],
                'new' => [
                    'status' => TransactionStatus::Completed->value,
                    'approved_by' => $approverId,
                    'approved_at' => $transaction->approved_at->toIso8601String(),
                    'aml_flags_checked' => $amlResult['flags_created'] ?? 0,
                ],
            ]);

            // Dispatch event for async compliance processing
            Event::dispatch(new TransactionApproved($transaction));

            return [
                'success' => true,
                'message' => 'Transaction approved and completed successfully.',
                'transaction' => $transaction->fresh(),
            ];
        });
    }
}
