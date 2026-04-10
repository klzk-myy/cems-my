@extends('layouts.app')

@section('title', 'Create STR - CEMS-MY')

@section('content')
<div class="str-header">
    <h2>Create Suspicious Transaction Report</h2>
    <p>File STR within 24 hours of suspicion per BNM requirements</p>
</div>

@if($pendingAlerts->count() > 0)
<div class="info-box">
    <strong>Pending Alerts:</strong> There are {{ $pendingAlerts->count() }} open alerts that may require STR filing.
    <a href="{{ route('str.create') }}" class="btn btn-sm ml-4">Generate from Alert</a>
</div>
@endif

<form action="{{ route('str.store') }}" method="POST">
    @csrf

    <div class="form-section">
        <h3>Customer Information</h3>

        <div class="form-group">
            <label for="customer_id">Customer *</label>
            <select name="customer_id" id="customer_id" required>
                <option value="">Select Customer</option>
                @foreach(\App\Models\Customer::orderBy('full_name')->get() as $customer)
                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                    {{ $customer->full_name }} ({{ $customer->id_type }}: {{ $customer->id_number_encrypted ? '****' : 'N/A' }})
                </option>
                @endforeach
            </select>
            @error('customer_id')
            <span class="text-danger text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="alert_id">Link to Alert (Optional)</label>
            <select name="alert_id" id="alert_id">
                <option value="">None - Manual STR</option>
                @foreach($pendingAlerts as $alert)
                <option value="{{ $alert->id }}" {{ old('alert_id') == $alert->id ? 'selected' : '' }}>
                    Alert #{{ $alert->id }} - {{ $alert->flag_type->value }} ({{ $alert->flag_reason }})
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-section">
        <h3>Suspicious Transactions</h3>

        <div class="form-group">
            <label for="transaction_ids">Transaction IDs *</label>
            <select name="transaction_ids[]" id="transaction_ids" multiple required size="5">
                @foreach(\App\Models\Transaction::orderBy('created_at', 'desc')->limit(100)->get() as $txn)
                <option value="{{ $txn->id }}" {{ in_array($txn->id, old('transaction_ids', [])) ? 'selected' : '' }}>
                    #{{ $txn->id }} - {{ $txn->customer->full_name ?? 'N/A' }} - RM {{ number_format($txn->amount_local, 2) }} ({{ $txn->created_at->format('Y-m-d H:i') }})
                </option>
                @endforeach
            </select>
            <small class="text-small">Hold Ctrl/Cmd to select multiple transactions</small>
            @error('transaction_ids')
            <span class="text-danger text-sm">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="form-section">
        <h3>Reason for Suspicion</h3>

        <div class="form-group">
            <label for="reason">Detailed Reason *</label>
            <textarea name="reason" id="reason" required minlength="20" placeholder="Describe why this transaction is suspicious and warrants reporting to BNM...">{{ old('reason') }}</textarea>
            @error('reason')
            <span class="text-danger text-sm">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="flex gap-1 mt-6">
        <button type="submit" class="btn btn-primary">Create STR Draft</button>
        <a href="{{ route('str.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection