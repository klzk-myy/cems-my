@extends('layouts.app')

@section('title', 'Create User - CEMS-MY')

@section('content')
<div class="create-user-header">
    <h2>Create New User</h2>
    <p>Add a new staff member to the system</p>
</div>

<div class="card">
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

    <form action="/users" method="POST">
        @csrf

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" required class="form-input">
            @error('username')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input">
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required class="form-input">
            <div class="password-requirements">
                Minimum 12 characters, include uppercase, lowercase, number, and special character
            </div>
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input">
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required class="form-input" onchange="updateRoleDescription(this.value)">
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
            <a href="/users" class="btn btn-secondary">Cancel</a>
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
