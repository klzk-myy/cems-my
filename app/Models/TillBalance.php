<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TillBalance extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'till_id',
        'currency_code',
        'opening_balance',
        'closing_balance',
        'variance',
        'date',
        'opened_by',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'closing_balance' => 'decimal:4',
        'variance' => 'decimal:4',
        'date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Calculate variance based on closing and opening balance
     */
    public function calculateVariance(): float
    {
        if ($this->closing_balance === null) {
            return 0.0;
        }
        return (float) $this->closing_balance - (float) $this->opening_balance;
    }

    /**
     * Check if variance exceeds threshold
     */
    public function hasSignificantVariance(float $threshold = 100.00): bool
    {
        return abs($this->calculateVariance()) > $threshold;
    }

    /**
     * Scope for today's balances
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope for open tills (not yet closed)
     */
    public function scopeOpen($query)
    {
        return $query->whereNull('closed_at');
    }

    /**
     * Scope for closed tills
     */
    public function scopeClosed($query)
    {
        return $query->whereNotNull('closed_at');
    }
}
