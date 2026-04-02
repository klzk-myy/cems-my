@extends('layouts.app')

@section('title', 'Reports & Analytics - CEMS-MY')

@section('styles')
<style>
    .page-header {
        margin-bottom: 2rem;
    }

    .page-header h1 {
        font-size: 1.875rem;
        color: #1a365d;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: #718096;
        font-size: 1rem;
    }

    /* Report Cards Grid */
    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (min-width: 768px) {
        .reports-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .reports-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .report-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: box-shadow 0.2s ease;
        display: block;
        text-decoration: none;
        color: inherit;
    }

    .report-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Card type styles */
    .report-card.regulatory {
        border-left: 4px solid #3182ce;
    }

    .report-card.financial {
        border-left: 4px solid #38a169;
    }

.report-card.operational {
    border-left: 4px solid #718096;
}

.report-card.risk {
    border-left-color: #d69e2e; /* orange */
}

    .report-card-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .report-icon {
        font-size: 1.5rem;
        line-height: 1;
    }

    .report-card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #2d3748;
    }

    .report-card-description {
        color: #4a5568;
        font-size: 0.875rem;
        line-height: 1.5;
        margin-bottom: 1rem;
    }

    .report-card-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #718096;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .report-card-meta::before {
        content: "📅";
    }

    /* Recent Reports Section */
    .recent-reports-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .section-header h2 {
        font-size: 1.25rem;
        color: #1a365d;
        margin: 0;
        border: none;
        padding: 0;
    }

    .view-all-link {
        color: #3182ce;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .view-all-link:hover {
        text-decoration: underline;
    }

    /* Status badges */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-generated {
        background: #c6f6d5;
        color: #276749;
    }

    .status-submitted {
        background: #bee3f8;
        color: #2c5282;
    }

    .status-pending {
        background: #feebc8;
        color: #c05621;
    }

    /* Table styles */
    .reports-table {
        width: 100%;
        border-collapse: collapse;
    }

    .reports-table th,
    .reports-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .reports-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #4a5568;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .reports-table tr:hover {
        background: #f7fafc;
    }

    .reports-table td {
        font-size: 0.875rem;
        color: #4a5568;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #718096;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        font-size: 1.125rem;
        color: #4a5568;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        font-size: 0.875rem;
        max-width: 400px;
        margin: 0 auto;
    }

    /* Download button */
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    /* Responsive table */
    @media (max-width: 768px) {
        .reports-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1>Reports & Analytics</h1>
    <p>Generate regulatory and financial reports</p>
</div>

<!-- Report Cards Grid -->
<div class="reports-grid">
    <!-- LCTR Report Card -->
    <a href="{{ route('reports.lctr') }}" class="report-card regulatory">
        <div class="report-card-header">
            <span class="report-icon">🏛️</span>
            <h3 class="report-card-title">LCTR Report</h3>
        </div>
        <p class="report-card-description">Bank Negara Malaysia Large Currency Transaction Report. Monthly submission required.</p>
        <div class="report-card-meta">Due by 10th of each month</div>
    </a>

    <!-- MSB(2) Report Card -->
    <a href="{{ route('reports.msb2') }}" class="report-card regulatory">
        <div class="report-card-header">
            <span class="report-icon">📋</span>
            <h3 class="report-card-title">MSB(2) Report</h3>
        </div>
        <p class="report-card-description">Daily Money Services Business Transaction Summary for BNM compliance.</p>
        <div class="report-card-meta">Due next business day</div>
    </a>

    <!-- Trial Balance Card -->
    <a href="{{ route('accounting.trial-balance') }}" class="report-card financial">
        <div class="report-card-header">
            <span class="report-icon">⚖️</span>
            <h3 class="report-card-title">Trial Balance</h3>
        </div>
        <p class="report-card-description">Chart of accounts with debit/credit balances for accounting reconciliation.</p>
        <div class="report-card-meta">On-demand</div>
    </a>

    <!-- Profit & Loss Card -->
    <a href="{{ route('accounting.profit-loss') }}" class="report-card financial">
        <div class="report-card-header">
            <span class="report-icon">📈</span>
            <h3 class="report-card-title">Profit & Loss</h3>
        </div>
        <p class="report-card-description">Revenue and expense statement showing financial performance.</p>
        <div class="report-card-meta">Monthly/Quarterly/Annual</div>
    </a>

    <!-- Balance Sheet Card -->
    <a href="{{ route('accounting.balance-sheet') }}" class="report-card financial">
        <div class="report-card-header">
            <span class="report-icon">📊</span>
            <h3 class="report-card-title">Balance Sheet</h3>
        </div>
        <p class="report-card-description">Assets, liabilities, and equity snapshot at a point in time.</p>
        <div class="report-card-meta">Monthly/Quarterly/Annual</div>
    </a>

    <!-- Currency Position Card -->
    <a href="{{ route('accounting') }}" class="report-card operational">
        <div class="report-card-header">
            <span class="report-icon">💱</span>
            <h3 class="report-card-title">Currency Position</h3>
        </div>
        <p class="report-card-description">Current inventory status and unrealized P&L by currency.</p>
        <div class="report-card-meta">Real-time / On-demand</div>
    </a>

    <!-- Customer Risk Report Card -->
    <a href="/compliance/risk-report" class="report-card risk">
        <div class="report-card-header">
            <span class="report-icon">⚠️</span>
            <h3 class="report-card-title">Customer Risk Report</h3>
        </div>
        <p class="report-card-description">High-risk customer analysis and flags</p>
        <div class="report-card-meta">Weekly</div>
    </a>

<!-- Audit Trail Card -->
 <a href="/admin/audit-logs" class="report-card operational">
 <div class="report-card-header">
 <span class="report-icon">🔍</span>
 <h3 class="report-card-title">Audit Trail</h3>
 </div>
 <p class="report-card-description">Complete system activity log</p>
 <div class="report-card-meta">On-demand</div>
 </a>

 <!-- Monthly Trends Card -->
 <a href="{{ route('reports.monthly-trends') }}" class="report-card financial">
 <div class="report-card-header">
 <span class="report-icon">📈</span>
 <h3 class="report-card-title">Monthly Trends</h3>
 </div>
 <p class="report-card-description">Transaction volume trends and month-over-month analysis with Chart.js visualization.</p>
 <div class="report-card-meta">Monthly/Annual</div>
 </a>

 <!-- Profitability Analysis Card -->
 <a href="{{ route('reports.profitability') }}" class="report-card financial">
 <div class="report-card-header">
 <span class="report-icon">💰</span>
 <h3 class="report-card-title">Profitability</h3>
 </div>
 <p class="report-card-description">Realized and unrealized P&L by currency with position tracking.</p>
 <div class="report-card-meta">On-demand</div>
 </a>

 <!-- Customer Analysis Card -->
 <a href="{{ route('reports.customer-analysis') }}" class="report-card operational">
 <div class="report-card-header">
 <span class="report-icon">👥</span>
 <h3 class="report-card-title">Customer Analysis</h3>
 </div>
 <p class="report-card-description">Top customers by volume, activity trends, and risk distribution.</p>
 <div class="report-card-meta">On-demand</div>
 </a>

 <!-- Compliance Summary Card -->
 <a href="{{ route('reports.compliance-summary') }}" class="report-card risk">
 <div class="report-card-header">
 <span class="report-icon">⚠️</span>
 <h3 class="report-card-title">Compliance Summary</h3>
 </div>
 <p class="report-card-description">AML/CFT monitoring, flagged transactions, and BNM reporting checklist.</p>
 <div class="report-card-meta">Daily/Weekly</div>
 </a>
 </div>

<!-- Recent Reports Section -->
<div class="recent-reports-section">
    <div class="section-header">
        <h2>Recently Generated Reports</h2>
        <a href="#" class="view-all-link">View All History</a>
    </div>

    @if($recentReports->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3>No reports generated yet</h3>
            <p>Select a report type above to get started.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="reports-table">
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
                                        'Submitted' => 'status-submitted',
                                        'Pending' => 'status-pending',
                                        default => 'status-generated'
                                    };
                                @endphp
                                <span class="status-badge {{ $statusClass }}">{{ $report->status ?? 'Generated' }}</span>
                            </td>
                            <td>
                                @if($report->file_path)
                                    <a href="{{ asset('storage/' . $report->file_path) }}" class="btn btn-primary btn-sm" download>Download</a>
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
