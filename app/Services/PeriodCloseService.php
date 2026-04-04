<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\SystemLog;
use Exception;
use Illuminate\Support\Facades\DB;

class PeriodCloseService
{
    protected AccountingService $accountingService;

    protected MathService $mathService;

    public function __construct(AccountingService $accountingService, MathService $mathService)
    {
        $this->accountingService = $accountingService;
        $this->mathService = $mathService;
    }

    /**
     * Close an accounting period
     */
    public function closePeriod(AccountingPeriod $period, int $closedBy): array
    {
        if ($period->isClosed()) {
            throw new Exception('Period is already closed');
        }

        return DB::transaction(function () use ($period, $closedBy) {
            // Step 1: Validate all entries are balanced
            $this->validatePeriodBalances($period);

            // Step 2: Create closing entries for revenue/expense accounts
            $closingEntries = $this->createClosingEntries($period, $closedBy);

            // Step 3: Update period status
            $period->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $closedBy,
            ]);

            // Step 4: Log the action
            SystemLog::create([
                'user_id' => $closedBy,
                'action' => 'period_closed',
                'entity_type' => 'AccountingPeriod',
                'entity_id' => $period->id,
                'new_values' => [
                    'period_code' => $period->period_code,
                    'closed_at' => now()->toDateTimeString(),
                ],
                'severity' => 'INFO',
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'period' => $period,
                'closing_entries' => $closingEntries,
            ];
        });
    }

    /**
     * Validate all journal entries in period are balanced
     */
    protected function validatePeriodBalances(AccountingPeriod $period): void
    {
        $unbalanced = JournalEntry::where('period_id', $period->id)
            ->where('status', 'Posted')
            ->get()
            ->filter(fn ($entry) => ! $entry->isBalanced());

        if ($unbalanced->isNotEmpty()) {
            $ids = $unbalanced->pluck('id')->join(', ');
            throw new Exception("Unbalanced journal entries found: {$ids}");
        }
    }

    /**
     * Create closing entries to transfer revenue/expense to retained earnings
     */
    protected function createClosingEntries(AccountingPeriod $period, int $closedBy): array
    {
        $entries = [];

        // Get revenue accounts
        $revenues = ChartOfAccount::where('account_type', 'Revenue')->get();
        $totalRevenue = '0';
        foreach ($revenues as $account) {
            $balance = $this->accountingService->getAccountBalance(
                $account->account_code,
                $period->end_date->toDateString()
            );
            $totalRevenue = $this->mathService->add($totalRevenue, $balance);
        }

        // Get expense accounts
        $expenses = ChartOfAccount::where('account_type', 'Expense')->get();
        $totalExpenses = '0';
        foreach ($expenses as $account) {
            $balance = $this->accountingService->getAccountBalance(
                $account->account_code,
                $period->end_date->toDateString()
            );
            $totalExpenses = $this->mathService->add($totalExpenses, $balance);
        }

        // Calculate net income
        $netIncome = $this->mathService->subtract($totalRevenue, $totalExpenses);

        // Only create entry if there's activity
        if ($this->mathService->compare($netIncome, '0') !== 0) {
            // Validate and get configured account codes
            $revenueSummaryAccount = $this->getValidatedAccountCode('accounting.revenue_summary_account', '4000');
            $expenseSummaryAccount = $this->getValidatedAccountCode('accounting.expense_summary_account', '5000');
            $retainedEarningsAccount = $this->getValidatedAccountCode('accounting.retained_earnings_account', '3100');

            $entry = $this->accountingService->createJournalEntry(
                [
                    [
                        'account_code' => $revenueSummaryAccount,
                        'debit' => $totalRevenue,
                        'credit' => 0,
                    ],
                    [
                        'account_code' => $expenseSummaryAccount,
                        'debit' => 0,
                        'credit' => $totalExpenses,
                    ],
                    [
                        'account_code' => $retainedEarningsAccount,
                        'debit' => $this->mathService->compare($netIncome, '0') < 0 ? $this->mathService->multiply($netIncome, '-1') : 0,
                        'credit' => $this->mathService->compare($netIncome, '0') > 0 ? $netIncome : 0,
                    ],
                ],
                'Period_Close',
                $period->id,
                "Period close for {$period->period_code} - Net Income: RM {$netIncome}",
                $period->end_date->toDateString(),
                $closedBy
            );

            // Update the entry with period_id
            $entry->update(['period_id' => $period->id]);
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Get validated account code from config.
     * Throws exception if account doesn't exist or is inactive (when validation is enabled).
     */
    protected function getValidatedAccountCode(string $configKey, string $defaultCode): string
    {
        $code = \Illuminate\Support\Facades\Config::get($configKey, $defaultCode);

        if (\Illuminate\Support\Facades\Config::get('accounting.validate_accounts', true)) {
            $account = ChartOfAccount::where('account_code', $code)->first();

            if (! $account) {
                throw new \InvalidArgumentException("Configured account '{$configKey}' with code '{$code}' does not exist in chart of accounts");
            }

            if (! $account->is_active) {
                throw new \InvalidArgumentException("Configured account '{$configKey}' with code '{$code}' is not active");
            }
        }

        return $code;
    }
}
