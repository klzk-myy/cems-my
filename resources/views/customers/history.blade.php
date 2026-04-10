@extends('layouts.app')

@section('title', 'Customer History - ' . $customer->full_name)

@section('content')
<div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-8 text-white mb-6">
    <h2 class="text-2xl font-bold mb-2">{{ $customer->full_name }}</h2>
    <p class="opacity-90 mb-1"><strong>ID:</strong> {{ $customer->id_number_encrypted }}</p>
    <p class="opacity-90 mb-1"><strong>Nationality:</strong> {{ $customer->nationality }}</p>
    <p class="opacity-90 mb-1"><strong>Phone:</strong> {{ $customer->phone }}</p>
    <p class="opacity-90 mb-1"><strong>Email:</strong> {{ $customer->email ?? 'N/A' }}</p>
    <p class="opacity-90"><strong>Risk Rating:</strong> {{ $customer->risk_rating ?? 'Not Rated' }}</p>
</div>

<h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Statistics</h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg p-6 text-center border-l-4 border-purple-500 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_count']) }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Transactions</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center border-l-4 border-green-500 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['buy_volume'], 2) }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Buy Volume (MYR)</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center border-l-4 border-red-500 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['sell_volume'], 2) }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Sell Volume (MYR)</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center border-l-4 border-purple-500 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['avg_transaction'], 2) }}</div>
        <div class="text-sm text-gray-500 mt-1">Avg Transaction Size</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center border-l-4 border-purple-500 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ $stats['first_transaction'] ? $stats['first_transaction']->format('M d, Y') : 'N/A' }}</div>
        <div class="text-sm text-gray-500 mt-1">First Transaction</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center border-l-4 border-purple-500 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ $stats['last_transaction'] ? $stats['last_transaction']->format('M d, Y') : 'N/A' }}</div>
        <div class="text-sm text-gray-500 mt-1">Last Transaction</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Monthly Volume Trend</h3>
        <a href="{{ route('customers.export', $customer) }}" class="px-4 py-2 bg-green-600 text-white no-underline rounded font-semibold text-sm hover:bg-green-700 transition-colors">
            Export to CSV
        </a>
    </div>
    <div class="relative h-80 mb-6">
        <canvas id="volumeChart"></canvas>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction History</h3>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Date</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Type</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Currency</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Amount (Foreign)</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Amount (MYR)</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Rate</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">User</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold {{ $transaction->type === 'Buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $transaction->type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->currency_code }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm text-right">{{ number_format($transaction->amount_foreign, 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm text-right">{{ number_format($transaction->amount_local, 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm text-right">{{ number_format($transaction->rate, 6) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->user->username ?? 'N/A' }}</td>
                    <td class="px-4 py-3 border-b border-gray-100">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                            {{ $transaction->status->value }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center p-8 text-gray-500">
                        No transactions found for this customer.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var chartLabels = {!! json_encode($chartLabels) !!};
    var chartBuyData = {!! json_encode($chartBuyData) !!};
    var chartSellData = {!! json_encode($chartSellData) !!};

    var ctx = document.getElementById('volumeChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Buy Volume',
                    data: chartBuyData,
                    backgroundColor: '#38a169',
                    borderColor: '#276749',
                    borderWidth: 1
                },
                {
                    label: 'Sell Volume',
                    data: chartSellData,
                    backgroundColor: '#e53e3e',
                    borderColor: '#c53030',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'MYR ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': MYR ' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endsection
