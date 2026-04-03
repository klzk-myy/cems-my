<?php

namespace App\Enums;

/**
 * Transaction Status Enum
 *
 * Represents the various statuses a transaction can have in its lifecycle.
 */
enum TransactionStatus: string
{
    case Pending = 'Pending';
    case Completed = 'Completed';
    case OnHold = 'OnHold';
    case Cancelled = 'Cancelled';

    /**
     * Check if the transaction is in a final state.
     * Final states cannot be changed without special handling.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /**
     * Check if the transaction is pending approval.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Check if the transaction is on hold.
     */
    public function isOnHold(): bool
    {
        return $this === self::OnHold;
    }

    /**
     * Check if the transaction was completed.
     */
    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Check if the transaction was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Approval',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get the color class for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::OnHold => 'danger',
            self::Cancelled => 'secondary',
        };
    }
}
