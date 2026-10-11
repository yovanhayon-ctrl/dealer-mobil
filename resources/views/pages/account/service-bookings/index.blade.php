@extends('layouts.app')

@section('title', 'Servis Saya')

@section('content')
    <x-account.layout title="Servis Saya" active="account.service-bookings.*"
                      :summary="$statusCounts['semua'].' booking servis'">
        <x-slot:actions>
            <a href="{{ route('service-bookings.create') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-plus-lg"></i>Booking Servis
            </a>
        </x-slot:actions>

        @if ($statusCounts['semua'] > 0)
            <x-slot:filters>
                <x-account.status-filter route="account.service-bookings.index" :counts="$statusCounts" :active="$statusGroup" />
            </x-slot:filters>
        @endif

        @if ($bookings->isEmpty())
            <div class="card">
                @if ($statusGroup)
                    <x-empty-state icon="bi-funnel" title="Tidak ada booking servis dengan status ini"
                                   message="Pilih status lain atau tampilkan semua booking servis.">
                        <a href="{{ route('account.service-bookings.index') }}" class="btn btn-primary btn-sm">Tampilkan Semua</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="bi-wrench-adjustable" title="Belum ada booking servis"
                                   message="Booking servis untuk merawat kendaraan Anda.">
                        <a href="{{ route('service-bookings.create') }}" class="btn btn-primary btn-sm">Booking Servis</a>
                    </x-empty-state>
                @endif
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($bookings as $booking)
                    <article class="card test-drive-item account-item" id="servis-{{ $booking->id }}">
                        <div class="card-body p-3">
                            <div class="account-item-head">
                                <span class="service-card-icon flex-shrink-0" aria-hidden="true"><i class="bi bi-tools"></i></span>

                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <h2 class="h6 mb-0">{{ $booking->service->name }}</h2>
                                        <x-status-badge :status="$booking->status" />
                                    </div>
                                    <p class="small mb-0">
                                        <i class="bi bi-car-front me-1"></i>{{ $booking->vehicle_model }}@if ($booking->vehicle_year) ({{ $booking->vehicle_year }})@endif,
                                        <span class="fw-semibold">{{ $booking->plate_number }}</span>@if ($booking->mileage !== null),
                                            {{ number_format($booking->mileage, 0, ',', '.') }} km
                                        @endif
                                    </p>
                                    <p class="small mb-0">
                                        <i class="bi bi-calendar-event me-1"></i>{{ $booking->preferred_date->translatedFormat('l, d M Y') }}
                                        <i class="bi bi-clock ms-2 me-1"></i>{{ $booking->timeLabel() }} WIB
                                    </p>
                                </div>
                            </div>

                            @if ($booking->admin_note)
                                <div class="admin-note-box small rounded-3 p-2 mt-3">
                                    <span class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Catatan bengkel:</span>
                                    {{ $booking->admin_note }}
                                </div>
                            @endif

                            <details class="account-item-details">
                                <summary>Lihat rincian</summary>
                                <div class="small pt-2">
                                    @if ($booking->complaint)
                                        <p class="text-muted mb-2"><span class="fw-semibold">Keluhan Anda:</span> {{ $booking->complaint }}</p>
                                    @endif
                                    <p class="text-muted mb-0">Dibuat {{ $booking->created_at->translatedFormat('d M Y H:i') }}</p>
                                </div>
                            </details>

                            @if ($booking->canBeCancelledByCustomer())
                                <div class="account-item-actions">
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
    </x-account.layout>
@endsection
