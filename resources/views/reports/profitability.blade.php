@extends('layouts.app')

@section('title', 'Profitability Analysis - CEMS-MY')

@section('styles')
<style>
    .profitability-header {
        margin-bottom: 1.5rem;
    }
    
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .kpi-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        border-left: 4px solid #3182ce;
    }
    
    .kpi-card.profit {
        border-left-color: #38a169;
    }
    
    .kpi-card.loss {
        border-left-color: #e53e3e;
    }
    
    .kpi-value {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .kpi-value.profit {
        color: #38a169;
    }
    
    .kpi-value.loss {
        color: #e53e3e;
    }
    
    .kpi-label {
        color: #718096;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
    
    .filter-bar {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: end;
    }
    
    .filter-group {
        flex: 1;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #4a5568;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .data-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #4a5568;
    }
    
    .pnl-positive {
        color: #38a169;
        font-weight: 600;
    }
    
    .pnl-negative {
        color: #e53e3e;
        font-weight: 600;
    }
    
    .rate-diff {
        font-size: 0.875rem;
        color: #718096;
    }
</style>
@endsection

@section('content')
<div class="profitability-header">
    <h2>Profitability Analysis</h2>
    <p>Currency trading profit and loss analysis</p>
</div>

<!-- Date Range Filter -->
<form method="GET" action="{{ route('reports.profitability') }}" class="filter-bar">
    <div class="filter-group">
        <label for="start_date">Start Date</label>
        <input type="date" name="start_date" id="start_date" 
               value="{{ $startDate }}" class="form-input">
    </div>
    <div class="filter-group">
        <label for="end_date">End Date</label>
        <input type="date" name="end_date" id="end_date" 
               value="{{ $endDate }}" class="form-input">
    </div>
    <div class="filter-group">
        <button type="submit" class="btn btn-primary">Update Report</button>
    </div>
    <div class="filter-group">
        <a href="{{ route('reports.profitability', ['start_date' => now()->subMonth()->startOfMonth()->toDateString(), 'end_date' => now()->subMonth()->endOfMonth()->toDateString()]) }}" 
           class="btn btn-secondary">Last Month</a>
    </div>
</form>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-value {{ $totals['total_unrealized'] >= 0 ? 'profit' : 'loss' }}">
            RM {{ number_format($totals['total_unrealized'], 2) }}
        </div>
        <div class="kpi-label">Total Unrealized P&L</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-value {{ $totals['total_realized'] >= 0 ? 'profit' : 'loss' }}">
            RM {{ number_format($totals['total_realized'], 2) }}
        </div>
        <div class="kpi-label">Total Realized P&L (Period)</div>
    </div>
    
    <div class="kpi-card {{ $totals['total_pnl'] >= 0 ? 'profit' : 'loss' }}">
        <div class="kpi-value {{ $totals['total_pnl'] >= 0 ? 'profit' : 'loss' }}">
            RM {{ number_format($totals['total_pnl'], 2) }}
        </div>
        <div class="kpi-label">Total P&L</div>
    </div>
</div>

<!-- Currency Breakdown -->
<div class="card">
    <h3>Currency Profitability Breakdown</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Currency</th>
                <th>Balance</th>
                <th>Avg Cost Rate</th>
                <th>Current Rate</th>
                <th>Unrealized P&L</th>
                <th>Realized P&L (Period)</th>
                <th>Total P&L</th>
                <th>Buy Volume</th>
                <th>Sell Volume</th>
            </tr>
        </thead>
        <tbody>
            @forelse($positions as $position)
            <tr>
                <td>
                    <strong>{{ $position['currency']->code }}</strong>
                    <br>
                    <small>{{ $position['currency']->name }}</small>
                </td>
                <td>{{ number_format($position['balance'], 2) }}</td>
                <td>{{ number_format($position['avg_cost_rate'], 4) }}</td>
                <td>{{ number_format($position['current_rate'], 4) }}</td>
                <td class="{{ $position['unrealized_pnl'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ $position['unrealized_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['unrealized_pnl'], 2) }}
                </td>
                <td class="{{ $position['realized_pnl'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ $position['realized_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['realized_pnl'], 2) }}
                </td>
                <td class="{{ $position['total_pnl'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ $position['total_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['total_pnl'], 2) }}
                </td>
                <td>RM {{ number_format($position['buy_volume'], 2) }}</td>
                <td>RM {{ number_format($position['sell_volume'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 2rem;">
                    No currency positions found.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot style="font-weight: bold; background: #f7fafc;">
            <tr>
                <td colspan="4">TOTAL</td>
                <td class="{{ $totals['total_unrealized'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ $totals['total_unrealized'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_unrealized'], 2) }}
                </td>
                <td class="{{ $totals['total_realized'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ $totals['total_realized'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_realized'], 2) }}
                </td>
                <td class="{{ $totals['total_pnl'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ $totals['total_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_pnl'], 2) }}
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Info Card -->
<div class="card" style="margin-top: 1.5rem;">
    <h3>P&L Calculation Method</h3>
    <p><strong>Unrealized P&L:</strong> Potential gain/loss on current inventory based on current market rate vs average cost rate.</p>
    <p><strong>Realized P&L:</strong> Actual profit/loss from sell transactions during the selected period.</p>
    <p><strong>Formula:</strong> P&L = (Current Rate - Avg Cost Rate) × Balance</p>
</div>
@endsection
