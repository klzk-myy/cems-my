@extends('layouts.app')

@section('title', 'Profit & Loss Statement - CEMS-MY')

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
            <span class="breadcrumbs__text">Profit & Loss</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Profit & Loss Statement</h1>
        <p class="page-header__subtitle">Period: {{ $fromDate }} to {{ $toDate }}</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="from" value="{{ $fromDate }}" class="form-input" style="width: auto;">
            <input type="date" name="to" value="{{ $toDate }}" class="form-input" style="width: auto;">
            <button type="submit" class="btn btn--secondary btn--sm">Update</button>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format((float) $pl['total_revenue'], 2) }}</div>
        <div class="stat-card__label">Total Revenue</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">RM {{ number_format(abs((float) $pl['total_expenses']), 2) }}</div>
        <div class="stat-card__label">Total Expenses</div>
    </div>
    <div class="stat-card {{ (float) $pl['net_profit'] >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">{{ (float) $pl['net_profit'] >= 0 ? 'Profit' : 'Loss' }}</div>
        <div class="stat-card__label">RM {{ number_format(abs((float) $pl['net_profit']), 2) }}</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body p-0">
        <table class="data-table">
            <tbody>
                <!-- Revenue Section -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-blue-900">REVENUE</td>
                </tr>
                @if(count($pl['revenues']) > 0)
                    @foreach($pl['revenues'] as $account)
                    <tr>
                        <td class="text-gray-600 pl-8">{{ $account['account_name'] }}</td>
                        <td class="text-right text-green-600">RM {{ number_format((float) $account['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-gray-500 pl-8">No revenue accounts with activity</td>
                        <td class="text-right text-gray-400">-</td>
                    </tr>
                @endif
                <tr class="border-t-2 bg-gray-50">
                    <td class="font-semibold">Total Revenue</td>
                    <td class="text-right font-semibold text-green-600">RM {{ number_format($pl['total_revenue'], 2) }}</td>
                </tr>

                <!-- Expenses Section -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-blue-900">EXPENSES</td>
                </tr>
                @if(count($pl['expenses']) > 0)
                    @foreach($pl['expenses'] as $account)
                    <tr>
                        <td class="text-gray-600 pl-8">{{ $account['account_name'] }}</td>
                        <td class="text-right text-red-600">RM {{ number_format(abs((float) $account['amount']), 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-gray-500 pl-8">No expense accounts with activity</td>
                        <td class="text-right text-gray-400">-</td>
                    </tr>
                @endif
                <tr class="border-t-2 bg-gray-50">
                    <td class="font-semibold">Total Expenses</td>
                    <td class="text-right font-semibold text-red-600">RM {{ number_format(abs($pl['total_expenses']), 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Net Profit/Loss Banner -->
    <div class="p-4 border-t border-gray-200 {{ (float) $pl['net_profit'] >= 0 ? 'bg-green-100' : 'bg-red-100' }}">
        <div class="flex justify-between items-center">
            <span class="text-xl font-bold {{ (float) $pl['net_profit'] >= 0 ? 'text-green-800' : 'text-red-800' }}">
                NET {{ (float) $pl['net_profit'] >= 0 ? 'PROFIT' : 'LOSS' }}
            </span>
            <span class="text-2xl font-bold font-mono {{ (float) $pl['net_profit'] >= 0 ? 'text-green-800' : 'text-red-800' }}">
                RM {{ number_format(abs((float) $pl['net_profit']), 2) }}
            </span>
        </div>
    </div>
</div>
@endsection