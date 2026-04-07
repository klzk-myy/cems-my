<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

/**
 * Cash Flow Service
 *
 * Generates cash flow statements using the direct method:
 * Operating Activities: Cash receipts - Cash payments
 * Investing Activities: Purchase/sale of fixed assets, investments
 * Financing Activities: Proceeds/repayment of loans, dividends
 */
class CashFlowService
{
    /**
     * Math service for high-precision calculations.
     */
    protected MathService $mathService;

    /**
     * Account codes for cash accounts (bank accounts).
     */
    protected array $cashAccounts = [
        '1000', '1010', '1020', '1030', '1040', '1050', '1060', '1070', // Cash accounts
        '1100', '1110', '1120', '1130', // Bank accounts
    ];

    /**
     * Account codes for operating activities.
     */
    protected array $operatingAccounts = [
        'revenue' => ['5000', '5010', '5020', '5200', '5300', '5400'], // Revenue accounts
        'expense' => ['6000', '6010', '6100', '6200', '6210', '6220', '6230', '6300', '6310', '6320', '6330', '6400', '6410', '6500', '6510', '6520', '6530'],
    ];

    /**
     * Account codes for investing activities.
     */
    protected array $investingAccounts = [
        'asset_purchase' => ['2200'], // Security deposits
    ];

    /**
     * Account codes for financing activities.
     */
    protected array $financingAccounts = [
        'loans_received' => [],
        'loan_repayment' => [],
        'dividends' => [],
    ];

    /**
     * Create a new CashFlowService instance.
     */
    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    /**
     * Get complete cash flow statement.
     *
     * @param  string  $fromDate  Start date (YYYY-MM-DD)
     * @param  string  $toDate  End date (YYYY-MM-DD)
     * @return array Cash flow statement data
     */
    public function getCashFlowStatement(string $fromDate, string $toDate): array
    {
        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'operating' => $this->getOperatingCashFlow($fromDate, $toDate),
            'investing' => $this->getInvestingCashFlow($fromDate, $toDate),
            'financing' => $this->getFinancingCashFlow($fromDate, $toDate),
            'net_change' => $this->getNetCashChange($fromDate, $toDate),
            'opening_balance' => $this->getOpeningCashBalance($fromDate),
            'closing_balance' => $this->getClosingCashBalance($toDate),
        ];
    }

    /**
     * Get operating cash flow.
     *
     * Cash from customers - Cash paid to suppliers/employees/other
     */
    public function getOperatingCashFlow(string $fromDate, string $toDate): array
    {
        $cashReceived = $this->getCashReceivedFromCustomers($fromDate, $toDate);
        $cashPaidToSuppliers = $this->getCashPaidToSuppliers($fromDate, $toDate);
        $cashPaidForSalaries = $this->getCashPaidForSalaries($fromDate, $toDate);
        $cashPaidForExpenses = $this->getCashPaidForOtherExpenses($fromDate, $toDate);

        $netOperating = $this->mathService->subtract(
            $this->mathService->add($cashReceived, $cashPaidToSuppliers),
            $this->mathService->add($cashPaidForSalaries, $cashPaidForExpenses)
        );

        return [
            'cash_from_customers' => $cashReceived,
            'cash_paid_to_suppliers' => $cashPaidToSuppliers,
            'cash_paid_for_salaries' => $cashPaidForSalaries,
            'cash_paid_for_expenses' => $cashPaidForExpenses,
            'net_operating' => $netOperating,
        ];
    }

    /**
     * Get investing cash flow.
     *
     * Purchase/sale of assets, investment income
     */
    public function getInvestingCashFlow(string $fromDate, string $toDate): array
    {
        $assetPurchases = $this->getAssetPurchases($fromDate, $toDate);
        $assetSales = $this->getAssetSales($fromDate, $toDate);
        $investmentIncome = $this->getInvestmentIncome($fromDate, $toDate);

        $netInvesting = $this->mathService->add(
            $this->mathService->subtract($assetSales, $assetPurchases),
            $investmentIncome
        );

        return [
            'asset_purchases' => $assetPurchases,
            'asset_sales' => $assetSales,
            'investment_income' => $investmentIncome,
            'net_investing' => $netInvesting,
        ];
    }

    /**
     * Get financing cash flow.
     *
     * Proceeds/repayment of loans, dividends
     */
    public function getFinancingCashFlow(string $fromDate, string $toDate): array
    {
        $loansReceived = $this->getLoansReceived($fromDate, $toDate);
        $loanRepayments = $this->getLoanRepayments($fromDate, $toDate);
        $dividendsPaid = $this->getDividendsPaid($fromDate, $toDate);

        $netFinancing = $this->mathService->subtract(
            $this->mathService->subtract($loansReceived, $loanRepayments),
            $dividendsPaid
        );

        return [
            'loans_received' => $loansReceived,
            'loan_repayments' => $loanRepayments,
            'dividends_paid' => $dividendsPaid,
            'net_financing' => $netFinancing,
        ];
    }

    /**
     * Get net cash change for period.
     */
    public function getNetCashChange(string $fromDate, string $toDate): string
    {
        $operating = $this->getOperatingCashFlow($fromDate, $toDate);
        $investing = $this->getInvestingCashFlow($fromDate, $toDate);
        $financing = $this->getFinancingCashFlow($fromDate, $toDate);

        return $this->mathService->add(
            $this->mathService->add($operating['net_operating'], $investing['net_investing']),
            $financing['net_financing']
        );
    }

    /**
     * Get opening cash balance as of a date.
     */
    public function getOpeningCashBalance(string $asOfDate): string
    {
        $total = '0';

        foreach ($this->cashAccounts as $accountCode) {
            $balance = $this->getAccountBalance($accountCode, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get closing cash balance as of a date.
     */
    public function getClosingCashBalance(string $asOfDate): string
    {
        return $this->getOpeningCashBalance($asOfDate);
    }

    /**
     * Get cash received from customers.
     */
    protected function getCashReceivedFromCustomers(string $fromDate, string $toDate): string
    {
        // Cash received = Credits to AR + Debits to Cash accounts from revenue
        $total = '0';

        // Look at cash/bank accounts for inflows from revenue accounts
        foreach ($this->cashAccounts as $cashAccount) {
            $entries = AccountLedger::where('account_code', $cashAccount)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->where('debit', '>', 0)
                ->get();

            foreach ($entries as $entry) {
                // Check if this debit came from a revenue account (customer payment)
                $journalEntry = $entry->journal_entry;
                if ($journalEntry && $journalEntry->reference_type === 'CustomerPayment') {
                    $total = $this->mathService->add($total, (string) $entry->debit);
                }
            }
        }

        // Simplified: Total revenue * estimated cash collection ratio
        // In a real system, you'd track this more precisely via AR
        $totalRevenue = $this->getTotalForAccounts($this->operatingAccounts['revenue'], $fromDate, $toDate, 'credit');
        $totalExpense = $this->getTotalForAccounts($this->operatingAccounts['expense'], $fromDate, $toDate, 'debit');

        // Net operating cash flow approximation
        return $this->mathService->subtract($totalRevenue, $totalExpense);
    }

    /**
     * Get cash paid to suppliers.
     */
    protected function getCashPaidToSuppliers(string $fromDate, string $toDate): string
    {
        // Cash paid = Debits to AP - Credits from AP
        $totalApPaid = '0';

        // Simplified: COGS amount as proxy
        $cogs = $this->getTotalForAccounts(['6000', '6010'], $fromDate, $toDate, 'debit');

        return $cogs;
    }

    /**
     * Get cash paid for salaries.
     */
    protected function getCashPaidForSalaries(string $fromDate, string $toDate): string
    {
        $salaryAccounts = ['6200', '6210', '6220', '6230']; // Salaries, EPF, EIS, SOCSO
        return $this->getTotalForAccounts($salaryAccounts, $fromDate, $toDate, 'debit');
    }

    /**
     * Get cash paid for other operating expenses.
     */
    protected function getCashPaidForOtherExpenses(string $fromDate, string $toDate): string
    {
        $expenseAccounts = array_diff($this->operatingAccounts['expense'], ['6200', '6210', '6220', '6230']);
        return $this->getTotalForAccounts(array_values($expenseAccounts), $fromDate, $toDate, 'debit');
    }

    /**
     * Get asset purchases.
     */
    protected function getAssetPurchases(string $fromDate, string $toDate): string
    {
        return $this->getTotalForAccounts($this->investingAccounts['asset_purchase'], $fromDate, $toDate, 'debit');
    }

    /**
     * Get asset sales proceeds.
     */
    protected function getAssetSales(string $fromDate, string $toDate): string
    {
        return $this->getTotalForAccounts($this->investingAccounts['asset_purchase'], $fromDate, $toDate, 'credit');
    }

    /**
     * Get investment income received.
     */
    protected function getInvestmentIncome(string $fromDate, string $toDate): string
    {
        return $this->getTotalForAccounts(['5300'], $fromDate, $toDate, 'debit');
    }

    /**
     * Get loans received.
     */
    protected function getLoansReceived(string $fromDate, string $toDate): string
    {
        return '0'; // No loan accounts currently
    }

    /**
     * Get loan repayments.
     */
    protected function getLoanRepayments(string $fromDate, string $toDate): string
    {
        return '0'; // No loan accounts currently
    }

    /**
     * Get dividends paid.
     */
    protected function getDividendsPaid(string $fromDate, string $toDate): string
    {
        return '0'; // No dividend accounts currently
    }

    /**
     * Get total debits or credits for a set of accounts.
     */
    protected function getTotalForAccounts(array $accountCodes, string $fromDate, string $toDate, string $type): string
    {
        $total = '0';

        foreach ($accountCodes as $accountCode) {
            $query = AccountLedger::where('account_code', $accountCode)
                ->whereBetween('entry_date', [$fromDate, $toDate]);

            if ($type === 'debit') {
                $amount = $query->sum('debit');
            } else {
                $amount = $query->sum('credit');
            }

            $total = $this->mathService->add($total, (string) $amount);
        }

        return $total;
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
}
