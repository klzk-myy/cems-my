<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportingService
{
    protected EncryptionService $encryptionService;
    protected MathService $mathService;

    public function __construct(
        EncryptionService $encryptionService,
        MathService $mathService
    ) {
        $this->encryptionService = $encryptionService;
        $this->mathService = $mathService;
    }

    public function generateLCTR(string $month): string
    {
        $startDate = now()->parse($month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $transactions = Transaction::where('amount_local', '>=', 25000)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'user'])
            ->get();

        $filename = "LCTR_{$month}.csv";
        $filepath = "reports/{$filename}";

        // Ensure the reports directory exists
        if (!Storage::exists('reports')) {
            Storage::makeDirectory('reports');
        }

        $csv = fopen(Storage::path($filepath), 'w');

        // Headers per BNM format
        fputcsv($csv, [
            'Transaction_ID',
            'Date',
            'Time',
            'Customer_ID',
            'Customer_Name',
            'ID_Type',
            'Amount_Local',
            'Amount_Foreign',
            'Currency',
            'Transaction_Type',
            'Branch_ID',
            'Teller_ID',
        ]);

        foreach ($transactions as $txn) {
            fputcsv($csv, [
                $txn->id,
                $txn->created_at->format('Y-m-d'),
                $txn->created_at->format('H:i:s'),
                $txn->customer_id,
                $this->maskName($txn->customer->full_name),
                $txn->customer->id_type,
                $txn->amount_local,
                $txn->amount_foreign,
                $txn->currency_code,
                $txn->type,
                'MAIN', // TODO: Use actual branch
                $txn->user_id,
            ]);
        }

        fclose($csv);

        return $filepath;
    }

    public function generateMSB2(string $date): string
    {
        $queryDate = now()->parse($date);

        $summary = DB::table('transactions')
            ->select(
                'currency_code',
                DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_foreign ELSE 0 END) as buy_volume"),
                DB::raw("SUM(CASE WHEN type = 'Buy' THEN 1 ELSE 0 END) as buy_count"),
                DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_foreign ELSE 0 END) as sell_volume"),
                DB::raw("SUM(CASE WHEN type = 'Sell' THEN 1 ELSE 0 END) as sell_count")
            )
            ->whereDate('created_at', $queryDate)
            ->groupBy('currency_code')
            ->get();

        $filename = "MSB2_{$date}.csv";
        $filepath = "reports/{$filename}";

        // Ensure the reports directory exists
        if (!Storage::exists('reports')) {
            Storage::makeDirectory('reports');
        }

        $csv = fopen(Storage::path($filepath), 'w');

        fputcsv($csv, [
            'Date',
            'Currency',
            'Buy_Volume',
            'Buy_Count',
            'Sell_Volume',
            'Sell_Count',
        ]);

        foreach ($summary as $row) {
            fputcsv($csv, [
                $date,
                $row->currency_code,
                $row->buy_volume,
                $row->buy_count,
                $row->sell_volume,
                $row->sell_count,
            ]);
        }

        fclose($csv);

        return $filepath;
    }

    protected function maskName(string $name): string
    {
        $parts = explode(' ', $name);
        $masked = [];

        foreach ($parts as $part) {
            if (strlen($part) > 2) {
                $masked[] = substr($part, 0, 2) . str_repeat('*', strlen($part) - 2);
            } else {
                $masked[] = $part;
            }
        }

        return implode(' ', $masked);
    }

    public function generateLCTRData(string $month): array
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $transactions = Transaction::with(['customer', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('amount_local', '>=', 25000)
            ->where('status', 'Completed')
            ->orderBy('created_at')
            ->get();

        $rows = [];
        foreach ($transactions as $txn) {
            $idNumber = $txn->customer->id_number_encrypted 
                ? $this->encryptionService->decrypt($txn->customer->id_number_encrypted)
                : 'N/A';
            
            $rows[] = [
                'Transaction_ID' => 'TXN-' . str_pad($txn->id, 8, '0', STR_PAD_LEFT),
                'Transaction_Date' => $txn->created_at->format('Y-m-d'),
                'Transaction_Time' => $txn->created_at->format('H:i:s'),
                'Customer_ID_Type' => $txn->customer->id_type,
                'Customer_ID_Number' => $idNumber,
                'Customer_Name' => $txn->customer->full_name,
                'Customer_Nationality' => $txn->customer->nationality,
                'Transaction_Type' => $txn->type,
                'Currency_Code' => $txn->currency_code,
                'Amount_Local' => $txn->amount_local,
                'Amount_Foreign' => $txn->amount_foreign,
                'Exchange_Rate' => $txn->rate,
                'Till_ID' => $txn->till_id ?? 'MAIN',
                'Teller_ID' => 'USR-' . str_pad($txn->user_id, 6, '0', STR_PAD_LEFT),
                'Purpose' => $txn->purpose,
                'Source_of_Funds' => $txn->source_of_funds,
                'CDD_Level' => $txn->cdd_level,
                'Status' => $txn->status,
            ];
        }

        return [
            'month' => $month,
            'generated_at' => now()->toIso8601String(),
            'total_transactions' => count($rows),
            'total_amount' => $transactions->sum('amount_local'),
            'data' => $rows,
        ];
    }

    public function generateMSB2Data(string $date): array
    {
        $transactions = Transaction::whereDate('created_at', $date)
            ->where('status', 'Completed')
            ->get();

        $currencies = Currency::where('is_active', true)->get();
        $rows = [];

        foreach ($currencies as $currency) {
            $buyTxns = $transactions->where('currency_code', $currency->code)->where('type', 'Buy');
            $sellTxns = $transactions->where('currency_code', $currency->code)->where('type', 'Sell');
            $position = CurrencyPosition::where('currency_code', $currency->code)->first();

            $rows[] = [
                'Date' => $date,
                'Currency' => $currency->code,
                'Buy_Volume_MYR' => (float) $buyTxns->sum('amount_local'),
                'Buy_Count' => $buyTxns->count(),
                'Sell_Volume_MYR' => (float) $sellTxns->sum('amount_local'),
                'Sell_Count' => $sellTxns->count(),
                'Avg_Buy_Rate' => $buyTxns->avg('rate') ?? 0,
                'Avg_Sell_Rate' => $sellTxns->avg('rate') ?? 0,
                'Opening_Position' => $position ? (float) $position->balance : 0,
                'Closing_Position' => $position ? (float) $position->balance : 0,
            ];
        }

        return [
            'date' => $date,
            'generated_at' => now()->toIso8601String(),
            'data' => $rows,
        ];
    }

    public function generateCurrencyPositionReport(): array
    {
        $positions = CurrencyPosition::with('currency')->get();
        
        $data = [];
        $totalUnrealizedPnl = '0';

        foreach ($positions as $position) {
            $data[] = [
                'currency_code' => $position->currency_code,
                'currency_name' => $position->currency->name ?? $position->currency_code,
                'balance' => $position->balance,
                'avg_cost_rate' => $position->avg_cost_rate,
                'last_valuation_rate' => $position->last_valuation_rate,
                'unrealized_pnl' => $position->unrealized_pnl,
            ];
            $totalUnrealizedPnl = $this->mathService->add($totalUnrealizedPnl, $position->unrealized_pnl ?? '0');
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'positions' => $data,
            'total_unrealized_pnl' => $totalUnrealizedPnl,
        ];
    }

    public function generateUnrealizedPnLReport(): array
    {
        $positions = CurrencyPosition::with('currency')
            ->whereRaw('unrealized_pnl != 0')
            ->get();

        $data = [];
        $totalGain = '0';
        $totalLoss = '0';

        foreach ($positions as $position) {
            $pnl = $position->unrealized_pnl ?? '0';
            
            if ($this->mathService->compare($pnl, '0') >= 0) {
                $totalGain = $this->mathService->add($totalGain, $pnl);
            } else {
                $totalLoss = $this->mathService->add($totalLoss, $pnl);
            }

            $data[] = [
                'currency_code' => $position->currency_code,
                'currency_name' => $position->currency->name ?? $position->currency_code,
                'balance' => $position->balance,
                'avg_cost_rate' => $position->avg_cost_rate,
                'last_valuation_rate' => $position->last_valuation_rate,
                'unrealized_pnl' => $pnl,
                'is_gain' => $this->mathService->compare($pnl, '0') >= 0,
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'positions' => $data,
            'total_gain' => $totalGain,
            'total_loss' => $totalLoss,
            'net_pnl' => $this->mathService->add($totalGain, $totalLoss),
        ];
    }
}
