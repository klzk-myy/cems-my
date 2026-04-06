@extends('layouts.app')

@section('title', 'Compliance Portal - CEMS-MY')

@section('styles')
<style>
.compliance-header {
    margin-bottom: 1.5rem;
}
.compliance-header h2 {
    color: #2d3748;
    margin-bottom: 0.5rem;
}
.compliance-header p {
    color: #718096;
}

.summary-box {
    background: #f7fafc;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}
.summary-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1a365d;
}
.summary-label {
    color: #718096;
    margin-top: 0.5rem;
    font-size: 0.875rem;
}
.summary-value.open { color: #e53e3e; }
.summary-value.under_review { color: #dd6b20; }
.summary-value.resolved { color: #38a169; }
.summary-value.high_priority { color: #e53e3e; }

.filter-bar {
    background: #f7fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}
.filter-bar label {
    font-weight: 600;
    color: #4a5568;
    font-size: 0.875rem;
}
.filter-bar select {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    background: white;
    font-size: 0.875rem;
    cursor: pointer;
}
.filter-bar .btn {
    margin-left: auto;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Status badges */
.badge-status-open {
    background: #fed7d7;
    color: #c53030;
}
.badge-status-under_review {
    background: #feebc8;
    color: #c05621;
}
.badge-status-resolved {
    background: #c6f6d5;
    color: #276749;
}

/* Flag type badges */
.badge-type-velocity {
    background: #ebf8ff;
    color: #2b6cb0;
}
.badge-type-structuring {
            background: #feebc8;
            color: #c05621;
        }
.badge-type-edd {
    background: #faf5ff;
    color: #6b46c1;
}
.badge-type-sanction {
    background: #fed7d7;
    color: #c53030;
}
.badge-type-manual {
    background: #e2e8f0;
    color: #4a5568;
}
.badge-type-pep {
    background: #fffff0;
    color: #d69e2e;
}

.action-btns {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-view {
    background: #3182ce;
    color: white;
}
.btn-view:hover {
    background: #2c5282;
}

.btn-assign {
    background: #dd6b20;
    color: white;
}
.btn-assign:hover {
    background: #c05621;
}

.btn-resolve {
    background: #38a169;
    color: white;
}
.btn-resolve:hover {
    background: #2f855a;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #718096;
}
.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 1.5rem;
    gap: 0.25rem;
}
.pagination a,
.pagination span {
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.875rem;
}
.pagination a {
    background: #edf2f7;
    color: #4a5568;
}
.pagination a:hover {
    background: #e2e8f0;
}
.pagination span {
    background: #3182ce;
    color: white;
}

.transaction-link {
    color: #3182ce;
    text-decoration: none;
    font-weight: 600;
}
.transaction-link:hover {
    text-decoration: underline;
}

.priority-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 0.5rem;
}
.priority-high {
    background: #e53e3e;
}
.priority-medium {
    background: #dd6b20;
}
.priority-low {
    background: #38a169;
}
</style>
@endsection

@section('content')
<div class="compliance-header">
    <h2>Compliance Portal</h2>
    <p>Review and resolve suspicious transaction flags for AML monitoring</p>
</div>

<!-- Summary Cards -->
<div class="grid">
    <div class="summary-box">
        <div class="summary-value open">{{ $stats['open'] ?? 0 }}</div>
        <div class="summary-label">Open Flags</div>
    </div>
    <div class="summary-box">
        <div class="summary-value under_review">{{ $stats['under_review'] ?? 0 }}</div>
        <div class="summary-label">Under Review</div>
    </div>
    <div class="summary-box">
        <div class="summary-value resolved">{{ $stats['resolved_today'] ?? 0 }}</div>
        <div class="summary-label">Resolved Today</div>
    </div>
    <div class="summary-box">
        <div class="summary-value high_priority">{{ $stats['high_priority'] ?? 0 }}</div>
        <div class="summary-label">High Priority</div>
    </div>
</div>

<!-- STR Deadline Warning -->
@if(isset($strStats) && ($strStats['overdue'] > 0 || $strStats['near_deadline'] > 0))
<div class="alert alert-warning" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; background: #fff3cd; border: 1px solid #ffc107;">
    <h4 style="margin: 0 0 0.5rem 0; color: #856404;">STR Filing Deadline Warning</h4>
    @if($strStats['overdue'] > 0)
    <p style="margin: 0; color: #721c24;">
        <strong>{{ $strStats['overdue'] }} STR(s) overdue</strong> - Filing deadline (3 working days from suspicion) has passed. Immediate action required.
    </p>
    @endif
    @if($strStats['near_deadline'] > 0)
    <p style="margin: 0.5rem 0 0 0; color: #856404;">
        <strong>{{ $strStats['near_deadline'] }} STR(s) approaching deadline</strong> - Filing deadline within 2 days.
    </p>
    @endif
</div>
@endif

<!-- Filter Bar -->
<div class="filter-bar">
    <label for="status-filter">Status:</label>
    <select id="status-filter" onchange="window.location.href='?status='+this.value+'&flag_type={{ request('flag_type', 'all') }}'">
        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
        <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
        <option value="Under_Review" {{ request('status') == 'Under_Review' ? 'selected' : '' }}>Under Review</option>
        <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
    </select>

    <label for="type-filter">Flag Type:</label>
    <select id="type-filter" onchange="window.location.href='?status={{ request('status', 'all') }}&flag_type='+this.value">
        <option value="all" {{ request('flag_type') == 'all' ? 'selected' : '' }}>All Types</option>
        <option value="Velocity" {{ request('flag_type') == 'Velocity' ? 'selected' : '' }}>Velocity</option>
        <option value="Structuring" {{ request('flag_type') == 'Structuring' ? 'selected' : '' }}>Structuring</option>
        <option value="Large_Amount" {{ request('flag_type') == 'Large_Amount' ? 'selected' : '' }}>Large Amount</option>
        <option value="EDD_Required" {{ request('flag_type') == 'EDD_Required' ? 'selected' : '' }}>EDD Required</option>
        <option value="Sanction_Match" {{ request('flag_type') == 'Sanction_Match' ? 'selected' : '' }}>Sanction Match</option>
        <option value="Pep_Status" {{ request('flag_type') == 'Pep_Status' ? 'selected' : '' }}>PEP Status</option>
        <option value="High_Risk_Customer" {{ request('flag_type') == 'High_Risk_Customer' ? 'selected' : '' }}>High Risk Customer</option>
        <option value="High_Risk_Country" {{ request('flag_type') == 'High_Risk_Country' ? 'selected' : '' }}>High Risk Country</option>
        <option value="Round_Amount" {{ request('flag_type') == 'Round_Amount' ? 'selected' : '' }}>Round Amount</option>
        <option value="Profile_Deviation" {{ request('flag_type') == 'Profile_Deviation' ? 'selected' : '' }}>Profile Deviation</option>
        <option value="Manual_Review" {{ request('flag_type') == 'Manual_Review' ? 'selected' : '' }}>Manual Review</option>
    </select>

    <a href="{{ route('compliance') }}" class="btn btn-primary">Clear Filters</a>
</div>

<!-- Flags Table -->
<div class="card">
    <h2>Flagged Transactions</h2>

    @if($flags->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Flag ID</th>
                <th>Transaction</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($flags as $flag)
            <tr>
                <td>#{{ $flag->id }}</td>
                <td>
                    @if($flag->transaction)
                    <a href="{{ route('transactions.show', $flag->transaction) }}" class="transaction-link">
                        #{{ $flag->transaction->id }}
                    </a>
                    @else
                    <span style="color: #718096;">N/A</span>
                    @endif
                </td>
                <td>{{ $flag->transaction->customer->full_name ?? 'N/A' }}</td>
                <td>
            @php
            $typeClass = match($flag->flag_type->value) {
                'Velocity' => 'badge-type-velocity',
                'Structuring' => 'badge-type-structuring',
                'Large_Amount' => 'badge-type-edd',
                'EDD_Required' => 'badge-type-edd',
                'Sanction_Match', 'Sanctions_Hit' => 'badge-type-sanction',
                'Pep_Status' => 'badge-type-pep',
                'High_Risk_Customer', 'High_Risk_Country' => 'badge-type-sanction',
                'Round_Amount', 'Profile_Deviation', 'Unusual_Pattern' => 'badge-type-manual',
                default => 'badge-type-manual'
            };
            $typeLabel = $flag->flag_type->value;
            @endphp
            <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
                </td>
                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">{{ $flag->flag_reason }}</td>
                <td>
            @php
            $statusClass = match($flag->status->value) {
                'Open' => 'badge-status-open',
                'Under_Review' => 'badge-status-under_review',
                'Resolved' => 'badge-status-resolved',
                default => 'badge-status-open'
            };
            $statusLabel = str_replace('_', ' ', $flag->status->value);
            @endphp
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </td>
                <td>{{ $flag->assignedTo->username ?? 'Unassigned' }}</td>
                <td style="color: #718096; font-size: 0.875rem;">{{ $flag->created_at->diffForHumans() }}</td>
                <td>
                    <div class="action-btns">
                        @if($flag->transaction)
                        <a href="{{ route('transactions.show', $flag->transaction) }}" class="action-btn btn-view">View</a>
                        @endif
                        @if($flag->status !== 'Resolved')
                            @if(!$flag->assigned_to || $flag->assigned_to !== auth()->id())
                            <form action="{{ route('compliance.flags.assign', $flag) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="action-btn btn-assign">Assign</button>
                            </form>
                            @endif
                            @if($flag->assigned_to === auth()->id())
                            <form action="{{ route('compliance.flags.resolve', $flag) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="action-btn btn-resolve">Resolve</button>
                            </form>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $flags->appends(request()->query())->links() }}
    </div>
@else
        <div class="empty-state">
            <div class="empty-state-icon">🛡️</div>
            <h3>No Flagged Transactions</h3>
            <p><strong>No flagged transactions found.</strong><br>
            Great! Your compliance monitoring is working effectively.
            @if(request()->has('status') || request()->has('flag_type'))
                <br><a href="{{ route('compliance') }}" style="color: #3182ce;">Clear filters</a>
            @endif
            </p>
        </div>
    @endif
</div>
@endsection
