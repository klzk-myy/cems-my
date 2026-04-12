{{--
    Alert Component
    Usage:
    @include('components.alert', [
        'variant' => 'success', // success|warning|danger|info
        'title' => null, // optional title
        'dismissible' => false,
        'class' => '',
    ])
--}}
@php
    $variant = $variant ?? 'info';

    // Variant classes
    $variantClasses = [
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'warning' => 'bg-yellow-50 border-yellow-500 text-yellow-800',
        'danger' => 'bg-red-50 border-red-500 text-red-800',
        'error' => 'bg-red-50 border-red-500 text-red-800',
        'info' => 'bg-blue-50 border-blue-500 text-blue-800',
    ];

    // Icon SVG paths for each variant
    $icons = [
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        'danger' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'error' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ];

    $baseClasses = 'border-l-4 rounded-r-lg p-4 flex items-start gap-3';
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['info']) . ' ' . ($class ?? '');
@endphp

<div class="{{ $classes }}" role="alert" aria-live="polite">
    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        {!! $icons[$variant] ?? $icons['info'] !!}
    </svg>
    <div class="flex-1">
        @if($title ?? false)
            <p class="font-semibold mb-1">{{ $title }}</p>
        @endif
        <div class="text-sm">
            {{ $slot }}
        </div>
    </div>
    @if($dismissible ?? false)
        <button type="button" class="flex-shrink-0 opacity-50 hover:opacity-100 transition-opacity" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
