@unless($breadcrumbs->isEmpty() || $breadcrumbs->count() === 1)
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        @foreach($breadcrumbs as $breadcrumb)
            @if($loop->last)
                <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
                    <span class="breadcrumbs__text">{{ $breadcrumb['label'] }}</span>
                </li>
            @else
                <li class="breadcrumbs__item">
                    <a href="{{ $breadcrumb['url'] }}" class="breadcrumbs__link">
                        {{ $breadcrumb['label'] }}
                    </a>
                    <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endunless
