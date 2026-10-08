@extends('layouts.app')

@section('title', 'Tentang Kami')
@section('meta_description', config('dealer.name').' — Nissan Heritage & Performance: dealer mobil Nissan baru, koleksi klasik Jepang, dan layanan servis JAF Service.')

@php
    $dealer = config('dealer');
    $journey = [
        ['bi-search', 'Temukan', 'Cari dan filter kendaraan di katalog.'],
        ['bi-card-list', 'Kenali', 'Lihat foto, spesifikasi, harga, dan promo.'],
        ['bi-calculator', 'Pertimbangkan', 'Hitung simulasi kredit atau tanya dealer.'],
        ['bi-calendar-check', 'Coba', 'Booking jadwal test drive.'],
        ['bi-cart-check', 'Ajukan', 'Kirim pengajuan pembelian cash atau kredit.'],
        ['bi-arrow-repeat', 'Tindak lanjut', 'Pantau status pengajuan di akun Anda.'],
    ];
    $offers = [
        ['bi-car-front', 'Nissan Baru', 'Model Nissan terkini seperti Kicks e-Power, Serena e-Power, dan X-Trail e-Power.', 'cars.index', ['kondisi' => 'baru']],
        ['bi-trophy', 'Heritage & Klasik Jepang', 'Koleksi bekas pilihan: Skyline GT-R, Silvia, Fairlady Z, hingga ikon JDM lainnya.', 'cars.index', ['kondisi' => 'bekas']],
        ['bi-tools', 'JAF Service', 'Servis berkala, inspeksi, perawatan, ban & aki, detailing, dan suku cadang.', 'services.index', []],
    ];
@endphp

@section('content')
    <section class="home-hero py-5" aria-labelledby="about-title">
        <div class="container py-lg-3">
            <div class="row">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold mb-2 opacity-75">Nissan Heritage &amp; Performance</p>
                    <h1 id="about-title" class="display-6 fw-bold text-white mb-3">Tentang {{ $dealer['name'] }}</h1>
                    <p class="lead mb-0">{{ $dealer['tagline'] ?: 'Dream the Legacy. Drive the Future.' }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Tentang Kami']]])

        <div class="row g-4 align-items-center mb-5">
            <div class="col-lg-7">
                <h2 class="h4 mb-3">Siapa Kami</h2>
                <p>
                    {{ $dealer['name'] }} adalah dealer mobil berbasis web yang menjadikan katalog kendaraan Nissan sebagai titik awal
                    interaksi dengan calon pembeli, dari generasi terdahulu sampai model yang tersedia saat ini, dilengkapi koleksi
                    mobil klasik Jepang pilihan.
                </p>
                <p class="mb-0">
                    Website ini bukan sekadar etalase. Anda dapat menemukan kendaraan, membandingkan informasi, menghitung simulasi kredit,
                    lalu melanjutkan ke test drive atau pengajuan pembelian, tanpa harus datang ke showroom hanya untuk informasi dasar.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="row g-3 text-center">
                    @foreach ([['cars', 'Mobil tersedia', 'bi-car-front'], ['brands', 'Merek', 'bi-tags'], ['services', 'Layanan servis', 'bi-tools']] as [$key, $label, $icon])
                        <div class="col-4">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <i class="bi {{ $icon }} fs-3 text-accent"></i>
                                    <p class="h4 fw-bold mb-0">{{ $stats[$key] }}</p>
                                    <p class="small text-muted mb-0">{{ $label }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <h2 class="h4 mb-3">Yang Kami Tawarkan</h2>
        <div class="row g-4 mb-5">
            @foreach ($offers as [$icon, $heading, $text, $route, $query])
                <div class="col-md-4">
                    <div class="card h-100 card-hover">
                        <div class="card-body p-4">
                            <span class="service-card-icon mb-3" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                            <h3 class="h5">
                                @if (Route::has($route))
                                    <a href="{{ route($route, $query) }}" class="stretched-link text-reset text-decoration-none">{{ $heading }}</a>
                                @else
                                    {{ $heading }}
                                @endif
                            </h3>
                            <p class="small text-muted mb-0">{{ $text }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <h2 class="h4 mb-3">Dari Menemukan hingga Membeli</h2>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-5">
            @foreach ($journey as $index => [$icon, $heading, $text])
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="small text-muted fw-semibold mb-1">0{{ $index + 1 }}</p>
                            <p class="fw-semibold mb-1"><i class="bi {{ $icon }} text-accent me-1"></i>{{ $heading }}</p>
                            <p class="small text-muted mb-0">{{ $text }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="home-cta rounded-4 p-4 p-lg-5 d-lg-flex align-items-center justify-content-between gap-4">
            <div class="mb-3 mb-lg-0">
                <h2 class="h4 text-white mb-1">Siap menemukan Nissan impian Anda?</h2>
                <p class="mb-0">Jelajahi katalog atau hubungi tim kami untuk konsultasi.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('cars.index') }}" class="btn btn-accent"><i class="bi bi-car-front"></i>Lihat Mobil</a>
                <a href="{{ route('contact') }}" class="btn btn-outline-light"><i class="bi bi-telephone"></i>Hubungi Kami</a>
            </div>
        </div>

        <p class="small text-muted mt-4 mb-0">
            <i class="bi bi-info-circle me-1"></i>{{ $dealer['name'] }} adalah konsep dealer fiktif untuk kebutuhan akademik dan demonstrasi aplikasi;
            tidak mewakili dealer resmi Nissan.
        </p>
    </div>
@endsection
