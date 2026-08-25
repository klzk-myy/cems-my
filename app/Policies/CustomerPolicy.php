<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     * Users can view customers if they are assigned to a branch (or are admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->branch_id !== null;
    }

    /**
     * Determine whether the user can view the customer.
     * Enforces branch isolation: non-admins can only view customers who have
     * at least one transaction in their branch.
     */
    public function view(User $user, Customer $customer): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        // Check if the customer has any transaction in the user's branch
        return $customer->transactions()
            ->where('branch_id', $user->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create customers.
     * Tellers, managers, and admins can create customers.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Teller, UserRole::Manager, UserRole::Admin]);
    }

    /**
     * Determine whether the user can update the customer.
     * Managers and admins can update customers in their branch.
     */
    public function update(User $user, Customer $customer): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role !== UserRole::Manager) {
            return false;
        }

        // Check if the customer has any transaction in the user's branch
        return $customer->transactions()
            ->where('branch_id', $user->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the customer.
     * Only admins can delete customers.
     */
    public function delete(User $user, Customer $customer): bool
    {
        if ($user->role !== UserRole::Admin) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can add a note to the customer.
     *
     * Enforces branch isolation (same rule as view) without the Manager-only
     * restriction of update, so tellers can record notes on customers they
     * handle, but never on customers from another branch.
     */
    public function createNote(User $user, Customer $customer): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->branch_id === null) {
            return false;
        }

        return $customer->transactions()
            ->where('branch_id', $user->branch_id)
            ->exists();
    }
}
