@extends('layouts.app')

@section('title', 'Tentang Kami')
@section('meta_description', config('dealer.name').', Nissan Heritage & Performance: dealer khusus Nissan: mobil baru, koleksi Nissan heritage, dan layanan servis JAF Service.')

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
    <section class="home-hero" aria-labelledby="about-title">
        <div class="container py-5">
            <div class="col-lg-8 px-0 py-lg-3">
                <p class="hero-kicker">Nissan Heritage &amp; Performance</p>
                <h1 id="about-title" class="display-6 fw-bold text-white mb-3">Tentang {{ $dealer['name'] }}</h1>
                <p class="lead mb-0">{{ $dealer['tagline'] ?: 'Dream the Legacy. Drive the Future.' }}</p>
            </div>
        </div>
    </section>

    <div class="container py-5">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Tentang Kami']]])

        <div class="row g-4 g-lg-5 align-items-center mb-5">
            <div class="col-lg-7">
                <h2 class="h4 mb-3">Siapa Kami</h2>
                <p>
                    {{ $dealer['name'] }} adalah dealer mobil berbasis web yang menjadikan katalog kendaraan Nissan sebagai titik awal
                    interaksi dengan calon pembeli, dari generasi terdahulu seperti Skyline GT-R dan Silvia sampai model yang tersedia saat ini.
                </p>
                <p class="mb-0">
                    Website ini bukan sekadar etalase. Anda dapat menemukan kendaraan, membandingkan informasi, menghitung simulasi kredit,
                    lalu melanjutkan ke test drive atau pengajuan pembelian, tanpa harus datang ke showroom hanya untuk informasi dasar.
                </p>
            </div>
            <div class="col-lg-5">
                {{-- Angka dari database (mobil aktif, merek yang punya mobil aktif, layanan aktif). --}}
                <dl class="about-stats mb-0">
                    @foreach ([['cars', 'Mobil tersedia'], ['brands', 'Merek'], ['services', 'Layanan servis']] as [$key, $label])
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $stats[$key] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>

        <h2 class="h4 mb-3">Yang Kami Tawarkan</h2>
        <ul class="about-offers list-unstyled mb-5">
            @foreach ($offers as [$icon, $heading, $text, $route, $query])
                <li class="about-offer">
                    <h3 class="h5 mb-1">{{ $heading }}</h3>
                    <p class="text-muted mb-0">{{ $text }}</p>
                    @if (Route::has($route))
                        <a href="{{ route($route, $query) }}" class="car-detail-link text-nowrap">Lihat<i class="bi bi-arrow-right ms-1"></i><span class="visually-hidden"> {{ $heading }}</span></a>
                    @endif
                </li>
            @endforeach
        </ul>

        <h2 class="h4 mb-3">Dari Menemukan hingga Membeli</h2>
        <ol class="about-steps list-unstyled mb-5">
            @foreach ($journey as [$icon, $heading, $text])
                <li>
                    <p class="fw-semibold mb-1">{{ $heading }}</p>
                    <p class="small text-muted mb-0">{{ $text }}</p>
                </li>
            @endforeach
        </ol>

        <p class="small text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>{{ $dealer['name'] }} adalah konsep dealer fiktif untuk kebutuhan akademik dan demonstrasi aplikasi;
            tidak mewakili dealer resmi Nissan.
        </p>
    </div>

    @include('partials.help-band', ['title' => 'Siap menemukan Nissan impian Anda?'])
@endsection
