<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'currency_code',
        'quantity',
        'rate',
        'value_myr',
        'quantity_received',
        'quantity_in_transit',
        'variance_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'rate' => 'decimal:6',
        'value_myr' => 'decimal:2',
        'quantity_received' => 'decimal:4',
        'quantity_in_transit' => 'decimal:4',
    ];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function isFullyReceived(): bool
    {
        return bccomp($this->quantity_received, $this->quantity, 4) >= 0;
    }

    public function hasVariance(): bool
    {
        return bccomp($this->quantity_received, $this->quantity, 4) !== 0;
    }

    public function getVarianceAttribute(): string
    {
        return bcsub($this->quantity_received, $this->quantity, 4);
    }
}
