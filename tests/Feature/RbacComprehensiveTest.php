<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $managerUser;

    protected User $complianceOfficer;

    protected User $tellerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->complianceOfficer = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => UserRole::ComplianceOfficer,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Test teller can access transaction creation
     */
    public function test_teller_can_access_transaction_create(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/transactions/create');

        $response->assertStatus(200);
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
     * Test teller cannot access accounting module
     */
    public function test_teller_cannot_access_accounting(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/accounting');

        $response->assertStatus(403);
    }

    /**
     * Test teller cannot access compliance module
     */
    public function test_teller_cannot_access_compliance(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/compliance');

        $response->assertStatus(403);
    }

    /**
     * Test teller cannot access reports
     */
    public function test_teller_cannot_access_reports(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/reports');

        $response->assertStatus(403);
    }

    /**
     * Test manager can access accounting module
     */
    public function test_manager_can_access_accounting(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting');

        $response->assertStatus(200);
    }

    /**
     * Test manager can access reports
     */
    public function test_manager_can_access_reports(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/reports');

        $response->assertStatus(200);
    }

    /**
     * Test manager cannot access user management (admin only)
     */
    public function test_manager_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/users');

        $response->assertStatus(403);
    }

    /**
     * Test manager can approve transactions
     */
    public function test_manager_can_approve_transactions(): void
    {
        // Create pending transaction
        $transaction = \App\Models\Transaction::create([
            'customer_id' => 1,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => 'Pending',
            'cdd_level' => 'Enhanced',
            'version' => 0,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('Completed', $transaction->status->value);
    }

    /**
     * Test manager cannot delete users
     */
    public function test_manager_cannot_delete_users(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->delete("/users/{$this->tellerUser->id}");

        $response->assertStatus(403);
    }

    /**
     * Test compliance officer can access compliance module
     */
    public function test_compliance_officer_can_access_compliance(): void
    {
        $response = $this->actingAs($this->complianceOfficer)
            ->get('/compliance');

        $response->assertStatus(200);
    }

    /**
     * Test compliance officer can view flagged transactions
     */
    public function test_compliance_officer_can_view_flagged(): void
    {
        $response = $this->actingAs($this->complianceOfficer)
            ->get('/compliance/flagged');

        $response->assertStatus(200);
    }

    /**
     * Test compliance officer can access STR management
     */
    public function test_compliance_officer_can_access_str(): void
    {
        $response = $this->actingAs($this->complianceOfficer)
            ->get('/str');

        $response->assertStatus(200);
    }

    /**
     * Test compliance officer cannot access user management
     */
    public function test_compliance_officer_cannot_access_users(): void
    {
        $response = $this->actingAs($this->complianceOfficer)->get('/users');

        $response->assertStatus(403);
    }

    /**
     * Test compliance officer cannot access accounting module
     */
    public function test_compliance_officer_cannot_access_accounting(): void
    {
        $response = $this->actingAs($this->complianceOfficer)
            ->get('/accounting');

        $response->assertStatus(403);
    }

    /**
     * Test compliance officer cannot access reports (manager only)
     */
    public function test_compliance_officer_cannot_access_reports(): void
    {
        $response = $this->actingAs($this->complianceOfficer)
            ->get('/reports');

        $response->assertStatus(403);
    }

    /**
     * Test admin has full access to all modules
     */
    public function test_admin_has_full_access(): void
    {
        // User management
        $response = $this->actingAs($this->adminUser)->get('/users');
        $this->assertNotEquals(403, $response->status());

        // Accounting
        $response = $this->actingAs($this->adminUser)->get('/accounting');
        $this->assertEquals(200, $response->status());

        // Compliance
        $response = $this->actingAs($this->adminUser)->get('/compliance');
        $this->assertEquals(200, $response->status());

        // Reports
        $response = $this->actingAs($this->adminUser)->get('/reports');
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test admin can create users
     */
    public function test_admin_can_create_users(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/users', [
            'username' => 'newuser',
            'email' => 'newuser@cems.my',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'Pass@12345678',
            'role' => 'teller',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@cems.my',
        ]);
    }

    /**
     * Test admin can delete users
     */
    public function test_admin_can_delete_users(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete("/users/{$this->tellerUser->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $this->tellerUser->id,
        ]);
    }

    /**
     * Test teller cannot approve transactions
     */
    public function test_teller_cannot_approve_transactions(): void
    {
        $transaction = \App\Models\Transaction::create([
            'customer_id' => 1,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => 'Pending',
            'cdd_level' => 'Enhanced',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertStatus(403);
    }

    /**
     * Test teller can view transactions
     */
    public function test_teller_can_view_transactions(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/transactions');

        $response->assertStatus(200);
    }

    /**
     * Test teller can create transactions
     */
    public function test_teller_can_create_transaction(): void
    {
        // Create customer first
        $customer = \App\Models\Customer::create([
            'full_name' => 'Test Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('123 Test Street'),
            'contact_number_encrypted' => encrypt('0123456789'),
            'email' => 'customer@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Low',
        ]);

        // Create till balance
        \App\Models\TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => '100000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);

        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertRedirect();
    }

    /**
     * Test manager can access trial balance
     */
    public function test_manager_can_access_trial_balance(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/trial-balance');

        $response->assertStatus(200);
    }

    /**
     * Test manager can access profit/loss
     */
    public function test_manager_can_access_profit_loss(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/profit-loss');

        $response->assertStatus(200);
    }

    /**
     * Test manager can access balance sheet
     */
    public function test_manager_can_access_balance_sheet(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/balance-sheet');

        $response->assertStatus(200);
    }

    /**
     * Test manager can access ledger
     */
    public function test_manager_can_access_ledger(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/ledger');

        $response->assertStatus(200);
    }

    /**
     * Test manager can access journal entries
     */
    public function test_manager_can_access_journal(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/journal');

        $response->assertStatus(200);
    }

    /**
     * Test admin can access all accounting sub-pages
     */
    public function test_admin_can_access_all_accounting_pages(): void
    {
        $pages = [
            '/accounting/trial-balance',
            '/accounting/profit-loss',
            '/accounting/balance-sheet',
            '/accounting/ledger',
            '/accounting/journal',
            '/accounting/revaluation',
            '/accounting/periods',
            '/accounting/reconciliation',
            '/accounting/budget',
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($this->adminUser)->get($page);
            $this->assertEquals(200, $response->status(), "Failed: {$page}");
        }
    }

    /**
     * Test roles are correctly assigned via enum
     */
    public function test_user_role_enum_works(): void
    {
        $this->assertTrue($this->adminUser->role->isAdmin());
        $this->assertTrue($this->adminUser->role->isManager());
        $this->assertTrue($this->adminUser->role->isComplianceOfficer());

        $this->assertFalse($this->tellerUser->role->isAdmin());
        $this->assertTrue($this->tellerUser->role->isTeller());

        $this->assertFalse($this->managerUser->role->isTeller());
        $this->assertTrue($this->managerUser->role->isManager());

        $this->assertFalse($this->complianceOfficer->role->isManager());
        $this->assertTrue($this->complianceOfficer->role->isComplianceOfficer());
    }

    /**
     * Test permission methods on enum
     */
    public function test_permission_methods_on_role_enum(): void
    {
        // Admin has all permissions
        $this->assertTrue($this->adminUser->role->canApproveLargeTransactions());
        $this->assertTrue($this->adminUser->role->canAccessCompliance());
        $this->assertTrue($this->adminUser->role->canManageUsers());
        $this->assertTrue($this->adminUser->role->canApproveHandover());

        // Manager can approve large transactions and handover
        $this->assertTrue($this->managerUser->role->canApproveLargeTransactions());
        $this->assertFalse($this->managerUser->role->canManageUsers());
        $this->assertTrue($this->managerUser->role->canApproveHandover());
        $this->assertFalse($this->managerUser->role->canAccessCompliance());

        // Compliance officer can access compliance
        $this->assertTrue($this->complianceOfficer->role->canAccessCompliance());
        $this->assertFalse($this->complianceOfficer->role->canApproveLargeTransactions());
        $this->assertFalse($this->complianceOfficer->role->canManageUsers());

        // Teller has limited permissions
        $this->assertFalse($this->tellerUser->role->canApproveLargeTransactions());
        $this->assertFalse($this->tellerUser->role->canAccessCompliance());
        $this->assertFalse($this->tellerUser->role->canManageUsers());
        $this->assertFalse($this->tellerUser->role->canApproveHandover());
    }
}
