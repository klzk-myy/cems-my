<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleBranchTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // canManageAllBranches() Tests
    // ========================================

    public function test_admin_can_manage_all_branches()
    {
        $this->assertTrue(UserRole::Admin->canManageAllBranches());
    }

    public function test_manager_cannot_manage_all_branches()
    {
        $this->assertFalse(UserRole::Manager->canManageAllBranches());
    }

    public function test_compliance_officer_cannot_manage_all_branches()
    {
        $this->assertFalse(UserRole::ComplianceOfficer->canManageAllBranches());
    }

    public function test_teller_cannot_manage_all_branches()
    {
        $this->assertFalse(UserRole::Teller->canManageAllBranches());
    }
}
