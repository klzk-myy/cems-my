<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\MathService;

class TransactionReportController extends Controller
{
    public function __construct(
        protected MathService $mathService
    ) {}

    /**
     * Display customer's transaction history
     */
    public function customerTransactions(Customer $customer)
    {
        $this->authorizeCustomerHistory($customer);

        $transactions = Transaction::where('customer_id', $customer->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('transactions.customer', compact('transactions', 'customer'));
    }

    /**
     * Display comprehensive customer transaction history
     */
    public function customerHistory(Customer $customer)
    {
        $this->authorizeCustomerHistory($customer);

        $allTransactions = Transaction::where('customer_id', $customer->id)->get();

        $stats = [
            'total_count' => $allTransactions->count(),
            'buy_volume' => $allTransactions->where('type', TransactionType::Buy)->sum('amount_local'),
            'sell_volume' => $allTransactions->where('type', TransactionType::Sell)->sum('amount_local'),
            'total_volume' => $allTransactions->sum('amount_local'),
            'avg_transaction' => $allTransactions->count() > 0
                ? $this->mathService->divide(
                    (string) $allTransactions->sum('amount_local'),
                    (string) $allTransactions->count()
                )
                : '0',
            'first_transaction' => $allTransactions->min('created_at'),
            'last_transaction' => $allTransactions->max('created_at'),
        ];

        $transactions = Transaction::where('customer_id', $customer->id)
            ->with(['user', 'currency'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyData = Transaction::where('customer_id', $customer->id)
            ->selectRaw("{$dateFormat} as month, type, SUM(amount_local) as total")
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        $chartLabels = [];
        $chartBuyData = [];
        $chartSellData = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthLabel = now()->subMonths($i)->format('M Y');
            $chartLabels[] = $monthLabel;

            $monthData = $monthlyData->get($monthKey, collect());
            $chartBuyData[] = (float) ($monthData->where('type', TransactionType::Buy)->first()?->total ?? 0);
            $chartSellData[] = (float) ($monthData->where('type', TransactionType::Sell)->first()?->total ?? 0);
        }

        return view('customers.history', compact(
            'customer', 'transactions', 'stats', 'chartLabels', 'chartBuyData', 'chartSellData'
        ));
    }

    /**
     * Export customer transaction history to CSV
     */
    public function exportCustomerHistory(Customer $customer)
    {
        $transactions = Transaction::where('customer_id', $customer->id)
            ->with(['user', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer_'.$customer->id.'_history.csv"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Type', 'Currency', 'Amount Foreign', 'Amount Local', 'Rate', 'User', 'Status']);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->type->value,
                    $transaction->currency_code,
                    $transaction->amount_foreign,
                    $transaction->amount_local,
                    $transaction->rate,
                    $transaction->user->username ?? 'N/A',
                    $transaction->status->value,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Authorize customer history access
     *
     * Only managers, compliance officers, or the customer themselves can view history.
     */
    protected function authorizeCustomerHistory(Customer $customer): void
    {
        $user = auth()->user();

        // Managers and compliance officers can view any customer history
        if ($user->isManager() || $user->isComplianceOfficer() || $user->isAdmin()) {
            return;
        }

        // Customers can only view their own history
        if ($user->customer_id === $customer->id) {
            return;
        }

        abort(403, 'Unauthorized to view this customer\'s history.');
    }
}
