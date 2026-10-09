@extends('layouts.app')

@section('title', 'Riwayat Servis')

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Riwayat Servis']]])

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h3 mb-0">Riwayat Servis</h1>
            <a href="{{ route('service-bookings.create') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-plus-lg"></i>Booking Baru
            </a>
        </div>

        @if ($bookings->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-wrench-adjustable" title="Belum ada booking servis"
                               message="Booking servis untuk merawat kendaraan Anda.">
                    <a href="{{ route('service-bookings.create') }}" class="btn btn-primary btn-sm">Booking Servis</a>
                </x-empty-state>
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($bookings as $booking)
                    <article class="card test-drive-item" id="servis-{{ $booking->id }}">
                        <div class="card-body p-3 d-flex flex-column flex-md-row gap-3">
                            <span class="service-card-icon flex-shrink-0" aria-hidden="true"><i class="bi bi-tools"></i></span>

                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h2 class="h6 mb-0">{{ $booking->service->name }}</h2>
                                    <x-status-badge :status="$booking->status" />
                                </div>
                                <p class="small mb-1">
                                    <i class="bi bi-car-front me-1"></i>{{ $booking->vehicle_model }}@if ($booking->vehicle_year) ({{ $booking->vehicle_year }})@endif
                                    · <span class="fw-semibold">{{ $booking->plate_number }}</span>
                                    @if ($booking->mileage !== null)
                                        · {{ number_format($booking->mileage, 0, ',', '.') }} km
                                    @endif
                                </p>
                                <p class="small mb-2">
                                    <i class="bi bi-calendar-event me-1"></i>{{ $booking->preferred_date->translatedFormat('l, d M Y') }}
                                    <i class="bi bi-clock ms-2 me-1"></i>{{ $booking->timeLabel() }} WIB
                                    <span class="text-muted ms-2">· dibuat {{ $booking->created_at->translatedFormat('d M Y H:i') }}</span>
                                </p>

                                @if ($booking->complaint)
                                    <p class="small text-muted mb-2"><span class="fw-semibold">Keluhan Anda:</span> {{ $booking->complaint }}</p>
                                @endif

                                @if ($booking->admin_note)
                                    <div class="admin-note-box small rounded-3 p-2 mb-2">
                                        <span class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Catatan bengkel:</span>
                                        {{ $booking->admin_note }}
                                    </div>
                                @endif
                            </div>

                            @if ($booking->canBeCancelledByCustomer())
                                <div class="flex-shrink-0">
                                    <form method="POST" action="{{ route('account.service-bookings.cancel', $booking) }}"
                                          data-confirm="Batalkan booking {{ $booking->service->name }} untuk {{ $booking->plate_number }} pada {{ $booking->preferred_date->translatedFormat('d M Y') }}?"
                                          data-disable-on-submit>
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-x-circle"></i>Batalkan
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($bookings->hasPages())
                <div class="mt-4">
                    {{ $bookings->onEachSide(1)->links('partials.pagination-links') }}
                </div>
            @endif
        @endif
    </div>
@endsection
