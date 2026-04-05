<?php

namespace App\Http\Controllers;

use App\Services\LedgerService;
use Illuminate\Http\Request;

class FinancialStatementController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    protected function requireManagerOrAdmin(): void
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function trialBalance(Request $request)
    {
        $this->requireManagerOrAdmin();

        $asOfDate = $request->input('as_of', now()->toDateString());
        $trialBalance = $this->ledgerService->getTrialBalance($asOfDate);

        return view('accounting.trial-balance', compact('trialBalance', 'asOfDate'));
    }

    public function profitLoss(Request $request)
    {
        $this->requireManagerOrAdmin();

        $fromDate = $request->input('from', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to', now()->toDateString());

        $pl = $this->ledgerService->getProfitAndLoss($fromDate, $toDate);

        return view('accounting.profit-loss', compact('pl', 'fromDate', 'toDate'));
    }

    public function balanceSheet(Request $request)
    {
        $this->requireManagerOrAdmin();

        $asOfDate = $request->input('as_of', now()->toDateString());
        $balanceSheet = $this->ledgerService->getBalanceSheet($asOfDate);

        return view('accounting.balance-sheet', compact('balanceSheet', 'asOfDate'));
    }
}
