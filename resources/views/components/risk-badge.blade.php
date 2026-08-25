@props([
    'customer' => null,
])

@php
    $riskValue = $customer?->risk_rating instanceof \App\Enums\RiskRating 
        ? $customer->risk_rating->value 
        : ($customer?->risk_rating ?? 'Medium');
    
    $variant = $customer?->risk_variant ?? match (strtolower($riskValue)) {
        'high', 'critical' => 'danger',
        'medium' => 'warning',
        default => 'success',
    };
@endphp

<x-badge :variant="$variant">
    {{ ucfirst($riskValue) }} Risk
</x-badge>
