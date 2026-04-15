<?php

namespace App\Enums;

enum TellerAllocationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ACTIVE = 'active';
    case RETURNED = 'returned';
    case CLOSED = 'closed';
    case AUTO_RETURNED = 'auto_returned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::ACTIVE => 'Active',
            self::RETURNED => 'Returned',
            self::CLOSED => 'Closed',
            self::AUTO_RETURNED => 'Auto Returned',
        };
    }
}
