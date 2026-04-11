@extends('layouts.app')

@section('title', 'Budget vs Actual - CEMS-MY')

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
            <span class="breadcrumbs__text">Budget</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Budget vs Actual Report</h1>
        <p class="page-header__subtitle">Compare budgeted amounts with actual expenditures</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Select Period</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.budget') }}" class="flex items-end gap-4">
            <div>
                <label for="period" class="form-label">Accounting Period</label>
                <input type="month" name="period" id="period" value="{{ $periodCode }}" class="form-input">
            </div>
            <button type="submit" class="btn btn--primary">View Report</button>
        </form>
    </div>
</div>

@if(isset($report) && isset($report['items']) && count($report['items']) > 0)
<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">RM {{ number_format((float) ($report['total_budget'] ?? 0), 2) }}</div>
        <div class="stat-card__label">Total Budget</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format((float) ($report['total_actual'] ?? 0), 2) }}</div>
        <div class="stat-card__label">Total Actual</div>
    </div>
    <div class="stat-card {{ ($report['total_variance'] ?? 0) >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">{{ ($report['total_variance'] ?? 0) >= 0 ? '+' : '' }}RM {{ number_format((float) ($report['total_variance'] ?? 0), 2) }}</div>
        <div class="stat-card__label">Total Variance</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $report['over_budget_count'] ?? 0 }}</div>
        <div class="stat-card__label">Over Budget</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Budget Report - {{ $report['period_code'] ?? $periodCode }}</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-right">Budget</th>
                    <th class="text-right">Actual</th>
                    <th class="text-right">Variance</th>
                    <th class="text-right">% Used</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['items'] as $item)
                <tr>
                    <td><strong>{{ $item['account_code'] }}</strong></td>
                    <td>{{ $item['account_name'] }}</td>
                    <td class="text-right">RM {{ number_format((float) $item['budget'], 2) }}</td>
                    <td class="text-right">RM {{ number_format((float) $item['actual'], 2) }}</td>
                    <td class="text-right">
                        @php
                            $varClass = (float) $item['variance'] >= 0 ? 'text-green-600' : 'text-red-600';
                        @endphp
                        <span class="{{ $varClass }}">
                            {{ (float) $item['variance'] >= 0 ? '+' : '' }}{{ number_format((float) $item['variance'], 2) }}
                        </span>
                    </td>
                    <td class="text-right">
                        @php
                            $percent = (float) $item['budget'] != 0 ? ((float) $item['actual'] / (float) $item['budget']) * 100 : 0;
                            $color = $percent > 100 ? 'text-red-600' : ($percent > 80 ? 'text-yellow-600' : 'text-green-600');
                        @endphp
                        <span class="{{ $color }}">{{ number_format($percent, 1) }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center">
        <div class="text-5xl mb-4 text-gray-300">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">No Budget Data</h3>
        <p class="text-gray-500">No budget data available for this period.</p>
        <p class="text-sm text-gray-400 mt-2">Run <code>php artisan db:seed --class=BudgetSeeder</code> to create sample budgets.</p>
    </div>
</div>
@endif

@if(isset($unbudgeted) && count($unbudgeted) > 0)
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-yellow-600">Accounts Without Budget</h3>
    </div>
    <div class="card-body p-0">
        <div class="alert alert-warning mb-4">The following accounts have transactions but no budget allocated for {{ $periodCode }}.</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Account Type</th>
                    <th class="text-right">Actual Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unbudgeted as $account)
                <tr>
                    <td>{{ $account->account_code }}</td>
                    <td>{{ $account->account_name }}</td>
                    <td>{{ $account->account_type }}</td>
                    <td class="text-right">RM {{ number_format((float) $account->actual_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection