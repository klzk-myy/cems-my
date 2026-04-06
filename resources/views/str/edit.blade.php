@extends('layouts.app')

@section('title', "Edit STR {{ $str->str_no }} - CEMS-MY")

@section('styles')
<style>
    .form-section {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .form-section h3 {
        margin-bottom: 1rem;
        color: #2d3748;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0.5rem;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    .form-group textarea {
        min-height: 150px;
    }
</style>
@endsection

@section('content')
<div class="str-header">
    <h2>Edit STR Draft - {{ $str->str_no }}</h2>
    <p>Update the suspicious transaction report details</p>
</div>

<form action="{{ route('str.update', $str) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-section">
        <h3>Report Information</h3>
        <div class="form-group">
            <label>STR Number</label>
            <input type="text" value="{{ $str->str_no }}" disabled>
        </div>
        <div class="form-group">
            <label>Customer</label>
            <input type="text" value="{{ $str->customer->full_name ?? 'N/A' }}" disabled>
        </div>
    </div>

    <div class="form-section">
        <h3>Suspicious Transactions</h3>
        <div class="form-group">
            <label for="transaction_ids">Transaction IDs *</label>
            <select name="transaction_ids[]" id="transaction_ids" multiple required size="5">
                @foreach(\App\Models\Transaction::orderBy('created_at', 'desc')->limit(100)->get() as $txn)
                <option value="{{ $txn->id }}" {{ in_array($txn->id, $str->transaction_ids ?? []) ? 'selected' : '' }}>
                    #{{ $txn->id }} - {{ $txn->customer->full_name ?? 'N/A' }} - RM {{ number_format($txn->amount_local, 2) }} ({{ $txn->created_at->format('Y-m-d H:i') }})
                </option>
                @endforeach
            </select>
            <small style="color: #718096;">Hold Ctrl/Cmd to select multiple transactions</small>
        </div>
    </div>

    <div class="form-section">
        <h3>Reason for Suspicion</h3>
        <div class="form-group">
            <label for="reason">Detailed Reason *</label>
            <textarea name="reason" id="reason" required minlength="20">{{ $str->reason }}</textarea>
        </div>
    </div>

    <div class="flex gap-1" style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('str.show', $str) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
