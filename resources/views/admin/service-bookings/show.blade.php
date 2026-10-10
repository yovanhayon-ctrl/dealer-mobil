@extends('layouts.admin')

@section('title', 'Detail Booking Servis')

@php
    $service = $booking->service;
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Booking Servis', 'url' => route('admin.service-bookings.index')],
        ['label' => $booking->user->name.' · '.$booking->preferred_date->translatedFormat('d M Y')],
    ]])
@endsection

@section('content')
    <x-admin.detail-header :title="'Booking Servis #'.$booking->id" :subtitle="'Masuk '.$booking->created_at->translatedFormat('d F Y H:i').' WIB'"
                           :back-url="route('admin.service-bookings.index')" back-label="Booking Servis">
        <x-status-badge :status="$booking->status" />
    </x-admin.detail-header>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <dl class="admin-detail-list">
                        <dt>Jadwal</dt>
                        <dd>{{ $booking->preferred_date->translatedFormat('l, d F Y') }} · {{ $booking->timeLabel() }} WIB</dd>

                        <dt>Layanan</dt>
                        <dd>
                            {{ $service->name }}
                            @if ($service->price_from)
                                <span class="text-muted">· mulai Rp {{ number_format($service->price_from, 0, ',', '.') }}</span>
                            @endif
                            @if ($service->duration_minutes)
                                <span class="text-muted">· ±{{ $service->duration_minutes }} menit</span>
                            @endif
                            @unless ($service->is_active)
                                <x-status-badge status="inactive" class="ms-1" />
                            @endunless
                        </dd>

                        <dt>Kendaraan</dt>
                        <dd>
                            {{ $booking->vehicle_model }}@if ($booking->vehicle_year) ({{ $booking->vehicle_year }})@endif
                        </dd>

                        <dt>Plat nomor</dt>
                        <dd>{{ $booking->plate_number }}</dd>

                        <dt>Kilometer</dt>
                        <dd>{{ $booking->mileage !== null ? number_format($booking->mileage, 0, ',', '.').' km' : '—' }}</dd>

                        <dt>Customer</dt>
                        <dd>
                            <a href="{{ route('admin.users.show', $booking->user) }}">{{ $booking->user->name }}</a>
                            <span class="text-muted">· <a href="mailto:{{ $booking->user->email }}" class="text-muted">{{ $booking->user->email }}</a></span>
                        </dd>

                        <dt>Nomor HP</dt>
                        <dd>
                            <a href="tel:{{ $booking->phone }}">{{ $booking->phone }}</a>
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $booking->phone)) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-success py-0 ms-2"><i class="bi bi-whatsapp"></i>WhatsApp</a>
                        </dd>

                        <dt>Keluhan</dt>
                        <dd>{{ $booking->complaint ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($booking->status === \App\Models\ServiceBooking::STATUS_CONFIRMED && $booking->preferred_date->isAfter(today()))
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>Status <strong>Dikerjakan</strong> baru bisa dipilih pada tanggal jadwal servis.
                </div>
            @endif
        </div>

        <div class="col-xl-5">
            <div class="card admin-sticky-panel">
                <div class="card-body p-4">
                    <h3 class="admin-card-title">Status & Catatan Admin</h3>
                    @include('admin.partials.status-form', [
                        'action' => route('admin.service-bookings.update-status', $booking),
                        'currentLabel' => $booking->statusLabel(),
                        'allowed' => collect($booking->allowedTransitions())
                            ->mapWithKeys(fn ($status) => [$status => \App\Models\ServiceBooking::STATUS_LABELS[$status]])
                            ->all(),
                        'adminNote' => $booking->admin_note,
                        'noteHelp' => 'Wajib diisi saat membatalkan (alasan untuk customer). Bisa juga berisi hasil pekerjaan. Maksimal 1000 karakter.',
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
