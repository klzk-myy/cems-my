@extends('layouts.app')

@section('title', 'Ledger - CEMS-MY')

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
            <span class="breadcrumbs__text">Ledger</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Chart of Accounts / Ledger</h1>
        <p class="page-header__subtitle">View all accounts and drill down to individual ledgers</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ count($trialBalance['accounts']) }}</div>
        <div class="stat-card__label">Total Accounts</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($trialBalance['total_debits'], 2) }}</div>
        <div class="stat-card__label">Total Debits</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">RM {{ number_format($trialBalance['total_credits'], 2) }}</div>
        <div class="stat-card__label">Total Credits</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">RM {{ number_format((float) $trialBalance['total_balance'], 2) }}</div>
        <div class="stat-card__label">Net Balance</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">All Accounts</h3>
    </div>
    <div class="card-body p-0">
        @if(count($trialBalance['accounts']) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th class="text-right">Debit Balance</th>
                    <th class="text-right">Credit Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trialBalance['accounts'] as $account)
                <tr>
                    <td><strong>{{ $account['account_code'] }}</strong></td>
                    <td>
                        <a href="{{ route('accounting.ledger.account', $account['account_code']) }}" class="text-blue-600 hover:underline">
                            {{ $account['account_name'] }}
                        </a>
                    </td>
                    <td>
                        @php
                            $typeClass = match($account['account_type']) {
                                'Asset' => 'status-badge--active',
                                'Liability' => 'status-badge--danger',
                                'Equity' => 'status-badge--warning',
                                'Revenue' => 'status-badge--success',
                                'Expense' => 'status-badge--inactive',
                                default => 'status-badge--inactive'
                            };
                        @endphp
                        <span class="status-badge {{ $typeClass }}">{{ $account['account_type'] }}</span>
                    </td>
                    <td class="text-right text-gray-600">{{ $account['debit'] != 0 ? number_format($account['debit'], 2) : '-' }}</td>
                    <td class="text-right text-gray-600">{{ $account['credit'] != 0 ? number_format($account['credit'], 2) : '-' }}</td>
                    <td>
                        <a href="{{ route('accounting.ledger.account', $account['account_code']) }}" class="btn btn--primary btn--sm">View Ledger</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50">
                    <td colspan="3" class="text-right font-semibold text-gray-700">TOTAL</td>
                    <td class="text-right font-semibold text-gray-700">{{ number_format($trialBalance['total_debits'], 2) }}</td>
                    <td class="text-right font-semibold text-gray-700">{{ number_format($trialBalance['total_credits'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="p-12 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Chart of Accounts</h3>
            <p class="text-gray-500">Please run database migrations to set up accounts.</p>
        </div>
        @endif
    </div>
</div>

<!-- Quick Links -->
<div class="card mt-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Quick Links</h3>
    </div>
    <div class="card-body">
        <div class="flex gap-4">
            <a href="{{ route('accounting.trial-balance') }}" class="btn btn--primary">Trial Balance</a>
            <a href="{{ route('accounting.profit-loss') }}" class="btn btn--success">Profit & Loss</a>
            <a href="{{ route('accounting.balance-sheet') }}" class="btn btn--secondary">Balance Sheet</a>
        </div>
    </div>
</div>
@endsection