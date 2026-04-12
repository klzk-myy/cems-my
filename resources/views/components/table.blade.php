{{--
    Table Component
    Usage:
    @include('components.table', [
        'striped' => false,
        'hover' => true,
        'compact' => false,
        'class' => '',
    ])
    Then use with <thead> and <tbody> slots

    Or use the table-row and table-cell components inside
--}}
@php
    $striped = $striped ?? false;
    $hover = $hover ?? true;
    $compact = $compact ?? false;

    $baseClasses = 'w-full text-sm';

    $theadClasses = 'bg-gray-50';
    $thClasses = 'px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-gray-200';
    $tdClasses = 'px-4 py-3 border-b border-gray-100';
    $trClasses = $hover ? 'hover:bg-blue-50 transition-colors' : '';

    if ($striped) {
        $trClasses .= ' odd:bg-gray-50';
    }
@endphp

<table class="{{ $baseClasses }} {{ $class ?? '' }}">
    <thead class="{{ $theadClasses }}">
        {{ $thead ?? '' }}
    </thead>
    <tbody class="divide-y divide-gray-100">
        {{ $slot }}
    </tbody>
</table>
