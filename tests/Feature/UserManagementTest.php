<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@123456'),
            'role' => 'admin',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Test admin can view user list
     */
    public function test_admin_can_view_user_list(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/users');

        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertSee('admin');
        $response->assertSee('teller1');
    }

    /**
     * Test non-admin cannot access user management
     */
    public function test_non_admin_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/users');

        $response->assertStatus(403);
    }

    /**
     * Test admin can create new user
     */
    public function test_admin_can_create_new_user(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'newteller',
            'email' => 'newteller@cems.my',
            'password' => 'NewPass@1234',
            'password_confirmation' => 'NewPass@1234',
            'role' => 'teller',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'newteller',
            'email' => 'newteller@cems.my',
            'role' => 'teller',
        ]);
    }

    /**
     * Test cannot create user with duplicate email
     */
    public function test_cannot_create_user_with_duplicate_email(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'anotheruser',
            'email' => 'teller1@cems.my',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'Pass@12345678',
            'role' => 'teller',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test cannot create user with weak password
     */
    public function test_cannot_create_user_with_weak_password(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'weakuser',
            'email' => 'weak@cems.my',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'teller',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test admin can update user role
     */
    public function test_admin_can_update_user_role(): void
    {
        $response = $this->actingAs($this->adminUser)->put("/users/{$this->tellerUser->id}", [
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'role' => 'manager',
            'is_active' => true,
        ]);

        $response->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'id' => $this->tellerUser->id,
            'role' => 'manager',
        ]);
    }

    /**
     * Test admin can deactivate user
     */
    public function test_admin_can_deactivate_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post("/users/{$this->tellerUser->id}/toggle");

        $response->assertRedirect('/users');

        $this->tellerUser->refresh();
        $this->assertFalse($this->tellerUser->is_active);
    }

    /**
     * Test admin can activate user
     */
    public function test_admin_can_activate_user(): void
    {
        $this->tellerUser->update(['is_active' => false]);

        $response = $this->actingAs($this->adminUser)
            ->post("/users/{$this->tellerUser->id}/toggle");

        $response->assertRedirect('/users');

        $this->tellerUser->refresh();
        $this->assertTrue($this->tellerUser->is_active);
    }

    /**
     * Test admin cannot delete themselves
     */
    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete("/users/{$this->adminUser->id}");

        $response->assertRedirect('/users');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $this->adminUser->id,
        ]);
    }

    /**
     * Test admin can delete other users
     */
    public function test_admin_can_delete_other_users(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete("/users/{$this->tellerUser->id}");

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('users', [
            'id' => $this->tellerUser->id,
        ]);
    }

    /**
     * Test cannot delete last admin
     */
    public function test_cannot_delete_last_admin(): void
    {
        // Delete teller first to avoid foreign key issues
        User::destroy($this->tellerUser->id);

        // Try to delete the only admin
        $response = $this->actingAs($this->adminUser)
            ->delete("/users/{$this->adminUser->id}");

        $response->assertRedirect('/users');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $this->adminUser->id,
        ]);
    }

    /**
     * Test create user page is accessible
     */
    public function test_create_user_page_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/users/create');

        $response->assertStatus(200);
        $response->assertSee('Create New User');
    }

    /**
     * Test edit user page is accessible
     */
    public function test_edit_user_page_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/users/{$this->tellerUser->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit User');
    }

    /**
     * Test user list paginates correctly
     */
    public function test_user_list_paginates(): void
    {
        // Create additional users
        for ($i = 1; $i <= 25; $i++) {
            User::create([
                'username' => "user{$i}",
                'email' => "user{$i}@cems.my",
                'password_hash' => Hash::make('Pass@12345678'),
                'role' => 'teller',
                'mfa_enabled' => false,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->adminUser)->get('/users');

        $response->assertStatus(200);
        // Should show pagination controls
        $response->assertSee('Next');
    }

    /**
     * Test user creation validates required fields
     */
    public function test_user_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => '',
            'email' => '',
            'password' => '',
            'role' => '',
        ]);

        $response->assertSessionHasErrors(['username', 'email', 'password', 'role']);
    }

    /**
     * Test user creation validates email format
     */
    public function test_user_creation_validates_email_format(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'testuser',
            'email' => 'invalid-email',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'Pass@12345678',
            'role' => 'teller',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test user creation validates password confirmation
     */
    public function test_user_creation_validates_password_confirmation(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'testuser',
            'email' => 'test@cems.my',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'DifferentPass@1234',
            'role' => 'teller',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test user creation validates role options
     */
    public function test_user_creation_validates_role_options(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'testuser',
            'email' => 'test@cems.my',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'Pass@12345678',
            'role' => 'invalid_role',
        ]);

        $response->assertSessionHasErrors('role');
    }

    /**
     * Test user creation creates audit log entry
     */
    public function test_user_creation_creates_audit_log(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'auditusertest',
            'email' => 'auditusertest@cems.my',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'Pass@12345678',
            'role' => 'teller',
        ]);

        $newUser = User::where('email', 'auditusertest@cems.my')->first();

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'user_created',
            'entity_type' => 'User',
            'entity_id' => $newUser->id,
        ]);
    }

    /**
     * Test user update creates audit log entry
     */
    public function test_user_update_creates_audit_log(): void
    {
        $response = $this->actingAs($this->adminUser)->put("/users/{$this->tellerUser->id}", [
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'user_updated',
            'entity_type' => 'User',
            'entity_id' => $this->tellerUser->id,
        ]);
    }

    /**
     * Test user deletion creates audit log entry
     */
    public function test_user_deletion_creates_audit_log(): void
    {
        $tellerId = $this->tellerUser->id;

        $response = $this->actingAs($this->adminUser)
            ->delete("/users/{$this->tellerUser->id}");

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'user_deleted',
            'entity_type' => 'User',
            'entity_id' => $tellerId,
        ]);
    }

    /**
     * Test user status toggle creates audit log entry
     */
    public function test_user_status_toggle_creates_audit_log(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post("/users/{$this->tellerUser->id}/toggle");

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'user_status_toggled',
            'entity_type' => 'User',
            'entity_id' => $this->tellerUser->id,
        ]);
    }

    /**
     * Test user update logs old and new role values
     */
    public function test_user_update_logs_role_changes(): void
    {
        $oldRole = $this->tellerUser->role;

        $response = $this->actingAs($this->adminUser)->put("/users/{$this->tellerUser->id}", [
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'role' => 'manager',
            'is_active' => true,
        ]);

        $log = \App\Models\SystemLog::where('action', 'user_updated')
            ->where('entity_id', $this->tellerUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('role', $log->old_values ?? []);
        $this->assertArrayHasKey('role', $log->new_values ?? []);
        // Compare enum values as strings since old_values/new_values are stored as JSON
        $this->assertEquals($oldRole->value, $log->old_values['role'] ?? null);
        $this->assertEquals('manager', $log->new_values['role'] ?? null);
    }
}
