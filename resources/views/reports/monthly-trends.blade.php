@extends('layouts.app')

@section('title', 'Monthly Trends Report - CEMS-MY')

@section('styles')
<style>
    /* Breadcrumb */
    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        color: #718096;
    }

    .breadcrumb a {
        color: #3182ce;
        text-decoration: none;
    }

    .breadcrumb a:hover {
        text-decoration: underline;
    }

    /* Header Section */
    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-header h1 {
        font-size: 1.5rem;
        color: #1a365d;
        margin-bottom: 0.25rem;
    }

    .page-header p {
        color: #718096;
        font-size: 0.875rem;
    }

    /* Control Card */
    .control-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }

    .control-card h2 {
        font-size: 1rem;
        color: #2d3748;
        margin-bottom: 1rem;
        border: none;
        padding: 0;
    }

    .control-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-group label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #4a5568;
    }

    .form-control {
        padding: 0.5rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 0.875rem;
        min-width: 200px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        cursor: pointer;
        border: none;
        font-size: 0.875rem;
        transition: background 0.2s;
    }

    .btn-primary {
        background: #3182ce;
        color: white;
    }

    .btn-primary:hover {
        background: #2c5282;
    }

    .btn-success {
        background: #38a169;
        color: white;
    }

    .btn-success:hover {
        background: #2f855a;
    }

    /* Summary Cards */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1024px) {
        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }

    .summary-card {
        background: white;
        border-radius: 8px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .summary-card-label {
        font-size: 0.875rem;
        color: #718096;
        margin-bottom: 0.5rem;
    }

    .summary-card-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2d3748;
    }

    /* Chart Card */
    .chart-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }

    .chart-card h2 {
        font-size: 1.125rem;
        color: #2d3748;
        margin-bottom: 1rem;
        border: none;
        padding: 0;
    }

    .chart-container {
        height: 400px;
        position: relative;
    }

    /* Table Card */
    .table-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }

    .table-card h2 {
        font-size: 1.125rem;
        color: #2d3748;
        margin-bottom: 1rem;
        border: none;
        padding: 0;
    }

    .table-container {
        overflow-x: auto;
        margin: 0 -1.5rem;
        padding: 0 1.5rem;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .data-table th, .data-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .data-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #4a5568;
    }

    .data-table tr:hover {
        background: #f7fafc;
    }

    .data-table td {
        color: #4a5568;
    }

    .data-table td.numeric {
        text-align: right;
    }

    /* Trend Indicators */
    .trend-up {
        color: #38a169;
        font-weight: 600;
    }

    .trend-down {
        color: #e53e3e;
        font-weight: 600;
    }

    .trend-neutral {
        color: #718096;
    }

    .trend-arrow {
        margin-right: 0.25rem;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #718096;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<!-- Breadcrumb -->
<nav class="breadcrumb">
    <a href="{{ route('reports.index') }}">Reports</a>
    <span>›</span>
    <span>Monthly Trends</span>
</nav>

<!-- Header -->
<div class="page-header">
    <h1>Monthly Transaction Trends</h1>
    <p>Analyze transaction patterns and volume trends over time</p>
</div>

<!-- Control Card -->
<div class="control-card">
    <h2>Report Filters</h2>
    <form method="GET" action="{{ route('reports.monthly-trends') }}" class="control-row">
        <div class="form-group">
            <label for="year">Year</label>
            <select name="year" id="year" class="form-control">
                @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group">
            <label for="currency">Currency</label>
            <select name="currency" id="currency" class="form-control">
                <option value="all" {{ $currency === 'all' ? 'selected' : '' }}>All Currencies</option>
                @foreach($currencies as $curr)
                    <option value="{{ $curr }}" {{ $currency === $curr ? 'selected' : '' }}>{{ $curr }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="align-self: flex-end;">
            <button type="submit" class="btn btn-primary">Update Report</button>
        </div>
        <div class="form-group" style="align-self: flex-end;">
            <button type="button" class="btn btn-success" onclick="exportCSV()">Export CSV</button>
        </div>
    </form>
</div>

@if($monthlyData->isEmpty())
<div class="empty-state">
    <div class="empty-state-icon">📊</div>
    <h3>No Data Available</h3>
    <p>No completed transactions found for the selected filters.</p>
</div>
@else
<!-- Summary Cards -->
@php
    $totalTransactions = $monthlyData->sum('count');
    $totalVolume = $monthlyData->sum('total_volume');
    $avgMonthlyVolume = $monthlyData->count() > 0 ? $totalVolume / $monthlyData->count() : 0;
    $peakMonth = $monthlyData->sortByDesc('total_volume')->first();
@endphp
<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-card-label">Total Transactions ({{ $year }})</div>
        <div class="summary-card-value">{{ number_format($totalTransactions) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Total Volume (MYR)</div>
        <div class="summary-card-value">RM {{ number_format($totalVolume, 2) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Avg Monthly Volume</div>
        <div class="summary-card-value">RM {{ number_format($avgMonthlyVolume, 2) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Peak Month</div>
        <div class="summary-card-value">
            @if($peakMonth)
                {{ date('M', mktime(0, 0, 0, $peakMonth->month, 1)) }}
                (RM {{ number_format($peakMonth->total_volume, 0) }})
            @else
                -
            @endif
        </div>
    </div>
</div>

<!-- Chart Card -->
<div class="chart-card">
    <h2>Monthly Volume Trends (Buy vs Sell)</h2>
    <div class="chart-container">
        <canvas id="trendsChart"></canvas>
    </div>
</div>

<!-- Data Table -->
<div class="table-card">
    <h2>Monthly Breakdown</h2>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="numeric">Transactions</th>
                    <th class="numeric">Buy Volume (MYR)</th>
                    <th class="numeric">Sell Volume (MYR)</th>
                    <th class="numeric">Total Volume (MYR)</th>
                    <th class="numeric">MoM Trend</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyData as $row)
                @php
                    $trend = $trends[$row->month] ?? ['trend' => null, 'direction' => 'neutral'];
                    $trendClass = $trend['direction'] === 'up' ? 'trend-up' : ($trend['direction'] === 'down' ? 'trend-down' : 'trend-neutral');
                    $trendIcon = $trend['direction'] === 'up' ? '↑' : ($trend['direction'] === 'down' ? '↓' : '→');
                @endphp
                <tr>
                    <td>{{ date('F Y', mktime(0, 0, 0, $row->month, 1, $year)) }}</td>
                    <td class="numeric">{{ number_format($row->count) }}</td>
                    <td class="numeric">{{ number_format($row->buy_volume, 2) }}</td>
                    <td class="numeric">{{ number_format($row->sell_volume, 2) }}</td>
                    <td class="numeric">{{ number_format($row->total_volume, 2) }}</td>
                    <td class="numeric {{ $trendClass }}">
                        @if($trend['trend'] !== null)
                            <span class="trend-arrow">{{ $trendIcon }}</span>{{ number_format(abs($trend['trend']), 1) }}%
                        @else
                            <span class="trend-neutral">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if(!$monthlyData->isEmpty())
    const ctx = document.getElementById('trendsChart').getContext('2d');

    const months = {!! json_encode($monthlyData->pluck('month')->map(function($m) {
        return date('M', mktime(0, 0, 0, $m, 1));
    })) !!};

    const buyVolumes = {!! json_encode($monthlyData->pluck('buy_volume')) !!};
    const sellVolumes = {!! json_encode($monthlyData->pluck('sell_volume')) !!};

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Buy Volume (MYR)',
                    data: buyVolumes,
                    borderColor: '#38a169',
                    backgroundColor: 'rgba(56, 161, 105, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Sell Volume (MYR)',
                    data: sellVolumes,
                    borderColor: '#3182ce',
                    backgroundColor: 'rgba(49, 130, 206, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    @endif

    function exportCSV() {
        const year = document.getElementById('year').value;
        const currency = document.getElementById('currency').value;
        window.location.href = "{{ route('reports.export') }}?report_type=monthly_trends&period=" + year + "&currency=" + currency + "&format=CSV";
    }
</script>
@endsection