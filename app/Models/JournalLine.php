<?php

namespace App\Models;

use App\Services\MathService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_code',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
    }

    public function isDebit(): bool
    {
        $mathService = new MathService;

        return $mathService->compare((string) $this->debit, '0') > 0;
    }

    public function isCredit(): bool
    {
        $mathService = new MathService;

        return $mathService->compare((string) $this->credit, '0') > 0;
    }

    public function getAmount(): string
    {
        $mathService = new MathService;
        if ($mathService->compare((string) $this->debit, '0') > 0) {
            return (string) $this->debit;
        }

        return (string) $this->credit;
    }
}
