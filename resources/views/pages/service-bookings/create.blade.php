@extends('layouts.app')

@section('title', 'Booking Servis')
@section('meta_description', 'Booking jadwal servis kendaraan di '.config('dealer.name').', pilih layanan, tanggal, dan jam yang Anda inginkan.')

@php
    $slots = \App\Models\ServiceBooking::TIME_SLOTS;
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [
            ['label' => 'Servis', 'url' => route('services.index')],
            ['label' => 'Booking Servis'],
        ]])

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <h1 class="h3 mb-1">Booking Servis</h1>
                <p class="text-muted mb-4">
                    Isi data kendaraan dan jadwal, lalu tim bengkel kami akan menghubungi Anda lewat WhatsApp untuk konfirmasi.
                </p>

                @if ($isAdmin)
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div>
                            {{ \App\Http\Requests\ServiceBookingRequest::ADMIN_MESSAGE }}
                            <a href="{{ route('admin.service-bookings.index') }}" class="alert-link">Kelola booking servis di dashboard admin</a>.
                        </div>
                    </div>
                @elseif ($services->isEmpty())
                    <div class="card">
                        <x-empty-state icon="bi-tools" title="Layanan servis belum tersedia"
                                       message="Silakan cek kembali nanti atau hubungi dealer." />
                    </div>
                @else
                    <div class="card">
                        <div class="card-body p-3 p-lg-4">
                            <form method="POST" action="{{ route('service-bookings.store') }}" data-disable-on-submit>
                                @csrf

                                <x-form.select name="service_id" label="Layanan" required placeholder="Pilih layanan"
                                               :value="$selectedService?->id"
                                               :options="$services->mapWithKeys(fn ($service) => [$service->id => $service->price_from
                                                   ? $service->name.' (mulai Rp '.number_format($service->price_from, 0, ',', '.').')'
                                                   : $service->name])->all()" />

                                <h2 class="h6 text-muted text-uppercase mt-4 mb-3">Data Kendaraan</h2>
                                <div class="row g-3">
                                    <div class="col-sm-7">
                                        <x-form.input name="vehicle_model" label="Merek & Model" required maxlength="100"
                                                      placeholder="Contoh: Nissan Livina VL" />
                                    </div>
                                    <div class="col-sm-5">
                                        <x-form.input name="plate_number" label="Plat Nomor" required maxlength="15"
                                                      placeholder="B 1234 ABC" autocapitalize="characters" />
                                    </div>
                                    <div class="col-6 col-sm-4">
                                        <x-form.input name="vehicle_year" label="Tahun" inputmode="numeric" maxlength="4"
                                                      placeholder="2020" help="Opsional." />
                                    </div>
                                    <div class="col-6 col-sm-8">
                                        <x-form.input name="mileage" label="Kilometer Saat Ini" inputmode="numeric"
                                                      placeholder="Contoh: 45.000" help="Opsional, membantu menentukan paket servis." />
                                    </div>
                                </div>

                                <h2 class="h6 text-muted text-uppercase mt-2 mb-3">Jadwal</h2>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <x-form.input name="preferred_date" type="date" label="Tanggal" required
                                                      min="{{ $firstDate }}" max="{{ $lastDate }}"
                                                      help="Besok s/d 30 hari ke depan." />
                                    </div>
                                    <div class="col-sm-6">
                                        <x-form.select name="preferred_time" label="Jam (WIB)" required placeholder="Pilih jam"
                                                       :options="collect($slots)->mapWithKeys(fn ($time) => [$time => $time.' WIB'])->all()" />
                                    </div>
                                </div>

                                <x-form.input name="phone" type="tel" label="Nomor WhatsApp" required
                                              :value="auth()->user()->phone" inputmode="tel" autocomplete="tel"
                                              help="Format 08xx. Dipakai tim kami untuk konfirmasi jadwal." />

                                <x-form.textarea name="complaint" label="Keluhan (opsional)" rows="3"
                                                 maxlength="{{ \App\Http\Requests\ServiceBookingRequest::MAX_COMPLAINT_LENGTH }}"
                                                 help="Mis. AC kurang dingin, rem berbunyi, atau ingin sekalian ganti aki." />

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <button type="submit" class="btn btn-accent">
                                        <i class="bi bi-calendar-check"></i>Kirim Booking
                                    </button>
                                    <a href="{{ route('account.service-bookings.index') }}" class="btn btn-outline-secondary">Riwayat Servis</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <ul class="small text-muted mt-3 mb-0">
                        <li>Jadwal dapat dipilih mulai besok sampai 30 hari ke depan, pukul {{ reset($slots) }}–{{ end($slots) }} WIB.</li>
                        <li>Setiap jam hanya menerima {{ \App\Models\ServiceBooking::slotCapacity() }} kendaraan sesuai kapasitas bengkel.</li>
                        <li>Status booking "Menunggu" sampai dikonfirmasi dealer; Anda bisa membatalkannya selama belum dikonfirmasi.</li>
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
