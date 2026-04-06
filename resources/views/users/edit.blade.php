@extends('layouts.app')

@section('title', 'Edit User - CEMS-MY')

@section('styles')
<style>
    .edit-user-header {
        margin-bottom: 1.5rem;
    }
    .edit-user-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .edit-user-header p {
        color: #718096;
    }

    .user-info {
        background: #f7fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .user-info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .user-info-row:last-child {
        border-bottom: none;
    }
    .user-info-label {
        font-weight: 600;
        color: #4a5568;
    }
    .user-info-value {
        color: #2d3748;
    }

    .form-group {
        margin-bottom: 1.25rem;
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
        transition: border-color 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3182ce;
    }
    .form-group .error {
        color: #e53e3e;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
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
        margin-left: 0.5rem;
    }
    .status-active {
        background: #c6f6d5;
        color: #276749;
    }
    .status-inactive {
        background: #e2e8f0;
        color: #718096;
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

    .actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="edit-user-header">
    <h2>Edit User: {{ $user->username }}</h2>
    <p>Update user information and permissions</p>
</div>

<div class="card">
    <div class="user-info">
        <div class="user-info-row">
            <span class="user-info-label">User ID:</span>
            <span class="user-info-value">{{ $user->id }}</span>
        </div>
        <div class="user-info-row">
            <span class="user-info-label">Current Role:</span>
            <span class="user-info-value">
@php
                $roleClass = match($user->role->value) {
                    'admin' => 'role-admin',
                    'manager' => 'role-manager',
                    'compliance_officer' => 'role-compliance',
                    default => 'role-teller'
                };
            @endphp
                <span class="role-badge {{ $roleClass }}">
                    {{ ucfirst(str_replace('_', ' ', $user->role->value)) }}
                </span>
            </span>
        </div>
        <div class="user-info-row">
            <span class="user-info-label">Current Status:</span>
            <span class="user-info-value">
                @if($user->is_active)
                    <span class="status-indicator status-active">Active</span>
                @else
                    <span class="status-indicator status-inactive">Inactive</span>
                @endif
            </span>
        </div>
        <div class="user-info-row">
            <span class="user-info-label">Created:</span>
            <span class="user-info-value">{{ $user->created_at->format('Y-m-d H:i') }}</span>
        </div>
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

    <form action="/users/{{ $user->id }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required>
            @error('username')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" {{ old('role', $user->role) == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="checkbox-group">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
            <label for="is_active">User is active and can log in</label>
        </div>
        @error('is_active')
            <div class="error">{{ $message }}</div>
        @enderror

        <div class="actions">
            <a href="/users" class="btn" style="background: #e2e8f0; color: #4a5568;">Cancel</a>
            <button type="submit" class="btn btn-primary">Update User</button>
        </div>
    </form>
</div>
@endsection
