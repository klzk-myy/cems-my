@extends('layouts.app')

@section('title', 'Import Results - CEMS-MY')

@section('styles')
<style>
    .results-header {
        margin-bottom: 1.5rem;
    }
    .results-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .results-header p {
        color: #718096;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-card h3 {
        color: #2d3748;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .summary-item {
        padding: 1rem;
        background: #f7fafc;
        border-radius: 6px;
    }

    .summary-item label {
        display: block;
        font-size: 0.875rem;
        color: #718096;
        margin-bottom: 0.25rem;
    }

    .summary-item .value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2d3748;
    }

    .summary-item.success .value {
        color: #38a169;
    }

    .summary-item.error .value {
        color: #e53e3e;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-success {
        background: #c6f6d5;
        color: #276749;
    }

    .badge-danger {
        background: #fed7d7;
        color: #c53030;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .data-table th,
    .data-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .data-table th {
        background: #edf2f7;
        font-weight: 600;
        color: #2d3748;
    }

    .data-table tr:hover {
        background: #f7fafc;
    }

    .error-section {
        margin-top: 2rem;
    }

    .error-card {
        background: #fff5f5;
        border: 1px solid #feb2b2;
        border-radius: 8px;
        padding: 1.5rem;
    }

    .error-card h3 {
        color: #c53030;
        margin-bottom: 1rem;
    }

    .error-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
    }

    .error-table th,
    .error-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #feb2b2;
    }

    .error-table th {
        background: #fed7d7;
        color: #c53030;
        font-weight: 600;
    }

    .error-row {
        font-family: monospace;
        font-size: 0.875rem;
        background: #fff;
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .error-message {
        color: #c53030;
        font-size: 0.875rem;
    }

    .actions {
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #718096;
    }
</style>
@endsection

@section('content')
<div class="results-header">
    <h2>Import Results</h2>
    <p>Details for import: <strong>{{ $import->original_filename }}</strong></p>
</div>

<!-- Summary Card -->
<div class="summary-card">
    <h3>Import Summary</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <label>Filename</label>
            <div class="value" style="font-size: 1rem;">{{ $import->original_filename }}</div>
        </div>
        <div class="summary-item">
            <label>Import Date</label>
            <div class="value" style="font-size: 1rem;">{{ $import->created_at->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="summary-item">
            <label>Total Rows</label>
            <div class="value">{{ $import->total_rows }}</div>
        </div>
        <div class="summary-item success">
            <label>Success</label>
            <div class="value">{{ $import->success_count }}</div>
        </div>
        <div class="summary-item {{ $import->error_count > 0 ? 'error' : '' }}">
            <label>Errors</label>
            <div class="value">{{ $import->error_count }}</div>
        </div>
        <div class="summary-item">
            <label>Status</label>
            <div class="value" style="font-size: 1rem;">
                <span class="badge badge-{{ $import->getStatusColor() }}">
                    {{ ucfirst($import->status) }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Successful Transactions -->
@if($import->success_count > 0)
<div class="card">
    <h3>Successful Transactions</h3>
    <p style="color: #38a169; margin-bottom: 1rem;">
        ✓ {{ $import->success_count }} transactions imported successfully
    </p>
    <p style="color: #718096; font-size: 0.875rem;">
        View all transactions in the <a href="{{ route('transactions.index') }}">Transaction History</a>
    </p>
</div>
@endif

<!-- Error Details -->
@if($import->hasErrors())
<div class="error-section">
    <div class="error-card">
        <h3>⚠️ Error Details ({{ count($import->getErrors()) }} errors)</h3>
        <p style="margin-bottom: 1rem;">The following rows could not be imported:</p>

        <div style="overflow-x: auto;">
            <table class="error-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Data</th>
                        <th>Error Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($import->getErrors() as $error)
                    <tr>
                        <td>{{ $error['row'] }}</td>
                        <td class="error-row" title="{{ implode(', ', $error['data']) }}">
                            {{ implode(', ', $error['data']) }}
                        </td>
                        <td class="error-message">{{ $error['error'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            <a href="{{ route('transactions.batch-upload') }}?export_errors={{ $import->id }}" class="btn btn-secondary">
                Export Errors to CSV
            </a>
        </div>
    </div>
</div>
@endif

<!-- Processing Time -->
@if($import->started_at && $import->completed_at)
<div class="card" style="margin-top: 2rem;">
    <h3>Processing Details</h3>
    <div class="summary-grid" style="margin-top: 1rem;">
        <div class="summary-item">
            <label>Started At</label>
            <div class="value" style="font-size: 1rem;">{{ $import->started_at->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="summary-item">
            <label>Completed At</label>
            <div class="value" style="font-size: 1rem;">{{ $import->completed_at->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="summary-item">
            <label>Duration</label>
            <div class="value" style="font-size: 1rem;">{{ $import->started_at->diffInSeconds($import->completed_at) }} seconds</div>
        </div>
    </div>
</div>
@endif

<div class="actions">
    <a href="{{ route('transactions.batch-upload') }}" class="btn btn-secondary">
        ← Back to Upload
    </a>
    <a href="{{ route('transactions.index') }}" class="btn">
        View All Transactions
    </a>
</div>
@endsection
