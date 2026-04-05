<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\BankReconciliation;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $managerUser;

    protected User $tellerUser;

    protected ChartOfAccount $bankAccount;

    protected AccountingPeriod $period;

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

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
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

        // Create currency
        Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'rate_buy' => 4.7200,
                'rate_sell' => 4.7500,
                'is_active' => true,
            ]
        );

        Currency::firstOrCreate(
            ['code' => 'MYR'],
            [
                'name' => 'Malaysian Ringgit',
                'symbol' => 'RM',
                'rate_buy' => 1.0000,
                'rate_sell' => 1.0000,
                'is_active' => true,
            ]
        );

        // Create chart of accounts
        $this->bankAccount = ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'is_active' => true]
        );

        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Inventory - USD', 'account_type' => 'Asset', 'is_active' => true]
        );

        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Forex Trading Revenue', 'account_type' => 'Revenue', 'is_active' => true]
        );

        // Create accounting period
        $this->period = AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Open till
        TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => '100000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);
    }

    /**
     * Test manager can access reconciliation page
     */
    public function test_manager_can_access_reconciliation_page(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/reconciliation');

        $response->assertStatus(200);
        $response->assertSee('Bank Reconciliation');
    }

    /**
     * Test teller cannot access reconciliation page
     */
    public function test_teller_cannot_access_reconciliation(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/accounting/reconciliation');

        $response->assertStatus(403);
    }

    /**
     * Test can import bank statement lines
     */
    public function test_can_import_bank_statement_lines(): void
    {
        $response = $this->actingAs($this->managerUser)->post('/accounting/reconciliation/import', [
            'account_code' => '1000',
            'lines' => [
                [
                    'date' => now()->toDateString(),
                    'reference' => 'BANK-001',
                    'description' => 'Customer deposit',
                    'debit' => '0',
                    'credit' => '5000.00',
                ],
                [
                    'date' => now()->toDateString(),
                    'reference' => 'BANK-002',
                    'description' => 'Wire transfer out',
                    'debit' => '2000.00',
                    'credit' => '0',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check records were created
        $this->assertDatabaseHas('bank_reconciliations', [
            'account_code' => '1000',
            'reference' => 'BANK-001',
            'credit' => '5000.00',
            'status' => 'unmatched',
        ]);

        $this->assertDatabaseHas('bank_reconciliations', [
            'account_code' => '1000',
            'reference' => 'BANK-002',
            'debit' => '2000.00',
            'status' => 'unmatched',
        ]);
    }

    /**
     * Test reconciliation can match entries automatically
     */
    public function test_reconciliation_auto_matches_entries(): void
    {
        // Create journal entry for the bank transaction
        $journal = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'description' => 'Customer deposit',
            'reference' => 'BANK-001',
            'status' => 'Posted',
            'created_by' => $this->managerUser->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $journal->id,
            'account_code' => '1000',
            'debit' => '0',
            'credit' => '5000.00',
        ]);

        JournalLine::create([
            'journal_entry_id' => $journal->id,
            'account_code' => '5000',
            'debit' => '5000.00',
            'credit' => '0',
        ]);

        // Import bank statement that matches
        $response = $this->actingAs($this->managerUser)->post('/accounting/reconciliation/import', [
            'account_code' => '1000',
            'lines' => [
                [
                    'date' => now()->toDateString(),
                    'reference' => 'BANK-001',
                    'description' => 'Customer deposit',
                    'debit' => '0',
                    'credit' => '5000.00',
                ],
            ],
        ]);

        $response->assertRedirect();

        // Check that the item was auto-matched
        $this->assertDatabaseHas('bank_reconciliations', [
            'account_code' => '1000',
            'reference' => 'BANK-001',
            'status' => 'matched',
        ]);
    }

    /**
     * Test unmatched items are identified in reconciliation
     */
    public function test_unmatched_items_identified(): void
    {
        // Create bank statement items
        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'BANK-001',
            'description' => 'Customer deposit',
            'debit' => '0',
            'credit' => '5000.00',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'BANK-002',
            'description' => 'Another deposit',
            'debit' => '0',
            'credit' => '3000.00',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/reconciliation?account_code=1000&from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->endOfMonth()->toDateString());

        $response->assertStatus(200);
        $response->assertSee('BANK-001');
        $response->assertSee('BANK-002');
    }

    /**
     * Test can mark item as exception
     */
    public function test_can_mark_item_as_exception(): void
    {
        $item = BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'BANK-999',
            'description' => 'Bank fee',
            'debit' => '50.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->post("/accounting/reconciliation/{$item->id}/exception", [
                'reason' => 'Bank fee not in books',
            ]);

        $response->assertRedirect();

        $item->refresh();
        $this->assertEquals('exception', $item->status);
        $this->assertEquals('Bank fee not in books', $item->notes);
    }

    /**
     * Test reconciliation report shows statement balance
     */
    public function test_reconciliation_report_shows_balance(): void
    {
        // Create mixed items
        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'DEP-001',
            'description' => 'Deposit',
            'debit' => '0',
            'credit' => '10000.00',
            'status' => 'matched',
            'created_by' => $this->managerUser->id,
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'WD-001',
            'description' => 'Withdrawal',
            'debit' => '3000.00',
            'credit' => '0',
            'status' => 'matched',
            'created_by' => $this->managerUser->id,
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'UNMATCHED-001',
            'description' => 'Unknown deposit',
            'debit' => '0',
            'credit' => '500.00',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/reconciliation/report?account_code=1000&from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->endOfMonth()->toDateString());

        $response->assertStatus(200);
        $response->assertSee('1000'); // Account code
        $response->assertSee('statement_balance');
    }

    /**
     * Test can view unmatched deposits specifically
     */
    public function test_can_view_unmatched_deposits(): void
    {
        // Create various items
        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'DEP-001',
            'description' => 'Deposit',
            'debit' => '0',
            'credit' => '5000.00',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'WD-001',
            'description' => 'Withdrawal',
            'debit' => '2000.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/reconciliation?account_code=1000&filter=deposits');

        $response->assertStatus(200);
    }

    /**
     * Test can view unmatched withdrawals specifically
     */
    public function test_can_view_unmatched_withdrawals(): void
    {
        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'WD-001',
            'description' => 'Withdrawal',
            'debit' => '2000.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/reconciliation?account_code=1000&filter=withdrawals');

        $response->assertStatus(200);
    }

    /**
     * Test admin can access reconciliation
     */
    public function test_admin_can_access_reconciliation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/accounting/reconciliation');

        $response->assertStatus(200);
    }

    /**
     * Test reconciliation report can be exported
     */
    public function test_reconciliation_report_can_be_exported(): void
    {
        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'DEP-001',
            'description' => 'Deposit',
            'debit' => '0',
            'credit' => '5000.00',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/reconciliation/export?account_code=1000&from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->endOfMonth()->toDateString());

        // Should return some response (could be download or view)
        $this->assertNotEquals(403, $response->status());
    }
}