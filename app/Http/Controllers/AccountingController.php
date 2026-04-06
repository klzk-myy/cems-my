<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliation;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    protected function requireManagerOrAdmin(): void
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function index()
    {
        $this->requireManagerOrAdmin();

        $entries = JournalEntry::with('postedBy')
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('accounting.journal.index', compact('entries'));
    }

    public function create()
    {
        $this->requireManagerOrAdmin();

        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('accounting.journal.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_code' => 'required|string|exists:chart_of_accounts,account_code',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        try {
            $entry = $this->accountingService->createJournalEntry(
                $validated['lines'],
                'Manual',
                null,
                $validated['description'],
                $validated['entry_date']
            );

            return redirect()->route('accounting.journal.show', $entry)
                ->with('success', 'Journal entry created successfully.');

        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }
    }

    public function show(JournalEntry $entry)
    {
        $this->requireManagerOrAdmin();
        $entry->load('lines.account', 'postedBy', 'reversedBy');

        return view('accounting.journal.show', compact('entry'));
    }

    public function reverse(Request $request, JournalEntry $entry)
    {
        $this->requireManagerOrAdmin();

        if ($entry->isReversed()) {
            return back()->with('error', 'Entry is already reversed.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $reversal = $this->accountingService->reverseJournalEntry(
                $entry,
                $validated['reason']
            );

            return redirect()->route('accounting.journal.show', $reversal)
                ->with('success', 'Entry reversed successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Reversal failed: '.$e->getMessage());
        }
    }

    /**
     * Display period management
     */
    public function periods(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }
        $periods = \App\Models\AccountingPeriod::orderBy('start_date', 'desc')->paginate(12);

        return view('accounting.periods', compact('periods'));
    }

    /**
     * Close a period
     */
    public function closePeriod(Request $request, \App\Models\AccountingPeriod $period)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }
        $service = new \App\Services\PeriodCloseService(
            new \App\Services\AccountingService(new \App\Services\MathService),
            new \App\Services\MathService
        );
        try {
            $result = $service->closePeriod($period, auth()->id());

            return redirect()->route('accounting.periods')
                ->with('success', "Period {$period->period_code} closed successfully");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display budget vs actual report
     */
    public function budget(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }
        $periodCode = $request->get('period', now()->format('Y-m'));
        $service = new \App\Services\BudgetService(
            new \App\Services\AccountingService(new \App\Services\MathService),
            new \App\Services\MathService
        );
        $report = $service->getBudgetReport($periodCode);
        $unbudgeted = $service->getAccountsWithoutBudget($periodCode);

        return view('accounting.budget', compact('report', 'unbudgeted', 'periodCode'));
    }

    /**
     * Display bank reconciliation
     */
    public function reconciliation(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }
        $cashAccounts = \App\Models\ChartOfAccount::where('account_type', 'Asset')
            ->where('account_name', 'like', '%Cash%')
            ->where('is_active', true)
            ->get();
        $accountCode = $request->get('account_code', $request->get('account', $cashAccounts->first()?->account_code));
        $fromDate = $request->get('from', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to', now()->endOfMonth()->toDateString());
        $service = new \App\Services\ReconciliationService;
        $rawReport = $service->getReconciliationReport(
            $accountCode,
            $fromDate,
            $toDate
        );

        // Transform report to match view expectations
        $outstandingChecks = collect();
        $outstandingDeposits = collect();
        foreach ($rawReport['unmatched_items'] as $item) {
            $itemData = [
                'date' => $item->statement_date?->toDateString(),
                'reference' => $item->reference,
                'amount' => $item->getAmount(),
            ];
            if ($item->debit > 0) {
                $outstandingChecks->push($itemData);
            } else {
                $outstandingDeposits->push($itemData);
            }
        }

        $report = [
            'book_balance' => $rawReport['statement_balance'] ?? 0,
            'outstanding_checks' => $outstandingChecks->sum(fn ($i) => $i['amount']),
            'outstanding_deposits' => $outstandingDeposits->sum(fn ($i) => abs($i['amount'])),
            'adjusted_balance' => ($rawReport['statement_balance'] ?? 0) + $outstandingChecks->sum(fn ($i) => $i['amount']) - $outstandingDeposits->sum(fn ($i) => abs($i['amount'])),
            'outstanding_checks_list' => $outstandingChecks->toArray(),
            'outstanding_deposits_list' => $outstandingDeposits->toArray(),
        ];

        return view('accounting.reconciliation', compact('report', 'cashAccounts'));
    }

    /**
     * Import bank statement lines
     */
    public function importBankStatement(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'account_code' => 'required|string|exists:chart_of_accounts,account_code',
            'lines' => 'required|array|min:1',
            'lines.*.date' => 'required|date',
            'lines.*.reference' => 'nullable|string|max:255',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $service = new \App\Services\ReconciliationService;
        $result = $service->importStatement(
            $validated['account_code'],
            $validated['lines'],
            auth()->id()
        );

        return redirect()->route('accounting.reconciliation')
            ->with('success', "Imported {$result['imported']} lines. {$result['unmatched']} unmatched.");
    }

    /**
     * Mark a reconciliation item as exception
     */
    public function markAsException(Request $request, BankReconciliation $reconciliation)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $service = new \App\Services\ReconciliationService;
        $service->markAsException($reconciliation->id, $validated['reason'], auth()->id());

        return redirect()->route('accounting.reconciliation')
            ->with('success', 'Item marked as exception.');
    }

    /**
     * Generate bank reconciliation report
     */
    public function reconciliationReport(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'account_code' => 'required|string|exists:chart_of_accounts,account_code',
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $service = new \App\Services\ReconciliationService;
        $report = $service->getReconciliationReport(
            $validated['account_code'],
            $validated['from'],
            $validated['to']
        );

        return view('accounting.reconciliation_report', compact('report'));
    }

    /**
     * Export bank reconciliation report
     */
    public function exportReconciliation(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'account_code' => 'required|string|exists:chart_of_accounts,account_code',
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $service = new \App\Services\ReconciliationService;
        $report = $service->getReconciliationReport(
            $validated['account_code'],
            $validated['from'],
            $validated['to']
        );

        return view('accounting.reconciliation_export', compact('report'));
    }

    /**
     * Store or update budget for a period
     */
    public function storeBudget(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'period_code' => 'required|string',
            'budgets' => 'required|array|min:1',
            'budgets.*.account_code' => 'required|string|exists:chart_of_accounts,account_code',
            'budgets.*.amount' => 'required|numeric|min:0',
        ]);

        $service = new \App\Services\BudgetService(
            new \App\Services\AccountingService(new \App\Services\MathService),
            new \App\Services\MathService
        );

        foreach ($validated['budgets'] as $budgetData) {
            $service->setBudget(
                $budgetData['account_code'],
                $validated['period_code'],
                $budgetData['amount'],
                auth()->id()
            );
        }

        return redirect()->route('accounting.budget', ['period' => $validated['period_code']])
            ->with('success', 'Budget saved successfully.');
    }

    /**
     * Update an existing budget
     */
    public function updateBudget(Request $request, Budget $budget)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $budget->update([
            'budget_amount' => $validated['amount'],
        ]);

        return redirect()->route('accounting.budget')
            ->with('success', 'Budget updated successfully.');
    }
}
