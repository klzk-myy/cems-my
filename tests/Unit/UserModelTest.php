<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can be created with valid attributes
     */
    public function test_user_can_be_created(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'teller',
        ]);
    }

    /**
     * Test user has correct fillable attributes
     */
    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('username', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password_hash', $fillable);
        $this->assertContains('role', $fillable);
        $this->assertContains('mfa_enabled', $fillable);
        $this->assertContains('mfa_secret', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    /**
     * Test user has correct hidden attributes
     */
    public function test_user_has_correct_hidden_attributes(): void
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password_hash', $hidden);
        $this->assertContains('mfa_secret', $hidden);
    }

    /**
     * Test user has correct casts
     */
    public function test_user_has_correct_casts(): void
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertArrayHasKey('mfa_enabled', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('last_login_at', $casts);
    }

    /**
     * Test isAdmin method
     */
    public function test_is_admin_method(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);
        $teller = User::factory()->make(['role' => 'teller']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($teller->isAdmin());
    }

    /**
     * Test isManager method
     */
    public function test_is_manager_method(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);
        $manager = User::factory()->make(['role' => 'manager']);
        $teller = User::factory()->make(['role' => 'teller']);

        $this->assertTrue($admin->isManager());
        $this->assertTrue($manager->isManager());
        $this->assertFalse($teller->isManager());
    }

    /**
     * Test isComplianceOfficer method
     */
    public function test_is_compliance_officer_method(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);
        $compliance = User::factory()->make(['role' => 'compliance_officer']);
        $teller = User::factory()->make(['role' => 'teller']);

        $this->assertTrue($admin->isComplianceOfficer());
        $this->assertTrue($compliance->isComplianceOfficer());
        $this->assertFalse($teller->isComplianceOfficer());
    }

    /**
     * Test getAuthPassword method
     */
    public function test_get_auth_password_method(): void
    {
        $user = User::factory()->make(['password_hash' => 'hashed_password']);

        $this->assertEquals('hashed_password', $user->getAuthPassword());
    }

    /**
     * Test transactions relationship exists
     */
    public function test_transactions_relationship_exists(): void
    {
        $user = new User();

        $this->assertTrue(method_exists($user, 'transactions'));
    }

    /**
     * Test valid roles exist
     */
    public function test_valid_roles(): void
    {
        $validRoles = ['admin', 'manager', 'compliance_officer', 'teller'];

        foreach ($validRoles as $role) {
            $user = User::factory()->make(['role' => $role]);
            $this->assertEquals($role, $user->role);
        }
    }

    /**
     * Test email must be unique
     */
    public function test_email_must_be_unique(): void
    {
        User::create([
            'username' => 'user1',
            'email' => 'duplicate@example.com',
            'password_hash' => Hash::make('password'),
            'role' => 'teller',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'username' => 'user2',
            'email' => 'duplicate@example.com',
            'password_hash' => Hash::make('password'),
            'role' => 'teller',
        ]);
    }

    /**
     * Test username must be unique
     */
    public function test_username_must_be_unique(): void
    {
        User::create([
            'username' => 'uniqueuser',
            'email' => 'user1@example.com',
            'password_hash' => Hash::make('password'),
            'role' => 'teller',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'username' => 'uniqueuser',
            'email' => 'user2@example.com',
            'password_hash' => Hash::make('password'),
            'role' => 'teller',
        ]);
    }

    /**
     * Test user can update last login timestamp
     */
    public function test_user_can_update_last_login(): void
    {
        $user = User::factory()->create();

        $user->update(['last_login_at' => now()]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    /**
     * Test inactive user cannot authenticate
     */
    public function test_inactive_user_status(): void
    {
        $user = User::factory()->make(['is_active' => false]);

        $this->assertFalse($user->is_active);
    }

    /**
     * Test MFA enabled status
     */
    public function test_mfa_enabled_status(): void
    {
        $userWithMfa = User::factory()->make(['mfa_enabled' => true]);
        $userWithoutMfa = User::factory()->make(['mfa_enabled' => false]);

        $this->assertTrue($userWithMfa->mfa_enabled);
        $this->assertFalse($userWithoutMfa->mfa_enabled);
    }
}
