<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\CurrencyPosition;

class LedgerService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function getTrialBalance(?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('account_code')->get();
        
        $trialBalance = [];
        $totalDebits = '0';
        $totalCredits = '0';

        foreach ($accounts as $account) {
            $balance = app(AccountingService::class)->getAccountBalance($account->account_code, $asOfDate);
            
            $debit = $this->mathService->compare($balance, '0') >= 0 ? $balance : '0';
            $credit = $this->mathService->compare($balance, '0') < 0 ? $this->mathService->multiply($balance, '-1') : '0';

            if (in_array($account->account_type, ['Liability', 'Equity', 'Revenue'])) {
                $debit = $this->mathService->compare($balance, '0') < 0 ? $this->mathService->multiply($balance, '-1') : '0';
                $credit = $this->mathService->compare($balance, '0') >= 0 ? $balance : '0';
            }

            $trialBalance[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];

            $totalDebits = $this->mathService->add($totalDebits, $debit);
            $totalCredits = $this->mathService->add($totalCredits, $credit);
        }

        return [
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => $this->mathService->compare($totalDebits, $totalCredits) === 0,
            'as_of_date' => $asOfDate,
        ];
    }

    public function getAccountLedger(string $accountCode, string $fromDate, string $toDate): array
    {
        $account = ChartOfAccount::findOrFail($accountCode);
        
        $entries = AccountLedger::with('journalEntry')
            ->where('account_code', $accountCode)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        return [
            'account' => $account,
            'entries' => $entries,
            'opening_balance' => $this->getOpeningBalance($accountCode, $fromDate),
            'closing_balance' => $this->getClosingBalance($accountCode, $toDate),
            'total_debits' => $entries->sum('debit'),
            'total_credits' => $entries->sum('credit'),
            'period' => ['from' => $fromDate, 'to' => $toDate],
        ];
    }

    public function getProfitAndLoss(string $fromDate, string $toDate): array
    {
        $revenues = ChartOfAccount::where('account_type', 'Revenue')->get();
        $revenueData = [];
        $totalRevenue = '0';

        foreach ($revenues as $revenue) {
            $balance = $this->getAccountActivity($revenue->account_code, $fromDate, $toDate);
            $revenueData[] = [
                'account_code' => $revenue->account_code,
                'account_name' => $revenue->account_name,
                'amount' => $balance,
            ];
            $totalRevenue = $this->mathService->add($totalRevenue, $balance);
        }

        $expenses = ChartOfAccount::where('account_type', 'Expense')->get();
        $expenseData = [];
        $totalExpenses = '0';

        foreach ($expenses as $expense) {
            $balance = $this->getAccountActivity($expense->account_code, $fromDate, $toDate);
            $expenseData[] = [
                'account_code' => $expense->account_code,
                'account_name' => $expense->account_name,
                'amount' => $balance,
            ];
            $totalExpenses = $this->mathService->add($totalExpenses, $balance);
        }

        $netProfit = $this->mathService->subtract($totalRevenue, $totalExpenses);

        return [
            'revenues' => $revenueData,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenseData,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'period' => ['from' => $fromDate, 'to' => $toDate],
        ];
    }

    public function getBalanceSheet(string $asOfDate): array
    {
        $assets = ChartOfAccount::where('account_type', 'Asset')->get();
        $assetData = [];
        $totalAssets = '0';

        foreach ($assets as $asset) {
            $balance = app(AccountingService::class)->getAccountBalance($asset->account_code, $asOfDate);
            $assetData[] = [
                'account_code' => $asset->account_code,
                'account_name' => $asset->account_name,
                'balance' => $balance,
            ];
            $totalAssets = $this->mathService->add($totalAssets, $balance);
        }

        $liabilities = ChartOfAccount::where('account_type', 'Liability')->get();
        $liabilityData = [];
        $totalLiabilities = '0';

        foreach ($liabilities as $liability) {
            $balance = app(AccountingService::class)->getAccountBalance($liability->account_code, $asOfDate);
            $liabilityData[] = [
                'account_code' => $liability->account_code,
                'account_name' => $liability->account_name,
                'balance' => $balance,
            ];
            $totalLiabilities = $this->mathService->add($totalLiabilities, $balance);
        }

        $equities = ChartOfAccount::where('account_type', 'Equity')->get();
        $equityData = [];
        $totalEquity = '0';

        foreach ($equities as $equity) {
            $balance = app(AccountingService::class)->getAccountBalance($equity->account_code, $asOfDate);
            $equityData[] = [
                'account_code' => $equity->account_code,
                'account_name' => $equity->account_name,
                'balance' => $balance,
            ];
            $totalEquity = $this->mathService->add($totalEquity, $balance);
        }

        $liabilitiesPlusEquity = $this->mathService->add($totalLiabilities, $totalEquity);

        return [
            'assets' => $assetData,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilityData,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equityData,
            'total_equity' => $totalEquity,
            'liabilities_plus_equity' => $liabilitiesPlusEquity,
            'is_balanced' => $this->mathService->compare($totalAssets, $liabilitiesPlusEquity) === 0,
            'as_of_date' => $asOfDate,
        ];
    }

    protected function getOpeningBalance(string $accountCode, string $fromDate): string
    {
        $entry = AccountLedger::where('account_code', $accountCode)
            ->where('entry_date', '<', $fromDate)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $entry ? (string) $entry->running_balance : '0';
    }

    protected function getClosingBalance(string $accountCode, string $toDate): string
    {
        return app(AccountingService::class)->getAccountBalance($accountCode, $toDate);
    }

    protected function getAccountActivity(string $accountCode, string $fromDate, string $toDate): string
    {
        $entries = AccountLedger::where('account_code', $accountCode)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->get();

        $activity = '0';
        foreach ($entries as $entry) {
            $activity = $this->mathService->add($activity, (string) $entry->credit);
            $activity = $this->mathService->subtract($activity, (string) $entry->debit);
        }

        return $activity;
    }
}
