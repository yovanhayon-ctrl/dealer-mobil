@extends('layouts.admin')

@section('title', 'Booking Servis')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Booking Servis']]])
@endsection

@php
    $activeFilters = collect($filters)->except('urut')->filter(fn ($value) => $value !== null && $value !== '')->count();
@endphp

@section('content')
    <x-admin.list-header :summary="number_format($bookings->total(), 0, ',', '.').' booking servis'.($hasFilters ? ' ditemukan' : '')"></x-admin.list-header>

    <x-admin.filters :action="route('admin.service-bookings.index')" :reset-url="route('admin.service-bookings.index')"
                     :active="$activeFilters" :show-reset="$hasFilters || $filters['urut'] !== 'terbaru'">
        <div class="col-12 col-lg">
            <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
            <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Customer, plat nomor, kendaraan…">
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_layanan" class="form-label small mb-1">Layanan</label>
            <select id="filter_layanan" name="layanan" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach ($serviceOptions as $id => $name)
                    <option value="{{ $id }}" @selected($filters['layanan'] === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_status" class="form-label small mb-1">Status</label>
            <select id="filter_status" name="status" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach (\App\Models\ServiceBooking::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_dari" class="form-label small mb-1">Jadwal dari</label>
            <input type="date" id="filter_dari" name="dari" value="{{ $filters['dari'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_sampai" class="form-label small mb-1">Jadwal sampai</label>
            <input type="date" id="filter_sampai" name="sampai" value="{{ $filters['sampai'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_urut" class="form-label small mb-1">Urutkan</label>
            <select id="filter_urut" name="urut" class="form-select form-select-sm">
                <option value="terbaru" @selected($filters['urut'] === 'terbaru')>Terbaru masuk</option>
                <option value="jadwal" @selected($filters['urut'] === 'jadwal')>Jadwal terdekat</option>
            </select>
        </div>
    </x-admin.filters>

    <div class="card">
        @if ($bookings->isEmpty())
            @if ($hasFilters)
                <x-empty-state icon="bi-search" title="Booking servis tidak ditemukan" message="Tidak ada booking servis yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-wrench-adjustable" title="Belum ada booking servis" message="Booking servis dari customer akan muncul di sini." />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Jadwal</th>
                            <th>Customer</th>
                            <th>Kendaraan</th>
                            <th>Layanan</th>
                            <th>Status</th>
                            <th class="d-none d-xxl-table-cell">Masuk</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            @php($detailUrl = route('admin.service-bookings.show', $booking))
                            <tr data-row-link="{{ $detailUrl }}">
                                <td class="text-nowrap">
                                    <a href="{{ $detailUrl }}" class="text-reset text-decoration-none fw-semibold">{{ $booking->preferred_date->translatedFormat('d M Y') }}</a>
                                    <div class="small text-muted">{{ $booking->timeLabel() }} WIB</div>
                                </td>
                                <td>
                                    <div class="text-nowrap">{{ $booking->user->name }}</div>
                                    <div class="small text-muted admin-truncate">{{ $booking->user->email }}</div>
                                </td>
                                <td>
                                    <span class="admin-truncate" title="{{ $booking->vehicle_model }}">{{ $booking->vehicle_model }}</span>
                                    <div class="small text-muted text-nowrap">{{ $booking->plate_number }}</div>
                                </td>
                                <td><span class="admin-truncate" title="{{ $booking->service->name }}">{{ $booking->service->name }}</span></td>
                                <td><x-status-badge :status="$booking->status" /></td>
                                <td class="text-nowrap small d-none d-xxl-table-cell">{{ $booking->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary btn-icon" title="Detail" aria-label="Detail booking servis {{ $booking->user->name }}">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $bookings])
        @endif
    </div>
@endsection
