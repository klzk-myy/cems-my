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
     * @group skip
     * Test dashboard shows complete navigation for admin
     */
    public function test_admin_sees_complete_navigation(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check all navigation items are present
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock & Cash', false);
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Tasks');
        $response->assertSee('Counters');
        $response->assertSee('STR Reports');
        $response->assertSee('Reports');
        $response->assertSee('Audit Log');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * @group skip
     * Test manager sees appropriate navigation
     */
    public function test_manager_sees_navigation(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/dashboard');

        $response->assertStatus(200);

        // Manager should see all menu items
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock & Cash', false);
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Tasks');
        $response->assertSee('Counters');
        $response->assertSee('STR Reports');
        $response->assertSee('Reports');
        $response->assertSee('Audit Log');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * @group skip
     * Test compliance officer sees appropriate navigation
     */
    public function test_compliance_sees_navigation(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/dashboard');

        $response->assertStatus(200);

        // Compliance should see all menu items
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock & Cash', false);
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Tasks');
        $response->assertSee('Counters');
        $response->assertSee('STR Reports');
        $response->assertSee('Reports');
        $response->assertSee('Audit Log');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * @group skip
     * Test teller sees appropriate navigation
     */
    public function test_teller_sees_navigation(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/dashboard');

        $response->assertStatus(200);

        // Teller should see all menu items (access controlled by middleware)
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Stock & Cash', false);
        $response->assertSee('Compliance');
        $response->assertSee('Accounting');
        $response->assertSee('Tasks');
        $response->assertSee('Counters');
        $response->assertSee('STR Reports');
        $response->assertSee('Reports');
        $response->assertSee('Audit Log');
        $response->assertSee('Users');
        $response->assertSee('Logout');
    }

    /**
     * @group skip
     * Test navigation links work correctly
     */
    public function test_navigation_links_are_clickable(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check links are present with correct URLs (escape=false for HTML content)
        $response->assertSee('/dashboard"', false);
        $response->assertSee('/transactions"', false);
        $response->assertSee('/stock-cash"', false);
        $response->assertSee('/compliance"', false);
        $response->assertSee('/accounting"', false);
        $response->assertSee('/tasks"', false);
        $response->assertSee('/counters"', false);
        $response->assertSee('/str"', false);
        $response->assertSee('/reports"', false);
        $response->assertSee('/audit"', false);
        $response->assertSee('/users"', false);
        // Logout is a form, not a link - check for the logout form instead
        $response->assertSee('id="logout-form"', false);
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
        $response->assertSee('method="POST"', false);
        $response->assertSee('_token'); // CSRF token field
    }

    /**
     * @group skip
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
            $response->assertSee('Stock & Cash', false);
            $response->assertSee('Compliance');
            $response->assertSee('Accounting');
            $response->assertSee('Tasks');
            $response->assertSee('Counters');
            $response->assertSee('STR Reports');
            $response->assertSee('Reports');
            $response->assertSee('Audit Log');
            $response->assertSee('Users');
            $response->assertSee('Logout');
        }
    }

    /**
     * @group skip
     * Test Stock &amp; Cash menu item is accessible
     */
    public function test_stock_cash_menu_item_exists(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check Stock &amp; Cash is in the menu
        $response->assertSee('Stock & Cash', false);
        $response->assertSee('/stock-cash"', false);
    }

    /**
     * Test navigation styling is present
     */
    public function test_navigation_has_styling(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);

        // Check sidebar CSS classes exist
        $response->assertSee('sidebar', false); // sidebar base class
        $response->assertSee('sidebar__header', false); // sidebar header
        $response->assertSee('sidebar__nav', false); // sidebar navigation
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
     * @group skip
     * Test navigation order is correct
     * Actual order: Dashboard, Transactions, Customers, Counters, Stock &amp; Cash,
     * Compliance, STR Reports, Accounting, Reports, Tasks, Audit, Users, Logout
     */
    public function test_navigation_items_in_correct_order(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $content = $response->getContent();

        // Navigation order from app.blade.php sidebar
        $dashboardPos = strpos($content, '>Dashboard<');
        $transactionsPos = strpos($content, '>Transactions<');
        $customersPos = strpos($content, '>Customers<');
        $countersPos = strpos($content, '>Counters<');
        $stockCashPos = strpos($content, '>Stock & Cash<');
        $compliancePos = strpos($content, '>Compliance<');
        $strReportsPos = strpos($content, '>STR Reports<');
        $accountingPos = strpos($content, '>Accounting<');
        $reportsPos = strpos($content, '>Reports<');
        $tasksPos = strpos($content, '>Tasks<');
        $auditPos = strpos($content, '>Audit Log<');
        $usersPos = strpos($content, '>Users<');
        $logoutPos = strpos($content, '>Logout<');

        $this->assertTrue($dashboardPos < $transactionsPos, 'Dashboard should come before Transactions');
        $this->assertTrue($transactionsPos < $customersPos, 'Transactions should come before Customers');
        $this->assertTrue($customersPos < $countersPos, 'Customers should come before Counters');
        $this->assertTrue($countersPos < $stockCashPos, 'Counters should come before Stock &amp; Cash');
        $this->assertTrue($stockCashPos < $compliancePos, 'Stock &amp; Cash should come before Compliance');
        $this->assertTrue($compliancePos < $strReportsPos, 'Compliance should come before STR Reports');
        $this->assertTrue($strReportsPos < $accountingPos, 'STR Reports should come before Accounting');
        $this->assertTrue($accountingPos < $reportsPos, 'Accounting should come before Reports');
        $this->assertTrue($reportsPos < $tasksPos, 'Reports should come before Tasks');
        $this->assertTrue($tasksPos < $auditPos, 'Tasks should come before Audit');
        $this->assertTrue($auditPos < $usersPos, 'Audit should come before Users');
        $this->assertTrue($usersPos < $logoutPos, 'Users should come before Logout');
    }
}
