<?php

namespace App\Modules\Pos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosDailyRate extends Model
{
    use HasFactory;

    protected $table = 'pos_daily_rates';

    protected $fillable = [
        'rate_date',
        'currency_code',
        'buy_rate',
        'sell_rate',
        'mid_rate',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'buy_rate' => 'decimal:6',
        'sell_rate' => 'decimal:6',
        'mid_rate' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('rate_date', $date);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCurrency($query, $currencyCode)
    {
        return $query->where('currency_code', $currencyCode);
    }
}
