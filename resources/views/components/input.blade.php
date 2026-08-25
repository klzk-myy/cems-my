@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'placeholder' => null,
    'help' => null,
    'inline' => false,
])

@php
$hasError = $name && isset($errors) && $errors->has($name ?? '');
@endphp

<div class="{{ $inline ? '' : 'mb-4' }}">
    @if($label)
        <label for="{{ $name ?? $attributes->whereStartsWith('id')->first() }}"
               class="block text-sm font-medium text-ink">
            {{ $label }}
            @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

    <input type="{{ $type }}"
           @if($name) name="{{ $name }}" id="{{ $name }}" @endif
           @if($required) required aria-required="true" @endif
           @if($disabled) disabled @endif
           @if($readonly) readonly @endif
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           @if($hasError) aria-describedby="{{ $name }}-error" @endif
           {{ $attributes->except(['label', 'name', 'type', 'required', 'disabled', 'readonly', 'placeholder', 'help', 'inline']) }}
           class="mt-1 w-full px-3 py-2 text-sm bg-canvas-subtle border border-border rounded-lg
                  focus:bg-surface text-ink placeholder:text-ink-muted
                  focus:outline-none focus:ring-2 focus:ring-primary
                  disabled:bg-canvas-subtle disabled:text-ink-muted
                  @if($hasError) border-danger @endif
                  {{ $attributes->get('class', '') }}">

    @if($help)
        <p class="mt-1 text-xs text-ink-muted">{{ $help }}</p>
    @endif

    @if($name && isset($errors))
        @error($name)
            <p id="{{ $name }}-error" class="mt-1 text-xs text-danger-text" role="alert">{{ $message }}</p>
        @enderror
    @endif
</div>
