@extends('layouts.app')

@section('title', $promo->title)
@section('meta_description', \Illuminate\Support\Str::limit($promo->description ?: 'Promo '.$promo->title.' di '.config('dealer.name').'.', 155))

@php
    $car = $promo->car;
    $remaining = $promo->remainingLabel();
    $shareImage = $promo->image_url ?? $car?->primaryImage?->url;
@endphp

@if ($shareImage)
    @section('og_image', $shareImage)
@endif

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [
            ['label' => 'Promo', 'url' => route('promos.index')],
            ['label' => $promo->title],
        ]])

        <div class="row g-4">
            <div class="col-lg-8">
                <article class="card overflow-hidden">
                    @if ($promo->image_url)
                        <img src="{{ $promo->image_url }}" alt="{{ $promo->title }}" class="promo-card-img">
                    @elseif ($car?->primaryImage)
                        <img src="{{ $car->primaryImage->url }}" alt="{{ $car->brand->name }} {{ $car->name }} {{ $car->year }}" class="promo-card-img promo-detail-car-img">
                    @else
                        <div class="promo-card-img promo-card-placeholder" role="img" aria-label="Banner {{ $promo->title }}">
                            <span class="promo-card-placeholder-label"><i class="bi bi-percent"></i>Promo</span>
                            <span class="promo-card-placeholder-value">{{ \Illuminate\Support\Str::limit($promo->title, 40) }}</span>
                        </div>
                    @endif

                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            @if ($promo->isGeneral())
                                <span class="badge rounded-pill badge-status-muted">Promo umum</span>
                            @else
                                <span class="badge rounded-pill badge-status-red"><i class="bi bi-percent"></i> Khusus mobil</span>
                            @endif
                            @if ($isEnded)
                                <x-status-badge status="ended" />
                            @endif
                        </div>

                        <h1 class="h3 mb-2">{{ $promo->title }}</h1>
                        <p class="text-muted mb-3">
                            <i class="bi bi-calendar-event me-1"></i>
                            {{ $promo->start_date->translatedFormat('d F Y') }} - {{ $promo->end_date->translatedFormat('d F Y') }}
                            @if ($remaining)
                                <span class="fw-semibold text-accent ms-2"><i class="bi bi-hourglass-split"></i> {{ $remaining }}</span>
                            @endif
                        </p>

                        @if ($isEnded)
                            <div class="alert alert-secondary small d-flex align-items-center" role="status">
                                <i class="bi bi-info-circle me-2"></i>
                                <div>Promo ini sudah berakhir pada {{ $promo->end_date->translatedFormat('d F Y') }}. Lihat promo lain yang masih berjalan.</div>
                            </div>
                        @endif

                        @if ($promo->description)
                            <p class="mb-0" style="white-space: pre-line">{{ $promo->description }}</p>
                        @endif

                        @if ($car)
                            <p class="mt-4 mb-0">
                                <span class="fw-semibold">Potongan harga</span> untuk {{ $car->brand->name }} {{ $car->name }} {{ $car->year }}:
                                <span class="fw-bold text-accent">hemat <x-price :amount="$promo->discount_amount" /></span>
                            </p>
                        @endif

                        @unless ($isEnded)
                            {{-- Dua tombol utama; test drive, simulasi kredit, dan WhatsApp berupa tautan teks. --}}
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                @if ($car)
                                    @if ($car->canBePurchased() && Route::has('purchase-requests.create'))
                                        <a href="{{ route('purchase-requests.create', $car) }}" class="btn btn-accent">
                                            <i class="bi bi-cart-check"></i>Ajukan Pembelian
                                        </a>
                                    @endif
                                    <a href="{{ route('cars.show', $car) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-car-front"></i>Lihat Detail Mobil
                                    </a>
                                @else
                                    <a href="{{ route('cars.index') }}" class="btn btn-accent">
                                        <i class="bi bi-car-front"></i>Lihat Semua Mobil
                                    </a>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap gap-3 mt-3">
                                @if ($car && $car->canBeTestDriven() && Route::has('test-drives.create'))
                                    <a href="{{ route('test-drives.create', ['mobil' => $car->slug]) }}" class="car-detail-link">
                                        <i class="bi bi-calendar-check me-1"></i>Booking Test Drive
                                    </a>
                                @endif
                                @if ($car && Route::has('credit.index'))
                                    <a href="{{ route('credit.index', ['mobil' => $car->slug]) }}" class="car-detail-link">
                                        <i class="bi bi-calculator me-1"></i>Simulasi Kredit
                                    </a>
                                @endif
                                @if ($whatsappUrl)
                                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="car-detail-link">
                                        <i class="bi bi-whatsapp me-1"></i>Tanya via WhatsApp
                                    </a>
                                @endif
                            </div>
                        @endunless
                    </div>
                </article>
            </div>

            <div class="col-lg-4">
                @if ($car)
                    <h2 class="h5 mb-3">Mobil promo</h2>
                    <x-car-card :car="$car" class="h-auto mb-4" />
                @endif

                <h2 class="h5 mb-3">Promo lainnya</h2>
                @forelse ($otherPromos as $other)
                    <x-promo-card :promo="$other" :show-remaining="true" class="h-auto mb-3" />
                @empty
                    <p class="small text-muted">Belum ada promo lain yang sedang berjalan.</p>
                @endforelse
                <a href="{{ route('promos.index') }}" class="small fw-semibold">Semua promo <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
@endsection
