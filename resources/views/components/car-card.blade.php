{{--
    Kartu mobil publik (RANCANGAN §4): <x-car-card :car="$car" />
    Memerlukan eager load Car::CARD_RELATIONS (preventLazyLoading menangkap N+1).
--}}
@props(['car'])

@php
    $credit = app(\App\Support\CreditCalculator::class);
    $finalPrice = $car->finalPrice();
    // Cicilan termurah: DP minimum + tenor terpanjang (hitungan PHP murni, tanpa query).
    $installment = $finalPrice > 0
        ? $credit->calculate($finalPrice, $credit->minDownPayment($finalPrice), max($credit->tenors()))['monthly_installment']
        : null;
    $detailUrl = Route::has('cars.show') ? route('cars.show', $car) : null;
    $title = "{$car->brand->name} {$car->name} {$car->year}";
@endphp

<article {{ $attributes->class(['card car-card card-hover h-100']) }}>
    <div class="car-card-media">
        @if ($detailUrl)<a href="{{ $detailUrl }}" tabindex="-1" aria-hidden="true">@endif
            @if ($car->primaryImage)
                <img src="{{ $car->primaryImage->url }}" alt="{{ $title }}" class="car-card-img" loading="lazy">
            @else
                <div class="car-card-img car-card-img-empty" role="img" aria-label="Belum ada foto {{ $title }}">
                    <i class="bi bi-car-front"></i>
                </div>
            @endif
        @if ($detailUrl)</a>@endif

        {{-- Di atas stretched-link judul agar tombol tetap bisa diklik. --}}
        <div class="car-card-favorite-wrap">
            <x-favorite-button :car="$car" />
        </div>

        <div class="car-card-badges">
            <span @class(['badge rounded-pill', 'badge-condition-new' => $car->isNew(), 'badge-condition-used' => ! $car->isNew()])>{{ $car->condition_label }}</span>
            @if ($car->hasPromoPrice())
                <span class="badge rounded-pill badge-status-red"><i class="bi bi-percent"></i> Promo</span>
            @endif
            @unless ($car->inStock())
                <x-status-badge status="out_of_stock" />
            @endunless
        </div>
    </div>

    <div class="card-body d-flex flex-column p-3">
        <p class="small text-muted mb-1">{{ $car->brand->name }} · {{ $car->category->name }}</p>
        <h3 class="h6 mb-2 car-card-title">
            @if ($detailUrl)
                <a href="{{ $detailUrl }}" class="stretched-link text-reset text-decoration-none">{{ $car->name }} {{ $car->year }}</a>
            @else
                {{ $car->name }} {{ $car->year }}
            @endif
        </h3>

        <ul class="car-card-specs list-inline small text-muted mb-3">
            <li class="list-inline-item"><i class="bi bi-gear"></i> {{ $car->transmission_label }}</li>
            <li class="list-inline-item"><i class="bi bi-fuel-pump"></i> {{ $car->fuel_type_label }}</li>
            @unless ($car->isNew())
                <li class="list-inline-item"><i class="bi bi-speedometer2"></i> {{ number_format($car->mileage, 0, ',', '.') }} km</li>
            @endunless
        </ul>

        <div class="mt-auto">
            @if ($car->hasPromoPrice())
                <del class="price-old small d-block"><x-price :amount="$car->price" /></del>
            @endif
            <x-price :amount="$finalPrice" class="price-final" />
            @if ($installment)
                <p class="small text-muted mb-3">Cicilan mulai <x-price :amount="$installment" />/bln</p>
            @endif

            @if ($detailUrl)
                <a href="{{ $detailUrl }}" class="btn btn-outline-primary btn-sm w-100">Detail</a>
            @else
                <span class="btn btn-outline-primary btn-sm w-100 disabled" aria-disabled="true">Detail</span>
            @endif
        </div>
    </div>
</article>
