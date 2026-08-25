@props(['value' => 0, 'color' => null, 'minHeight' => 5])

@php
$height = max($value, $minHeight);
$colorClass = $color ?? ($value >= 80 ? 'bg-success' : ($value >= 50 ? 'bg-warning' : 'bg-danger'));
@endphp

<div {{ $attributes->merge(['class' => 'w-full rounded-t relative']) }}
     x-data="{ height: {{ $height }} }"
     :style="'height: ' + height + '%'">
    <div class="absolute inset-0 {{ $colorClass }} rounded-t"></div>
</div>