<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::ComplianceOfficer || $user->role === UserRole::Manager;
    }

    public function view(User $user, Alert $alert): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::ComplianceOfficer || $user->role === UserRole::Manager;
    }

    public function assign(User $user, Alert $alert): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::ComplianceOfficer || $user->role === UserRole::Manager;
    }

    public function updateStatus(User $user, Alert $alert): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::ComplianceOfficer || $user->role === UserRole::Manager;
    }
}
