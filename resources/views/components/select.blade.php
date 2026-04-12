{{--
    Select Component (Enhanced)
    Usage:
    @include('components.select', [
        'name' => 'field_name',
        'label' => 'Field Label',
        'required' => false,
        'disabled' => false,
        'placeholder' => 'Select an option',
        'hint' => null,
        'error' => $errors->first('field_name'),
        'class' => '',
        'wrapperClass' => '',
    ])
--}}
@php
    $id = $id ?? $name;
    $hasError = !empty($error);
@endphp

<div class="{{ $wrapperClass ?? 'mb-4' }}">
    @if($label ?? false)
        <label for="{{ $id }}" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
            {{ $label }}
            @if($required ?? false)
                <span class="text-gold ml-0.5">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required ?? false) required @endif
        @if($disabled ?? false) disabled @endif
        aria-describedby="{{ $id }}-hint {{ $id }}-error"
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        class="w-full px-4 py-2.5 text-sm border-2 rounded-lg transition-colors duration-150 appearance-none bg-no-repeat bg-right
               {{ $hasError
                   ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-200'
                   : 'border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200' }}
               {{ ($disabled ?? false) ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' }}
               {{ $class ?? '' }}"
        style="background-image: url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e\"); background-position: right 0.75rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;"
    >
        @if($placeholder ?? false)
            <option value="" disabled {{ empty($value ?? '') ? 'selected' : '' }}>{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    @if($hint ?? false)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-gray-500">{{ $hint }}</p>
    @endif

    @if($error ?? false)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs text-red-600 flex items-center gap-1" role="alert">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $error }}
        </p>
    @endif
</div>
