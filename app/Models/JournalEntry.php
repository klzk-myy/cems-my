<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AccountLedger::class);
    }

    public function isPosted(): bool
    {
        return $this->status === 'Posted';
    }

    public function isReversed(): bool
    {
        return $this->status === 'Reversed';
    }

    public function getTotalDebits(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCredits(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->getTotalDebits() - $this->getTotalCredits()) < 0.0001;
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }
}
