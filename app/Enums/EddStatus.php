<?php

namespace App\Enums;

enum EddStatus: string
{
    case Incomplete = 'Incomplete';
    case PendingReview = 'Pending_Review';
    case Approved = 'Approved';
    case Rejected = 'Rejected';

    public function label(): string
    {
        return match($this) {
            self::Incomplete => 'Incomplete',
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Incomplete => 'secondary',
            self::PendingReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
