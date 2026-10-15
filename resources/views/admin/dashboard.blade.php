@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    // Setiap peran hanya melihat kartu, grafik, dan daftar yang sesuai hak aksesnya (User::PERMISSIONS).
    $panelUser = auth()->user();
    $routeOrNull = fn (string $name, array $parameters = []) => Route::has($name) ? route($name, $parameters) : null;
    $quickActions = array_filter([
        ['admin.cars.create', 'bi-plus-lg', 'Tambah Mobil', 'btn-accent', 'cars.manage'],
        ['admin.promos.create', 'bi-percent', 'Tambah Promo', 'btn-outline-primary', 'promos.manage'],
        ['admin.reports.index', 'bi-bar-chart', 'Laporan', 'btn-outline-primary', 'reports.view'],
    ], fn (array $action) => Route::has($action[0]) && $panelUser->can($action[4]));
    $attention = array_filter([
        [$stats['overdue_payments'], 'pengajuan lewat tenggat bayar', 'admin.purchase-requests.index', ['pembayaran' => 'lewat-tenggat'], 'purchases.status'],
        [$stats['overdue_test_drives'], 'test drive lewat jadwal', 'admin.test-drives.index', ['jadwal' => 'lewat'], 'test-drives.manage'],
        [$stats['overdue_service_bookings'], 'booking servis lewat jadwal', 'admin.service-bookings.index', ['jadwal' => 'lewat'], 'service-bookings.manage'],
        [$stats['pending_refunds'], 'pengajuan batal, dana belum dikembalikan', 'admin.purchase-requests.index', ['pembayaran' => 'dana-belum-dikembalikan'], 'payments.manage'],
    ], fn (array $item) => $item[0] > 0 && $panelUser->can($item[4]));
@endphp

@section('content')
    {{-- Sambutan + aksi cepat --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">Selamat datang, {{ auth()->user()->name }}</h2>
            <p class="text-muted mb-0">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        @if ($quickActions)
            <div class="d-flex flex-wrap gap-2">
                @foreach ($quickActions as [$route, $icon, $label, $style, $ability])
                    <a href="{{ route($route) }}" class="btn {{ $style }} btn-sm"><i class="bi {{ $icon }}"></i>{{ $label }}</a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Perlu ditindaklanjuti: hanya tampil bila ada --}}
    @if ($attention)
        <div class="dashboard-attention mb-4" role="status">
            <span class="dashboard-attention-title"><i class="bi bi-exclamation-triangle"></i>Perlu ditindaklanjuti</span>
            <ul class="dashboard-attention-list">
                @foreach ($attention as [$count, $label, $route, $parameters, $ability])
                    <li><a href="{{ $routeOrNull($route, $parameters) }}"><strong>{{ $count }}</strong> {{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Ringkasan --}}
    <section class="mb-4" aria-labelledby="summary-title">
        <h3 id="summary-title" class="dashboard-section-title">Ringkasan</h3>
        <div class="stat-grid">
            @can('cars.view')
                <x-stat-card label="Mobil Aktif" :value="$stats['active_cars']" icon="bi-car-front" color="navy"
                             :href="$routeOrNull('admin.cars.index', ['status' => 'aktif'])" />
                <x-stat-card label="Stok Habis" :value="$stats['out_of_stock_cars']" icon="bi-box-seam" color="dark"
                             :href="$routeOrNull('admin.cars.index', ['status' => 'aktif', 'stok' => 'habis'])" />
            @endcan
            @can('test-drives.manage')
                <x-stat-card label="Test Drive Pending" :value="$stats['pending_test_drives']" icon="bi-calendar-event" color="yellow" alert
                             :href="$routeOrNull('admin.test-drives.index', ['status' => 'pending'])" />
            @endcan
            @can('purchases.view')
                <x-stat-card label="Pengajuan Pending" :value="$stats['pending_purchases']" icon="bi-hourglass-split" color="yellow" alert
                             :href="$routeOrNull('admin.purchase-requests.index', ['status' => 'pending'])" />
            @endcan
            @can('service-bookings.manage')
                <x-stat-card label="Servis Pending" :value="$stats['pending_service_bookings']" icon="bi-wrench-adjustable" color="yellow" alert
                             :href="$routeOrNull('admin.service-bookings.index', ['status' => 'pending'])" />
            @endcan
            @can('users.view')
                <x-stat-card label="Customer" :value="$stats['customers']" icon="bi-people" color="green"
                             :href="$routeOrNull('admin.users.index', ['role' => 'customer'])" />
            @endcan
        </div>
    </section>

    @can('reports.view')
        @include('admin.partials.dashboard-trend', ['trend' => $trend])
    @endcan

    {{-- Aktivitas terbaru --}}
    <section aria-labelledby="activity-title">
        <h3 id="activity-title" class="dashboard-section-title">Aktivitas Terbaru</h3>

        @can('purchases.view')
            <div class="card mb-4">
                <div class="card-header dashboard-card-header">
                    <h4 class="h6 mb-0">Pengajuan Terbaru</h4>
                    @if ($url = $routeOrNull('admin.purchase-requests.index'))
                        <a href="{{ $url }}" class="small">Lihat semua</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($latestPurchases->isEmpty())
                        <x-empty-state icon="bi-file-earmark-text" title="Belum ada pengajuan" message="Pengajuan dari customer akan muncul di sini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 admin-table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Customer</th>
                                        <th>Mobil</th>
                                        <th>Metode</th>
                                        <th class="text-end">Harga</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($latestPurchases as $purchase)
                                        @php($carLabel = "{$purchase->car->brand->name} {$purchase->car->name}")
                                        @php($detailUrl = $routeOrNull('admin.purchase-requests.show', [$purchase]))
                                        <tr @if ($detailUrl) data-row-link="{{ $detailUrl }}" @endif>
                                            <td class="text-nowrap">
                                                @if ($detailUrl)
                                                    <a href="{{ $detailUrl }}" class="text-reset text-decoration-none fw-medium">{{ $purchase->created_at->translatedFormat('d M Y') }}</a>
                                                @else
                                                    {{ $purchase->created_at->translatedFormat('d M Y') }}
                                                @endif
                                            </td>
                                            <td class="text-nowrap">{{ $purchase->user->name }}</td>
                                            <td><span class="dashboard-truncate" title="{{ $carLabel }}">{{ $carLabel }}</span></td>
                                            <td>{{ $purchase->isCredit() ? 'Kredit' : 'Cash' }}</td>
                                            <td class="text-end text-nowrap">Rp {{ number_format($purchase->car_price, 0, ',', '.') }}</td>
                                            <td><x-status-badge :status="$purchase->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
        @endcan
        </div>

        <div class="row g-4">
            @can('test-drives.manage')
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header dashboard-card-header">
                            <h4 class="h6 mb-0">Test Drive Terdekat</h4>
                            @if ($url = $routeOrNull('admin.test-drives.index'))
                                <a href="{{ $url }}" class="small">Lihat semua</a>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            @if ($upcomingTestDrives->isEmpty())
                                <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal test drive" message="Jadwal test drive mendatang akan muncul di sini." />
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 admin-table dashboard-table dashboard-table-compact">
                                        <thead>
                                            <tr>
                                                <th class="col-schedule">Jadwal</th>
                                                <th>Customer &amp; Mobil</th>
                                                <th class="col-status">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($upcomingTestDrives as $testDrive)
                                                @php($carLabel = "{$testDrive->car->brand->name} {$testDrive->car->name}")
                                                @php($detailUrl = $routeOrNull('admin.test-drives.show', [$testDrive]))
                                                <tr @if ($detailUrl) data-row-link="{{ $detailUrl }}" @endif>
                                                    <td class="text-nowrap">
                                                        @if ($detailUrl)
                                                            <a href="{{ $detailUrl }}" class="text-reset text-decoration-none fw-medium">{{ $testDrive->preferred_date->translatedFormat('d M Y') }}</a>
                                                        @else
                                                            {{ $testDrive->preferred_date->translatedFormat('d M Y') }}
                                                        @endif
                                                        <div class="small text-muted">{{ substr($testDrive->preferred_time, 0, 5) }} WIB</div>
                                                    </td>
                                                    <td>
                                                        <span class="dashboard-truncate">{{ $testDrive->user->name }}</span>
                                                        <span class="small text-muted dashboard-truncate" title="{{ $carLabel }}">{{ $carLabel }}</span>
                                                    </td>
                                                    <td><x-status-badge :status="$testDrive->status" /></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endcan

            @can('service-bookings.manage')
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header dashboard-card-header">
                            <h4 class="h6 mb-0">Booking Servis Terdekat</h4>
                            @if ($url = $routeOrNull('admin.service-bookings.index'))
                                <a href="{{ $url }}" class="small">Lihat semua</a>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            @if ($upcomingServiceBookings->isEmpty())
                                <x-empty-state icon="bi-wrench-adjustable" title="Belum ada jadwal servis" message="Booking servis mendatang akan muncul di sini." />
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 admin-table dashboard-table dashboard-table-compact">
                                        <thead>
                                            <tr>
                                                <th class="col-schedule">Jadwal</th>
                                                <th>Customer &amp; Servis</th>
                                                <th class="col-status">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($upcomingServiceBookings as $booking)
                                                @php($detailUrl = $routeOrNull('admin.service-bookings.show', [$booking]))
                                                <tr @if ($detailUrl) data-row-link="{{ $detailUrl }}" @endif>
                                                    <td class="text-nowrap">
                                                        @if ($detailUrl)
                                                            <a href="{{ $detailUrl }}" class="text-reset text-decoration-none fw-medium">{{ $booking->preferred_date->translatedFormat('d M Y') }}</a>
                                                        @else
                                                            {{ $booking->preferred_date->translatedFormat('d M Y') }}
                                                        @endif
                                                        <div class="small text-muted">{{ $booking->timeLabel() }} WIB</div>
                                                    </td>
                                                    <td>
                                                        <span class="dashboard-truncate">{{ $booking->user->name }}</span>
                                                        <span class="small text-muted dashboard-truncate" title="{{ $booking->vehicle_model }} · {{ $booking->plate_number }}">{{ $booking->vehicle_model }} · {{ $booking->plate_number }}</span>
                                                        <span class="small dashboard-truncate" title="{{ $booking->service->name }}"><i class="bi bi-wrench-adjustable me-1"></i>{{ $booking->service->name }}</span>
                                                    </td>
                                                    <td><x-status-badge :status="$booking->status" /></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </section>
@endsection
