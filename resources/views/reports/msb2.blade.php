@extends('layouts.app')

@section('title', 'MSB(2) Report - CEMS-MY')

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

.summary-card-value.positive {
    color: #38a169;
}

.summary-card-value.negative {
    color: #e53e3e;
}

/* Validation Alerts */
.validation-alert {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 0 4px 4px 0;
    border-left: 4px solid;
}

.validation-alert.warning {
    background: #fffaf0;
    border-left-color: #dd6b20;
    color: #c05621;
}

.validation-alert.info {
    background: #ebf8ff;
    border-left-color: #3182ce;
    color: #2b6cb0;
}

/* Table Card */
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

.table-container {
    overflow-x: auto;
    margin: 0 -1.5rem;
    padding: 0 1.5rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    margin-top: 1rem;
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
    text-align: center;
}

.data-table th.header-row-1 {
    border-bottom: 1px solid #cbd5e0;
}

.data-table th.header-row-2 {
    font-size: 0.8rem;
}

/* Column Grouping Colors */
.data-table th.buy-col,
.data-table td.buy-col {
    background: rgba(198, 246, 213, 0.15);
}

.data-table th.sell-col,
.data-table td.sell-col {
    background: rgba(254, 215, 215, 0.15);
}

.data-table th.net-col,
.data-table td.net-col {
    background: rgba(190, 227, 248, 0.15);
}

.data-table tr:hover td {
    background: #f7fafc !important;
}

.data-table tr:hover td.buy-col {
    background: rgba(198, 246, 213, 0.25) !important;
}

.data-table tr:hover td.sell-col {
    background: rgba(254, 215, 215, 0.25) !important;
}

.data-table tr:hover td.net-col {
    background: rgba(190, 227, 248, 0.25) !important;
}

/* Value Formatting */
.value-positive {
    color: #38a169;
    font-weight: 600;
}

.value-negative {
    color: #e53e3e;
    font-weight: 600;
}

/* Grand Total Row */
.grand-total {
    background: #edf2f7 !important;
    font-weight: 600;
}

.grand-total td {
    border-top: 2px solid #cbd5e0;
    border-bottom: none;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #718096;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
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
    <span>MSB(2)</span>
</nav>

<!-- Header -->
<div class="page-header">
    <h1>MSB(2) Report</h1>
    <p>Daily Money Services Business Transaction Summary</p>
</div>

<!-- Control Card -->
<div class="control-card">
    <h2>Report Controls</h2>
    <div class="control-row">
        <div class="form-group">
            <label for="date">Select Date</label>
            <input type="date" id="date" name="date" value="{{ $date }}" class="form-control" form="msb2-form">
        </div>

        <div class="status-info">
            @php
            if ($reportGenerated) {
                if ($reportGenerated->status === 'Submitted') {
                    $status = 'Submitted';
                    $statusClass = 'status-submitted';
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
        <div class="summary-card-label">Total Transactions</div>
        <div class="summary-card-value">{{ number_format($stats['total_transactions']) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Total Buy Volume (MYR)</div>
        <div class="summary-card-value">RM {{ number_format($stats['total_buy_myr'], 2) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Total Sell Volume (MYR)</div>
        <div class="summary-card-value">RM {{ number_format($stats['total_sell_myr'], 2) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Net Position (Buy - Sell)</div>
        <div class="summary-card-value {{ $stats['net_position'] >= 0 ? 'positive' : 'negative' }}">
            {{ $stats['net_position'] >= 0 ? '+' : '' }}RM {{ number_format($stats['net_position'], 2) }}
        </div>
    </div>
</div>

<!-- Validation Alerts -->
@if($stats['net_position'] < 0)
<div class="validation-alert warning">
    ⚠️ Validation Notice: Negative net position indicates more sales than purchases for this period.
</div>
@endif

@if($isToday)
<div class="validation-alert info">
    ℹ️ Note: You are viewing today's data. The report should typically be generated for the previous completed business day.
</div>
@endif

<!-- Currency Summary Table -->
<div class="table-card">
    <h2>Currency Summary</h2>
    
    @if($summary->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <p>No transactions found for {{ $date }}. Select a different date or check if transactions have been recorded.</p>
    </div>
    @else
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align: middle; background: #f7fafc;">Currency<br>Code</th>
                    <th colspan="3" class="header-row-1 buy-col">Buy Transactions</th>
                    <th colspan="3" class="header-row-1 sell-col">Sell Transactions</th>
                    <th colspan="2" class="header-row-1 net-col">Net</th>
                </tr>
                <tr>
                    <th class="header-row-2 buy-col">Volume<br>(Foreign)</th>
                    <th class="header-row-2 buy-col">Count</th>
                    <th class="header-row-2 buy-col">Amount<br>(MYR)</th>
                    <th class="header-row-2 sell-col">Volume<br>(Foreign)</th>
                    <th class="header-row-2 sell-col">Count</th>
                    <th class="header-row-2 sell-col">Amount<br>(MYR)</th>
                    <th class="header-row-2 net-col">Volume<br>(Foreign)</th>
                    <th class="header-row-2 net-col">Amount<br>(MYR)</th>
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
                    <td class="buy-col">{{ number_format($currency->buy_volume_foreign, 4) }}</td>
                    <td class="buy-col">{{ number_format($currency->buy_count) }}</td>
                    <td class="buy-col">{{ number_format($currency->buy_amount_myr, 2) }}</td>
                    <td class="sell-col">{{ number_format($currency->sell_volume_foreign, 4) }}</td>
                    <td class="sell-col">{{ number_format($currency->sell_count) }}</td>
                    <td class="sell-col">{{ number_format($currency->sell_amount_myr, 2) }}</td>
                    <td class="net-col {{ $netVolume >= 0 ? 'value-positive' : 'value-negative' }}">
                        {{ $netVolume >= 0 ? '+' : '' }}{{ number_format($netVolume, 4) }}
                    </td>
                    <td class="net-col {{ $netAmount >= 0 ? 'value-positive' : 'value-negative' }}">
                        {{ $netAmount >= 0 ? '+' : '' }}RM {{ number_format($netAmount, 2) }}
                    </td>
                </tr>
                @endforeach

                @php
                $grandNetVolume = $totals['buy_volume_foreign'] - $totals['sell_volume_foreign'];
                $grandNetAmount = $totals['buy_amount_myr'] - $totals['sell_amount_myr'];
                @endphp

                <tr class="grand-total">
                    <td><strong>Grand Total</strong></td>
                    <td class="buy-col">{{ number_format($totals['buy_volume_foreign'], 4) }}</td>
                    <td class="buy-col">{{ number_format($totals['buy_count']) }}</td>
                    <td class="buy-col">{{ number_format($totals['buy_amount_myr'], 2) }}</td>
                    <td class="sell-col">{{ number_format($totals['sell_volume_foreign'], 4) }}</td>
                    <td class="sell-col">{{ number_format($totals['sell_count']) }}</td>
                    <td class="sell-col">{{ number_format($totals['sell_amount_myr'], 2) }}</td>
                    <td class="net-col {{ $grandNetVolume >= 0 ? 'value-positive' : 'value-negative' }}">
                        {{ $grandNetVolume >= 0 ? '+' : '' }}{{ number_format($grandNetVolume, 4) }}
                    </td>
                    <td class="net-col {{ $grandNetAmount >= 0 ? 'value-positive' : 'value-negative' }}">
                        {{ $grandNetAmount >= 0 ? '+' : '' }}RM {{ number_format($grandNetAmount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- Compliance Footer -->
<div class="compliance-footer">
    <div class="compliance-item">
        <span class="compliance-label">Reporting Rule</span>
        <span class="compliance-value">All transactions included</span>
    </div>
    <div class="compliance-item">
        <span class="compliance-label">Submission Deadline</span>
        <span class="compliance-value">Next business day</span>
    </div>
    <div class="compliance-item">
        <span class="compliance-label">Next Business Day</span>
        <span class="compliance-value">{{ $nextBusinessDay }}</span>
    </div>
</div>

<form id="msb2-form" method="GET" action="{{ route('reports.msb2') }}" style="display: none;">
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
