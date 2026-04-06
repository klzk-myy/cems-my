@extends('layouts.app')

@section('title', 'Customer Analysis - CEMS-MY')

@section('styles')
<style>
    .analysis-header {
        margin-bottom: 1.5rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1a365d;
    }
    
    .stat-label {
        color: #718096;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
    
    .chart-container {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .customer-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .customer-table th,
    .customer-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .customer-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #4a5568;
    }
    
    .customer-name {
        font-family: monospace;
        background: #edf2f7;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    
    .risk-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .risk-Low { background: #c6f6d5; color: #276749; }
    .risk-Medium { background: #feebc8; color: #c05621; }
    .risk-High { background: #fed7d7; color: #c53030; }
    
    .volume-bar {
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .volume-fill {
        height: 100%;
        background: #3182ce;
        border-radius: 4px;
    }
</style>
@endsection

@section('content')
<div class="analysis-header">
    <h2>Customer Analysis</h2>
    <p>Top customers by transaction volume and activity</p>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value">{{ $topCustomers->count() }}</div>
        <div class="stat-label">Top Customers</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">RM {{ number_format($topCustomers->sum('total_volume'), 0) }}</div>
        <div class="stat-label">Total Volume</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ number_format($topCustomers->avg('transaction_count'), 1) }}</div>
        <div class="stat-label">Avg Transactions/Customer</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">RM {{ number_format($topCustomers->avg('avg_transaction'), 0) }}</div>
        <div class="stat-label">Avg Transaction Size</div>
    </div>
</div>

<!-- Risk Distribution Chart -->
<div class="chart-container">
    <h3>Risk Distribution</h3>
    <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
        @if($riskDistribution->isNotEmpty())
        <table style="width: 100%;">
            <tr>
                @foreach($riskDistribution as $risk)
                <td style="text-align: center; padding: 1rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: 
                        {{ $risk->risk_rating === 'Low' ? '#38a169' : ($risk->risk_rating === 'Medium' ? '#d69e2e' : '#e53e3e') }}">
                        {{ $risk->count }}
                    </div>
                    <div style="margin-top: 0.5rem;">
                        <span class="risk-badge risk-{{ $risk->risk_rating }}">
                            {{ $risk->risk_rating }}
                        </span>
                    </div>
                </td>
                @endforeach
            </tr>
        </table>
        @else
        <p style="color: #718096;">No risk data available</p>
        @endif
    </div>
</div>

<!-- Top Customers Table -->
<div class="card">
    <h3>Top 50 Customers by Transaction Volume</h3>
    
    <table class="customer-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Customer</th>
                <th>Risk</th>
                <th>Transactions</th>
                <th>Total Volume</th>
                <th>Avg Transaction</th>
                <th>First Transaction</th>
                <th>Last Transaction</th>
                <th>Activity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topCustomers as $index => $customer)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    @php
                        $name = $customer['customer']->full_name ?? 'N/A';
                        $masked = strlen($name) > 4 ? substr($name, 0, 2) . str_repeat('*', strlen($name) - 4) . substr($name, -2) : $name;
                    @endphp
                    <span class="customer-name">{{ $masked }}</span>
                    <br>
                    <small>ID: {{ $customer['customer']->id }}</small>
                </td>
                <td>
                    <span class="risk-badge risk-{{ $customer['risk_rating'] ?? 'Low' }}">
                        {{ $customer['risk_rating'] ?? 'Low' }}
                    </span>
                </td>
                <td>{{ number_format($customer['transaction_count']) }}</td>
                <td>RM {{ number_format($customer['total_volume'], 2) }}</td>
                <td>RM {{ number_format($customer['avg_transaction'], 2) }}</td>
                <td>{{ $customer['first_transaction'] ? date('d/m/Y', strtotime($customer['first_transaction'])) : 'N/A' }}</td>
                <td>{{ $customer['last_transaction'] ? date('d/m/Y', strtotime($customer['last_transaction'])) : 'N/A' }}</td>
                <td>
                    @php
                        $daysSince = $customer['last_transaction'] ? now()->diffInDays($customer['last_transaction']) : null;
                        $activityClass = $daysSince === null ? 'gray' : ($daysSince < 30 ? 'green' : ($daysSince < 90 ? 'yellow' : 'red'));
                    @endphp
                    <span style="color: {{ $activityClass === 'green' ? '#38a169' : ($activityClass === 'yellow' ? '#d69e2e' : ($activityClass === 'red' ? '#e53e3e' : '#718096')) }}">
                        {{ $daysSince === null ? 'Never' : ($daysSince < 30 ? 'Active' : ($daysSince < 90 ? 'Recent' : 'Inactive')) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 2rem;">
                    No customer data found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Activity Legend -->
<div class="card" style="margin-top: 1.5rem;">
    <h3>Activity Status Legend</h3>
    <div style="display: flex; gap: 2rem;">
        <div>
            <span style="color: #38a169; font-weight: 600;">● Active</span>
            <p style="color: #718096; font-size: 0.875rem;">Transaction within last 30 days</p>
        </div>
        <div>
            <span style="color: #d69e2e; font-weight: 600;">● Recent</span>
            <p style="color: #718096; font-size: 0.875rem;">Transaction within last 90 days</p>
        </div>
        <div>
            <span style="color: #e53e3e; font-weight: 600;">● Inactive</span>
            <p style="color: #718096; font-size: 0.875rem;">No transaction in 90+ days</p>
        </div>
    </div>
</div>
@endsection
