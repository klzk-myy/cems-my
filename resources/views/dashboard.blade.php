@extends('layouts.app')

@section('title', 'CEMS-MY Dashboard')

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

<div class="grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Quick Actions -->
    <div class="card card--featured">
        <h2 style="font-family: var(--font-heading); font-size: 1.125rem; margin-bottom: 1rem; color: var(--color-gray-800);">Quick Actions</h2>
        <div class="quick-actions" style="display: flex; flex-direction: column; gap: 0.75rem;">
            <a href="{{ route('transactions.create') }}" class="btn btn--success btn--full">+ New Transaction</a>
            <a href="{{ route('customers.create') }}" class="btn btn--primary btn--full">Register Customer</a>
            <a href="{{ route('compliance.flagged') }}" class="btn btn--warning btn--full">View Flagged ({{ $stats['flagged'] ?? 0 }})</a>
        </div>
    </div>

    <!-- System Status -->
    <div class="card">
        <h2 style="font-family: var(--font-heading); font-size: 1.125rem; margin-bottom: 1rem; color: var(--color-gray-800);">System Status</h2>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--color-gray-100);">
                <span style="color: var(--color-gray-600);">Database</span>
                <span class="status-badge status-badge--active">Connected</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--color-gray-100);">
                <span style="color: var(--color-gray-600);">Redis Cache</span>
                <span class="status-badge status-badge--active">Active</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--color-gray-100);">
                <span style="color: var(--color-gray-600);">Rate API</span>
                <span class="status-badge status-badge--active">Online</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                <span style="color: var(--color-gray-600);">Encryption</span>
                <span class="status-badge status-badge--active">AES-256</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <h2 style="font-family: var(--font-heading); font-size: 1.125rem; margin-bottom: 1rem; color: var(--color-gray-800);">Recent Transactions</h2>
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
                <td>#{{ $tx->id }}</td>
                <td>{{ $tx->customer->full_name ?? 'N/A' }}</td>
                <td>{{ $tx->type->label() }}</td>
                <td>{{ $tx->currency_code }}</td>
                <td>{{ number_format($tx->amount_local, 2) }} MYR</td>
                <td>{{ $tx->rate }}</td>
                <td>
                    @php
                        $badgeClass = match($tx->status->value) {
                            'Completed' => 'status-badge--completed',
                            'Pending' => 'status-badge--pending',
                            default => 'status-badge--pending'
                        };
                    @endphp
                    <span class="status-badge {{ $badgeClass }}">{{ $tx->status->label() }}</span>
                </td>
                <td style="font-size: 0.75rem; color: var(--color-gray-500);">{{ $tx->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 2rem; color: var(--color-gray-500);">No transactions yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
