{{--
    Badge Component
    Usage:
    @include('components.badge', [
        'variant' => 'success', // success|warning|danger|info|neutral|pending|onhold|completed|active|inactive|draft|flagged
        'size' => 'md', // sm|md|lg
        'dot' => false, // show dot indicator
        'class' => '',
    ])
--}}
@php
    $variant = $variant ?? 'neutral';
    $size = $size ?? 'md';

    // Variant classes (Tailwind-based)
    $variantClasses = [
        // Success variants
        'success' => 'bg-green-100 text-green-800',
        'completed' => 'bg-green-100 text-green-800',
        'active' => 'bg-green-100 text-green-800',
        'approved' => 'bg-green-100 text-green-800',
        // Warning variants
        'warning' => 'bg-yellow-100 text-yellow-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'onhold' => 'bg-orange-100 text-orange-800',
        'flagged' => 'bg-orange-100 text-orange-800',
        // Danger variants
        'danger' => 'bg-red-100 text-red-800',
        'error' => 'bg-red-100 text-red-800',
        'rejected' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'failed' => 'bg-red-100 text-red-800',
        // Info variants
        'info' => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'submitted' => 'bg-blue-100 text-blue-800',
        // Neutral variants
        'neutral' => 'bg-gray-100 text-gray-600',
        'inactive' => 'bg-gray-100 text-gray-600',
        'draft' => 'bg-gray-100 text-gray-600',
        'default' => 'bg-gray-100 text-gray-600',
    ];

    // Size classes
    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
    ];

    $baseClasses = 'inline-flex items-center gap-1.5 font-semibold rounded-full';

    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['neutral']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']) . ' ' . ($class ?? '');
@endphp

<span class="{{ $classes }}">
    @if($dot ?? false)
        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    @endif
    {{ $slot }}
</span>
