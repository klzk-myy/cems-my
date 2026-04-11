@extends('layouts.app')

@section('title', 'MSB(2) Report - CEMS-MY')

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
            <span class="breadcrumbs__text">MSB(2)</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">MSB(2) Report</h1>
        <p class="page-header__subtitle">Daily Money Services Business Transaction Summary</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date" id="date" value="{{ $date }}" class="form-input" style="width: auto;" form="msb2-form">
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

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ number_format($stats['total_transactions']) }}</div>
        <div class="stat-card__label">Total Transactions</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($stats['total_buy_myr'], 2) }}</div>
        <div class="stat-card__label">Total Buy Volume (MYR)</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">RM {{ number_format($stats['total_sell_myr'], 2) }}</div>
        <div class="stat-card__label">Total Sell Volume (MYR)</div>
    </div>
    <div class="stat-card {{ $stats['net_position'] >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">{{ $stats['net_position'] >= 0 ? '+' : '' }}RM {{ number_format($stats['net_position'], 2) }}</div>
        <div class="stat-card__label">Net Position</div>
    </div>
</div>

<!-- Validation Alerts -->
@if($stats['net_position'] < 0)
<div class="alert alert-warning mb-6">
    <p>Validation Notice: Negative net position indicates more sales than purchases for this period.</p>
</div>
@endif

@if($isToday)
<div class="alert alert-info mb-6">
    <p>Note: You are viewing today's data. The report should typically be generated for the previous completed business day.</p>
</div>
@endif

<!-- Currency Summary Table -->
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Currency Summary</h3>
    </div>
    <div class="card-body p-0">
        @if($summary->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            <p>No transactions found for {{ $date }}. Select a different date or check if transactions have been recorded.</p>
        </div>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2">Currency</th>
                    <th colspan="3" class="text-center">Buy Transactions</th>
                    <th colspan="3" class="text-center">Sell Transactions</th>
                    <th colspan="2" class="text-center">Net</th>
                </tr>
                <tr>
                    <th class="text-right">Volume (Foreign)</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Amount (MYR)</th>
                    <th class="text-right">Volume (Foreign)</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Amount (MYR)</th>
                    <th class="text-right">Volume (Foreign)</th>
                    <th class="text-right">Amount (MYR)</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totals = [
                    'buy_volume_foreign' => 0,
                    'buy_count' => 0,
                    'buy_amount_myr' => 0,
                    'sell_volume_foreign' => 0,
                    'sell_count' => 0,
                    'sell_amount_myr' => 0,
                ];
                @endphp

                @foreach($summary as $currency)
                @php
                $netVolume = $currency->buy_volume_foreign - $currency->sell_volume_foreign;
                $netAmount = $currency->buy_amount_myr - $currency->sell_amount_myr;

                $totals['buy_volume_foreign'] += $currency->buy_volume_foreign;
                $totals['buy_count'] += $currency->buy_count;
                $totals['buy_amount_myr'] += $currency->buy_amount_myr;
                $totals['sell_volume_foreign'] += $currency->sell_volume_foreign;
                $totals['sell_count'] += $currency->sell_count;
                $totals['sell_amount_myr'] += $currency->sell_amount_myr;
                @endphp
                <tr>
                    <td><strong>{{ $currency->currency_code }}</strong></td>
                    <td class="text-right">{{ number_format($currency->buy_volume_foreign, 4) }}</td>
                    <td class="text-right">{{ number_format($currency->buy_count) }}</td>
                    <td class="text-right">{{ number_format($currency->buy_amount_myr, 2) }}</td>
                    <td class="text-right">{{ number_format($currency->sell_volume_foreign, 4) }}</td>
                    <td class="text-right">{{ number_format($currency->sell_count) }}</td>
                    <td class="text-right">{{ number_format($currency->sell_amount_myr, 2) }}</td>
                    <td class="text-right {{ $netVolume >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $netVolume >= 0 ? '+' : '' }}{{ number_format($netVolume, 4) }}
                    </td>
                    <td class="text-right {{ $netAmount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $netAmount >= 0 ? '+' : '' }}RM {{ number_format($netAmount, 2) }}
                    </td>
                </tr>
                @endforeach

                @php
                $grandNetVolume = $totals['buy_volume_foreign'] - $totals['sell_volume_foreign'];
                $grandNetAmount = $totals['buy_amount_myr'] - $totals['sell_amount_myr'];
                @endphp

                <tr class="bg-gray-50 font-semibold">
                    <td><strong>Grand Total</strong></td>
                    <td class="text-right">{{ number_format($totals['buy_volume_foreign'], 4) }}</td>
                    <td class="text-right">{{ number_format($totals['buy_count']) }}</td>
                    <td class="text-right">{{ number_format($totals['buy_amount_myr'], 2) }}</td>
                    <td class="text-right">{{ number_format($totals['sell_volume_foreign'], 4) }}</td>
                    <td class="text-right">{{ number_format($totals['sell_count']) }}</td>
                    <td class="text-right">{{ number_format($totals['sell_amount_myr'], 2) }}</td>
                    <td class="text-right {{ $grandNetVolume >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $grandNetVolume >= 0 ? '+' : '' }}{{ number_format($grandNetVolume, 4) }}
                    </td>
                    <td class="text-right {{ $grandNetAmount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $grandNetAmount >= 0 ? '+' : '' }}RM {{ number_format($grandNetAmount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
        @endif
    </div>
</div>

<form id="msb2-form" method="GET" action="{{ route('reports.msb2') }}" class="hidden">
    @csrf
</form>
@endsection

@section('scripts')
<script>
const routeMSB2 = "{{ route('reports.msb2') }}";
const routeAPIMSB2 = "{{ route('api.reports.msb2') }}";
const routeExport = "{{ route('reports.export') }}";
const csrfToken = "{{ csrf_token() }}";

function updateView() {
    const date = document.getElementById('date').value;
    window.location.href = routeMSB2 + '?date=' + encodeURIComponent(date);
}

async function generateReport() {
    const date = document.getElementById('date').value;

    if (!confirm('Generate MSB(2) report for ' + date + '?')) {
        return;
    }

    try {
        const response = await fetch(routeAPIMSB2, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ date: date })
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
    const date = document.getElementById('date').value;
    window.location.href = routeExport + '?report_type=msb2&period=' + encodeURIComponent(date) + '&format=CSV';
}

async function markSubmitted() {
    const date = document.getElementById('date').value;

    if (!confirm('Mark this report as submitted to Bank Negara Malaysia? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch('{{ route("api.reports.msb2.status") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          date: date,
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
