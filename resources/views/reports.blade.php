@extends('layouts.app')

@section('title', 'Reports & Analytics - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Reports</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Reports & Analytics</h1>
        <p class="page-header__subtitle">Generate regulatory and financial reports</p>
    </div>
</div>

<!-- Report Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- LCTR Report Card -->
    <a href="{{ route('reports.lctr') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #2563eb;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 12h6M12 9v6"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">LCTR Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Bank Negara Malaysia Large Currency Transaction Report. Monthly submission required.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Due by 10th of each month
        </div>
    </a>

    <!-- MSB(2) Report Card -->
    <a href="{{ route('reports.msb2') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #2563eb;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">MSB(2) Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Daily Money Services Business Transaction Summary for BNM compliance.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Due next business day
        </div>
    </a>

    <!-- Trial Balance Card -->
    <a href="{{ route('accounting.trial-balance') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #059669;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18M5.5 5.5l13 13M18.5 5.5l-13 13"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Trial Balance</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Chart of accounts with debit/credit balances for accounting reconciliation.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            On-demand
        </div>
    </a>

    <!-- Profit & Loss Card -->
    <a href="{{ route('accounting.profit-loss') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #059669;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Profit & Loss</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Revenue and expense statement showing financial performance.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Monthly/Quarterly/Annual
        </div>
    </a>

    <!-- Balance Sheet Card -->
    <a href="{{ route('accounting.balance-sheet') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #059669;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21H3V3"/><path d="M21 9H3"/><path d="M9 21V9"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Balance Sheet</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Assets, liabilities, and equity snapshot at a point in time.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Monthly/Quarterly/Annual
        </div>
    </a>

    <!-- Currency Position Card -->
    <a href="{{ route('accounting.index') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #6b7280;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Currency Position</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Current inventory status and unrealized P&L by currency.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Real-time / On-demand
        </div>
    </a>

    <!-- Customer Risk Report Card -->
    <a href="{{ route('compliance.flagged') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #ea580c;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Customer Risk Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">High-risk customer analysis and flags</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Weekly
        </div>
    </a>

    <!-- LMCA Report Card -->
    <a href="{{ route('reports.lmca') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #2563eb;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">LMCA Report</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Large Money Changers Act compliance report. Monthly submission to BNM.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Due by 15th of each month
        </div>
    </a>

    <!-- Quarterly LVR Report Card -->
    <a href="{{ route('reports.quarterly-lvr') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #2563eb;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Quarterly LVR</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Quarterly Large Value Report for high-value transactions.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Due 30 days after quarter end
        </div>
    </a>

    <!-- Position Limit Report Card -->
    <a href="{{ route('reports.position-limit') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #ea580c;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h2M6 12h2M10 12h2M14 12h2M18 12h2M12 2v2M12 6v2M12 10v2M12 14v2M12 18v2M12 20v2"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Position Limit</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Currency position limits vs actual exposure monitoring.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Daily
        </div>
    </a>

    <!-- Report History Card -->
    <a href="{{ route('reports.history') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #6b7280;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Report History</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">View all previously generated reports with version tracking.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            On-demand
        </div>
    </a>

    <!-- Compare Reports Card -->
    <a href="{{ route('reports.compare') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #6b7280;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Compare Reports</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Compare two versions of the same report side by side.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            On-demand
        </div>
    </a>

    <!-- Audit Trail Card -->
    <a href="{{ route('audit.index') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #6b7280;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Audit Trail</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Complete system activity log</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            On-demand
        </div>
    </a>

    <!-- Monthly Trends Card -->
    <a href="{{ route('reports.monthly-trends') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #059669;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Monthly Trends</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Transaction volume trends and month-over-month analysis with Chart.js visualization.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Monthly/Annual
        </div>
    </a>

    <!-- Profitability Analysis Card -->
    <a href="{{ route('reports.profitability') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #059669;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Profitability</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Realized and unrealized P&L by currency with position tracking.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            On-demand
        </div>
    </a>

    <!-- Customer Analysis Card -->
    <a href="{{ route('reports.customer-analysis') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #6b7280;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Customer Analysis</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">Top customers by volume, activity trends, and risk distribution.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            On-demand
        </div>
    </a>

    <!-- Compliance Summary Card -->
    <a href="{{ route('reports.compliance-summary') }}" class="card card--hover block no-underline text-inherit" style="border-left: 4px solid #ea580c;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-8 h-8 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <h3 class="text-lg font-semibold text-gray-800">Compliance Summary</h3>
        </div>
        <p class="text-gray-600 text-sm mb-3">AML/CFT monitoring, flagged transactions, and BNM reporting checklist.</p>
        <div class="text-gray-500 text-xs font-medium flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Daily/Weekly
        </div>
    </a>
</div>

<!-- Recent Reports Section -->
<div class="card">
    <div class="border-b border-gray-200 pb-4 mb-4 flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800 m-0">Recently Generated Reports</h2>
        <a href="{{ route('reports.history') }}" class="btn btn--ghost btn--sm">View All History</a>
    </div>
    <div>
        @if($recentReports->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No reports generated yet</h3>
                <p class="max-w-md mx-auto">Select a report type above to get started.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Report Type</th>
                            <th>Period</th>
                            <th>Generated By</th>
                            <th>Generated At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentReports as $report)
                            <tr>
                                <td>{{ str_replace('_', ' ', $report->report_type) }}</td>
                                <td>
                                    @if($report->period_start && $report->period_end)
                                        {{ $report->period_start->format('M d') }} - {{ $report->period_end->format('M d, Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $report->generatedBy?->name ?? 'System' }}</td>
                                <td>{{ $report->generated_at?->format('M d, Y H:i') ?? '-' }}</td>
                                <td>
                                    @php
                                        $statusClass = match($report->status ?? 'Generated') {
                                            'Submitted' => 'status-badge--completed',
                                            'Pending' => 'status-badge--pending',
                                            default => 'status-badge--draft'
                                        };
                                    @endphp
                                    <span class="status-badge {{ $statusClass }}">{{ $report->status ?? 'Generated' }}</span>
                                </td>
                                <td>
                                    @if($report->file_path)
                                        <a href="{{ asset('storage/' . $report->file_path) }}" class="btn btn--primary btn--sm" download>Download</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
