<?php

namespace App\Models;

use App\Services\MathService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLedger extends Model
{
    use HasFactory;

    protected $table = 'account_ledger';

    protected $fillable = [
        'account_code',
        'entry_date',
        'journal_entry_id',
        'debit',
        'credit',
        'running_balance',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'running_balance' => 'decimal:4',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function getNetAmount(): string
    {
        $mathService = new MathService;

        return $mathService->subtract((string) $this->debit, (string) $this->credit);
    }
}
