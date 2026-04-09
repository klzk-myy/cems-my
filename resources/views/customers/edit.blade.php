@extends('layouts.app')

@section('title', 'Edit Customer - CEMS-MY')

@section('styles')
<style>
    .edit-customer-header {
        margin-bottom: 1.5rem;
    }
    .edit-customer-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .edit-customer-header p {
        color: #718096;
    }

    .customer-info {
        background: #f7fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .customer-info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .customer-info-row:last-child {
        border-bottom: none;
    }
    .customer-info-label {
        font-weight: 600;
        color: #4a5568;
    }
    .customer-info-value {
        color: #2d3748;
    }

    .form-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .form-section h3 {
        color: #2d3748;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #2d3748;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .form-group label .required {
        color: #e53e3e;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3182ce;
    }
    .form-group .error {
        color: #e53e3e;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }
    .form-group .hint {
        color: #718096;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .checkbox-group input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    .checkbox-group label {
        margin: 0;
        font-weight: normal;
        cursor: pointer;
    }

    .status-indicator {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-active { background: #c6f6d5; color: #276749; }
    .status-inactive { background: #e2e8f0; color: #718096; }

    .risk-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .risk-low { background: #c6f6d5; color: #276749; }
    .risk-medium { background: #feebc8; color: #c05621; }
    .risk-high { background: #fed7d7; color: #c53030; }

    .actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .btn-danger {
        background: #e53e3e;
        color: white;
    }
    .btn-danger:hover {
        background: #c53030;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="edit-customer-header">
<h2>Edit Customer: {{ e($customer->full_name) }}</h2>
<p>Update customer information and KYC details</p>
</div>

<!-- Customer Summary -->
<div class="customer-info">
    <div class="customer-info-row">
        <span class="customer-info-label">Customer ID:</span>
        <span class="customer-info-value">{{ $customer->id }}</span>
    </div>
    <div class="customer-info-row">
        <span class="customer-info-label">Current Status:</span>
        <span class="customer-info-value">
            @if($customer->is_active ?? true)
                <span class="status-indicator status-active">Active</span>
            @else
                <span class="status-indicator status-inactive">Inactive</span>
            @endif
        </span>
    </div>
    <div class="customer-info-row">
        <span class="customer-info-label">Current Risk Rating:</span>
        <span class="customer-info-value">
            <span class="risk-badge risk-{{ strtolower($customer->risk_rating) }}">
                {{ $customer->risk_rating }}
            </span>
        </span>
    </div>
    <div class="customer-info-row">
        <span class="customer-info-label">PEP Status:</span>
        <span class="customer-info-value">
            @if($customer->pep_status)
                <span style="color: #c53030; font-weight: 600;">Yes - Politically Exposed Person</span>
            @else
                No
            @endif
        </span>
    </div>
    <div class="customer-info-row">
        <span class="customer-info-label">Created:</span>
        <span class="customer-info-value">{{ $customer->created_at->format('Y-m-d H:i') }}</span>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error" role="alert" aria-live="assertive">
<strong>Please fix the following errors:</strong>
<ul style="margin-top: 0.5rem; margin-left: 1rem;">
@foreach($errors->all() as $error)
<li>{{ e($error) }}</li>
@endforeach
</ul>
</div>
@endif

<form action="{{ route('customers.update', $customer) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Basic Information -->
    <div class="form-section">
        <h3>Basic Information</h3>
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $customer->full_name) }}" required>
                @error('full_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="id_type">ID Type <span class="required">*</span></label>
                <select id="id_type" name="id_type" required>
                    @foreach($idTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('id_type', $customer->id_type) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('id_type')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="id_number">ID Number <span class="required">*</span></label>
                <input type="text" id="id_number" name="id_number" value="{{ old('id_number', $decryptedIdNumber) }}" required>
                <div class="hint">MyKad format: XXXXXX-XX-XXXX for Malaysian IC</div>
                @error('id_number')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth->format('Y-m-d')) }}" required>
                @error('date_of_birth')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="nationality">Nationality <span class="required">*</span></label>
                <select id="nationality" name="nationality" required>
                    @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" {{ old('nationality', $customer->nationality) == $nat ? 'selected' : '' }}>
                            {{ $nat }}
                        </option>
                    @endforeach
                </select>
                @error('nationality')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="form-section">
        <h3>Contact Information</h3>
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3">{{ old('address') }}</textarea>
                @error('address')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="phone">Contact Number</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g., 60123456789">
                <div class="hint">Malaysian format: 60123456789 or +60123456789</div>
                @error('phone')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}">
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Employment Information -->
    <div class="form-section">
        <h3>Employment Information</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="occupation">Occupation</label>
                <input type="text" id="occupation" name="occupation" value="{{ old('occupation', $customer->occupation) }}">
                @error('occupation')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="employer_name">Employer Name</label>
                <input type="text" id="employer_name" name="employer_name" value="{{ old('employer_name', $customer->employer_name) }}">
                @error('employer_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label for="employer_address">Employer Address</label>
                <textarea id="employer_address" name="employer_address" rows="2">{{ old('employer_address', $customer->employer_address) }}</textarea>
                @error('employer_address')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Risk & Compliance -->
    <div class="form-section">
        <h3>Risk Assessment</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="risk_rating">Risk Rating <span class="required">*</span></label>
                <select id="risk_rating" name="risk_rating" required>
                    @foreach($riskRatings as $rating)
                        <option value="{{ $rating }}" {{ old('risk_rating', $customer->risk_rating) == $rating ? 'selected' : '' }}>
                            {{ $rating }}
                        </option>
                    @endforeach
                </select>
                @error('risk_rating')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <div class="checkbox-group">
                    <input type="checkbox" id="pep_status" name="pep_status" value="1" {{ old('pep_status', $customer->pep_status) ? 'checked' : '' }}>
                    <label for="pep_status">Politically Exposed Person (PEP)</label>
                </div>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <div class="checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
                    <label for="is_active">Customer is active</label>
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        <a href="{{ route('customers.show', $customer) }}" class="btn" style="background: #e2e8f0; color: #4a5568;">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Customer</button>
    </div>
</form>
@endsection
