@extends('layouts.app')

@section('title', 'LCTR Report - CEMS-MY')

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
            <span class="breadcrumbs__text">LCTR</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">LCTR Report</h1>
        <p class="page-header__subtitle">Large Currency Transaction Report for Bank Negara Malaysia compliance</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="month" id="month" value="{{ $month }}" class="form-input" style="width: auto;" form="lctr-form">
            <button type="button" class="btn btn--secondary btn--sm" onclick="updateView()">Update View</button>
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
                $now = now();
                $selectedMonth = now()->parse($month);
                $periodEnd = $selectedMonth->copy()->endOfMonth();
                $deadline = $periodEnd->copy()->addDays(10);

                if ($reportGenerated) {
                    if ($reportGenerated->status === 'Submitted') {
                        $status = 'Submitted';
                        $statusClass = 'stat-card--success';
                    } elseif ($now->isAfter($deadline)) {
                        $status = 'Overdue';
                        $statusClass = 'stat-card--danger';
                    } else {
                        $status = 'Generated';
                        $statusClass = 'stat-card--primary';
                    }
                } else {
                    $status = 'Not Generated';
                    $statusClass = 'stat-card--warning';
                }
                @endphp

                <span class="status-badge status-badge--{{ strtolower($status) }}">{{ $status }}</span>

                @if($reportGenerated)
                <span class="text-sm text-gray-500">
                    Generated: {{ $reportGenerated->generated_at->format('M d, Y H:i') }}
                </span>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn--primary" onclick="generateReport()" {{ $reportGenerated ? 'disabled' : '' }}>
                    Generate Report
                </button>
                <button type="button" class="btn btn--success" onclick="downloadCSV()" {{ !$reportGenerated ? 'disabled' : '' }}>
                    Download CSV
                </button>
                <button type="button" class="btn btn--warning" onclick="markSubmitted()" {{ !$reportGenerated || $status === 'Submitted' ? 'disabled' : '' }}>
                    Mark as Submitted
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ number_format($stats['count']) }}</div>
        <div class="stat-card__label">Qualifying Transactions</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($stats['total_amount'], 2) }}</div>
        <div class="stat-card__label">Total Amount (MYR)</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ number_format($stats['unique_customers']) }}</div>
        <div class="stat-card__label">Unique Customers</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ now()->parse($month)->format('M Y') }}</div>
        <div class="stat-card__label">Report Period</div>
    </div>
</div>

<!-- Pending Transactions Alert -->
@if($pendingTransactions > 0)
<div class="alert alert-warning mb-6">
    <p>Warning: {{ $pendingTransactions }} qualifying transaction{{ $pendingTransactions > 1 ? 's are' : ' is' }} still pending approval and not included in this report.</p>
</div>
@endif

<!-- Transaction Preview Table -->
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Transaction Details</h3>
        <p class="text-sm text-gray-500 mt-1">Showing first {{ min($transactions->count(), 50) }} of {{ $transactions->count() }} transactions. Download CSV for complete report.</p>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Txn ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Customer ID</th>
                    <th>Customer Name</th>
                    <th>ID Type</th>
                    <th class="text-right">Amount (MYR)</th>
                    <th class="text-right">Amount (Foreign)</th>
                    <th>Currency</th>
                    <th>Type</th>
                    <th>Branch</th>
                    <th>Teller</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions->take(50) as $transaction)
                    @php
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
                        <td class="text-right">RM {{ number_format($transaction->amount_local, 2) }}</td>
                        <td class="text-right">{{ number_format($transaction->amount_foreign, 4) }}</td>
                        <td>{{ $transaction->currency_code }}</td>
                        <td>
                            <span class="status-badge status-badge--{{ strtolower($transaction->type->value ?? $transaction->type) }}">
                                {{ $transaction->type->value ?? $transaction->type }}
                            </span>
                        </td>
                        <td>MAIN</td>
                        <td>{{ $transaction->user_id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-8 text-gray-500">
                            No qualifying transactions found for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($transactions->count() > 50)
        <div class="text-center py-4 text-gray-500 italic border-t border-gray-200">
            ... and {{ $transactions->count() - 50 }} more transactions
        </div>
        @endif
    </div>
</div>

<form id="lctr-form" method="GET" action="{{ route('reports.lctr') }}" class="hidden">
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
            const response = await fetch('{{ route("api.reports.lctr.status") }}', {
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
