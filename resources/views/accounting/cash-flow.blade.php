@extends('layouts.app')

@section('title', 'Cash Flow Statement - CEMS-MY')

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
            <span class="breadcrumbs__text">Cash Flow</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Cash Flow Statement</h1>
        <p class="page-header__subtitle">Direct method cash flow analysis</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <label for="from_date" class="text-sm text-gray-600">From:</label>
            <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}" class="form-input" style="width: auto;">
            <label for="to_date" class="text-sm text-gray-600">To:</label>
            <input type="date" name="to_date" id="to_date" value="{{ $toDate }}" class="form-input" style="width: auto;">
            <button type="submit" class="btn btn--secondary btn--sm">Update</button>
        </form>
    </div>
</div>

@if(isset($cashFlow))
<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">RM {{ number_format((float) $cashFlow['opening_balance'], 2) }}</div>
        <div class="stat-card__label">Opening Balance</div>
    </div>
    <div class="stat-card {{ (float) $cashFlow['net_change'] >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">{{ (float) $cashFlow['net_change'] >= 0 ? '+' : '' }}RM {{ number_format((float) $cashFlow['net_change'], 2) }}</div>
        <div class="stat-card__label">Net Change</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format((float) $cashFlow['closing_balance'], 2) }}</div>
        <div class="stat-card__label">Closing Balance</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Cash Flow Statement</h3>
        <p class="text-muted mb-0">{{ $fromDate }} to {{ $toDate }}</p>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <tbody>
                <!-- Operating Activities -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-gray-700">Operating Activities</td>
                </tr>
                <tr>
                    <td>Cash Received from Customers</td>
                    <td class="text-right">{{ number_format((float) $cashFlow['operating']['cash_from_customers'], 2) }}</td>
                </tr>
                <tr>
                    <td>Cash Paid to Suppliers</td>
                    <td class="text-right">( {{ number_format((float) $cashFlow['operating']['cash_paid_to_suppliers'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Cash Paid for Salaries</td>
                    <td class="text-right">( {{ number_format((float) $cashFlow['operating']['cash_paid_for_salaries'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Cash Paid for Other Expenses</td>
                    <td class="text-right">( {{ number_format((float) $cashFlow['operating']['cash_paid_for_expenses'], 2) }} )</td>
                </tr>
                <tr class="border-t-2">
                    <td class="font-semibold">Net Cash from Operating Activities</td>
                    <td class="text-right font-semibold">
                        @php $netOpClass = (float) $cashFlow['operating']['net_operating'] >= 0 ? 'text-green-600' : 'text-red-600'; @endphp
                        <span class="{{ $netOpClass }}">{{ number_format((float) $cashFlow['operating']['net_operating'], 2) }}</span>
                    </td>
                </tr>

                <!-- Investing Activities -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-gray-700">Investing Activities</td>
                </tr>
                <tr>
                    <td>Asset Purchases</td>
                    <td class="text-right">( {{ number_format((float) $cashFlow['investing']['asset_purchases'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Asset Sales</td>
                    <td class="text-right">{{ number_format((float) $cashFlow['investing']['asset_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td>Investment Income</td>
                    <td class="text-right">{{ number_format((float) $cashFlow['investing']['investment_income'], 2) }}</td>
                </tr>
                <tr class="border-t-2">
                    <td class="font-semibold">Net Cash from Investing Activities</td>
                    <td class="text-right font-semibold">
                        @php $netInvClass = (float) $cashFlow['investing']['net_investing'] >= 0 ? 'text-green-600' : 'text-red-600'; @endphp
                        <span class="{{ $netInvClass }}">{{ number_format((float) $cashFlow['investing']['net_investing'], 2) }}</span>
                    </td>
                </tr>

                <!-- Financing Activities -->
                <tr class="bg-gray-50">
                    <td colspan="2" class="font-semibold text-gray-700">Financing Activities</td>
                </tr>
                <tr>
                    <td>Loans Received</td>
                    <td class="text-right">{{ number_format((float) $cashFlow['financing']['loans_received'], 2) }}</td>
                </tr>
                <tr>
                    <td>Loan Repayments</td>
                    <td class="text-right">( {{ number_format((float) $cashFlow['financing']['loan_repayments'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Dividends Paid</td>
                    <td class="text-right">( {{ number_format((float) $cashFlow['financing']['dividends_paid'], 2) }} )</td>
                </tr>
                <tr class="border-t-2">
                    <td class="font-semibold">Net Cash from Financing Activities</td>
                    <td class="text-right font-semibold">
                        @php $netFinClass = (float) $cashFlow['financing']['net_financing'] >= 0 ? 'text-green-600' : 'text-red-600'; @endphp
                        <span class="{{ $netFinClass }}">{{ number_format((float) $cashFlow['financing']['net_financing'], 2) }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center">
        <div class="text-5xl mb-4 text-gray-300">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Select Date Range</h3>
        <p class="text-gray-500">Select a date range and click "Update" to view the cash flow statement.</p>
    </div>
</div>
@endif
@endsection