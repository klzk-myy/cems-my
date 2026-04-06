@extends('layouts.app')

@section('title', 'STR Reports - CEMS-MY')

@section('styles')
<style>
    .str-header {
        margin-bottom: 1.5rem;
    }
    .str-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .str-header p {
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

    .badge-str {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-draft { background: #e2e8f0; color: #4a5568; }
    .badge-pending_review { background: #feebc8; color: #c05621; }
    .badge-pending_approval { background: #ebf8ff; color: #2b6cb0; }
    .badge-submitted { background: #bee3f8; color: #2c5282; }
    .badge-acknowledged { background: #c6f6d5; color: #276749; }

    .action-btns {
        display: flex;
        gap: 0.5rem;
    }

    .btn-create {
        background: #e53e3e;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
    }
    .btn-create:hover {
        background: #c53030;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #718096;
    }
</style>
@endsection

@section('content')
<div class="str-header">
    <div class="flex justify-between items-center">
        <div>
            <h2>Suspicious Transaction Reports (STR)</h2>
            <p>Manage STR filings for BNM compliance - Must be filed within 24 hours of suspicion</p>
        </div>
        <a href="{{ route('str.create') }}" class="btn-create">+ Create STR</a>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid">
    <div class="summary-box">
        <div class="summary-value">{{ $stats['draft'] ?? 0 }}</div>
        <div class="summary-label">Drafts</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">{{ $stats['pending_review'] ?? 0 }}</div>
        <div class="summary-label">Pending Review</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">{{ $stats['pending_approval'] ?? 0 }}</div>
        <div class="summary-label">Pending Approval</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">{{ $stats['submitted'] ?? 0 }}</div>
        <div class="summary-label">Submitted</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">{{ $stats['acknowledged'] ?? 0 }}</div>
        <div class="summary-label">Acknowledged</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <label>Status:</label>
    <select onchange="window.location.href='?status='+this.value">
        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="pending_review" {{ request('status') == 'pending_review' ? 'selected' : '' }}>Pending Review</option>
        <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
        <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
    </select>
    @if(request('status'))
        <a href="{{ route('str.index') }}" class="btn btn-sm">Clear Filter</a>
    @endif
</div>

<!-- STR Table -->
<div class="card">
    <h2>STR Reports</h2>

    @if($strReports->count() > 0)
    <table>
        <thead>
            <tr>
                <th>STR No.</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Filed By</th>
                <th>Created</th>
                <th>Submitted</th>
                <th>BNM Reference</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($strReports as $str)
            <tr>
                <td><strong>{{ $str->str_no }}</strong></td>
                <td>{{ $str->customer->full_name ?? 'N/A' }}</td>
                <td>
                    <span class="badge-str badge-{{ $str->status->value }}">
                        {{ $str->status->label() }}
                    </span>
                </td>
                <td>{{ $str->creator->full_name ?? 'N/A' }}</td>
                <td>{{ $str->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $str->submitted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                <td>{{ $str->bnm_reference ?? '-' }}</td>
                <td>
                    <div class="action-btns">
                        <a href="{{ route('str.show', $str) }}" class="btn btn-sm btn-view">View</a>
                        @if($str->isDraft())
                            <form action="{{ route('str.submit-review', $str) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">Submit for Review</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $strReports->appends(request()->query())->links() }}
    </div>
    @else
    <div class="empty-state">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
        <h3>No STR Reports Found</h3>
        <p>No suspicious transaction reports have been filed yet.</p>
    </div>
    @endif
</div>
@endsection
