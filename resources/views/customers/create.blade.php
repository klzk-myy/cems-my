@extends('layouts.app')

@section('title', 'Create Customer - CEMS-MY')

@section('styles')
<style>
    .create-customer-header {
        margin-bottom: 1.5rem;
    }
    .create-customer-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .create-customer-header p {
        color: #718096;
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

    .risk-indicator {
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

    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }
    .alert-warning {
        background: #fffaf0;
        border-left: 4px solid #dd6b20;
        color: #c05621;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="create-customer-header">
    <h2>Create New Customer</h2>
    <p>Add a new customer with KYC information for compliance tracking</p>
</div>

@if($errors->any())
    <div class="alert alert-error">
        <strong>Please fix the following errors:</strong>
        <ul style="margin-top: 0.5rem; margin-left: 1rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('customers.store') }}" method="POST">
    @csrf

    <!-- Basic Information -->
    <div class="form-section">
        <h3>Basic Information</h3>
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required placeholder="As shown on ID document">
                @error('full_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="id_type">ID Type <span class="required">*</span></label>
                <select id="id_type" name="id_type" required onchange="toggleIdHint(this.value)">
                    @foreach($idTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('id_type') == $key ? 'selected' : '' }}>
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
                <input type="text" id="id_number" name="id_number" value="{{ old('id_number') }}" required placeholder="">
                <div id="id_hint" class="hint">MyKad format: XXXXXX-XX-XXXX (e.g., 900123-01-2345)</div>
                @error('id_number')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required max="{{ date('Y-m-d', strtotime('-18 years')) }}">
                @error('date_of_birth')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="nationality">Nationality <span class="required">*</span></label>
                <select id="nationality" name="nationality" required>
                    @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" {{ old('nationality') == $nat ? 'selected' : '' }}>
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
                <textarea id="address" name="address" rows="3" placeholder="Full residential address">{{ old('address') }}</textarea>
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
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="customer@example.com">
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Employment Information (Optional) -->
    <div class="form-section">
        <h3>Employment Information (Optional)</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="occupation">Occupation</label>
                <input type="text" id="occupation" name="occupation" value="{{ old('occupation') }}" placeholder="e.g., Engineer, Business Owner">
                @error('occupation')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="employer_name">Employer Name</label>
                <input type="text" id="employer_name" name="employer_name" value="{{ old('employer_name') }}" placeholder="Company or organization name">
                @error('employer_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label for="employer_address">Employer Address</label>
                <textarea id="employer_address" name="employer_address" rows="2" placeholder="Employer business address">{{ old('employer_address') }}</textarea>
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
                <label for="risk_rating">Initial Risk Rating <span class="required">*</span></label>
                <select id="risk_rating" name="risk_rating" required>
                    @foreach($riskRatings as $rating)
                        <option value="{{ $rating }}" {{ old('risk_rating') == $rating ? 'selected' : '' }}>
                            {{ $rating }}
                        </option>
                    @endforeach
                </select>
                <div class="hint">
                    Risk will be automatically assessed based on nationality and PEP status
                </div>
                @error('risk_rating')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <div class="checkbox-group">
                    <input type="checkbox" id="pep_status" name="pep_status" value="1" {{ old('pep_status') ? 'checked' : '' }}>
                    <label for="pep_status">Politically Exposed Person (PEP)</label>
                </div>
                <div class="hint">Check if customer holds or held a prominent public position</div>
            </div>
        </div>

        <div style="margin-top: 1rem; padding: 1rem; background: #fffaf0; border-radius: 6px; border-left: 4px solid #dd6b20;">
            <strong style="color: #c05621;">Compliance Notice:</strong>
            <p style="margin-top: 0.5rem; color: #744210; font-size: 0.875rem;">
                Upon submission, the customer's name will be automatically screened against sanctions lists.
                Customers from high-risk countries or with PEP status will be assigned higher risk ratings.
            </p>
        </div>
    </div>

    <div class="actions">
        <a href="{{ route('customers.index') }}" class="btn" style="background: #e2e8f0; color: #4a5568;">Cancel</a>
        <button type="submit" class="btn btn-success">Create Customer</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    function toggleIdHint(value) {
        const hint = document.getElementById('id_hint');
        const idInput = document.getElementById('id_number');

        if (value === 'MyKad') {
            hint.textContent = 'MyKad format: XXXXXX-XX-XXXX (e.g., 900123-01-2345)';
            idInput.placeholder = '900123-01-2345';
        } else if (value === 'Passport') {
            hint.textContent = 'Passport number as shown in passport';
            idInput.placeholder = 'AB123456';
        } else {
            hint.textContent = 'Enter ID number as shown on document';
            idInput.placeholder = '';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const idType = document.getElementById('id_type').value;
        toggleIdHint(idType);
    });
</script>
@endsection
