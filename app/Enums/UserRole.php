<?php

namespace App\Enums;

/**
 * User Role Enum
 *
 * Represents the different roles a user can have in the system
 * with their associated permissions.
 */
enum UserRole: string
{
    case Teller = 'teller';
    case Manager = 'manager';
    case ComplianceOfficer = 'compliance_officer';
    case Admin = 'admin';

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Check if the user is a manager or admin.
     */
    public function isManager(): bool
    {
        return in_array($this, [self::Manager, self::Admin], true);
    }

    /**
     * Check if the user is a compliance officer or admin.
     */
    public function isComplianceOfficer(): bool
    {
        return in_array($this, [self::ComplianceOfficer, self::Admin], true);
    }

    /**
     * Check if the user is a teller.
     */
    public function isTeller(): bool
    {
        return $this === self::Teller;
    }

    /**
     * Check if the user can approve large transactions.
     * Transactions >= RM 50,000 require manager or admin approval.
     */
    public function canApproveLargeTransactions(): bool
    {
        return $this->isManager();
    }

    /**
     * Check if the user can access compliance features.
     */
    public function canAccessCompliance(): bool
    {
        return $this->isComplianceOfficer();
    }

    /**
     * Check if the user can manage users.
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if the user can manage system settings.
     */
    public function canManageSettings(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if the user can approve counter handovers.
     */
    public function canApproveHandover(): bool
    {
        return $this->isManager();
    }

    /**
     * Check if the user can cancel any transaction.
     * Admins and managers can cancel any transaction.
     */
    public function canCancelAnyTransaction(): bool
    {
        return $this->isManager();
    }

    /**
     * Check if the user can view reports.
     */
    public function canViewReports(): bool
    {
        return in_array($this, [self::Manager, self::ComplianceOfficer, self::Admin], true);
    }

    /**
     * Check if the user can perform revaluation.
     */
    public function canPerformRevaluation(): bool
    {
        return $this->isManager();
    }

    /**
     * Get a human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Teller => 'Teller',
            self::Manager => 'Manager',
            self::ComplianceOfficer => 'Compliance Officer',
            self::Admin => 'Administrator',
        };
    }

    /**
     * Get a description of the role's permissions.
     */
    public function description(): string
    {
        return match ($this) {
            self::Teller => 'Can create transactions',
            self::Manager => 'Can approve transactions and manage counters',
            self::ComplianceOfficer => 'Can review flagged transactions and compliance reports',
            self::Admin => 'Full system access',
        };
    }

    /**
     * Get all roles that can be assigned by this role.
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::Admin => [self::Teller, self::Manager, self::ComplianceOfficer, self::Admin],
            self::Manager => [self::Teller],
            default => [],
        };
    }
}
