<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'account_code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'account_code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_code', 'account_code');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AccountLedger::class, 'account_code', 'account_code');
    }

    public function isAsset(): bool
    {
        return $this->account_type === 'Asset';
    }

    public function isLiability(): bool
    {
        return $this->account_type === 'Liability';
    }

    public function isEquity(): bool
    {
        return $this->account_type === 'Equity';
    }

    public function isRevenue(): bool
    {
        return $this->account_type === 'Revenue';
    }

    public function isExpense(): bool
    {
        return $this->account_type === 'Expense';
    }
}
