<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\BankReconciliation;
use App\Models\ChartOfAccount;
use App\Models\Currency;
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
            'password_hash' => Hash::make('Admin@123456'),
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
            'reference_type' => 'BankReconciliation',
            'reference_id' => null,
            'status' => 'Posted',
            'posted_by' => $this->managerUser->id,
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
            ->get('/accounting/reconciliation?account_code=1000&from='.now()->startOfMonth()->toDateString().'&to='.now()->endOfMonth()->toDateString());

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
            ->get('/accounting/reconciliation/report?account_code=1000&from='.now()->startOfMonth()->toDateString().'&to='.now()->endOfMonth()->toDateString());

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
            ->get('/accounting/reconciliation/export?account_code=1000&from='.now()->startOfMonth()->toDateString().'&to='.now()->endOfMonth()->toDateString());

        // Should return some response (could be download or view)
        $this->assertNotEquals(403, $response->status());
    }

    /**
     * Test presentCheck throws exception when check is not in 'issued' status
     */
    public function test_present_check_throws_when_not_issued(): void
    {
        $record = BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'CHK-001',
            'description' => 'Check issued',
            'debit' => '500.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-001',
            'check_date' => now()->toDateString(),
            'check_status' => 'presented',
        ]);

        $service = new \App\Services\ReconciliationService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Check CHK-001 is not in 'issued' status");

        $service->presentCheck($record->id);
    }

    /**
     * Test clearCheck throws exception when check is in wrong status
     */
    public function test_clear_check_throws_when_wrong_status(): void
    {
        $record = BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'CHK-002',
            'description' => 'Check stopped',
            'debit' => '500.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-002',
            'check_date' => now()->toDateString(),
            'check_status' => 'stopped',
        ]);

        $service = new \App\Services\ReconciliationService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Check CHK-002 cannot be cleared from 'stopped' status");

        $service->clearCheck($record->id, now()->toDateString());
    }

    /**
     * Test stopCheck throws exception when check is already cleared
     */
    public function test_stop_check_throws_when_already_cleared(): void
    {
        $record = BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'CHK-003',
            'description' => 'Check cleared',
            'debit' => '500.00',
            'credit' => '0',
            'status' => 'matched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-003',
            'check_date' => now()->toDateString(),
            'check_status' => 'cleared',
        ]);

        $service = new \App\Services\ReconciliationService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Check CHK-003 has already been cleared and cannot be stopped');

        $service->stopCheck($record->id, 'Account closed', $this->managerUser->id);
    }

    /**
     * Test returnCheck marks check as returned
     */
    public function test_return_check_marks_as_returned(): void
    {
        $record = BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'CHK-004',
            'description' => 'Check presented',
            'debit' => '500.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-004',
            'check_date' => now()->toDateString(),
            'check_status' => 'presented',
        ]);

        $service = new \App\Services\ReconciliationService;
        $result = $service->returnCheck($record->id, 'Insufficient funds');

        $record->refresh();
        $this->assertEquals('returned', $record->check_status);
        $this->assertStringContainsString('Returned: Insufficient funds', $record->notes);
    }

    /**
     * Test getOutstandingChecksReport returns all status categories
     */
    public function test_outstanding_checks_report_returns_all_categories(): void
    {
        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->subDays(5)->toDateString(),
            'reference' => 'CHK-ISSUED',
            'description' => 'Check issued',
            'debit' => '100.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-ISSUED',
            'check_date' => now()->subDays(5),
            'check_status' => 'issued',
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->subDays(3)->toDateString(),
            'reference' => 'CHK-PRESENTED',
            'description' => 'Check presented',
            'debit' => '200.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-PRESENTED',
            'check_date' => now()->subDays(3),
            'check_status' => 'presented',
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->subDays(1)->toDateString(),
            'reference' => 'CHK-CLEARED',
            'description' => 'Check cleared',
            'debit' => '300.00',
            'credit' => '0',
            'status' => 'matched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-CLEARED',
            'check_date' => now()->subDays(1),
            'check_status' => 'cleared',
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->subDays(2)->toDateString(),
            'reference' => 'CHK-RETURNED',
            'description' => 'Check returned',
            'debit' => '400.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-RETURNED',
            'check_date' => now()->subDays(2),
            'check_status' => 'returned',
        ]);

        BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->subDays(4)->toDateString(),
            'reference' => 'CHK-STOPPED',
            'description' => 'Check stopped',
            'debit' => '500.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
            'check_number' => 'CHK-STOPPED',
            'check_date' => now()->subDays(4),
            'check_status' => 'stopped',
        ]);

        $service = new \App\Services\ReconciliationService;
        $report = $service->getOutstandingChecksReport('1000');

        $this->assertEquals('1000', $report['account_code']);
        $this->assertArrayHasKey('issued', $report);
        $this->assertArrayHasKey('presented', $report);
        $this->assertArrayHasKey('cleared', $report);
        $this->assertArrayHasKey('returned', $report);
        $this->assertArrayHasKey('stopped', $report);
        $this->assertEquals(1, $report['issued']['count']);
        $this->assertEquals(1, $report['presented']['count']);
        $this->assertEquals(1, $report['cleared']['count']);
        $this->assertEquals(1, $report['returned']['count']);
        $this->assertEquals(1, $report['stopped']['count']);
        $this->assertEquals('100.00', $report['issued']['total']);
        $this->assertEquals('200.00', $report['presented']['total']);
    }

    /**
     * Test getChecksAgingReport with no checks returns empty categories
     */
    public function test_checks_aging_report_empty(): void
    {
        $service = new \App\Services\ReconciliationService;
        $report = $service->getChecksAgingReport('1000');

        $this->assertEquals('1000', $report['account_code']);
        $this->assertArrayHasKey('aging', $report);
        $this->assertEquals(0, $report['aging']['current_0_30']['count']);
        $this->assertEquals(0, $report['aging']['days_31_60']['count']);
        $this->assertEquals(0, $report['aging']['days_61_90']['count']);
        $this->assertEquals(0, $report['aging']['days_91_180']['count']);
        $this->assertEquals(0, $report['aging']['over_180']['count']);
        $this->assertEquals('0', $report['aging']['current_0_30']['total']);
        $this->assertEquals('0', $report['aging']['days_31_60']['total']);
        $this->assertEquals('0', $report['aging']['days_61_90']['total']);
        $this->assertEquals('0', $report['aging']['days_91_180']['total']);
        $this->assertEquals('0', $report['aging']['over_180']['total']);
    }

    /**
     * Test markAsException with empty string reason
     */
    public function test_mark_as_exception_with_empty_reason(): void
    {
        $record = BankReconciliation::create([
            'account_code' => '1000',
            'statement_date' => now()->toDateString(),
            'reference' => 'BANK-EMPTY',
            'description' => 'Test item',
            'debit' => '50.00',
            'credit' => '0',
            'status' => 'unmatched',
            'created_by' => $this->managerUser->id,
        ]);

        $service = new \App\Services\ReconciliationService;
        $result = $service->markAsException($record->id, '', $this->managerUser->id);

        $record->refresh();
        $this->assertEquals('exception', $record->status);
        $this->assertEquals('', $record->notes);
    }
}
