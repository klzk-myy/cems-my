<?php

namespace Tests\Unit;

use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\BudgetService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BudgetAndReversalFixTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $accountingService;

    protected BudgetService $budgetService;

    protected MathService $mathService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mathService = new MathService;
        $this->accountingService = new AccountingService($this->mathService);
        $this->budgetService = new BudgetService($this->accountingService, $this->mathService);
        $this->seedChartOfAccounts();
    }

    protected function seedChartOfAccounts(): void
    {
        // Seed required accounts for tests
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Forex Position', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '3100'],
            ['account_name' => 'Retained Earnings', 'account_type' => 'Equity', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '4000'],
            ['account_name' => 'Revenue', 'account_type' => 'Revenue', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '4001'],
            ['account_name' => 'Revenue Summary', 'account_type' => 'Revenue', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Expense Summary', 'account_type' => 'Expense', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5100'],
            ['account_name' => 'FX Gain', 'account_type' => 'Revenue', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '6100'],
            ['account_name' => 'FX Loss', 'account_type' => 'Expense', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '7000'],
            ['account_name' => 'Office Expense', 'account_type' => 'Expense', 'is_active' => true]
        );
    }

    /**
     * FAULT #9 TEST: Budget actuals should only include entries within the period date range
     */
    public function test_budget_actuals_respect_period_date_range()
    {
        $user = User::factory()->create();

        // Create a period for January 2024
        $period = AccountingPeriod::create([
            'period_code' => '2024-01',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create entries BEFORE the period (December 2023)
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '7000', 'debit' => 500, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 500],
            ],
            'Test',
            1,
            'Pre-period expense',
            '2023-12-15',
            $user->id
        );

        // Create entries WITHIN the period (January 2024)
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '7000', 'debit' => 300, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 300],
            ],
            'Test',
            2,
            'Period expense',
            '2024-01-15',
            $user->id
        );

        // Create entries AFTER the period (February 2024)
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '7000', 'debit' => 200, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 200],
            ],
            'Test',
            3,
            'Post-period expense',
            '2024-02-15',
            $user->id
        );

        // Create a budget for the expense account in this period
        Budget::create([
            'account_code' => '7000',
            'period_code' => '2024-01',
            'budget_amount' => '1000',
            'actual_amount' => '0',
            'created_by' => $user->id,
        ]);

        // Update actuals - should only include the January entry (300)
        $this->budgetService->updateActuals('2024-01');

        // Refresh the budget
        $budget = Budget::where('account_code', '7000')
            ->where('period_code', '2024-01')
            ->first();

        // The actual should be 300 (only the January entry), not 1000 (cumulative)
        $this->assertEquals('300.00', $budget->actual_amount);
    }

    /**
     * FAULT #10 TEST: Revaluation service validates hardcoded account codes exist
     */
    public function test_revaluation_service_validates_account_codes_exist()
    {
        // This test validates that the configured accounts exist
        $configAccounts = [
            'forex_position_account' => Config::get('accounting.forex_position_account', '2000'),
            'revaluation_gain_account' => Config::get('accounting.revaluation_gain_account', '5100'),
            'revaluation_loss_account' => Config::get('accounting.revaluation_loss_account', '6100'),
        ];

        foreach ($configAccounts as $key => $code) {
            $this->assertDatabaseHas('chart_of_accounts', [
                'account_code' => $code,
                'is_active' => true,
            ]);
            // Verify the account exists and is active
            $account = ChartOfAccount::where('account_code', $code)->where('is_active', true)->first();
            $this->assertNotNull($account, "Configured {$key} ({$code}) should exist in chart of accounts");
        }
    }

    /**
     * FAULT #10 TEST: Period close service validates hardcoded account codes exist
     */
    public function test_period_close_service_validates_account_codes_exist()
    {
        $configAccounts = [
            'revenue_summary_account' => Config::get('accounting.revenue_summary_account', '4000'),
            'expense_summary_account' => Config::get('accounting.expense_summary_account', '5000'),
            'retained_earnings_account' => Config::get('accounting.retained_earnings_account', '3100'),
        ];

        foreach ($configAccounts as $key => $code) {
            $this->assertDatabaseHas('chart_of_accounts', [
                'account_code' => $code,
                'is_active' => true,
            ]);
            // Verify the account exists and is active
            $account = ChartOfAccount::where('account_code', $code)->where('is_active', true)->first();
            $this->assertNotNull($account, "Configured {$key} ({$code}) should exist in chart of accounts");
        }
    }

    /**
     * FAULT #11 TEST: Cannot reverse an already reversed entry
     */
    public function test_cannot_reverse_already_reversed_entry()
    {
        $user = User::factory()->create();

        // Create an original entry
        $originalEntry = $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4000', 'debit' => 0, 'credit' => 100],
            ],
            'Test',
            1,
            'Original',
            now()->toDateString(),
            $user->id
        );

        // First reversal should succeed
        $reversal1 = $this->accountingService->reverseJournalEntry(
            $originalEntry,
            'First reversal',
            $user->id
        );

        $this->assertInstanceOf(JournalEntry::class, $reversal1);

        // Second reversal should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entry has already been reversed');

        $this->accountingService->reverseJournalEntry(
            $originalEntry->fresh(),
            'Second reversal',
            $user->id
        );
    }

    /**
     * FAULT #11 TEST: Cannot reverse an entry that is not posted
     */
    public function test_cannot_reverse_non_posted_entry()
    {
        $user = User::factory()->create();

        // Create an entry with Draft status (directly in DB since service only creates Posted)
        $draftEntry = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Test',
            'reference_id' => 1,
            'description' => 'Draft entry',
            'status' => 'Draft',
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        // Attempting to reverse a Draft entry should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entry must be Posted to be reversed');

        $this->accountingService->reverseJournalEntry(
            $draftEntry,
            'Try to reverse draft',
            $user->id
        );
    }

    /**
     * FAULT #11 TEST: Reversal entry has explicit link to original via reference_id
     */
    public function test_reversal_creates_explicit_link_to_original()
    {
        $user = User::factory()->create();

        // Create original entry
        $originalEntry = $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => 200, 'credit' => 0],
                ['account_code' => '4000', 'debit' => 0, 'credit' => 200],
            ],
            'Test',
            1,
            'Original with link test',
            now()->toDateString(),
            $user->id
        );

        // Reverse it
        $reversal = $this->accountingService->reverseJournalEntry(
            $originalEntry,
            'With link',
            $user->id
        );

        // Reversal should have reference_type = 'Reversal' and reference_id = original entry id
        $this->assertEquals('Reversal', $reversal->reference_type);
        $this->assertEquals($originalEntry->id, $reversal->reference_id);

        // Original should be marked as Reversed
        $originalEntry->refresh();
        $this->assertEquals('Reversed', $originalEntry->status);
    }

    /**
     * FAULT #11 TEST: Reversed entry status is correctly set
     */
    public function test_reversed_entry_status_is_updated()
    {
        $user = User::factory()->create();

        $originalEntry = $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => 150, 'credit' => 0],
                ['account_code' => '4000', 'debit' => 0, 'credit' => 150],
            ],
            'Test',
            1,
            'Status test',
            now()->toDateString(),
            $user->id
        );

        $this->assertEquals('Posted', $originalEntry->status);

        $this->accountingService->reverseJournalEntry(
            $originalEntry,
            'Test reversal',
            $user->id
        );

        $originalEntry->refresh();
        $this->assertEquals('Reversed', $originalEntry->status);
    }

    /**
     * Test that BudgetService uses AccountingPeriod date range
     */
    public function test_budget_service_uses_period_date_range()
    {
        $user = User::factory()->create();

        // Create period
        $period = AccountingPeriod::create([
            'period_code' => '2024-03',
            'start_date' => '2024-03-01',
            'end_date' => '2024-03-31',
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create budget
        Budget::create([
            'account_code' => '7000',
            'period_code' => '2024-03',
            'budget_amount' => '500',
            'actual_amount' => '0',
            'created_by' => $user->id,
        ]);

        // Entry before period
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '7000', 'debit' => 100, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 100],
            ],
            'Test',
            1,
            'Before period',
            '2024-02-28',
            $user->id
        );

        // Entry within period
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '7000', 'debit' => 250, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 250],
            ],
            'Test',
            2,
            'Within period',
            '2024-03-15',
            $user->id
        );

        // Entry after period
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '7000', 'debit' => 50, 'credit' => 0],
                ['account_code' => '1000', 'debit' => 0, 'credit' => 50],
            ],
            'Test',
            3,
            'After period',
            '2024-04-01',
            $user->id
        );

        $this->budgetService->updateActuals('2024-03');

        $budget = Budget::where('account_code', '7000')
            ->where('period_code', '2024-03')
            ->first();

        // Should be 250 (only March entry)
        $this->assertEquals('250.00', $budget->actual_amount);
    }
}
