@extends('layouts.app')

@section('title', "STR {{ $str->str_no }} - CEMS-MY")

@section('content')
<div class="str-detail-header">
    <div class="flex justify-between items-center">
        <div>
            <h2>STR {{ $str->str_no }}</h2>
            <p>Suspicious Transaction Report for BNM Compliance</p>
        </div>
        <div>
            <span class="status-badge status-{{ $str->status->value }}">
                {{ $str->status->label() }}
            </span>
        </div>
    </div>
</div>

<!-- Workflow Timeline -->
<div class="workflow-timeline">
    <div class="workflow-step {{ $str->isDraft() ? 'active' : ($str->status->value != 'draft' ? 'completed' : '') }}">
        <div class="icon">1</div>
        <label>Draft</label>
    </div>
    <div class="workflow-step {{ $str->isPendingReview() ? 'active' : ($str->status->value != 'draft' && $str->status->value != 'pending_review' ? 'completed' : '') }}">
        <div class="icon">2</div>
        <label>Manager Review</label>
    </div>
    <div class="workflow-step {{ $str->isPendingApproval() ? 'active' : ($str->status->value == 'submitted' || $str->status->value == 'acknowledged' ? 'completed' : '') }}">
        <div class="icon">3</div>
        <label>PO Approval</label>
    </div>
    <div class="workflow-step {{ $str->isSubmitted() ? 'active' : ($str->status->value == 'acknowledged' ? 'completed' : '') }}">
        <div class="icon">4</div>
        <label>goAML Submit</label>
    </div>
    <div class="workflow-step {{ $str->isAcknowledged() ? 'active' : '' }}">
        <div class="icon">5</div>
        <label>Acknowledged</label>
    </div>
</div>

<div class="detail-section">
    <h3>Report Details</h3>
    <div class="detail-grid">
        <div class="detail-item">
            <label>STR Number</label>
            <span>{{ $str->str_no }}</span>
        </div>
        <div class="detail-item">
            <label>Customer</label>
            <span>{{ $str->customer->full_name ?? 'N/A' }}</span>
        </div>
        <div class="detail-item">
            <label>Created By</label>
            <span>{{ $str->creator->full_name ?? 'N/A' }}</span>
        </div>
        <div class="detail-item">
            <label>Created At</label>
            <span>{{ $str->created_at->format('Y-m-d H:i:s') }}</span>
        </div>
        @if($str->reviewer)
        <div class="detail-item">
            <label>Reviewed By</label>
            <span>{{ $str->reviewer->full_name }}</span>
        </div>
        @endif
        @if($str->approver)
        <div class="detail-item">
            <label>Approved By</label>
            <span>{{ $str->approver->full_name }}</span>
        </div>
        @endif
        @if($str->submitted_at)
        <div class="detail-item">
            <label>Submitted At</label>
            <span>{{ $str->submitted_at->format('Y-m-d H:i:s') }}</span>
        </div>
        @endif
        @if($str->bnm_reference)
        <div class="detail-item">
            <label>BNM Reference</label>
            <span>{{ $str->bnm_reference }}</span>
        </div>
        @endif
    </div>
</div>

<div class="detail-section">
    <h3>Suspicious Transactions</h3>
    @if($transactions->count() > 0)
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $txn)
            <tr>
                <td>#{{ $txn->id }}</td>
                <td>{{ $txn->customer->full_name ?? 'N/A' }}</td>
                <td>RM {{ number_format($txn->amount_local, 2) }}</td>
                <td>{{ $txn->currency }}</td>
                <td>{{ $txn->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="text-gray-500">No transactions linked to this STR.</p>
    @endif
</div>

<div class="detail-section">
    <h3>Reason for Suspicion</h3>
    <div class="reason-box">{{ $str->reason }}</div>
</div>

@if($str->alert)
<div class="detail-section">
    <h3>Linked Alert</h3>
    <p>
        Alert #{{ $str->alert->id }} -
        <span class="badge">{{ $str->alert->flag_type->value }}</span>
        {{ $str->alert->flag_reason }}
    </p>
</div>
@endif

<!-- Action Box -->
<div class="action-box">
    <h3 class="mb-4">Workflow Actions</h3>

    @if($str->isDraft())
    <form action="{{ route('str.submit-review', $str) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="btn btn-primary">Submit for Manager Review</button>
    </form>
    <a href="{{ route('str.edit', $str) }}" class="btn btn-secondary">Edit Draft</a>
    @endif

    @if($str->isPendingReview())
    <form action="{{ route('str.submit-approval', $str) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="btn btn-primary">Submit for PO Approval</button>
    </form>
    @endif

    @if($str->isPendingApproval())
    <form action="{{ route('str.approve', $str) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="btn btn-success">Approve STR</button>
    </form>
    <form action="{{ route('str.submit', $str) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="btn btn-danger">Submit to goAML</button>
    </form>
    @endif

    @if($str->isSubmitted())
    <form action="{{ route('str.track-acknowledgment', $str) }}" method="POST" class="inline">
        @csrf
        <div class="action-form-inline">
            <input type="text" name="bnm_reference" placeholder="Enter BNM Reference" required class="action-form-input">
            <button type="submit" class="btn btn-success">Track Acknowledgment</button>
        </div>
    </form>
    @endif

    <a href="{{ route('str.index') }}" class="btn btn-secondary ml-4">Back to List</a>
</div>
@endsection