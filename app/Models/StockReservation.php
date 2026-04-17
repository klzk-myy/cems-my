<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'transaction_id',
        'currency_code',
        'till_id',
        'amount_foreign',
        'status',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'amount_foreign' => 'string',
        'expires_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConsumed(): bool
    {
        return $this->status === self::STATUS_CONSUMED;
    }

    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    public function isExpired(): bool
    {
        return $this->isPending() && $this->expires_at->isPast();
    }
}
