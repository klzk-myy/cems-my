@extends('layouts.app')

@section('title', 'Position Limit Report - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('reports.index') }}" class="breadcrumbs__link">Reports</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Position Limits</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Position Limit Utilization Report</h1>
        <p class="page-header__subtitle">Monitor currency position limits and exposure levels</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Report Controls</h3>
    </div>
    <div class="card-body">
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn--primary" onclick="generateReport()" {{ $reportGenerated ? 'disabled' : '' }}>
                Generate Report
            </button>
            <button type="button" class="btn btn--success" onclick="downloadCSV()" {{ !$reportGenerated ? 'disabled' : '' }}>
                Download CSV
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $reportData['summary']['total_currencies'] }}</div>
        <div class="stat-card__label">Total Currencies</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">RM {{ number_format((float)$reportData['total_exposure_myr'], 2) }}</div>
        <div class="stat-card__label">Total Exposure (MYR)</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $reportData['summary']['currencies_at_warning'] }}</div>
        <div class="stat-card__label">At Warning (≥75%)</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $reportData['summary']['currencies_at_critical'] }}</div>
        <div class="stat-card__label">At Critical (≥90%)</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Currency Positions</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Currency</th>
                    <th class="text-right">Current Balance</th>
                    <th class="text-right">Position Limit</th>
                    <th>Utilization</th>
                    <th class="text-right">Avg Cost Rate</th>
                    <th class="text-right">Valuation Rate</th>
                    <th class="text-right">Exposure (MYR)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['positions'] as $position)
                <tr>
                    <td><strong>{{ $position['currency_code'] }}</strong><br><small class="text-gray-500">{{ $position['currency_name'] }}</small></td>
                    <td class="text-right">{{ number_format((float)$position['current_balance'], 4) }}</td>
                    <td class="text-right">{{ $position['position_limit'] ? number_format((float)$position['position_limit'], 4) : 'N/A' }}</td>
                    <td>
                        @if($position['position_limit'])
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 100px; background: #e2e8f0; border-radius: 4px; height: 8px;">
                                    <div style="width: {{ min($position['utilization_percent'], 100) }}%; background: {{ $position['status'] === 'Critical' ? '#e53e3e' : ($position['status'] === 'Warning' ? '#d69e2e' : '#38a169') }}; height: 100%; border-radius: 4px;"></div>
                                </div>
                                <span>{{ $position['utilization_percent'] }}%</span>
                            </div>
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float)$position['avg_cost_rate'], 4) }}</td>
                    <td class="text-right">{{ number_format((float)$position['last_valuation_rate'], 4) }}</td>
                    <td class="text-right">RM {{ number_format((float)$position['exposure_myr'], 2) }}</td>
                    <td>
                        <span class="status-badge status-badge--{{ strtolower($position['status']) }}">
                            {{ $position['status'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrfToken = "{{ csrf_token() }}";

async function generateReport() {
    if (!confirm('Generate Position Limit Report?')) return;
    
    try {
        const response = await fetch('{{ route("reports.position-limit.generate") }}');
        const data = await response.json();
        if (response.ok) {
            alert('Report generated successfully!');
            window.location.reload();
        } else {
            alert('Failed: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        alert('Failed: ' + error.message);
    }
}

function downloadCSV() {
    window.location.href = '{{ route("reports.position-limit.generate") }}';
}
</script>
@endsection