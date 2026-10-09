@extends('layouts.app')

@section('title', 'Beranda')
@section('meta_description', config('dealer.name').' — Nissan Heritage & Performance: mobil Nissan baru, koleksi klasik Jepang, promo, cicilan ringan, dan layanan servis.')

@php
    $dealer = config('dealer');
    $creditUrl = Route::has('credit.index') ? route('credit.index') : null;
@endphp

@section('content')
    <section class="home-hero py-5" aria-labelledby="hero-title">
        <div class="container py-lg-4">
            <div class="row">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold mb-2 opacity-75">Nissan Heritage &amp; Performance</p>
                    <h1 id="hero-title" class="display-5 fw-bold text-white mb-3">{{ $dealer['name'] }}</h1>
                    <p class="lead mb-4">
                        {{ $dealer['tagline'] ?: 'Temukan mobil impian Anda dengan proses yang mudah dan transparan.' }}
                    </p>
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
            </div>
        </div>
    </section>

    <section class="container home-search" aria-labelledby="search-title">
        <div class="card">
            <div class="card-body p-3 p-lg-4">
                <h2 id="search-title" class="h5 mb-3">Cari Mobil</h2>
                <form method="GET" action="{{ route('cars.index') }}" class="row g-2 align-items-end" role="search">
                    <div class="col-12 col-md-5">
                        <label for="home_q" class="form-label small mb-1">Kata kunci</label>
                        <input type="search" id="home_q" name="q" class="form-control" placeholder="Nama mobil atau merek…" maxlength="100">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="home_merek" class="form-label small mb-1">Merek</label>
                        <select id="home_merek" name="merek" class="form-select">
                            <option value="">Semua merek</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->slug }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="home_kondisi" class="form-label small mb-1">Kondisi</label>
                        <select id="home_kondisi" name="kondisi" class="form-select">
                            <option value="">Semua</option>
                            @foreach (\App\Models\Car::CONDITIONS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i>Cari</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @if ($latestCars->isEmpty())
        <section class="container py-5" aria-label="Mobil">
            <x-empty-state icon="bi-car-front" title="Belum ada mobil" message="Mobil akan tampil di sini setelah ditambahkan oleh dealer." />
        </section>
    @else
        <section class="container py-5" aria-labelledby="latest-title">
            <div class="d-flex align-items-end justify-content-between gap-2 mb-3">
                <h2 id="latest-title" class="h4 mb-0">Mobil Terbaru</h2>
                <a href="{{ route('cars.index') }}" class="small fw-semibold text-nowrap">Lihat semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row g-4">
                @foreach ($latestCars as $car)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <x-car-card :car="$car" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($promos->isNotEmpty())
        <section class="bg-section py-5" aria-labelledby="promo-title">
            <div class="container">
                <div class="d-flex align-items-end justify-content-between gap-2 mb-3">
                    <h2 id="promo-title" class="h4 mb-0">Promo Berjalan</h2>
                    @if (Route::has('promos.index'))
                        <a href="{{ route('promos.index') }}" class="small fw-semibold text-nowrap">Semua promo <i class="bi bi-arrow-right"></i></a>
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

    @if ($brands->isNotEmpty() || $categories->isNotEmpty())
        <section class="container py-5" aria-labelledby="shortcut-title">
            <h2 id="shortcut-title" class="h4 mb-3">Jelajahi Mobil</h2>
            <div class="row g-4">
                @if ($brands->isNotEmpty())
                    <div class="col-lg-6">
                        <h3 class="h6 text-muted text-uppercase mb-2">Berdasarkan merek</h3>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($brands as $brand)
                                <a href="{{ route('cars.index', ['merek' => $brand->slug]) }}" class="shortcut-chip">
                                    {{ $brand->name }} <span class="shortcut-chip-count">{{ $brand->cars_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($categories->isNotEmpty())
                    <div class="col-lg-6">
                        <h3 class="h6 text-muted text-uppercase mb-2">Berdasarkan kategori</h3>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($categories as $category)
                                <a href="{{ route('cars.index', ['kategori' => $category->slug]) }}" class="shortcut-chip">
                                    {{ $category->name }} <span class="shortcut-chip-count">{{ $category->cars_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($creditUrl || $dealer['whatsapp'])
        <section class="container pb-2" aria-labelledby="cta-title">
            <div class="home-cta rounded-4 p-4 p-lg-5 d-lg-flex align-items-center justify-content-between gap-4">
                <div class="mb-3 mb-lg-0">
                    <h2 id="cta-title" class="h4 text-white mb-1">Butuh bantuan memilih mobil?</h2>
                    <p class="mb-0">Hitung cicilan atau tanyakan langsung ke tim kami.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if ($creditUrl)
                        <a href="{{ $creditUrl }}" class="btn btn-accent"><i class="bi bi-calculator"></i>Hitung Cicilan</a>
                    @endif
                    @if ($dealer['whatsapp'])
                        <a href="https://wa.me/{{ $dealer['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-success">
                            <i class="bi bi-whatsapp"></i>Tanya via WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        </section>
    @endif
@endsection
