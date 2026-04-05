<?php

namespace App\Http\Controllers;

use App\Enums\CddLevel;
use App\Enums\ComplianceFlagType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\FlaggedTransaction;
use App\Models\ReportGenerated;
use App\Models\Transaction;
use App\Services\ExportService;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    protected ReportingService $reportingService;

    protected ExportService $exportService;

    public function __construct(
        ReportingService $reportingService,
        ExportService $exportService
    ) {
        $this->reportingService = $reportingService;
        $this->exportService = $exportService;
    }

    protected function requireManagerOrAdmin(): void
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function lctr(Request $request)
    {
        $this->requireManagerOrAdmin();

        // Validate month parameter
        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');

        // Check if report already generated
        $reportGenerated = ReportGenerated::where('report_type', 'LCTR')
            ->where('period_start', now()->parse($month)->startOfMonth())
            ->first();

        // Get qualifying transactions (>= RM50,000 and Completed per BNM requirements)
        $startDate = now()->parse($month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $transactions = Transaction::where('amount_local', '>=', ReportingService::CTR_THRESHOLD)
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'user'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Count pending transactions that would qualify
        $pendingTransactions = Transaction::where('amount_local', '>=', ReportingService::CTR_THRESHOLD)
            ->where('status', TransactionStatus::Pending)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $stats = [
            'count' => $transactions->count(),
            'total_amount' => $transactions->sum('amount_local'),
            'unique_customers' => $transactions->pluck('customer_id')->unique()->count(),
        ];

        return view('reports.lctr', compact('month', 'transactions', 'stats', 'reportGenerated', 'pendingTransactions'));
    }

    public function lctrGenerate(Request $request)
    {
        $this->requireManagerOrAdmin();

        $month = $request->input('month', now()->format('Y-m'));
        $report = $this->reportingService->generateLCTRData($month);

        $periodStart = now()->parse($month)->startOfMonth();
        $version = ReportGenerated::where('report_type', 'LCTR')
            ->where('period_start', $periodStart)
            ->max('version') + 1;

        ReportGenerated::create([
            'report_type' => 'LCTR',
            'period_start' => $periodStart,
            'period_end' => now()->parse($month)->endOfMonth(),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
            'version' => $version,
        ]);

        return response()->json($report);
    }

    public function msb2(Request $request)
    {
        $this->requireManagerOrAdmin();

        // Validate date parameter
        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $date = $validated['date'] ?? now()->subDay()->toDateString();

        // Check existing report
        $reportGenerated = ReportGenerated::where('report_type', 'MSB2')
            ->whereDate('period_start', $date)
            ->first();

        // Get summary data using query builder
        $summary = DB::table('transactions')
            ->select(
                'currency_code',
                DB::raw("SUM(CASE WHEN type = '".TransactionType::Buy->value."' THEN amount_foreign ELSE 0 END) as buy_volume_foreign"),
                DB::raw("SUM(CASE WHEN type = '".TransactionType::Buy->value."' THEN amount_local ELSE 0 END) as buy_amount_myr"),
                DB::raw("COUNT(CASE WHEN type = '".TransactionType::Buy->value."' THEN 1 END) as buy_count"),
                DB::raw("SUM(CASE WHEN type = '".TransactionType::Sell->value."' THEN amount_foreign ELSE 0 END) as sell_volume_foreign"),
                DB::raw("SUM(CASE WHEN type = '".TransactionType::Sell->value."' THEN amount_local ELSE 0 END) as sell_amount_myr"),
                DB::raw("COUNT(CASE WHEN type = '".TransactionType::Sell->value."' THEN 1 END) as sell_count")
            )
            ->whereDate('created_at', $date)
            ->where('status', TransactionStatus::Completed)
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get();

        // Calculate totals
        $stats = [
            'total_transactions' => $summary->sum('buy_count') + $summary->sum('sell_count'),
            'total_buy_myr' => $summary->sum('buy_amount_myr'),
            'total_sell_myr' => $summary->sum('sell_amount_myr'),
            'net_position' => $summary->sum('buy_amount_myr') - $summary->sum('sell_amount_myr'),
        ];

        // Calculate next business day
        $nextBusinessDay = now()->parse($date)->addWeekday()->format('Y-m-d');
        $isToday = $date === now()->toDateString();

        return view('reports.msb2', compact('date', 'summary', 'stats', 'reportGenerated', 'nextBusinessDay', 'isToday'));
    }

    public function msb2Generate(Request $request)
    {
        $this->requireManagerOrAdmin();

        $date = $request->input('date', now()->subDay()->toDateString());
        $report = $this->reportingService->generateMSB2Data($date);

        ReportGenerated::create([
            'report_type' => 'MSB2',
            'period_start' => $date,
            'period_end' => $date,
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
        ]);

        return response()->json($report);
    }

    public function generateLCTR(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $filepath = $this->reportingService->generateLCTR($request->month);

        return response()->json([
            'message' => 'LCTR report generated',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/'.basename($filepath)),
        ]);
    }

    public function generateMSB2(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $filepath = $this->reportingService->generateMSB2($request->date);

        return response()->json([
            'message' => 'MSB(2) report generated',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/'.basename($filepath)),
        ]);
    }

    /**
     * Update LCTR report status (mark as submitted)
     */
    public function updateLCTRStatus(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'status' => 'required|in:Submitted',
        ]);

        $report = ReportGenerated::where('report_type', 'LCTR')
            ->where('period_start', now()->parse($validated['month'])->startOfMonth())
            ->first();

        if (! $report) {
            return response()->json([
                'message' => 'Report not found. Generate the report first.',
            ], 404);
        }

        $report->update([
            'status' => $validated['status'],
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Report status updated successfully',
            'status' => $report->status,
        ]);
    }

    /**
     * Update MSB2 report status (mark as submitted)
     */
    public function updateMSB2Status(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|in:Submitted',
        ]);

        $report = ReportGenerated::where('report_type', 'MSB2')
            ->whereDate('period_start', $validated['date'])
            ->first();

        if (! $report) {
            return response()->json([
                'message' => 'Report not found. Generate the report first.',
            ], 404);
        }

        $report->update([
            'status' => $validated['status'],
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Report status updated successfully',
            'status' => $report->status,
        ]);
    }

    public function download(string $filename)
    {
        $filepath = "reports/{$filename}";

        if (! Storage::exists($filepath)) {
            abort(404, 'Report not found');
        }

        return Storage::download($filepath);
    }

    public function export(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'report_type' => 'required|in:lctr,msb2,trial_balance,pl,balance_sheet',
            'period' => 'required|string',
            'format' => 'required|in:CSV,PDF,XLSX',
        ]);

        $data = match ($validated['report_type']) {
            'lctr' => $this->reportingService->generateLCTRData($validated['period']),
            'msb2' => $this->reportingService->generateMSB2Data($validated['period']),
            default => ['data' => []],
        };

        $filename = "{$validated['report_type']}_{$validated['period']}.".strtolower($validated['format']);

        switch ($validated['format']) {
            case 'CSV':
                $path = $this->exportService->toCSV($data['data'], $filename);

                return response()->download($path);

            case 'PDF':
                $path = $this->exportService->toPDF($data, 'reports.pdf', $filename);

                return response()->download($path);

            case 'XLSX':
                $path = $this->exportService->toExcel($data['data'], $filename);

                return response()->download($path);
        }
    }

    /**
     * Monthly transaction trends
     */
    public function monthlyTrends(Request $request)
    {
        $this->requireManagerOrAdmin();

        $year = $request->input('year', now()->year);
        $currency = $request->input('currency', 'all');

        // Query monthly data
        $query = Transaction::whereYear('created_at', $year)
            ->where('status', TransactionStatus::Completed);

        if ($currency !== 'all') {
            $query->where('currency_code', $currency);
        }

        $monthlyData = $query->select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count'),
            DB::raw("SUM(CASE WHEN type = '".TransactionType::Buy->value."' THEN amount_local ELSE 0 END) as buy_volume"),
            DB::raw("SUM(CASE WHEN type = '".TransactionType::Sell->value."' THEN amount_local ELSE 0 END) as sell_volume"),
            DB::raw('SUM(amount_local) as total_volume')
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Calculate trends
        $trends = $this->calculateTrends($monthlyData);

        // Get available currencies
        $currencies = Currency::where('is_active', true)->pluck('code');

        return view('reports.monthly-trends', compact('monthlyData', 'trends', 'year', 'currency', 'currencies'));
    }

    /**
     * Calculate month-over-month trends
     */
    protected function calculateTrends($data): array
    {
        $trends = [];
        $previousVolume = null;

        foreach ($data as $row) {
            $trend = null;
            if ($previousVolume !== null && $previousVolume > 0) {
                $trend = (($row->total_volume - $previousVolume) / $previousVolume) * 100;
            }
            $trends[$row->month] = [
                'volume' => $row->total_volume,
                'trend' => $trend,
                'direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'neutral'),
            ];
            $previousVolume = $row->total_volume;
        }

        return $trends;
    }

    /**
     * Profitability analysis by currency
     */
    public function profitability(Request $request)
    {
        $this->requireManagerOrAdmin();

        $startDate = $request->input('start_date', now()->subMonth()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->subMonth()->endOfMonth()->toDateString());

        // Get currency positions with profit/loss
        $positions = CurrencyPosition::with('currency')
            ->get()
            ->map(function ($position) use ($startDate, $endDate) {
                $stats = $this->calculateCurrencyProfitability(
                    $position->currency_code,
                    $startDate,
                    $endDate
                );

                return [
                    'currency' => $position->currency,
                    'balance' => $position->balance,
                    'avg_cost_rate' => $position->avg_cost_rate,
                    'current_rate' => $this->getCurrentRate($position->currency_code),
                    'unrealized_pnl' => $stats['unrealized_pnl'],
                    'realized_pnl' => $stats['realized_pnl'],
                    'total_pnl' => $stats['total_pnl'],
                    'buy_volume' => $stats['buy_volume'],
                    'sell_volume' => $stats['sell_volume'],
                ];
            });

        // Calculate totals
        $totals = [
            'total_unrealized' => $positions->sum('unrealized_pnl'),
            'total_realized' => $positions->sum('realized_pnl'),
            'total_pnl' => $positions->sum('total_pnl'),
        ];

        return view('reports.profitability', compact('positions', 'totals', 'startDate', 'endDate'));
    }

    /**
     * Calculate profitability for a currency
     */
    protected function calculateCurrencyProfitability(string $currencyCode, string $startDate, string $endDate): array
    {
        $position = CurrencyPosition::where('currency_code', $currencyCode)->first();

        if (! $position) {
            return [
                'unrealized_pnl' => 0,
                'realized_pnl' => 0,
                'total_pnl' => 0,
                'buy_volume' => 0,
                'sell_volume' => 0,
            ];
        }

        // Current market rate
        $currentRate = $this->getCurrentRate($currencyCode);

        // Unrealized P&L (on current balance)
        $avgCost = (float) $position->avg_cost_rate;
        $balance = (float) $position->balance;
        $unrealizedPnl = ($currentRate - $avgCost) * $balance;

        // Realized P&L (from sell transactions in period)
        $sells = Transaction::where('currency_code', $currencyCode)
            ->where('type', TransactionType::Sell)
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $realizedPnl = 0;
        foreach ($sells as $sell) {
            $sellRate = (float) $sell->rate;
            $sellAmount = (float) $sell->amount_foreign;
            // Gain = (sell rate - avg cost) * amount
            $realizedPnl += ($sellRate - $avgCost) * $sellAmount;
        }

        // Buy volume in period
        $buyVolume = Transaction::where('currency_code', $currencyCode)
            ->where('type', TransactionType::Buy)
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount_local');

        // Sell volume in period
        $sellVolume = Transaction::where('currency_code', $currencyCode)
            ->where('type', TransactionType::Sell)
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount_local');

        return [
            'unrealized_pnl' => $unrealizedPnl,
            'realized_pnl' => $realizedPnl,
            'total_pnl' => $unrealizedPnl + $realizedPnl,
            'buy_volume' => $buyVolume,
            'sell_volume' => $sellVolume,
        ];
    }

    /**
     * Get current exchange rate
     */
    protected function getCurrentRate(string $currencyCode): float
    {
        $rate = ExchangeRate::where('currency_code', $currencyCode)
            ->where('is_active', true)
            ->latest()
            ->first();

        return $rate ? (float) $rate->rate : 0;
    }

    /**
     * Customer transaction analysis
     */
    public function customerAnalysis(Request $request)
    {
        $this->requireManagerOrAdmin();

        $topCustomers = Customer::withCount('transactions')
            ->withSum('transactions', 'amount_local')
            ->orderBy('transactions_count', 'desc')
            ->take(50)
            ->get()
            ->map(function ($customer) {
                return [
                    'customer' => $customer,
                    'transaction_count' => $customer->transactions_count,
                    'total_volume' => $customer->transactions_sum_amount_local,
                    'avg_transaction' => $customer->transactions_count > 0 ? $customer->transactions_sum_amount_local / $customer->transactions_count : 0,
                    'first_transaction' => $customer->transactions()->min('created_at'),
                    'last_transaction' => $customer->transactions()->max('created_at'),
                    'risk_rating' => $customer->risk_rating,
                ];
            });

        // Risk distribution
        $riskDistribution = Customer::select('risk_rating', DB::raw('COUNT(*) as count'))
            ->groupBy('risk_rating')
            ->get();

        return view('reports.customer-analysis', compact('topCustomers', 'riskDistribution'));
    }

    /**
     * Compliance summary report
     */
    public function complianceSummary(Request $request)
    {
        $this->requireManagerOrAdmin();

        $startDate = $request->input('start_date', today()->subMonth()->toDateString());
        $endDate = $request->input('end_date', today()->toDateString());

        // Flagged transactions
        $flaggedStats = FlaggedTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->select('flag_type', DB::raw('COUNT(*) as count'))
            ->groupBy('flag_type')
            ->get();

        // Large transactions (≥RM 50,000)
        $largeTransactions = Transaction::where('amount_local', '>=', 50000)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // EDD required count
        $eddCount = Transaction::where('cdd_level', CddLevel::Enhanced)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Suspicious activity
        $suspiciousCount = FlaggedTransaction::whereIn('flag_type', [ComplianceFlagType::Structuring, ComplianceFlagType::SanctionMatch])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return view('reports.compliance-summary', compact(
            'flaggedStats',
            'largeTransactions',
            'eddCount',
            'suspiciousCount',
            'startDate',
            'endDate'
        ));
    }

    /**
     * BNM Form LMCA - Monthly regulatory report
     */
    public function lmca(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');

        $reportGenerated = ReportGenerated::where('report_type', 'LMCA')
            ->where('period_start', now()->parse($month)->startOfMonth())
            ->first();

        $reportData = $this->reportingService->generateFormLMCA($month);

        return view('reports.lmca', compact('month', 'reportData', 'reportGenerated'));
    }

    /**
     * Generate BNM Form LMCA CSV
     */
    public function lmcaGenerate(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $month = $validated['month'];
        $filepath = $this->reportingService->generateFormLMCACsv($month);

        ReportGenerated::create([
            'report_type' => 'LMCA',
            'period_start' => now()->parse($month)->startOfMonth(),
            'period_end' => now()->parse($month)->endOfMonth(),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
        ]);

        return response()->json([
            'message' => 'Form LMCA generated successfully',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/'.basename($filepath)),
        ]);
    }

    /**
     * Update LMCA report status (mark as submitted)
     */
    public function updateLMCAStatus(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'status' => 'required|in:Submitted',
        ]);

        $report = ReportGenerated::where('report_type', 'LMCA')
            ->where('period_start', now()->parse($validated['month'])->startOfMonth())
            ->first();

        if (! $report) {
            return response()->json([
                'message' => 'Report not found. Generate the report first.',
            ], 404);
        }

        $report->update([
            'status' => $validated['status'],
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Report status updated successfully',
            'status' => $report->status,
        ]);
    }

    /**
     * Quarterly Large Value Report
     */
    public function quarterlyLvr(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'quarter' => 'nullable|date_format:Y-q',
        ]);

        $quarter = $validated['quarter'] ?? now()->format('Y').'-Q'.ceil(now()->format('n') / 3);

        $reportGenerated = ReportGenerated::where('report_type', 'QLVR')
            ->where('period_start', $this->getQuarterStart($quarter))
            ->first();

        $reportData = $this->reportingService->generateQuarterlyLargeValueReport($quarter);

        return view('reports.quarterly-lvr', compact('quarter', 'reportData', 'reportGenerated'));
    }

    /**
     * Generate Quarterly Large Value Report CSV
     */
    public function quarterlyLvrGenerate(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'quarter' => 'required|date_format:Y-q',
        ]);

        $quarter = $validated['quarter'];
        $filepath = $this->reportingService->generateQuarterlyLargeValueCsv($quarter);

        ReportGenerated::create([
            'report_type' => 'QLVR',
            'period_start' => $this->getQuarterStart($quarter),
            'period_end' => $this->getQuarterEnd($quarter),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
        ]);

        return response()->json([
            'message' => 'Quarterly Large Value Report generated successfully',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/'.basename($filepath)),
        ]);
    }

    /**
     * Position Limit Report
     */
    public function positionLimit(Request $request)
    {
        $this->requireManagerOrAdmin();

        $reportGenerated = ReportGenerated::where('report_type', 'PLR')
            ->whereDate('period_start', now()->toDateString())
            ->first();

        $reportData = $this->reportingService->generatePositionLimitReport();

        return view('reports.position-limit', compact('reportData', 'reportGenerated'));
    }

    /**
     * Generate Position Limit Report CSV
     */
    public function positionLimitGenerate(Request $request)
    {
        $this->requireManagerOrAdmin();

        $filepath = $this->reportingService->generatePositionLimitCsv();

        ReportGenerated::create([
            'report_type' => 'PLR',
            'period_start' => now()->startOfDay(),
            'period_end' => now()->endOfDay(),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
        ]);

        return response()->json([
            'message' => 'Position Limit Report generated successfully',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/'.basename($filepath)),
        ]);
    }

    protected function getQuarterStart(string $quarter): Carbon
    {
        $parts = explode('-', $quarter);
        $year = (int) $parts[0];
        $q = (int) substr($parts[1], 1);
        $startMonth = (($q - 1) * 3) + 1;

        return Carbon::create($year, $startMonth, 1)->startOfMonth();
    }

    protected function getQuarterEnd(string $quarter): Carbon
    {
        return $this->getQuarterStart($quarter)->copy()->addMonths(3)->subDay()->endOfDay();
    }

    /**
     * Report history with version tracking
     */
    public function history(Request $request)
    {
        $this->requireManagerOrAdmin();

        $reportType = $request->input('type');
        $periodStart = $request->input('period');

        $query = ReportGenerated::with(['generatedBy', 'submittedBy'])
            ->orderBy('generated_at', 'desc');

        if ($reportType) {
            $query->where('report_type', $reportType);
        }

        if ($periodStart) {
            $query->where('period_start', $periodStart);
        }

        $reports = $query->paginate(20);

        $reportTypes = ReportGenerated::select('report_type')->distinct()->pluck('report_type');

        return view('reports.history', compact('reports', 'reportTypes', 'reportType', 'periodStart'));
    }

    /**
     * Compare two report versions
     */
    public function compare(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'report_type' => 'required|string',
            'period_start' => 'required|date',
            'version1' => 'required|integer',
            'version2' => 'required|integer',
        ]);

        $report1 = ReportGenerated::where('report_type', $validated['report_type'])
            ->where('period_start', $validated['period_start'])
            ->where('version', $validated['version1'])
            ->first();

        $report2 = ReportGenerated::where('report_type', $validated['report_type'])
            ->where('period_start', $validated['period_start'])
            ->where('version', $validated['version2'])
            ->first();

        if (! $report1 || ! $report2) {
            return back()->with('error', 'Report version not found');
        }

        return view('reports.compare', compact('report1', 'report2'));
    }
}
