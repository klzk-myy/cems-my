<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $managerUser;
    protected User $tellerUser;
    protected Branch $branch1;
    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminUser = User::create([
            'username' => 'admin1',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create manager user at branch 1
        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create teller user at branch 1
        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create branch 1 (HQ)
        $this->branch1 = Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => 'head_office',
            'country' => 'Malaysia',
            'is_active' => true,
            'is_main' => true,
        ]);

        // Create branch 2 (branch)
        $this->branch2 = Branch::create([
            'code' => 'BR001',
            'name' => 'Kuala Lumpur Branch',
            'type' => 'branch',
            'country' => 'Malaysia',
            'is_active' => true,
            'is_main' => false,
        ]);

        // Assign users to branch1
        $this->managerUser->update(['branch_id' => $this->branch1->id]);
        $this->tellerUser->update(['branch_id' => $this->branch1->id]);
    }

    /**
     * Test admin can list all branches.
     */
    public function test_admin_can_list_branches(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/branches');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'name',
                    'type',
                    'is_active',
                    'is_main',
                ],
            ],
        ]);
        $this->assertEquals(2, count($response->json('data')));
    }

    /**
     * Test non-admin cannot list branches (403).
     */
    public function test_non_admin_cannot_list_branches(): void
    {
        $response = $this->actingAs($this->managerUser, 'sanctum')
            ->getJson('/api/v1/branches');

        $response->assertStatus(403);
    }

    /**
     * Test admin can create a branch.
     */
    public function test_admin_can_create_branch(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/branches', [
                'code' => 'BR002',
                'name' => 'Penang Branch',
                'type' => 'branch',
                'address' => '123 Main Street',
                'city' => 'Penang',
                'state' => 'Pulau Pinang',
                'postal_code' => '10000',
                'country' => 'Malaysia',
                'phone' => '+604-1234567',
                'email' => 'penang@cems.my',
                'is_active' => true,
                'is_main' => false,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'code',
                'name',
                'type',
            ],
        ]);
        $this->assertEquals('BR002', $response->json('data.code'));
        $this->assertDatabaseHas('branches', ['code' => 'BR002']);
    }

    /**
     * Test admin can get a single branch.
     */
    public function test_admin_can_get_branch(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/branches/{$this->branch1->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
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
            ],
        ]);
        $this->assertEquals('HQ', $response->json('data.code'));
    }

    /**
     * Test admin can update a branch.
     */
    public function test_admin_can_update_branch(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/v1/branches/{$this->branch2->id}", [
                'code' => 'BR001',
                'name' => 'Kuala Lumpur Main Branch',
                'type' => 'branch',
                'city' => 'Kuala Lumpur',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'code' => 'BR001',
                'name' => 'Kuala Lumpur Main Branch',
            ],
        ]);
    }

    /**
     * Test admin can deactivate a branch.
     */
    public function test_admin_can_deactivate_branch(): void
    {
        // First, make branch2 not main so it can be deactivated
        $this->branch2->update(['is_main' => false]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/branches/{$this->branch2->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Branch deactivated successfully',
        ]);

        $this->branch2->refresh();
        $this->assertFalse($this->branch2->is_active);
    }

    /**
     * Test cannot deactivate main branch.
     */
    public function test_cannot_deactivate_main_branch(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/branches/{$this->branch1->id}");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot deactivate the main branch',
        ]);
    }

    /**
     * Test non-admin can get their own branch.
     */
    public function test_non_admin_can_get_own_branch(): void
    {
        $response = $this->actingAs($this->managerUser, 'sanctum')
            ->getJson("/api/v1/branches/{$this->branch1->id}");

        $response->assertStatus(200);
        $this->assertEquals('HQ', $response->json('data.code'));
    }

    /**
     * Test non-admin cannot get other branch (403).
     */
    public function test_non_admin_cannot_get_other_branch(): void
    {
        $response = $this->actingAs($this->managerUser, 'sanctum')
            ->getJson("/api/v1/branches/{$this->branch2->id}");

        $response->assertStatus(403);
    }

    /**
     * Test admin can get counters for a branch.
     */
    public function test_admin_can_get_branch_counters(): void
    {
        // Create a counter for branch1
        Counter::create([
            'code' => 'TELLER1',
            'name' => 'Teller 1',
            'branch_id' => $this->branch1->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/branches/{$this->branch1->id}/counters");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'name',
                    'status',
                ],
            ],
        ]);
        $this->assertEquals(1, count($response->json('data')));
    }

    /**
     * Test admin can get users for a branch.
     */
    public function test_admin_can_get_branch_users(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/branches/{$this->branch1->id}/users");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'username',
                    'email',
                    'role',
                ],
            ],
        ]);
        // Should have manager and teller
        $this->assertEquals(2, count($response->json('data')));
    }

    /**
     * Test validation: code is required.
     */
    public function test_create_branch_requires_code(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/branches', [
                'name' => 'Test Branch',
                'type' => 'branch',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    /**
     * Test validation: code must be unique.
     */
    public function test_create_branch_code_must_be_unique(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/branches', [
                'code' => 'HQ', // Already exists
                'name' => 'Test Branch',
                'type' => 'branch',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    /**
     * Test validation: type must be valid.
     */
    public function test_create_branch_requires_valid_type(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/branches', [
                'code' => 'TEST',
                'name' => 'Test Branch',
                'type' => 'invalid_type',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    /**
     * Test validation: email must be valid.
     */
    public function test_create_branch_email_must_be_valid(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/branches', [
                'code' => 'TEST',
                'name' => 'Test Branch',
                'type' => 'branch',
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test branch not found returns 404.
     */
    public function test_branch_not_found_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/branches/99999');

        $response->assertStatus(404);
    }
}
