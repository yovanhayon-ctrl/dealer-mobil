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
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="h5 mb-0">Booking Servis #{{ $booking->id }}</h2>
                        <x-status-badge :status="$booking->status" />
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Jadwal</dt>
                        <dd class="col-sm-8">{{ $booking->preferred_date->translatedFormat('l, d F Y') }} · {{ $booking->timeLabel() }} WIB</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Layanan</dt>
                        <dd class="col-sm-8">
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

                        <dt class="col-sm-4 text-muted fw-normal">Kendaraan</dt>
                        <dd class="col-sm-8">
                            {{ $booking->vehicle_model }}@if ($booking->vehicle_year) ({{ $booking->vehicle_year }})@endif
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Plat nomor</dt>
                        <dd class="col-sm-8">{{ $booking->plate_number }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Kilometer</dt>
                        <dd class="col-sm-8">{{ $booking->mileage !== null ? number_format($booking->mileage, 0, ',', '.').' km' : '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Customer</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('admin.users.show', $booking->user) }}">{{ $booking->user->name }}</a>
                            <span class="text-muted">· {{ $booking->user->email }}</span>
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Nomor HP</dt>
                        <dd class="col-sm-8">{{ $booking->phone }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Keluhan</dt>
                        <dd class="col-sm-8">{{ $booking->complaint ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Masuk</dt>
                        <dd class="col-sm-8 mb-0">{{ $booking->created_at->translatedFormat('d M Y H:i') }} WIB</dd>
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
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="h6 mb-3">Status & Catatan Admin</h3>
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
