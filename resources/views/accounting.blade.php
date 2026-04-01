@extends('layouts.app')

@section('title', 'Accounting - CEMS-MY')

@section('styles')
<style>
    .accounting-header {
        margin-bottom: 1.5rem;
    }
    .accounting-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .accounting-header p {
        color: #718096;
    }

    .summary-box {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
    }
    .summary-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1a365d;
    }
    .summary-label {
        color: #718096;
        margin-top: 0.5rem;
    }
    .pnl-positive { color: #48bb78; }
    .pnl-negative { color: #fc8181; }
</style>
@endsection

@section('content')
<div class="accounting-header">
    <h2>Currency Positions & Accounting</h2>
    <p>Real-time position tracking with average cost calculation</p>
</div>

<!-- Total Unrealized P&L -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
    <div class="summary-box">
        @php
            $pnlClass = ($totalPnl ?? 0) >= 0 ? 'pnl-positive' : 'pnl-negative';
        @endphp
        <div class="summary-value {{ $pnlClass }}">
            RM {{ number_format($totalPnl ?? 0, 2) }}
        </div>
        <div class="summary-label">Total Unrealized P&L</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">{{ $positions->count() }}</div>
        <div class="summary-label">Active Currencies</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">{{ now()->format('M Y') }}</div>
        <div class="summary-label">Current Month</div>
    </div>
</div>

<!-- Currency Positions -->
<div class="card">
    <h2>Currency Positions</h2>

    @if($positions->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Currency</th>
                <th>Till</th>
                <th>Balance</th>
                <th>Avg Cost Rate</th>
                <th>Last Valuation</th>
                <th>Unrealized P&L (MYR)</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach($positions as $position)
            <tr>
                <td><strong>{{ $position->currency_code }}</strong></td>
                <td>{{ $position->till_id }}</td>
                <td>{{ number_format($position->balance, 4) }}</td>
                <td>{{ number_format($position->avg_cost_rate, 6) }}</td>
                <td>{{ $position->last_valuation_rate ? number_format($position->last_valuation_rate, 6) : 'N/A' }}</td>
                <td class="{{ $position->unrealized_pnl >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ $position->unrealized_pnl >= 0 ? '+' : '' }}{{ number_format($position->unrealized_pnl, 2) }}
                </td>
                <td>{{ $position->updated_at ? $position->updated_at->diffForHumans() : 'Never' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="alert alert-info">
        No currency positions recorded yet. Positions will be created automatically when transactions are processed.
    </div>
    @endif
</div>

<!-- Accounting Actions -->
<div class="card">
    <h2>Monthly Revaluation</h2>
    <div class="alert alert-info">
        <strong>Automatic Revaluation:</strong> Runs on the last day of each month at 23:59<br>
        <strong>Formula:</strong> (New Rate - Avg Cost Rate) × Position Amount<br>
        <strong>Next Run:</strong> {{ now()->endOfMonth()->format('Y-m-d 23:59') }}
    </div>
    <div style="margin-top: 1rem;">
        <a href="#" class="btn btn-primary">Run Manual Revaluation</a>
        <a href="#" class="btn btn-success">View Revaluation History</a>
    </div>
</div>

<!-- Chart of Accounts -->
<div class="card">
    <h2>Chart of Accounts (Summary)</h2>
    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1000</td>
                <td>Cash - MYR</td>
                <td>Asset</td>
                <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <tr>
                <td>1100</td>
                <td>Cash - USD</td>
                <td>Asset</td>
                <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <tr>
                <td>1200</td>
                <td>Cash - EUR</td>
                <td>Asset</td>
                <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <tr>
                <td>4000</td>
                <td>Revenue - Forex</td>
                <td>Revenue</td>
                <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <tr>
                <td>5000</td>
                <td>Expense - Revaluation Loss</td>
                <td>Expense</td>
                <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <tr>
                <td>5100</td>
                <td>Revenue - Revaluation Gain</td>
                <td>Revenue</td>
                <td><span class="status-badge status-active">Active</span></td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
