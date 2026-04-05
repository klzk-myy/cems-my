@extends('layouts.app')

@section('title', 'Create STR - CEMS-MY')

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
    .alert-box {
        background: #fed7d7;
        border-left: 4px solid #e53e3e;
        padding: 1rem;
        margin-bottom: 1.5rem;
        color: #c53030;
    }
    .info-box {
        background: #ebf8ff;
        border-left: 4px solid #3182ce;
        padding: 1rem;
        margin-bottom: 1.5rem;
        color: #2b6cb0;
    }
</style>
@endsection

@section('content')
<div class="str-header">
    <h2>Create Suspicious Transaction Report</h2>
    <p>File STR within 24 hours of suspicion per BNM requirements</p>
</div>

@if($pendingAlerts->count() > 0)
<div class="info-box">
    <strong>Pending Alerts:</strong> There are {{ $pendingAlerts->count() }} open alerts that may require STR filing.
    <a href="{{ route('str.create') }}" class="btn btn-sm" style="margin-left: 1rem;">Generate from Alert</a>
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
            <span style="color: #e53e3e; font-size: 0.875rem;">{{ $message }}</span>
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
            <small style="color: #718096;">Hold Ctrl/Cmd to select multiple transactions</small>
            @error('transaction_ids')
            <span style="color: #e53e3e; font-size: 0.875rem;">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="form-section">
        <h3>Reason for Suspicion</h3>

        <div class="form-group">
            <label for="reason">Detailed Reason *</label>
            <textarea name="reason" id="reason" required minlength="20" placeholder="Describe why this transaction is suspicious and warrants reporting to BNM...">{{ old('reason') }}</textarea>
            @error('reason')
            <span style="color: #e53e3e; font-size: 0.875rem;">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="flex gap-1" style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Create STR Draft</button>
        <a href="{{ route('str.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
