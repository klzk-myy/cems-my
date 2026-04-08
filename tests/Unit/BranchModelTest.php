<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\CurrencyPosition;
use App\Models\JournalEntry;
use App\Models\TillBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test branch constants are defined correctly
     */
    public function test_branch_type_constants(): void
    {
        $this->assertEquals('head_office', Branch::TYPE_HEAD_OFFICE);
        $this->assertEquals('branch', Branch::TYPE_BRANCH);
        $this->assertEquals('sub_branch', Branch::TYPE_SUB_BRANCH);
    }

    /**
     * Test branch can be created with valid attributes
     */
    public function test_branch_can_be_created(): void
    {
        $branch = Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'address' => 'Level 10, Menara Multi-Purpose',
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan',
            'postal_code' => '50250',
            'country' => 'Malaysia',
            'phone' => '+60 3-1234 5678',
            'email' => 'hq@cems.my',
            'is_active' => true,
            'is_main' => true,
        ]);

        $this->assertDatabaseHas('branches', [
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'city' => 'Kuala Lumpur',
            'is_active' => true,
            'is_main' => true,
        ]);
    }

    /**
     * Test branch has correct fillable attributes
     */
    public function test_branch_has_correct_fillable_attributes(): void
    {
        $branch = new Branch;
        $fillable = $branch->getFillable();

        $expectedFillable = [
            'code',
            'name',
            'type',
            'address',
            'city',
            'state',
            'postal_code',
            'country',
            'phone',
            'email',
            'is_active',
            'is_main',
        ];

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    /**
     * Test branch has correct casts
     */
    public function test_branch_has_correct_casts(): void
    {
        $branch = new Branch;
        $casts = $branch->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);

        $this->assertArrayHasKey('is_main', $casts);
        $this->assertEquals('boolean', $casts['is_main']);
    }

    /**
     * Test branch has users relationship
     */
    public function test_branch_has_users_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'users'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->users());
    }

    /**
     * Test branch has counters relationship
     */
    public function test_branch_has_counters_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'counters'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->counters());
    }

    /**
     * Test branch has transactions relationship
     */
    public function test_branch_has_transactions_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'transactions'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->transactions());
    }

    /**
     * Test branch has journalEntries relationship
     */
    public function test_branch_has_journal_entries_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'journalEntries'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->journalEntries());
    }

    /**
     * Test branch has currencyPositions relationship
     */
    public function test_branch_has_currency_positions_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'currencyPositions'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->currencyPositions());
    }

    /**
     * Test branch has tillBalances relationship
     */
    public function test_branch_has_till_balances_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'tillBalances'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->tillBalances());
    }

    /**
     * Test branch has counterSessions relationship (HasManyThrough)
     */
    public function test_branch_has_counter_sessions_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'counterSessions'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasManyThrough::class, $branch->counterSessions());
    }

    /**
     * Test branch has parent relationship (self-referential)
     */
    public function test_branch_has_parent_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'parent'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $branch->parent());
    }

    /**
     * Test branch has children relationship (self-referential)
     */
    public function test_branch_has_children_relationship(): void
    {
        $branch = new Branch;
        $this->assertTrue(method_exists($branch, 'children'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->children());
    }

    /**
     * Test active scope filters active branches
     */
    public function test_scope_active_filters_active_branches(): void
    {
        Branch::create([
            'code' => 'BR001',
            'name' => 'Active Branch',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        Branch::create([
            'code' => 'BR002',
            'name' => 'Inactive Branch',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => false,
            'is_main' => false,
        ]);

        $activeBranches = Branch::active()->get();

        $this->assertCount(1, $activeBranches);
        $this->assertEquals('BR001', $activeBranches->first()->code);
    }

    /**
     * Test main scope filters main branch
     */
    public function test_scope_main_filters_main_branch(): void
    {
        Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'is_active' => true,
            'is_main' => true,
        ]);

        Branch::create([
            'code' => 'BR001',
            'name' => 'Branch',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        $mainBranches = Branch::main()->get();

        $this->assertCount(1, $mainBranches);
        $this->assertEquals('HQ', $mainBranches->first()->code);
    }

    /**
     * Test branches scope filters by type
     */
    public function test_scope_branches_filters_by_type(): void
    {
        Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'is_active' => true,
            'is_main' => true,
        ]);

        Branch::create([
            'code' => 'BR001',
            'name' => 'Branch 1',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        Branch::create([
            'code' => 'BR002',
            'name' => 'Branch 2',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        $branches = Branch::branches()->get();

        $this->assertCount(2, $branches);
        $this->assertEquals('BR001', $branches->first()->code);
    }

    /**
     * Test headOffices scope filters by type
     */
    public function test_scope_head_offices_filters_by_type(): void
    {
        Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'is_active' => true,
            'is_main' => true,
        ]);

        Branch::create([
            'code' => 'BR001',
            'name' => 'Branch 1',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        $headOffices = Branch::headOffices()->get();

        $this->assertCount(1, $headOffices);
        $this->assertEquals('HQ', $headOffices->first()->code);
    }

    /**
     * Test branch can update attributes
     */
    public function test_branch_can_update_attributes(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Original Name',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        $branch->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('branches', [
            'code' => 'BR001',
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test branch code must be unique
     */
    public function test_branch_code_must_be_unique(): void
    {
        Branch::create([
            'code' => 'BR001',
            'name' => 'Branch 1',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Branch::create([
            'code' => 'BR001',
            'name' => 'Branch 2',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    /**
     * Test branch with users relationship
     */
    public function test_branch_can_have_users(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Branch 1',
            'type' => Branch::TYPE_BRANCH,
            'is_active' => true,
            'is_main' => false,
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->assertCount(1, $branch->users);
        $this->assertEquals($user->id, $branch->users->first()->id);
    }

}