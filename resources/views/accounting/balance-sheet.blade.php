@extends('layouts.app')

@section('title', 'Balance Sheet - CEMS-MY')

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
            <span class="breadcrumbs__text">Balance Sheet</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Balance Sheet</h1>
        <p class="page-header__subtitle">As of {{ $asOfDate }}</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="as_of" value="{{ $asOfDate }}" class="form-input" style="width: auto;">
            <button type="submit" class="btn btn--secondary btn--sm">Update</button>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">RM {{ number_format($balanceSheet['total_assets'], 2) }}</div>
        <div class="stat-card__label">Total Assets</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">RM {{ number_format($balanceSheet['total_liabilities'], 2) }}</div>
        <div class="stat-card__label">Total Liabilities</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($balanceSheet['total_equity'], 2) }}</div>
        <div class="stat-card__label">Total Equity</div>
    </div>
    <div class="stat-card {{ $balanceSheet['is_balanced'] ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">{{ $balanceSheet['is_balanced'] ? '✓ Balanced' : '✗ Unbalanced' }}</div>
        <div class="stat-card__label">{{ $balanceSheet['is_balanced'] ? 'Books Balanced' : 'Check Required' }}</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body p-0">
        <table class="data-table">
            <tbody>
                <!-- Assets Section -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-blue-900">ASSETS</td>
                </tr>
                @if(count($balanceSheet['assets']) > 0)
                    @foreach($balanceSheet['assets'] as $account)
                    <tr>
                        <td class="text-gray-600 pl-8">{{ $account['account_name'] }}</td>
                        <td class="text-right">RM {{ number_format($account['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-gray-500 pl-8">No asset accounts with balance</td>
                        <td class="text-right text-gray-400">-</td>
                    </tr>
                @endif
                <tr class="border-t-2 bg-gray-50">
                    <td class="font-semibold">Total Assets</td>
                    <td class="text-right font-semibold">RM {{ number_format($balanceSheet['total_assets'], 2) }}</td>
                </tr>

                <!-- Liabilities Section -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-blue-900">LIABILITIES</td>
                </tr>
                @if(count($balanceSheet['liabilities']) > 0)
                    @foreach($balanceSheet['liabilities'] as $account)
                    <tr>
                        <td class="text-gray-600 pl-8">{{ $account['account_name'] }}</td>
                        <td class="text-right">RM {{ number_format($account['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-gray-500 pl-8">No liability accounts with balance</td>
                        <td class="text-right text-gray-400">-</td>
                    </tr>
                @endif
                <tr class="border-t-2 bg-gray-50">
                    <td class="font-semibold">Total Liabilities</td>
                    <td class="text-right font-semibold">RM {{ number_format($balanceSheet['total_liabilities'], 2) }}</td>
                </tr>

                <!-- Equity Section -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-blue-900">EQUITY</td>
                </tr>
                @if(count($balanceSheet['equity']) > 0)
                    @foreach($balanceSheet['equity'] as $account)
                    <tr>
                        <td class="text-gray-600 pl-8">{{ $account['account_name'] }}</td>
                        <td class="text-right">RM {{ number_format($account['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-gray-500 pl-8">No equity accounts with balance</td>
                        <td class="text-right text-gray-400">-</td>
                    </tr>
                @endif
                <tr class="border-t-2 bg-gray-50">
                    <td class="font-semibold">Total Equity</td>
                    <td class="text-right font-semibold">RM {{ number_format($balanceSheet['total_equity'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Balance Check Banner -->
    <div class="p-4 border-t border-gray-200 {{ $balanceSheet['is_balanced'] ? 'bg-green-100' : 'bg-red-100' }}">
        <div class="flex justify-between items-center">
            <span class="font-medium text-gray-700">Total Assets vs Liabilities + Equity</span>
            <span class="font-mono font-bold {{ $balanceSheet['is_balanced'] ? 'text-green-700' : 'text-red-700' }}">
                @if($balanceSheet['is_balanced'])
                    ✓ Balanced
                @else
                    Difference: RM {{ number_format(abs((float) $balanceSheet['total_assets'] - (float) $balanceSheet['liabilities_plus_equity']), 2) }}
                @endif
            </span>
        </div>
    </div>
</div>
@endsection