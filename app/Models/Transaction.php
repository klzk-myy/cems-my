<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CddLevel;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Bases\TransactionModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Transaction Model
 *
 * Represents foreign currency buy/sell transactions in the CEMS-MY system.
 * Supports compliance monitoring, approval workflows, and refund operations.
 *
 * @property int $id
 * @property int|null $customer_id
 * @property int $user_id
 * @property string $till_id
 * @property TransactionType $type
 * @property string $currency_code
 * @property string|null $counterparty_country ISO 3-letter country code
 * @property string $amount_local MYR amount
 * @property string $amount_foreign Foreign currency amount
 * @property string $rate Exchange rate applied
 * @property string|null $purpose Transaction purpose
 * @property string|null $source_of_funds Source of funds
 * @property string|null $source_of_wealth Source of wealth (required for PEPs per pd-00.md 14C.13.1(c))
 * @property TransactionStatus $status
 * @property string|null $hold_reason Reason for hold status
 * @property int|null $approved_by User ID who approved
 * @property Carbon|null $approved_at
 * @property CddLevel $cdd_level
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property int|null $original_transaction_id For refunds
 * @property bool $is_refund
 * @property string|null $idempotency_key Duplicate prevention
 * @property int $version Optimistic locking
 * @property array|null $transition_history State machine transition history
 * @property string|null $failure_reason Reason for failed status
 * @property string|null $rejection_reason Reason for rejected status
 * @property string|null $reversal_reason Reason for reversed status
 */
class Transaction extends TransactionModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     *
     * SECURITY NOTE: Only user-controllable fields are fillable.
     * System-managed fields (approvals, journal entries, sync status, etc.)
     * are set explicitly by service layer methods, not mass assignment.
     */
    protected $fillable = [
        'customer_id',
        'currency_code',
        'counter_id',
        'till_id',
        'type',
        'counterparty_country',
        'amount_local',
        'amount_foreign',
        'rate',
        'purpose',
        'source_of_funds',
        'source_of_wealth',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Transaction $transaction) {
            // Free the unique idempotency_key when a soft delete occurs so a
            // retried operation presenting the same key can proceed. Assigned
            // quietly so the delete flow is not re-triggered.
            if (! $transaction->isForceDeleting() && $transaction->idempotency_key !== null) {
                $transaction->idempotency_key = null;
                $transaction->saveQuietly();
            }
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount_local' => MoneyCast::class,
        'amount_foreign' => MoneyCast::class,
        'rate' => MoneyCast::class.':6',
        'base_rate' => MoneyCast::class.':6',
        'rate_override' => 'boolean',
        'is_refund' => 'boolean',
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'cdd_level' => CddLevel::class,
        'cancelled_at' => 'datetime',
        'rate_override_approved_at' => 'datetime',
        'transition_history' => 'array',
        'journal_entries_created_at' => 'datetime',
        'has_deferred_accounting' => 'boolean',
        'customer_id' => 'integer',
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'approval_sync_failed' => 'boolean',
        'approval_sync_failed_at' => 'datetime',
        'version' => 'integer',
        'hold_reason' => 'string',
        'cancelled_by' => 'integer',
        'cancellation_reason' => 'string',
        'is_dlq' => 'boolean',
    ];

    /**
     * Human-readable transaction reference.
     *
     * There is no `reference` column in the transactions table; the reference is
     * derived from the row id so it is stable, unique, and searchable by number.
     */
    public function getReferenceAttribute(): string
    {
        return 'TX-'.str_pad((string) $this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Get the status badge variant for UI display.
     */
    public function getStatusVariantAttribute(): string
    {
        return match ($this->status?->value) {
            'Completed' => 'success',
            'Pending', 'PendingApproval' => 'warning',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Completed->value);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::PendingApproval->value);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', TransactionStatus::Cancelled->value);
    }

    public function scopeForDateRange(Builder $query, string $from, string $to): Builder
    {
        // Range predicate on the raw column (not DATE(created_at)) so an index
        // on created_at can be used on the transactions table. Carbon::parse
        // preserves the previous whereDate semantics even when $from/$to carry
        // a time component, without any string concatenation.
        return $query->whereBetween('created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ]);
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function scopeBuy(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Buy->value);
    }

    public function scopeSell(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Sell->value);
    }

    protected function activeStatusValues(): array
    {
        return [
            TransactionStatus::Approved->value,
            TransactionStatus::Processing->value,
            TransactionStatus::Completed->value,
            TransactionStatus::Finalized->value,
        ];
    }

    protected function openStatusValues(): array
    {
        return [
            TransactionStatus::Draft->value,
            TransactionStatus::PendingApproval->value,
            TransactionStatus::Pending->value,
            TransactionStatus::OnHold->value,
            TransactionStatus::PendingCancellation->value,
        ];
    }

    /**
     * Get all flagged transactions related to this transaction.
     */
    public function flags(): HasMany
    {
        return $this->hasMany(FlaggedTransaction::class);
    }

    /**
     * Get stock reservations for this transaction.
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * Get the refund transaction if this transaction was refunded.
     */
    public function refundTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'original_transaction_id');
    }

    /**
     * Get the original transaction if this is a refund.
     */
    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    /**
     * Get the user who cancelled this transaction.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get all transaction errors for this transaction.
     */
    public function transactionErrors(): HasMany
    {
        return $this->hasMany(TransactionError::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function deferredJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'deferred_journal_entry_id');
    }
}
