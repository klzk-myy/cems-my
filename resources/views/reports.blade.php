@extends('layouts.app')

@section('title', 'Reports & Analytics - CEMS-MY')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-blue-900 mb-2">Reports & Analytics</h1>
    <p class="text-gray-500">Generate regulatory and financial reports</p>
</div>

<!-- Report Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- LCTR Report Card -->
    <a href="{{ route('reports.lctr') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-blue-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">🏛️</span>
            <h3 class="text-lg font-semibold text-gray-800">LCTR Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Bank Negara Malaysia Large Currency Transaction Report. Monthly submission required.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Due by 10th of each month</div>
    </a>

    <!-- MSB(2) Report Card -->
    <a href="{{ route('reports.msb2') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-blue-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📋</span>
            <h3 class="text-lg font-semibold text-gray-800">MSB(2) Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Daily Money Services Business Transaction Summary for BNM compliance.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Due next business day</div>
    </a>

    <!-- Trial Balance Card -->
    <a href="{{ route('accounting.trial-balance') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-green-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">⚖️</span>
            <h3 class="text-lg font-semibold text-gray-800">Trial Balance</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Chart of accounts with debit/credit balances for accounting reconciliation.</p>
        <div class="text-gray-500 text-xs font-medium">📅 On-demand</div>
    </a>

    <!-- Profit & Loss Card -->
    <a href="{{ route('accounting.profit-loss') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-green-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📈</span>
            <h3 class="text-lg font-semibold text-gray-800">Profit & Loss</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Revenue and expense statement showing financial performance.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Monthly/Quarterly/Annual</div>
    </a>

    <!-- Balance Sheet Card -->
    <a href="{{ route('accounting.balance-sheet') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-green-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📊</span>
            <h3 class="text-lg font-semibold text-gray-800">Balance Sheet</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Assets, liabilities, and equity snapshot at a point in time.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Monthly/Quarterly/Annual</div>
    </a>

    <!-- Currency Position Card -->
    <a href="{{ route('accounting.index') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-gray-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">💱</span>
            <h3 class="text-lg font-semibold text-gray-800">Currency Position</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Current inventory status and unrealized P&L by currency.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Real-time / On-demand</div>
    </a>

    <!-- Customer Risk Report Card -->
    <a href="{{ route('compliance.flagged') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-orange-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">⚠️</span>
            <h3 class="text-lg font-semibold text-gray-800">Customer Risk Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">High-risk customer analysis and flags</p>
        <div class="text-gray-500 text-xs font-medium">📅 Weekly</div>
    </a>

    <!-- LMCA Report Card -->
    <a href="{{ route('reports.lmca') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-blue-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📊</span>
            <h3 class="text-lg font-semibold text-gray-800">LMCA Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Large Money Changers Act compliance report. Monthly submission to BNM.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Due by 15th of each month</div>
    </a>

    <!-- Quarterly LVR Report Card -->
    <a href="{{ route('reports.quarterly-lvr') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-blue-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📅</span>
            <h3 class="text-lg font-semibold text-gray-800">Quarterly LVR</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Quarterly Large Value Report for high-value transactions.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Due 30 days after quarter end</div>
    </a>

    <!-- Position Limit Report Card -->
    <a href="{{ route('reports.position-limit') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-orange-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📏</span>
            <h3 class="text-lg font-semibold text-gray-800">Position Limit</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Currency position limits vs actual exposure monitoring.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Daily</div>
    </a>

    <!-- Report History Card -->
    <a href="{{ route('reports.history') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-gray-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📜</span>
            <h3 class="text-lg font-semibold text-gray-800">Report History</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">View all previously generated reports with version tracking.</p>
        <div class="text-gray-500 text-xs font-medium">📅 On-demand</div>
    </a>

    <!-- Compare Reports Card -->
    <a href="{{ route('reports.compare') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-gray-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">🔄</span>
            <h3 class="text-lg font-semibold text-gray-800">Compare Reports</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Compare two versions of the same report side by side.</p>
        <div class="text-gray-500 text-xs font-medium">📅 On-demand</div>
    </a>

    <!-- Audit Trail Card -->
    <a href="{{ route('audit.index') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-gray-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">🔍</span>
            <h3 class="text-lg font-semibold text-gray-800">Audit Trail</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Complete system activity log</p>
        <div class="text-gray-500 text-xs font-medium">📅 On-demand</div>
    </a>

    <!-- Monthly Trends Card -->
    <a href="{{ route('reports.monthly-trends') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-green-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📈</span>
            <h3 class="text-lg font-semibold text-gray-800">Monthly Trends</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Transaction volume trends and month-over-month analysis with Chart.js visualization.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Monthly/Annual</div>
    </a>

    <!-- Profitability Analysis Card -->
    <a href="{{ route('reports.profitability') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-green-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">💰</span>
            <h3 class="text-lg font-semibold text-gray-800">Profitability</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Realized and unrealized P&L by currency with position tracking.</p>
        <div class="text-gray-500 text-xs font-medium">📅 On-demand</div>
    </a>

    <!-- Customer Analysis Card -->
    <a href="{{ route('reports.customer-analysis') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-gray-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">👥</span>
            <h3 class="text-lg font-semibold text-gray-800">Customer Analysis</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Top customers by volume, activity trends, and risk distribution.</p>
        <div class="text-gray-500 text-xs font-medium">📅 On-demand</div>
    </a>

    <!-- Compliance Summary Card -->
    <a href="{{ route('reports.compliance-summary') }}" class="bg-white rounded-lg shadow-sm p-6 block no-underline text-inherit hover:shadow-md transition-shadow border-l-4 border-orange-500">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">⚠️</span>
            <h3 class="text-lg font-semibold text-gray-800">Compliance Summary</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">AML/CFT monitoring, flagged transactions, and BNM reporting checklist.</p>
        <div class="text-gray-500 text-xs font-medium">📅 Daily/Weekly</div>
    </a>
</div>

<!-- Recent Reports Section -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6 pb-4 border-b-2 border-gray-200">
        <h2 class="text-xl font-semibold text-blue-900 m-0">Recently Generated Reports</h2>
        <a href="{{ route('reports.history') }}" class="text-blue-600 no-underline text-sm font-medium hover:underline">View All History</a>
    </div>

    @if($recentReports->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <div class="text-5xl mb-4">📋</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No reports generated yet</h3>
            <p class="max-w-md mx-auto">Select a report type above to get started.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">Report Type</th>
                        <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">Period</th>
                        <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">Generated By</th>
                        <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">Generated At</th>
                        <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">Status</th>
                        <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentReports as $report)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-b border-gray-100 text-sm text-gray-700">{{ str_replace('_', ' ', $report->report_type) }}</td>
                            <td class="px-4 py-3 border-b border-gray-100 text-sm text-gray-700">
                                @if($report->period_start && $report->period_end)
                                    {{ $report->period_start->format('M d') }} - {{ $report->period_end->format('M d, Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 border-b border-gray-100 text-sm text-gray-700">{{ $report->generatedBy?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 border-b border-gray-100 text-sm text-gray-700">{{ $report->generated_at?->format('M d, Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 border-b border-gray-100">
                                @php
                                    $statusClass = match($report->status ?? 'Generated') {
                                        'Submitted' => 'bg-green-100 text-green-800',
                                        'Pending' => 'bg-orange-100 text-orange-800',
                                        default => 'bg-blue-100 text-blue-800'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $report->status ?? 'Generated' }}</span>
                            </td>
                            <td class="px-4 py-3 border-b border-gray-100">
                                @if($report->file_path)
                                    <a href="{{ asset('storage/' . $report->file_path) }}" class="px-3 py-1 bg-blue-600 text-white no-underline rounded text-xs font-semibold hover:bg-blue-700 transition-colors" download>Download</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
