<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiscalYearCloseRequest;
use App\Http\Requests\StoreFiscalYearRequest;
use App\Models\FiscalYear;
use App\Services\Accounting\FiscalYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function __construct(
        protected FiscalYearService $fiscalYearService
    ) {}

    public function list(): View
    {
        $this->requireManagerOrAdmin();

        $fiscalYears = FiscalYear::with('periods')->orderBy('year_code', 'desc')->get();

        return view('accounting.fiscal-years', compact('fiscalYears'));
    }

    /**
     * Create a new fiscal year.
     */
    public function store(StoreFiscalYearRequest $request): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        try {
            $year = $this->fiscalYearService->createFiscalYear(
                $request->year_code,
                $request->start_date,
                $request->end_date
            );

            return redirect()->back()->with('success', "Fiscal year {$year->year_code} created successfully.");
        } catch (\Exception $e) {
            Log::error('FiscalYear create failed', ['exception' => $e, 'year_code' => $request->year_code]);

            return redirect()->back()->with('error', 'Failed to create fiscal year. Please try again.');
        }
    }

    /**
     * Close a fiscal year.
     */
    public function close(FiscalYear $year, FiscalYearCloseRequest $request): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validated();

        if ($validated['confirm_code'] !== $year->year_code) {
            return redirect()->back()->with('error', 'Year code confirmation failed.');
        }

        try {
            $result = $this->fiscalYearService->closeFiscalYear($year);

            return redirect()->back()->with('success', "Fiscal year {$year->year_code} closed successfully. Net income: {$result['net_income']}");
        } catch (\InvalidArgumentException $e) {
            Log::error('FiscalYear close failed', ['exception' => $e, 'year_code' => $year->year_code]);

            return redirect()->back()->with('error', 'Invalid fiscal year operation. Please check your input.');
        } catch (\Exception $e) {
            Log::error('FiscalYear close failed', ['exception' => $e, 'year_code' => $year->year_code]);

            return redirect()->back()->with('error', 'Failed to close fiscal year. Please try again.');
        }
    }
}
