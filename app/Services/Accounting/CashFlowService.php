<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Services\Contracts\MathServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CashFlowService
{
    public function __construct(
        protected MathServiceInterface $math,
    ) {}

    public function getCashFlow(string $fromDate, string $toDate, ?int $branchId = null): array
    {
        $cacheKey = "cashflow:{$fromDate}:{$toDate}:{$branchId}";

        return Cache::tags(['reports', 'cash-flow'])->remember($cacheKey, 300, function () use ($fromDate, $toDate, $branchId) {
            $operatingActivities = $this->calculateOperatingActivities($fromDate, $toDate, $branchId);
            $investingActivities = $this->calculateInvestingActivities($fromDate, $toDate, $branchId);
            $financingActivities = $this->calculateFinancingActivities($fromDate, $toDate, $branchId);

            $cashFromOperations = $operatingActivities['total'];
            $cashFromInvesting = $investingActivities['total'];
            $cashFromFinancing = $financingActivities['total'];

            $netCashChange = $this->math->add(
                $this->math->add($cashFromOperations, $cashFromInvesting),
                $cashFromFinancing
            );

            return [
                'from' => $fromDate,
                'to' => $toDate,
                'operating_activities' => $operatingActivities,
                'investing_activities' => $investingActivities,
                'financing_activities' => $financingActivities,
                'net_change_in_cash' => $netCashChange,
                'operating_total' => $cashFromOperations,
                'investing_total' => $cashFromInvesting,
                'financing_total' => $cashFromFinancing,
            ];
        });
    }

    protected function calculateOperatingActivities(string $fromDate, string $toDate, ?int $branchId): array
    {
        $entries = JournalEntry::whereBetween('entry_date', [$fromDate, $toDate])
            ->with(['lines.account'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        // Exact decimal arithmetic: the previous float subtraction fed rounded
        // values into the reported net income.
        $netIncome = $this->math->subtract($this->sumRevenueAccounts($entries), $this->sumExpenseAccounts($entries));

        $depreciation = $this->sumByAccountClass($entries, 'Expense', 'Depreciation');
        $amortization = $this->sumByAccountClass($entries, 'Expense', 'Amortization');

        $arChange = $this->calculateReceivableChange($fromDate, $toDate, $branchId);
        $apChange = $this->calculatePayableChange($fromDate, $toDate, $branchId);
        $inventoryChange = $this->calculateInventoryChange($fromDate, $toDate, $branchId);

        $nonCashAdjustments = $this->math->add($depreciation, $amortization);
        $workingCapitalChanges = $this->math->add(
            $this->math->add($arChange, $apChange),
            $inventoryChange
        );

        $total = $this->math->add(
            $this->math->add($netIncome, $nonCashAdjustments),
            $workingCapitalChanges
        );

        return [
            'net_income' => $netIncome,
            'depreciation' => $depreciation,
            'amortization' => $amortization,
            'ar_change' => (string) $arChange,
            'ap_change' => (string) $apChange,
            'inventory_change' => (string) $inventoryChange,
            'total' => $total,
        ];
    }

    protected function calculateInvestingActivities(string $fromDate, string $toDate, ?int $branchId): array
    {
        $entries = JournalEntry::whereBetween('entry_date', [$fromDate, $toDate])
            ->with(['lines.account'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        $assetPurchases = $this->sumByAccountClass($entries, 'Asset', 'Fixed Asset');
        $assetSales = $this->sumByAccountClass($entries, 'Revenue', 'Disposal');

        $total = $this->math->subtract($assetSales, $assetPurchases);

        return [
            'asset_purchases' => $assetPurchases,
            'asset_sales' => $assetSales,
            'total' => $total,
        ];
    }

    protected function calculateFinancingActivities(string $fromDate, string $toDate, ?int $branchId): array
    {
        $entries = JournalEntry::whereBetween('entry_date', [$fromDate, $toDate])
            ->with(['lines.account'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        $debtIssued = $this->sumByAccountClass($entries, 'Liability', 'Debt');
        $debtRepaid = $this->sumByAccountClass($entries, 'Liability', 'Debt');
        $equityIssued = $this->sumByAccountClass($entries, 'Equity', 'Capital');
        $dividends = $this->sumByAccountClass($entries, 'Equity', 'Dividend');

        $total = $this->math->add($this->math->add($equityIssued, $debtIssued), $this->math->subtract('0', $dividends));

        return [
            'debt_issued' => $debtIssued,
            'equity_issued' => $equityIssued,
            'dividends' => $dividends,
            'total' => $total,
        ];
    }

    protected function sumRevenueAccounts($entries): string
    {
        return $entries->flatMap->lines
            ->filter(fn ($line) => $line->account->account_type === 'Revenue')
            ->reduce(
                fn (string $carry, $line) => $this->math->add($carry, (string) $line->credit),
                '0'
            );
    }

    protected function sumExpenseAccounts($entries): string
    {
        return $entries->flatMap->lines
            ->filter(fn ($line) => $line->account->account_type === 'Expense')
            ->reduce(
                fn (string $carry, $line) => $this->math->add($carry, (string) $line->debit),
                '0'
            );
    }

    protected function sumByAccountClass($entries, string $type, string $class): string
    {
        // BCMath accumulation over decimal strings: collection sum() would
        // float-coerce decimal(18,4) columns and corrupt reporting output.
        $debit = $entries->flatMap->lines
            ->filter(fn ($line) => $line->account->account_type === $type && $line->account->account_class === $class)
            ->reduce(
                fn (string $carry, $line) => $this->math->add($carry, (string) $line->debit),
                '0'
            );

        $credit = $entries->flatMap->lines
            ->filter(fn ($line) => $line->account->account_type === $type && $line->account->account_class === $class)
            ->reduce(
                fn (string $carry, $line) => $this->math->add($carry, (string) $line->credit),
                '0'
            );

        return $this->math->subtract($debit, $credit);
    }

    protected function calculateReceivableChange(string $fromDate, string $toDate, ?int $branchId): string
    {
        $start = DB::table('journal_lines')
            ->join('chart_of_accounts', 'journal_lines.account_code', '=', 'chart_of_accounts.account_code')
            ->where('chart_of_accounts.account_class', 'Receivable')
            ->where('journal_lines.entry_date', '<', $fromDate)
            ->when($branchId, fn ($q) => $q->join('journal_entries as je_start', 'journal_lines.journal_entry_id', '=', 'je_start.id')->where('je_start.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        $end = DB::table('journal_lines')
            ->join('chart_of_accounts', 'journal_lines.account_code', '=', 'chart_of_accounts.account_code')
            ->where('chart_of_accounts.account_class', 'Receivable')
            ->where('journal_lines.entry_date', '<=', $toDate)
            ->when($branchId, fn ($q) => $q->join('journal_entries as je_end', 'journal_lines.journal_entry_id', '=', 'je_end.id')->where('je_end.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        return $this->math->negate($this->math->subtract((string) $end, (string) $start));
    }

    protected function calculatePayableChange(string $fromDate, string $toDate, ?int $branchId): string
    {
        $start = DB::table('journal_lines')
            ->join('chart_of_accounts', 'journal_lines.account_code', '=', 'chart_of_accounts.account_code')
            ->where('chart_of_accounts.account_class', 'Payable')
            ->where('journal_lines.entry_date', '<', $fromDate)
            ->when($branchId, fn ($q) => $q->join('journal_entries as je_start', 'journal_lines.journal_entry_id', '=', 'je_start.id')->where('je_start.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        $end = DB::table('journal_lines')
            ->join('chart_of_accounts', 'journal_lines.account_code', '=', 'chart_of_accounts.account_code')
            ->where('chart_of_accounts.account_class', 'Payable')
            ->where('journal_lines.entry_date', '<=', $toDate)
            ->when($branchId, fn ($q) => $q->join('journal_entries as je_end', 'journal_lines.journal_entry_id', '=', 'je_end.id')->where('je_end.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        return $this->math->subtract((string) $end, (string) $start);
    }

    protected function calculateInventoryChange(string $fromDate, string $toDate, ?int $branchId): string
    {
        $start = DB::table('journal_lines')
            ->join('chart_of_accounts', 'journal_lines.account_code', '=', 'chart_of_accounts.account_code')
            ->where('chart_of_accounts.account_class', 'Inventory')
            ->where('journal_lines.entry_date', '<', $fromDate)
            ->when($branchId, fn ($q) => $q->join('journal_entries as je_start', 'journal_lines.journal_entry_id', '=', 'je_start.id')->where('je_start.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        $end = DB::table('journal_lines')
            ->join('chart_of_accounts', 'journal_lines.account_code', '=', 'chart_of_accounts.account_code')
            ->where('chart_of_accounts.account_class', 'Inventory')
            ->where('journal_lines.entry_date', '<=', $toDate)
            ->when($branchId, fn ($q) => $q->join('journal_entries as je_end', 'journal_lines.journal_entry_id', '=', 'je_end.id')->where('je_end.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        return $this->math->negate($this->math->subtract((string) $end, (string) $start));
    }
}
