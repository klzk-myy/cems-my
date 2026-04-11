@extends('layouts.app')

@section('title', 'Profitability Analysis - CEMS-MY')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Profitability Analysis</h1>
        <p class="page-header__subtitle">Currency trading profit and loss analysis</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Date Range Filter</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.profitability') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="form-input">
            </div>
            <div>
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="form-input">
            </div>
            <button type="submit" class="btn btn--primary">Update Report</button>
            <a href="{{ route('reports.profitability', ['start_date' => now()->subMonth()->startOfMonth()->toDateString(), 'end_date' => now()->subMonth()->endOfMonth()->toDateString()]) }}" class="btn btn--secondary">
                Last Month
            </a>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card {{ $totals['total_unrealized'] >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">RM {{ number_format($totals['total_unrealized'], 2) }}</div>
        <div class="stat-card__label">Total Unrealized P&L</div>
    </div>
    <div class="stat-card {{ $totals['total_realized'] >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">RM {{ number_format($totals['total_realized'], 2) }}</div>
        <div class="stat-card__label">Total Realized P&L (Period)</div>
    </div>
    <div class="stat-card {{ $totals['total_pnl'] >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">RM {{ number_format($totals['total_pnl'], 2) }}</div>
        <div class="stat-card__label">Total P&L</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Currency Profitability Breakdown</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Currency</th>
                    <th class="text-right">Balance</th>
                    <th class="text-right">Avg Cost Rate</th>
                    <th class="text-right">Current Rate</th>
                    <th class="text-right">Unrealized P&L</th>
                    <th class="text-right">Realized P&L (Period)</th>
                    <th class="text-right">Total P&L</th>
                    <th class="text-right">Buy Volume</th>
                    <th class="text-right">Sell Volume</th>
                </tr>
            </thead>
            <tbody>
                @forelse($positions as $position)
                <tr>
                    <td><strong>{{ $position['currency']->code }}</strong><br><small class="text-gray-500">{{ $position['currency']->name }}</small></td>
                    <td class="text-right">{{ number_format($position['balance'], 2) }}</td>
                    <td class="text-right">{{ number_format($position['avg_cost_rate'], 4) }}</td>
                    <td class="text-right">{{ number_format($position['current_rate'], 4) }}</td>
                    <td class="text-right {{ $position['unrealized_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $position['unrealized_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['unrealized_pnl'], 2) }}
                    </td>
                    <td class="text-right {{ $position['realized_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $position['realized_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['realized_pnl'], 2) }}
                    </td>
                    <td class="text-right {{ $position['total_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $position['total_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['total_pnl'], 2) }}
                    </td>
                    <td class="text-right">RM {{ number_format($position['buy_volume'], 2) }}</td>
                    <td class="text-right">RM {{ number_format($position['sell_volume'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-8 text-gray-500">
                        No currency positions found.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="4" class="text-left">TOTAL</td>
                    <td class="text-right {{ $totals['total_unrealized'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $totals['total_unrealized'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_unrealized'], 2) }}
                    </td>
                    <td class="text-right {{ $totals['total_realized'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $totals['total_realized'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_realized'], 2) }}
                    </td>
                    <td class="text-right {{ $totals['total_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $totals['total_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_pnl'], 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">P&L Calculation Method</h3>
    </div>
    <div class="card-body">
        <p class="text-gray-600 text-sm mb-2"><strong>Unrealized P&L:</strong> Potential gain/loss on current inventory based on current market rate vs average cost rate.</p>
        <p class="text-gray-600 text-sm mb-2"><strong>Realized P&L:</strong> Actual profit/loss from sell transactions during the selected period.</p>
        <p class="text-gray-600 text-sm"><strong>Formula:</strong> P&L = (Current Rate - Avg Cost Rate) × Balance</p>
    </div>
</div>
@endsection
