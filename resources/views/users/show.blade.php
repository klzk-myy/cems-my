@extends('layouts.app')

@section('title', 'User Details - CEMS-MY')

@section('content')
<div class="user-header">
    <h2>User Details</h2>
    <div class="header-actions">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">Edit User</a>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">
    <!-- User Information -->
    <div class="card">
        <h2>Basic Information</h2>
        <table class="detail-table">
            <tr>
                <th>User ID</th>
                <td>{{ $user->id }}</td>
            </tr>
            <tr>
                <th>Username</th>
                <td>{{ $user->username }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <th>Role</th>
                <td>
                    <span class="role-badge role-{{ $user->role }}">
                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>MFA Enabled</th>
                <td>
                    <span class="status-badge {{ $user->mfa_enabled ? 'status-active' : 'status-inactive' }}">
                        {{ $user->mfa_enabled ? 'Yes' : 'No' }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Created</th>
                <td>{{ $user->created_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            <tr>
                <th>Last Login</th>
                <td>{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never' }}</td>
            </tr>
        </table>
    </div>
    
    <!-- Permissions -->
    <div class="card">
        <h2>Role Permissions</h2>
        <div class="permissions-grid">
            <div class="permission-item">
                <span class="permission-label">Create Transactions</span>
                <span class="permission-value {{ in_array($user->role, ['teller', 'manager', 'compliance_officer', 'admin']) ? 'granted' : 'denied' }}">
                    {{ in_array($user->role, ['teller', 'manager', 'compliance_officer', 'admin']) ? '✓' : '✗' }}
                </span>
            </div>
            <div class="permission-item">
                <span class="permission-label">Approve Transactions ≥ RM 50k</span>
                <span class="permission-value {{ in_array($user->role, ['manager', 'admin']) ? 'granted' : 'denied' }}">
                    {{ in_array($user->role, ['manager', 'admin']) ? '✓' : '✗' }}
                </span>
            </div>
            <div class="permission-item">
                <span class="permission-label">View Compliance Portal</span>
                <span class="permission-value {{ in_array($user->role, ['compliance_officer', 'admin']) ? 'granted' : 'denied' }}">
                    {{ in_array($user->role, ['compliance_officer', 'admin']) ? '✓' : '✗' }}
                </span>
            </div>
            <div class="permission-item">
                <span class="permission-label">Manage Stock/Cash</span>
                <span class="permission-value {{ in_array($user->role, ['manager', 'admin']) ? 'granted' : 'denied' }}">
                    {{ in_array($user->role, ['manager', 'admin']) ? '✓' : '✗' }}
                </span>
            </div>
            <div class="permission-item">
                <span class="permission-label">Run Reports</span>
                <span class="permission-value {{ in_array($user->role, ['manager', 'compliance_officer', 'admin']) ? 'granted' : 'denied' }}">
                    {{ in_array($user->role, ['manager', 'compliance_officer', 'admin']) ? '✓' : '✗' }}
                </span>
            </div>
            <div class="permission-item">
                <span class="permission-label">Manage Users</span>
                <span class="permission-value {{ $user->role === 'admin' ? 'granted' : 'denied' }}">
                    {{ $user->role === 'admin' ? '✓' : '✗' }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History (if applicable) -->
<div class="card">
    <h2>Recent Transactions</h2>
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
            @forelse($user->transactions()->latest()->take(10)->get() as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->type }}</td>
                <td>{{ $transaction->currency_code }}</td>
                <td>RM {{ number_format($transaction->amount_local, 2) }}</td>
                <td>{{ $transaction->rate }}</td>
                <td>
                    <span class="status-badge status-{{ strtolower($transaction->status) }}">
                        {{ $transaction->status }}
                    </span>
                </td>
                <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: #718096;">
                    No transactions found for this user.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- System Log Activity -->
<div class="card">
    <h2>Recent System Activity</h2>
    <table>
        <thead>
            <tr>
                <th>Action</th>
                <th>Entity</th>
                <th>Description</th>
                <th>IP Address</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @php
            $logs = App\Models\SystemLog::where('user_id', $user->id)
                ->latest()
                ->take(10)
                ->get();
            @endphp
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->action }}</td>
                <td>{{ $log->entity_type }} {{ $log->entity_id }}</td>
                <td>{{ Str::limit($log->description ?? '-', 50) }}</td>
                <td>{{ $log->ip_address }}</td>
                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 2rem; color: #718096;">
                    No activity logs found for this user.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@section('styles')
<style>
    .user-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
    }
    .detail-table {
        width: 100%;
    }
    .detail-table th,
    .detail-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .detail-table th {
        width: 35%;
        text-align: left;
        color: #718096;
        font-weight: 500;
    }
    .detail-table td {
        color: #2d3748;
    }
    .role-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .role-admin {
        background: #fed7d7;
        color: #c53030;
    }
    .role-manager {
        background: #c6f6d5;
        color: #22543d;
    }
    .role-compliance_officer {
        background: #fef3c7;
        color: #92400e;
    }
    .role-teller {
        background: #dbeafe;
        color: #1e40af;
    }
    .permissions-grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .permission-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: #f7fafc;
        border-radius: 4px;
    }
    .permission-value {
        font-weight: 600;
        font-size: 1.25rem;
    }
    .permission-value.granted {
        color: #38a169;
    }
    .permission-value.denied {
        color: #e53e3e;
    }
    .status-inactive {
        background: #fed7d7;
        color: #c53030;
    }
</style>
@endsection
@endsection
