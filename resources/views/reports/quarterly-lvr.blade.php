@extends('layouts.app')

@section('title', 'Quarterly Large Value Report - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('reports.index') }}">Reports</a>
    <span>›</span>
    <span>Quarterly Large Value Report</span>
</nav>

<div class="page-header">
    <h1>Quarterly Large Value Transaction Report</h1>
    <p>Quarterly summary of transactions ≥ RM50,000 for BNM regulatory compliance</p>
</div>

<div class="control-card">
    <form method="GET" action="{{ route('reports.quarterly-lvr') }}" id="qlvr-form">
        <div class="form-group">
            <label for="quarter">Select Quarter</label>
            <select id="quarter" name="quarter" class="form-control" onchange="document.getElementById('qlvr-form').submit()">
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
        </div>
    </form>

    <div class="status-info">
        @php
        $status = $reportGenerated ? ($reportGenerated->status === 'Submitted' ? 'Submitted' : 'Generated') : 'Not Generated';
        $statusClass = $reportGenerated ? ($reportGenerated->status === 'Submitted' ? 'status-submitted' : 'status-generated') : 'status-not-generated';
        @endphp
        <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
    </div>

    <div class="button-group">
        <button type="button" class="btn btn-primary" onclick="generateReport()" {{ $reportGenerated ? 'disabled' : '' }}>
            Generate Report
        </button>
        <button type="button" class="btn btn-success" onclick="downloadCSV()" {{ !$reportGenerated ? 'disabled' : '' }}>
            Download CSV
        </button>
    </div>
</div>

<div class="info-card">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Period</span>
            <span class="info-value">{{ $reportData['period_start'] }} to {{ $reportData['period_end'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Total Transactions</span>
            <span class="info-value">{{ number_format($reportData['total_transactions']) }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Total Amount</span>
            <span class="info-value">RM {{ number_format($reportData['total_amount'], 2) }}</span>
        </div>
    </div>
</div>

<div class="table-card">
    <h2>Monthly Breakdown</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Month</th>
                <th>Transaction Count</th>
                <th>Total Amount (MYR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['monthly_breakdown'] as $month)
            <tr>
                <td>{{ $month['month'] }}</td>
                <td>{{ number_format($month['count']) }}</td>
                <td>RM {{ number_format($month['total_amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="table-card">
    <h2>By Currency</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Currency</th>
                <th>Transaction Count</th>
                <th>Total Amount (MYR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['by_currency'] as $currency)
            <tr>
                <td>{{ $currency['currency'] }}</td>
                <td>{{ number_format($currency['count']) }}</td>
                <td>RM {{ number_format($currency['total_amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
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