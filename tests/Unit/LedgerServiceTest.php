<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\LedgerService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $ledgerService;

    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();
        $mathService = new MathService;
        $this->accountingService = new AccountingService($mathService);
        $this->ledgerService = new LedgerService($mathService, $this->accountingService);

        $this->seedChartOfAccounts();
    }

    protected function seedChartOfAccounts(): void
    {
        // Assets
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash', 'account_type' => 'Asset']
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1100'],
            ['account_name' => 'Inventory', 'account_type' => 'Asset']
        );
        // Liabilities
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Payables', 'account_type' => 'Liability']
        );
        // Equity
        ChartOfAccount::firstOrCreate(
            ['account_code' => '3000'],
            ['account_name' => 'Equity', 'account_type' => 'Equity']
        );
        // Revenue
        ChartOfAccount::firstOrCreate(
            ['account_code' => '4000'],
            ['account_name' => 'Revenue', 'account_type' => 'Revenue']
        );
        // Expense
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Expenses', 'account_type' => 'Expense']
        );
    }

    public function test_get_trial_balance_returns_all_accounts()
    {
        $result = $this->ledgerService->getTrialBalance();

        // At least the 6 accounts we seeded should be present (may be more from other seeders)
        $this->assertGreaterThanOrEqual(6, count($result['accounts']));
        $this->assertArrayHasKey('total_debits', $result);
        $this->assertArrayHasKey('total_credits', $result);
        $this->assertTrue($result['is_balanced']);
    }

    public function test_get_trial_balance_is_balanced_after_entries()
    {
        $user = User::factory()->create();

        // Create balanced entry
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->accountingService->createJournalEntry(
            $lines,
            'Test',
            1,
            'Test entry',
            now()->toDateString(),
            $user->id
        );

        $result = $this->ledgerService->getTrialBalance();
        $this->assertTrue($result['is_balanced']);
    }

    public function test_get_account_ledger_returns_entries()
    {
        $user = User::factory()->create();

        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->accountingService->createJournalEntry(
            $lines,
            'Test',
            1,
            'Test entry',
            now()->toDateString(),
            $user->id
        );

        $fromDate = now()->subDay()->toDateString();
        $toDate = now()->addDay()->toDateString();

        $result = $this->ledgerService->getAccountLedger('1000', $fromDate, $toDate);

        $this->assertArrayHasKey('account', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('opening_balance', $result);
        $this->assertArrayHasKey('closing_balance', $result);
        $this->assertEquals('1000', $result['account']->account_code);
    }

    public function test_get_profit_and_loss_returns_revenue_and_expenses()
    {
        $user = User::factory()->create();

        // Create revenue entry
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->accountingService->createJournalEntry(
            $lines,
            'Test',
            1,
            'Revenue entry',
            now()->toDateString(),
            $user->id
        );

        $fromDate = now()->subDay()->toDateString();
        $toDate = now()->addDay()->toDateString();

        $result = $this->ledgerService->getProfitAndLoss($fromDate, $toDate);

        $this->assertArrayHasKey('revenues', $result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('total_expenses', $result);
    }

    public function test_get_balance_sheet_returns_assets_liabilities_equity()
    {
        $user = User::factory()->create();

        // Create entry affecting balance sheet
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->accountingService->createJournalEntry(
            $lines,
            'Test',
            1,
            'Test entry',
            now()->toDateString(),
            $user->id
        );

        $asOfDate = now()->toDateString();
        $result = $this->ledgerService->getBalanceSheet($asOfDate);

        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('liabilities', $result);
        $this->assertArrayHasKey('equity', $result);
        $this->assertArrayHasKey('total_assets', $result);
        $this->assertArrayHasKey('total_liabilities', $result);
        $this->assertArrayHasKey('total_equity', $result);
        $this->assertArrayHasKey('liabilities_plus_equity', $result);
        $this->assertArrayHasKey('is_balanced', $result);
    }
}
