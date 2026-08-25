@props([
    'transaction' => null,
])

@php
    $reference = $transaction?->reference ?? 'TX-00000000';
    $variant = $transaction?->status_variant ?? 'gray';
@endphp

<span class="font-mono text-sm">
    <x-badge :variant="$variant">
        {{ $reference }}
    </x-badge>
</span>
