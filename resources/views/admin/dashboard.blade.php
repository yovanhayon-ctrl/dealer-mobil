@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $routeOrNull = fn (string $name) => Route::has($name) ? route($name) : null;
@endphp

@section('content')
    <div class="mb-4">
        <h2 class="h4 mb-1">Selamat datang, {{ auth()->user()->name }}</h2>
        <p class="text-muted mb-0">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-4">
        <div class="col">
            <x-stat-card label="Mobil Aktif" :value="$stats['active_cars']" icon="bi-car-front" color="navy" :href="$routeOrNull('admin.cars.index')" />
        </div>
        <div class="col">
            <x-stat-card label="Stok Habis" :value="$stats['out_of_stock_cars']" icon="bi-box-seam" color="dark" :href="$routeOrNull('admin.cars.index')" />
        </div>
        <div class="col">
            <x-stat-card label="Test Drive Pending" :value="$stats['pending_test_drives']" icon="bi-calendar-event" color="yellow" :href="$routeOrNull('admin.test-drives.index')" />
        </div>
        <div class="col">
            <x-stat-card label="Pengajuan Pending" :value="$stats['pending_purchases']" icon="bi-hourglass-split" color="yellow" :href="$routeOrNull('admin.purchase-requests.index')" />
        </div>
        <div class="col">
            <x-stat-card label="Customer" :value="$stats['customers']" icon="bi-people" color="green" :href="$routeOrNull('admin.users.index')" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-3 px-3">
                    <h3 class="h6 mb-0">Pengajuan Terbaru</h3>
                    @if ($url = $routeOrNull('admin.purchase-requests.index'))
                        <a href="{{ $url }}" class="small">Lihat semua</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($latestPurchases->isEmpty())
                        <x-empty-state icon="bi-file-earmark-text" title="Belum ada pengajuan" message="Pengajuan dari customer akan muncul di sini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 admin-table">
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
                                        <tr>
                                            <td class="text-nowrap">{{ $purchase->created_at->translatedFormat('d M Y') }}</td>
                                            <td>{{ $purchase->user->name }}</td>
                                            <td>
                                                <div class="small text-muted">{{ $purchase->car->brand->name }}</div>
                                                {{ $purchase->car->name }}
                                            </td>
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
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-3 px-3">
                    <h3 class="h6 mb-0">Test Drive Terdekat</h3>
                    @if ($url = $routeOrNull('admin.test-drives.index'))
                        <a href="{{ $url }}" class="small">Lihat semua</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($upcomingTestDrives->isEmpty())
                        <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal test drive" message="Jadwal test drive mendatang akan muncul di sini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th>Jadwal</th>
                                        <th>Customer</th>
                                        <th>Mobil</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($upcomingTestDrives as $testDrive)
                                        <tr>
                                            <td class="text-nowrap">
                                                {{ $testDrive->preferred_date->translatedFormat('d M Y') }}
                                                <div class="small text-muted">{{ substr($testDrive->preferred_time, 0, 5) }} WIB</div>
                                            </td>
                                            <td>{{ $testDrive->user->name }}</td>
                                            <td>
                                                <div class="small text-muted">{{ $testDrive->car->brand->name }}</div>
                                                {{ $testDrive->car->name }}
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
    </div>
@endsection
