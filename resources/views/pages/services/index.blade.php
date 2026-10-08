@extends('layouts.app')

@section('title', 'Servis & Perawatan')
@section('meta_description', 'Layanan servis '.config('dealer.name').': servis berkala, inspeksi, perawatan ringan, ban & aki, detailing, dan suku cadang. Booking jadwal servis secara online.')

@php
    $whatsapp = config('dealer.whatsapp');
    $slots = \App\Models\ServiceBooking::TIME_SLOTS;
@endphp

@section('content')
    <section class="home-hero py-5" aria-labelledby="service-title">
        <div class="container py-lg-3">
            <div class="row">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold mb-2 opacity-75">JAF Service</p>
                    <h1 id="service-title" class="display-6 fw-bold text-white mb-3">Servis & Perawatan Kendaraan</h1>
                    <p class="lead mb-4">
                        Layanan yang menemani Anda setelah pembelian: jaga kendaraan tetap prima dengan servis berkala,
                        inspeksi, dan perawatan oleh tim bengkel kami.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('service-bookings.create') }}" class="btn btn-accent btn-lg">
                            <i class="bi bi-calendar-plus"></i>Booking Servis
                        </a>
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Halo, saya ingin konsultasi servis kendaraan.') }}"
                               target="_blank" rel="noopener" class="btn btn-success btn-lg">
                                <i class="bi bi-whatsapp"></i>Konsultasi Servis
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Servis']]])

        <h2 class="h4 mb-1">Layanan Kami</h2>
        <p class="text-muted mb-4">Harga adalah estimasi awal; biaya akhir mengikuti pemeriksaan dan komponen yang dibutuhkan.</p>

        @if ($services->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-tools" title="Layanan belum tersedia"
                               message="Silakan hubungi dealer untuk informasi servis." />
            </div>
        @else
            <div class="row g-4">
                @foreach ($services as $service)
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card h-100 service-card">
                            <div class="card-body d-flex flex-column p-4">
                                <span class="service-card-icon mb-3" aria-hidden="true"><i class="bi bi-tools"></i></span>
                                <h3 class="h5 mb-2">{{ $service->name }}</h3>
                                @if ($service->description)
                                    <p class="text-muted small mb-3">{{ $service->description }}</p>
                                @endif

                                <div class="mt-auto">
                                    <p class="mb-1">
                                        @if ($service->price_from)
                                            <span class="small text-muted">Mulai</span>
                                            <span class="fw-bold">Rp {{ number_format($service->price_from, 0, ',', '.') }}</span>
                                        @else
                                            <span class="small text-muted">Harga sesuai kebutuhan — hubungi dealer</span>
                                        @endif
                                    </p>
                                    @if ($service->duration_minutes)
                                        <p class="small text-muted mb-3"><i class="bi bi-clock me-1"></i>Estimasi ±{{ $service->duration_minutes }} menit</p>
                                    @else
                                        <div class="mb-3"></div>
                                    @endif
                                    <a href="{{ route('service-bookings.create', ['layanan' => $service->slug]) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-calendar-plus"></i>Booking Layanan Ini
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="row g-4 mt-2">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Cara Booking Servis</h2>
                        <ol class="small mb-0 ps-3">
                            <li class="mb-1">Masuk atau daftar akun customer.</li>
                            <li class="mb-1">Pilih layanan, isi data kendaraan, lalu pilih tanggal dan jam.</li>
                            <li class="mb-1">Tim kami mengonfirmasi jadwal lewat WhatsApp.</li>
                            <li>Pantau status servis di menu <strong>Servis Saya</strong>.</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Layanan Pendukung</h2>
                        <ul class="small mb-0 ps-3">
                            <li class="mb-1">Konsultasi kebutuhan servis sesuai kondisi dan pemakaian kendaraan.</li>
                            <li class="mb-1">Informasi estimasi pekerjaan dan komponen yang perlu diperiksa.</li>
                            <li class="mb-1">Riwayat servis tersimpan di akun Anda.</li>
                            <li>Jadwal bengkel: besok s/d 30 hari ke depan, pukul {{ reset($slots) }}–{{ end($slots) }} WIB.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
