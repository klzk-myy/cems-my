@props(['message' => 'No results found', 'colspan' => 1, 'icon' => null, 'heading' => null, 'as' => 'tr'])

@php
    $tag = in_array($as, ['tr', 'div']) ? $as : 'tr';
@endphp

@if($tag === 'tr')
    <tr {{ $attributes->merge([]) }}>
        <td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
            <x-empty-state.content :message="$message" :icon="$icon" :heading="$heading" />
        </td>
    </tr>
@else
    <div {{ $attributes->merge(['class' => 'px-4 py-12 text-center']) }}>
        <x-empty-state.content :message="$message" :icon="$icon" :heading="$heading" />
    </div>
@endif