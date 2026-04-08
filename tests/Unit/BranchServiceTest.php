<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Counter;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BranchService $branchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branchService = new BranchService;
    }

    public function test_list_branches_returns_only_active_branches(): void
    {
        $activeBranch = Branch::factory()->create(['is_active' => true]);
        $inactiveBranch = Branch::factory()->create(['is_active' => false]);

        $branches = $this->branchService->listBranches();

        $this->assertTrue($branches->contains($activeBranch));
        $this->assertFalse($branches->contains($inactiveBranch));
    }

    public function test_list_branches_orders_by_is_main_desc_then_code(): void
    {
        $branch1 = Branch::factory()->create(['code' => 'BR001', 'is_main' => false, 'is_active' => true]);
        $branch2 = Branch::factory()->create(['code' => 'BR002', 'is_main' => true, 'is_active' => true]);
        $branch3 = Branch::factory()->create(['code' => 'BR003', 'is_main' => false, 'is_active' => true]);

        $branches = $this->branchService->listBranches();

        $this->assertEquals($branch2->id, $branches->first()->id);
        $this->assertEquals($branch1->id, $branches->skip(1)->first()->id);
        $this->assertEquals($branch3->id, $branches->last()->id);
    }

    public function test_create_branch(): void
    {
        $data = [
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'address' => '123 Test Street',
            'city' => 'Kuala Lumpur',
            'state' => 'WP Kuala Lumpur',
            'postal_code' => '50000',
            'country' => 'Malaysia',
            'phone' => '+60312345678',
            'email' => 'test@branch.com',
            'is_active' => true,
            'is_main' => false,
        ];

        $branch = $this->branchService->createBranch($data);

        $this->assertInstanceOf(Branch::class, $branch);
        $this->assertEquals('BR001', $branch->code);
        $this->assertEquals('Test Branch', $branch->name);
        $this->assertTrue($branch->is_active);
    }

    public function test_update_branch(): void
    {
        $branch = Branch::factory()->create([
            'code' => 'BR001',
            'name' => 'Original Name',
        ]);

        $updated = $this->branchService->updateBranch($branch, [
            'name' => 'Updated Name',
            'city' => 'Petaling Jaya',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('Petaling Jaya', $updated->city);
        $this->assertEquals('BR001', $updated->code);
    }

    public function test_deactivate_branch(): void
    {
        $branch = Branch::factory()->create(['is_active' => true]);

        $deactivated = $this->branchService->deactivateBranch($branch);

        $this->assertFalse($deactivated->is_active);
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_active' => false,
        ]);
    }

    public function test_get_branch_summary(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        Counter::factory()->create(['branch_id' => $branch->id]);
        Counter::factory()->create(['branch_id' => $branch->id]);
        User::factory()->create(['branch_id' => $branch->id]);
        User::factory()->create(['branch_id' => $branch->id]);

        // Create journal entries for this branch
        JournalEntry::factory()->create(['branch_id' => $branch->id, 'posted_by' => $user->id]);
        JournalEntry::factory()->create(['branch_id' => $branch->id, 'posted_by' => $user->id]);
        JournalEntry::factory()->create(['branch_id' => $branch->id, 'posted_by' => $user->id]);

        $summary = $this->branchService->getBranchSummary($branch);

        $this->assertEquals(2, $summary['counters_count']);
        $this->assertEquals(3, $summary['users_count']);
        $this->assertEquals(3, $summary['recent_journal_entries_count']);
    }

    public function test_get_branch_summary_returns_zero_for_empty_branch(): void
    {
        $branch = Branch::factory()->create();

        $summary = $this->branchService->getBranchSummary($branch);

        $this->assertEquals(0, $summary['counters_count']);
        $this->assertEquals(0, $summary['users_count']);
        $this->assertEquals(0, $summary['recent_transactions_count']);
        $this->assertEquals(0, $summary['recent_journal_entries_count']);
    }

    public function test_get_all_branches_including_inactive(): void
    {
        $activeBranch = Branch::factory()->create(['is_active' => true]);
        $inactiveBranch = Branch::factory()->create(['is_active' => false]);

        $branches = $this->branchService->getAllBranchesIncludingInactive();

        $this->assertTrue($branches->contains($activeBranch));
        $this->assertTrue($branches->contains($inactiveBranch));
        $this->assertEquals(2, $branches->count());
    }
}