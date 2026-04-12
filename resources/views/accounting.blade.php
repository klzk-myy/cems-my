@extends('layouts.app')

@section('title', 'Accounting - CEMS-MY')

@section('content')
<div class="page-header">
    <h1 class="page-header__title">Currency Positions & Accounting</h1>
    <p class="page-header__subtitle">Real-time position tracking with average cost calculation</p>
</div>

<!-- Total Unrealized P&L -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="card text-center">
        @php
            $pnlValue = $totalPnl ?? 0;
            $pnlClass = $pnlValue >= 0 ? 'text-green-600' : 'text-red-600';
        @endphp
        <div class="text-3xl font-bold font-mono {{ $pnlClass }}">
            RM {{ number_format($pnlValue, 2) }}
        </div>
        <div class="text-sm text-gray-500 uppercase tracking-wide mt-2">Total Unrealized P&L</div>
    </div>
    <div class="card text-center">
        <div class="text-3xl font-bold font-mono text-gray-900">{{ $positions->count() }}</div>
        <div class="text-sm text-gray-500 uppercase tracking-wide mt-2">Active Currencies</div>
    </div>
    <div class="card text-center">
        <div class="text-3xl font-bold text-gray-900">{{ now()->format('M Y') }}</div>
        <div class="text-sm text-gray-500 uppercase tracking-wide mt-2">Current Month</div>
    </div>
</div>

<!-- Quick Links -->
<div class="card mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Accounting Menu</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <a href="{{ route('accounting.journal') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">📝</span>
            <span class="text-sm text-center text-gray-700">Journal Entries</span>
        </a>
        <a href="{{ route('accounting.journal.create') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">➕</span>
            <span class="text-sm text-center text-gray-700">New Journal Entry</span>
        </a>
        <a href="{{ route('accounting.ledger') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">📒</span>
            <span class="text-sm text-center text-gray-700">Ledger</span>
        </a>
        <a href="{{ route('accounting.trial-balance') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">⚖️</span>
            <span class="text-sm text-center text-gray-700">Trial Balance</span>
        </a>
        <a href="{{ route('accounting.profit-loss') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">📊</span>
            <span class="text-sm text-center text-gray-700">Profit & Loss</span>
        </a>
        <a href="{{ route('accounting.balance-sheet') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">📈</span>
            <span class="text-sm text-center text-gray-700">Balance Sheet</span>
        </a>
        <a href="{{ route('accounting.periods') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">📅</span>
            <span class="text-sm text-center text-gray-700">Periods</span>
        </a>
        <a href="{{ route('accounting.revaluation') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">💱</span>
            <span class="text-sm text-center text-gray-700">Revaluation</span>
        </a>
        <a href="{{ route('accounting.reconciliation') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">🏦</span>
            <span class="text-sm text-center text-gray-700">Reconciliation</span>
        </a>
        <a href="{{ route('accounting.budget') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">💰</span>
            <span class="text-sm text-center text-gray-700">Budget</span>
        </a>
        <a href="{{ route('accounting.journal.workflow') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">✅</span>
            <span class="text-sm text-center text-gray-700">Entry Workflow</span>
        </a>
        <a href="{{ route('accounting.cash-flow') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">💵</span>
            <span class="text-sm text-center text-gray-700">Cash Flow</span>
        </a>
        <a href="{{ route('accounting.ratios') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">📐</span>
            <span class="text-sm text-center text-gray-700">Financial Ratios</span>
        </a>
        <a href="{{ route('accounting.fiscal-years') }}" class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-100 hover:border-primary-300 hover:bg-gray-50 transition-colors">
            <span class="text-2xl mb-2">📆</span>
            <span class="text-sm text-center text-gray-700">Fiscal Years</span>
        </a>
    </div>
</div>

<!-- Currency Positions -->
<div class="card mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Currency Positions</h2>

    @if($positions->count() > 0)
    <div class="overflow-x-auto">
        <table class="data-table">
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
                    <td class="font-bold">{{ $position->currency_code }}</td>
                    <td class="text-sm">{{ $position->till_id }}</td>
                    <td class="font-mono text-sm">{{ number_format($position->balance, 4) }}</td>
                    <td class="font-mono text-sm">{{ number_format($position->avg_cost_rate, 6) }}</td>
                    <td class="font-mono text-sm">{{ $position->last_valuation_rate ? number_format($position->last_valuation_rate, 6) : 'N/A' }}</td>
                    <td class="font-mono text-sm font-semibold {{ $position->unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $position->unrealized_pnl >= 0 ? '+' : '' }}{{ number_format($position->unrealized_pnl, 2) }}
                    </td>
                    <td class="text-sm text-gray-500">{{ $position->updated_at ? $position->updated_at->diffForHumans() : 'Never' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="alert alert--info" role="alert">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>No currency positions recorded yet. Positions will be created automatically when transactions are processed.</span>
    </div>
    @endif
</div>

<!-- Monthly Revaluation Info -->
<div class="card">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Revaluation</h2>
    <div class="alert alert--info mb-4" role="alert">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="text-sm">
            <p class="font-semibold">Automatic Revaluation:</strong> Runs on the last day of each month at 23:59</p>
            <p class="mt-1"><strong>Formula:</strong> (New Rate - Avg Cost Rate) × Position Amount</p>
            <p class="mt-1"><strong>Next Run:</strong> {{ now()->endOfMonth()->format('Y-m-d 23:59') }}</p>
        </div>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('accounting.revaluation.run') }}" class="btn btn--primary">Run Manual Revaluation</a>
        <a href="{{ route('accounting.revaluation.history') }}" class="btn btn--success">View Revaluation History</a>
    </div>
</div>
@endsection
