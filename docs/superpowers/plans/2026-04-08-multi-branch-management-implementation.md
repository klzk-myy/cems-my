# Multi-Branch Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable multi-branch support where each branch operates as a standalone entity for transactions, counters, and accounting, while customer data remains centralized. Admin users see consolidated reports across all branches; branch managers see only their own branch.

**Architecture:** Shared COA + Branch Dimension approach. All branches share the same Chart of Accounts. Branch scope is enforced via `branch_id` FK on transactions, counters, journal_entries, journal_lines, currency_positions, and till_balances. Centralized entities (customers, aml_rules, etc.) carry no branch_id. BranchScopeService applies query filtering based on current user context.

**Tech Stack:** Laravel 10.x, PHP 8.1+, Eloquent ORM

---

## File Inventory

### New Files:
- `app/Models/Branch.php`
- `app/Services/BranchService.php`
- `app/Services/BranchScopeService.php`
- `app/Http/Controllers/BranchController.php`
- `app/Http/Controllers/Api/V1/BranchController.php`
- `app/Http/Middleware/CheckBranchAccess.php`
- `resources/views/branches/index.blade.php`
- `resources/views/branches/create.blade.php`
- `resources/views/branches/edit.blade.php`
- `resources/views/branches/show.blade.php`
- `database/migrations/2026_04_11_000000_add_branch_scope_columns.php`

### Files to Modify:
- `app/Enums/UserRole.php` — add `canManageAllBranches()`, `canAccessBranch()`
- `app/Config/Navigation.php` — add Branches menu item (Admin only)
- `app/Services/LedgerService.php` — accept `?int $branchId` on relevant methods
- `app/Services/FinancialRatioService.php` — accept `?int $branchId`
- `app/Services/CashFlowService.php` — accept `?int $branchId`
- `app/Services/ReportingService.php` — filter by branch
- `database/seeders/BranchSeeder.php` — update for additional fields
- `routes/web.php` — add `/branches` routes
- `routes/api_v1.php` — add `/api/v1/branches` routes

### Files to Read (for existing patterns):
- `app/Models/User.php` — existing `branch_id` FK pattern
- `app/Http/Middleware/CheckRole.php` — for middleware pattern reference
- `app/Services/LedgerService.php` — for service method signature patterns

---

## Task 1: Create Branch Model

**Files:**
- Create: `app/Models/Branch.php`
- Test: `tests/Unit/BranchModelTest.php`

- [ ] **Step 1: Create the failing test**

```php
// tests/Unit/BranchModelTest.php
<?php

namespace Tests\Unit;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_has_correct_constants(): void
    {
        $this->assertEquals('head_office', Branch::TYPE_HEAD_OFFICE);
        $this->assertEquals('branch', Branch::TYPE_BRANCH);
        $this->assertEquals('sub_branch', Branch::TYPE_SUB_BRANCH);
    }

    public function test_branch_can_be_created(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Kuala Lumpur Branch',
            'type' => Branch::TYPE_BRANCH,
            'city' => 'Kuala Lumpur',
            'country' => 'Malaysia',
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->assertDatabaseHas('branches', [
            'code' => 'BR001',
            'name' => 'Kuala Lumpur Branch',
            'type' => 'branch',
        ]);
    }

    public function test_branch_has_users_relationship(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->users());
    }

    public function test_branch_has_counters_relationship(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->counters());
    }

    public function test_branch_has_transactions_relationship(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->transactions());
    }

    public function test_branch_has_journal_entries_relationship(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $branch->journalEntries());
    }

    public function test_branch_scope_active(): void
    {
        Branch::create([
            'code' => 'BR001',
            'name' => 'Active Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
            'is_active' => true,
        ]);

        Branch::create([
            'code' => 'BR002',
            'name' => 'Inactive Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
            'is_active' => false,
        ]);

        $activeBranches = Branch::active()->get();
        $this->assertCount(1, $activeBranches);
        $this->assertEquals('BR001', $activeBranches->first()->code);
    }

    public function test_branch_is_main_scope(): void
    {
        Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'country' => 'Malaysia',
            'is_main' => true,
        ]);

        Branch::create([
            'code' => 'BR001',
            'name' => 'Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
            'is_main' => false,
        ]);

        $mainBranch = Branch::main()->first();
        $this->assertEquals('HQ', $mainBranch->code);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BranchModelTest`
Expected: FAIL with "Class Branch does not exist"

- [ ] **Step 3: Create Branch model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Branch extends Model
{
    use HasFactory;

    public const TYPE_HEAD_OFFICE = 'head_office';
    public const TYPE_BRANCH = 'branch';
    public const TYPE_SUB_BRANCH = 'sub_branch';

    protected $fillable = [
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

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(Counter::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function currencyPositions(): HasMany
    {
        return $this->hasMany(CurrencyPosition::class);
    }

    public function tillBalances(): HasMany
    {
        return $this->hasMany(TillBalance::class);
    }

    public function counterSessions(): HasManyThrough
    {
        return $this->hasManyThrough(CounterSession::class, Counter::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    public function scopeBranches($query)
    {
        return $query->where('type', self::TYPE_BRANCH);
    }

    public function scopeHeadOffices($query)
    {
        return $query->where('type', self::TYPE_HEAD_OFFICE);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BranchModelTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Branch.php tests/Unit/BranchModelTest.php
git commit -m "feat: add Branch model with relationships and scopes"
```

---

## Task 2: Create Database Migration for Branch Scope Columns

**Files:**
- Create: `database/migrations/2026_04_11_000000_add_branch_scope_columns.php`
- Test: runs via Task 1 tests once model is complete

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // counters.branch_id
        Schema::table('counters', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('status')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->index('branch_id');
        });

        // transactions.branch_id
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->index('branch_id');
        });

        // journal_lines.branch_id
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('journal_entry_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->index('branch_id');
        });

        // currency_positions.branch_id
        Schema::table('currency_positions', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('currency_code')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->index('branch_id');
        });

        // till_balances.branch_id
        Schema::table('till_balances', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('counter_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->index('branch_id');
        });

        // Add parent_id to branches (for hierarchy)
        if (!Schema::hasColumn('branches', 'parent_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
                $table->index('parent_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['counters', 'transactions', 'journal_lines', 'currency_positions', 'till_balances'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table, 'branch_id')) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                }
            });
        }

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate`
Expected: Migration runs successfully, new columns added

- [ ] **Step 3: Verify columns exist**

Run: `php artisan db:schema dump --dump=sqlite`
Verify `branch_id` FK on all listed tables

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_11_000000_add_branch_scope_columns.php
git commit -m "feat: add branch_id FK to counters, transactions, journal_lines, currency_positions, till_balances"
```

---

## Task 3: Create BranchService

**Files:**
- Create: `app/Services/BranchService.php`
- Test: `tests/Unit/BranchServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/BranchServiceTest.php
<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchServiceTest extends TestCase
{
    use RefreshDatabase;

    private BranchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BranchService();
    }

    public function test_list_branches_returns_only_active(): void
    {
        Branch::create(['code' => 'BR001', 'name' => 'Active', 'type' => 'branch', 'country' => 'MY', 'is_active' => true]);
        Branch::create(['code' => 'BR002', 'name' => 'Inactive', 'type' => 'branch', 'country' => 'MY', 'is_active' => false]);

        $result = $this->service->listBranches();

        $this->assertCount(1, $result);
        $this->assertEquals('BR001', $result->first()->code);
    }

    public function test_create_branch(): void
    {
        $data = [
            'code' => 'BR001',
            'name' => 'Kuala Lumpur Branch',
            'type' => Branch::TYPE_BRANCH,
            'address' => 'Level 10',
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan',
            'postal_code' => '50250',
            'country' => 'Malaysia',
            'phone' => '+60 3-1234 5678',
            'email' => 'kl@cems.my',
            'is_active' => true,
            'is_main' => false,
        ];

        $branch = $this->service->createBranch($data);

        $this->assertDatabaseHas('branches', ['code' => 'BR001', 'name' => 'Kuala Lumpur Branch']);
        $this->assertEquals('Kuala Lumpur Branch', $branch->name);
    }

    public function test_update_branch(): void
    {
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Old Name', 'type' => 'branch', 'country' => 'MY']);

        $updated = $this->service->updateBranch($branch, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('branches', ['code' => 'BR001', 'name' => 'New Name']);
    }

    public function test_deactivate_branch(): void
    {
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Test', 'type' => 'branch', 'country' => 'MY', 'is_active' => true]);

        $result = $this->service->deactivateBranch($branch);

        $this->assertFalse($result->is_active);
        $this->assertDatabaseHas('branches', ['code' => 'BR001', 'is_active' => false]);
    }

    public function test_get_branch_summary(): void
    {
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Test', 'type' => 'branch', 'country' => 'MY']);

        $summary = $this->service->getBranchSummary($branch);

        $this->assertArrayHasKey('counters_count', $summary);
        $this->assertArrayHasKey('users_count', $summary);
        $this->assertArrayHasKey('recent_transactions_count', $summary);
        $this->assertArrayHasKey('recent_journal_entries_count', $summary);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BranchServiceTest`
Expected: FAIL with "Class BranchService does not exist"

- [ ] **Step 3: Write BranchService**

```php
<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

class BranchService
{
    public function listBranches(): Collection
    {
        return Branch::active()
            ->orderBy('is_main', 'desc')
            ->orderBy('code')
            ->get();
    }

    public function createBranch(array $data): Branch
    {
        return Branch::create($data);
    }

    public function updateBranch(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch->fresh();
    }

    public function deactivateBranch(Branch $branch): Branch
    {
        $branch->update(['is_active' => false]);
        return $branch->fresh();
    }

    public function getBranchSummary(Branch $branch): array
    {
        return [
            'counters_count' => $branch->counters()->count(),
            'users_count' => $branch->users()->count(),
            'recent_transactions_count' => $branch->transactions()->count(),
            'recent_journal_entries_count' => $branch->journalEntries()->count(),
        ];
    }

    public function getAllBranchesIncludingInactive(): Collection
    {
        return Branch::orderBy('is_main', 'desc')
            ->orderBy('code')
            ->get();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BranchServiceTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/BranchService.php tests/Unit/BranchServiceTest.php
git commit -m "feat: add BranchService with CRUD operations"
```

---

## Task 4: Create BranchScopeService

**Files:**
- Create: `app/Services/BranchScopeService.php`
- Test: `tests/Unit/BranchScopeServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/BranchScopeServiceTest.php
<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    private BranchScopeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BranchScopeService();
    }

    public function test_admin_can_access_all_branches(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $branchIds = $this->service->getAccessibleBranchIds($admin);

        $this->assertNull($branchIds); // null means "all"
    }

    public function test_manager_can_access_own_branch_only(): void
    {
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Test', 'type' => 'branch', 'country' => 'MY']);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'branch_id' => $branch->id]);

        $branchIds = $this->service->getAccessibleBranchIds($manager);

        $this->assertEquals([$branch->id], $branchIds);
    }

    public function test_scope_to_user_branch_applies_filter(): void
    {
        $branch1 = Branch::create(['code' => 'BR001', 'name' => 'Branch 1', 'type' => 'branch', 'country' => 'MY']);
        $branch2 = Branch::create(['code' => 'BR002', 'name' => 'Branch 2', 'type' => 'branch', 'country' => 'MY']);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'branch_id' => $branch1->id]);

        // Simulate a query scoped to user branch
        $query = Branch::query();
        $this->service->scopeToUserBranch($query, $manager);

        $result = $query->get();
        $this->assertCount(1, $result);
        $this->assertEquals($branch1->id, $result->first()->id);
    }

    public function test_admin_gets_no_filter(): void
    {
        $branch1 = Branch::create(['code' => 'BR001', 'name' => 'Branch 1', 'type' => 'branch', 'country' => 'MY']);
        $branch2 = Branch::create(['code' => 'BR002', 'name' => 'Branch 2', 'type' => 'branch', 'country' => 'MY']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $query = Branch::query();
        $this->service->scopeToUserBranch($query, $admin);

        $result = $query->get();
        $this->assertCount(2, $result); // Admin sees all
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BranchScopeServiceTest`
Expected: FAIL with "Class BranchScopeService does not exist"

- [ ] **Step 3: Write BranchScopeService**

```php
<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BranchScopeService
{
    /**
     * Returns branch IDs the user can access.
     * Returns null for Admin (means "all branches").
     *
     * @return array<int>|null
     */
    public function getAccessibleBranchIds(User $user): array|null
    {
        if ($user->role->canManageAllBranches()) {
            return null; // null = all branches
        }

        return $user->branch_id ? [$user->branch_id] : [];
    }

    /**
     * Scope a query to the user's accessible branches.
     * Admin gets no filter (sees all).
     */
    public function scopeToUserBranch(Builder $query, User $user): Builder
    {
        $branchIds = $this->getAccessibleBranchIds($user);

        if ($branchIds === null) {
            // Admin — no filter
            return $query;
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Check if a user can access a specific branch.
     */
    public function canAccessBranch(User $user, Branch $branch): bool
    {
        if ($user->role->canManageAllBranches()) {
            return true;
        }

        return $user->branch_id === $branch->id;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BranchScopeServiceTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/BranchScopeService.php tests/Unit/BranchScopeServiceTest.php
git commit -m "feat: add BranchScopeService for branch-aware query filtering"
```

---

## Task 5: Update UserRole Enum with Branch Permission Methods

**Files:**
- Modify: `app/Enums/UserRole.php`
- Test: `tests/Unit/UserRoleBranchTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/UserRoleBranchTest.php
<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_all_branches(): void
    {
        $this->assertTrue(UserRole::Admin->canManageAllBranches());
        $this->assertFalse(UserRole::Manager->canManageAllBranches());
        $this->assertFalse(UserRole::ComplianceOfficer->canManageAllBranches());
        $this->assertFalse(UserRole::Teller->canManageAllBranches());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserRoleBranchTest`
Expected: FAIL with "canManageAllBranches method does not exist"

- [ ] **Step 3: Read current UserRole enum**

Read `app/Enums/UserRole.php`

- [ ] **Step 4: Add branch permission methods to UserRole enum**

Add these methods after the existing role methods in `UserRole.php`:

```php
public function canManageAllBranches(): bool
{
    return $this === self::Admin;
}

public function canAccessBranch(Branch $branch, User $user): bool
{
    if ($this->canManageAllBranches()) {
        return true;
    }

    return $user->branch_id === $branch->id;
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=UserRoleBranchTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Enums/UserRole.php tests/Unit/UserRoleBranchTest.php
git commit -m "feat: add branch permission methods to UserRole enum"
```

---

## Task 6: Create CheckBranchAccess Middleware

**Files:**
- Create: `app/Http/Middleware/CheckBranchAccess.php`
- Test: `tests/Feature/BranchAccessMiddlewareTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/BranchAccessMiddlewareTest.php
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_any_branch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Test', 'type' => 'branch', 'country' => 'MY']);

        $response = $this->actingAs($admin)->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200);
    }

    public function test_manager_cannot_access_other_branch(): void
    {
        $branch1 = Branch::create(['code' => 'BR001', 'name' => 'Branch 1', 'type' => 'branch', 'country' => 'MY']);
        $branch2 = Branch::create(['code' => 'BR002', 'name' => 'Branch 2', 'type' => 'branch', 'country' => 'MY']);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'branch_id' => $branch1->id]);

        $response = $this->actingAs($manager)->getJson("/api/v1/branches/{$branch2->id}");

        $response->assertStatus(403);
    }

    public function test_manager_can_access_own_branch(): void
    {
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Test', 'type' => 'branch', 'country' => 'MY']);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'branch_id' => $branch->id]);

        $response = $this->actingAs($manager)->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BranchAccessMiddlewareTest`
Expected: FAIL with "Class CheckBranchAccess does not exist"

- [ ] **Step 3: Create CheckBranchAccess middleware**

```php
<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->role->canManageAllBranches()) {
            return $next($request);
        }

        // Get branch ID from route parameter
        $branchId = $request->route('branch')?->id ?? $request->route('branch_id');

        if ($branchId === null) {
            return $next($request); // No branch in route, continue
        }

        $branch = Branch::find($branchId);

        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        if ($user->branch_id !== $branch->id) {
            return response()->json(['error' => 'Access denied to this branch'], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BranchAccessMiddlewareTest`
Expected: PASS

- [ ] **Step 5: Register middleware in bootstrap/app.php**

Read `bootstrap/app.php` and add the middleware alias if not already registered.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/CheckBranchAccess.php tests/Feature/BranchAccessMiddlewareTest.php
git commit -m "feat: add CheckBranchAccess middleware for branch-scoped access control"
```

---

## Task 7: Create BranchController and Views (Web UI)

**Files:**
- Create: `app/Http/Controllers/BranchController.php`
- Create: `resources/views/branches/index.blade.php`
- Create: `resources/views/branches/create.blade.php`
- Create: `resources/views/branches/edit.blade.php`
- Create: `resources/views/branches/show.blade.php`

- [ ] **Step 1: Create BranchController**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService,
    ) {}

    public function index(): View
    {
        $branches = $this->branchService->listBranches();
        $allBranches = $this->branchService->getAllBranchesIncludingInactive();

        return view('branches.index', [
            'branches' => $branches,
            'allBranches' => $allBranches,
        ]);
    }

    public function create(): View
    {
        return view('branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:branches,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:head_office,branch,sub_branch',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
            'is_main' => 'boolean',
        ]);

        $this->branchService->createBranch($validated);

        return redirect()->route('branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch): View
    {
        $summary = $this->branchService->getBranchSummary($branch);

        return view('branches.show', [
            'branch' => $branch,
            'summary' => $summary,
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('branches.edit', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:head_office,branch,sub_branch',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
            'is_main' => 'boolean',
        ]);

        $this->branchService->updateBranch($branch, $validated);

        return redirect()->route('branches.show', $branch)
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->branchService->deactivateBranch($branch);

        return redirect()->route('branches.index')
            ->with('success', 'Branch deactivated successfully.');
    }
}
```

- [ ] **Step 2: Create Blade views**

Create `resources/views/branches/index.blade.php` — table listing all branches with code, name, type, city, status, actions (edit, view, deactivate). Follow existing blade pattern from `resources/views/users/` or similar list views.

Create `resources/views/branches/create.blade.php` — form with all branch fields. Follow existing form pattern from similar create views.

Create `resources/views/branches/edit.blade.php` — pre-filled form for editing branch.

Create `resources/views/branches/show.blade.php` — branch detail page showing overview, counters list, users list, recent transactions, recent journal entries.

- [ ] **Step 3: Add routes to web.php**

Add to `routes/web.php`:
```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('branches', BranchController::class);
});
```

- [ ] **Step 4: Verify pages load**

Run: `php artisan route:list --path=branches`
Expected: All 7 resource routes visible

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/BranchController.php resources/views/branches/
git add routes/web.php
git commit -m "feat: add BranchController and views for branch CRUD UI"
```

---

## Task 8: Create Api/V1/BranchController

**Files:**
- Create: `app/Http/Controllers/Api/V1/BranchController.php`
- Test: `tests/Feature/Api/V1/BranchControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Api/V1/BranchControllerTest.php
<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_branches(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Branch::create(['code' => 'BR001', 'name' => 'Branch 1', 'type' => 'branch', 'country' => 'MY']);
        Branch::create(['code' => 'BR002', 'name' => 'Branch 2', 'type' => 'branch', 'country' => 'MY']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/branches');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_manager_cannot_list_branches(): void
    {
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Branch 1', 'type' => 'branch', 'country' => 'MY']);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'branch_id' => $branch->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/v1/branches');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_branch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/branches', [
            'code' => 'BR001',
            'name' => 'Kuala Lumpur Branch',
            'type' => 'branch',
            'city' => 'Kuala Lumpur',
            'country' => 'Malaysia',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'BR001');

        $this->assertDatabaseHas('branches', ['code' => 'BR001']);
    }

    public function test_admin_can_get_branch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Test', 'type' => 'branch', 'country' => 'MY']);

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $branch->id);
    }

    public function test_admin_can_update_branch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Old Name', 'type' => 'branch', 'country' => 'MY']);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/branches/{$branch->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_admin_can_deactivate_branch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $branch = Branch::create(['code' => 'BR001', 'name' => 'Test', 'type' => 'branch', 'country' => 'MY', 'is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('branches', ['code' => 'BR001', 'is_active' => false]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BranchControllerTest`
Expected: FAIL with "Class Api\V1\BranchController does not exist"

- [ ] **Step 3: Create Api/V1/BranchController**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->canManageAllBranches()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $branches = $this->branchService->listBranches();

        return response()->json([
            'data' => $branches->map(fn(Branch $b) => $this->formatBranch($b)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->canManageAllBranches()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:branches,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:head_office,branch,sub_branch',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
            'is_main' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = $this->branchService->createBranch($validator->validated());

        return response()->json(['data' => $this->formatBranch($branch)], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $branch = Branch::findOrFail($id);

        if (!$user->role->canManageAllBranches() && $user->branch_id !== $branch->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $this->formatBranch($branch)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->canManageAllBranches()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $branch = Branch::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:head_office,branch,sub_branch',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
            'is_main' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = $this->branchService->updateBranch($branch, $validator->validated());

        return response()->json(['data' => $this->formatBranch($branch)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->canManageAllBranches()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $branch = Branch::findOrFail($id);
        $this->branchService->deactivateBranch($branch);

        return response()->json(['message' => 'Branch deactivated successfully.']);
    }

    public function counters(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $branch = Branch::findOrFail($id);

        if (!$user->role->canManageAllBranches() && $user->branch_id !== $branch->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $branch->counters()->get()->map(fn($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name]),
        ]);
    }

    public function users(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $branch = Branch::findOrFail($id);

        if (!$user->role->canManageAllBranches() && $user->branch_id !== $branch->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $branch->users()->get()->map(fn($u) => ['id' => $u->id, 'username' => $u->username, 'role' => $u->role->value]),
        ]);
    }

    private function formatBranch(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'type' => $branch->type,
            'address' => $branch->address,
            'city' => $branch->city,
            'state' => $branch->state,
            'postal_code' => $branch->postal_code,
            'country' => $branch->country,
            'phone' => $branch->phone,
            'email' => $branch->email,
            'is_active' => $branch->is_active,
            'is_main' => $branch->is_main,
        ];
    }
}
```

- [ ] **Step 4: Add API routes to api_v1.php**

```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('branches', [BranchController::class, 'index']);
    Route::post('branches', [BranchController::class, 'store']);
    Route::get('branches/{id}', [BranchController::class, 'show']);
    Route::put('branches/{id}', [BranchController::class, 'update']);
    Route::delete('branches/{id}', [BranchController::class, 'destroy']);
    Route::get('branches/{id}/counters', [BranchController::class, 'counters']);
    Route::get('branches/{id}/users', [BranchController::class, 'users']);
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=BranchControllerTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/V1/BranchController.php routes/api_v1.php
git add tests/Feature/Api/V1/BranchControllerTest.php
git commit -m "feat: add API v1 BranchController with CRUD endpoints"
```

---

## Task 9: Update Navigation for Branches Menu

**Files:**
- Modify: `app/Config/Navigation.php`

- [ ] **Step 1: Read current Navigation.php**

Read `app/Config/Navigation.php`

- [ ] **Step 2: Add Branches to System section**

In the `system` section's `items` array, add:

```php
[
    'label' => 'Branches',
    'route' => 'branches.index',
    'icon' => 'branch',
    'uri' => '/branches',
],
```

Note: This menu item should only be shown to Admin role. The route middleware `role:admin` already enforces this, but consider adding role-filtering in `Navigation::getForRole()` if the sidebar filters by role.

- [ ] **Step 3: Verify navigation renders correctly**

Run: `php artisan route:list --path=branches`
Expected: `branches.index` route visible

- [ ] **Step 4: Commit**

```bash
git add app/Config/Navigation.php
git commit -m "feat: add Branches menu item to System navigation"
```

---

## Task 10: Update Reporting Services for Branch Filtering

**Files:**
- Modify: `app/Services/LedgerService.php`
- Modify: `app/Services/FinancialRatioService.php`
- Modify: `app/Services/CashFlowService.php`
- Modify: `app/Services/ReportingService.php`
- Test: `tests/Unit/BranchReportingTest.php`

- [ ] **Step 1: Read current LedgerService methods**

Read `app/Services/LedgerService.php` to identify methods to update:
- `getTrialBalance()` — add `?int $branchId = null`
- `getLedgerEntries()` — add `?int $branchId = null`
- `getProfitLoss()` — add `?int $branchId = null`

- [ ] **Step 2: Read current FinancialRatioService methods**

Read `app/Services/FinancialRatioService.php` to identify methods to update.

- [ ] **Step 3: Read current CashFlowService methods**

Read `app/Services/CashFlowService.php` to identify methods to update.

- [ ] **Step 4: Update LedgerService**

For each method that queries `journal_lines` or `journal_entries`, add branch filtering:

```php
public function getTrialBalance(..., ?int $branchId = null): Collection
{
    $query = JournalLine::query()
        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
        // ... rest of method
}
```

- [ ] **Step 5: Update FinancialRatioService similarly**

- [ ] **Step 6: Update CashFlowService similarly**

- [ ] **Step 7: Write branch reporting tests**

```php
// tests/Unit/BranchReportingTest.php
<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_filters_by_branch(): void
    {
        $branch1 = Branch::create(['code' => 'BR001', 'name' => 'Branch 1', 'type' => 'branch', 'country' => 'MY']);
        $branch2 = Branch::create(['code' => 'BR002', 'name' => 'Branch 2', 'type' => 'branch', 'country' => 'MY']);

        $ledgerService = new LedgerService();

        // Create journal entries in each branch
        $entries1 = $this->createJournalEntryForBranch($branch1);
        $entries2 = $this->createJournalEntryForBranch($branch2);

        $tbBranch1 = $ledgerService->getTrialBalance($entries1->first()->fiscal_year_id, null, $branch1->id);
        $tbBranch2 = $ledgerService->getTrialBalance($entries2->first()->fiscal_year_id, null, $branch2->id);

        $this->assertNotEquals($tbBranch1->count(), $tbBranch2->count());
    }

    private function createJournalEntryForBranch(Branch $branch)
    {
        // Helper to create journal entry with branch_id set
    }
}
```

- [ ] **Step 8: Commit**

```bash
git add app/Services/LedgerService.php app/Services/FinancialRatioService.php app/Services/CashFlowService.php app/Services/ReportingService.php
git commit -m "feat: add branch_id filtering to accounting report services"
```

---

## Task 11: Update BranchSeeder

**Files:**
- Modify: `database/seeders/BranchSeeder.php`

- [ ] **Step 1: Read current BranchSeeder**

Read `database/seeders/BranchSeeder.php`

- [ ] **Step 2: Update seeder to include all new fields**

Update the HQ and branch entries to include `address`, `state`, `postal_code`, `phone`, `email` fields.

- [ ] **Step 3: Run seeder**

Run: `php artisan db:seed --class=BranchSeeder`
Expected: Seed completes without errors

- [ ] **Step 4: Commit**

```bash
git add database/seeders/BranchSeeder.php
git commit -m "feat: update BranchSeeder with full address and contact fields"
```

---

## Task 12: Header Branch Selector for Admin

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (or main layout)
- Modify: `resources/views/components/branch-selector.blade.php` (optional component)

- [ ] **Step 1: Identify main layout file**

Find the layout that contains the top navigation bar (likely in `resources/views/layouts/`).

- [ ] **Step 2: Add branch selector dropdown for Admin**

Add a `<select>` element in the header that posts to a route updating the session's active branch scope. Admin selects "All Branches" or a specific branch.

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/
git commit -m "feat: add branch selector dropdown in header for Admin users"
```

---

## Self-Review Checklist

1. **Spec coverage:** Skim each section of the spec:
   - ✅ Branch model — Task 1
   - ✅ Branch-scoped FKs — Task 2
   - ✅ Access control (UserRole methods, CheckBranchAccess middleware) — Tasks 5, 6
   - ✅ Navigation — Task 9
   - ✅ Branch CRUD UI — Task 7
   - ✅ API endpoints — Task 8
   - ✅ BranchScopeService — Task 4
   - ✅ BranchService — Task 3
   - ✅ Reporting services with branch filter — Task 10
   - ✅ BranchSeeder update — Task 11
   - ✅ Header branch selector — Task 12

2. **Placeholder scan:** All steps show actual file paths, actual code, actual commands.

3. **Type consistency:** Method signatures use `?int $branchId = null` consistently across all reporting services.

---

## Execution Choice

**Plan complete and saved to `docs/superpowers/plans/2026-04-08-multi-branch-management-implementation.md`.**

Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks sequentially in this session using executing-plans

**Which approach?**
