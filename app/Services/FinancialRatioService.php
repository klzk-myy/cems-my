<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;

/**
 * Financial Ratio Service
 *
 * Calculates financial ratios for liquidity, profitability, leverage, and efficiency analysis.
 */
class FinancialRatioService
{
    /**
     * Math service for high-precision calculations.
     */
    protected MathService $mathService;

    /**
     * Create a new FinancialRatioService instance.
     */
    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    /**
     * Get all financial ratios.
     */
    public function getAllRatios(string $asOfDate, string $fromDate, string $toDate): array
    {
        return [
            'as_of_date' => $asOfDate,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'liquidity' => $this->getLiquidityRatios($asOfDate),
            'profitability' => $this->getProfitabilityRatios($fromDate, $toDate),
            'leverage' => $this->getLeverageRatios($asOfDate),
            'efficiency' => $this->getEfficiencyRatios($fromDate, $toDate),
        ];
    }

    /**
     * Get liquidity ratios.
     *
     * Current Ratio = Current Assets / Current Liabilities
     * Quick Ratio = (Current Assets - Inventory) / Current Liabilities
     * Cash Ratio = Cash / Current Liabilities
     */
    public function getLiquidityRatios(string $asOfDate): array
    {
        $currentAssets = $this->getCurrentAssets($asOfDate);
        $currentLiabilities = $this->getCurrentLiabilities($asOfDate);
        $inventory = $this->getInventory($asOfDate);
        $cash = $this->getCashBalance($asOfDate);

        $currentRatio = $this->divide($currentAssets, $currentLiabilities);
        $quickRatio = $this->divide(
            $this->mathService->subtract($currentAssets, $inventory),
            $currentLiabilities
        );
        $cashRatio = $this->divide($cash, $currentLiabilities);

        return [
            'current_ratio' => $currentRatio,
            'quick_ratio' => $quickRatio,
            'cash_ratio' => $cashRatio,
            'current_assets' => $currentAssets,
            'current_liabilities' => $currentLiabilities,
            'inventory' => $inventory,
            'cash' => $cash,
        ];
    }

    /**
     * Get profitability ratios.
     *
     * Gross Profit Margin = (Revenue - COGS) / Revenue
     * Net Profit Margin = Net Income / Revenue
     * ROE = Net Income / Equity
     * ROA = Net Income / Total Assets
     */
    public function getProfitabilityRatios(string $fromDate, string $toDate): array
    {
        $revenue = $this->getTotalRevenue($fromDate, $toDate);
        $cogs = $this->getTotalCOGS($fromDate, $toDate);
        $netIncome = $this->getNetIncome($fromDate, $toDate);
        $equity = $this->getTotalEquity($toDate);
        $totalAssets = $this->getTotalAssets($toDate);

        $grossProfit = $this->mathService->subtract($revenue, $cogs);
        $grossMargin = $this->divide($grossProfit, $revenue);
        $netMargin = $this->divide($netIncome, $revenue);
        $roe = $this->divide($netIncome, $equity);
        $roa = $this->divide($netIncome, $totalAssets);

        return [
            'gross_profit_margin' => $grossMargin,
            'net_profit_margin' => $netMargin,
            'roe' => $roe,
            'roa' => $roa,
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'net_income' => $netIncome,
            'equity' => $equity,
            'total_assets' => $totalAssets,
        ];
    }

    /**
     * Get leverage ratios.
     *
     * Debt-to-Equity = Total Debt / Equity
     * Debt-to-Assets = Total Debt / Total Assets
     */
    public function getLeverageRatios(string $asOfDate): array
    {
        $totalDebt = $this->getTotalLiabilities($asOfDate);
        $equity = $this->getTotalEquity($asOfDate);
        $totalAssets = $this->getTotalAssets($asOfDate);

        $debtToEquity = $this->divide($totalDebt, $equity);
        $debtToAssets = $this->divide($totalDebt, $totalAssets);

        return [
            'debt_to_equity' => $debtToEquity,
            'debt_to_assets' => $debtToAssets,
            'total_debt' => $totalDebt,
            'equity' => $equity,
            'total_assets' => $totalAssets,
        ];
    }

    /**
     * Get efficiency ratios.
     *
     * Asset Turnover = Revenue / Total Assets
     * Inventory Turnover = COGS / Inventory
     */
    public function getEfficiencyRatios(string $fromDate, string $toDate): array
    {
        $revenue = $this->getTotalRevenue($fromDate, $toDate);
        $cogs = $this->getTotalCOGS($fromDate, $toDate);
        $totalAssets = $this->getTotalAssets($toDate);
        $inventory = $this->getInventory($toDate);

        $assetTurnover = $this->divide($revenue, $totalAssets);
        $inventoryTurnover = $this->divide($cogs, $inventory);

        return [
            'asset_turnover' => $assetTurnover,
            'inventory_turnover' => $inventoryTurnover,
            'revenue' => $revenue,
            'total_assets' => $totalAssets,
            'cogs' => $cogs,
            'inventory' => $inventory,
        ];
    }

    /**
     * Get current assets (Asset accounts in 1000-1999 range).
     */
    protected function getCurrentAssets(string $asOfDate): string
    {
        $total = '0';
        $assetAccounts = ChartOfAccount::where('account_type', 'Asset')->get();

        foreach ($assetAccounts as $account) {
            $balance = $this->getAccountBalance($account->account_code, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get current liabilities (Liability accounts).
     */
    protected function getCurrentLiabilities(string $asOfDate): string
    {
        $total = '0';
        $liabilityAccounts = ChartOfAccount::where('account_type', 'Liability')->get();

        foreach ($liabilityAccounts as $account) {
            $balance = $this->getAccountBalance($account->account_code, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get inventory balance.
     */
    protected function getInventory(string $asOfDate): string
    {
        $total = '0';
        // Inventory accounts (2000-2499 range)
        $inventoryAccounts = ChartOfAccount::where('account_class', 'Inventory')->get();

        foreach ($inventoryAccounts as $account) {
            $balance = $this->getAccountBalance($account->account_code, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        // If no inventory accounts with class, use 2000 range
        if ($this->mathService->compare($total, '0') === 0) {
            $inventoryAccounts = ChartOfAccount::whereBetween('account_code', ['2000', '2499'])->get();
            foreach ($inventoryAccounts as $account) {
                $balance = $this->getAccountBalance($account->account_code, $asOfDate);
                $total = $this->mathService->add($total, $balance);
            }
        }

        return $total;
    }

    /**
     * Get cash balance (cash + bank accounts).
     */
    protected function getCashBalance(string $asOfDate): string
    {
        $total = '0';
        // Cash accounts (1000-1499 range)
        $cashAccounts = ChartOfAccount::whereBetween('account_code', ['1000', '1499'])->get();

        foreach ($cashAccounts as $account) {
            $balance = $this->getAccountBalance($account->account_code, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get total revenue for a period.
     */
    protected function getTotalRevenue(string $fromDate, string $toDate): string
    {
        $total = '0';
        $revenueAccounts = ChartOfAccount::where('account_type', 'Revenue')->get();

        foreach ($revenueAccounts as $account) {
            $credits = AccountLedger::where('account_code', $account->account_code)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->sum('credit');
            $debits = AccountLedger::where('account_code', $account->account_code)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->sum('debit');
            // Revenue has credit balance, so credits - debits
            $balance = $this->mathService->subtract((string) $credits, (string) $debits);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get total COGS for a period.
     */
    protected function getTotalCOGS(string $fromDate, string $toDate): string
    {
        $total = '0';
        // COGS accounts (6000-6499 range)
        $cogsAccounts = ChartOfAccount::whereBetween('account_code', ['6000', '6499'])->get();

        foreach ($cogsAccounts as $account) {
            $debits = AccountLedger::where('account_code', $account->account_code)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->sum('debit');
            $credits = AccountLedger::where('account_code', $account->account_code)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->sum('credit');
            // COGS has debit balance, so debits - credits
            $balance = $this->mathService->subtract((string) $debits, (string) $credits);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get net income (Revenue - Expenses).
     */
    protected function getNetIncome(string $fromDate, string $toDate): string
    {
        $revenue = $this->getTotalRevenue($fromDate, $toDate);
        $expenses = $this->getTotalExpenses($fromDate, $toDate);

        return $this->mathService->subtract($revenue, $expenses);
    }

    /**
     * Get total expenses for a period.
     */
    protected function getTotalExpenses(string $fromDate, string $toDate): string
    {
        $total = '0';
        $expenseAccounts = ChartOfAccount::where('account_type', 'Expense')->get();

        foreach ($expenseAccounts as $account) {
            $debits = AccountLedger::where('account_code', $account->account_code)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->sum('debit');
            $credits = AccountLedger::where('account_code', $account->account_code)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->sum('credit');
            // Expense has debit balance
            $balance = $this->mathService->subtract((string) $debits, (string) $credits);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get total equity.
     */
    protected function getTotalEquity(string $asOfDate): string
    {
        $total = '0';
        $equityAccounts = ChartOfAccount::where('account_type', 'Equity')->get();

        foreach ($equityAccounts as $account) {
            $balance = $this->getAccountBalance($account->account_code, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get total assets.
     */
    protected function getTotalAssets(string $asOfDate): string
    {
        return $this->getCurrentAssets($asOfDate);
    }

    /**
     * Get total liabilities.
     */
    protected function getTotalLiabilities(string $asOfDate): string
    {
        return $this->getCurrentLiabilities($asOfDate);
    }

    /**
     * Get account balance as of a date.
     */
    protected function getAccountBalance(string $accountCode, string $asOfDate): string
    {
        $lastEntry = AccountLedger::where('account_code', $accountCode)
            ->whereRaw('DATE(entry_date) <= ?', [$asOfDate])
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastEntry ? (string) $lastEntry->running_balance : '0';
    }

    /**
     * Safe division that returns 0 if divisor is 0.
     */
    protected function divide(string $numerator, string $denominator): string
    {
        if ($this->mathService->compare($denominator, '0') === 0) {
            return '0';
        }

        return $this->mathService->divide($numerator, $denominator, 4);
    }
}
