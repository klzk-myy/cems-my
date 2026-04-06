@extends('layouts.app')

@section('title', 'New EDD Record - CEMS-MY')

@section('content')
<div class="compliance-header">
    <h2>New EDD Record</h2>
    <p>Enhanced Due Diligence for High-Risk Customer</p>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('compliance.edd.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="customer_id" class="form-label">Customer *</label>
                <select name="customer_id" id="customer_id" class="form-control" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->risk_rating }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="risk_level" class="form-label">Risk Level *</label>
                <select name="risk_level" id="risk_level" class="form-control" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <hr>

            <h5>Source of Funds</h5>

            <div class="mb-3">
                <label for="source_of_funds" class="form-label">Source of Funds *</label>
                <select name="source_of_funds" id="source_of_funds" class="form-control" required>
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

            <div class="mb-3">
                <label for="source_of_funds_description" class="form-label">Source of Funds Description</label>
                <textarea name="source_of_funds_description" id="source_of_funds_description" class="form-control" rows="3"></textarea>
            </div>

            <hr>

            <h5>Purpose of Transaction</h5>

            <div class="mb-3">
                <label for="purpose_of_transaction" class="form-label">Purpose of Transaction *</label>
                <select name="purpose_of_transaction" id="purpose_of_transaction" class="form-control" required>
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

            <div class="mb-3">
                <label for="business_justification" class="form-label">Business Justification</label>
                <textarea name="business_justification" id="business_justification" class="form-control" rows="3"></textarea>
            </div>

            <hr>

            <h5>Employment Information</h5>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="employment_status" class="form-label">Employment Status</label>
                        <input type="text" name="employment_status" id="employment_status" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="employer_name" class="form-label">Employer Name</label>
                        <input type="text" name="employer_name" id="employer_name" class="form-control">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="annual_income_range" class="form-label">Annual Income Range</label>
                        <input type="text" name="annual_income_range" id="annual_income_range" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="estimated_net_worth" class="form-label">Estimated Net Worth</label>
                        <input type="text" name="estimated_net_worth" id="estimated_net_worth" class="form-control">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Create EDD Record</button>
                <a href="{{ route('compliance.edd.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection