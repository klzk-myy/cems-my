@extends('layouts.app')

@section('title', 'Stock & Cash Management - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Stock & Cash</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Stock & Cash Management</h1>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['total_currencies'] ?? 0 }}</div>
        <div class="stat-card__label">Active Currencies</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $stats['active_positions'] ?? 0 }}</div>
        <div class="stat-card__label">Currency Positions</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $stats['open_tills'] ?? 0 }}</div>
        <div class="stat-card__label">Open Tills</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ number_format($stats['total_variance'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Total Variance (MYR)</div>
    </div>
</div>

<!-- Till Operations -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Till Operations</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Open Till Form -->
            <form action="/stock-cash/open" method="POST">
                @csrf
                <h4 class="text-base font-semibold text-gray-700 mb-4">Open Till</h4>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Till ID</label>
                    <input type="text" name="till_id" placeholder="e.g., TILL-001" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Currency</label>
                    <select name="currency_code" required class="form-select">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Opening Balance</label>
                    <input type="number" step="0.0001" name="opening_balance" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Notes</label>
                    <input type="text" name="notes" placeholder="Optional" class="form-input">
                </div>
                <button type="submit" class="btn btn--success btn--full">Open Till</button>
            </form>

            <!-- Close Till Form -->
            <form action="/stock-cash/close" method="POST">
                @csrf
                <h4 class="text-base font-semibold text-gray-700 mb-4">Close Till</h4>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Till ID</label>
                    <input type="text" name="till_id" placeholder="e.g., TILL-001" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Currency</label>
                    <select name="currency_code" required class="form-select">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->code }}">{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Closing Balance</label>
                    <input type="number" step="0.0001" name="closing_balance" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-600">Notes</label>
                    <input type="text" name="notes" placeholder="Optional" class="form-input">
                </div>
                <button type="submit" class="btn btn--warning btn--full">Close Till</button>
            </form>
        </div>
    </div>
</div>

<!-- Currency Positions -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Currency Positions</h3>
    </div>
    <div class="card-body p-0">
    @if(count($positions) > 0)
        <table class="data-table">
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
                    <td>
                        @php
                            $pnlClass = ($position->unrealized_pnl ?? 0) >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                        @endphp
                        <span class="{{ $pnlClass }}">{{ $position->unrealized_pnl ? number_format($position->unrealized_pnl, 2) : '0.00' }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <strong>Total Unrealized P&L: </strong>
            @php
                $totalPnlClass = ($totalPnl ?? 0) >= 0 ? 'text-green-600' : 'text-red-600';
            @endphp
            <span class="{{ $totalPnlClass }} font-semibold text-lg">MYR {{ number_format($totalPnl ?? 0, 2) }}</span>
        </div>
    @else
        <div class="p-6 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Currency Positions</h3>
            <p class="text-gray-500">Positions will be created automatically when transactions are processed.</p>
        </div>
    @endif
    </div>
</div>

<!-- Today's Till Balances -->
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Today's Till Balances</h3>
    </div>
    <div class="card-body p-0">
    @if($todayBalances->count() > 0)
        <table class="data-table">
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
                    <td>
                        @if(($balance->variance ?? 0) == 0)
                            <span class="text-green-600">{{ $balance->variance ? number_format($balance->variance, 2) : '-' }}</span>
                        @else
                            <span class="text-red-600">{{ $balance->variance ? number_format($balance->variance, 2) : '-' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($balance->closed_at)
                            <span class="status-badge status-badge--active">Closed</span>
                        @else
                            <span class="status-badge status-badge--danger">Open</span>
                        @endif
                    </td>
                    <td>{{ $balance->opener->username ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="p-6 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Tills Opened Today</h3>
            <p class="text-gray-500">Use the "Open Till" form above to get started.</p>
        </div>
    @endif
    </div>
</div>
@endsection