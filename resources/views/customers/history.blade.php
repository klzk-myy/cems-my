@extends('layouts.app')

@section('title', 'Customer History - ' . $customer->full_name)

@section('styles')
<style>
    .customer-profile {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        padding: 2rem;
        color: white;
        margin-bottom: 1.5rem;
    }
    .customer-profile h2 {
        margin-bottom: 0.5rem;
        font-size: 1.5rem;
    }
    .customer-profile p {
        opacity: 0.9;
        margin-bottom: 0.25rem;
    }
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .stat-box {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        border-left: 4px solid #667eea;
    }
    .stat-box .value {
        font-size: 1.75rem;
        font-weight: bold;
        color: #2d3748;
    }
    .stat-box .label {
        color: #718096;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .stat-box.buy {
        border-left-color: #38a169;
    }
    .stat-box.sell {
        border-left-color: #e53e3e;
    }
    .chart-container {
        position: relative;
        height: 300px;
        margin: 1.5rem 0;
    }
    .export-btn {
        float: right;
        margin-bottom: 1rem;
    }
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-completed { background: #c6f6d5; color: #276749; }
    .status-pending { background: #fefcbf; color: #744210; }
    .status-onhold { background: #fed7d7; color: #c53030; }
</style>
@endsection

@section('content')
<div class="customer-profile">
    <h2>{{ $customer->full_name }}</h2>
    <p><strong>ID:</strong> {{ $customer->id_number_encrypted }}</p>
    <p><strong>Nationality:</strong> {{ $customer->nationality }}</p>
    <p><strong>Phone:</strong> {{ $customer->phone }}</p>
    <p><strong>Email:</strong> {{ $customer->email ?? 'N/A' }}</p>
    <p><strong>Risk Rating:</strong> {{ $customer->risk_rating ?? 'Not Rated' }}</p>
</div>

<h3>Transaction Statistics</h3>
<div class="stat-grid">
    <div class="stat-box">
        <div class="value">{{ number_format($stats['total_count']) }}</div>
        <div class="label">Total Transactions</div>
    </div>
    <div class="stat-box buy">
        <div class="value">{{ number_format($stats['buy_volume'], 2) }}</div>
        <div class="label">Total Buy Volume (MYR)</div>
    </div>
    <div class="stat-box sell">
        <div class="value">{{ number_format($stats['sell_volume'], 2) }}</div>
        <div class="label">Total Sell Volume (MYR)</div>
    </div>
    <div class="stat-box">
        <div class="value">{{ number_format($stats['avg_transaction'], 2) }}</div>
        <div class="label">Avg Transaction Size</div>
    </div>
    <div class="stat-box">
        <div class="value">{{ $stats['first_transaction'] ? $stats['first_transaction']->format('M d, Y') : 'N/A' }}</div>
        <div class="label">First Transaction</div>
    </div>
    <div class="stat-box">
        <div class="value">{{ $stats['last_transaction'] ? $stats['last_transaction']->format('M d, Y') : 'N/A' }}</div>
        <div class="label">Last Transaction</div>
    </div>
</div>

<div class="card">
    <h3>Monthly Volume Trend
        <a href="{{ route('customers.export', $customer) }}" class="btn btn-success export-btn">
            Export to CSV
        </a>
    </h3>
    <div class="chart-container">
        <canvas id="volumeChart"></canvas>
    </div>
</div>

<div class="card">
    <h3>Transaction History</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Currency</th>
                <th>Amount (Foreign)</th>
                <th>Amount (MYR)</th>
                <th>Rate</th>
                <th>User</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                <td>
                    <span class="status-badge {{ $transaction->type === 'Buy' ? 'status-completed' : 'status-sell' }}">
                        {{ $transaction->type }}
                    </span>
                </td>
                <td>{{ $transaction->currency_code }}</td>
                <td>{{ number_format($transaction->amount_foreign, 4) }}</td>
                <td>{{ number_format($transaction->amount_local, 2) }}</td>
                <td>{{ number_format($transaction->rate, 6) }}</td>
                <td>{{ $transaction->user->username ?? 'N/A' }}</td>
                <td>
                    <span class="status-badge status-{{ strtolower($transaction->status) }}">
                        {{ $transaction->status }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; color: #718096;">
                    No transactions found for this customer.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
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
