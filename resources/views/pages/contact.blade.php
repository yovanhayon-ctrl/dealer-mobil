@extends('layouts.app')

@section('title', 'Kontak')
@section('meta_description', 'Hubungi '.config('dealer.name').': alamat showroom, telepon, WhatsApp, email, dan jam operasional.')

@php
    $items = array_filter([
        ['bi-geo-alt', 'Alamat Showroom', $dealer['address'], null],
        ['bi-telephone', 'Telepon', $dealer['phone'], $phoneUrl],
        ['bi-whatsapp', 'WhatsApp', $whatsappUrl ? 'Chat dengan tim kami' : null, $whatsappUrl],
        ['bi-envelope', 'Email', $dealer['email'], $dealer['email'] ? 'mailto:'.$dealer['email'] : null],
        ['bi-clock', 'Jam Operasional', $dealer['hours'], null],
    ], fn (array $item) => filled($item[2]));
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Kontak']]])

        <h1 class="h3 mb-1">Hubungi Kami</h1>
        <p class="text-muted mb-4">Ada pertanyaan tentang mobil, kredit, atau servis? Tim {{ $dealer['name'] }} siap membantu.</p>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        @if ($items === [])
                            <p class="text-muted mb-0">Informasi kontak belum tersedia.</p>
                        @else
                            <ul class="list-unstyled mb-0">
                                @foreach ($items as [$icon, $label, $value, $url])
                                    <li @class(['d-flex gap-3', 'mb-3' => ! $loop->last])>
                                        <span class="service-card-icon flex-shrink-0" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                                        <div class="min-w-0">
                                            <p class="small text-muted mb-0">{{ $label }}</p>
                                            @if ($url)
                                                <a href="{{ $url }}" class="fw-semibold text-break"
                                                   @if (str_starts_with($url, 'https://')) target="_blank" rel="noopener" @endif>{{ $value }}</a>
                                            @else
                                                <p class="fw-semibold mb-0">{{ $value }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- WhatsApp cukup di daftar kontak di atas; pintasan berupa tautan teks. --}}
                <h2 class="h6 mb-2">Butuh sesuatu yang lebih cepat?</h2>
                <ul class="list-unstyled mb-0">
                    @foreach ([
                        ['test-drives.create', 'bi-calendar-check', 'Booking Test Drive'],
                        ['services.index', 'bi-tools', 'Booking Servis'],
                        ['credit.index', 'bi-calculator', 'Simulasi Kredit'],
                    ] as [$route, $icon, $label])
                        @if (Route::has($route))
                            <li class="mb-2">
                                <a href="{{ route($route) }}" class="car-detail-link"><i class="bi {{ $icon }} me-2"></i>{{ $label }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            <div class="col-lg-7">
                <div class="card h-100 overflow-hidden">
                    @if ($mapsUrl)
                        <iframe src="{{ $mapsUrl }}" title="Peta lokasi {{ $dealer['name'] }}" class="contact-map"
                                loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @else
                        <x-empty-state icon="bi-map" title="Peta belum tersedia"
                                       :message="$dealer['address'] ?: 'Silakan hubungi kami untuk petunjuk lokasi.'" />
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
