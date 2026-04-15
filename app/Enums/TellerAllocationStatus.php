<?php

namespace App\Enums;

enum TellerAllocationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Active = 'active';
    case Returned = 'returned';
    case Closed = 'closed';
    case AutoReturned = 'auto_returned';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isReturned(): bool
    {
        return $this === self::Returned;
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }

    public function isAutoReturned(): bool
    {
        return $this === self::AutoReturned;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::Returned => 'Returned',
            self::Closed => 'Closed',
            self::AutoReturned => 'Auto Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Active => 'success',
            self::Returned => 'primary',
            self::Closed => 'secondary',
            self::AutoReturned => 'danger',
        };
    }
}
