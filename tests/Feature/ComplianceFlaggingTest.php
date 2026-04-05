<?php

namespace Tests\Feature;

use App\Enums\CddLevel;
use App\Enums\ComplianceFlagType;
use App\Enums\FlagStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\JournalEntry;
use App\Models\SystemLog;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComplianceFlaggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected User $managerUser;

    protected User $complianceOfficer;

    protected Customer $regularCustomer;

    protected Customer $pepCustomer;

    protected Customer $highRiskCustomer;

    protected TillBalance $tillBalance;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => UserRole::Admin,
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

        // Create customers
        $this->regularCustomer = Customer::create([
            'full_name' => 'Regular Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('123 Regular Street'),
            'contact_number_encrypted' => encrypt('0123456789'),
            'email' => 'regular@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Low',
        ]);

        $this->pepCustomer = Customer::create([
            'full_name' => 'PEP Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('223456789012'),
            'date_of_birth' => '1980-05-15',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('456 PEP Street'),
            'contact_number_encrypted' => encrypt('0198765432'),
            'email' => 'pep@test.com',
            'pep_status' => true,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Medium',
        ]);

        $this->highRiskCustomer = Customer::create([
            'full_name' => 'High Risk Customer',
            'id_type' => 'Passport',
            'id_number_encrypted' => encrypt('323456789012'),
            'date_of_birth' => '1975-03-20',
            'nationality' => 'Foreign',
            'address_encrypted' => encrypt('789 High Risk Avenue'),
            'contact_number_encrypted' => encrypt('0187654321'),
            'email' => 'highrisk@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'High',
        ]);

        // Open till
        $this->tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => '100000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);

        // Create accounting period
        AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create chart of accounts
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Inventory', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Forex Trading Revenue', 'account_type' => 'Revenue', 'is_active' => true]
        );
    }

    /**
     * Test transaction >= RM 50,000 becomes Pending with compliance flag
     */
    public function test_large_transaction_requires_approval_and_creates_flag(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->regularCustomer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000', // > RM 50,000 at 4.72
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'till_id' => 'TILL-001',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->regularCustomer->id)
            ->where('type', TransactionType::Buy)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::Pending, $transaction->status);
        $this->assertEquals(CddLevel::Enhanced, $transaction->cdd_level);

        // Compliance flag should be created
        $this->assertDatabaseHas('flagged_transactions', [
            'transaction_id' => $transaction->id,
            'flag_type' => ComplianceFlagType::LargeTransaction,
            'status' => FlagStatus::Open,
        ]);
    }

    /**
     * Test manager approval completes transaction and creates journal entries
     */
    public function test_manager_approval_completes_transaction_and_creates_journals(): void
    {
        // Create pending transaction
        $transaction = Transaction::create([
            'customer_id' => $this->regularCustomer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => TransactionStatus::Pending,
            'cdd_level' => CddLevel::Enhanced,
            'version' => 0,
        ]);

        // Create flag
        FlaggedTransaction::create([
            'transaction_id' => $transaction->id,
            'flag_type' => ComplianceFlagType::LargeTransaction,
            'flag_reason' => 'Transaction amount exceeds RM 50,000 threshold',
            'status' => FlagStatus::Open,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertRedirect();

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
        $this->assertNotNull($transaction->approved_by);
        $this->assertNotNull($transaction->approved_at);

        // Journal entries should be created
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'Transaction',
            'reference_id' => $transaction->id,
        ]);

        // Journal lines should exist
        $journalEntry = JournalEntry::where('reference_type', 'Transaction')
            ->where('reference_id', $transaction->id)
            ->first();

        $this->assertNotNull($journalEntry);
        $this->assertTrue($journalEntry->lines->count() >= 2);
    }

    /**
     * Test approval creates compliance log entry
     */
    public function test_approval_creates_compliance_log_entry(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->regularCustomer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => TransactionStatus::Pending,
            'cdd_level' => CddLevel::Enhanced,
            'version' => 0,
        ]);

        $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        // System log should record the approval
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->managerUser->id,
            'action' => 'transaction_approved',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);
    }

    /**
     * Test PEP customer + small amount goes OnHold (not pending approval)
     */
    public function test_pep_customer_small_amount_goes_on_hold(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->pepCustomer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '500', // RM 2,360 - below threshold
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->pepCustomer->id)
            ->where('type', TransactionType::Buy)
            ->first();

        $this->assertNotNull($transaction);
        // PEP customers trigger enhanced CDD and OnHold status
        $this->assertEquals(CddLevel::Enhanced, $transaction->cdd_level);
    }

    /**
     * Test high risk customer + large amount goes Pending with flag
     */
    public function test_high_risk_customer_large_amount_goes_pending_with_flag(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->highRiskCustomer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '12000', // > RM 50,000
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'till_id' => 'TILL-001',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->highRiskCustomer->id)
            ->where('type', TransactionType::Buy)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::Pending, $transaction->status);

        // Flag should be created
        $this->assertDatabaseHas('flagged_transactions', [
            'transaction_id' => $transaction->id,
            'status' => FlagStatus::Open,
        ]);
    }

    /**
     * Test compliance officer can view flagged transactions
     */
    public function test_compliance_officer_can_view_flagged_transactions(): void
    {
        // Create flagged transaction
        $transaction = Transaction::create([
            'customer_id' => $this->regularCustomer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => TransactionStatus::Pending,
            'cdd_level' => CddLevel::Enhanced,
        ]);

        FlaggedTransaction::create([
            'transaction_id' => $transaction->id,
            'flag_type' => ComplianceFlagType::LargeTransaction,
            'flag_reason' => 'Large transaction threshold',
            'status' => FlagStatus::Open,
        ]);

        $response = $this->actingAs($this->complianceOfficer)
            ->get('/compliance/flagged');

        $response->assertStatus(200);
        $response->assertSee('Flagged Transactions');
    }

    /**
     * Test compliance officer can resolve flag after review
     */
    public function test_compliance_officer_can_resolve_flag(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->regularCustomer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => TransactionStatus::Pending,
            'cdd_level' => CddLevel::Enhanced,
        ]);

        $flag = FlaggedTransaction::create([
            'transaction_id' => $transaction->id,
            'flag_type' => ComplianceFlagType::LargeTransaction,
            'flag_reason' => 'Large transaction threshold',
            'status' => FlagStatus::Open,
            'assigned_to' => $this->complianceOfficer->id,
        ]);

        $response = $this->actingAs($this->complianceOfficer)
            ->patch("/compliance/flags/{$flag->id}/resolve", [
                'notes' => 'Reviewed and verified legitimate transaction',
            ]);

        $response->assertRedirect();

        $flag->refresh();
        $this->assertEquals(FlagStatus::Resolved, $flag->status);
        $this->assertNotNull($flag->resolved_at);
        $this->assertEquals($this->complianceOfficer->id, $flag->reviewed_by);
    }

    /**
     * Test teller cannot access compliance flagged page
     */
    public function test_teller_cannot_access_compliance_flags(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/compliance/flagged');

        $response->assertStatus(403);
    }

    /**
     * Test manager can approve large transaction
     */
    public function test_manager_can_approve_large_transaction(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->regularCustomer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '12000',
            'amount_local' => '56640.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => TransactionStatus::Pending,
            'cdd_level' => CddLevel::Enhanced,
            'version' => 0,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertRedirect();

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
        $this->assertEquals($this->managerUser->id, $transaction->approved_by);
    }

    /**
     * Test regular transaction below threshold completes immediately
     */
    public function test_regular_transaction_completes_immediately(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->regularCustomer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000', // RM 4,720 - below threshold
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->regularCustomer->id)
            ->where('type', TransactionType::Buy)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
        $this->assertEquals(CddLevel::Simplified, $transaction->cdd_level);

        // No flag should be created for regular transactions
        $this->assertDatabaseMissing('flagged_transactions', [
            'transaction_id' => $transaction->id,
        ]);
    }
}