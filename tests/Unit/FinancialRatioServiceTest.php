<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\AccountLedger;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\AccountingPeriod;
use App\Services\FinancialRatioService;
use App\Services\MathService;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialRatioServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialRatioService $service;
    protected MathService $mathService;
    protected AccountingService $accountingService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mathService = new MathService;
        $this->accountingService = new AccountingService($this->mathService);
        $this->service = new FinancialRatioService($this->mathService);

        // Disable foreign key checks for truncation
        DB::statement('PRAGMA foreign_keys = OFF');
        ChartOfAccount::truncate();
        AccountLedger::truncate();
        JournalEntry::query()->truncate();
        DB::statement('PRAGMA foreign_keys = ON');

        // Create a test user
        $this->user = User::factory()->create();

        // Create accounting period for 2026-01
        AccountingPeriod::create([
            'period_code' => '2026-01',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'period_type' => 'month',
            'status' => 'open',
        ]);
    }

    /**
     * Helper to create a chart of account.
     */
    protected function createAccount(string $code, string $name, string $type, ?string $class = null): ChartOfAccount
    {
        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'account_class' => $class,
        ]);
    }

    /**
     * Helper to create a balanced journal entry with ledger entries.
     */
    protected function createJournalEntry(array $lines, string $date, string $description = 'Test entry'): JournalEntry
    {
        return $this->accountingService->createJournalEntry(
            $lines,
            'Test',
            null,
            $description,
            $date,
            $this->user->id
        );
    }

    // ============================================
    // Tests for getLiquidityRatios
    // ============================================

    public function test_liquidity_ratios_with_assets_and_liabilities()
    {
        // Create chart of accounts for liquidity test
        $this->createAccount('1000', 'Cash', 'Asset', 'Cash');
        $this->createAccount('1100', 'Bank', 'Asset', 'Cash');
        $this->createAccount('2000', 'Inventory', 'Asset', 'Inventory');
        $this->createAccount('3000', 'Payables', 'Liability', 'Current');
        $this->createAccount('4000', 'Revenue', 'Revenue'); // For balancing

        // Create ledger entries via journal entry
        // Total debits = 50000 + 20000 + 20000 = 90000
        // Total credits = 30000 + 60000 = 90000 (balanced)
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 50000, 'credit' => 0],   // Asset debit
            ['account_code' => '1100', 'debit' => 20000, 'credit' => 0],  // Asset debit
            ['account_code' => '2000', 'debit' => 20000, 'credit' => 0],  // Asset debit
            ['account_code' => '3000', 'debit' => 0, 'credit' => 30000],  // Liability credit
            ['account_code' => '4000', 'debit' => 0, 'credit' => 60000],  // Revenue credit (balancer)
        ], '2026-01-31', 'Assets and liabilities entry');

        $result = $this->service->getLiquidityRatios('2026-01-31');

        $this->assertArrayHasKey('current_ratio', $result);
        $this->assertArrayHasKey('quick_ratio', $result);
        $this->assertArrayHasKey('cash_ratio', $result);
        $this->assertArrayHasKey('current_assets', $result);
        $this->assertArrayHasKey('current_liabilities', $result);
        $this->assertArrayHasKey('inventory', $result);
        $this->assertArrayHasKey('cash', $result);

        // Current assets = 50000 + 20000 + 20000 = 90000
        $this->assertEquals('90000.000000', $result['current_assets']);
        // Current liabilities = 30000
        $this->assertEquals('30000.000000', $result['current_liabilities']);
        // Inventory = 20000
        $this->assertEquals('20000.000000', $result['inventory']);
        // Cash = 50000 + 20000 = 70000 (accounts 1000-1499)
        $this->assertEquals('70000.000000', $result['cash']);

        // Current ratio = 90000/30000 = 3.000000
        $this->assertEquals('3.000000', $result['current_ratio']);
        // Quick ratio = (90000 - 20000) / 30000 = 70000/30000 = 2.333333
        $this->assertEquals('2.333333', $result['quick_ratio']);
        // Cash ratio = 70000/30000 = 2.333333
        $this->assertEquals('2.333333', $result['cash_ratio']);
    }

    public function test_liquidity_ratios_with_zero_liabilities()
    {
        $this->createAccount('1000', 'Cash', 'Asset', 'Cash');
        $this->createAccount('4000', 'Revenue', 'Revenue');

        // Balanced: Debit 50000, Credit 50000
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 50000, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 50000],
        ], '2026-01-31', 'Cash entry');

        $result = $this->service->getLiquidityRatios('2026-01-31');

        // When liabilities are 0, should return '0' not divide by zero
        $this->assertEquals('0', $result['current_ratio']);
        $this->assertEquals('0', $result['quick_ratio']);
        $this->assertEquals('0', $result['cash_ratio']);
    }

    public function test_liquidity_ratios_with_no_inventory_account_class()
    {
        // Test inventory fallback to 2000-2499 range when account_class is not set
        $this->createAccount('1000', 'Cash', 'Asset', 'Cash');
        $this->createAccount('2200', 'Inventory Items', 'Asset'); // No account_class
        $this->createAccount('3000', 'Payables', 'Liability', 'Current');
        $this->createAccount('4000', 'Revenue', 'Revenue');

        // Balanced: Debits 50000 + 15000 = 65000, Credits 20000 + 45000 = 65000
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 50000, 'credit' => 0],
            ['account_code' => '2200', 'debit' => 15000, 'credit' => 0],
            ['account_code' => '3000', 'debit' => 0, 'credit' => 20000],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 45000],
        ], '2026-01-31', 'Inventory test');

        $result = $this->service->getLiquidityRatios('2026-01-31');

        // Inventory should fall back to 2200 (in 2000-2499 range)
        $this->assertEquals('15000.000000', $result['inventory']);
        // Current assets = 50000 + 15000 = 65000
        $this->assertEquals('65000.000000', $result['current_assets']);
    }

    // ============================================
    // Tests for getProfitabilityRatios
    // ============================================

    public function test_profitability_ratios_calculates_margins()
    {
        // Create revenue and expense accounts using codes similar to working test
        $this->createAccount('5000', 'Forex Revenue', 'Revenue');
        $this->createAccount('6000', 'Forex Loss', 'Expense'); // In 6000-6499 range (COGS)
        $this->createAccount('4000', 'Capital', 'Equity');

        // Balanced: Debits 60000 + 40000 = 100000, Credits 100000
        $this->createJournalEntry([
            ['account_code' => '5000', 'debit' => 0, 'credit' => 100000], // Revenue credit
            ['account_code' => '6000', 'debit' => 60000, 'credit' => 0],   // COGS debit
            ['account_code' => '4000', 'debit' => 40000, 'credit' => 0],   // Equity debit (decreases) to balance
        ], '2026-01-15', 'Revenue and expense entry');

        $result = $this->service->getProfitabilityRatios('2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('gross_profit_margin', $result);
        $this->assertArrayHasKey('net_profit_margin', $result);
        $this->assertArrayHasKey('roe', $result);
        $this->assertArrayHasKey('roa', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('cogs', $result);
        $this->assertArrayHasKey('gross_profit', $result);
        $this->assertArrayHasKey('net_income', $result);

        // Revenue = 100000 (credits - debits)
        $this->assertEquals('100000.000000', $result['revenue']);
        // COGS = 60000 (debits - credits, in 6000-6499 range)
        $this->assertEquals('60000.000000', $result['cogs']);
        // Gross profit = 100000 - 60000 = 40000
        $this->assertEquals('40000.000000', $result['gross_profit']);
        // Net income = Revenue - Expenses = 100000 - 60000 = 40000
        $this->assertEquals('40000.000000', $result['net_income']);

        // Gross margin = 40000/100000 = 0.4000
        $this->assertEquals('0.400000', $result['gross_profit_margin']);
        // Net margin = 40000/100000 = 0.4000
        $this->assertEquals('0.400000', $result['net_profit_margin']);
        // ROE = 40000/-40000 = -1.000000 (equity is -40000 after debit)
        $this->assertEquals('-1.000000', $result['roe']);
        // ROA = 40000/0 = 0 (no assets)
        $this->assertEquals('0', $result['roa']);
    }

    public function test_profitability_ratios_with_zero_revenue()
    {
        $this->createAccount('5000', 'Forex Revenue', 'Revenue');
        $this->createAccount('6000', 'Forex Loss', 'Expense');
        $this->createAccount('4000', 'Capital', 'Equity');

        // Balanced: Debits 10000, Credits 10000
        $this->createJournalEntry([
            ['account_code' => '5000', 'debit' => 5000, 'credit' => 10000], // Revenue: net credit = 5000
            ['account_code' => '6000', 'debit' => 5000, 'credit' => 0],      // Expense: 5000
            ['account_code' => '4000', 'debit' => 0, 'credit' => 0],         // Equity: no change
        ], '2026-01-20', 'Zero revenue test');

        $result = $this->service->getProfitabilityRatios('2026-01-01', '2026-01-31');

        // Revenue = 10000 - 5000 = 5000
        $this->assertEquals('5000.000000', $result['revenue']);
        // Net income = 5000 - 5000 = 0
        $this->assertEquals('0.000000', $result['net_income']);
        // Margins should be 0 when net income is 0
        $this->assertEquals('0.000000', $result['gross_profit_margin']);
        $this->assertEquals('0.000000', $result['net_profit_margin']);
    }

    public function test_profitability_ratios_uses_account_type_expense()
    {
        // Create multiple expense accounts to test total expenses calculation
        $this->createAccount('5000', 'Revenue', 'Revenue');
        $this->createAccount('7000', 'Expense Type 1', 'Expense'); // Outside 6000-6499 range for COGS
        $this->createAccount('6100', 'Expense Type 2', 'Expense'); // In 6000-6499 range for COGS
        $this->createAccount('4000', 'Capital', 'Equity');

        // Balanced: Debits 20000 + 30000 = 50000, Credits 100000
        $this->createJournalEntry([
            ['account_code' => '5000', 'debit' => 0, 'credit' => 100000],
            ['account_code' => '7000', 'debit' => 20000, 'credit' => 0],  // Not COGS (outside 6000-6499)
            ['account_code' => '6100', 'debit' => 30000, 'credit' => 0],  // Is COGS (in 6000-6499)
            ['account_code' => '4000', 'debit' => 50000, 'credit' => 0],  // Balancer
        ], '2026-01-20', 'Multiple expenses test');

        $result = $this->service->getProfitabilityRatios('2026-01-01', '2026-01-31');

        // Revenue = 100000
        $this->assertEquals('100000.000000', $result['revenue']);
        // COGS = 30000 (only 6100 is in 6000-6499 range)
        $this->assertEquals('30000.000000', $result['cogs']);
        // Total expenses = 20000 + 30000 = 50000
        // Net income = 100000 - 50000 = 50000
        $this->assertEquals('50000.000000', $result['net_income']);
        // Gross profit = 100000 - 30000 = 70000
        $this->assertEquals('70000.000000', $result['gross_profit']);
    }

    // ============================================
    // Tests for getLeverageRatios
    // ============================================

    public function test_leverage_ratios_calculates_debt_to_equity()
    {
        $this->createAccount('3000', 'Payables', 'Liability');
        $this->createAccount('3100', 'Long Term Debt', 'Liability');
        $this->createAccount('4000', 'Capital', 'Equity');
        $this->createAccount('1000', 'Cash', 'Asset');

        // Balanced: Debits 150000, Credits 50000 + 30000 + 70000 = 150000
        $this->createJournalEntry([
            ['account_code' => '3000', 'debit' => 0, 'credit' => 50000],   // Liability credit
            ['account_code' => '3100', 'debit' => 0, 'credit' => 30000],   // Liability credit
            ['account_code' => '4000', 'debit' => 0, 'credit' => 70000],   // Equity credit
            ['account_code' => '1000', 'debit' => 150000, 'credit' => 0], // Asset debit
        ], '2026-01-31', 'Debt and equity entry');

        $result = $this->service->getLeverageRatios('2026-01-31');

        $this->assertArrayHasKey('debt_to_equity', $result);
        $this->assertArrayHasKey('debt_to_assets', $result);
        $this->assertArrayHasKey('total_debt', $result);
        $this->assertArrayHasKey('equity', $result);
        $this->assertArrayHasKey('total_assets', $result);

        // Total debt = 50000 + 30000 = 80000 (all liabilities)
        $this->assertEquals('80000.000000', $result['total_debt']);
        // Equity = 70000
        $this->assertEquals('70000.000000', $result['equity']);
        // Total assets = 150000 (all assets)
        $this->assertEquals('150000.000000', $result['total_assets']);

        // Debt to equity = 80000/70000 = 1.142857
        $this->assertEquals('1.142857', $result['debt_to_equity']);
        // Debt to assets = 80000/150000 = 0.533333
        $this->assertEquals('0.533333', $result['debt_to_assets']);
    }

    public function test_leverage_ratios_with_zero_equity()
    {
        $this->createAccount('3000', 'Payables', 'Liability');
        $this->createAccount('4000', 'Capital', 'Equity');
        $this->createAccount('1000', 'Cash', 'Asset');

        // Balanced: Debits 50000, Credits 50000
        $this->createJournalEntry([
            ['account_code' => '3000', 'debit' => 0, 'credit' => 50000],
            ['account_code' => '1000', 'debit' => 50000, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 0], // Zero equity
        ], '2026-01-31', 'Zero equity test');

        $result = $this->service->getLeverageRatios('2026-01-31');

        // When equity is 0, should return '0' not divide by zero
        $this->assertEquals('0', $result['debt_to_equity']);
        // Debt to assets = 50000/50000 = 1.000000
        $this->assertEquals('1.000000', $result['debt_to_assets']);
    }

    // ============================================
    // Tests for getEfficiencyRatios
    // ============================================

    public function test_efficiency_ratios_calculates_asset_turnover()
    {
        $this->createAccount('5000', 'Revenue', 'Revenue');
        $this->createAccount('6000', 'COGS', 'Expense'); // In 6000-6499 range
        $this->createAccount('2000', 'Inventory', 'Asset', 'Inventory');
        $this->createAccount('1000', 'Cash', 'Asset');

        // Balanced: Debits 60000 + 25000 + 15000 = 100000, Credits 100000
        $this->createJournalEntry([
            ['account_code' => '5000', 'debit' => 0, 'credit' => 100000],
            ['account_code' => '6000', 'debit' => 60000, 'credit' => 0],
            ['account_code' => '2000', 'debit' => 25000, 'credit' => 0],
            ['account_code' => '1000', 'debit' => 15000, 'credit' => 0],
        ], '2026-01-15', 'Efficiency test');

        $result = $this->service->getEfficiencyRatios('2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('asset_turnover', $result);
        $this->assertArrayHasKey('inventory_turnover', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('total_assets', $result);
        $this->assertArrayHasKey('cogs', $result);
        $this->assertArrayHasKey('inventory', $result);

        // Revenue = 100000
        $this->assertEquals('100000.000000', $result['revenue']);
        // Total assets = 25000 + 15000 = 40000
        $this->assertEquals('40000.000000', $result['total_assets']);
        // COGS = 60000 (6000-6499 range)
        $this->assertEquals('60000.000000', $result['cogs']);
        // Inventory = 25000
        $this->assertEquals('25000.000000', $result['inventory']);

        // Asset turnover = 100000/40000 = 2.500000
        $this->assertEquals('2.500000', $result['asset_turnover']);
        // Inventory turnover = 60000/25000 = 2.400000
        $this->assertEquals('2.400000', $result['inventory_turnover']);
    }

    public function test_efficiency_ratios_with_zero_inventory()
    {
        $this->createAccount('5000', 'Revenue', 'Revenue');
        $this->createAccount('6000', 'COGS', 'Expense');
        $this->createAccount('2000', 'Inventory', 'Asset', 'Inventory');
        $this->createAccount('1000', 'Cash', 'Asset');

        // Balanced: Debits 60000 + 40000 = 100000, Credits 100000
        $this->createJournalEntry([
            ['account_code' => '5000', 'debit' => 0, 'credit' => 100000],
            ['account_code' => '6000', 'debit' => 60000, 'credit' => 0],
            ['account_code' => '2000', 'debit' => 0, 'credit' => 0], // Zero inventory
            ['account_code' => '1000', 'debit' => 40000, 'credit' => 0],
        ], '2026-01-31', 'Zero inventory test');

        $result = $this->service->getEfficiencyRatios('2026-01-01', '2026-01-31');

        // When inventory is 0, inventory turnover should be 0
        $this->assertEquals('0', $result['inventory_turnover']);
    }

    // ============================================
    // Tests for getAllRatios
    // ============================================

    public function test_get_all_ratios_returns_all_categories()
    {
        $this->createAccount('1000', 'Cash', 'Asset', 'Cash');
        $this->createAccount('3000', 'Payables', 'Liability', 'Current');
        $this->createAccount('5000', 'Revenue', 'Revenue');
        $this->createAccount('6000', 'Expense', 'Expense');
        $this->createAccount('4000', 'Capital', 'Equity');

        // Balanced: Debits 100000 + 30000 = 130000, Credits 50000 + 80000 = 130000
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 100000, 'credit' => 0],
            ['account_code' => '3000', 'debit' => 0, 'credit' => 50000],
            ['account_code' => '5000', 'debit' => 0, 'credit' => 80000],
            ['account_code' => '6000', 'debit' => 30000, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 0], // Balancer
        ], '2026-01-31', 'Full ratios test');

        $result = $this->service->getAllRatios('2026-01-31', '2026-01-01', '2026-01-31');

        $this->assertArrayHasKey('liquidity', $result);
        $this->assertArrayHasKey('profitability', $result);
        $this->assertArrayHasKey('leverage', $result);
        $this->assertArrayHasKey('efficiency', $result);
        $this->assertArrayHasKey('as_of_date', $result);
        $this->assertArrayHasKey('from_date', $result);
        $this->assertArrayHasKey('to_date', $result);

        $this->assertEquals('2026-01-31', $result['as_of_date']);
        $this->assertEquals('2026-01-01', $result['from_date']);
        $this->assertEquals('2026-01-31', $result['to_date']);

        // Verify liquidity ratios are present
        $this->assertArrayHasKey('current_ratio', $result['liquidity']);
        $this->assertArrayHasKey('quick_ratio', $result['liquidity']);
        $this->assertArrayHasKey('cash_ratio', $result['liquidity']);

        // Verify profitability ratios are present
        $this->assertArrayHasKey('gross_profit_margin', $result['profitability']);
        $this->assertArrayHasKey('net_profit_margin', $result['profitability']);
        $this->assertArrayHasKey('roe', $result['profitability']);

        // Verify leverage ratios are present
        $this->assertArrayHasKey('debt_to_equity', $result['leverage']);
        $this->assertArrayHasKey('debt_to_assets', $result['leverage']);

        // Verify efficiency ratios are present
        $this->assertArrayHasKey('asset_turnover', $result['efficiency']);
        $this->assertArrayHasKey('inventory_turnover', $result['efficiency']);
    }

    public function test_get_all_ratios_with_empty_ledger_entries()
    {
        // Create chart of accounts but no ledger entries
        $this->createAccount('1000', 'Cash', 'Asset', 'Cash');
        $this->createAccount('3000', 'Payables', 'Liability', 'Current');
        $this->createAccount('5000', 'Revenue', 'Revenue');
        $this->createAccount('6000', 'Expense', 'Expense');
        $this->createAccount('4000', 'Capital', 'Equity');

        $result = $this->service->getAllRatios('2026-01-31', '2026-01-01', '2026-01-31');

        // All ratios should be 0 when there are no ledger entries
        $this->assertEquals('0', $result['liquidity']['current_ratio']);
        $this->assertEquals('0', $result['profitability']['gross_profit_margin']);
        $this->assertEquals('0', $result['leverage']['debt_to_equity']);
        $this->assertEquals('0', $result['efficiency']['asset_turnover']);
    }

    // ============================================
    // Tests for account balance lookup
    // ============================================

    public function test_get_account_balance_uses_latest_entry_before_date()
    {
        $this->createAccount('1000', 'Cash', 'Asset', 'Cash');
        $this->createAccount('4000', 'Revenue', 'Revenue');

        // Entry 1: Debit 10000, Credit 10000 (net 0 for revenue)
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 10000, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 10000],
        ], '2026-01-15', 'First entry');

        // Entry 2: Debit 20000, Credit 20000 (net 0 for revenue)
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 20000, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 20000],
        ], '2026-01-20', 'Second entry');

        // Entry 3: Debit 5000, Credit 5000 (net 0 for revenue)
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 5000, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 5000],
        ], '2026-01-25', 'Third entry');

        // Entry after the asOfDate should not be used
        $this->createJournalEntry([
            ['account_code' => '1000', 'debit' => 10000, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 10000],
        ], '2026-02-05', 'After date entry');

        $result = $this->service->getLiquidityRatios('2026-01-31');

        // Should use entry from 2026-01-25 with running_balance of 35000
        $this->assertEquals('35000.000000', $result['cash']);
        $this->assertEquals('35000.000000', $result['current_assets']);
    }

    public function test_no_entries_returns_zero_balance()
    {
        $this->createAccount('1000', 'Cash', 'Asset', 'Cash');
        $this->createAccount('3000', 'Payables', 'Liability', 'Current');

        // No ledger entries
        $result = $this->service->getLiquidityRatios('2026-01-31');

        $this->assertEquals('0.000000', $result['current_assets']);
        $this->assertEquals('0.000000', $result['current_liabilities']);
        $this->assertEquals('0.000000', $result['cash']);
    }
}
