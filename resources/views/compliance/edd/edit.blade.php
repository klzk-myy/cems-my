@extends('layouts.app')

@section('title', 'Edit EDD Record - CEMS-MY')

@section('content')
<div class="compliance-header">
    <h2>Edit EDD Record: {{ $record->edd_reference }}</h2>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('compliance.edd.update', $record) }}" method="POST">
            @csrf
            @method('PUT')

            <h5>Source of Funds</h5>
            <div class="mb-3">
                <label for="source_of_funds" class="form-label">Source of Funds *</label>
                <select name="source_of_funds" id="source_of_funds" class="form-control" required>
                    <option value="Salary" {{ $record->source_of_funds == 'Salary' ? 'selected' : '' }}>Salary / Employment Income</option>
                    <option value="Business" {{ $record->source_of_funds == 'Business' ? 'selected' : '' }}>Business Income</option>
                    <option value="Investment" {{ $record->source_of_funds == 'Investment' ? 'selected' : '' }}>Investment Returns</option>
                    <option value="Inheritance" {{ $record->source_of_funds == 'Inheritance' ? 'selected' : '' }}>Inheritance</option>
                    <option value="Gift" {{ $record->source_of_funds == 'Gift' ? 'selected' : '' }}>Gift / Donation</option>
                    <option value="Sale of Asset" {{ $record->source_of_funds == 'Sale of Asset' ? 'selected' : '' }}>Sale of Asset</option>
                    <option value="Loan" {{ $record->source_of_funds == 'Loan' ? 'selected' : '' }}>Loan / Borrowed Funds</option>
                    <option value="Savings" {{ $record->source_of_funds == 'Savings' ? 'selected' : '' }}>Savings</option>
                    <option value="Other" {{ $record->source_of_funds == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="source_of_funds_description" class="form-label">Description</label>
                <textarea name="source_of_funds_description" id="source_of_funds_description" class="form-control" rows="3">{{ $record->source_of_funds_description }}</textarea>
            </div>

            <h5>Purpose of Transaction</h5>
            <div class="mb-3">
                <label for="purpose_of_transaction" class="form-label">Purpose *</label>
                <select name="purpose_of_transaction" id="purpose_of_transaction" class="form-control" required>
                    <option value="Business Payment" {{ $record->purpose_of_transaction == 'Business Payment' ? 'selected' : '' }}>Business Payment</option>
                    <option value="Personal Transaction" {{ $record->purpose_of_transaction == 'Personal Transaction' ? 'selected' : '' }}>Personal Transaction</option>
                    <option value="Investment" {{ $record->purpose_of_transaction == 'Investment' ? 'selected' : '' }}>Investment</option>
                    <option value="Education" {{ $record->purpose_of_transaction == 'Education' ? 'selected' : '' }}>Education</option>
                    <option value="Travel" {{ $record->purpose_of_transaction == 'Travel' ? 'selected' : '' }}>Travel</option>
                    <option value="Remittance" {{ $record->purpose_of_transaction == 'Remittance' ? 'selected' : '' }}>Remittance / Money Transfer</option>
                    <option value="Import/Export" {{ $record->purpose_of_transaction == 'Import/Export' ? 'selected' : '' }}>Import/Export Payment</option>
                    <option value="Other" {{ $record->purpose_of_transaction == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('compliance.edd.show', $record) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection