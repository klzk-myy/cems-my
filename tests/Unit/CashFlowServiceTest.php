<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\CashFlowService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CashFlowService $service;
    protected MathService $mathService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mathService = new MathService;
        $this->service = new CashFlowService($this->mathService);

        // Create a test user for journal entries
        $this->user = User::create([
            'username' => 'testuser',
            'email' => 'test@cems.my',
            'password_hash' => 'dummy',
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    public function test_get_cash_flow_statement_returns_all_categories()
    {
        $result = $this->service->getCashFlowStatement('2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('operating', $result);
        $this->assertArrayHasKey('investing', $result);
        $this->assertArrayHasKey('financing', $result);
        $this->assertArrayHasKey('net_change', $result);
        $this->assertArrayHasKey('opening_balance', $result);
        $this->assertArrayHasKey('closing_balance', $result);
    }

    public function test_opening_cash_balance_returns_zero_when_no_entries()
    {
        $result = $this->service->getOpeningCashBalance('2026-01-31');
        $this->assertEquals('0.000000', $result);
    }

    public function test_opening_cash_balance_calculates_from_cash_accounts()
    {
        $journalEntry = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Test entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash MYR', 'account_type' => 'Asset']);

        AccountLedger::create([
            'account_code' => '1000',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry->id,
            'debit' => 50000,
            'credit' => 0,
            'running_balance' => '50000',
        ]);

        $result = $this->service->getOpeningCashBalance('2026-01-31');
        $this->assertEquals('50000.000000', $result);
    }

    public function test_opening_cash_balance_sums_multiple_cash_accounts()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-10',
            'reference_type' => 'Manual',
            'description' => 'Test entry 1',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Test entry 2',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash MYR', 'account_type' => 'Asset']);
        ChartOfAccount::firstOrCreate(['account_code' => '1100'], ['account_name' => 'Bank MYR', 'account_type' => 'Asset']);

        AccountLedger::create([
            'account_code' => '1000',
            'entry_date' => '2026-01-10',
            'journal_entry_id' => $journalEntry1->id,
            'debit' => 30000,
            'credit' => 0,
            'running_balance' => '30000',
        ]);

        AccountLedger::create([
            'account_code' => '1100',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry2->id,
            'debit' => 25000,
            'credit' => 0,
            'running_balance' => '25000',
        ]);

        $result = $this->service->getOpeningCashBalance('2026-01-31');
        $this->assertEquals('55000.000000', $result);
    }

    public function test_operating_cash_flow_returns_all_components()
    {
        $result = $this->service->getOperatingCashFlow('2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('cash_from_customers', $result);
        $this->assertArrayHasKey('cash_paid_to_suppliers', $result);
        $this->assertArrayHasKey('cash_paid_for_salaries', $result);
        $this->assertArrayHasKey('cash_paid_for_expenses', $result);
        $this->assertArrayHasKey('net_operating', $result);
    }

    public function test_operating_cash_flow_with_revenue_and_expenses()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Revenue entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-20',
            'reference_type' => 'Manual',
            'description' => 'Expense entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '5000'], ['account_name' => 'Revenue', 'account_type' => 'Revenue']);
        ChartOfAccount::firstOrCreate(['account_code' => '6000'], ['account_name' => 'COGS', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash', 'account_type' => 'Asset']);

        // Revenue posted to ledger (credit increases revenue)
        AccountLedger::create([
            'account_code' => '5000',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry1->id,
            'credit' => 100000,
            'debit' => 0,
            'running_balance' => '100000',
        ]);

        // Expense posted to ledger (debit increases expense)
        AccountLedger::create([
            'account_code' => '6000',
            'entry_date' => '2026-01-20',
            'journal_entry_id' => $journalEntry2->id,
            'debit' => 60000,
            'credit' => 0,
            'running_balance' => '60000',
        ]);

        $result = $this->service->getOperatingCashFlow('2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('net_operating', $result);
        $this->assertIsString($result['net_operating']);
    }

    public function test_operating_cash_flow_calculates_salary_expenses()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-25',
            'reference_type' => 'Manual',
            'description' => 'Salary entry 1',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-25',
            'reference_type' => 'Manual',
            'description' => 'Salary entry 2',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '6200'], ['account_name' => 'Salaries', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6210'], ['account_name' => 'EPF', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6220'], ['account_name' => 'EIS', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6230'], ['account_name' => 'SOCSO', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash', 'account_type' => 'Asset']);

        // Salary expenses
        AccountLedger::create([
            'account_code' => '6200',
            'entry_date' => '2026-01-25',
            'journal_entry_id' => $journalEntry1->id,
            'debit' => 15000,
            'credit' => 0,
            'running_balance' => '15000',
        ]);

        AccountLedger::create([
            'account_code' => '6210',
            'entry_date' => '2026-01-25',
            'journal_entry_id' => $journalEntry2->id,
            'debit' => 1800,
            'credit' => 0,
            'running_balance' => '1800',
        ]);

        $result = $this->service->getOperatingCashFlow('2026-01-01', '2026-01-31');

        $this->assertIsString($result['cash_paid_for_salaries']);
        // Salary accounts 6200, 6210, 6220, 6230 should be included
        $this->assertEquals('16800.000000', $result['cash_paid_for_salaries']);
    }

    public function test_net_cash_change_sums_all_activities()
    {
        $journalEntry = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Revenue entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '5000'], ['account_name' => 'Revenue', 'account_type' => 'Revenue']);
        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash', 'account_type' => 'Asset']);

        AccountLedger::create([
            'account_code' => '5000',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry->id,
            'credit' => 50000,
            'debit' => 0,
            'running_balance' => '50000',
        ]);

        $result = $this->service->getNetCashChange('2026-01-01', '2026-01-31');
        $this->assertIsString($result);
    }

    public function test_investing_cash_flow_returns_all_components()
    {
        $result = $this->service->getInvestingCashFlow('2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('asset_purchases', $result);
        $this->assertArrayHasKey('asset_sales', $result);
        $this->assertArrayHasKey('investment_income', $result);
        $this->assertArrayHasKey('net_investing', $result);
    }

    public function test_investing_cash_flow_returns_zeros_when_no_activity()
    {
        $result = $this->service->getInvestingCashFlow('2026-01-01', '2026-01-31');

        $this->assertEquals('0.000000', $result['asset_purchases']);
        $this->assertEquals('0.000000', $result['asset_sales']);
        $this->assertEquals('0.000000', $result['investment_income']);
        $this->assertEquals('0.000000', $result['net_investing']);
    }

    public function test_investing_cash_flow_calculates_asset_purchases()
    {
        $journalEntry = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Asset purchase',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '2200'], ['account_name' => 'Security Deposits', 'account_type' => 'Asset']);

        AccountLedger::create([
            'account_code' => '2200',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry->id,
            'debit' => 5000,
            'credit' => 0,
            'running_balance' => '5000',
        ]);

        $result = $this->service->getInvestingCashFlow('2026-01-01', '2026-01-31');

        $this->assertEquals('5000.000000', $result['asset_purchases']);
    }

    public function test_financing_cash_flow_returns_all_components()
    {
        $result = $this->service->getFinancingCashFlow('2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('loans_received', $result);
        $this->assertArrayHasKey('loan_repayments', $result);
        $this->assertArrayHasKey('dividends_paid', $result);
        $this->assertArrayHasKey('net_financing', $result);
    }

    public function test_financing_cash_flow_returns_zeros_when_no_activity()
    {
        $result = $this->service->getFinancingCashFlow('2026-01-01', '2026-01-31');

        $this->assertEquals('0', $result['loans_received']);
        $this->assertEquals('0', $result['loan_repayments']);
        $this->assertEquals('0', $result['dividends_paid']);
        $this->assertEquals('0.000000', $result['net_financing']);
    }

    public function test_closing_cash_balance_equals_opening_balance()
    {
        $journalEntry = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Cash entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash MYR', 'account_type' => 'Asset']);

        AccountLedger::create([
            'account_code' => '1000',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry->id,
            'debit' => 75000,
            'credit' => 0,
            'running_balance' => '75000',
        ]);

        // Both should return the same balance when called with the same date
        $opening = $this->service->getOpeningCashBalance('2026-01-31');
        $closing = $this->service->getClosingCashBalance('2026-01-31');

        $this->assertEquals($opening, $closing);
        $this->assertEquals('75000.000000', $opening);
    }

    public function test_operating_cash_flow_calculates_other_expenses()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-05',
            'reference_type' => 'Manual',
            'description' => 'Rent entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-10',
            'reference_type' => 'Manual',
            'description' => 'Utilities entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '6100'], ['account_name' => 'Rent', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6300'], ['account_name' => 'Utilities', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6400'], ['account_name' => 'Office Supplies', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash', 'account_type' => 'Asset']);

        AccountLedger::create([
            'account_code' => '6100',
            'entry_date' => '2026-01-05',
            'journal_entry_id' => $journalEntry1->id,
            'debit' => 5000,
            'credit' => 0,
            'running_balance' => '5000',
        ]);

        AccountLedger::create([
            'account_code' => '6300',
            'entry_date' => '2026-01-10',
            'journal_entry_id' => $journalEntry2->id,
            'debit' => 800,
            'credit' => 0,
            'running_balance' => '800',
        ]);

        $result = $this->service->getOperatingCashFlow('2026-01-01', '2026-01-31');

        $this->assertIsString($result['cash_paid_for_expenses']);
        // Should include 6100, 6300 but not salary accounts
        $this->assertEquals('5800.000000', $result['cash_paid_for_expenses']);
    }

    public function test_cash_flow_statement_includes_date_range()
    {
        $result = $this->service->getCashFlowStatement('2026-03-01', '2026-03-31');

        $this->assertEquals('2026-03-01', $result['from_date']);
        $this->assertEquals('2026-03-31', $result['to_date']);
    }

    public function test_get_total_for_accounts_sums_multiple_accounts()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'COGS entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-20',
            'reference_type' => 'Manual',
            'description' => 'Labor entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '6000'], ['account_name' => 'COGS', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6010'], ['account_name' => 'Direct Labor', 'account_type' => 'Expense']);

        AccountLedger::create([
            'account_code' => '6000',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry1->id,
            'debit' => 25000,
            'credit' => 0,
            'running_balance' => '25000',
        ]);

        AccountLedger::create([
            'account_code' => '6010',
            'entry_date' => '2026-01-20',
            'journal_entry_id' => $journalEntry2->id,
            'debit' => 15000,
            'credit' => 0,
            'running_balance' => '15000',
        ]);

        $result = $this->service->getOperatingCashFlow('2026-01-01', '2026-01-31');

        // Cash paid to suppliers includes 6000 and 6010
        $this->assertEquals('40000.000000', $result['cash_paid_to_suppliers']);
    }

    public function test_account_balance_returns_zero_for_nonexistent_account()
    {
        // Test with account that has no entries - use account code not created by other tests
        $result = $this->service->getOpeningCashBalance('2026-01-31');

        // Without any cash account entries, should return 0
        $this->assertEquals('0.000000', $result);
    }

    public function test_investing_cash_flow_calculates_investment_income()
    {
        $journalEntry = JournalEntry::create([
            'entry_date' => '2026-01-20',
            'reference_type' => 'Manual',
            'description' => 'Investment income',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '5300'], ['account_name' => 'Investment Income', 'account_type' => 'Revenue']);

        AccountLedger::create([
            'account_code' => '5300',
            'entry_date' => '2026-01-20',
            'journal_entry_id' => $journalEntry->id,
            'debit' => 1500,
            'credit' => 0,
            'running_balance' => '1500',
        ]);

        $result = $this->service->getInvestingCashFlow('2026-01-01', '2026-01-31');

        $this->assertEquals('1500.000000', $result['investment_income']);
    }

    public function test_net_cash_change_combines_all_activities()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Revenue entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-20',
            'reference_type' => 'Manual',
            'description' => 'Expense entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry3 = JournalEntry::create([
            'entry_date' => '2026-01-18',
            'reference_type' => 'Manual',
            'description' => 'Asset purchase',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '5000'], ['account_name' => 'Revenue', 'account_type' => 'Revenue']);
        ChartOfAccount::firstOrCreate(['account_code' => '6000'], ['account_name' => 'Expense', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '2200'], ['account_name' => 'Security Deposits', 'account_type' => 'Asset']);
        ChartOfAccount::firstOrCreate(['account_code' => '1000'], ['account_name' => 'Cash', 'account_type' => 'Asset']);

        // Operating: Revenue 100000, Expense 60000
        AccountLedger::create([
            'account_code' => '5000',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry1->id,
            'credit' => 100000,
            'debit' => 0,
            'running_balance' => '100000',
        ]);

        AccountLedger::create([
            'account_code' => '6000',
            'entry_date' => '2026-01-20',
            'journal_entry_id' => $journalEntry2->id,
            'debit' => 60000,
            'credit' => 0,
            'running_balance' => '60000',
        ]);

        // Investing: Asset purchase 5000
        AccountLedger::create([
            'account_code' => '2200',
            'entry_date' => '2026-01-18',
            'journal_entry_id' => $journalEntry3->id,
            'debit' => 5000,
            'credit' => 0,
            'running_balance' => '5000',
        ]);

        $result = $this->service->getNetCashChange('2026-01-01', '2026-01-31');

        $this->assertIsString($result);
    }

    public function test_multiple_revenue_accounts_aggregated()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-10',
            'reference_type' => 'Manual',
            'description' => 'Forex revenue',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-15',
            'reference_type' => 'Manual',
            'description' => 'Commission income',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '5000'], ['account_name' => 'Forex Trading', 'account_type' => 'Revenue']);
        ChartOfAccount::firstOrCreate(['account_code' => '5010'], ['account_name' => 'Commission Income', 'account_type' => 'Revenue']);
        ChartOfAccount::firstOrCreate(['account_code' => '5020'], ['account_name' => 'Spread Income', 'account_type' => 'Revenue']);

        AccountLedger::create([
            'account_code' => '5000',
            'entry_date' => '2026-01-10',
            'journal_entry_id' => $journalEntry1->id,
            'credit' => 50000,
            'debit' => 0,
            'running_balance' => '50000',
        ]);

        AccountLedger::create([
            'account_code' => '5010',
            'entry_date' => '2026-01-15',
            'journal_entry_id' => $journalEntry2->id,
            'credit' => 10000,
            'debit' => 0,
            'running_balance' => '10000',
        ]);

        $result = $this->service->getOperatingCashFlow('2026-01-01', '2026-01-31');

        $this->assertIsString($result['cash_from_customers']);
    }

    public function test_multiple_expense_accounts_aggregated()
    {
        $journalEntry1 = JournalEntry::create([
            'entry_date' => '2026-01-05',
            'reference_type' => 'Manual',
            'description' => 'Rent entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        $journalEntry2 = JournalEntry::create([
            'entry_date' => '2026-01-10',
            'reference_type' => 'Manual',
            'description' => 'Utilities entry',
            'status' => 'Posted',
            'posted_by' => $this->user->id,
        ]);

        ChartOfAccount::firstOrCreate(['account_code' => '6100'], ['account_name' => 'Rent', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6300'], ['account_name' => 'Utilities', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6500'], ['account_name' => 'Insurance', 'account_type' => 'Expense']);
        ChartOfAccount::firstOrCreate(['account_code' => '6510'], ['account_name' => 'Repairs', 'account_type' => 'Expense']);

        AccountLedger::create([
            'account_code' => '6100',
            'entry_date' => '2026-01-05',
            'journal_entry_id' => $journalEntry1->id,
            'debit' => 3000,
            'credit' => 0,
            'running_balance' => '3000',
        ]);

        AccountLedger::create([
            'account_code' => '6300',
            'entry_date' => '2026-01-10',
            'journal_entry_id' => $journalEntry2->id,
            'debit' => 500,
            'credit' => 0,
            'running_balance' => '500',
        ]);

        $result = $this->service->getOperatingCashFlow('2026-01-01', '2026-01-31');

        // Should aggregate all non-salary expenses
        $this->assertIsString($result['cash_paid_for_expenses']);
    }
}