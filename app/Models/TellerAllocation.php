<?php

namespace App\Models;

use App\Enums\TellerAllocationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TellerAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'counter_id',
        'currency_code',
        'allocated_amount',
        'current_balance',
        'requested_amount',
        'daily_limit_myr',
        'daily_used_myr',
        'status',
        'session_date',
        'approved_by',
        'approved_at',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'requested_amount' => 'decimal:4',
        'daily_limit_myr' => 'decimal:4',
        'daily_used_myr' => 'decimal:4',
        'session_date' => 'date',
        'approved_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'status' => TellerAllocationStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isReturned(): bool
    {
        return $this->status->isReturned();
    }

    public function hasAvailable($amount): bool
    {
        return bccomp($this->current_balance, $amount, 4) >= 0;
    }

    public function deduct($amount): void
    {
        $this->current_balance = bcsub($this->current_balance, $amount, 4);
        $this->save();
    }

    public function add($amount): void
    {
        $this->current_balance = bcadd($this->current_balance, $amount, 4);
        $this->save();
    }

    public function addDailyUsed($amountMyr): void
    {
        $this->daily_used_myr = bcadd($this->daily_used_myr, $amountMyr, 4);
        $this->save();
    }

    public function hasDailyLimitRemaining($amountMyr): bool
    {
        if ($this->daily_limit_myr === null) {
            return true;
        }

        $remaining = bcsub($this->daily_limit_myr, $this->daily_used_myr, 4);

        return bccomp($remaining, $amountMyr, 4) >= 0;
    }

    public function approve(User $approver, $allocatedAmount, $dailyLimitMyr = null): void
    {
        $this->update([
            'status' => TellerAllocationStatus::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'allocated_amount' => $allocatedAmount,
            'current_balance' => $allocatedAmount,
            'daily_limit_myr' => $dailyLimitMyr,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => TellerAllocationStatus::Active,
            'opened_at' => now(),
        ]);
    }

    public function returnToPool(): void
    {
        $this->update([
            'status' => TellerAllocationStatus::Returned,
            'closed_at' => now(),
        ]);
    }

    public function forceReturn(): void
    {
        $this->update([
            'status' => TellerAllocationStatus::AutoReturned,
            'closed_at' => now(),
        ]);
    }
}
