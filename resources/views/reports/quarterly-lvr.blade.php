@extends('layouts.app')

@section('title', 'Quarterly Large Value Report - CEMS-MY')

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
            <span class="breadcrumbs__text">Quarterly LVR</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Quarterly Large Value Transaction Report</h1>
        <p class="page-header__subtitle">Quarterly summary of transactions ≥ RM50,000 for BNM regulatory compliance</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <select name="quarter" id="quarter" class="form-select" onchange="document.getElementById('qlvr-form').submit()">
                @php
                $currentYear = now()->year;
                for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
                    for ($q = 4; $q >= 1; $q--) {
                        $val = $y . '-Q' . $q;
                        $selected = $val === $quarter ? 'selected' : '';
                        echo "<option value='{$val}' {$selected}>{$val}</option>";
                    }
                }
                @endphp
            </select>
        </form>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Report Controls</h3>
    </div>
    <div class="card-body">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex flex-wrap items-center gap-3">
                @php
                $status = $reportGenerated ? ($reportGenerated->status === 'Submitted' ? 'Submitted' : 'Generated') : 'Not Generated';
                @endphp
                <span class="status-badge status-badge--{{ strtolower($status) }}">{{ $status }}</span>
            </div>

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
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $reportData['period_start'] }} to {{ $reportData['period_end'] }}</div>
        <div class="stat-card__label">Period</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ number_format($reportData['total_transactions']) }}</div>
        <div class="stat-card__label">Total Transactions</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">RM {{ number_format($reportData['total_amount'], 2) }}</div>
        <div class="stat-card__label">Total Amount</div>
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
                    <th class="text-right">Transaction Count</th>
                    <th class="text-right">Total Amount (MYR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['monthly_breakdown'] as $month)
                <tr>
                    <td>{{ $month['month'] }}</td>
                    <td class="text-right">{{ number_format($month['count']) }}</td>
                    <td class="text-right">RM {{ number_format($month['total_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">By Currency</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Currency</th>
                    <th class="text-right">Transaction Count</th>
                    <th class="text-right">Total Amount (MYR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['by_currency'] as $currency)
                <tr>
                    <td>{{ $currency['currency'] }}</td>
                    <td class="text-right">{{ number_format($currency['count']) }}</td>
                    <td class="text-right">RM {{ number_format($currency['total_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<form id="qlvr-form" method="GET" action="{{ route('reports.quarterly-lvr') }}" class="hidden">
    @csrf
</form>
@endsection

@section('scripts')
<script>
const csrfToken = "{{ csrf_token() }}";
const quarter = "{{ $quarter }}";

async function generateReport() {
    if (!confirm('Generate Quarterly Large Value Report for ' + quarter + '?')) return;
    
    try {
        const response = await fetch('{{ route("reports.quarterly-lvr.generate") }}?quarter=' + encodeURIComponent(quarter));
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
    window.location.href = '{{ route("reports.quarterly-lvr.generate") }}?quarter=' + encodeURIComponent(quarter);
}
</script>
@endsection