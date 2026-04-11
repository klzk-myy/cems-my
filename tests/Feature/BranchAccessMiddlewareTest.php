<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Branch Access Middleware Tests
 *
 * Tests that the CheckBranchAccess middleware properly enforces branch-level access control:
 * - Admin users can access any branch
 * - Non-admin users can only access their own branch
 * - Unauthenticated users get 401
 */
class BranchAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $adminUser;
    protected User $managerUserBranchA;
    protected User $managerUserBranchB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two branches
        $this->branchA = Branch::create([
            'code' => 'BR001',
            'name' => 'Branch A',
            'type' => 'branch',
            'country' => 'Malaysia',
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->branchB = Branch::create([
            'code' => 'BR002',
            'name' => 'Branch B',
            'type' => 'branch',
            'country' => 'Malaysia',
            'is_active' => true,
            'is_main' => false,
        ]);

        // Create admin user (no branch restriction - can access any branch)
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => bcrypt('Admin@123456'),
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
            'branch_id' => null, // Admin with no specific branch
        ]);

        // Create manager users, one for each branch
        $this->managerUserBranchA = User::create([
            'username' => 'manager_a',
            'email' => 'manager_a@cems.my',
            'password_hash' => bcrypt('Manager@123'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
            'branch_id' => $this->branchA->id,
        ]);

        $this->managerUserBranchB = User::create([
            'username' => 'manager_b',
            'email' => 'manager_b@cems.my',
            'password_hash' => bcrypt('Manager@123'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
            'branch_id' => $this->branchB->id,
        ]);

        // Set up a test route with branch middleware
        $this->setupTestRoutes();
    }

    /**
     * Set up test routes with the branch middleware
     */
    protected function setupTestRoutes(): void
    {
        // Route with 'branch' parameter
        Route::middleware('auth:web')->group(function () {
            Route::get('/test/branch/{branch}', function ($branch) {
                return response('OK');
            })->middleware('branch.access');

            // Route with 'branch_id' parameter
            Route::get('/test/branch-id/{branch_id}', function ($branchId) {
                return response('OK');
            })->middleware('branch.access');

            // Route without branch parameter (should pass through)
            Route::get('/test/no-branch', function () {
                return response('OK');
            })->middleware('branch.access');
        });
    }

    /**
     * Test unauthenticated user gets 401 or redirect from middleware when auth fails
     *
     * For web routes, auth middleware typically redirects to login (302).
     * For API routes, it returns 401. Either is valid for "not authenticated".
     */
    public function test_unauthenticated_user_gets_401_or_redirect(): void
    {
        $response = $this->get('/test/branch/'.$this->branchA->id);

        // Either 401 (API auth) or 302 (web redirect to login) indicates unauthenticated
        $this->assertTrue(
            in_array($response->status(), [401, 302]),
            'Expected 401 or 302 for unauthenticated user, got '.$response->status()
        );
    }

    /**
     * Test admin can access any branch
     */
    public function test_admin_can_access_any_branch(): void
    {
        $this->actingAs($this->adminUser);

        // Admin should access Branch A
        $response = $this->get('/test/branch/'.$this->branchA->id);
        $response->assertStatus(200);

        // Admin should access Branch B
        $response = $this->get('/test/branch/'.$this->branchB->id);
        $response->assertStatus(200);
    }

    /**
     * Test manager cannot access other branch (403)
     */
    public function test_manager_cannot_access_other_branch(): void
    {
        // Manager of Branch A trying to access Branch B
        $this->actingAs($this->managerUserBranchA);

        $response = $this->get('/test/branch/'.$this->branchB->id);
        $response->assertStatus(403);
    }

    /**
     * Test manager can access own branch (200)
     */
    public function test_manager_can_access_own_branch(): void
    {
        // Manager of Branch A accessing Branch A
        $this->actingAs($this->managerUserBranchA);

        $response = $this->get('/test/branch/'.$this->branchA->id);
        $response->assertStatus(200);
    }

    /**
     * Test branch_id parameter works correctly
     */
    public function test_branch_id_parameter_works(): void
    {
        // Manager of Branch A trying to access Branch B via branch_id
        $this->actingAs($this->managerUserBranchA);

        $response = $this->get('/test/branch-id/'.$this->branchB->id);
        $response->assertStatus(403);

        // Manager of Branch A accessing Branch A via branch_id
        $response = $this->get('/test/branch-id/'.$this->branchA->id);
        $response->assertStatus(200);
    }

    /**
     * Test route without branch parameter passes through
     */
    public function test_route_without_branch_parameter_passes_through(): void
    {
        $this->actingAs($this->managerUserBranchA);

        $response = $this->get('/test/no-branch');
        $response->assertStatus(200);
    }

    /**
     * Test non-existent branch returns 403 when user cannot access it
     *
     * When a branch doesn't exist, the middleware returns 403 because
     * the user's branch_id cannot match a non-existent branch.
     * This is acceptable access control behavior - we don't distinguish
     * between "branch doesn't exist" and "you can't access this branch"
     * for security reasons (don't reveal resource existence).
     */
    public function test_non_existent_branch_returns_403(): void
    {
        $this->actingAs($this->managerUserBranchA);

        $response = $this->get('/test/branch/99999');
        $response->assertStatus(403);
    }
}