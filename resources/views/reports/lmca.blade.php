@extends('layouts.app')

@section('title', 'BNM Form LMCA - CEMS-MY')

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
            <span class="breadcrumbs__text">LMCA</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">BNM Form LMCA</h1>
        <p class="page-header__subtitle">Monthly Regulatory Report for Bank Negara Malaysia</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="month" id="month" value="{{ $month }}" class="form-input" style="width: auto;" form="lmca-form">
            <button type="button" class="btn btn--secondary btn--sm" onclick="document.getElementById('lmca-form').submit()">Update View</button>
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
                if ($reportGenerated) {
                    if ($reportGenerated->status === 'Submitted') {
                        $status = 'Submitted';
                        $statusClass = 'stat-card--success';
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

<!-- Info Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $reportData['license_number'] }}</div>
        <div class="stat-card__label">License Number</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $reportData['reporting_period'] }}</div>
        <div class="stat-card__label">Reporting Period</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ number_format($reportData['customer_count']) }}</div>
        <div class="stat-card__label">Customers Served</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ number_format($reportData['staff_count']) }}</div>
        <div class="stat-card__label">Active Staff</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Currency Summary</h3>
    </div>
    <div class="card-body p-0">
        @if(empty($reportData['currencies']))
        <div class="text-center py-12 text-gray-500">
            <p>No transaction data available for this period.</p>
        </div>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2">Currency</th>
                    <th colspan="3" class="text-center">Buy Transactions</th>
                    <th colspan="3" class="text-center">Sell Transactions</th>
                    <th colspan="2" class="text-center">Stock Position</th>
                </tr>
                <tr>
                    <th class="text-right">Count</th>
                    <th class="text-right">Volume (Foreign)</th>
                    <th class="text-right">Value (MYR)</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Volume (Foreign)</th>
                    <th class="text-right">Value (MYR)</th>
                    <th class="text-right">Opening</th>
                    <th class="text-right">Closing</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totals = [
                    'buy_count' => 0,
                    'buy_volume' => 0,
                    'buy_value' => 0,
                    'sell_count' => 0,
                    'sell_volume' => 0,
                    'sell_value' => 0,
                ];
                @endphp

                @foreach($reportData['currencies'] as $currency)
                @php
                $totals['buy_count'] += $currency['buy_count'];
                $totals['buy_volume'] += $currency['buy_volume'];
                $totals['buy_value'] += $currency['buy_value_myr'];
                $totals['sell_count'] += $currency['sell_count'];
                $totals['sell_volume'] += $currency['sell_volume'];
                $totals['sell_value'] += $currency['sell_value_myr'];
                @endphp
                <tr>
                    <td><strong>{{ $currency['currency_code'] }}</strong><br><small class="text-gray-500">{{ $currency['currency_name'] }}</small></td>
                    <td class="text-right">{{ number_format($currency['buy_count']) }}</td>
                    <td class="text-right">{{ number_format($currency['buy_volume'], 4) }}</td>
                    <td class="text-right">RM {{ number_format($currency['buy_value_myr'], 2) }}</td>
                    <td class="text-right">{{ number_format($currency['sell_count']) }}</td>
                    <td class="text-right">{{ number_format($currency['sell_volume'], 4) }}</td>
                    <td class="text-right">RM {{ number_format($currency['sell_value_myr'], 2) }}</td>
                    <td class="text-right">{{ number_format($currency['opening_stock'], 4) }}</td>
                    <td class="text-right">{{ number_format($currency['closing_stock'], 4) }}</td>
                </tr>
                @endforeach

                <tr class="bg-gray-50 font-semibold">
                    <td><strong>Grand Total</strong></td>
                    <td class="text-right">{{ number_format($totals['buy_count']) }}</td>
                    <td class="text-right">{{ number_format($totals['buy_volume'], 4) }}</td>
                    <td class="text-right">RM {{ number_format($totals['buy_value'], 2) }}</td>
                    <td class="text-right">{{ number_format($totals['sell_count']) }}</td>
                    <td class="text-right">{{ number_format($totals['sell_volume'], 4) }}</td>
                    <td class="text-right">RM {{ number_format($totals['sell_value'], 2) }}</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrfToken = "{{ csrf_token() }}";
const month = "{{ $month }}";

async function generateReport() {
    if (!confirm('Generate BNM Form LMCA for ' + month + '?')) {
        return;
    }

    try {
        const response = await fetch('{{ route("reports.lmca.generate") }}?month=' + encodeURIComponent(month), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            }
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
    window.location.href = '{{ route("reports.lmca.generate") }}?month=' + encodeURIComponent(month);
}

async function markSubmitted() {
    if (!confirm('Mark this report as submitted to Bank Negara Malaysia? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('{{ route("reports.lmca.generate") }}', {
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
