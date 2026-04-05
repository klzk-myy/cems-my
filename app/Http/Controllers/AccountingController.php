<?php

namespace App\Http\Controllers;

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
        $accountCode = $request->get('account', $cashAccounts->first()?->account_code);
        $service = new \App\Services\ReconciliationService;
        $report = $service->getReconciliationReport(
            $accountCode,
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        return view('accounting.reconciliation', compact('report', 'cashAccounts'));
    }
}
