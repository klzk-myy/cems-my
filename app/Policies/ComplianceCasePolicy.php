<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Compliance\ComplianceCase;
use App\Models\Customer;
use App\Models\User;

class ComplianceCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::ComplianceOfficer || $user->role === UserRole::Manager;
    }

    public function view(User $user, ComplianceCase $case): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role === UserRole::ComplianceOfficer) {
            return true;
        }

        return $user->role === UserRole::Manager
            && $user->branch_id !== null
            && Customer::where('id', $case->customer_id)
                ->whereHas('transactions', fn ($t) => $t->where('branch_id', $user->branch_id))
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::ComplianceOfficer || $user->role === UserRole::Manager;
    }

    public function update(User $user, ComplianceCase $case): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role !== UserRole::ComplianceOfficer) {
            return false;
        }

        return true;
    }

    public function addNote(User $user, ComplianceCase $case): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role !== UserRole::ComplianceOfficer) {
            return false;
        }

        return true;
    }
}
