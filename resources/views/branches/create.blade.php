@extends('layouts.app')

@section('title', 'Create Branch - CEMS-MY')

@section('content')
<div class="create-branch-header">
    <h2>Create New Branch</h2>
    <p>Add a new branch or head office to the system</p>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert-error mb-6">
            <strong>Please fix the following errors:</strong>
            <ul class="mt-2 ml-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('branches.store') }}" method="POST">
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label for="code">Branch Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" required maxlength="20" placeholder="e.g., HQ, BR001, SB001" class="form-input">
                @error('code')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="name">Branch Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="e.g., Kuala Lumpur Branch" class="form-input">
                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type">Branch Type *</label>
                <select id="type" name="type" required class="form-input">
                    @foreach($branchTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
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
                <select id="parent_id" name="parent_id" class="form-input">
                    <option value="">-- No Parent (Top Level) --</option>
                    @foreach($parentBranches as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
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
            <textarea id="address" name="address" rows="2" maxlength="500" placeholder="Street address" class="form-input">{{ old('address') }}</textarea>
            @error('address')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="{{ old('city') }}" maxlength="100" placeholder="e.g., Kuala Lumpur" class="form-input">
                @error('city')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="state">State</label>
                <input type="text" id="state" name="state" value="{{ old('state') }}" maxlength="100" placeholder="e.g., Selangor" class="form-input">
                @error('state')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" maxlength="20" placeholder="e.g., 50000" class="form-input">
                @error('postal_code')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="{{ old('country', 'Malaysia') }}" maxlength="50" class="form-input">
                @error('country')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="e.g., +603-12345678" class="form-input">
                @error('phone')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="100" placeholder="e.g., branch@cems-my.com" class="form-input">
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label for="is_active">Active</label>

                <input type="hidden" name="is_main" value="0">
                <input type="checkbox" id="is_main" name="is_main" value="1" {{ old('is_main') ? 'checked' : '' }}>
                <label for="is_main">Main Branch (Head Office)</label>
            </div>
            <div class="hint">Only one branch can be the main branch. Setting this will unset the current main branch.</div>
        </div>

        <div class="actions">
            <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Branch</button>
        </div>
    </form>
</div>
@endsection
