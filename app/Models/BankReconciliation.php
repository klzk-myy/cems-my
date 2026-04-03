<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'statement_date',
        'reference',
        'description',
        'debit',
        'credit',
        'status',
        'matched_to_journal_entry_id',
        'created_by',
        'matched_at',
        'notes',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'matched_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
    }

    public function matchedEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'matched_to_journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUnmatched($query)
    {
        return $query->where('status', 'unmatched');
    }

    public function scopeExceptions($query)
    {
        return $query->where('status', 'exception');
    }

    public function getAmount(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }
}
