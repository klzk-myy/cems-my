@extends('layouts.app')

@section('title', 'Customer Analysis - CEMS-MY')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Customer Analysis</h1>
        <p class="page-header__subtitle">Top customers by transaction volume and activity</p>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $topCustomers->count() }}</div>
        <div class="stat-card__label">Top Customers</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($topCustomers->sum('total_volume'), 0) }}</div>
        <div class="stat-card__label">Total Volume</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ number_format($topCustomers->avg('transaction_count'), 1) }}</div>
        <div class="stat-card__label">Avg Transactions/Customer</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">RM {{ number_format($topCustomers->avg('avg_transaction'), 0) }}</div>
        <div class="stat-card__label">Avg Transaction Size</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Top 50 Customers by Transaction Volume</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Customer</th>
                    <th>Risk</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Total Volume</th>
                    <th class="text-right">Avg Transaction</th>
                    <th>First Transaction</th>
                    <th>Last Transaction</th>
                    <th>Activity</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topCustomers as $index => $customer)
                @php
                    $name = $customer['customer']->full_name ?? 'N/A';
                    $masked = strlen($name) > 4 ? substr($name, 0, 2) . str_repeat('*', strlen($name) - 4) . substr($name, -2) : $name;
                    $daysSince = $customer['last_transaction'] ? now()->diffInDays($customer['last_transaction']) : null;
                    $activityClass = $daysSince === null ? 'gray' : ($daysSince < 30 ? 'green' : ($daysSince < 90 ? 'yellow' : 'red'));
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ $masked }}</span>
                        <br>
                        <small class="text-gray-500">ID: {{ $customer['customer']->id }}</small>
                    </td>
                    <td>
                        <span class="status-badge status-badge--{{ strtolower($customer['risk_rating'] ?? 'Low') }}">
                            {{ $customer['risk_rating'] ?? 'Low' }}
                        </span>
                    </td>
                    <td class="text-right">{{ number_format($customer['transaction_count']) }}</td>
                    <td class="text-right">RM {{ number_format($customer['total_volume'], 2) }}</td>
                    <td class="text-right">RM {{ number_format($customer['avg_transaction'], 2) }}</td>
                    <td>{{ $customer['first_transaction'] ? date('d/m/Y', strtotime($customer['first_transaction'])) : 'N/A' }}</td>
                    <td>{{ $customer['last_transaction'] ? date('d/m/Y', strtotime($customer['last_transaction'])) : 'N/A' }}</td>
                    <td>
                        <span class="{{ $activityClass === 'green' ? 'text-green-600' : ($activityClass === 'yellow' ? 'text-orange-500' : ($activityClass === 'red' ? 'text-red-600' : 'text-gray-500')) }} font-semibold">
                            {{ $daysSince === null ? 'Never' : ($daysSince < 30 ? 'Active' : ($daysSince < 90 ? 'Recent' : 'Inactive')) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-8 text-gray-500">
                        No customer data found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
