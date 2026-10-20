@extends('layouts.app')

@section('title', 'Beranda')
@section('meta_description', config('dealer.name').', Nissan Heritage & Performance: mobil Nissan baru dan heritage, promo, cicilan ringan, dan layanan servis.')

@php
    $dealer = config('dealer');
    $creditUrl = Route::has('credit.index') ? route('credit.index') : null;
    // Mobil unggulan: pilihan admin ("Jadikan unggulan", maks. 5); bila tidak ada, Nissan berfoto terbaru (lihat HomeController).
    // Hero memakai mobil pertama; bagian "Mobil Unggulan" menampilkan semuanya bergantian.
    $featuredCars = $chosenFeaturedCars->isNotEmpty() ? $chosenFeaturedCars : collect([$autoFeaturedCar])->filter();
    $featuredCar = $featuredCars->first();
    // Latar hero (Admin > Pengaturan): video, gambar banner unggahan, atau foto mobil unggulan.
    $heroBackground = $dealer['hero']['background'];
    $heroBanner = $heroBackground === 'image' && filled($dealer['hero']['image'])
        ? Storage::disk('public')->url($dealer['hero']['image'])
        : null;
    // Video latar hero (DEALER_HERO_VIDEOS): hanya file .mp4 yang benar-benar ada di folder public.
    $heroVideos = collect($heroBackground === 'video' ? config('dealer.hero_videos', []) : [])
        ->filter(fn ($path) => is_string($path) && str_ends_with(strtolower($path), '.mp4')
            && ! str_contains($path, '..') && is_file(public_path($path)))
        ->map(fn ($path) => \App\Support\Asset::url(ltrim($path, '/')))
        ->values();
@endphp

@section('content')
    @php($heroPhoto = $heroBanner ?? $featuredCar?->primaryImage?->url)
    {{--
        Hero: judul nilai jual + 2 tombol, rata kiri di bagian bawah.
        Latar: gambar banner unggahan (semua layar), video (layar lebar), foto mobil unggulan (HP / tanpa video),
        atau gradien + garis livery.
    --}}
    <section @class(['home-hero', 'has-video' => $heroVideos->isNotEmpty(), 'has-photo' => $heroPhoto, 'has-banner' => $heroBanner]) aria-labelledby="hero-title"
             @if ($heroPhoto) style="--hero-photo: url('{{ $heroPhoto }}')" @endif>
        @if ($heroVideos->isNotEmpty())
            {{-- Dekoratif: diputar app.js hanya di layar lebar & tanpa "reduce motion"; di HP tidak diunduh. --}}
            <video class="hero-video" muted playsinline preload="none" aria-hidden="true" tabindex="-1"
                   data-hero-videos='@json($heroVideos)'></video>
        @endif
        <div class="container home-hero-inner">
            <div class="row align-items-end g-4 w-100 mx-0">
                <div class="col-lg-7 px-0">
                    <p class="hero-kicker">{{ $dealer['hero']['kicker'] ?: ($dealer['tagline'] ?: 'Nissan Heritage & Performance') }}</p>
                    <h1 id="hero-title" class="hero-title">{{ $dealer['hero']['title'] }}</h1>
                    @if (filled($dealer['hero']['lead']))
                        <p class="hero-lead">{{ $dealer['hero']['lead'] }}</p>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('cars.index') }}" class="btn btn-accent btn-lg">
                            <i class="bi bi-car-front"></i>Lihat Mobil
                        </a>
                        @if ($creditUrl)
                            <a href="{{ $creditUrl }}" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-calculator"></i>Simulasi Kredit
                            </a>
                        @endif
                    </div>
                </div>

                @if ($featuredCar && $heroVideos->isEmpty() && ! $heroBanner)
                    @php($featuredTitle = "{$featuredCar->brand->name} {$featuredCar->name} {$featuredCar->year}")
                    {{-- Hanya layar lebar: di HP foto mobil unggulan sudah menjadi latar hero. --}}
                    <div class="col-lg-5 d-none d-lg-block pe-0">
                        <a href="{{ route('cars.show', $featuredCar) }}" class="hero-feature d-block text-decoration-none"
                           aria-label="Mobil unggulan: {{ $featuredTitle }}">
                            <span class="hero-feature-badge"><i class="bi bi-star-fill"></i>Unggulan</span>
                            <img src="{{ $featuredCar->primaryImage->url }}" alt="{{ $featuredTitle }}" class="hero-feature-img">
                            <span class="hero-feature-body d-flex align-items-end justify-content-between gap-3">
                                <span class="min-w-0">
                                    <span class="d-block small text-muted">{{ $featuredCar->brand->name }} · {{ $featuredCar->condition_label }}</span>
                                    <span class="d-block fw-semibold text-body text-truncate">{{ $featuredCar->name }} {{ $featuredCar->year }}</span>
                                    @if ($featuredCar->hasPromoPrice())
                                        <del class="price-old small d-block"><x-price :amount="$featuredCar->price" /></del>
                                    @endif
                                    <x-price :amount="$featuredCar->finalPrice()" class="price-final" />
                                </span>
                                <span class="btn btn-accent btn-sm flex-shrink-0">Lihat Detail<i class="bi bi-arrow-right ms-1 me-0"></i></span>
                            </span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($latestCars->isEmpty())
        <section class="container py-5" aria-label="Mobil">
            <x-empty-state icon="bi-car-front" title="Belum ada mobil" message="Mobil akan tampil di sini setelah ditambahkan oleh dealer." />
        </section>
    @else
        <section class="container home-section" aria-labelledby="latest-title">
            <div class="home-section-head">
                <h2 id="latest-title" class="h4 mb-0">Mobil Terbaru</h2>
                <a href="{{ route('cars.index') }}" class="home-section-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
            </div>
            {{-- HP (<576px): satu baris yang bisa digeser; layar lebih besar: grid seperti biasa. --}}
            <div class="row g-4 car-scroller" tabindex="0" role="region" aria-label="Daftar mobil terbaru, geser untuk melihat lainnya">
                @foreach ($latestCars as $car)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <x-car-card :car="$car" />
                    </div>
                @endforeach
            </div>
            @if ($latestCars->count() > 1)
                <p class="car-scroller-hint small text-muted mb-0 mt-2"><i class="bi bi-arrow-left-right me-1"></i>Geser untuk melihat mobil lainnya</p>
            @endif
        </section>
    @endif

    {{-- Mobil unggulan saat hero memakai video (tanpa video, mobil unggulan sudah tampil di hero). --}}
    @if ($featuredCars->isNotEmpty() && $heroVideos->isNotEmpty())
        @php($isSlider = $featuredCars->count() > 1)
        <section class="container home-section scroll-showcase" aria-labelledby="showcase-title">
            <div class="scroll-showcase-head text-center mx-auto">
                <h2 id="showcase-title" class="h4 mb-1">Mobil Unggulan</h2>
                <p class="text-muted mb-0">
                    @if ($isSlider)
                        {{ $featuredCars->count() }} mobil pilihan {{ $dealer['name'] }}.
                    @else
                        {{ $featuredCar->brand->name }} {{ $featuredCar->name }} {{ $featuredCar->year }}, {{ strtolower($featuredCar->condition_label) }}.
                    @endif
                </p>
            </div>

            {{--
                Bingkai miring 3D lalu tegak saat digulir (CSS murni, app.css). Beberapa mobil: Carousel Bootstrap
                tanpa autoplay; pindah lewat tombol, titik, geser (HP), atau panah keyboard.
            --}}
            <div class="scroll-showcase-stage">
                <div class="scroll-showcase-card">
                    <div @if ($isSlider) id="featuredCarousel" class="carousel slide" data-bs-interval="false"
                         aria-roledescription="carousel" aria-label="Mobil unggulan" @endif>
                        <div @class(['carousel-inner' => $isSlider])>
                            @foreach ($featuredCars as $car)
                                @php($showcaseTitle = "{$car->brand->name} {$car->name} {$car->year}")
                                <div @class(['carousel-item' => $isSlider, 'active' => $isSlider && $loop->first])
                                     @if ($isSlider) aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}" @endif>
                                    <a href="{{ route('cars.show', $car) }}" class="d-block text-decoration-none"
                                       aria-label="Lihat detail {{ $showcaseTitle }}">
                                        <img src="{{ $car->primaryImage->url }}" alt="{{ $showcaseTitle }}"
                                             width="1600" height="900" loading="lazy" class="scroll-showcase-img">
                                        <span class="scroll-showcase-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                                            <span class="min-w-0">
                                                <span class="d-block fw-semibold font-heading text-white text-truncate">{{ $showcaseTitle }}</span>
                                                @if ($car->hasPromoPrice())
                                                    <del class="small d-block scroll-showcase-old"><x-price :amount="$car->price" /></del>
                                                @endif
                                                <x-price :amount="$car->finalPrice()" class="fs-5 fw-bold text-white" />
                                            </span>
                                            <span class="btn btn-accent">Lihat Detail<i class="bi bi-arrow-right ms-1 me-0"></i></span>
                                        </span>
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        @if ($isSlider)
                            {{-- Tombol di bawah slide (bukan di atas foto) agar tidak menutupi tombol Lihat Detail. --}}
                            <div class="scroll-showcase-nav">
                                <button type="button" class="scroll-showcase-arrow" data-bs-target="#featuredCarousel" data-bs-slide="prev">
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">Mobil sebelumnya</span>
                                </button>
                                <div class="carousel-indicators scroll-showcase-dots">
                                    @foreach ($featuredCars as $car)
                                        <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="{{ $loop->index }}"
                                                @class(['active' => $loop->first]) @if ($loop->first) aria-current="true" @endif
                                                aria-label="Mobil {{ $loop->iteration }}: {{ $car->brand->name }} {{ $car->name }}"></button>
                                    @endforeach
                                </div>
                                <button type="button" class="scroll-showcase-arrow" data-bs-target="#featuredCarousel" data-bs-slide="next">
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Mobil berikutnya</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($promos->isNotEmpty())
        <section class="bg-section home-section" aria-labelledby="promo-title">
            <div class="container">
                <div class="home-section-head">
                    <h2 id="promo-title" class="h4 mb-0">Promo Berjalan</h2>
                    @if (Route::has('promos.index'))
                        <a href="{{ route('promos.index') }}" class="home-section-link">Semua promo <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="row g-4">
                    @foreach ($promos as $promo)
                        <div class="col-12 col-md-4">
                            <x-promo-card :promo="$promo" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Kartu merek hanya bila ada lebih dari satu merek (dealer satu merek cukup kategori). --}}
    @php($showBrands = $brands->count() > 1)
    @if ($showBrands || $categories->isNotEmpty())
        <section class="container home-section" aria-labelledby="shortcut-title">
            <div class="home-section-head">
                <h2 id="shortcut-title" class="h4 mb-0">Jelajahi Mobil</h2>
            </div>
            <div class="row g-4">
                @if ($showBrands)
                    <div class="col-lg-6">
                        <div class="card h-100 shortcut-card">
                            <div class="card-body p-3 p-lg-4">
                        <h3 class="shortcut-card-title">Berdasarkan merek</h3>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($brands as $brand)
                                <a href="{{ route('cars.index', ['merek' => $brand->slug]) }}" class="shortcut-chip">
                                    {{ $brand->name }} <span class="shortcut-chip-count">{{ $brand->cars_count }}</span>
                                </a>
                            @endforeach
                        </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($categories->isNotEmpty())
                    <div @class(['col-lg-6' => $showBrands, 'col-12' => ! $showBrands])>
                        <div class="card h-100 shortcut-card">
                            <div class="card-body p-3 p-lg-4">
                        <h3 class="shortcut-card-title">Berdasarkan kategori</h3>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($categories as $category)
                                <a href="{{ route('cars.index', ['kategori' => $category->slug]) }}" class="shortcut-chip">
                                    {{ $category->name }} <span class="shortcut-chip-count">{{ $category->cars_count }}</span>
                                </a>
                            @endforeach
                        </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        {{-- Ringkasan rating di kiri, ulasan berupa daftar bergaris tipis di kanan (tanpa kartu & ikon kutip). --}}
        <section class="bg-section home-section" aria-labelledby="testimonial-title">
            <div class="container">
                <div class="row g-4 g-lg-5">
                    <div class="col-lg-4">
                        <h2 id="testimonial-title" class="h4 mb-3">Kata Pelanggan</h2>
                        <p class="testimonial-score mb-1">
                            {{ number_format((float) $ratingSummary->average, 1, ',', '.') }}<span class="testimonial-score-max">/5</span>
                        </p>
                        <x-rating-stars :rating="round((float) $ratingSummary->average)" class="mb-2" />
                        <p class="text-muted small mb-0">Dari {{ $ratingSummary->total }} ulasan pembeli yang sudah menerima mobilnya.</p>
                    </div>
                    <div class="col-lg-8">
                        <ul class="testimonial-list list-unstyled mb-0">
                            @foreach ($testimonials as $testimonial)
                                @php($car = $testimonial->purchaseRequest->car)
                                <li class="testimonial-item">
                                    <figure class="mb-0">
                                        <blockquote class="mb-2">
                                            <p class="testimonial-quote mb-0 text-break">{{ \Illuminate\Support\Str::limit($testimonial->comment, 220) }}</p>
                                        </blockquote>
                                        <figcaption class="small d-flex flex-wrap align-items-center gap-2">
                                            <span class="fw-semibold">{{ \App\Models\Testimonial::publicName($testimonial->user->name) }}</span>
                                            <span class="text-muted">membeli {{ $car->brand->name }} {{ $car->name }} {{ $car->year }}</span>
                                            <x-rating-stars :rating="$testimonial->rating" class="testimonial-stars" />
                                        </figcaption>
                                    </figure>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @include('partials.help-band')
@endsection
