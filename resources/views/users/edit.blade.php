@extends('layouts.app')

@section('title', 'Edit User - CEMS-MY')

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
            <ul class="mt-2 ml-4">
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
            <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required class="form-input">
            @error('username')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required class="form-input">
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
            <a href="/users" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update User</button>
        </div>
    </form>
</div>
@endsection
