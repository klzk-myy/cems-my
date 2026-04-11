@extends('layouts.app')

@section('title', 'Monthly Revaluation - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('accounting.index') }}" class="breadcrumbs__link">Accounting</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Revaluation</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Monthly Revaluation</h1>
        <p class="page-header__subtitle">Currency position revaluation for accounting compliance</p>
    </div>
    <div class="page-header__actions">
        <form method="POST" action="{{ route('accounting.revaluation.run') }}" class="inline">
            @csrf
            <button type="submit" class="btn btn--success" onclick="return confirm('Are you sure you want to run revaluation? This will create journal entries.');">
                Run Revaluation
            </button>
        </form>
        <a href="{{ route('accounting.revaluation.history') }}" class="btn btn--secondary">View History</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-6">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-6">{{ session('error') }}</div>
@endif

<!-- Revaluation Status Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $status['month'] }}</div>
        <div class="stat-card__label">Current Month</div>
    </div>
    <div class="stat-card {{ $status['has_run'] ? 'stat-card--success' : 'stat-card--warning' }}">
        <div class="stat-card__value">{{ $status['has_run'] ? 'Completed' : 'Pending' }}</div>
        <div class="stat-card__label">Status</div>
    </div>
    @if($status['has_run'])
    <div class="stat-card">
        <div class="stat-card__value">{{ $status['positions_updated'] }}</div>
        <div class="stat-card__label">Positions Updated</div>
    </div>
    <div class="stat-card {{ ($status['total_gain_loss'] ?? 0) >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">RM {{ number_format($status['total_gain_loss'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Total Gain/Loss</div>
    </div>
    @endif
</div>

<!-- Currency Positions -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Currency Positions</h3>
        <p class="text-muted mb-0 text-sm">Revaluation calculates unrealized P&L using: (Current Rate - Avg Cost Rate) × Position Amount</p>
    </div>
    <div class="card-body p-0">
        @if($positions->count() > 0)
        <table class="data-table">
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
                    <td><strong class="text-gray-800">{{ $position->currency_code }}</strong></td>
                    <td class="text-gray-600">{{ $position->till_id }}</td>
                    <td class="text-gray-600">{{ number_format($position->balance, 4) }}</td>
                    <td class="text-gray-600">{{ number_format($position->avg_cost_rate, 6) }}</td>
                    <td class="text-gray-600">{{ $position->last_valuation_rate ? number_format($position->last_valuation_rate, 6) : 'N/A' }}</td>
                    <td class="text-right">
                        @php
                            $pnlClass = ($position->unrealized_pnl ?? 0) >= 0 ? 'text-green-600' : 'text-red-600';
                        @endphp
                        <span class="{{ $pnlClass }} font-semibold">
                            {{ ($position->unrealized_pnl ?? 0) >= 0 ? '+' : '' }}
                            RM {{ number_format($position->unrealized_pnl ?? 0, 2) }}
                        </span>
                    </td>
                    <td class="text-gray-500">{{ $position->updated_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50">
                    <td colspan="5" class="text-right font-semibold text-gray-700">Total Unrealized P&L:</td>
                    <td class="text-right font-semibold text-lg">
                        @php
                            $totalPnlClass = $positions->sum('unrealized_pnl') >= 0 ? 'text-green-600' : 'text-red-600';
                        @endphp
                        <span class="{{ $totalPnlClass }}">
                            {{ $positions->sum('unrealized_pnl') >= 0 ? '+' : '' }}
                            RM {{ number_format($positions->sum('unrealized_pnl'), 2) }}
                        </span>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="p-6 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Currency Positions</h3>
            <p class="text-gray-500">Positions are created automatically when transactions are processed.</p>
        </div>
        @endif
    </div>
</div>

<!-- Automation Schedule -->
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Automation Schedule</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Automatic Revaluation:</strong> Runs on the last day of each month at 23:59<br>
            <strong>Next Run:</strong> {{ now()->endOfMonth()->format('Y-m-d 23:59') }}<br>
            <strong>Notification:</strong> Manager and Compliance Officer will receive email notification
        </div>
    </div>
</div>
@endsection