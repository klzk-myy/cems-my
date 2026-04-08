@extends('layouts.app')

@section('title', 'EDD Detail - CEMS-MY')

@section('content')
<div class="compliance-header">
    <h2>EDD Record: {{ $record->edd_reference }}</h2>
    <p>Enhanced Due Diligence Documentation</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h4>Customer Information</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Customer:</strong> {{ $record->customer->name ?? 'N/A' }}</p>
                <p><strong>Risk Level:</strong> <span class="badge bg-{{ $record->risk_level === 'Critical' ? 'danger' : 'info' }}">{{ $record->risk_level }}</span></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge bg-{{ $record->status->color() }}">{{ $record->status->label() }}</span></p>
                <p><strong>Created:</strong> {{ $record->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4>Source of Funds</h4>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-12">
                <strong>Source:</strong>
                <p>{{ $record->source_of_funds ?? 'Not provided' }}</p>
            </div>
        </div>
        @if($record->source_of_funds_description)
        <div class="row mb-3">
            <div class="col-md-12">
                <strong>Description:</strong>
                <p>{{ $record->source_of_funds_description }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4>Purpose of Transaction</h4>
    </div>
    <div class="card-body">
        <p><strong>Purpose:</strong> {{ $record->purpose_of_transaction ?? 'Not provided' }}</p>
        @if($record->business_justification)
            <p><strong>Business Justification:</strong> {{ $record->business_justification }}</p>
        @endif
    </div>
</div>

@if($record->employment_status)
<div class="card">
    <div class="card-header">
        <h4>Employment Information</h4>
    </div>
    <div class="card-body">
        <p><strong>Status:</strong> {{ $record->employment_status }}</p>
        @if($record->employer_name)
            <p><strong>Employer:</strong> {{ $record->employer_name }}</p>
        @endif
        @if($record->annual_income_range)
            <p><strong>Annual Income Range:</strong> {{ $record->annual_income_range }}</p>
        @endif
        @if($record->estimated_net_worth)
            <p><strong>Estimated Net Worth:</strong> {{ $record->estimated_net_worth }}</p>
        @endif
    </div>
</div>
@endif

@if($record->source_of_wealth)
<div class="card">
    <div class="card-header">
        <h4>Source of Wealth</h4>
    </div>
    <div class="card-body">
        <p><strong>Source:</strong> {{ $record->source_of_wealth }}</p>
        @if($record->source_of_wealth_description)
            <p><strong>Description:</strong> {{ $record->source_of_wealth_description }}</p>
        @endif
    </div>
</div>
@endif

@if($record->reviewed_by)
<div class="card">
    <div class="card-header">
        <h4>Review</h4>
    </div>
    <div class="card-body">
        <p><strong>Reviewed By:</strong> {{ $record->reviewer->username ?? 'N/A' }}</p>
        <p><strong>Reviewed At:</strong> {{ $record->reviewed_at->format('Y-m-d H:i') }}</p>
        @if($record->review_notes)
            <p><strong>Notes:</strong> {{ $record->review_notes }}</p>
        @endif
    </div>
</div>
@endif

<div class="card">
    <div class="card-body">
        @if($record->status === App\Enums\EddStatus::Incomplete)
            <a href="{{ route('compliance.edd.edit', $record) }}" class="btn btn-primary">Complete EDD</a>
        @elseif($record->status === App\Enums\EddStatus::PendingReview)
            <form action="{{ route('compliance.edd.approve', $record) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-success">Approve</button>
            </form>
            <form action="{{ route('compliance.edd.reject', $record) }}" method="POST" style="display: inline;">
                @csrf
                <input type="text" name="reason" placeholder="Rejection reason" required>
                <button type="submit" class="btn btn-danger">Reject</button>
            </form>
        @endif
    </div>
</div>
@endsection
