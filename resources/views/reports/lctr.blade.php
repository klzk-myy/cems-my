@extends('layouts.app')

@section('title', 'LCTR Report - CEMS-MY')

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

    .status-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-not-generated {
        background: #e2e8f0;
        color: #4a5568;
    }

    .status-generated {
        background: #bee3f8;
        color: #2c5282;
    }

    .status-submitted {
        background: #c6f6d5;
        color: #276749;
    }

    .status-overdue {
        background: #fed7d7;
        color: #c53030;
    }

    .timestamp {
        font-size: 0.875rem;
        color: #718096;
    }

    .button-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
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

    .btn-warning {
        background: #d69e2e;
        color: white;
    }

    .btn-warning:hover {
        background: #b7791f;
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.25rem;
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

    /* Compliance Alert */
    .compliance-alert {
        background: #fffaf0;
        border-left: 4px solid #dd6b20;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 0 4px 4px 0;
    }

    .compliance-alert.warning {
        background: #fffaf0;
        border-left-color: #dd6b20;
    }

    /* Transaction Table */
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
        margin-bottom: 0.5rem;
        border: none;
        padding: 0;
    }

    .table-info {
        font-size: 0.875rem;
        color: #718096;
        margin-bottom: 1rem;
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

    .data-table th,
    .data-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .data-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #4a5568;
    }

    .data-table tr:hover {
        background: #f7fafc;
    }

    .type-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .type-buy {
        background: #c6f6d5;
        color: #276749;
    }

    .type-sell {
        background: #fed7d7;
        color: #c53030;
    }

    .more-row {
        text-align: center;
        color: #718096;
        font-style: italic;
    }

    /* Compliance Footer */
    .compliance-footer {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.25rem;
        margin-top: 1.5rem;
    }

    @media (max-width: 768px) {
        .compliance-footer {
            grid-template-columns: 1fr;
        }
    }

    .compliance-item {
        display: flex;
        flex-direction: column;
    }

    .compliance-label {
        font-size: 0.75rem;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }

    .compliance-value {
        font-size: 0.875rem;
        font-weight: 600;
        color: #2d3748;
    }
</style>
@endsection

@section('content')
<!-- Breadcrumb -->
<nav class="breadcrumb">
    <a href="{{ route('reports') }}">Reports</a>
    <span>›</span>
    <span>LCTR</span>
</nav>

<!-- Header -->
<div class="page-header">
    <h1>LCTR Report</h1>
    <p>Large Currency Transaction Report for Bank Negara Malaysia compliance</p>
</div>

<!-- Control Card -->
<div class="control-card">
    <h2>Report Controls</h2>
    <div class="control-row">
        <div class="form-group">
            <label for="month">Select Month</label>
            <input type="month" id="month" name="month" value="{{ $month }}" class="form-control" form="lctr-form">
        </div>

        <div class="status-info">
            @php
                $now = now();
                $selectedMonth = now()->parse($month);
                $periodEnd = $selectedMonth->copy()->endOfMonth();
                $deadline = $periodEnd->copy()->addDays(10);
                
                if ($reportGenerated) {
                    if ($reportGenerated->status === 'Submitted') {
                        $status = 'Submitted';
                        $statusClass = 'status-submitted';
                    } elseif ($now->isAfter($deadline)) {
                        $status = 'Overdue';
                        $statusClass = 'status-overdue';
                    } else {
                        $status = 'Generated';
                        $statusClass = 'status-generated';
                    }
                } else {
                    $status = 'Not Generated';
                    $statusClass = 'status-not-generated';
                }
            @endphp

            <span class="status-badge {{ $statusClass }}">{{ $status }}</span>

            @if($reportGenerated)
                <span class="timestamp">
                    Generated: {{ $reportGenerated->generated_at->format('M d, Y H:i') }}
                </span>
            @endif
        </div>

        <div class="button-group">
            <button type="button" class="btn btn-primary" onclick="updateView()">
                Update View
            </button>
            <button type="button" class="btn btn-primary" onclick="generateReport()" {{ $reportGenerated ? 'disabled' : '' }}>
                Generate Report
            </button>
            <button type="button" class="btn btn-success" onclick="downloadCSV()" {{ !$reportGenerated ? 'disabled' : '' }}>
                Download CSV
            </button>
            <button type="button" class="btn btn-warning" onclick="markSubmitted()" {{ !$reportGenerated || $status === 'Submitted' ? 'disabled' : '' }}>
                Mark as Submitted
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-card-label">Qualifying Transactions</div>
        <div class="summary-card-value">{{ number_format($stats['count']) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Total Amount (MYR)</div>
        <div class="summary-card-value">RM {{ number_format($stats['total_amount'], 2) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Unique Customers</div>
        <div class="summary-card-value">{{ number_format($stats['unique_customers']) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Report Period</div>
        <div class="summary-card-value">{{ now()->parse($month)->format('M Y') }}</div>
    </div>
</div>

<!-- Pending Transactions Alert -->
@if($pendingTransactions > 0)
<div class="compliance-alert warning">
    ⚠️ Warning: {{ $pendingTransactions }} qualifying transaction{{ $pendingTransactions > 1 ? 's are' : ' is' }} still pending approval and not included in this report.
</div>
@endif

<!-- Transaction Preview Table -->
<div class="table-card">
    <h2>Transaction Details</h2>
    <p class="table-info">
        📋 Showing first {{ min($transactions->count(), 50) }} of {{ $transactions->count() }} transactions. Download CSV for complete report.
    </p>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Txn ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Customer ID</th>
                    <th>Customer Name</th>
                    <th>ID Type</th>
                    <th>Amount (MYR)</th>
                    <th>Amount (Foreign)</th>
                    <th>Currency</th>
                    <th>Type</th>
                    <th>Branch</th>
                    <th>Teller</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions->take(50) as $transaction)
                    @php
                        // Mask customer name: first 2 chars + asterisks + last 2 chars
                        $customerName = $transaction->customer->full_name ?? 'Unknown';
                        $maskedName = strlen($customerName) > 4 
                            ? substr($customerName, 0, 2) . str_repeat('*', strlen($customerName) - 4) . substr($customerName, -2)
                            : $customerName;
                    @endphp
                    <tr>
                        <td>{{ $transaction->id }}</td>
                        <td>{{ $transaction->created_at->format('Y-m-d') }}</td>
                        <td>{{ $transaction->created_at->format('H:i:s') }}</td>
                        <td>{{ $transaction->customer_id }}</td>
                        <td>{{ $maskedName }}</td>
                        <td>{{ $transaction->customer->id_type ?? 'N/A' }}</td>
                        <td>RM {{ number_format($transaction->amount_local, 2) }}</td>
                        <td>{{ number_format($transaction->amount_foreign, 4) }}</td>
                        <td>{{ $transaction->currency_code }}</td>
                        <td>
                            <span class="type-badge type-{{ strtolower($transaction->type) }}">
                                {{ $transaction->type }}
                            </span>
                        </td>
                        <td>MAIN</td>
                        <td>{{ $transaction->user_id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 2rem; color: #718096;">
                            No qualifying transactions found for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($transactions->count() > 50)
        <div class="more-row">
            ... and {{ $transactions->count() - 50 }} more transactions
        </div>
        @endif
    </div>
</div>

<!-- Compliance Footer -->
<div class="compliance-footer">
    <div class="compliance-item">
        <span class="compliance-label">Threshold</span>
        <span class="compliance-value">RM 25,000 per transaction</span>
    </div>
    <div class="compliance-item">
        <span class="compliance-label">Submission Deadline</span>
        <span class="compliance-value">10th of following month</span>
    </div>
    <div class="compliance-item">
        <span class="compliance-label">Next Deadline</span>
        <span class="compliance-value">
            {{ now()->parse($month)->endOfMonth()->addDays(10)->format('M d, Y') }}
        </span>
    </div>
</div>

<form id="lctr-form" method="GET" action="{{ route('reports.lctr') }}" style="display: none;">
    @csrf
</form>
@endsection

@section('scripts')
<script>
    const routeLCTR = "{{ route('reports.lctr') }}";
    const routeAPILCTR = "{{ route('api.reports.lctr') }}";
    const routeExport = "{{ route('reports.export') }}";
    const csrfToken = "{{ csrf_token() }}";

    function updateView() {
        const month = document.getElementById('month').value;
        window.location.href = routeLCTR + '?month=' + encodeURIComponent(month);
    }

    async function generateReport() {
        const month = document.getElementById('month').value;
        
        if (!confirm('Generate LCTR report for ' + month + '?')) {
            return;
        }

        try {
            const response = await fetch(routeAPILCTR, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ month: month })
            });

            const data = await response.json();

            if (response.ok) {
                alert('Report generated successfully!');
                window.location.reload();
            } else {
                alert('Failed to generate report: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Failed to generate report: ' + error.message);
        }
    }

    function downloadCSV() {
        const month = document.getElementById('month').value;
        window.location.href = routeExport + '?report_type=lctr&period=' + encodeURIComponent(month) + '&format=CSV';
    }

    async function markSubmitted() {
        const month = document.getElementById('month').value;
        
        if (!confirm('Mark this report as submitted to Bank Negara Malaysia? This action cannot be undone.')) {
            return;
        }

        try {
            // Update the report status via AJAX
            const response = await fetch(routeAPILCTR + '/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ 
                    month: month,
                    status: 'Submitted'
                })
            });

            if (response.ok) {
                alert('Report marked as submitted!');
                window.location.reload();
            } else {
                const data = await response.json();
                alert('Failed to update status: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Failed to update status: ' + error.message);
        }
    }
</script>
@endsection
