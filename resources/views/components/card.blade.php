{{--
    Card Component
    Usage:
    @include('components.card', [
        'title' => 'Card Title', // optional
        'subtitle' => 'Card subtitle', // optional
        'variant' => 'default', // default|bordered|featured|hover
        'padding' => 'default', // none|sm|default|lg
        'class' => '', // additional classes
        'headerActions' => '', // HTML for header actions
    ])
--}}
@php
    $variant = $variant ?? 'default';
    $padding = $padding ?? 'default';

    // Variant classes
    $variantClasses = [
        'default' => 'bg-white rounded-xl shadow-sm',
        'bordered' => 'bg-white rounded-xl border border-gray-200',
        'featured' => 'bg-white rounded-xl shadow-sm border-t-4 border-gold',
        'hover' => 'bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200',
    ];

    // Padding classes
    $paddingClasses = [
        'none' => 'p-0',
        'sm' => 'p-4',
        'default' => 'p-6',
        'lg' => 'p-8',
    ];

    $cardClasses = ($variantClasses[$variant] ?? $variantClasses['default']) . ' ' . ($class ?? '');
@endphp

<div class="{{ $cardClasses }}">
    @if($title ?? false || $headerActions ?? false)
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
            <div>
                @if($title ?? false)
                    <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                @endif
                @if($subtitle ?? false)
                    <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @if($headerActions ?? false)
                <div class="flex items-center gap-2">
                    {!! $headerActions !!}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $paddingClasses[$padding] ?? $paddingClasses['default'] }}">
        {{ $slot }}
    </div>
</div>
