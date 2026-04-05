@extends('layouts.app')

@section('title', 'Customer Profile - CEMS-MY')

@section('styles')
<style>
    .customer-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .customer-header h2 {
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    .customer-header p {
        color: #718096;
        font-size: 0.875rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .info-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h3 {
        color: #2d3748;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
        font-size: 1rem;
    }

    .detail-table {
        width: 100%;
    }
    .detail-table th,
    .detail-table td {
        padding: 0.625rem 0.5rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    .detail-table th {
        width: 40%;
        color: #718096;
        font-weight: 500;
        font-size: 0.875rem;
    }
    .detail-table td {
        color: #2d3748;
        font-size: 0.875rem;
    }
    .detail-table tr:last-child th,
    .detail-table tr:last-child td {
        border-bottom: none;
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

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-active { background: #c6f6d5; color: #276749; }
    .status-inactive { background: #e2e8f0; color: #718096; }

    .pep-badge {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .pep-yes { background: #fed7d7; color: #c53030; }
    .pep-no { background: #e2e8f0; color: #718096; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 6px;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
    }
    .stat-label {
        font-size: 0.75rem;
        color: #718096;
        margin-top: 0.25rem;
    }

    .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.75rem; }
    .btn-warning { background: #dd6b20; color: white; }
    .btn-danger { background: #e53e3e; color: white; }

    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }
    .alert-warning {
        background: #fffaf0;
        border-left: 4px solid #dd6b20;
        color: #c05621;
    }

    .document-status {
        display: flex;
        gap: 1rem;
        margin-top: 0.5rem;
    }
    .document-stat {
        font-size: 0.875rem;
    }
    .document-stat strong {
        color: #2d3748;
    }
    .document-stat span {
        color: #718096;
    }

    .warning-box {
        background: #fffaf0;
        border-left: 4px solid #dd6b20;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 0 6px 6px 0;
    }
    .warning-box h4 {
        color: #c05621;
        margin-bottom: 0.5rem;
    }
    .warning-box p {
        color: #744210;
        font-size: 0.875rem;
    }
</style>
@endsection

@section('content')
<div class="customer-header">
    <div>
        <h2>{{ $customer->full_name }}</h2>
        <p>Customer ID: {{ $customer->id }} | Created: {{ $customer->created_at->format('Y-m-d H:i') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm">Edit</a>
        <a href="{{ route('customers.kyc', $customer) }}" class="btn btn-primary btn-sm">KYC Documents</a>
        <a href="{{ route('customers.index') }}" class="btn btn-sm" style="background: #e2e8f0; color: #4a5568;">Back to List</a>
    </div>
</div>

<!-- Risk Warning -->
@if($customer->risk_rating === 'High' || $customer->pep_status)
    <div class="warning-box">
        <h4>High Risk Customer Alert</h4>
        @if($customer->risk_rating === 'High')
            <p>This customer has been flagged as <strong>High Risk</strong>. All transactions require enhanced due diligence (EDD) and manager approval.</p>
        @endif
        @if($customer->pep_status)
            <p style="margin-top: 0.5rem;">Customer is a <strong>Politically Exposed Person (PEP)</strong>. Additional monitoring and approval requirements apply.</p>
        @endif
    </div>
@endif

<div class="profile-grid">
    <!-- Basic Information -->
    <div class="info-card">
        <h3>Basic Information</h3>
        <table class="detail-table">
            <tr>
                <th>Full Name</th>
                <td>{{ $customer->full_name }}</td>
            </tr>
            <tr>
                <th>ID Type</th>
                <td>{{ $customer->id_type }}</td>
            </tr>
            <tr>
                <th>ID Number</th>
                <td>{{ Str::limit($customer->id_number_encrypted, 8, '****') }}</td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td>{{ $customer->date_of_birth->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <th>Nationality</th>
                <td>{{ $customer->nationality }}</td>
            </tr>
            <tr>
                <th>Risk Rating</th>
                <td>
                    <span class="risk-badge risk-{{ strtolower($customer->risk_rating) }}">
                        {{ $customer->risk_rating }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Risk Score</th>
                <td>{{ $customer->risk_score ?? 0 }} / 100</td>
            </tr>
            <tr>
                <th>PEP Status</th>
                <td>
                    @if($customer->pep_status)
                        <span class="pep-badge pep-yes">PEP</span>
                    @else
                        <span class="pep-badge pep-no">Non-PEP</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if($customer->is_active ?? true)
                        <span class="status-badge status-active">Active</span>
                    @else
                        <span class="status-badge status-inactive">Inactive</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Contact Information -->
    <div class="info-card">
        <h3>Contact Information</h3>
        <table class="detail-table">
            <tr>
                <th>Address</th>
                <td>{{ $customer->address ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>{{ $customer->phone ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $customer->email ?? 'Not provided' }}</td>
            </tr>
        </table>
    </div>

    <!-- Employment Information -->
    <div class="info-card">
        <h3>Employment Information</h3>
        <table class="detail-table">
            <tr>
                <th>Occupation</th>
                <td>{{ $customer->occupation ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Employer Name</th>
                <td>{{ $customer->employer_name ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Employer Address</th>
                <td>{{ $customer->employer_address ?? 'Not provided' }}</td>
            </tr>
        </table>
    </div>

    <!-- Transaction Summary -->
    <div class="info-card">
        <h3>Transaction Summary</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">{{ $transactionStats['total_transactions'] }}</div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">RM {{ number_format($transactionStats['total_volume'], 2) }}</div>
                <div class="stat-label">Total Volume</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">RM {{ number_format($transactionStats['avg_transaction'], 2) }}</div>
                <div class="stat-label">Avg Transaction</div>
            </div>
        </div>
        @if($transactionStats['last_transaction'])
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #718096;">
                Last transaction: {{ $transactionStats['last_transaction']->diffForHumans() }}
            </p>
        @endif
        <div style="margin-top: 1rem;">
            <a href="{{ route('customers.history', $customer) }}" class="btn btn-sm" style="background: #3182ce; color: white;">View Full History</a>
        </div>
    </div>
</div>

<!-- KYC Document Status -->
<div class="info-card">
    <h3>KYC Document Status</h3>
    <div class="document-status">
        <div class="document-stat">
            <strong>{{ $documentStatus['total'] }}</strong> <span>Total Documents</span>
        </div>
        <div class="document-stat">
            <strong style="color: #38a169;">{{ $documentStatus['verified'] }}</strong> <span>Verified</span>
        </div>
        <div class="document-stat">
            <strong style="color: #dd6b20;">{{ $documentStatus['pending'] }}</strong> <span>Pending Verification</span>
        </div>
        @if($documentStatus['expired'] > 0)
        <div class="document-stat">
            <strong style="color: #e53e3e;">{{ $documentStatus['expired'] }}</strong> <span>Expired</span>
        </div>
        @endif
    </div>
    <div style="margin-top: 1rem;">
        <a href="{{ route('customers.kyc', $customer) }}" class="btn btn-sm" style="background: #38a169; color: white;">Manage KYC Documents</a>
    </div>
</div>

<!-- Recent Transactions -->
<div class="info-card" style="margin-top: 1.5rem;">
    <h3>Recent Transactions</h3>
    @if($customer->transactions->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Currency</th>
                    <th>Amount</th>
                    <th>Rate</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customer->transactions as $transaction)
                <tr>
                    <td>{{ $transaction->id }}</td>
                    <td>{{ $transaction->type->value ?? $transaction->type }}</td>
                    <td>{{ $transaction->currency_code }}</td>
                    <td>RM {{ number_format($transaction->amount_local, 2) }}</td>
                    <td>{{ $transaction->rate }}</td>
                    <td>
                        <span class="status-badge status-{{ strtolower($transaction->status->value ?? $transaction->status) }}">
                            {{ $transaction->status->label() ?? $transaction->status }}
                        </span>
                    </td>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; padding: 2rem; color: #718096;">No transactions found for this customer.</p>
    @endif
</div>
@endsection
