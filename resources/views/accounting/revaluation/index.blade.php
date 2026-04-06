@extends('layouts.app')

@section('title', 'Monthly Revaluation - CEMS-MY')

@section('content')
<div class="reval-header">
    <h2>Monthly Revaluation</h2>
    <div class="header-actions">
        <form method="POST" action="{{ route('accounting.revaluation.run') }}" onsubmit="return confirm('Are you sure you want to run revaluation? This will create journal entries.');">
            @csrf
            <button type="submit" class="btn btn-primary">Run Revaluation</button>
        </form>
        <a href="{{ route('accounting.revaluation.history') }}" class="btn btn-secondary">View History</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

<!-- Revaluation Status -->
<div class="card">
    <h2>Current Month Status</h2>
    <div class="status-grid">
        <div class="status-item">
            <span class="status-label">Month</span>
            <span class="status-value">{{ $status['month'] }}</span>
        </div>
        <div class="status-item">
            <span class="status-label">Status</span>
            <span class="status-badge status-{{ $status['has_run'] ? 'completed' : 'pending' }}">
                {{ $status['has_run'] ? 'Completed' : 'Pending' }}
            </span>
        </div>
        @if($status['has_run'])
        <div class="status-item">
            <span class="status-label">Run Date</span>
            <span class="status-value">{{ $status['run_date'] }}</span>
        </div>
        <div class="status-item">
            <span class="status-label">Positions Updated</span>
            <span class="status-value">{{ $status['positions_updated'] }}</span>
        </div>
        <div class="status-item">
            <span class="status-label">Total Gain/Loss</span>
            <span class="status-value {{ ($status['total_gain_loss'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                RM {{ number_format($status['total_gain_loss'] ?? 0, 2) }}
            </span>
        </div>
        @endif
    </div>
</div>

<!-- Currency Positions -->
<div class="card">
    <h2>Currency Positions</h2>
    <p style="color: #718096; margin-bottom: 1rem;">
        Revaluation calculates unrealized P&L using: <strong>(Current Rate - Avg Cost Rate) × Position Amount</strong>
    </p>
    
    @if($positions->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Currency</th>
                <th>Till</th>
                <th>Balance</th>
                <th>Avg Cost Rate</th>
                <th>Last Valuation</th>
                <th class="text-right">Unrealized P&L</th>
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
                <td class="text-right {{ ($position->unrealized_pnl ?? 0) >= 0 ? 'positive' : 'negative' }}">
                    {{ ($position->unrealized_pnl ?? 0) >= 0 ? '+' : '' }}
                    RM {{ number_format($position->unrealized_pnl ?? 0, 2) }}
                </td>
                <td>{{ $position->updated_at->diffForHumans() }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total Unrealized P&L:</th>
                <th class="text-right {{ $positions->sum('unrealized_pnl') >= 0 ? 'positive' : 'negative' }}">
                    {{ $positions->sum('unrealized_pnl') >= 0 ? '+' : '' }}
                    RM {{ number_format($positions->sum('unrealized_pnl'), 2) }}
                </th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="alert alert-info">
        No currency positions found. Positions are created automatically when transactions are processed.
    </div>
    @endif
</div>

<!-- Revaluation Schedule -->
<div class="card">
    <h2>Automation Schedule</h2>
    <div class="alert alert-info">
        <strong>Automatic Revaluation:</strong> Runs on the last day of each month at 23:59<br>
        <strong>Next Run:</strong> {{ now()->endOfMonth()->format('Y-m-d 23:59') }}<br>
        <strong>Notification:</strong> Manager and Compliance Officer will receive email notification
    </div>
</div>

@section('styles')
<style>
    .reval-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
    }
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    .status-item {
        display: flex;
        flex-direction: column;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 8px;
    }
    .status-label {
        color: #718096;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    .status-value {
        font-weight: 600;
        color: #2d3748;
        font-size: 1.125rem;
    }
    .status-completed {
        background: #c6f6d5;
        color: #22543d;
    }
    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    .text-right {
        text-align: right;
    }
    .positive {
        color: #38a169;
    }
    .negative {
        color: #e53e3e;
    }
    tfoot tr {
        border-top: 2px solid #e2e8f0;
        background: #f7fafc;
    }
    .alert-success {
        background: #c6f6d5;
        color: #22543d;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .alert-error {
        background: #fed7d7;
        color: #c53030;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
</style>
@endsection
@endsection
