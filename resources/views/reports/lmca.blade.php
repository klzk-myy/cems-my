@extends('layouts.app')

@section('title', 'BNM Form LMCA - CEMS-MY')

@section('styles')
<style>
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

.data-table th.buy-col,
.data-table td.buy-col {
    background: rgba(198, 246, 213, 0.15);
}

.data-table th.sell-col,
.data-table td.sell-col {
    background: rgba(254, 215, 215, 0.15);
}

.data-table th.stock-col,
.data-table td.stock-col {
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

.data-table tr:hover td.stock-col {
    background: rgba(190, 227, 248, 0.25) !important;
}

.grand-total {
    background: #edf2f7 !important;
    font-weight: 600;
}

.grand-total td {
    border-top: 2px solid #cbd5e0;
    border-bottom: none;
}

.info-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.75rem;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #2d3748;
}

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
<nav class="breadcrumb">
    <a href="{{ route('reports') }}">Reports</a>
    <span>›</span>
    <span>BNM Form LMCA</span>
</nav>

<div class="page-header">
    <h1>BNM Form LMCA</h1>
    <p>Monthly Regulatory Report for Bank Negara Malaysia</p>
</div>

<div class="control-card">
    <h2>Report Controls</h2>
    <div class="control-row">
        <form method="GET" action="{{ route('reports.lmca') }}" id="lmca-form">
            <div class="form-group">
                <label for="month">Select Month</label>
                <input type="month" id="month" name="month" value="{{ $month }}" class="form-control">
            </div>
        </form>

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
            <button type="button" class="btn btn-primary" onclick="document.getElementById('lmca-form').submit()">
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

<div class="info-card">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">License Number</span>
            <span class="info-value">{{ $reportData['license_number'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Reporting Period</span>
            <span class="info-value">{{ $reportData['reporting_period'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Report Generated</span>
            <span class="info-value">{{ $reportData['generated_at'] }}</span>
        </div>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-card-label">Total Customers Served</div>
        <div class="summary-card-value">{{ number_format($reportData['customer_count']) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Total Active Staff</div>
        <div class="summary-card-value">{{ number_format($reportData['staff_count']) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Currencies Traded</div>
        <div class="summary-card-value">{{ count($reportData['currencies']) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Submission Deadline</div>
        <div class="summary-card-value">10th of Next Month</div>
    </div>
</div>

<div class="table-card">
    <h2>Currency Summary</h2>
    
    @if(empty($reportData['currencies']))
    <p>No transaction data available for this period.</p>
    @else
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align: middle; background: #f7fafc;">Currency</th>
                    <th colspan="3" class="buy-col">Buy Transactions</th>
                    <th colspan="3" class="sell-col">Sell Transactions</th>
                    <th colspan="2" class="stock-col">Stock Position</th>
                </tr>
                <tr>
                    <th class="buy-col">Count</th>
                    <th class="buy-col">Volume (Foreign)</th>
                    <th class="buy-col">Value (MYR)</th>
                    <th class="sell-col">Count</th>
                    <th class="sell-col">Volume (Foreign)</th>
                    <th class="sell-col">Value (MYR)</th>
                    <th class="stock-col">Opening</th>
                    <th class="stock-col">Closing</th>
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
                    <td><strong>{{ $currency['currency_code'] }}</strong><br><small style="color: #718096;">{{ $currency['currency_name'] }}</small></td>
                    <td class="buy-col">{{ number_format($currency['buy_count']) }}</td>
                    <td class="buy-col">{{ number_format($currency['buy_volume'], 4) }}</td>
                    <td class="buy-col">RM {{ number_format($currency['buy_value_myr'], 2) }}</td>
                    <td class="sell-col">{{ number_format($currency['sell_count']) }}</td>
                    <td class="sell-col">{{ number_format($currency['sell_volume'], 4) }}</td>
                    <td class="sell-col">RM {{ number_format($currency['sell_value_myr'], 2) }}</td>
                    <td class="stock-col">{{ number_format($currency['opening_stock'], 4) }}</td>
                    <td class="stock-col">{{ number_format($currency['closing_stock'], 4) }}</td>
                </tr>
                @endforeach

                <tr class="grand-total">
                    <td><strong>Grand Total</strong></td>
                    <td class="buy-col">{{ number_format($totals['buy_count']) }}</td>
                    <td class="buy-col">{{ number_format($totals['buy_volume'], 4) }}</td>
                    <td class="buy-col">RM {{ number_format($totals['buy_value'], 2) }}</td>
                    <td class="sell-col">{{ number_format($totals['sell_count']) }}</td>
                    <td class="sell-col">{{ number_format($totals['sell_volume'], 4) }}</td>
                    <td class="sell-col">RM {{ number_format($totals['sell_value'], 2) }}</td>
                    <td class="stock-col">-</td>
                    <td class="stock-col">-</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="compliance-footer">
    <div class="compliance-item">
        <span class="compliance-label">Regulatory Requirement</span>
        <span class="compliance-value">BNM MSB Licensing & Operations</span>
    </div>
    <div class="compliance-item">
        <span class="compliance-label">Submission Method</span>
        <span class="compliance-value">BNM Portal (Manual or API)</span>
    </div>
    <div class="compliance-item">
        <span class="compliance-label">Record Retention</span>
        <span class="compliance-value">7 Years</span>
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