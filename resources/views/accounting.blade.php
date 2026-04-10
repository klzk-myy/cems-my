@extends('layouts.app')

@section('title', 'Accounting - CEMS-MY')

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

<!-- Quick Links -->
<div class="card">
    <h2>Accounting Menu</h2>
    <div class="quick-links">
        <a href="{{ route('accounting.journal') }}" class="quick-link">
            <span class="quick-link-icon">📝</span>
            <span>Journal Entries</span>
        </a>
        <a href="{{ route('accounting.journal.create') }}" class="quick-link">
            <span class="quick-link-icon">➕</span>
            <span>New Journal Entry</span>
        </a>
        <a href="{{ route('accounting.ledger') }}" class="quick-link">
            <span class="quick-link-icon">📒</span>
            <span>Ledger</span>
        </a>
        <a href="{{ route('accounting.trial-balance') }}" class="quick-link">
            <span class="quick-link-icon">⚖️</span>
            <span>Trial Balance</span>
        </a>
        <a href="{{ route('accounting.profit-loss') }}" class="quick-link">
            <span class="quick-link-icon">📊</span>
            <span>Profit & Loss</span>
        </a>
        <a href="{{ route('accounting.balance-sheet') }}" class="quick-link">
            <span class="quick-link-icon">📈</span>
            <span>Balance Sheet</span>
        </a>
        <a href="{{ route('accounting.periods') }}" class="quick-link">
            <span class="quick-link-icon">📅</span>
            <span>Periods</span>
        </a>
        <a href="{{ route('accounting.revaluation') }}" class="quick-link">
            <span class="quick-link-icon">💱</span>
            <span>Revaluation</span>
        </a>
        <a href="{{ route('accounting.reconciliation') }}" class="quick-link">
            <span class="quick-link-icon">🏦</span>
            <span>Reconciliation</span>
        </a>
        <a href="{{ route('accounting.budget') }}" class="quick-link">
            <span class="quick-link-icon">💰</span>
            <span>Budget</span>
        </a>
        <a href="{{ route('accounting.journal.workflow') }}" class="quick-link">
            <span class="quick-link-icon">✅</span>
            <span>Entry Workflow</span>
        </a>
        <a href="{{ route('accounting.cash-flow') }}" class="quick-link">
            <span class="quick-link-icon">💵</span>
            <span>Cash Flow</span>
        </a>
        <a href="{{ route('accounting.ratios') }}" class="quick-link">
            <span class="quick-link-icon">📐</span>
            <span>Financial Ratios</span>
        </a>
        <a href="{{ route('accounting.fiscal-years') }}" class="quick-link">
            <span class="quick-link-icon">📆</span>
            <span>Fiscal Years</span>
        </a>
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

<!-- Monthly Revaluation Info -->
<div class="card">
    <h2>Monthly Revaluation</h2>
    <div class="alert alert-info">
        <strong>Automatic Revaluation:</strong> Runs on the last day of each month at 23:59<br>
        <strong>Formula:</strong> (New Rate - Avg Cost Rate) × Position Amount<br>
        <strong>Next Run:</strong> {{ now()->endOfMonth()->format('Y-m-d 23:59') }}
    </div>
    <div class="mt-4">
        <a href="{{ route('accounting.revaluation.run') }}" class="btn btn-primary">Run Manual Revaluation</a>
        <a href="{{ route('accounting.revaluation.history') }}" class="btn btn-success">View Revaluation History</a>
    </div>
</div>
@endsection
