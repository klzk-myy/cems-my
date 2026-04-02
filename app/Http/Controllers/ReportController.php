<?php

namespace App\Http\Controllers;

use App\Models\ReportGenerated;
use App\Models\Transaction;
use App\Services\ExportService;
use App\Services\ReportingService;
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

    protected function requireManagerOrAdmin()
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

        // Get qualifying transactions (≥ RM 25,000 and Completed)
        $startDate = now()->parse($month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $transactions = Transaction::where('amount_local', '>=', 25000)
            ->where('status', 'Completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'user'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Count pending transactions that would qualify
        $pendingTransactions = Transaction::where('amount_local', '>=', 25000)
            ->where('status', 'Pending')
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

        ReportGenerated::create([
            'report_type' => 'LCTR',
            'period_start' => now()->parse($month)->startOfMonth(),
            'period_end' => now()->parse($month)->endOfMonth(),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
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
                DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_foreign ELSE 0 END) as buy_volume_foreign"),
                DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_local ELSE 0 END) as buy_amount_myr"),
                DB::raw("COUNT(CASE WHEN type = 'Buy' THEN 1 END) as buy_count"),
                DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_foreign ELSE 0 END) as sell_volume_foreign"),
                DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_local ELSE 0 END) as sell_amount_myr"),
                DB::raw("COUNT(CASE WHEN type = 'Sell' THEN 1 END) as sell_count")
            )
            ->whereDate('created_at', $date)
            ->where('status', 'Completed')
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
}
