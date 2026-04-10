@extends('layouts.app')

@section('title', 'New EDD Record - CEMS-MY')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">New EDD Record</h1>
        <p class="page-header__subtitle">Enhanced Due Diligence for High-Risk Customer</p>
    </div>
</div>

<div class="card">
    @if($errors->any())
        <div class="p-4 mb-6 rounded bg-red-100 text-red-800 border border-red-300">
            <strong>Please fix the following errors:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('compliance.edd.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="customer_id" class="form-label">Customer *</label>
            <select name="customer_id" id="customer_id" class="form-input" required>
                <option value="">Select Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->risk_rating }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="risk_level" class="form-label">Risk Level *</label>
            <select name="risk_level" id="risk_level" class="form-input" required>
                <option value="Low">Low</option>
                <option value="Medium" selected>Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
            </select>
        </div>

        <hr class="my-6">

        <h5 class="font-semibold text-gray-800 mb-4">Source of Funds</h5>

        <div class="form-group">
            <label for="source_of_funds" class="form-label">Source of Funds *</label>
            <select name="source_of_funds" id="source_of_funds" class="form-input" required>
                <option value="">Select Source</option>
                <option value="Salary">Salary / Employment Income</option>
                <option value="Business">Business Income</option>
                <option value="Investment">Investment Returns</option>
                <option value="Inheritance">Inheritance</option>
                <option value="Gift">Gift / Donation</option>
                <option value="Sale of Asset">Sale of Asset</option>
                <option value="Loan">Loan / Borrowed Funds</option>
                <option value="Savings">Savings</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="source_of_funds_description" class="form-label">Source of Funds Description</label>
            <textarea name="source_of_funds_description" id="source_of_funds_description" class="form-input" rows="3"></textarea>
        </div>

        <hr class="my-6">

        <h5 class="font-semibold text-gray-800 mb-4">Purpose of Transaction</h5>

        <div class="form-group">
            <label for="purpose_of_transaction" class="form-label">Purpose of Transaction *</label>
            <select name="purpose_of_transaction" id="purpose_of_transaction" class="form-input" required>
                <option value="">Select Purpose</option>
                <option value="Business Payment">Business Payment</option>
                <option value="Personal Transaction">Personal Transaction</option>
                <option value="Investment">Investment</option>
                <option value="Education">Education</option>
                <option value="Travel">Travel</option>
                <option value="Remittance">Remittance / Money Transfer</option>
                <option value="Import/Export">Import/Export Payment</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="business_justification" class="form-label">Business Justification</label>
            <textarea name="business_justification" id="business_justification" class="form-input" rows="3"></textarea>
        </div>

        <hr class="my-6">

        <h5 class="font-semibold text-gray-800 mb-4">Employment Information</h5>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="employment_status" class="form-label">Employment Status</label>
                <input type="text" name="employment_status" id="employment_status" class="form-input">
            </div>
            <div class="form-group">
                <label for="employer_name" class="form-label">Employer Name</label>
                <input type="text" name="employer_name" id="employer_name" class="form-input">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="annual_income_range" class="form-label">Annual Income Range</label>
                <input type="text" name="annual_income_range" id="annual_income_range" class="form-input">
            </div>
            <div class="form-group">
                <label for="estimated_net_worth" class="form-label">Estimated Net Worth</label>
                <input type="text" name="estimated_net_worth" id="estimated_net_worth" class="form-input">
            </div>
        </div>

        <div class="flex gap-4 pt-6 border-t border-gray-200">
            <button type="submit" class="btn btn--primary">Create EDD Record</button>
            <a href="{{ route('compliance.edd.index') }}" class="btn btn--secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection