@extends('layouts.app')

@section('title', 'CEMS-MY Dashboard')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Dashboard</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-header__title">Welcome to CEMS-MY</h1>
    <p class="page-header__subtitle">Bank Negara Malaysia Compliant Currency Exchange Management System</p>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['total_transactions'] ?? 0 }}</div>
        <div class="stat-card__label">Total Transactions</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ number_format($stats['buy_volume'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Buy Volume (MYR)</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ number_format($stats['sell_volume'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Sell Volume (MYR)</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $stats['flagged'] ?? 0 }}</div>
        <div class="stat-card__label">Flagged Transactions</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Quick Actions -->
    <div class="card card--featured">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="flex flex-col gap-3">
            <a href="{{ route('transactions.create') }}" class="btn btn--success btn--lg btn--full">+ New Transaction</a>
            <a href="{{ route('customers.create') }}" class="btn btn--primary btn--lg btn--full">Register Customer</a>
            <a href="{{ route('compliance.flagged') }}" class="btn btn--warning btn--lg btn--full">View Flagged ({{ $stats['flagged'] ?? 0 }})</a>
        </div>
    </div>

    <!-- System Status -->
    <div class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">System Status</h2>
        <div class="flex flex-col gap-2">
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Database</span>
                <span class="status-badge status-badge--active">Connected</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Redis Cache</span>
                <span class="status-badge status-badge--active">Active</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Rate API</span>
                <span class="status-badge status-badge--active">Online</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-600">Encryption</span>
                <span class="status-badge status-badge--active">AES-256</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Transactions</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Currency</th>
                <th>Amount</th>
                <th>Rate</th>
                <th>Status</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recent_transactions ?? [] as $tx)
            <tr>
                <td class="font-mono text-xs">#{{ $tx->id }}</td>
                <td>{{ $tx->customer->full_name ?? 'N/A' }}</td>
                <td>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $tx->type->value === 'Buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $tx->type->label() }}
                    </span>
                </td>
                <td class="font-mono">{{ $tx->currency_code }}</td>
                <td class="font-mono">{{ number_format($tx->amount_local, 2) }} MYR</td>
                <td class="font-mono">{{ $tx->rate }}</td>
                <td>
                    @php
                        $badgeClass = match($tx->status->value) {
                            'Completed' => 'bg-green-100 text-green-800',
                            'Pending' => 'bg-yellow-100 text-yellow-800',
                            'OnHold' => 'bg-orange-100 text-orange-800',
                            'Cancelled' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-600'
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full {{ $badgeClass }}">
                        {{ $tx->status->label() }}
                    </span>
                </td>
                <td class="text-xs text-gray-500">{{ $tx->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-12 text-gray-500">No transactions yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
