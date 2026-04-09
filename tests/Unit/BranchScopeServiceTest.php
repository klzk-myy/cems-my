<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BranchScopeService $branchScopeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branchScopeService = new BranchScopeService;
        // Clear branches to avoid conflicts with DatabaseSeeder
        Branch::query()->delete();
    }

    public function test_admin_user_returns_null_for_accessible_branches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $result = $this->branchScopeService->getAccessibleBranchIds($admin);

        $this->assertNull($result);
    }

    public function test_manager_with_branch_id_returns_array_with_branch_id(): void
    {
        $branch = Branch::factory()->create();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $result = $this->branchScopeService->getAccessibleBranchIds($manager);

        $this->assertIsArray($result);
        $this->assertEquals([$branch->id], $result);
    }

    public function test_manager_with_no_branch_id_returns_empty_array(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => null,
        ]);

        $result = $this->branchScopeService->getAccessibleBranchIds($manager);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_scope_to_user_branch_applies_filter_for_non_admin(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $query = Branch::query();
        $scoped = $this->branchScopeService->scopeToUserBranch($query, $manager);

        $this->assertEquals(1, $scoped->count());
        $this->assertEquals($branch->id, $scoped->first()->id);
    }

    public function test_scope_to_user_branch_applies_no_filter_for_admin(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $query = Branch::query();
        $scoped = $this->branchScopeService->scopeToUserBranch($query, $admin);

        // Admin sees all branches (3 = 2 explicit + 1 from UserFactory branch creation)
        $this->assertEquals(3, $scoped->count());
    }

    public function test_can_access_branch_returns_true_for_admin_accessing_any_branch(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $branch = Branch::factory()->create();

        $result = $this->branchScopeService->canAccessBranch($admin, $branch);

        $this->assertTrue($result);
    }

    public function test_can_access_branch_returns_true_for_user_accessing_own_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $result = $this->branchScopeService->canAccessBranch($user, $branch);

        $this->assertTrue($result);
    }

    public function test_can_access_branch_returns_false_for_user_accessing_other_branch(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $user = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $result = $this->branchScopeService->canAccessBranch($user, $otherBranch);

        $this->assertFalse($result);
    }
}