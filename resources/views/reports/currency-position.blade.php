@extends('layouts.app')

@section('title', 'Currency Position Report - CEMS-MY')

@section('content')
<div class="report-header">
    <h2>Currency Position Report</h2>
    <p>Real-time foreign currency inventory and unrealized P&L</p>
</div>

@php
$reportData = app(App\Services\ReportingService::class)->generateCurrencyPositionReport();
@endphp

<!-- Summary Cards -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
    <div class="card summary-card">
        <h3>Active Currencies</h3>
        <p class="amount">{{ count($reportData['positions']) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Total Unrealized P&L</h3>
        <p class="amount {{ $reportData['total_unrealized_pnl'] >= 0 ? 'positive' : 'negative' }}">
            {{ $reportData['total_unrealized_pnl'] >= 0 ? '+' : '' }}
            RM {{ number_format($reportData['total_unrealized_pnl'], 2) }}
        </p>
    </div>
    <div class="card summary-card">
        <h3>Report Generated</h3>
        <p class="amount" style="font-size: 1rem;">{{ $reportData['generated_at'] }}</p>
    </div>
</div>

<!-- Positions Table -->
<div class="card">
    <h2>Currency Positions Detail</h2>
    
    @if(count($reportData['positions']) > 0)
    <table>
        <thead>
            <tr>
                <th>Currency</th>
                <th>Name</th>
                <th>Balance</th>
                <th>Avg Cost Rate</th>
                <th>Last Valuation Rate</th>
                <th class="text-right">Unrealized P&L</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['positions'] as $position)
            <tr>
                <td><strong>{{ $position['currency_code'] }}</strong></td>
                <td>{{ $position['currency_name'] }}</td>
                <td>{{ number_format($position['balance'], 4) }}</td>
                <td>{{ number_format($position['avg_cost_rate'], 6) }}</td>
                <td>{{ $position['last_valuation_rate'] ? number_format($position['last_valuation_rate'], 6) : 'N/A' }}</td>
                <td class="text-right {{ $position['unrealized_pnl'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $position['unrealized_pnl'] >= 0 ? '+' : '' }}
                    RM {{ number_format($position['unrealized_pnl'], 2) }}
                </td>
                <td>
                    @if($position['balance'] > 0)
                        <span class="status-badge status-active">Long</span>
                    @elseif($position['balance'] < 0)
                        <span class="status-badge status-flagged">Short</span>
                    @else
                        <span class="status-badge">Flat</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total Unrealized P&L:</th>
                <th class="text-right {{ $reportData['total_unrealized_pnl'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $reportData['total_unrealized_pnl'] >= 0 ? '+' : '' }}
                    RM {{ number_format($reportData['total_unrealized_pnl'], 2) }}
                </th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 1.5rem; text-align: center;">
        <a href="{{ route('reports.export') }}?report_type=currency_position&period={{ now()->format('Y-m-d') }}&format=CSV" class="btn btn-success">Export CSV</a>
        <a href="{{ route('reports.export') }}?report_type=currency_position&period={{ now()->format('Y-m-d') }}&format=PDF" class="btn btn-primary">Export PDF</a>
    </div>
    @else
    <div class="alert alert-info">
        No currency positions found. Positions are created automatically when transactions are processed.
    </div>
    @endif
</div>

<!-- Formula Reference -->
<div class="card">
    <h2>Calculation Formula</h2>
    <div class="alert alert-info">
        <strong>Unrealized P&L =</strong> (Last Valuation Rate - Average Cost Rate) × Balance<br>
        <strong>Note:</strong> Positive value indicates unrealized gain, negative indicates unrealized loss
    </div>
</div>

@section('styles')
<style>
    .report-header {
        margin-bottom: 1.5rem;
    }
    .report-header h2 {
        margin-bottom: 0.5rem;
        color: #2d3748;
    }
    .report-header p {
        color: #718096;
    }
    .summary-card {
        text-align: center;
        padding: 1.5rem;
    }
    .summary-card h3 {
        color: #718096;
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }
    .amount {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .positive {
        color: #38a169;
    }
    .negative {
        color: #e53e3e;
    }
    .text-right {
        text-align: right;
    }
    tfoot tr {
        border-top: 2px solid #e2e8f0;
        background: #f7fafc;
    }
</style>
@endsection
@endsection
