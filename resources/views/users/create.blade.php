@extends('layouts.app')

@section('title', 'Create User - CEMS-MY')

@section('styles')
<style>
    .create-user-header {
        margin-bottom: 1.5rem;
    }
    .create-user-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .create-user-header p {
        color: #718096;
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
    .password-requirements {
        font-size: 0.75rem;
        color: #718096;
        margin-top: 0.25rem;
    }
    .role-description {
        font-size: 0.875rem;
        color: #718096;
        margin-top: 0.25rem;
        padding: 0.5rem;
        background: #f7fafc;
        border-radius: 4px;
    }

    .actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="create-user-header">
    <h2>Create New User</h2>
    <p>Add a new staff member to the system</p>
</div>

<div class="card">
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

    <form action="/users" method="POST">
        @csrf

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" required>
            @error('username')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div class="password-requirements">
                Minimum 12 characters, include uppercase, lowercase, number, and special character
            </div>
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required onchange="updateRoleDescription(this.value)">
                @foreach($roles as $key => $description)
                    <option value="{{ $key }}" {{ old('role') == $key ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                    </option>
                @endforeach
            </select>
            <div id="role-description" class="role-description">
                {{ $roles['teller'] ?? 'Select a role' }}
            </div>
            @error('role')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="actions">
            <a href="/users" class="btn" style="background: #e2e8f0; color: #4a5568;">Cancel</a>
            <button type="submit" class="btn btn-primary">Create User</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    var roleDescriptions = @json($roles);
    function updateRoleDescription(role) {
        var desc = roleDescriptions[role] || 'Select a role';
        document.getElementById('role-description').textContent = desc;
    }
</script>
@endsection
