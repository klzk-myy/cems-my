@extends('layouts.app')

@section('title', 'KYC Documents - CEMS-MY')

@section('styles')
<style>
    .kyc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .kyc-header h2 {
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    .kyc-header p {
        color: #718096;
        font-size: 0.875rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .customer-summary {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .customer-info {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
    }
    .customer-field {
        display: flex;
        flex-direction: column;
    }
    .customer-field-label {
        font-size: 0.75rem;
        color: #718096;
        text-transform: uppercase;
    }
    .customer-field-value {
        font-weight: 600;
        color: #2d3748;
    }

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

    .document-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .document-section h3 {
        color: #2d3748;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .upload-form {
        border: 2px dashed #e2e8f0;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .upload-form:hover {
        border-color: #3182ce;
        background: #f7fafc;
    }
    .upload-form p {
        color: #718096;
        margin-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
        text-align: left;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #2d3748;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 1rem;
    }
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3182ce;
    }
    .form-group .hint {
        color: #718096;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }
    .form-group .error {
        color: #e53e3e;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .document-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }
    .document-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        position: relative;
    }
    .document-card.verified {
        border-color: #38a169;
        background: #f0fff4;
    }
    .document-card.pending {
        border-color: #dd6b20;
        background: #fffaf0;
    }
    .document-card.expired {
        border-color: #e53e3e;
        background: #fff5f5;
    }

    .document-type {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .document-meta {
        font-size: 0.75rem;
        color: #718096;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .document-status-badge {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-top: 0.5rem;
    }
    .status-verified { background: #c6f6d5; color: #276749; }
    .status-pending { background: #feebc8; color: #c05621; }
    .status-expired { background: #fed7d7; color: #c53030; }

    .document-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.75rem; }

    .btn-success { background: #38a169; color: white; }
    .btn-danger { background: #e53e3e; color: white; }
    .btn-warning { background: #dd6b20; color: white; }

    .info-box {
        background: #ebf8ff;
        border-left: 4px solid #3182ce;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 0 6px 6px 0;
    }
    .info-box p {
        color: #2b6cb0;
        font-size: 0.875rem;
    }

    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }
    .alert-success {
        background: #c6f6d5;
        border-left: 4px solid #38a169;
        color: #276749;
    }
    .alert-warning {
        background: #fffaf0;
        border-left: 4px solid #dd6b20;
        color: #c05621;
    }
    .alert-error {
        background: #fed7d7;
        border-left: 4px solid #e53e3e;
        color: #c53030;
    }

    .required-docs {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 6px;
    }
    .required-docs h4 {
        color: #2d3748;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    .required-docs ul {
        margin: 0;
        padding-left: 1.5rem;
        color: #4a5568;
        font-size: 0.875rem;
    }
    .required-docs li {
        margin-bottom: 0.25rem;
    }
</style>
@endsection

@section('content')
<div class="kyc-header">
    <div>
        <h2>KYC Document Management</h2>
        <p>Upload and verify customer identification documents</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm" style="background: #e2e8f0; color: #4a5568;">Back to Profile</a>
        <a href="{{ route('customers.index') }}" class="btn btn-sm" style="background: #e2e8f0; color: #4a5568;">Back to List</a>
    </div>
</div>

<!-- Customer Summary -->
<div class="customer-summary">
    <div class="customer-info">
        <div class="customer-field">
            <span class="customer-field-label">Customer Name</span>
            <span class="customer-field-value">{{ $customer->full_name }}</span>
        </div>
        <div class="customer-field">
            <span class="customer-field-label">ID Type</span>
            <span class="customer-field-value">{{ $customer->id_type }}</span>
        </div>
        <div class="customer-field">
            <span class="customer-field-label">Risk Rating</span>
            <span class="customer-field-value">
                <span class="risk-badge risk-{{ strtolower($customer->risk_rating) }}">
                    {{ $customer->risk_rating }}
                </span>
            </span>
        </div>
        <div class="customer-field">
            <span class="customer-field-label">Documents</span>
            <span class="customer-field-value">
                {{ $documents->where('verified_at', '!=', null)->count() }} / {{ $documents->count() }} Verified
            </span>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<!-- Document Upload Section -->
<div class="document-section">
    <h3>Upload New Document</h3>

    <div class="upload-form">
        <form action="{{ route('customers.kyc.upload', $customer) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="document_type">Document Type <span style="color: #e53e3e;">*</span></label>
                <select id="document_type" name="document_type" required>
                    <option value="">Select document type...</option>
                    @foreach($documentTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('document_type')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="document_file">Document File <span style="color: #e53e3e;">*</span></label>
                <input type="file" id="document_file" name="document_file" required accept=".jpg,.jpeg,.png,.pdf">
                <div class="hint">Accepted formats: JPG, PNG, PDF. Maximum file size: 10MB</div>
                @error('document_file')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="expiry_date">Expiry Date (Optional)</label>
                <input type="date" id="expiry_date" name="expiry_date" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                <div class="hint">For passports and some ID cards. Leave blank if document doesn't expire.</div>
                @error('expiry_date')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-success">Upload Document</button>
        </form>
    </div>

    @if($customer->id_type === 'MyKad')
    <div class="required-docs">
        <h4>Required Documents for MyKad Customer:</h4>
        <ul>
            <li>MyKad Front (Required)</li>
            <li>MyKad Back (Required)</li>
            <li>Proof of Address (If address differs from IC)</li>
        </ul>
    </div>
    @elseif($customer->id_type === 'Passport')
    <div class="required-docs">
        <h4>Required Documents for Passport Customer:</h4>
        <ul>
            <li>Passport (Required - must show photo and personal details page)</li>
            <li>Proof of Address (Required)</li>
        </ul>
    </div>
    @endif
</div>

<!-- Document List Section -->
<div class="document-section">
    <h3>Uploaded Documents ({{ $documents->count() }})</h3>

    @if($documents->count() > 0)
        <div class="document-grid">
            @foreach($documents as $document)
                <div class="document-card {{ $document->isVerified() ? 'verified' : ($document->isExpired() ? 'expired' : 'pending') }}">
                    <div class="document-type">
                        {{ $documentTypes[$document->document_type] ?? $document->document_type }}
                    </div>
                    <div class="document-meta">
                        <span>Uploaded: {{ $document->created_at->format('Y-m-d H:i') }}</span>
                        @if($document->uploader)
                            <span>By: {{ $document->uploader->username }}</span>
                        @endif
                        <span>Size: {{ number_format($document->file_size / 1024, 2) }} KB</span>

                        @if($document->expiry_date)
                            <span>Expires: {{ $document->expiry_date->format('Y-m-d') }}</span>
                        @endif

                        @if($document->isVerified())
                            <span class="document-status-badge status-verified">
                                Verified by {{ $document->verifier->username ?? 'Unknown' }} on {{ $document->verified_at->format('Y-m-d') }}
                            </span>
                        @elseif($document->isExpired())
                            <span class="document-status-badge status-expired">Expired</span>
                        @else
                            <span class="document-status-badge status-pending">Pending Verification</span>
                        @endif
                    </div>

                    <div class="document-actions">
                        @if(! $document->isVerified() && $canVerify)
                            <form action="{{ route('customers.kyc.verify', [$customer, $document]) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Verify</button>
                            </form>
                        @endif

                        @if($document->isVerified())
                            <span style="color: #38a169; font-size: 0.75rem; align-self: center;">Verified</span>
                        @endif

                        <form action="{{ route('customers.kyc.delete', [$customer, $document]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this document?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 3rem; color: #718096;">
            <p>No documents uploaded yet.</p>
            <p>Please upload the required KYC documents above.</p>
        </div>
    @endif
</div>

<!-- Verification Notice -->
@if($canVerify)
    <div class="info-box">
        <p><strong>Compliance Officer/Admin Notice:</strong> You have permission to verify customer documents. Please ensure all verified documents are genuine and legible before approving.</p>
    </div>
@else
    <div class="info-box">
        <p><strong>Note:</strong> Only Compliance Officers and Administrators can verify KYC documents. Please contact your supervisor after uploading all required documents.</p>
    </div>
@endif
@endsection
