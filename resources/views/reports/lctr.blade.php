@extends('layouts.app')

@section('title', 'LCTR Report - CEMS-MY')

@section('content')
<!-- Breadcrumb -->
<nav class="flex items-center gap-2 mb-4 text-sm text-gray-500">
    <a href="{{ route('reports.index') }}" class="text-blue-600 no-underline hover:underline">Reports</a>
    <span>›</span>
    <span>LCTR</span>
</nav>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-blue-900 mb-1">LCTR Report</h1>
    <p class="text-gray-500 text-sm">Large Currency Transaction Report for Bank Negara Malaysia compliance</p>
</div>

<!-- Control Card -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Report Controls</h2>
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex flex-col gap-1">
            <label for="month" class="text-sm font-medium text-gray-600">Select Month</label>
            <input type="month" id="month" name="month" value="{{ $month }}" class="p-2 border border-gray-200 rounded text-sm min-w-48" form="lctr-form">
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @php
                $now = now();
                $selectedMonth = now()->parse($month);
                $periodEnd = $selectedMonth->copy()->endOfMonth();
                $deadline = $periodEnd->copy()->addDays(10);

                if ($reportGenerated) {
                    if ($reportGenerated->status === 'Submitted') {
                        $status = 'Submitted';
                        $statusClass = 'bg-green-100 text-green-800';
                    } elseif ($now->isAfter($deadline)) {
                        $status = 'Overdue';
                        $statusClass = 'bg-red-100 text-red-800';
                    } else {
                        $status = 'Generated';
                        $statusClass = 'bg-blue-100 text-blue-800';
                    }
                } else {
                    $status = 'Not Generated';
                    $statusClass = 'bg-gray-200 text-gray-600';
                }
            @endphp

            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>

            @if($reportGenerated)
                <span class="text-sm text-gray-500">
                    Generated: {{ $reportGenerated->generated_at->format('M d, Y H:i') }}
                </span>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors" onclick="updateView()">
                Update View
            </button>
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors" onclick="generateReport()" {{ $reportGenerated ? 'disabled' : '' }}>
                Generate Report
            </button>
            <button type="button" class="px-4 py-2 bg-green-600 text-white rounded font-semibold text-sm hover:bg-green-700 transition-colors" onclick="downloadCSV()" {{ !$reportGenerated ? 'disabled' : '' }}>
                Download CSV
            </button>
            <button type="button" class="px-4 py-2 bg-yellow-500 text-white rounded font-semibold text-sm hover:bg-yellow-600 transition-colors" onclick="markSubmitted()" {{ !$reportGenerated || $status === 'Submitted' ? 'disabled' : '' }}>
                Mark as Submitted
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Qualifying Transactions</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['count']) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Total Amount (MYR)</div>
        <div class="text-2xl font-bold text-gray-800">RM {{ number_format($stats['total_amount'], 2) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Unique Customers</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['unique_customers']) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Report Period</div>
        <div class="text-2xl font-bold text-gray-800">{{ now()->parse($month)->format('M Y') }}</div>
    </div>
</div>

<!-- Pending Transactions Alert -->
@if($pendingTransactions > 0)
<div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6 rounded-r-lg">
    <p class="text-orange-800 text-sm">⚠️ Warning: {{ $pendingTransactions }} qualifying transaction{{ $pendingTransactions > 1 ? 's are' : ' is' }} still pending approval and not included in this report.</p>
</div>
@endif

<!-- Transaction Preview Table -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-2">Transaction Details</h2>
    <p class="text-sm text-gray-500 mb-4">
        📋 Showing first {{ min($transactions->count(), 50) }} of {{ $transactions->count() }} transactions. Download CSV for complete report.
    </p>

    <div class="overflow-x-auto -mx-6 px-6">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Txn ID</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Date</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Time</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Customer ID</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Customer Name</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">ID Type</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Amount (MYR)</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Amount (Foreign)</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Currency</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Type</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Branch</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Teller</th>
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
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 border-b border-gray-100">{{ $transaction->id }}</td>
                        <td class="px-4 py-3 border-b border-gray-100">{{ $transaction->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 border-b border-gray-100">{{ $transaction->created_at->format('H:i:s') }}</td>
                        <td class="px-4 py-3 border-b border-gray-100">{{ $transaction->customer_id }}</td>
                        <td class="px-4 py-3 border-b border-gray-100">{{ $maskedName }}</td>
                        <td class="px-4 py-3 border-b border-gray-100">{{ $transaction->customer->id_type ?? 'N/A' }}</td>
                        <td class="px-4 py-3 border-b border-gray-100 text-right">RM {{ number_format($transaction->amount_local, 2) }}</td>
                        <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($transaction->amount_foreign, 4) }}</td>
                        <td class="px-4 py-3 border-b border-gray-100">{{ $transaction->currency_code }}</td>
                        <td class="px-4 py-3 border-b border-gray-100">
                            <span class="inline-flex px-2 py-1 rounded text-xs font-semibold {{ strtolower($transaction->type->value ?? $transaction->type) === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $transaction->type->value ?? $transaction->type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 border-b border-gray-100">MAIN</td>
                        <td class="px-4 py-3 border-b border-gray-100">{{ $transaction->user_id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                            No qualifying transactions found for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($transactions->count() > 50)
        <div class="text-center py-4 text-gray-500 italic">
            ... and {{ $transactions->count() - 50 }} more transactions
        </div>
        @endif
    </div>
</div>

<!-- Compliance Footer -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 rounded-lg p-5 mt-6">
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Threshold</span>
        <span class="text-sm font-semibold text-gray-800">RM 25,000 per transaction</span>
    </div>
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Submission Deadline</span>
        <span class="text-sm font-semibold text-gray-800">10th of following month</span>
    </div>
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Next Deadline</span>
        <span class="text-sm font-semibold text-gray-800">
            {{ now()->parse($month)->endOfMonth()->addDays(10)->format('M d, Y') }}
        </span>
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
