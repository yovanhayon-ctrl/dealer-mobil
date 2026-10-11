{{--
    Kartu promo publik: <x-promo-card :promo="$promo" />
    Promo khusus mobil memerlukan eager load `car.brand`.
    :show-remaining="true" menampilkan sisa waktu promo (mis. "Berakhir dalam 3 hari").
--}}
@props(['promo', 'showRemaining' => false])

@php
    $detailUrl = Route::has('promos.show') ? route('promos.show', $promo) : null;
    // Nominal ringkas untuk blok pengganti banner: Rp 15 jt, Rp 1,2 M.
    $discount = (int) $promo->discount_amount;
    $shortDiscount = match (true) {
        $discount >= 1_000_000_000 => 'Rp '.str_replace('.', ',', rtrim(rtrim(number_format($discount / 1_000_000_000, 1, '.', ''), '0'), '.')).' M',
        $discount >= 1_000_000 => 'Rp '.str_replace('.', ',', rtrim(rtrim(number_format($discount / 1_000_000, 1, '.', ''), '0'), '.')).' jt',
        $discount > 0 => 'Rp '.number_format($discount, 0, ',', '.'),
        default => null,
    };
@endphp

<article {{ $attributes->class(['card promo-card card-hover h-100 overflow-hidden']) }}>
    @if ($promo->image_url)
        <img src="{{ $promo->image_url }}" alt="{{ $promo->title }}" class="promo-card-img" loading="lazy">
    @else
        {{-- Tanpa banner: blok bergaya JAF berisi nilai hemat (bukan kotak abu kosong). --}}
        <div class="promo-card-img promo-card-placeholder" role="img" aria-label="Banner {{ $promo->title }}">
            <span class="promo-card-placeholder-label"><i class="bi bi-percent"></i>{{ $shortDiscount ? 'Hemat' : 'Promo' }}</span>
            <span class="promo-card-placeholder-value">{{ $shortDiscount ?? \Illuminate\Support\Str::limit($promo->title, 28) }}</span>
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
            {{ $promo->start_date->translatedFormat('d M Y') }} - {{ $promo->end_date->translatedFormat('d M Y') }}
        </p>
        @if ($showRemaining && ($remaining = $promo->remainingLabel()))
            <p class="small fw-semibold text-accent mb-2"><i class="bi bi-hourglass-split"></i> {{ $remaining }}</p>
        @endif

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
