@extends('layouts.app')

@section('title', 'Edit Branch - CEMS-MY')

@section('styles')
<style>
    .edit-branch-header {
        margin-bottom: 1.5rem;
    }
    .edit-branch-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .edit-branch-header p {
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
        font-size: 0.75rem;
        color: #718096;
        margin-top: 0.25rem;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .checkbox-group {
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }
    .checkbox-group input[type="checkbox"] {
        width: 1.25rem;
        height: 1.25rem;
    }
    .checkbox-group label {
        margin-bottom: 0;
        font-weight: normal;
    }

    .actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .warning-box {
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .warning-box h4 {
        color: #92400e;
        margin-bottom: 0.5rem;
    }
    .warning-box p {
        color: #92400e;
        font-size: 0.875rem;
    }
</style>
@endsection

@section('content')
<div class="edit-branch-header">
    <h2>Edit Branch</h2>
    <p>Update branch information</p>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <strong>Please fix the following errors:</strong>
            <ul style="margin-top: 0.5rem; margin-left: 1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('branches.update', $branch) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label for="code">Branch Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code', $branch->code) }}" required maxlength="20" placeholder="e.g., HQ, BR001, SB001">
                @error('code')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="name">Branch Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $branch->name) }}" required maxlength="255" placeholder="e.g., Kuala Lumpur Branch">
                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type">Branch Type *</label>
                <select id="type" name="type" required>
                    @foreach($branchTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('type', $branch->type) == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('type')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="parent_id">Parent Branch</label>
                <select id="parent_id" name="parent_id">
                    <option value="">-- No Parent (Top Level) --</option>
                    @foreach($parentBranches as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $branch->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->code }} - {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                <div class="hint">Select if this is a sub-branch of another branch</div>
                @error('parent_id')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="2" maxlength="500" placeholder="Street address">{{ old('address', $branch->address) }}</textarea>
            @error('address')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="{{ old('city', $branch->city) }}" maxlength="100" placeholder="e.g., Kuala Lumpur">
                @error('city')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="state">State</label>
                <input type="text" id="state" name="state" value="{{ old('state', $branch->state) }}" maxlength="100" placeholder="e.g., Selangor">
                @error('state')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $branch->postal_code) }}" maxlength="20" placeholder="e.g., 50000">
                @error('postal_code')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="{{ old('country', $branch->country) }}" maxlength="50">
                @error('country')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" maxlength="30" placeholder="e.g., +603-12345678">
                @error('phone')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $branch->email) }}" maxlength="100" placeholder="e.g., branch@cems-my.com">
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                <label for="is_active">Active</label>

                <input type="hidden" name="is_main" value="0">
                <input type="checkbox" id="is_main" name="is_main" value="1" {{ old('is_main', $branch->is_main) ? 'checked' : '' }}>
                <label for="is_main">Main Branch (Head Office)</label>
            </div>
            <div class="hint">Only one branch can be the main branch. Setting this will unset the current main branch.</div>
        </div>

        @if($branch->is_main)
            <div class="warning-box">
                <h4>Main Branch</h4>
                <p>This branch is designated as the main branch (head office). It cannot be deactivated.</p>
            </div>
        @endif

        <div class="actions">
            <a href="{{ route('branches.index') }}" class="btn" style="background: #e2e8f0; color: #4a5568;">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Branch</button>
        </div>
    </form>
</div>
@endsection