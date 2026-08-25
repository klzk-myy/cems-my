<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditTrail extends BaseModel
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'user_id',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'auditable_id' => 'integer',
        'user_id' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAuditableType(Builder $query, string $type): Builder
    {
        return $query->where('auditable_type', $type);
    }

    public function scopeSince(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '>=', $date);
    }
}
