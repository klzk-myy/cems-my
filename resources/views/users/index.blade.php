@extends('layouts.app')

@section('title', 'User Management - CEMS-MY')

@section('styles')
<style>
    .users-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .users-header h2 {
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    .users-header p {
        color: #718096;
        font-size: 0.875rem;
    }

    .role-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .role-admin { background: #fed7d7; color: #c53030; }
    .role-manager { background: #feebc8; color: #c05621; }
    .role-compliance { background: #ebf8ff; color: #2b6cb0; }
    .role-teller { background: #c6f6d5; color: #276749; }

    .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.75rem; }
    .actions { display: flex; gap: 0.5rem; }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .pagination a, .pagination span {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
    }
    .pagination a { background: #e2e8f0; color: #4a5568; }
    .pagination span { background: #3182ce; color: white; }
</style>
@endsection

@section('content')
<div class="users-header">
    <div>
        <h2>User Management</h2>
        <p>Manage users, roles, and permissions</p>
    </div>
    <a href="/users/create" class="btn btn-success">+ Add New User</a>
</div>

<div class="card">
    <h2>Users ({{ $users->total() }})</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td><strong>{{ $user->username }}</strong></td>
                <td>{{ $user->email }}</td>
                <td>
                    @php
                        $roleClass = match($user->role) {
                            'admin' => 'role-admin',
                            'manager' => 'role-manager',
                            'compliance_officer' => 'role-compliance',
                            default => 'role-teller'
                        };
                        $roleLabel = match($user->role) {
                            'admin' => 'Admin',
                            'manager' => 'Manager',
                            'compliance_officer' => 'Compliance',
                            default => 'Teller'
                        };
                    @endphp
                    <span class="role-badge {{ $roleClass }}">{{ $roleLabel }}</span>
                </td>
                <td>
                    @if($user->is_active)
                        <span class="status-badge status-active">Active</span>
                    @else
                        <span class="status-badge status-inactive">Inactive</span>
                    @endif
                </td>
                <td>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
                <td>{{ $user->created_at->format('Y-m-d') }}</td>
                <td>
                    <div class="actions">
                        <a href="/users/{{ $user->id }}/edit" class="btn btn-primary btn-sm">Edit</a>
                        <form action="/users/{{ $user->id }}/toggle" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <form action="/users/{{ $user->id }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $users->links() }}
    </div>
</div>

<!-- Role Permissions Reference -->
<div class="card">
    <h2>Role Permissions Matrix</h2>
    <table>
        <thead>
            <tr>
                <th>Feature</th>
                <th>Teller</th>
                <th>Manager</th>
                <th>Compliance</th>
                <th>Admin</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Create Transaction</td>
                <td>✅</td>
                <td>✅</td>
                <td>✅</td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Approve >RM 50k</td>
                <td>❌</td>
                <td>✅</td>
                <td>✅</td>
                <td>✅</td>
            </tr>
            <tr>
                <td>View Compliance</td>
                <td>❌</td>
                <td>❌</td>
                <td>✅</td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Manage Users</td>
                <td>❌</td>
                <td>❌</td>
                <td>❌</td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Run Reports</td>
                <td>❌</td>
                <td>✅</td>
                <td>✅</td>
                <td>✅</td>
            </tr>
            <tr>
                <td>System Config</td>
                <td>❌</td>
                <td>❌</td>
                <td>❌</td>
                <td>✅</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
