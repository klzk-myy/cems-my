{{--
    Button Component
    Usage:
    @include('components.button', [
        'type' => 'submit',
        'variant' => 'primary', // primary|secondary|success|danger|warning|ghost
        'size' => 'md', // sm|md|lg
        'href' => null, // if set, renders as <a> tag
        'disabled' => false,
        'icon' => null, // icon HTML (optional)
        'class' => '', // additional classes
    ])
--}}
@php
    $variant = $variant ?? 'primary';
    $size = $size ?? 'md';
    $type = $type ?? 'button';
    $disabled = $disabled ?? false;

    // Variant classes
    $variantClasses = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        'secondary' => 'bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-gray-400',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'warning' => 'bg-orange-500 text-white hover:bg-orange-600 focus:ring-orange-400',
        'ghost' => 'bg-transparent text-primary-600 hover:bg-gray-100 focus:ring-gray-400',
    ];

    // Size classes
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold rounded-md transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']) . ' ' . ($class ?? '');
@endphp

@if($href ?? false)
    <a href="{{ $href }}"
       class="{{ $classes }}"
       @if($disabled) aria-disabled="true" @endif>
        {!! $icon ?? '' !!}
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            class="{{ $classes }}"
            @if($disabled) disabled @endif>
        {!! $icon ?? '' !!}
        {{ $slot }}
    </button>
@endif
