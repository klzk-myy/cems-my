@props(['method' => 'GET', 'class' => null])

<div {{ $attributes->merge(['class' => "bg-surface border border-border rounded-xl p-4 mb-6" . ($class ? " $class" : "")]) }}>
    <form method="{{ $method === 'GET' ? 'GET' : 'POST' }}" class="flex flex-wrap gap-4 items-end">
        @if($method !== 'GET')
            @csrf
            @method($method)
        @endif
        {{ $slot }}
    </form>
</div>