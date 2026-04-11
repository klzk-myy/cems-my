@extends('layouts.app')

@section('title', 'Bank Reconciliation - CEMS-MY')

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
            <span class="breadcrumbs__text">Reconciliation</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Bank Reconciliation</h1>
        <p class="page-header__subtitle">Reconcile cash accounts with bank statements</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Select Account</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.reconciliation') }}" class="flex items-end gap-4">
            <div>
                <label for="account" class="form-label">Cash Account</label>
                <select name="account" id="account" class="form-select">
                    @foreach($cashAccounts as $account)
                        <option value="{{ $account->account_code }}" {{ $account->account_code == request('account') ? 'selected' : '' }}>
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn--primary">View Reconciliation</button>
        </form>
    </div>
</div>

@if(isset($report))
<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">RM {{ number_format($report['book_balance'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Book Balance</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">RM {{ number_format($report['outstanding_checks'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Outstanding Checks</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($report['outstanding_deposits'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Outstanding Deposits</div>
    </div>
    <div class="stat-card {{ ($report['adjusted_balance'] ?? 0) >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">RM {{ number_format($report['adjusted_balance'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Adjusted Balance</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Reconciliation Report</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Outstanding Checks -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-red-600 mb-3">Outstanding Checks</h4>
                @if(count($report['outstanding_checks_list'] ?? []) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['outstanding_checks_list'] as $item)
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td>{{ $item['reference'] }}</td>
                            <td class="text-right">RM {{ number_format($item['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-gray-500">No outstanding checks</p>
                @endif
            </div>

            <!-- Outstanding Deposits -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-green-600 mb-3">Outstanding Deposits</h4>
                @if(count($report['outstanding_deposits_list'] ?? []) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['outstanding_deposits_list'] as $item)
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td>{{ $item['reference'] }}</td>
                            <td class="text-right">RM {{ number_format($item['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-gray-500">No outstanding deposits</p>
                @endif
            </div>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center">
        <div class="text-5xl mb-4 text-gray-300">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Select Cash Account</h3>
        <p class="text-gray-500">Select a cash account above to view the reconciliation report.</p>
    </div>
</div>
@endif
@endsection