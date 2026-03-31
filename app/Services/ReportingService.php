<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportingService
{
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
}
