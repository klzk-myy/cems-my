<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected User $managerUser;

    protected User $complianceUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
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

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->complianceUser = User::create([
            'username' => 'compliance1',
            'email' => 'compliance1@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => 'compliance_officer',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Test login page is accessible
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('CEMS-MY');
        $response->assertSee('Login');
    }

    /**
     * Test admin can login with valid credentials
     */
    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'Admin@1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->adminUser);
    }

    /**
     * Test teller can login with valid credentials
     */
    public function test_teller_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'username' => 'teller1',
            'password' => 'Teller@1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->tellerUser);
    }

    /**
     * Test login fails with invalid password
     */
    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'WrongPassword123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test login fails with non-existent email
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->post('/login', [
            'username' => 'nonexistent',
            'password' => 'SomePassword123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test inactive user cannot login
     */
    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::create([
            'username' => 'inactive',
            'email' => 'inactive@cems.my',
            'password_hash' => Hash::make('Inactive@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@cems.my',
            'password' => 'Inactive@1234',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test authenticated user can logout
     */
    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test unauthenticated user is redirected to login
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test admin role methods
     */
    public function test_admin_has_correct_role_permissions(): void
    {
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertTrue($this->adminUser->isManager());
        $this->assertTrue($this->adminUser->isComplianceOfficer());
    }

    /**
     * Test manager role methods
     */
    public function test_manager_has_correct_role_permissions(): void
    {
        $this->assertFalse($this->managerUser->isAdmin());
        $this->assertTrue($this->managerUser->isManager());
        $this->assertFalse($this->managerUser->isComplianceOfficer());
    }

    /**
     * Test teller role methods
     */
    public function test_teller_has_correct_role_permissions(): void
    {
        $this->assertFalse($this->tellerUser->isAdmin());
        $this->assertFalse($this->tellerUser->isManager());
        $this->assertFalse($this->tellerUser->isComplianceOfficer());
    }

    /**
     * Test compliance officer role methods
     */
    public function test_compliance_officer_has_correct_role_permissions(): void
    {
        $this->assertFalse($this->complianceUser->isAdmin());
        $this->assertFalse($this->complianceUser->isManager());
        $this->assertTrue($this->complianceUser->isComplianceOfficer());
    }

    /**
     * Test dashboard is accessible to authenticated users
     */
    public function test_dashboard_is_accessible_to_authenticated_users(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome to CEMS-MY');
    }

    /**
     * Test compliance page is accessible to compliance officers
     */
    public function test_compliance_page_is_accessible_to_compliance(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/compliance');

        $response->assertStatus(200);
        $response->assertSee('Compliance Portal');
    }

    /**
     * Test teller can access dashboard
     */
    public function test_teller_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test password is hashed in database
     */
    public function test_password_is_hashed_in_database(): void
    {
        $user = User::where('email', 'admin@cems.my')->first();

        $this->assertNotEquals('Admin@1234', $user->password_hash);
        $this->assertTrue(Hash::check('Admin@1234', $user->password_hash));
    }

    /**
     * Test teller cannot access user management
     */
    public function test_teller_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/users');

        $response->assertStatus(403);
    }

    /**
     * Test manager cannot access user management
     */
    public function test_manager_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/users');

        $response->assertStatus(403);
    }

    /**
     * Test compliance officer cannot access user management
     */
    public function test_compliance_officer_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/users');

        $response->assertStatus(403);
    }

    /**
     * Test admin can access user management
     */
    public function test_admin_can_access_user_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/users');

        $response->assertStatus(200);
        $response->assertSee('User Management');
    }

    /**
     * Test teller cannot access compliance portal
     */
    public function test_teller_cannot_access_compliance_portal(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/compliance');

        $response->assertStatus(403);
    }

    /**
     * Test manager cannot access compliance portal
     */
    public function test_manager_cannot_access_compliance_portal(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/compliance');

        $response->assertStatus(403);
    }

    /**
     * Test compliance officer can access compliance portal
     */
    public function test_compliance_officer_can_access_compliance_portal(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/compliance');

        $response->assertStatus(200);
    }

    /**
     * Test admin can access compliance portal
     */
    public function test_admin_can_access_compliance_portal(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/compliance');

        $response->assertStatus(200);
    }

    /**
     * Test teller cannot access accounting
     */
    public function test_teller_cannot_access_accounting(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/accounting');

        $response->assertStatus(403);
    }

    /**
     * Test compliance officer cannot access accounting
     */
    public function test_compliance_officer_cannot_access_accounting(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/accounting');

        $response->assertStatus(403);
    }

    /**
     * Test manager can access accounting
     */
    public function test_manager_can_access_accounting(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/accounting');

        $response->assertStatus(200);
    }

    /**
     * Test teller cannot access stock-cash
     */
    public function test_teller_cannot_access_stock_cash(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/stock-cash');

        $response->assertStatus(403);
    }

    /**
     * Test manager can access stock-cash
     */
    public function test_manager_can_access_stock_cash(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/stock-cash');

        $response->assertStatus(200);
    }

    /**
     * Test admin can access stock-cash
     */
    public function test_admin_can_access_stock_cash(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/stock-cash');

        $response->assertStatus(200);
    }

    /**
     * Test login creates audit log entry
     */
    public function test_login_creates_audit_log(): void
    {
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'Admin@1234',
        ]);

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'login',
        ]);
    }

    /**
     * Test failed login creates audit log entry
     */
    public function test_failed_login_creates_audit_log(): void
    {
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'WrongPassword',
        ]);

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'login_failed',
        ]);
    }

    /**
     * Test failed login log does not reveal user status
     */
    public function test_failed_login_log_does_not_reveal_user_status(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => false,
        ]);

        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong',
        ]);

        $this->assertDatabaseMissing('system_logs', [
            'action' => 'login_failed',
            'description' => 'Failed login attempt - inactive account',
        ]);

        $this->assertDatabaseMissing('system_logs', [
            'action' => 'login_failed',
            'description' => 'Failed login attempt - wrong password',
        ]);

        $this->assertDatabaseHas('system_logs', [
            'action' => 'login_failed',
            'description' => 'Failed login attempt',
        ]);
    }
}
