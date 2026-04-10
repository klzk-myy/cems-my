@extends('layouts.app')

@section('title', 'Profitability Analysis - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Profitability Analysis</h2>
    <p class="text-gray-500 text-sm">Currency trading profit and loss analysis</p>
</div>

<!-- Date Range Filter -->
<form method="GET" action="{{ route('reports.profitability') }}" class="bg-white rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-40">
        <label for="start_date" class="block mb-1 text-sm font-medium text-gray-600">Start Date</label>
        <input type="date" name="start_date" id="start_date"
               value="{{ $startDate }}" class="w-full p-2 border border-gray-200 rounded text-sm">
    </div>
    <div class="flex-1 min-w-40">
        <label for="end_date" class="block mb-1 text-sm font-medium text-gray-600">End Date</label>
        <input type="date" name="end_date" id="end_date"
               value="{{ $endDate }}" class="w-full p-2 border border-gray-200 rounded text-sm">
    </div>
    <div class="flex-1 min-w-32">
        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors">Update Report</button>
    </div>
    <div class="flex-1 min-w-40">
        <a href="{{ route('reports.profitability', ['start_date' => now()->subMonth()->startOfMonth()->toDateString(), 'end_date' => now()->subMonth()->endOfMonth()->toDateString()]) }}"
           class="inline-block w-full px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors text-center">Last Month</a>
    </div>
</form>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white border-l-4 border-blue-500 rounded-lg p-6">
        <div class="text-3xl font-bold {{ $totals['total_unrealized'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            RM {{ number_format($totals['total_unrealized'], 2) }}
        </div>
        <div class="text-sm text-gray-500 mt-2">Total Unrealized P&L</div>
    </div>

    <div class="bg-white border-l-4 border-blue-500 rounded-lg p-6">
        <div class="text-3xl font-bold {{ $totals['total_realized'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            RM {{ number_format($totals['total_realized'], 2) }}
        </div>
        <div class="text-sm text-gray-500 mt-2">Total Realized P&L (Period)</div>
    </div>

    <div class="bg-white border-l-4 {{ $totals['total_pnl'] >= 0 ? 'border-green-500' : 'border-red-500' }} rounded-lg p-6">
        <div class="text-3xl font-bold {{ $totals['total_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            RM {{ number_format($totals['total_pnl'], 2) }}
        </div>
        <div class="text-sm text-gray-500 mt-2">Total P&L</div>
    </div>
</div>

<!-- Currency Breakdown -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Currency Profitability Breakdown</h3>

    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Currency</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Balance</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Avg Cost Rate</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Current Rate</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Unrealized P&L</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Realized P&L (Period)</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Total P&L</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Buy Volume</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Sell Volume</th>
            </tr>
        </thead>
        <tbody>
            @forelse($positions as $position)
            <tr>
                <td class="px-4 py-3 border-b border-gray-100">
                    <strong>{{ $position['currency']->code }}</strong>
                    <br>
                    <small class="text-gray-500">{{ $position['currency']->name }}</small>
                </td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($position['balance'], 2) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($position['avg_cost_rate'], 4) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($position['current_rate'], 4) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right {{ $position['unrealized_pnl'] >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                    {{ $position['unrealized_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['unrealized_pnl'], 2) }}
                </td>
                <td class="px-4 py-3 border-b border-gray-100 text-right {{ $position['realized_pnl'] >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                    {{ $position['realized_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['realized_pnl'], 2) }}
                </td>
                <td class="px-4 py-3 border-b border-gray-100 text-right {{ $position['total_pnl'] >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                    {{ $position['total_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($position['total_pnl'], 2) }}
                </td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">RM {{ number_format($position['buy_volume'], 2) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">RM {{ number_format($position['sell_volume'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                    No currency positions found.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot class="font-semibold bg-gray-50">
            <tr>
                <td colspan="4" class="px-4 py-3 border-t-2 border-gray-300">TOTAL</td>
                <td class="px-4 py-3 border-t-2 border-gray-300 text-right {{ $totals['total_unrealized'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $totals['total_unrealized'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_unrealized'], 2) }}
                </td>
                <td class="px-4 py-3 border-t-2 border-gray-300 text-right {{ $totals['total_realized'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $totals['total_realized'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_realized'], 2) }}
                </td>
                <td class="px-4 py-3 border-t-2 border-gray-300 text-right {{ $totals['total_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $totals['total_pnl'] >= 0 ? '+' : '' }}RM {{ number_format($totals['total_pnl'], 2) }}
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Info Card -->
<div class="bg-white rounded-lg shadow-sm p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">P&L Calculation Method</h3>
    <p class="text-gray-600 text-sm mb-2"><strong>Unrealized P&L:</strong> Potential gain/loss on current inventory based on current market rate vs average cost rate.</p>
    <p class="text-gray-600 text-sm mb-2"><strong>Realized P&L:</strong> Actual profit/loss from sell transactions during the selected period.</p>
    <p class="text-gray-600 text-sm"><strong>Formula:</strong> P&L = (Current Rate - Avg Cost Rate) × Balance</p>
</div>
@endsection
