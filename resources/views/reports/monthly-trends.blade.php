@extends('layouts.app')

@section('title', 'Monthly Trends Report - CEMS-MY')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Monthly Transaction Trends</h1>
        <p class="page-header__subtitle">Analyze transaction patterns and volume trends over time</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Report Filters</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.monthly-trends') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="currency" class="form-label">Currency</label>
                <select name="currency" id="currency" class="form-select">
                    <option value="all" {{ $currency === 'all' ? 'selected' : '' }}>All Currencies</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr }}" {{ $currency === $curr ? 'selected' : '' }}>{{ $curr }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn--primary">Update Report</button>
            <button type="button" class="btn btn--success" onclick="exportCSV()">Export CSV</button>
        </form>
    </div>
</div>

@if($monthlyData->isEmpty())
<div class="card">
    <div class="card-body text-center">
        <div class="text-5xl mb-4 text-gray-300">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">No Data Available</h3>
        <p class="text-gray-500">No completed transactions found for the selected filters.</p>
    </div>
</div>
@else
@php
$totalTransactions = $monthlyData->sum('count');
$totalVolume = $monthlyData->sum('total_volume');
$avgMonthlyVolume = $monthlyData->count() > 0 ? $totalVolume / $monthlyData->count() : 0;
$peakMonth = $monthlyData->sortByDesc('total_volume')->first();
@endphp

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ number_format($totalTransactions) }}</div>
        <div class="stat-card__label">Total Transactions ({{ $year }})</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($totalVolume, 2) }}</div>
        <div class="stat-card__label">Total Volume (MYR)</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">RM {{ number_format($avgMonthlyVolume, 2) }}</div>
        <div class="stat-card__label">Avg Monthly Volume</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">
            @if($peakMonth)
                {{ date('M', mktime(0, 0, 0, $peakMonth->month, 1)) }}
            @else
                -
            @endif
        </div>
        <div class="stat-card__label">Peak Month</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Monthly Breakdown</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Buy Volume (MYR)</th>
                    <th class="text-right">Sell Volume (MYR)</th>
                    <th class="text-right">Total Volume (MYR)</th>
                    <th class="text-right">MoM Trend</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyData as $row)
                @php
                    $trend = $trends[$row->month] ?? ['trend' => null, 'direction' => 'neutral'];
                    $trendClass = $trend['direction'] === 'up' ? 'text-green-600' : ($trend['direction'] === 'down' ? 'text-red-600' : 'text-gray-500');
                    $trendIcon = $trend['direction'] === 'up' ? '↑' : ($trend['direction'] === 'down' ? '↓' : '→');
                @endphp
                <tr>
                    <td>{{ date('F Y', mktime(0, 0, 0, $row->month, 1, $year)) }}</td>
                    <td class="text-right">{{ number_format($row->count) }}</td>
                    <td class="text-right">{{ number_format($row->buy_volume, 2) }}</td>
                    <td class="text-right">{{ number_format($row->sell_volume, 2) }}</td>
                    <td class="text-right">{{ number_format($row->total_volume, 2) }}</td>
                    <td class="text-right {{ $trendClass }}">
                        @if($trend['trend'] !== null)
                            <span>{{ $trendIcon }} {{ number_format(abs($trend['trend']), 1) }}%</span>
                        @else
                            <span>-</span>
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
