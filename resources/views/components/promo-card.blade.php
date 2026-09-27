{{--
    Kartu promo publik: <x-promo-card :promo="$promo" />
    Promo khusus mobil memerlukan eager load `car.brand`.
--}}
@props(['promo'])

@php
    $detailUrl = Route::has('promos.show') ? route('promos.show', $promo) : null;
@endphp

<article {{ $attributes->class(['card promo-card card-hover h-100 overflow-hidden']) }}>
    @if ($promo->image_url)
        <img src="{{ $promo->image_url }}" alt="{{ $promo->title }}" class="promo-card-img" loading="lazy">
    @else
        <div class="promo-card-img promo-card-img-empty" role="img" aria-label="Banner {{ $promo->title }}">
            <i class="bi bi-percent"></i>
        </div>
    @endif

    <div class="card-body p-3">
        <h3 class="h6 mb-1">
            @if ($detailUrl)
                <a href="{{ $detailUrl }}" class="stretched-link text-reset text-decoration-none">{{ $promo->title }}</a>
            @else
                {{ $promo->title }}
            @endif
        </h3>
        <p class="small text-muted mb-2">
            <i class="bi bi-calendar-event"></i>
            {{ $promo->start_date->translatedFormat('d M Y') }} – {{ $promo->end_date->translatedFormat('d M Y') }}
        </p>

        @if ($promo->isGeneral())
            <span class="badge rounded-pill badge-status-muted">Promo umum</span>
        @else
            <p class="small mb-0">
                {{ $promo->car->brand->name }} {{ $promo->car->name }} {{ $promo->car->year }}:
                <span class="fw-semibold text-accent">hemat <x-price :amount="$promo->discount_amount" /></span>
            </p>
        @endif
    </div>
</article>
