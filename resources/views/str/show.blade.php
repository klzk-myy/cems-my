@extends('layouts.app')

@section('title', "STR {{ $str->str_no }} - CEMS-MY")

@section('styles')
<style>
    .str-detail-header {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .str-detail-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        font-weight: 700;
        font-size: 1rem;
    }
    .status-draft { background: #e2e8f0; color: #4a5568; }
    .status-pending_review { background: #feebc8; color: #c05621; }
    .status-pending_approval { background: #ebf8ff; color: #2b6cb0; }
    .status-submitted { background: #bee3f8; color: #2c5282; }
    .status-acknowledged { background: #c6f6d5; color: #276749; }

    .detail-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .detail-section h3 {
        color: #2d3748;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    .detail-item {
        margin-bottom: 1rem;
    }
    .detail-item label {
        display: block;
        font-weight: 600;
        color: #718096;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    .detail-item span {
        color: #2d3748;
    }

    .reason-box {
        background: #fff5f5;
        border-left: 4px solid #e53e3e;
        padding: 1rem;
        border-radius: 4px;
        white-space: pre-wrap;
    }

    .workflow-timeline {
        display: flex;
        justify-content: space-between;
        margin: 2rem 0;
        position: relative;
    }
    .workflow-timeline::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 4px;
        background: #e2e8f0;
    }
    .workflow-step {
        position: relative;
        text-align: center;
        z-index: 1;
    }
    .workflow-step .icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
    }
    .workflow-step.active .icon {
        background: #3182ce;
        color: white;
    }
    .workflow-step.completed .icon {
        background: #38a169;
        color: white;
    }
    .workflow-step label {
        font-size: 0.75rem;
        color: #718096;
    }

    .action-box {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }

    .btn-submit {
        background: #e53e3e;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        border: none;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-submit:hover {
        background: #c53030;
    }
</style>
@endsection

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
    <p style="color: #718096;">No transactions linked to this STR.</p>
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
    <h3 style="margin-bottom: 1rem;">Workflow Actions</h3>

    @if($str->isDraft())
    <form action="{{ route('str.submit-review', $str) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-primary">Submit for Manager Review</button>
    </form>
    <a href="{{ route('str.edit', $str) }}" class="btn btn-secondary">Edit Draft</a>
    @endif

    @if($str->isPendingReview())
    <form action="{{ route('str.submit-approval', $str) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-primary">Submit for PO Approval</button>
    </form>
    @endif

    @if($str->isPendingApproval())
    <form action="{{ route('str.approve', $str) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-success">Approve STR</button>
    </form>
    <form action="{{ route('str.submit', $str) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn-submit">Submit to goAML</button>
    </form>
    @endif

    @if($str->isSubmitted())
    <form action="{{ route('str.track-acknowledgment', $str) }}" method="POST" style="display:inline;">
        @csrf
        <div style="display: flex; gap: 1rem; align-items: center;">
            <input type="text" name="bnm_reference" placeholder="Enter BNM Reference" required style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; width: 250px;">
            <button type="submit" class="btn btn-success">Track Acknowledgment</button>
        </div>
    </form>
    @endif

    <a href="{{ route('str.index') }}" class="btn btn-secondary" style="margin-left: 1rem;">Back to List</a>
</div>
@endsection
