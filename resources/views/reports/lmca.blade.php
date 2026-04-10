@extends('layouts.app')

@section('title', 'BNM Form LMCA - CEMS-MY')

@section('content')
<nav class="flex items-center gap-2 mb-4 text-sm text-gray-500">
    <a href="{{ route('reports.index') }}" class="text-blue-600 no-underline hover:underline">Reports</a>
    <span>›</span>
    <span>BNM Form LMCA</span>
</nav>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-blue-900 mb-1">BNM Form LMCA</h1>
    <p class="text-gray-500 text-sm">Monthly Regulatory Report for Bank Negara Malaysia</p>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Report Controls</h2>
    <div class="flex flex-wrap items-center gap-4">
        <form method="GET" action="{{ route('reports.lmca') }}" id="lmca-form">
            <div class="flex flex-col gap-1">
                <label for="month" class="text-sm font-medium text-gray-600">Select Month</label>
                <input type="month" id="month" name="month" value="{{ $month }}" class="p-2 border border-gray-200 rounded text-sm min-w-48">
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-3">
            @php
            if ($reportGenerated) {
                if ($reportGenerated->status === 'Submitted') {
                    $status = 'Submitted';
                    $statusClass = 'bg-green-100 text-green-800';
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
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors" onclick="document.getElementById('lmca-form').submit()">
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

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex flex-col">
            <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">License Number</span>
            <span class="text-base font-semibold text-gray-800">{{ $reportData['license_number'] }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Reporting Period</span>
            <span class="text-base font-semibold text-gray-800">{{ $reportData['reporting_period'] }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Report Generated</span>
            <span class="text-base font-semibold text-gray-800">{{ $reportData['generated_at'] }}</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Total Customers Served</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($reportData['customer_count']) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Total Active Staff</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($reportData['staff_count']) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Currencies Traded</div>
        <div class="text-2xl font-bold text-gray-800">{{ count($reportData['currencies']) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Submission Deadline</div>
        <div class="text-2xl font-bold text-gray-800">10th of Next Month</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Currency Summary</h2>

    @if(empty($reportData['currencies']))
    <p class="text-gray-500">No transaction data available for this period.</p>
    @else
    <div class="overflow-x-auto -mx-6 px-6">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle bg-gray-50 text-left px-4 py-3 font-semibold text-gray-700">Currency</th>
                    <th colspan="3" class="text-center px-4 py-3 bg-green-50/50 font-semibold text-green-800">Buy Transactions</th>
                    <th colspan="3" class="text-center px-4 py-3 bg-red-50/50 font-semibold text-red-800">Sell Transactions</th>
                    <th colspan="2" class="text-center px-4 py-3 bg-blue-50/50 font-semibold text-blue-800">Stock Position</th>
                </tr>
                <tr>
                    <th class="text-left px-4 py-2 bg-green-50/50 text-green-800">Count</th>
                    <th class="text-left px-4 py-2 bg-green-50/50 text-green-800">Volume (Foreign)</th>
                    <th class="text-left px-4 py-2 bg-green-50/50 text-green-800">Value (MYR)</th>
                    <th class="text-left px-4 py-2 bg-red-50/50 text-red-800">Count</th>
                    <th class="text-left px-4 py-2 bg-red-50/50 text-red-800">Volume (Foreign)</th>
                    <th class="text-left px-4 py-2 bg-red-50/50 text-red-800">Value (MYR)</th>
                    <th class="text-left px-4 py-2 bg-blue-50/50 text-blue-800">Opening</th>
                    <th class="text-left px-4 py-2 bg-blue-50/50 text-blue-800">Closing</th>
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
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100"><strong>{{ $currency['currency_code'] }}</strong><br><small class="text-gray-500">{{ $currency['currency_name'] }}</small></td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-green-50/25">{{ number_format($currency['buy_count']) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-green-50/25">{{ number_format($currency['buy_volume'], 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-green-50/25">RM {{ number_format($currency['buy_value_myr'], 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-red-50/25">{{ number_format($currency['sell_count']) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-red-50/25">{{ number_format($currency['sell_volume'], 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-red-50/25">RM {{ number_format($currency['sell_value_myr'], 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-blue-50/25">{{ number_format($currency['opening_stock'], 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-blue-50/25">{{ number_format($currency['closing_stock'], 4) }}</td>
                </tr>
                @endforeach

                <tr class="bg-gray-100 font-semibold">
                    <td class="px-4 py-3 border-t-2 border-gray-300"><strong>Grand Total</strong></td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-green-50/50">{{ number_format($totals['buy_count']) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-green-50/50">{{ number_format($totals['buy_volume'], 4) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-green-50/50">RM {{ number_format($totals['buy_value'], 2) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-red-50/50">{{ number_format($totals['sell_count']) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-red-50/50">{{ number_format($totals['sell_volume'], 4) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-red-50/50">RM {{ number_format($totals['sell_value'], 2) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-blue-50/50">-</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-blue-50/50">-</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 rounded-lg p-5 mt-6">
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Regulatory Requirement</span>
        <span class="text-sm font-semibold text-gray-800">BNM MSB Licensing & Operations</span>
    </div>
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Submission Method</span>
        <span class="text-sm font-semibold text-gray-800">BNM Portal (Manual or API)</span>
    </div>
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Record Retention</span>
        <span class="text-sm font-semibold text-gray-800">7 Years</span>
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
