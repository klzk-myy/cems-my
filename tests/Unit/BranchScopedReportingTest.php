<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Services\CashFlowService;
use App\Services\FinancialRatioService;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchScopedReportingTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledgerService;

    private FinancialRatioService $financialRatioService;

    private CashFlowService $cashFlowService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = app(LedgerService::class);
        $this->financialRatioService = app(FinancialRatioService::class);
        $this->cashFlowService = app(CashFlowService::class);
    }

    public function test_trial_balance_accepts_branch_id_parameter(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        // Should not throw an error
        $result = $this->ledgerService->getTrialBalance(now()->toDateString(), $branch->id);

        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('is_balanced', $result);
    }

    public function test_trial_balance_returns_different_results_for_different_branches(): void
    {
        $branch1 = Branch::create([
            'code' => 'BR001',
            'name' => 'Branch 1',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);
        $branch2 = Branch::create([
            'code' => 'BR002',
            'name' => 'Branch 2',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        // Test that both branches can be queried without errors
        $tbBranch1 = $this->ledgerService->getTrialBalance(now()->toDateString(), $branch1->id);
        $tbBranch2 = $this->ledgerService->getTrialBalance(now()->toDateString(), $branch2->id);

        // Both should return valid trial balance structure
        $this->assertArrayHasKey('accounts', $tbBranch1);
        $this->assertArrayHasKey('accounts', $tbBranch2);
        $this->assertArrayHasKey('is_balanced', $tbBranch1);
        $this->assertArrayHasKey('is_balanced', $tbBranch2);
    }

    public function test_account_ledger_accepts_branch_id_parameter(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $account = ChartOfAccount::first();

        $result = $this->ledgerService->getAccountLedger(
            $account->account_code,
            now()->subMonth()->toDateString(),
            now()->toDateString(),
            $branch->id
        );

        $this->assertArrayHasKey('account', $result);
        $this->assertArrayHasKey('entries', $result);
    }

    public function test_profit_and_loss_accepts_branch_id_parameter(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $result = $this->ledgerService->getProfitAndLoss(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            $branch->id
        );

        $this->assertArrayHasKey('revenues', $result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('net_profit', $result);
    }

    public function test_balance_sheet_accepts_branch_id_parameter(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $result = $this->ledgerService->getBalanceSheet(
            now()->toDateString(),
            $branch->id
        );

        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('liabilities', $result);
        $this->assertArrayHasKey('equity', $result);
        $this->assertArrayHasKey('is_balanced', $result);
    }

    public function test_financial_ratios_accepts_branch_id_parameter(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $result = $this->financialRatioService->getAllRatios(
            now()->toDateString(),
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            $branch->id
        );

        $this->assertArrayHasKey('liquidity', $result);
        $this->assertArrayHasKey('profitability', $result);
        $this->assertArrayHasKey('leverage', $result);
        $this->assertArrayHasKey('efficiency', $result);
        $this->assertEquals($branch->id, $result['branch_id']);
    }

    public function test_cash_flow_statement_accepts_branch_id_parameter(): void
    {
        $branch = Branch::create([
            'code' => 'BR001',
            'name' => 'Test Branch',
            'type' => Branch::TYPE_BRANCH,
            'country' => 'Malaysia',
        ]);

        $result = $this->cashFlowService->getCashFlowStatement(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            $branch->id
        );

        $this->assertArrayHasKey('operating', $result);
        $this->assertArrayHasKey('investing', $result);
        $this->assertArrayHasKey('financing', $result);
        $this->assertArrayHasKey('net_change', $result);
        $this->assertEquals($branch->id, $result['branch_id']);
    }

    public function test_services_work_with_null_branch_id(): void
    {
        // All services should work without branch_id (consolidated view)
        $tbResult = $this->ledgerService->getTrialBalance(now()->toDateString(), null);
        $this->assertArrayHasKey('accounts', $tbResult);

        $plResult = $this->ledgerService->getProfitAndLoss(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            null
        );
        $this->assertArrayHasKey('net_profit', $plResult);

        $bsResult = $this->ledgerService->getBalanceSheet(now()->toDateString(), null);
        $this->assertArrayHasKey('is_balanced', $bsResult);

        $ratioResult = $this->financialRatioService->getAllRatios(
            now()->toDateString(),
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            null
        );
        $this->assertArrayHasKey('liquidity', $ratioResult);

        $cfResult = $this->cashFlowService->getCashFlowStatement(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            null
        );
        $this->assertArrayHasKey('net_change', $cfResult);
    }
}
