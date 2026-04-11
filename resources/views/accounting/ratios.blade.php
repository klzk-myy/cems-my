@extends('layouts.app')

@section('title', 'Financial Ratios - CEMS-MY')

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
            <span class="breadcrumbs__text">Financial Ratios</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Financial Ratios Dashboard</h1>
        <p class="page-header__subtitle">Key performance indicators for financial analysis</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="from_date" value="{{ $fromDate }}" class="form-input" style="width: auto;">
            <input type="date" name="to_date" value="{{ $toDate }}" class="form-input" style="width: auto;">
            <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="form-input" style="width: auto;">
            <button type="submit" class="btn btn--secondary btn--sm">Calculate Ratios</button>
        </form>
    </div>
</div>

@if(isset($ratios))
<!-- Liquidity Ratios -->
<div class="card mb-6">
    <div class="card-header bg-blue-600 text-white">
        <h3 class="text-lg font-semibold m-0">Liquidity Ratios</h3>
        <p class="text-blue-100 text-sm mb-0">Short-term solvency measures</p>
    </div>
    <div class="card-body">
        <div class="stats-grid mb-6">
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['liquidity']['current_ratio'], 2) }}</div>
                <div class="stat-card__label">Current Ratio</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['liquidity']['quick_ratio'], 2) }}</div>
                <div class="stat-card__label">Quick Ratio</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['liquidity']['cash_ratio'], 2) }}</div>
                <div class="stat-card__label">Cash Ratio</div>
            </div>
        </div>
        <table class="data-table">
            <tbody>
                <tr>
                    <td>Current Assets</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['liquidity']['current_assets'], 2) }}</td>
                </tr>
                <tr>
                    <td>Current Liabilities</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['liquidity']['current_liabilities'], 2) }}</td>
                </tr>
                <tr>
                    <td>Inventory</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['liquidity']['inventory'], 2) }}</td>
                </tr>
                <tr>
                    <td>Cash</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['liquidity']['cash'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Profitability Ratios -->
<div class="card mb-6">
    <div class="card-header bg-green-600 text-white">
        <h3 class="text-lg font-semibold m-0">Profitability Ratios</h3>
        <p class="text-green-100 text-sm mb-0">Earnings and return measures</p>
    </div>
    <div class="card-body">
        <div class="stats-grid mb-6">
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['profitability']['gross_profit_margin'] * 100, 1) }}%</div>
                <div class="stat-card__label">Gross Profit Margin</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['profitability']['net_profit_margin'] * 100, 1) }}%</div>
                <div class="stat-card__label">Net Profit Margin</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['profitability']['roe'], 2) }}</div>
                <div class="stat-card__label">Return on Equity</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['profitability']['roa'], 2) }}</div>
                <div class="stat-card__label">Return on Assets</div>
            </div>
        </div>
        <table class="data-table">
            <tbody>
                <tr>
                    <td>Revenue</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['profitability']['revenue'], 2) }}</td>
                </tr>
                <tr>
                    <td>COGS</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['profitability']['cogs'], 2) }}</td>
                </tr>
                <tr>
                    <td>Gross Profit</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['profitability']['gross_profit'], 2) }}</td>
                </tr>
                <tr>
                    <td>Net Income</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['profitability']['net_income'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Leverage Ratios -->
<div class="card mb-6">
    <div class="card-header bg-yellow-500 text-gray-900">
        <h3 class="text-lg font-semibold m-0">Leverage Ratios</h3>
        <p class="text-yellow-800 text-sm mb-0">Financial leverage measures</p>
    </div>
    <div class="card-body">
        <div class="stats-grid mb-6">
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['leverage']['debt_to_equity'], 2) }}</div>
                <div class="stat-card__label">Debt-to-Equity</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['leverage']['debt_to_assets'], 2) }}</div>
                <div class="stat-card__label">Debt-to-Assets</div>
            </div>
        </div>
        <table class="data-table">
            <tbody>
                <tr>
                    <td>Total Debt</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['leverage']['total_debt'], 2) }}</td>
                </tr>
                <tr>
                    <td>Equity</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['leverage']['equity'], 2) }}</td>
                </tr>
                <tr>
                    <td>Total Assets</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['leverage']['total_assets'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Efficiency Ratios -->
<div class="card">
    <div class="card-header bg-cyan-600 text-white">
        <h3 class="text-lg font-semibold m-0">Efficiency Ratios</h3>
        <p class="text-cyan-100 text-sm mb-0">Asset utilization measures</p>
    </div>
    <div class="card-body">
        <div class="stats-grid mb-6">
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['efficiency']['asset_turnover'], 2) }}</div>
                <div class="stat-card__label">Asset Turnover</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format((float) $ratios['efficiency']['inventory_turnover'], 2) }}</div>
                <div class="stat-card__label">Inventory Turnover</div>
            </div>
        </div>
        <table class="data-table">
            <tbody>
                <tr>
                    <td>Revenue</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['efficiency']['revenue'], 2) }}</td>
                </tr>
                <tr>
                    <td>Total Assets</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['efficiency']['total_assets'], 2) }}</td>
                </tr>
                <tr>
                    <td>COGS</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['efficiency']['cogs'], 2) }}</td>
                </tr>
                <tr>
                    <td>Inventory</td>
                    <td class="text-right">RM {{ number_format((float) $ratios['efficiency']['inventory'], 2) }}</td>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Select Date Parameters</h3>
        <p class="text-gray-500">Select date parameters and click "Calculate Ratios" to view financial ratios.</p>
    </div>
</div>
@endif
@endsection