@extends('layouts.app')

@section('title', 'Compliance Summary - CEMS-MY')

@section('styles')
<style>
    .compliance-header {
        margin-bottom: 1.5rem;
    }
    
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .kpi-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        border-left: 4px solid #3182ce;
    }
    
    .kpi-card.warning {
        border-left-color: #d69e2e;
    }
    
    .kpi-card.critical {
        border-left-color: #e53e3e;
    }
    
    .kpi-value {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .kpi-value.warning {
        color: #d69e2e;
    }
    
    .kpi-value.critical {
        color: #e53e3e;
    }
    
    .kpi-label {
        color: #718096;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
    
    .filter-bar {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: end;
    }
    
    .chart-container {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .data-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #4a5568;
    }
    
    .flag-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .flag-Velocity { background: #ebf8ff; color: #2b6cb0; }
    .flag-Structuring { background: #feebc8; color: #c05621; }
    .flag-Sanction_Match { background: #fed7d7; color: #c53030; }
    .flag-EDD_Required { background: #e9d8fd; color: #6b46c1; }
    .flag-PEP_Status { background: #fffff0; color: #744210; }
    .flag-Manual { background: #e2e8f0; color: #4a5568; }
    
    .checklist {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
    }
    
    .checklist-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .checklist-item:last-child {
        border-bottom: none;
    }
    
    .check-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-weight: bold;
    }
    
    .check-icon.complete {
        background: #c6f6d5;
        color: #276749;
    }
    
    .check-icon.pending {
        background: #feebc8;
        color: #c05621;
    }
</style>
@endsection

@section('content')
<div class="compliance-header">
    <h2>Compliance Summary</h2>
    <p>AML/CFT monitoring and regulatory compliance overview</p>
</div>

<!-- Date Range Filter -->
<form method="GET" action="{{ route('reports.compliance-summary') }}" class="filter-bar">
    <div style="flex: 1;">
        <label for="start_date">Start Date</label>
        <input type="date" name="start_date" id="start_date" 
               value="{{ $startDate }}" class="form-input">
    </div>
    <div style="flex: 1;">
        <label for="end_date">End Date</label>
        <input type="date" name="end_date" id="end_date" 
               value="{{ $endDate }}" class="form-input">
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Update Report</button>
    </div>
</form>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-value">{{ $flaggedStats->sum('count') }}</div>
        <div class="kpi-label">Total Flagged Transactions</div>
    </div>
    
    <div class="kpi-card warning">
        <div class="kpi-value warning">{{ $largeTransactions }}</div>
        <div class="kpi-label">Large Transactions (≥RM 50k)</div>
    </div>
    
    <div class="kpi-card warning">
        <div class="kpi-value warning">{{ $eddCount }}</div>
        <div class="kpi-label">EDD Required Transactions</div>
    </div>
    
    <div class="kpi-card critical">
        <div class="kpi-value critical">{{ $suspiciousCount }}</div>
        <div class="kpi-label">Suspicious Activities</div>
    </div>
</div>

<!-- Flag Type Breakdown -->
<div class="chart-container">
    <h3>Flag Type Breakdown</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Flag Type</th>
                <th>Count</th>
                <th>Percentage</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($flaggedStats as $stat)
            @php
                $total = $flaggedStats->sum('count');
                $percentage = $total > 0 ? ($stat->count / $total * 100) : 0;
            @endphp
            <tr>
                <td>
                    <span class="flag-badge flag-{{ $stat->flag_type }}">
                        {{ $stat->flag_type }}
                    </span>
                </td>
                <td>{{ number_format($stat->count) }}</td>
                <td>{{ number_format($percentage, 1) }}%</td>
                <td>
                    @if($stat->flag_type === 'Sanction_Match' || $stat->flag_type === 'Structuring')
                        <span style="color: #e53e3e; font-weight: 600;">High Priority</span>
                    @elseif($stat->flag_type === 'EDD_Required')
                        <span style="color: #d69e2e; font-weight: 600;">Medium Priority</span>
                    @else
                        <span style="color: #38a169;">Standard</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 2rem;">
                    No flagged transactions in this period.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- BNM Reporting Checklist -->
<div class="checklist">
    <h3>BNM Compliance Checklist</h3>
    
    <div class="checklist-item">
        <div class="check-icon {{ $largeTransactions > 0 ? 'complete' : 'pending' }}">
            {{ $largeTransactions > 0 ? '✓' : '○' }}
        </div>
        <div>
            <strong>LCTR Report</strong>
            <p style="color: #718096; font-size: 0.875rem; margin-top: 0.25rem;">
                Large Currency Transaction Report
                @if($largeTransactions > 0)
                    <br><span style="color: #e53e3e;">{{ $largeTransactions }} qualifying transactions require reporting</span>
                @endif
            </p>
        </div>
    </div>
    
    <div class="checklist-item">
        <div class="check-icon {{ $suspiciousCount > 0 ? 'pending' : 'complete' }}">
            {{ $suspiciousCount > 0 ? '!' : '✓' }}
        </div>
        <div>
            <strong>Suspicious Activity Report (SAR)</strong>
            <p style="color: #718096; font-size: 0.875rem; margin-top: 0.25rem;">
                @if($suspiciousCount > 0)
                    <span style="color: #e53e3e;">{{ $suspiciousCount }} suspicious activities pending review</span>
                @else
                    No suspicious activities detected
                @endif
            </p>
        </div>
    </div>
    
    <div class="checklist-item">
        <div class="check-icon complete">✓</div>
        <div>
            <strong>CDD/EDD Documentation</strong>
            <p style="color: #718096; font-size: 0.875rem; margin-top: 0.25rem;">
                @if($eddCount > 0)
                    {{ $eddCount }} transactions require enhanced due diligence
                @else
                    All CDD requirements met
                @endif
            </p>
        </div>
    </div>
    
    <div class="checklist-item">
        <div class="check-icon complete">✓</div>
        <div>
            <strong>MSB(2) Daily Report</strong>
            <p style="color: #718096; font-size: 0.875rem; margin-top: 0.25rem;">
                Daily transaction summary report - Due next business day
            </p>
        </div>
    </div>
    
    <div class="checklist-item">
        <div class="check-icon complete">✓</div>
        <div>
            <strong>Sanctions Screening</strong>
            <p style="color: #718096; font-size: 0.875rem; margin-top: 0.25rem;">
                Real-time sanctions screening active
            </p>
        </div>
    </div>
</div>

<!-- Action Items -->
<div class="card" style="margin-top: 2rem;">
    <h3>Required Actions</h3>
    
    @if($flaggedStats->sum('count') > 0 || $largeTransactions > 0)
    <ul style="list-style: none; padding: 0;">
        @if($largeTransactions > 0)
        <li style="padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
            <span style="color: #e53e3e; font-weight: 600;">⚠ URGENT:</span>
            Generate LCTR report for {{ $largeTransactions }} large transactions
            <a href="{{ route('reports.lctr') }}" class="btn btn-sm btn-primary" style="margin-left: 1rem;">
                Generate Report
            </a>
        </li>
        @endif
        
        @if($suspiciousCount > 0)
        <li style="padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
            <span style="color: #e53e3e; font-weight: 600;">⚠ URGENT:</span>
            Review {{ $suspiciousCount }} suspicious activities in Compliance Portal
            <a href="{{ route('compliance') }}" class="btn btn-sm btn-warning" style="margin-left: 1rem;">
                Review Flags
            </a>
        </li>
        @endif
        
        @if($eddCount > 0)
        <li style="padding: 0.75rem 0;">
            <span style="color: #d69e2e; font-weight: 600;">⚠ ACTION REQUIRED:</span>
            Complete EDD for {{ $eddCount }} high-value transactions
        </li>
        @endif
    </ul>
    @else
    <p style="color: #38a169; padding: 1rem;">
        ✅ All compliance requirements are up to date. No action required.
    </p>
    @endif
</div>
@endsection
