<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $managerUser;

    protected User $complianceUser;

    protected User $tellerUser;

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
     * Test dashboard shows complete navigation for admin
     */
    public function test_admin_sees_complete_navigation(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check all navigation items are present
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock/Cash');
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Reports');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * Test manager sees appropriate navigation
     */
    public function test_manager_sees_navigation(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/dashboard');

        $response->assertStatus(200);

        // Manager should see all menu items
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock/Cash');
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Reports');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * Test compliance officer sees appropriate navigation
     */
    public function test_compliance_sees_navigation(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/dashboard');

        $response->assertStatus(200);

        // Compliance should see all menu items
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock/Cash');
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Reports');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * Test teller sees appropriate navigation
     */
    public function test_teller_sees_navigation(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/dashboard');

        $response->assertStatus(200);

        // Teller should see all menu items (access controlled by middleware)
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock/Cash');
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Reports');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * Test navigation links work correctly
     */
    public function test_navigation_links_are_clickable(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check links are present with correct URLs (escape=false for HTML content)
        $response->assertSee('href="/"', false);
        $response->assertSee('href="/transactions"', false);
        $response->assertSee('href="/stock-cash"', false);
        $response->assertSee('href="/compliance"', false);
        $response->assertSee('href="/accounting"', false);
        $response->assertSee('href="/reports"', false);
        $response->assertSee('href="/users"', false);
        // Logout is a form, not a link - check for the logout form instead
        $response->assertSee('action="/logout"', false);
    }

    /**
     * Test logout link has correct form
     */
    public function test_logout_link_has_csrf_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check logout form exists with CSRF protection
        $response->assertSee('id="logout-form"', false);
        $response->assertSee('action="/logout"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('_token'); // CSRF token field
    }

    /**
     * Test navigation is consistent across pages
     */
    public function test_navigation_consistent_across_pages(): void
    {
        $pages = [
            '/dashboard',
            '/compliance',
            '/accounting',
            '/reports',
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($this->adminUser)->get($page);
            $response->assertStatus(200);

            // All pages should have the same navigation
            $response->assertSee('Dashboard');
            $response->assertSee('Transactions');
            $response->assertSee('Stock/Cash');
            $response->assertSee('Compliance');
            $response->assertSee('Accounting');
            $response->assertSee('Reports');
            $response->assertSee('Users');
            $response->assertSee('Logout');
        }
    }

    /**
     * Test Stock/Cash menu item is accessible
     */
    public function test_stock_cash_menu_item_exists(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check Stock/Cash is in the menu
        $response->assertSee('Stock/Cash');
        $response->assertSee('href="/stock-cash"', false);
    }

    /**
     * Test navigation styling is present
     */
    public function test_navigation_has_styling(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check CSS classes exist
        $response->assertSee('sidebar-header', false); // header class on sidebar
        $response->assertSee('class="nav"', false);
    }

    /**
     * Test unauthenticated user does not see navigation
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test navigation order is correct
     */
    public function test_navigation_items_in_correct_order(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $content = $response->getContent();

        // Check order: Dashboard, Transactions, Stock/Cash, Compliance, Accounting, Reports, Users, Logout
        $dashboardPos = strpos($content, '>Dashboard<');
        $transactionsPos = strpos($content, '>Transactions<');
        $stockCashPos = strpos($content, '>Stock/Cash<');
        $compliancePos = strpos($content, '>Compliance<');
        $accountingPos = strpos($content, '>Accounting<');
        $reportsPos = strpos($content, '>Reports<');
        $usersPos = strpos($content, '>Users<');
        $logoutPos = strpos($content, '>Logout<');

        $this->assertTrue($dashboardPos < $transactionsPos, 'Dashboard should come before Transactions');
        $this->assertTrue($transactionsPos < $stockCashPos, 'Transactions should come before Stock/Cash');
        $this->assertTrue($stockCashPos < $compliancePos, 'Stock/Cash should come before Compliance');
        $this->assertTrue($compliancePos < $accountingPos, 'Compliance should come before Accounting');
        $this->assertTrue($accountingPos < $reportsPos, 'Accounting should come before Reports');
        $this->assertTrue($reportsPos < $usersPos, 'Reports should come before Users');
        $this->assertTrue($usersPos < $logoutPos, 'Users should come before Logout');
    }
}
