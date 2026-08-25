<?php

namespace App\Models;

use App\Enums\ComplianceFlagType;
use App\Enums\FlagStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlaggedTransaction extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'flag_type',
        'flag_reason',
        'severity',
        'status',
        'assigned_to',
        'reviewed_by',
        'notes',
        'resolved_at',
        'customer_id',
    ];

    protected $casts = [
        'flag_type' => ComplianceFlagType::class,
        'status' => FlagStatus::class,
        'resolved_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to filter open (unresolved) flags.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', FlagStatus::Open);
    }

    /**
     * Scope to filter high priority flags.
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('flag_type', [
            ComplianceFlagType::SanctionMatch,
            ComplianceFlagType::Structuring,
            ComplianceFlagType::Velocity,
        ])->where('status', '!=', FlagStatus::Resolved);
    }
}
