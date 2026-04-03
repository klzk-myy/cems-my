<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'till_id',
        'type',
        'currency_code',
        'amount_local',
        'amount_foreign',
        'rate',
        'purpose',
        'source_of_funds',
        'status',
        'hold_reason',
        'approved_by',
        'approved_at',
        'cdd_level',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'original_transaction_id',
        'is_refund',
        'idempotency_key',
        'version',
    ];

    protected $casts = [
        'amount_local' => 'decimal:4',
        'amount_foreign' => 'decimal:4',
        'rate' => 'decimal:6',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    public function flags()
    {
        return $this->hasMany(FlaggedTransaction::class);
    }

    public function isRefundable(): bool
    {
        // Must be completed
        if ($this->status !== 'Completed') {
            return false;
        }

        // Cannot be already cancelled
        if ($this->cancelled_at !== null) {
            return false;
        }

        // Must be within 24 hours
        if ($this->created_at->diffInHours(now()) > 24) {
            return false;
        }

        // Cannot be a refund
        if ($this->is_refund) {
            return false;
        }

        return true;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function refundTransaction()
    {
        return $this->hasOne(Transaction::class, 'original_transaction_id');
    }

    public function originalTransaction()
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
