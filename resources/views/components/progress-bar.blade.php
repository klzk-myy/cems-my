@props(['value' => 0, 'max' => 100, 'size' => 'md', 'width' => 'w-20'])

@php
$percent = min(($value / $max) * 100, 100);
$sizeClass = match($size) {
    'sm' => 'h-1',
    'md' => 'h-2',
    'lg' => 'h-3',
    default => 'h-2',
};
@endphp

<div {{ $attributes->merge(['class' => "$width bg-canvas-subtle rounded-full overflow-hidden"]) }}>
    <div x-data="{ percent: {{ $percent }} }"
         :style="'width: ' + percent + '%'"
         class="bg-primary {{ $sizeClass }} rounded-full"></div>
</div>