@extends('layouts.app')

@section('title', 'STR Reports - CEMS-MY')

@section('content')
<div class="str-header">
    <div class="flex justify-between items-center">
        <div>
            <h2>Suspicious Transaction Reports (STR)</h2>
            <p>Manage STR filings for BNM compliance - Must be filed within 24 hours of suspicion</p>
        </div>
        <a href="{{ route('str.create') }}" class="btn btn-danger">+ Create STR</a>
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
                        <a href="{{ route('str.show', $str) }}" class="btn btn-sm btn-primary">View</a>
                        @if($str->isDraft())
                            <form action="{{ route('str.submit-review', $str) }}" method="POST" class="inline">
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
        <div class="empty-state-icon">📋</div>
        <h3>No STR Reports Found</h3>
        <p>No suspicious transaction reports have been filed yet.</p>
    </div>
    @endif
</div>
@endsection