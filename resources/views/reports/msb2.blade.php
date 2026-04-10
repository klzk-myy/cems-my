@extends('layouts.app')

@section('title', 'MSB(2) Report - CEMS-MY')

@section('content')
<!-- Breadcrumb -->
<nav class="flex items-center gap-2 mb-4 text-sm text-gray-500">
    <a href="{{ route('reports.index') }}" class="text-blue-600 no-underline hover:underline">Reports</a>
    <span>›</span>
    <span>MSB(2)</span>
</nav>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-blue-900 mb-1">MSB(2) Report</h1>
    <p class="text-gray-500 text-sm">Daily Money Services Business Transaction Summary</p>
</div>

<!-- Control Card -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Report Controls</h2>
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex flex-col gap-1">
            <label for="date" class="text-sm font-medium text-gray-600">Select Date</label>
            <input type="date" id="date" name="date" value="{{ $date }}" class="p-2 border border-gray-200 rounded text-sm min-w-48" form="msb2-form">
        </div>

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
        <div class="text-sm text-gray-500 mb-2">Total Transactions</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_transactions']) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Total Buy Volume (MYR)</div>
        <div class="text-2xl font-bold text-gray-800">RM {{ number_format($stats['total_buy_myr'], 2) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Total Sell Volume (MYR)</div>
        <div class="text-2xl font-bold text-gray-800">RM {{ number_format($stats['total_sell_myr'], 2) }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="text-sm text-gray-500 mb-2">Net Position (Buy - Sell)</div>
        <div class="text-2xl font-bold {{ $stats['net_position'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $stats['net_position'] >= 0 ? '+' : '' }}RM {{ number_format($stats['net_position'], 2) }}
        </div>
    </div>
</div>

<!-- Validation Alerts -->
@if($stats['net_position'] < 0)
<div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6 rounded-r-lg">
    <p class="text-orange-800 text-sm">⚠️ Validation Notice: Negative net position indicates more sales than purchases for this period.</p>
</div>
@endif

@if($isToday)
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
    <p class="text-blue-800 text-sm">ℹ️ Note: You are viewing today's data. The report should typically be generated for the previous completed business day.</p>
</div>
@endif

<!-- Currency Summary Table -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Currency Summary</h2>

    @if($summary->isEmpty())
    <div class="text-center py-12 text-gray-500">
        <div class="text-5xl mb-4">📊</div>
        <p>No transactions found for {{ $date }}. Select a different date or check if transactions have been recorded.</p>
    </div>
    @else
    <div class="overflow-x-auto -mx-6 px-6">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle text-left px-4 py-3 bg-gray-50 font-semibold text-gray-700 border-b-2 border-gray-200">Currency<br>Code</th>
                    <th colspan="3" class="text-center px-4 py-3 bg-green-50/50 font-semibold text-green-800 border-b-2 border-gray-200">Buy Transactions</th>
                    <th colspan="3" class="text-center px-4 py-3 bg-red-50/50 font-semibold text-red-800 border-b-2 border-gray-200">Sell Transactions</th>
                    <th colspan="2" class="text-center px-4 py-3 bg-blue-50/50 font-semibold text-blue-800 border-b-2 border-gray-200">Net</th>
                </tr>
                <tr>
                    <th class="text-left px-4 py-2 bg-green-50/50 text-green-800 text-xs">Volume<br>(Foreign)</th>
                    <th class="text-left px-4 py-2 bg-green-50/50 text-green-800 text-xs">Count</th>
                    <th class="text-left px-4 py-2 bg-green-50/50 text-green-800 text-xs">Amount<br>(MYR)</th>
                    <th class="text-left px-4 py-2 bg-red-50/50 text-red-800 text-xs">Volume<br>(Foreign)</th>
                    <th class="text-left px-4 py-2 bg-red-50/50 text-red-800 text-xs">Count</th>
                    <th class="text-left px-4 py-2 bg-red-50/50 text-red-800 text-xs">Amount<br>(MYR)</th>
                    <th class="text-left px-4 py-2 bg-blue-50/50 text-blue-800 text-xs">Volume<br>(Foreign)</th>
                    <th class="text-left px-4 py-2 bg-blue-50/50 text-blue-800 text-xs">Amount<br>(MYR)</th>
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
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100"><strong>{{ $currency->currency_code }}</strong></td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-green-50/25">{{ number_format($currency->buy_volume_foreign, 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-green-50/25">{{ number_format($currency->buy_count) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-green-50/25">{{ number_format($currency->buy_amount_myr, 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-red-50/25">{{ number_format($currency->sell_volume_foreign, 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-red-50/25">{{ number_format($currency->sell_count) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-red-50/25">{{ number_format($currency->sell_amount_myr, 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-blue-50/25 {{ $netVolume >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                        {{ $netVolume >= 0 ? '+' : '' }}{{ number_format($netVolume, 4) }}
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 bg-blue-50/25 {{ $netAmount >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                        {{ $netAmount >= 0 ? '+' : '' }}RM {{ number_format($netAmount, 2) }}
                    </td>
                </tr>
                @endforeach

                @php
                $grandNetVolume = $totals['buy_volume_foreign'] - $totals['sell_volume_foreign'];
                $grandNetAmount = $totals['buy_amount_myr'] - $totals['sell_amount_myr'];
                @endphp

                <tr class="bg-gray-100 font-semibold">
                    <td class="px-4 py-3 border-t-2 border-gray-300"><strong>Grand Total</strong></td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-green-50/50">{{ number_format($totals['buy_volume_foreign'], 4) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-green-50/50">{{ number_format($totals['buy_count']) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-green-50/50">{{ number_format($totals['buy_amount_myr'], 2) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-red-50/50">{{ number_format($totals['sell_volume_foreign'], 4) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-red-50/50">{{ number_format($totals['sell_count']) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-red-50/50">{{ number_format($totals['sell_amount_myr'], 2) }}</td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-blue-50/50 {{ $grandNetVolume >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $grandNetVolume >= 0 ? '+' : '' }}{{ number_format($grandNetVolume, 4) }}
                    </td>
                    <td class="px-4 py-3 border-t-2 border-gray-300 bg-blue-50/50 {{ $grandNetAmount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $grandNetAmount >= 0 ? '+' : '' }}RM {{ number_format($grandNetAmount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- Compliance Footer -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 rounded-lg p-5 mt-6">
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Reporting Rule</span>
        <span class="text-sm font-semibold text-gray-800">All transactions included</span>
    </div>
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Submission Deadline</span>
        <span class="text-sm font-semibold text-gray-800">Next business day</span>
    </div>
    <div class="flex flex-col">
        <span class="text-xs uppercase tracking-wide text-gray-500 mb-1">Next Business Day</span>
        <span class="text-sm font-semibold text-gray-800">{{ $nextBusinessDay }}</span>
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
