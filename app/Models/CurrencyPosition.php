<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyPosition extends Model
{
    protected $fillable = [
        'currency_code',
        'till_id',
        'balance',
        'avg_cost_rate',
        'last_valuation_rate',
        'unrealized_pnl',
        'last_valuation_at',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'avg_cost_rate' => 'decimal:6',
        'last_valuation_rate' => 'decimal:6',
        'unrealized_pnl' => 'decimal:4',
        'last_valuation_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }
}
