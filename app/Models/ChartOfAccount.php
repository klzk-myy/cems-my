<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chart of Account model for managing the accounting chart of accounts hierarchy.
 *
 * Represents the hierarchical structure of accounts used in the accounting system,
 * including parent-child relationships and account classifications by type.
 *
 * @property string $account_code Primary key for the account
 * @property string $account_name Human-readable name of the account
 * @property string $account_type Type of account (Asset, Liability, Equity, Revenue, Expense)
 * @property string|null $parent_code Reference to parent account in hierarchy
 * @property bool $is_active Whether the account is active
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when record was created
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when record was last updated
 * @property-read ChartOfAccount|null $parent The parent account in the hierarchy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ChartOfAccount> $children Child accounts in the hierarchy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JournalLine> $journalLines Journal lines associated with this account
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AccountLedger> $ledgerEntries Ledger entries associated with this account
 */
class ChartOfAccount extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent account in the hierarchy.
     *
     * @return BelongsTo The parent account relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'account_code');
    }

    /**
     * Get the child accounts in the hierarchy.
     *
     * @return HasMany The child accounts relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'account_code');
    }

    /**
     * Get the journal lines associated with this account.
     *
     * @return HasMany The journal lines relationship
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_code', 'account_code');
    }

    /**
     * Get the ledger entries associated with this account.
     *
     * @return HasMany The ledger entries relationship
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AccountLedger::class, 'account_code', 'account_code');
    }

    /**
     * Determine if this account is an Asset type.
     *
     * @return bool True if account type is Asset, false otherwise
     */
    public function isAsset(): bool
    {
        return $this->account_type === 'Asset';
    }

    /**
     * Determine if this account is a Liability type.
     *
     * @return bool True if account type is Liability, false otherwise
     */
    public function isLiability(): bool
    {
        return $this->account_type === 'Liability';
    }

    /**
     * Determine if this account is an Equity type.
     *
     * @return bool True if account type is Equity, false otherwise
     */
    public function isEquity(): bool
    {
        return $this->account_type === 'Equity';
    }

    /**
     * Determine if this account is a Revenue type.
     *
     * @return bool True if account type is Revenue, false otherwise
     */
    public function isRevenue(): bool
    {
        return $this->account_type === 'Revenue';
    }

    /**
     * Determine if this account is an Expense type.
     *
     * @return bool True if account type is Expense, false otherwise
     */
    public function isExpense(): bool
    {
        return $this->account_type === 'Expense';
    }
}
