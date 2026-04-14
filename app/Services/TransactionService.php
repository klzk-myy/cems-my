<?php

namespace App\Services;

use App\Enums\CddLevel;
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
use Illuminate\Support\Facades\Log;

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
     * Validate currency code exists in system.
     *
     * @param  string  $currencyCode  Currency code to validate
     *
     * @throws \InvalidArgumentException If currency code is invalid
     */
    protected function validateCurrencyCode(string $currencyCode): void
    {
        $currency = \App\Models\Currency::where('code', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (! $currency) {
            throw new \InvalidArgumentException("Invalid or inactive currency code: {$currencyCode}");
        }
    }

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
        $this->validateCurrencyCode($data['currency_code']);

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
        if ($this->mathService->compare($amountLocal, $this->complianceService::LARGE_TRANSACTION_THRESHOLD) >= 0) {
            $cddTriggers[] = 'Large amount >= RM '.number_format((float) $this->complianceService::LARGE_TRANSACTION_THRESHOLD);
        } elseif ($this->mathService->compare($amountLocal, $this->complianceService::STANDARD_CDD_THRESHOLD) >= 0) {
            $cddTriggers[] = 'Standard amount >= RM '.number_format((float) $this->complianceService::STANDARD_CDD_THRESHOLD);
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
            if ($this->mathService->compare($amountLocal, $this->complianceService::LARGE_TRANSACTION_THRESHOLD) >= 0) {
                $status = TransactionStatus::Pending;
                $holdReason = ComplianceFlagType::EddRequired->label().': '.implode(', ', $holdCheck['reasons']);
            } else {
                $status = TransactionStatus::OnHold;
                $holdReason = implode(', ', $holdCheck['reasons']);
            }
        }

        return DB::transaction(function () use ($data, $userId, $tillBalance, $amountForeign, $rate, $amountLocal, $cddLevel, $status, $holdReason, $approvedBy) {
            // For Sell transactions, acquire position lock FIRST to prevent race conditions
            // where two concurrent transactions could both pass the duplicate check
            // before either acquires the lock
            $position = null;
            if ($data['type'] === TransactionType::Sell->value) {
                $position = $this->positionService->getPositionWithLock($data['currency_code'], $data['till_id']);

                // Verify sufficient stock for Sell transactions IMMEDIATELY after acquiring lock
                // This prevents race conditions where another transaction could modify the position
                if (! $position || $this->mathService->compare($position->balance, $amountForeign) < 0) {
                    $availableBalance = $position ? $position->balance : '0';
                    throw new \InvalidArgumentException("Insufficient stock. Available: {$availableBalance} {$data['currency_code']}");
                }
            }

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
                try {
                    $this->ctosReportService->createFromTransaction($transaction, $userId);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('CTOS report creation failed', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->auditService->logWithSeverity(
                        'ctos_report_creation_failed',
                        [
                            'entity_type' => 'Transaction',
                            'entity_id' => $transaction->id,
                            'new_values' => [
                                'error' => $e->getMessage(),
                                'requires_manual_submission' => true,
                            ],
                        ],
                        'WARNING'
                    );
                }
            }

            // Dispatch event for async processing
            Event::dispatch(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    /**
     * Verify till is still open for operations.
     *
     * @param  TillBalance  $tillBalance  The till balance to verify
     *
     * @throws \InvalidArgumentException If till is closed
     */
    protected function verifyTillIsOpen(TillBalance $tillBalance): void
    {
        if ($tillBalance->closed_at !== null) {
            throw new \InvalidArgumentException('Till is closed. Cannot perform operations on closed till.');
        }
    }

    /**
     * Update till balance after transaction.
     * Uses lockForUpdate to prevent race conditions on concurrent transactions.
     */
    protected function updateTillBalance(TillBalance $tillBalance, string $type, string $amountLocal, string $amountForeign): void
    {
        $this->verifyTillIsOpen($tillBalance);

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
     * For Enhanced CDD transactions, defers creation until approval.
     */
    protected function createAccountingEntries(Transaction $transaction): void
    {
        // Check if Enhanced CDD and not yet approved (status is PendingApproval)
        if ($transaction->cdd_level === CddLevel::Enhanced
            && $transaction->status !== TransactionStatus::Completed) {
            Log::info('Deferring journal entry creation for Enhanced CDD transaction', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status->value,
                'cdd_level' => $transaction->cdd_level->value,
            ]);

            $this->auditService->logTransaction('journal_entries_deferred', $transaction->id, [
                'cdd_level' => $transaction->cdd_level->value,
                'status' => $transaction->status->value,
                'reason' => 'Enhanced CDD requires approval before bookkeeping',
            ]);

            return;
        }

        // Create entries immediately for Simplified/Standard CDD or approved Enhanced CDD
        $this->createImmediateAccountingEntries($transaction);
    }

    /**
     * Create deferred journal entries for Enhanced CDD transactions.
     * Called when transaction is approved.
     */
    public function createDeferredAccountingEntries(int $transactionId): void
    {
        $transaction = Transaction::findOrFail($transactionId);

        // Verify it's Enhanced CDD
        if ($transaction->cdd_level !== CddLevel::Enhanced) {
            throw new \InvalidArgumentException('Only Enhanced CDD transactions support deferred entries');
        }

        // Verify it's completed (approved)
        if ($transaction->status !== TransactionStatus::Completed) {
            throw new \InvalidArgumentException('Transaction must be completed to create journal entries');
        }

        // Verify entries weren't already created
        if ($transaction->journal_entry_id !== null) {
            Log::info('Journal entries already exist for transaction', [
                'transaction_id' => $transactionId,
                'journal_entry_id' => $transaction->journal_entry_id,
            ]);

            return;
        }

        // Create the entries
        $this->createImmediateAccountingEntries($transaction);

        // Update tracking fields
        $transaction->journal_entries_created_at = now();
        $transaction->save();

        $this->auditService->logTransaction('deferred_journal_entries_created', $transaction->id, [
            'transaction_id' => $transaction->id,
            'journal_entry_id' => $transaction->journal_entry_id,
            'deferred_until' => now(),
            'approver_id' => $transaction->approved_by,
        ]);
    }

    /**
     * Create accounting journal entries immediately.
     */
    protected function createImmediateAccountingEntries(Transaction $transaction): void
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
            // Optimistic locking with pessimistic lock to prevent race conditions
            $lockedTransaction = Transaction::where('id', $transaction->id)
                ->where('status', TransactionStatus::Pending)
                ->where('version', $transaction->version)
                ->lockForUpdate()
                ->first();

            if (! $lockedTransaction) {
                throw new \RuntimeException(
                    'Transaction was already processed or modified by another user.'
                );
            }

            // Update the locked transaction
            $lockedTransaction->update([
                'status' => TransactionStatus::Completed,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'version' => $lockedTransaction->version + 1,
            ]);

            // Refresh the model to get updated version
            $lockedTransaction->refresh();

            // Get the till balance for today
            $tillBalance = TillBalance::where('till_id', $lockedTransaction->till_id ?? 'MAIN')
                ->where('currency_code', $lockedTransaction->currency_code)
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
                $lockedTransaction->currency_code,
                (string) $lockedTransaction->amount_foreign,
                (string) $lockedTransaction->rate,
                $lockedTransaction->type->value,
                $lockedTransaction->till_id ?? 'MAIN'
            );
            $this->updateTillBalance(
                $tillBalance,
                $lockedTransaction->type->value,
                (string) $lockedTransaction->amount_local,
                (string) $lockedTransaction->amount_foreign
            );

            // Create double-entry accounting journal entries
            $this->createAccountingEntries($lockedTransaction);

            // Audit logging for the approval action
            $this->auditService->logTransaction('transaction_approved', $lockedTransaction->id, [
                'old' => [
                    'status' => TransactionStatus::Pending->value,
                    'approved_by' => null,
                ],
                'new' => [
                    'status' => TransactionStatus::Completed->value,
                    'approved_by' => $approverId,
                    'approved_at' => $lockedTransaction->approved_at->toIso8601String(),
                    'aml_flags_checked' => $amlResult['flags_created'] ?? 0,
                ],
            ]);

            // Dispatch event for async compliance processing
            Event::dispatch(new TransactionApproved($lockedTransaction));

            return [
                'success' => true,
                'message' => 'Transaction approved and completed successfully.',
                'transaction' => $lockedTransaction->fresh(),
            ];
        });
    }
}
