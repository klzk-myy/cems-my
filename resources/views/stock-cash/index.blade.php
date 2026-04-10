@extends('layouts.app')

@section('title', 'Stock & Cash Management - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Stock & Cash Management</h2>
    <p class="text-gray-500 text-sm">Manage foreign currency inventory and till operations</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-6 text-white text-center">
        <div class="text-3xl font-bold">{{ $stats['total_currencies'] ?? 0 }}</div>
        <div class="text-sm opacity-90 mt-1">Active Currencies</div>
    </div>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-6 text-white text-center">
        <div class="text-3xl font-bold">{{ $stats['active_positions'] ?? 0 }}</div>
        <div class="text-sm opacity-90 mt-1">Currency Positions</div>
    </div>
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-6 text-white text-center">
        <div class="text-3xl font-bold">{{ $stats['open_tills'] ?? 0 }}</div>
        <div class="text-sm opacity-90 mt-1">Open Tills</div>
    </div>
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg p-6 text-white text-center">
        <div class="text-3xl font-bold">{{ number_format($stats['total_variance'] ?? 0, 2) }}</div>
        <div class="text-sm opacity-90 mt-1">Total Variance (MYR)</div>
    </div>
</div>

<!-- Till Operations -->
<div class="card">
    <h2>Till Operations</h2>

    <div class="flex flex-wrap gap-6 mb-6">
        <form action="/stock-cash/open" method="POST" class="flex-1 min-w-64">
            @csrf
            <h3 class="text-base font-semibold text-gray-800 mb-4">Open Till</h3>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Till ID</label>
                <input type="text" name="till_id" placeholder="e.g., TILL-001" required class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Currency</label>
                <select name="currency_code" required class="w-full p-2 border border-gray-200 rounded text-sm">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Opening Balance</label>
                <input type="number" step="0.0001" name="opening_balance" required class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Notes</label>
                <input type="text" name="notes" placeholder="Optional" class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <button type="submit" class="btn btn-success">Open Till</button>
        </form>

        <form action="/stock-cash/close" method="POST" class="flex-1 min-w-64">
            @csrf
            <h3 class="text-base font-semibold text-gray-800 mb-4">Close Till</h3>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Till ID</label>
                <input type="text" name="till_id" placeholder="e.g., TILL-001" required class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Currency</label>
                <select name="currency_code" required class="w-full p-2 border border-gray-200 rounded text-sm">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}">{{ $currency->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Closing Balance</label>
                <input type="number" step="0.0001" name="closing_balance" required class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-semibold text-gray-700">Notes</label>
                <input type="text" name="notes" placeholder="Optional" class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <button type="submit" class="btn btn-warning">Close Till</button>
        </form>
    </div>
</div>

<!-- Currency Positions -->
<div class="card">
    <h2>Currency Positions</h2>

    @if(count($positions) > 0)
        <table>
            <thead>
                <tr>
                    <th>Currency</th>
                    <th>Till</th>
                    <th>Balance</th>
                    <th>Avg Cost Rate</th>
                    <th>Last Valuation</th>
                    <th>Unrealized P&L</th>
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
                    <td class="{{ ($position->unrealized_pnl ?? 0) >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                        {{ $position->unrealized_pnl ? number_format($position->unrealized_pnl, 2) : '0.00' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 p-4 bg-gray-50 rounded text-sm">
            <strong>Total Unrealized P&L: </strong>
            <span class="{{ ($totalPnl ?? 0) >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }} text-lg">
                MYR {{ number_format($totalPnl ?? 0, 2) }}
            </span>
        </div>
    @else
        <div class="alert alert-success">
            No currency positions recorded yet. Positions will be created automatically when transactions are processed.
        </div>
    @endif
</div>

<!-- Today's Till Balances -->
<div class="card">
    <h2>Today's Till Balances</h2>

    @if($todayBalances->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Till ID</th>
                    <th>Currency</th>
                    <th>Opening</th>
                    <th>Closing</th>
                    <th>Variance</th>
                    <th>Status</th>
                    <th>Opened By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($todayBalances as $balance)
                <tr>
                    <td>{{ $balance->till_id }}</td>
                    <td>{{ $balance->currency_code }}</td>
                    <td>{{ number_format($balance->opening_balance, 4) }}</td>
                    <td>{{ $balance->closing_balance ? number_format($balance->closing_balance, 4) : '-' }}</td>
                    <td class="{{ ($balance->variance ?? 0) == 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $balance->variance ? number_format($balance->variance, 2) : '-' }}
                    </td>
                    <td>
                        @if($balance->closed_at)
                            <span class="text-green-600">Closed</span>
                        @else
                            <span class="text-red-600">Open</span>
                        @endif
                    </td>
                    <td>{{ $balance->opener->username ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-success">
            No tills opened today. Use the "Open Till" form above to get started.
        </div>
    @endif
</div>
@endsection
