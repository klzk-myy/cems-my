@extends('layouts.app')

@section('title', 'CEMS-MY Dashboard')

@section('styles')
<style>
    .dashboard-header {
        margin-bottom: 1.5rem;
    }
    .dashboard-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .dashboard-header p {
        color: #718096;
    }

    .rate-display {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .rate-display:last-child { border-bottom: none; }
    .currency { font-weight: 600; color: #2d3748; }
    .rates { display: flex; gap: 1rem; }
    .rate-buy { color: #38a169; font-weight: 600; }
    .rate-sell { color: #e53e3e; font-weight: 600; }

    .stat-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .stat-label { color: #718096; }
    .stat-value {
        font-weight: 600;
        color: #2d3748;
    }

    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .quick-actions .btn {
        text-align: center;
        padding: 0.75rem;
    }
</style>
@endsection

@section('content')
<div class="dashboard-header">
    <h2>Welcome to CEMS-MY</h2>
    <p>Bank Negara Malaysia Compliant Currency Exchange Management System</p>
</div>

<div class="grid">
    <!-- Live Exchange Rates -->
    <div class="card">
        <h2>Live Exchange Rates (MYR)</h2>
        <div id="rates-container">
            <div class="rate-display">
                <span class="currency">USD</span>
                <div class="rates">
                    <span class="rate-buy">Buy: 4.7200</span>
                    <span class="rate-sell">Sell: 4.7500</span>
                </div>
            </div>
            <div class="rate-display">
                <span class="currency">EUR</span>
                <div class="rates">
                    <span class="rate-buy">Buy: 5.1100</span>
                    <span class="rate-sell">Sell: 5.1400</span>
                </div>
            </div>
            <div class="rate-display">
                <span class="currency">GBP</span>
                <div class="rates">
                    <span class="rate-buy">Buy: 5.9200</span>
                    <span class="rate-sell">Sell: 5.9500</span>
                </div>
            </div>
            <div class="rate-display">
                <span class="currency">SGD</span>
                <div class="rates">
                    <span class="rate-buy">Buy: 3.5200</span>
                    <span class="rate-sell">Sell: 3.5400</span>
                </div>
            </div>
        </div>
        <p style="margin-top: 1rem; font-size: 0.75rem; color: #718096;">Last updated: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <!-- Quick Stats -->
    <div class="card">
        <h2>Today's Summary</h2>
        <div class="stat-row">
            <span class="stat-label">Total Transactions</span>
            <span class="stat-value">{{ $stats['total_transactions'] ?? 0 }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Buy Volume (MYR)</span>
            <span class="stat-value">{{ number_format($stats['buy_volume'] ?? 0, 2) }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Sell Volume (MYR)</span>
            <span class="stat-value">{{ number_format($stats['sell_volume'] ?? 0, 2) }}</span>
        </div>
@php
    $flaggedColor = ($stats['flagged'] ?? 0) > 0 ? '#e53e3e' : '#38a169';
@endphp
<style>
    .stat-flagged { color: {{ $flaggedColor }}; }
</style>
<div class="stat-row">
    <span class="stat-label">Flagged Transactions</span>
                    @php
                        $flaggedCount = $stats['flagged'] ?? 0;
                        $flaggedColor = $flaggedCount > 0 ? '#e53e3e' : '#38a169';
                    @endphp
                    <span class="stat-value" style="color: {{ $flaggedColor }}">
                        {{ $flaggedCount }}
                    </span>
</div>
        <div class="stat-row">
            <span class="stat-label">Active Customers</span>
            <span class="stat-value">{{ $stats['active_customers'] ?? 0 }}</span>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <h2>Quick Actions</h2>
<div class="quick-actions">
<a href="{{ route('transactions.create') }}" class="btn btn-success">New Transaction</a>
<a href="{{ route('customers.create') }}" class="btn btn-primary">Register Customer</a>
<a href="{{ route('compliance.flagged') }}" class="btn btn-warning">View Flagged ({{ $stats['flagged'] ?? 0 }})</a>
</div>
    </div>

    <!-- System Status -->
    <div class="card">
        <h2>System Status</h2>
        <div class="stat-row">
            <span class="stat-label">Database</span>
            <span class="status-badge status-active">Connected</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Redis Cache</span>
            <span class="status-badge status-active">Active</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Rate API</span>
            <span class="status-badge status-active">Online</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Encryption</span>
            <span class="status-badge status-active">AES-256</span>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <h2>Recent Transactions</h2>
    <table>
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
                <td>{{ $tx->type }}</td>
                <td>{{ $tx->currency_code }}</td>
                <td>{{ number_format($tx->amount_local, 2) }} MYR</td>
                <td>{{ $tx->rate }}</td>
                <td>
                    <span class="status-badge status-{{ $tx->status->isCompleted() ? 'active' : ($tx->status->isPending() ? 'pending' : 'flagged') }}">
                        {{ $tx->status->label() }}
                    </span>
                </td>
                <td style="color: #718096; font-size: 0.875rem;">{{ $tx->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="padding: 2rem; text-align: center; color: #718096;">No transactions yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
