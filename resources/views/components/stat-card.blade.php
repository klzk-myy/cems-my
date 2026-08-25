@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'trend' => null,
    'color' => null,
    'prefix' => '',
    'suffix' => '',
])

@php
$valueColorClass = match($color) {
    'blue' => 'text-info',
    'red' => 'text-danger',
    'yellow' => 'text-warning',
    'purple' => 'text-accent',
    'green' => 'text-success',
    default => 'text-ink',
};

$trendColorClass = match(true) {
    $trend > 0 => 'text-success-text',
    $trend < 0 => 'text-danger-text',
    default => 'text-ink-muted',
};
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface border border-border rounded-xl shadow-sm p-5']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-ink-muted">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold tabular-nums {{ $valueColorClass }}">
                @if($prefix)<span class="text-lg">{{ $prefix }}</span>@endif
                {{ $value }}
                @if($suffix)<span class="text-lg">{{ $suffix }}</span>@endif
            </p>
            @if($trend !== null)
                <p class="mt-1 text-xs tabular-nums {{ $trendColorClass }}">
                    {{ $trend > 0 ? '+' : ($trend < 0 ? '' : '—') }}{{ $trend }}% from last period
                </p>
            @endif
        </div>
        @if($icon)
            <div class="ml-4">{{ $icon }}</div>
        @endif
    </div>
</div>