@extends('layouts.app')

@section('title', 'Create Branch - CEMS-MY')

@section('content')
<div class="page-header">
    <h1 class="page-header__title">Create New Branch</h1>
    <p class="page-header__subtitle">Add a new branch or head office to the system</p>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert--error mb-6" role="alert">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="mt-2 ml-4 list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('branches.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
            @include('components.input', [
                'name' => 'code',
                'label' => 'Branch Code',
                'required' => true,
                'placeholder' => 'e.g., HQ, BR001, SB001',
                'maxlength' => 20,
                'error' => $errors->first('code'),
            ])

            @include('components.input', [
                'name' => 'name',
                'label' => 'Branch Name',
                'required' => true,
                'placeholder' => 'e.g., Kuala Lumpur Branch',
                'maxlength' => 255,
                'error' => $errors->first('name'),
            ])
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
            @include('components.select', [
                'name' => 'type',
                'label' => 'Branch Type',
                'required' => true,
                'error' => $errors->first('type'),
            ])
                @foreach($branchTypes as $value => $label)
                    <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            @endinclude

            @include('components.select', [
                'name' => 'parent_id',
                'label' => 'Parent Branch',
                'placeholder' => '-- No Parent (Top Level) --',
                'error' => $errors->first('parent_id'),
            ])
                @foreach($parentBranches as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                        {{ $parent->code }} - {{ $parent->name }}
                    </option>
                @endforeach
            @endinclude
        </div>

        @include('components.textarea', [
            'name' => 'address',
            'label' => 'Address',
            'rows' => 2,
            'maxlength' => 500,
            'placeholder' => 'Street address',
            'error' => $errors->first('address'),
        ])

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
            @include('components.input', [
                'name' => 'city',
                'label' => 'City',
                'placeholder' => 'e.g., Kuala Lumpur',
                'maxlength' => 100,
                'error' => $errors->first('city'),
            ])

            @include('components.input', [
                'name' => 'state',
                'label' => 'State',
                'placeholder' => 'e.g., Selangor',
                'maxlength' => 100,
                'error' => $errors->first('state'),
            ])
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
            @include('components.input', [
                'name' => 'postal_code',
                'label' => 'Postal Code',
                'placeholder' => 'e.g., 50000',
                'maxlength' => 20,
                'error' => $errors->first('postal_code'),
            ])

            @include('components.input', [
                'name' => 'country',
                'label' => 'Country',
                'value' => old('country', 'Malaysia'),
                'maxlength' => 50,
                'error' => $errors->first('country'),
            ])
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
            @include('components.input', [
                'name' => 'phone',
                'label' => 'Phone',
                'type' => 'tel',
                'placeholder' => 'e.g., +603-12345678',
                'maxlength' => 30,
                'error' => $errors->first('phone'),
            ])

            @include('components.input', [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'placeholder' => 'e.g., branch@cems-my.com',
                'maxlength' => 100,
                'error' => $errors->first('email'),
            ])
        </div>

        <div class="mb-6">
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <span class="text-sm text-gray-700">Active</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_main" value="0">
                    <input type="checkbox" id="is_main" name="is_main" value="1" {{ old('is_main') ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <span class="text-sm text-gray-700">Main Branch (Head Office)</span>
                </label>
            </div>
            <p class="mt-2 text-xs text-gray-500">Only one branch can be the main branch. Setting this will unset the current main branch.</p>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('branches.index') }}" class="btn btn--secondary">Cancel</a>
            <button type="submit" class="btn btn--primary">Create Branch</button>
        </div>
    </form>
</div>
@endsection
