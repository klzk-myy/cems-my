<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
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
        'cdd_level'
    ];

    protected $casts = [
        'amount_local' => 'decimal:4',
        'amount_foreign' => 'decimal:4',
        'rate' => 'decimal:6',
        'approved_at' => 'datetime',
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
}
