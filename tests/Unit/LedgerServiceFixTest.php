<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\LedgerService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Fault #3 and #4 fixes
 * Fault #3: Incorrect Trial Balance Debit/Credit Logic for credit-normal accounts
 * Fault #4: P&L Activity Calculation Error for Expense accounts
 */
class LedgerServiceFixTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $ledgerService;

    protected AccountingService $accountingService;

    protected MathService $mathService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mathService = new MathService;
        $this->accountingService = new AccountingService($this->mathService);
        $this->ledgerService = new LedgerService($this->mathService, $this->accountingService);

        $this->seedChartOfAccounts();
    }

    protected function seedChartOfAccounts(): void
    {
        // Asset (debit-normal): positive = debit, negative = credit
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash', 'account_type' => 'Asset']
        );

        // Liability (credit-normal): positive = credit, negative = debit
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Accounts Payable', 'account_type' => 'Liability']
        );

        // Equity (credit-normal): positive = credit, negative = debit
        ChartOfAccount::firstOrCreate(
            ['account_code' => '3000'],
            ['account_name' => 'Retained Earnings', 'account_type' => 'Equity']
        );

        // Revenue (credit-normal): positive = credit, negative = debit
        ChartOfAccount::firstOrCreate(
            ['account_code' => '4000'],
            ['account_name' => 'Sales Revenue', 'account_type' => 'Revenue']
        );

        // Expense (debit-normal): positive = debit, negative = credit
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Office Expense', 'account_type' => 'Expense']
        );
    }

    /**
     * FAULT #3: Test trial balance correctly handles credit-normal accounts (Liability, Equity, Revenue)
     *
     * For credit-normal accounts:
     * - Positive balance should appear in CREDIT column
     * - Negative balance (debit balance/overdrawn) should appear in DEBIT column
     */
    public function test_trial_balance_credit_normal_accounts_with_positive_balance_go_to_credit_column()
    {
        $user = User::factory()->create();

        // Create journal entry that results in positive credit balance for Liability
        // Dr: Asset (Cash) $500
        // Cr: Liability (Payable) $500
        $lines = [
            ['account_code' => '1000', 'debit' => 500.00, 'credit' => 0], // Asset
            ['account_code' => '2000', 'debit' => 0, 'credit' => 500.00], // Liability
        ];

        try {
            $entry = $this->accountingService->createJournalEntry(
                $lines,
                'TEST',
                1,
                'Test entry for liability',
                now()->toDateString(),
                $user->id
            );
            echo PHP_EOL.'Journal entry created: '.$entry->id.PHP_EOL;
        } catch (\Exception $e) {
            echo PHP_EOL.'Error creating journal entry: '.$e->getMessage().PHP_EOL;
            throw $e;
        }

        // Debug: Check ledger entries
        echo PHP_EOL.'Ledger entries for account 2000:'.PHP_EOL;
        $ledgers = \App\Models\AccountLedger::where('account_code', '2000')->get();
        foreach ($ledgers as $l) {
            echo sprintf('  Entry Date: %s, Debit: %s, Credit: %s, Balance: %s'.PHP_EOL,
                $l->entry_date, $l->debit, $l->credit, $l->running_balance);
        }

        // Debug: Check balance retrieval
        echo PHP_EOL.'Balance via accounting service: '.
            $this->accountingService->getAccountBalance('2000').PHP_EOL;
        echo 'Balance as of today: '.
            $this->accountingService->getAccountBalance('2000', now()->toDateString()).PHP_EOL;
        echo 'Today date string: '.now()->toDateString().PHP_EOL;

        $result = $this->ledgerService->getTrialBalance();

        // Debug: Print trial balance result
        echo PHP_EOL.'Trial Balance Accounts:'.PHP_EOL;
        foreach ($result['accounts'] as $acc) {
            echo sprintf('Account %s (%s): debit=%s, credit=%s, balance=%s'.PHP_EOL,
                $acc['account_code'], $acc['account_type'], $acc['debit'], $acc['credit'], $acc['balance']);
        }

        // Find the liability account in trial balance
        $liabilityAccount = collect($result['accounts'])->first(fn ($a) => $a['account_code'] === '2000');

        // Liability should have positive credit balance of 500
        // This should appear in the CREDIT column, not debit
        $this->assertNotNull($liabilityAccount, 'Liability account should be in trial balance');

        // Check that liability has positive credit balance
        // The credit column should have the positive balance value
        $this->assertTrue(
            $this->mathService->compare($liabilityAccount['credit'], '0') > 0,
            'Positive credit-normal balance should be in credit column'
        );
        $this->assertEquals('0', $liabilityAccount['debit'], 'Credit-normal balance should not be in debit column');
        // The actual balance value should be 500 (may have different precision)
        $this->assertEquals(500.00, (float) $liabilityAccount['credit'], '', 0.01);
    }

    /**
     * FAULT #3: Test trial balance correctly handles negative balance for credit-normal accounts
     *
     * A negative balance in a credit-normal account is unusual (overdrawn liability)
     * and should appear in the DEBIT column.
     */
    public function test_trial_balance_credit_normal_accounts_with_negative_balance_go_to_debit_column()
    {
        $user = User::factory()->create();

        // Create scenario where liability has negative balance (overdrawn/unusual)
        // First create a positive liability
        $lines1 = [
            ['account_code' => '1000', 'debit' => 500.00, 'credit' => 0],
            ['account_code' => '2000', 'debit' => 0, 'credit' => 500.00],
        ];
        $this->accountingService->createJournalEntry($lines1, 'TEST', 1, 'Entry 1', now()->toDateString(), $user->id);

        // Then reverse more than the original (creating negative liability balance)
        $lines2 = [
            ['account_code' => '2000', 'debit' => 800.00, 'credit' => 0],   // Pay liability
            ['account_code' => '1000', 'debit' => 0, 'credit' => 800.00],   // Cash out
        ];
        $this->accountingService->createJournalEntry($lines2, 'TEST', 1, 'Entry 2', now()->toDateString(), $user->id);

        $result = $this->ledgerService->getTrialBalance();
        $liabilityAccount = collect($result['accounts'])->first(fn ($a) => $a['account_code'] === '2000');

        // Liability now has negative balance (-300), which should appear in DEBIT column
        $this->assertNotNull($liabilityAccount);
        $this->assertTrue(
            $this->mathService->compare($liabilityAccount['debit'], '0') > 0,
            'Negative credit-normal balance should be in debit column'
        );
        $this->assertEquals('0', $liabilityAccount['credit'], 'Negative credit-normal balance should not be in credit column');
        // The debit amount should be approximately 300
        $this->assertEqualsWithDelta(300.00, (float) $liabilityAccount['debit'], 0.01, 'Debit amount should be 300');
    }

    /**
     * FAULT #3: Test trial balance correctly handles debit-normal accounts (Asset, Expense)
     *
     * For debit-normal accounts:
     * - Positive balance should appear in DEBIT column
     * - Negative balance should appear in CREDIT column
     */
    public function test_trial_balance_debit_normal_accounts_with_positive_balance_go_to_debit_column()
    {
        $user = User::factory()->create();

        // Create journal entry that results in positive debit balance for Asset
        // Dr: Asset (Cash) $1000
        // Cr: Revenue $1000
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->accountingService->createJournalEntry($lines, 'TEST', 1, 'Test entry', now()->toDateString(), $user->id);

        $result = $this->ledgerService->getTrialBalance();
        $assetAccount = collect($result['accounts'])->first(fn ($a) => $a['account_code'] === '1000');

        // Asset should have positive debit balance of 1000
        // This should appear in the DEBIT column
        $this->assertNotNull($assetAccount);
        $this->assertTrue(
            $this->mathService->compare($assetAccount['debit'], '0') > 0,
            'Positive debit-normal balance should be in debit column'
        );
        $this->assertEquals('0', $assetAccount['credit'], 'Debit-normal balance should not be in credit column');
        $this->assertEqualsWithDelta(1000.00, (float) $assetAccount['debit'], 0.01, 'Debit amount should be 1000');
    }

    /**
     * FAULT #4: Test P&L activity calculation for Revenue accounts
     *
     * For Revenue: Activity should be calculated as credits - debits
     * A credit to revenue increases revenue (positive activity)
     * A debit to revenue decreases revenue (negative activity, like a refund)
     */
    public function test_profit_and_loss_revenue_activity_calculated_as_credits_minus_debits()
    {
        $user = User::factory()->create();

        // Entry 1: Revenue of $1000
        // Dr: Cash $1000, Cr: Revenue $1000
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
                ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
            ],
            'REV',
            1,
            'Sales',
            now()->toDateString(),
            $user->id
        );

        // Entry 2: Revenue refund of $200 (debit to revenue)
        // Dr: Revenue $200, Cr: Cash $200
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '4000', 'debit' => 200.00, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 200.00],
            ],
            'REFUND',
            1,
            'Refund',
            now()->toDateString(),
            $user->id
        );

        $fromDate = now()->subDay()->toDateString();
        $toDate = now()->addDay()->toDateString();
        $result = $this->ledgerService->getProfitAndLoss($fromDate, $toDate);

        // Net revenue = credits ($1000) - debits ($200) = $800
        // Use delta comparison for floating point precision
        $this->assertEqualsWithDelta(800.00, (float) $result['total_revenue'], 0.01, 'Revenue activity should be credits minus debits');
    }

    /**
     * FAULT #4: Test P&L activity calculation for Expense accounts
     *
     * For Expense: Activity should be calculated as debits - credits
     * A debit to expense increases expense (positive activity)
     * A credit to expense decreases expense (negative activity, like a refund/rebate)
     */
    public function test_profit_and_loss_expense_activity_calculated_as_debits_minus_credits()
    {
        $user = User::factory()->create();

        // Entry 1: Expense of $500
        // Dr: Expense $500, Cr: Cash $500
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '5000', 'debit' => 500.00, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 500.00],
            ],
            'EXP',
            1,
            'Office supplies',
            now()->toDateString(),
            $user->id
        );

        // Entry 2: Expense refund of $100 (credit to expense)
        // Dr: Cash $100, Cr: Expense $100
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => 100.00, 'credit' => 0],
                ['account_code' => '5000', 'debit' => 0, 'credit' => 100.00],
            ],
            'REBATE',
            1,
            'Expense rebate',
            now()->toDateString(),
            $user->id
        );

        $fromDate = now()->subDay()->toDateString();
        $toDate = now()->addDay()->toDateString();
        $result = $this->ledgerService->getProfitAndLoss($fromDate, $toDate);

        // Net expense = debits ($500) - credits ($100) = $400
        $this->assertEqualsWithDelta(400.00, (float) $result['total_expenses'], 0.01, 'Expense activity should be debits minus credits');
    }

    /**
     * Comprehensive test: Verify net profit calculation is correct
     * Revenue $1000, Expense $400 => Net Profit $600
     */
    public function test_profit_and_loss_calculates_net_profit_correctly()
    {
        $user = User::factory()->create();

        // Revenue entry: $1000
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
                ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
            ],
            'REV',
            1,
            'Sales',
            now()->toDateString(),
            $user->id
        );

        // Expense entry: $400
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '5000', 'debit' => 400.00, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 400.00],
            ],
            'EXP',
            1,
            'Office supplies',
            now()->toDateString(),
            $user->id
        );

        $fromDate = now()->subDay()->toDateString();
        $toDate = now()->addDay()->toDateString();
        $result = $this->ledgerService->getProfitAndLoss($fromDate, $toDate);

        $this->assertEqualsWithDelta(1000.00, (float) $result['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(400.00, (float) $result['total_expenses'], 0.01);
        $this->assertEqualsWithDelta(600.00, (float) $result['net_profit'], 0.01, 'Net profit should be Revenue - Expenses');
    }

    /**
     * Test that Equity accounts (credit-normal) are handled correctly in trial balance
     */
    public function test_trial_balance_equity_accounts_handled_as_credit_normal()
    {
        $user = User::factory()->create();

        // Initial equity investment
        // Dr: Cash $5000, Cr: Equity $5000
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => 5000.00, 'credit' => 0],
                ['account_code' => '3000', 'debit' => 0, 'credit' => 5000.00],
            ],
            'INVEST',
            1,
            'Owner investment',
            now()->toDateString(),
            $user->id
        );

        $result = $this->ledgerService->getTrialBalance();
        $equityAccount = collect($result['accounts'])->first(fn ($a) => $a['account_code'] === '3000');

        // Equity has positive credit balance of 5000
        // Should appear in CREDIT column
        $this->assertNotNull($equityAccount);
        $this->assertTrue(
            $this->mathService->compare($equityAccount['credit'], '0') > 0,
            'Equity positive balance should be in credit column'
        );
        $this->assertEquals('0', $equityAccount['debit'], 'Equity positive balance should not be in debit column');
        $this->assertEqualsWithDelta(5000.00, (float) $equityAccount['credit'], 0.01, 'Credit amount should be 5000');
    }
}
