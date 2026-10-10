@extends('layouts.admin')

@section('title', 'Detail Pengguna')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Pengguna', 'url' => route('admin.users.index')],
        ['label' => $user->name],
    ]])
@endsection

@php
    // Halaman kelola test drive & pengajuan dibuat di tahap berikutnya; tombol muncul otomatis bila route-nya ada.
    $testDriveDetailRoute = Route::has('admin.test-drives.show');
    $purchaseDetailRoute = Route::has('admin.purchase-requests.show');
    $carLabel = fn ($car) => "{$car->brand->name} {$car->name} {$car->year}";
@endphp

@section('content')
    <x-admin.detail-header :title="$user->name" :back-url="route('admin.users.index')" back-label="Pengguna">
        @include('admin.users._role-badge')
    </x-admin.detail-header>

    <div class="row g-4">
        <div class="col-xxl-4">
            <div class="card admin-sticky-panel">
                <div class="card-body p-4">
                    <h3 class="admin-card-title">Profil</h3>
                    <dl class="admin-detail-list admin-detail-list-stacked">
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></dd>

                        <dt>Nomor HP</dt>
                        <dd>
                            @if ($user->phone)
                                <a href="tel:{{ $user->phone }}">{{ $user->phone }}</a>
                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $user->phone)) }}" target="_blank" rel="noopener"
                                   class="btn btn-sm btn-outline-success py-0 ms-2"><i class="bi bi-whatsapp"></i>WhatsApp</a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt>Terdaftar</dt>
                        <dd>{{ $user->created_at->translatedFormat('d F Y') }}</dd>

                        <dt>Email terverifikasi</dt>
                        <dd>{{ $user->email_verified_at ? 'Ya' : 'Belum' }}</dd>
                    </dl>

                    <div class="admin-mini-stats">
                        <div>Test drive <span>{{ $user->testDrives->count() }}</span></div>
                        <div>Pengajuan <span>{{ $user->purchaseRequests->count() }}</span></div>
                        <div>Booking servis <span>{{ $user->serviceBookings->count() }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-8">
            <div class="card mb-4">
                <div class="card-header dashboard-card-header">
                    <h3 class="h6 mb-0">Riwayat Test Drive</h3>
                </div>
                @if ($user->testDrives->isEmpty())
                    <x-empty-state icon="bi-calendar-x" title="Belum ada test drive" message="Pengguna ini belum pernah memesan test drive." />
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 admin-table">
                            <thead>
                                <tr>
                                    <th>Mobil</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                    @if ($testDriveDetailRoute)
                                        <th class="text-end">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->testDrives as $testDrive)
                                    @php($detailUrl = $testDriveDetailRoute ? route('admin.test-drives.show', $testDrive) : null)
                                    <tr @if ($detailUrl) data-row-link="{{ $detailUrl }}" @endif>
                                        <td><span class="admin-truncate" title="{{ $carLabel($testDrive->car) }}">{{ $carLabel($testDrive->car) }}</span></td>
                                        <td class="text-nowrap">
                                            {{ $testDrive->preferred_date->translatedFormat('d M Y') }}
                                            <div class="small text-muted">{{ substr($testDrive->preferred_time, 0, 5) }} WIB</div>
                                        </td>
                                        <td><x-status-badge :status="$testDrive->status" /></td>
                                        @if ($testDriveDetailRoute)
                                            <td class="text-end">
                                                <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary btn-icon" title="Detail" aria-label="Detail test drive {{ $carLabel($testDrive->car) }}"><i class="bi bi-chevron-right"></i></a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card mb-4">
                <div class="card-header dashboard-card-header">
                    <h3 class="h6 mb-0">Riwayat Pengajuan</h3>
                </div>
                @if ($user->purchaseRequests->isEmpty())
                    <x-empty-state icon="bi-file-earmark-text" title="Belum ada pengajuan" message="Pengguna ini belum pernah mengajukan pembelian." />
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 admin-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Mobil</th>
                                    <th class="text-end">Pembayaran</th>
                                    <th>Status</th>
                                    @if ($purchaseDetailRoute)
                                        <th class="text-end">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->purchaseRequests as $purchase)
                                    @php($detailUrl = $purchaseDetailRoute ? route('admin.purchase-requests.show', $purchase) : null)
                                    <tr @if ($detailUrl) data-row-link="{{ $detailUrl }}" @endif>
                                        <td class="text-nowrap">{{ $purchase->created_at->translatedFormat('d M Y') }}</td>
                                        <td><span class="admin-truncate" title="{{ $carLabel($purchase->car) }}">{{ $carLabel($purchase->car) }}</span></td>
                                        <td class="text-end text-nowrap">
                                            <div class="small text-muted">{{ $purchase->isCredit() ? 'Kredit' : 'Cash' }}</div>
                                            <x-price :amount="$purchase->car_price" />
                                        </td>
                                        <td><x-status-badge :status="$purchase->status" /></td>
                                        @if ($purchaseDetailRoute)
                                            <td class="text-end">
                                                <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary btn-icon" title="Detail" aria-label="Detail pengajuan {{ $carLabel($purchase->car) }}"><i class="bi bi-chevron-right"></i></a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header dashboard-card-header">
                    <h3 class="h6 mb-0">Riwayat Servis</h3>
                </div>
                @if ($user->serviceBookings->isEmpty())
                    <x-empty-state icon="bi-wrench-adjustable" title="Belum ada booking servis" message="Pengguna ini belum pernah booking servis." />
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 admin-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Layanan</th>
                                    <th>Kendaraan</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->serviceBookings as $booking)
                                    @php($detailUrl = route('admin.service-bookings.show', $booking))
                                    <tr data-row-link="{{ $detailUrl }}">
                                        <td class="text-nowrap">
                                            {{ $booking->preferred_date->translatedFormat('d M Y') }}
                                            <div class="small text-muted">{{ $booking->timeLabel() }} WIB</div>
                                        </td>
                                        <td><span class="admin-truncate" title="{{ $booking->service->name }}">{{ $booking->service->name }}</span></td>
                                        <td>
                                            <span class="admin-truncate" title="{{ $booking->vehicle_model }}">{{ $booking->vehicle_model }}</span>
                                            <div class="small text-muted">{{ $booking->plate_number }}</div>
                                        </td>
                                        <td><x-status-badge :status="$booking->status" /></td>
                                        <td class="text-end">
                                            <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary btn-icon" title="Detail" aria-label="Detail booking servis {{ $booking->service->name }}"><i class="bi bi-chevron-right"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
