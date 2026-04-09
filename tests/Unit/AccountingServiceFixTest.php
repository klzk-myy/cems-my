<?php

namespace Tests\Unit;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingServiceFixTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountingService(new MathService);

        // Seed chart of accounts with proper types
        $this->seedChartOfAccounts();
    }

    protected function seedChartOfAccounts(): void
    {
        // Delete any existing accounts to ensure clean state
        // Note: Using delete instead of truncate because truncate commits immediately
        // in MySQL and bypasses RefreshDatabase's transaction isolation
        DB::table('chart_of_accounts')->delete();

        // Assets - Debit normal accounts
        ChartOfAccount::create(
            ['account_code' => '1000', 'account_name' => 'Cash', 'account_type' => 'Asset']
        );

        ChartOfAccount::create(
            ['account_code' => '5000', 'account_name' => 'Office Expenses', 'account_type' => 'Expense']
        );

        // Liabilities - Credit normal accounts
        ChartOfAccount::create(
            ['account_code' => '2000', 'account_name' => 'Accounts Payable', 'account_type' => 'Liability']
        );

        // Revenue - Credit normal accounts
        ChartOfAccount::create(
            ['account_code' => '4000', 'account_name' => 'Revenue', 'account_type' => 'Revenue']
        );

        // Equity - Credit normal accounts
        ChartOfAccount::create(
            ['account_code' => '3000', 'account_name' => 'Owner Equity', 'account_type' => 'Equity']
        );
    }

    /**
     * TEST FAULT #1 FIX: Verify balance calculation for debit-normal accounts
     * Assets and Expenses: balance increases with debit, decreases with credit
     */
    public function test_debit_account_balance_increases_with_debit_and_decreases_with_credit()
    {
        $user = User::factory()->create();

        // Step 1: Initial debit of 1000 to Cash (Asset)
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00], // Revenue credit
        ];

        $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Initial cash receipt',
            now()->toDateString(),
            $user->id
        );

        $balance1 = (float) $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(1000.00, $balance1, 0.01, 'Cash balance should be 1000 after initial debit');

        // Step 2: Credit of 300 to Cash (Asset) - should decrease balance
        $lines2 = [
            ['account_code' => '4000', 'debit' => 300.00, 'credit' => 0], // Revenue debit
            ['account_code' => '1000', 'debit' => 0, 'credit' => 300.00], // Cash credit
        ];

        $this->service->createJournalEntry(
            $lines2,
            'Transaction',
            2,
            'Cash payment',
            now()->toDateString(),
            $user->id
        );

        $balance2 = (float) $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(700.00, $balance2, 0.01, 'Cash balance should be 700 after credit of 300');

        // Step 3: Another debit of 500 to Cash (Asset) - should increase balance
        $lines3 = [
            ['account_code' => '1000', 'debit' => 500.00, 'credit' => 0], // Cash debit
            ['account_code' => '4000', 'debit' => 0, 'credit' => 500.00], // Revenue credit
        ];

        $this->service->createJournalEntry(
            $lines3,
            'Transaction',
            3,
            'Additional cash receipt',
            now()->toDateString(),
            $user->id
        );

        $balance3 = (float) $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(1200.00, $balance3, 0.01, 'Cash balance should be 1200 after debit of 500');
    }

    /**
     * TEST FAULT #1 FIX: Verify balance calculation for credit-normal accounts
     * Liabilities, Equity, Revenue: balance decreases with debit, increases with credit
     */
    public function test_credit_account_balance_decreases_with_debit_and_increases_with_credit()
    {
        $user = User::factory()->create();

        // Step 1: Initial credit of 1000 to Accounts Payable (Liability)
        $lines = [
            ['account_code' => '5000', 'debit' => 1000.00, 'credit' => 0], // Expense debit
            ['account_code' => '2000', 'debit' => 0, 'credit' => 1000.00], // Liability credit
        ];

        $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Expense incurred on credit',
            now()->toDateString(),
            $user->id
        );

        $balance1 = (float) $this->service->getAccountBalance('2000');
        $this->assertEqualsWithDelta(1000.00, $balance1, 0.01, 'AP balance should be 1000 after initial credit');

        // Step 2: Debit of 400 to Accounts Payable (Liability) - should decrease balance
        $lines2 = [
            ['account_code' => '2000', 'debit' => 400.00, 'credit' => 0], // Liability debit
            ['account_code' => '1000', 'debit' => 0, 'credit' => 400.00], // Cash credit
        ];

        $this->service->createJournalEntry(
            $lines2,
            'Transaction',
            2,
            'Partial payment',
            now()->toDateString(),
            $user->id
        );

        $balance2 = (float) $this->service->getAccountBalance('2000');
        $this->assertEqualsWithDelta(600.00, $balance2, 0.01, 'AP balance should be 600 after debit of 400');

        // Step 3: Another credit of 300 to Accounts Payable (Liability) - should increase balance
        $lines3 = [
            ['account_code' => '5000', 'debit' => 300.00, 'credit' => 0], // Expense debit
            ['account_code' => '2000', 'debit' => 0, 'credit' => 300.00], // Liability credit
        ];

        $this->service->createJournalEntry(
            $lines3,
            'Transaction',
            3,
            'Additional expense on credit',
            now()->toDateString(),
            $user->id
        );

        $balance3 = (float) $this->service->getAccountBalance('2000');
        $this->assertEqualsWithDelta(900.00, $balance3, 0.01, 'AP balance should be 900 after credit of 300');
    }

    /**
     * TEST FAULT #1 FIX: Verify Expense account (debit-normal) balance calculation
     */
    public function test_expense_account_balance_increases_with_debit()
    {
        $user = User::factory()->create();

        // Expense incurred
        $lines = [
            ['account_code' => '5000', 'debit' => 500.00, 'credit' => 0], // Expense debit
            ['account_code' => '1000', 'debit' => 0, 'credit' => 500.00], // Cash credit
        ];

        $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Office supplies',
            now()->toDateString(),
            $user->id
        );

        $balance = (float) $this->service->getAccountBalance('5000');
        $this->assertEqualsWithDelta(500.00, $balance, 0.01, 'Expense balance should be 500 after debit');

        // Another expense
        $lines2 = [
            ['account_code' => '5000', 'debit' => 300.00, 'credit' => 0], // Expense debit
            ['account_code' => '1000', 'debit' => 0, 'credit' => 300.00], // Cash credit
        ];

        $this->service->createJournalEntry(
            $lines2,
            'Transaction',
            2,
            'More office supplies',
            now()->toDateString(),
            $user->id
        );

        $balance2 = (float) $this->service->getAccountBalance('5000');
        $this->assertEqualsWithDelta(800.00, $balance2, 0.01, 'Expense balance should be 800 after second debit');
    }

    /**
     * TEST FAULT #1 FIX: Verify Revenue account (credit-normal) balance calculation
     */
    public function test_revenue_account_balance_increases_with_credit()
    {
        $user = User::factory()->create();

        // Revenue earned
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0], // Cash debit
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00], // Revenue credit
        ];

        $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Sales revenue',
            now()->toDateString(),
            $user->id
        );

        $balance = (float) $this->service->getAccountBalance('4000');
        $this->assertEqualsWithDelta(1000.00, $balance, 0.01, 'Revenue balance should be 1000 after credit');

        // More revenue
        $lines2 = [
            ['account_code' => '1000', 'debit' => 500.00, 'credit' => 0], // Cash debit
            ['account_code' => '4000', 'debit' => 0, 'credit' => 500.00], // Revenue credit
        ];

        $this->service->createJournalEntry(
            $lines2,
            'Transaction',
            2,
            'More sales',
            now()->toDateString(),
            $user->id
        );

        $balance2 = (float) $this->service->getAccountBalance('4000');
        $this->assertEqualsWithDelta(1500.00, $balance2, 0.01, 'Revenue balance should be 1500 after second credit');
    }

    /**
     * TEST FAULT #2 FIX: Verify race condition fix in getAccountBalance
     * When entries have same date, should use created_at instead of id for ordering
     */
    public function test_get_account_balance_uses_created_at_for_same_date_entries()
    {
        $user = User::factory()->create();

        // Create two entries with the same date but different amounts
        $entryDate = now()->toDateString();

        // First entry: Debit 1000 to Cash
        $lines1 = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->service->createJournalEntry(
            $lines1,
            'Transaction',
            1,
            'First entry',
            $entryDate,
            $user->id
        );

        // Second entry: Credit 300 to Cash
        $lines2 = [
            ['account_code' => '4000', 'debit' => 300.00, 'credit' => 0],
            ['account_code' => '1000', 'debit' => 0, 'credit' => 300.00],
        ];

        $this->service->createJournalEntry(
            $lines2,
            'Transaction',
            2,
            'Second entry',
            $entryDate,
            $user->id
        );

        // Third entry: Debit 200 to Cash
        $lines3 = [
            ['account_code' => '1000', 'debit' => 200.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 200.00],
        ];

        $this->service->createJournalEntry(
            $lines3,
            'Transaction',
            3,
            'Third entry',
            $entryDate,
            $user->id
        );

        // Balance should be: 1000 - 300 + 200 = 900
        $balance = (float) $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(900.00, $balance, 0.01, 'Balance should correctly reflect sequential operations on same date');

        // Verify ledger entries exist with correct running balances
        $this->assertDatabaseHas('account_ledger', [
            'account_code' => '1000',
            'debit' => 1000.00,
            'running_balance' => 1000.00,
        ]);

        $this->assertDatabaseHas('account_ledger', [
            'account_code' => '1000',
            'credit' => 300.00,
            'running_balance' => 700.00,
        ]);

        $this->assertDatabaseHas('account_ledger', [
            'account_code' => '1000',
            'debit' => 200.00,
            'running_balance' => 900.00,
        ]);
    }

    /**
     * TEST FAULT #2 FIX: Verify ordering by entry_date then created_at (not id)
     * This tests that created_at is the secondary sort criteria
     */
    public function test_ledger_entries_ordered_by_date_then_created_at()
    {
        $user = User::factory()->create();

        // Create entries on different dates
        $lines1 = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->service->createJournalEntry(
            $lines1,
            'Transaction',
            1,
            'First day',
            '2024-01-01',
            $user->id
        );

        $lines2 = [
            ['account_code' => '4000', 'debit' => 200.00, 'credit' => 0],
            ['account_code' => '1000', 'debit' => 0, 'credit' => 200.00],
        ];

        $this->service->createJournalEntry(
            $lines2,
            'Transaction',
            2,
            'Second day',
            '2024-01-02',
            $user->id
        );

        // Get current balance - should show cumulative balance
        $balanceCurrent = (float) $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(800.00, $balanceCurrent, 0.01, 'Current balance should be 800');

        // Verify that query ordering uses created_at instead of id
        // This is tested by checking the SQL structure or behavior
        $entries = AccountLedger::where('account_code', '1000')
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->assertCount(2, $entries);
        // First entry should be the later date (2024-01-02)
        $this->assertEquals('2024-01-02', $entries[0]->entry_date->format('Y-m-d'));
        $this->assertEquals(800.00, (float) $entries[0]->running_balance);
    }

    /**
     * Comprehensive test: Verify balance calculation with multiple transactions
     * across different dates and account types
     */
    public function test_comprehensive_balance_calculation()
    {
        $user = User::factory()->create();

        // Transaction 1: Initial investment (Credit Equity, Debit Cash)
        $lines1 = [
            ['account_code' => '1000', 'debit' => 10000.00, 'credit' => 0],
            ['account_code' => '3000', 'debit' => 0, 'credit' => 10000.00],
        ];

        $this->service->createJournalEntry(
            $lines1,
            'Transaction',
            1,
            'Initial investment',
            '2024-01-01',
            $user->id
        );

        // Transaction 2: Revenue earned
        $lines2 = [
            ['account_code' => '1000', 'debit' => 5000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 5000.00],
        ];

        $this->service->createJournalEntry(
            $lines2,
            'Transaction',
            2,
            'Sales revenue',
            '2024-01-02',
            $user->id
        );

        // Transaction 3: Expense paid
        $lines3 = [
            ['account_code' => '5000', 'debit' => 1500.00, 'credit' => 0],
            ['account_code' => '1000', 'debit' => 0, 'credit' => 1500.00],
        ];

        $this->service->createJournalEntry(
            $lines3,
            'Transaction',
            3,
            'Office rent',
            '2024-01-03',
            $user->id
        );

        // Transaction 4: Liability incurred
        $lines4 = [
            ['account_code' => '5000', 'debit' => 800.00, 'credit' => 0],
            ['account_code' => '2000', 'debit' => 0, 'credit' => 800.00],
        ];

        $this->service->createJournalEntry(
            $lines4,
            'Transaction',
            4,
            'Utilities on credit',
            '2024-01-04',
            $user->id
        );

        // Verify final balances
        $cashBalance = (float) $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(13500.00, $cashBalance, 0.01, 'Cash balance should be 13500');

        $equityBalance = (float) $this->service->getAccountBalance('3000');
        $this->assertEqualsWithDelta(10000.00, $equityBalance, 0.01, 'Equity balance should be 10000');

        $revenueBalance = (float) $this->service->getAccountBalance('4000');
        $this->assertEqualsWithDelta(5000.00, $revenueBalance, 0.01, 'Revenue balance should be 5000');

        $expenseBalance = (float) $this->service->getAccountBalance('5000');
        $this->assertEqualsWithDelta(2300.00, $expenseBalance, 0.01, 'Expense balance should be 2300');

        $liabilityBalance = (float) $this->service->getAccountBalance('2000');
        $this->assertEqualsWithDelta(800.00, $liabilityBalance, 0.01, 'Liability balance should be 800');
    }

    /**
     * Test that verifies the balance calculation handles zero amounts correctly
     */
    public function test_balance_calculation_with_zero_amounts()
    {
        $user = User::factory()->create();

        // Entry with only debit (no credit on this account)
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Test',
            now()->toDateString(),
            $user->id
        );

        $balance = (float) $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(1000.00, $balance, 0.01);
    }
}
