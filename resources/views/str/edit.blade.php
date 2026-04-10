@extends('layouts.app')

@section('title', "Edit STR {{ $str->str_no }} - CEMS-MY")

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
            <small class="text-small">Hold Ctrl/Cmd to select multiple transactions</small>
        </div>
    </div>

    <div class="form-section">
        <h3>Reason for Suspicion</h3>
        <div class="form-group">
            <label for="reason">Detailed Reason *</label>
            <textarea name="reason" id="reason" required minlength="20">{{ $str->reason }}</textarea>
        </div>
    </div>

    <div class="flex gap-1 mt-6">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('str.show', $str) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection