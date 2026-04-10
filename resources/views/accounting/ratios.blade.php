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
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Financial Ratios Dashboard</h2>
    <p class="text-gray-500 text-sm">Key performance indicators for financial analysis</p>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h4 class="text-lg font-semibold text-gray-800 mb-4">Select Period</h4>
    <form method="GET" action="{{ route('accounting.ratios') }}">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="from_date" class="block mb-1 text-sm font-medium text-gray-600">From Date</label>
                <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}" class="p-2 border border-gray-200 rounded text-sm">
            </div>
            <div>
                <label for="to_date" class="block mb-1 text-sm font-medium text-gray-600">To Date</label>
                <input type="date" name="to_date" id="to_date" value="{{ $toDate }}" class="p-2 border border-gray-200 rounded text-sm">
            </div>
            <div>
                <label for="as_of_date" class="block mb-1 text-sm font-medium text-gray-600">As of Date</label>
                <input type="date" name="as_of_date" id="as_of_date" value="{{ $asOfDate }}" class="p-2 border border-gray-200 rounded text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors">Calculate Ratios</button>
        </div>
    </form>
</div>

@if(isset($ratios))
<!-- Liquidity Ratios -->
<div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
    <div class="bg-blue-600 text-white px-6 py-4">
        <h4 class="text-lg font-semibold m-0">Liquidity Ratios</h4>
        <p class="text-blue-100 text-sm m-0">Short-term solvency measures</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Current Ratio</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['liquidity']['current_ratio'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">Current Assets / Current Liabilities</div>
                <div class="mt-3">
                    @if((float) $ratios['liquidity']['current_ratio'] >= 1.5)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Strong</span>
                    @elseif((float) $ratios['liquidity']['current_ratio'] >= 1)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Acceptable</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Weak</span>
                    @endif
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Quick Ratio</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['liquidity']['quick_ratio'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">(Current Assets - Inventory) / Current Liabilities</div>
                <div class="mt-3">
                    @if((float) $ratios['liquidity']['quick_ratio'] >= 1)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Strong</span>
                    @elseif((float) $ratios['liquidity']['quick_ratio'] >= 0.5)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Acceptable</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Weak</span>
                    @endif
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Cash Ratio</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['liquidity']['cash_ratio'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">Cash / Current Liabilities</div>
                <div class="mt-3">
                    @if((float) $ratios['liquidity']['cash_ratio'] >= 0.5)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Strong</span>
                    @elseif((float) $ratios['liquidity']['cash_ratio'] >= 0.2)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Acceptable</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Weak</span>
                    @endif
                </div>
            </div>
        </div>

        <h6 class="text-sm font-semibold text-gray-600 mb-3">Components</h6>
        <table class="w-full text-sm">
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Current Assets</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['liquidity']['current_assets'], 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Current Liabilities</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['liquidity']['current_liabilities'], 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Inventory</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['liquidity']['inventory'], 2) }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Cash</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['liquidity']['cash'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- Profitability Ratios -->
<div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
    <div class="bg-green-600 text-white px-6 py-4">
        <h4 class="text-lg font-semibold m-0">Profitability Ratios</h4>
        <p class="text-green-100 text-sm m-0">Earnings and return measures</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Gross Profit Margin</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['profitability']['gross_profit_margin'] * 100, 1) }}%</div>
                <div class="text-xs text-gray-400 mt-1">(Revenue - COGS) / Revenue</div>
            </div>
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Net Profit Margin</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['profitability']['net_profit_margin'] * 100, 1) }}%</div>
                <div class="text-xs text-gray-400 mt-1">Net Income / Revenue</div>
            </div>
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Return on Equity (ROE)</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['profitability']['roe'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">Net Income / Equity</div>
            </div>
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Return on Assets (ROA)</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['profitability']['roa'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">Net Income / Total Assets</div>
            </div>
        </div>

        <h6 class="text-sm font-semibold text-gray-600 mb-3">Components</h6>
        <table class="w-full text-sm">
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Revenue</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['profitability']['revenue'], 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">COGS</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['profitability']['cogs'], 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Gross Profit</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['profitability']['gross_profit'], 2) }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Net Income</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['profitability']['net_income'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- Leverage Ratios -->
<div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
    <div class="bg-yellow-500 text-gray-900 px-6 py-4">
        <h4 class="text-lg font-semibold m-0">Leverage Ratios</h4>
        <p class="text-yellow-800 text-sm m-0">Financial leverage measures</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Debt-to-Equity</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['leverage']['debt_to_equity'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">Total Debt / Equity</div>
                <div class="mt-3">
                    @if((float) $ratios['leverage']['debt_to_equity'] <= 1)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Conservative</span>
                    @elseif((float) $ratios['leverage']['debt_to_equity'] <= 2)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Moderate</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">High Leverage</span>
                    @endif
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Debt-to-Assets</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['leverage']['debt_to_assets'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">Total Debt / Total Assets</div>
                <div class="mt-3">
                    @if((float) $ratios['leverage']['debt_to_assets'] <= 0.5)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Conservative</span>
                    @elseif((float) $ratios['leverage']['debt_to_assets'] <= 0.7)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Moderate</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">High Leverage</span>
                    @endif
                </div>
            </div>
        </div>

        <h6 class="text-sm font-semibold text-gray-600 mb-3">Components</h6>
        <table class="w-full text-sm">
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Total Debt</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['leverage']['total_debt'], 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Equity</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['leverage']['equity'], 2) }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Total Assets</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['leverage']['total_assets'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- Efficiency Ratios -->
<div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
    <div class="bg-cyan-600 text-white px-6 py-4">
        <h4 class="text-lg font-semibold m-0">Efficiency Ratios</h4>
        <p class="text-cyan-100 text-sm m-0">Asset utilization measures</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Asset Turnover</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['efficiency']['asset_turnover'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">Revenue / Total Assets</div>
            </div>
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <h5 class="text-sm text-gray-500 mb-2">Inventory Turnover</h5>
                <div class="text-3xl font-bold text-gray-800">{{ number_format((float) $ratios['efficiency']['inventory_turnover'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">COGS / Inventory</div>
            </div>
        </div>

        <h6 class="text-sm font-semibold text-gray-600 mb-3">Components</h6>
        <table class="w-full text-sm">
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Revenue</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['efficiency']['revenue'], 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">Total Assets</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['efficiency']['total_assets'], 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 text-gray-600">COGS</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['efficiency']['cogs'], 2) }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Inventory</td>
                <td class="py-2 text-right text-gray-800 font-medium">{{ number_format((float) $ratios['efficiency']['inventory'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-lg shadow-sm p-6">
    <p class="text-gray-500">Select date parameters and click "Calculate Ratios" to view financial ratios.</p>
</div>
@endif
@endsection
