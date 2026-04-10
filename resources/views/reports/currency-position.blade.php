@extends('layouts.app')

@section('title', 'Currency Position Report - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Currency Position Report</h2>
    <p class="text-gray-500 text-sm">Real-time foreign currency inventory and unrealized P&L</p>
</div>

@php
$reportData = app(App\Services\ReportingService::class)->generateCurrencyPositionReport();
@endphp

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
        <h3 class="text-sm text-gray-500 mb-2">Active Currencies</h3>
        <p class="text-3xl font-bold text-gray-800">{{ count($reportData['positions']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Unrealized P&L</h3>
        <p class="text-3xl font-bold {{ $reportData['total_unrealized_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $reportData['total_unrealized_pnl'] >= 0 ? '+' : '' }}
            RM {{ number_format($reportData['total_unrealized_pnl'], 2) }}
        </p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
        <h3 class="text-sm text-gray-500 mb-2">Report Generated</h3>
        <p class="text-lg font-semibold text-gray-800">{{ $reportData['generated_at'] }}</p>
    </div>
</div>

<!-- Positions Table -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Currency Positions Detail</h2>

    @if(count($reportData['positions']) > 0)
    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Currency</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Name</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Balance</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Avg Cost Rate</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Last Valuation Rate</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Unrealized P&L</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['positions'] as $position)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b border-gray-100"><strong>{{ $position['currency_code'] }}</strong></td>
                <td class="px-4 py-3 border-b border-gray-100">{{ $position['currency_name'] }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($position['balance'], 4) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($position['avg_cost_rate'], 6) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ $position['last_valuation_rate'] ? number_format($position['last_valuation_rate'], 6) : 'N/A' }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right {{ $position['unrealized_pnl'] >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                    {{ $position['unrealized_pnl'] >= 0 ? '+' : '' }}
                    RM {{ number_format($position['unrealized_pnl'], 2) }}
                </td>
                <td class="px-4 py-3 border-b border-gray-100">
                    @if($position['balance'] > 0)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Long</span>
                    @elseif($position['balance'] < 0)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Short</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Flat</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="font-semibold bg-gray-50 border-t-2 border-gray-200">
            <tr>
                <td colspan="5" class="text-right px-4 py-3">Total Unrealized P&L:</td>
                <td class="text-right px-4 py-3 {{ $reportData['total_unrealized_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $reportData['total_unrealized_pnl'] >= 0 ? '+' : '' }}
                    RM {{ number_format($reportData['total_unrealized_pnl'], 2) }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-6 text-center">
        <a href="{{ route('reports.export') }}?report_type=currency_position&period={{ now()->format('Y-m-d') }}&format=CSV" class="btn btn-success mr-2">Export CSV</a>
        <a href="{{ route('reports.export') }}?report_type=currency_position&period={{ now()->format('Y-m-d') }}&format=PDF" class="btn btn-primary">Export PDF</a>
    </div>
    @else
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
        No currency positions found. Positions are created automatically when transactions are processed.
    </div>
    @endif
</div>

<!-- Formula Reference -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Calculation Formula</h2>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg text-sm text-blue-800">
        <strong>Unrealized P&L =</strong> (Last Valuation Rate - Average Cost Rate) × Balance<br>
        <strong>Note:</strong> Positive value indicates unrealized gain, negative indicates unrealized loss
    </div>
</div>
@endsection
