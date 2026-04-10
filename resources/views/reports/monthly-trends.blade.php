@extends('layouts.app')

@section('title', 'Monthly Trends Report - CEMS-MY')

@section('content')
<!-- Breadcrumb -->
<nav class="flex items-center gap-2 mb-4 text-sm text-gray-500">
    <a href="{{ route('reports.index') }}" class="text-blue-600 no-underline hover:underline">Reports</a>
    <span>›</span>
    <span>Monthly Trends</span>
</nav>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-blue-900 mb-1">Monthly Transaction Trends</h1>
    <p class="text-gray-500 text-sm">Analyze transaction patterns and volume trends over time</p>
</div>

<!-- Control Card -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Report Filters</h2>
    <form method="GET" action="{{ route('reports.monthly-trends') }}" class="flex flex-wrap items-end gap-4">
        <div class="flex flex-col gap-1">
            <label for="year" class="text-sm font-medium text-gray-600">Year</label>
            <select name="year" id="year" class="p-2 border border-gray-200 rounded text-sm min-w-32">
                @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label for="currency" class="text-sm font-medium text-gray-600">Currency</label>
            <select name="currency" id="currency" class="p-2 border border-gray-200 rounded text-sm min-w-40">
                <option value="all" {{ $currency === 'all' ? 'selected' : '' }}>All Currencies</option>
                @foreach($currencies as $curr)
                    <option value="{{ $curr }}" {{ $currency === $curr ? 'selected' : '' }}>{{ $curr }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1 self-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors">Update Report</button>
        </div>
        <div class="flex flex-col gap-1 self-end">
            <button type="button" class="px-4 py-2 bg-green-600 text-white rounded font-semibold text-sm hover:bg-green-700 transition-colors" onclick="exportCSV()">Export CSV</button>
        </div>
    </form>
</div>

@if($monthlyData->isEmpty())
<div class="text-center py-12 text-gray-500">
    <div class="text-5xl mb-4">📊</div>
    <h3 class="text-lg font-semibold text-gray-700 mb-2">No Data Available</h3>
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
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="text-sm text-gray-500 mb-2">Total Transactions ({{ $year }})</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($totalTransactions) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="text-sm text-gray-500 mb-2">Total Volume (MYR)</div>
        <div class="text-2xl font-bold text-gray-800">RM {{ number_format($totalVolume, 2) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="text-sm text-gray-500 mb-2">Avg Monthly Volume</div>
        <div class="text-2xl font-bold text-gray-800">RM {{ number_format($avgMonthlyVolume, 2) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="text-sm text-gray-500 mb-2">Peak Month</div>
        <div class="text-2xl font-bold text-gray-800">
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
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Monthly Volume Trends (Buy vs Sell)</h2>
    <div class="h-96 relative">
        <canvas id="trendsChart"></canvas>
    </div>
</div>

<!-- Data Table -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Monthly Breakdown</h2>
    <div class="overflow-x-auto -mx-6 px-6">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Month</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Transactions</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Buy Volume (MYR)</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Sell Volume (MYR)</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Total Volume (MYR)</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">MoM Trend</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyData as $row)
                @php
                    $trend = $trends[$row->month] ?? ['trend' => null, 'direction' => 'neutral'];
                    $trendClass = $trend['direction'] === 'up' ? 'text-green-600 font-semibold' : ($trend['direction'] === 'down' ? 'text-red-600 font-semibold' : 'text-gray-500');
                    $trendIcon = $trend['direction'] === 'up' ? '↑' : ($trend['direction'] === 'down' ? '↓' : '→');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-700">{{ date('F Y', mktime(0, 0, 0, $row->month, 1, $year)) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-700 text-right">{{ number_format($row->count) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-700 text-right">{{ number_format($row->buy_volume, 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-700 text-right">{{ number_format($row->sell_volume, 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-700 text-right">{{ number_format($row->total_volume, 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right {{ $trendClass }}">
                        @if($trend['trend'] !== null)
                            <span class="mr-1">{{ $trendIcon }}</span>{{ number_format(abs($trend['trend']), 1) }}%
                        @else
                            <span class="text-gray-500">-</span>
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
