<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any transactions.
     * Users can view transactions if they are assigned to a branch (or are admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->branch_id !== null;
    }

    /**
     * Determine whether the user can view the transaction.
     * Enforces branch isolation: non-admins can only view transactions from their own branch.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $transaction->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can create transactions.
     * Tellers, managers, and admins can create transactions.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Teller, UserRole::Manager, UserRole::Admin]);
    }

    /**
     * Determine whether the user can update the transaction.
     * Admins can update any transaction. Owners may edit their own
     * transactions only while the record is still awaiting approval
     * (Teller role); once approved the ledger record is frozen for its
     * creator.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $transaction->user_id === $user->id
            && $user->role === UserRole::Teller
            && $transaction->status === TransactionStatus::PendingApproval;
    }

    /**
     * Determine whether the user can delete the transaction.
     * Only admins can delete transactions.
     */
    public function delete(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can request cancellation of the transaction.
     * Managers and admins can request cancellation for transactions in their branch.
     */
    public function requestCancellation(User $user, Transaction $transaction): bool
    {
        if (! in_array($user->role, [UserRole::Manager, UserRole::Admin])) {
            return false;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $transaction->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can approve cancellation of the transaction.
     * Managers, compliance officers, and admins can approve cancellation for transactions in their branch.
     */
    public function approveCancellation(User $user, Transaction $transaction): bool
    {
        if (! in_array($user->role, [UserRole::Manager, UserRole::ComplianceOfficer, UserRole::Admin])) {
            return false;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $transaction->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can reject cancellation of the transaction.
     * Managers, compliance officers, and admins can reject cancellation for transactions in their branch.
     */
    public function rejectCancellation(User $user, Transaction $transaction): bool
    {
        if (! in_array($user->role, [UserRole::Manager, UserRole::ComplianceOfficer, UserRole::Admin])) {
            return false;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $transaction->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can approve the transaction.
     * Managers and admins can approve transactions in their branch.
     */
    public function approve(User $user, Transaction $transaction): bool
    {
        if (! in_array($user->role, [UserRole::Manager, UserRole::Admin])) {
            return false;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $transaction->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can reject (decline) a pending transaction.
     * Mirrors approve(): managers and admins within their branch.
     */
    public function reject(User $user, Transaction $transaction): bool
    {
        return $this->approve($user, $transaction);
    }
}
