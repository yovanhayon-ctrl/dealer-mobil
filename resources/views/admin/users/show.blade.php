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
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="h5 mb-0">{{ $user->name }}</h2>
                        @include('admin.users._role-badge')
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Email</dt>
                        <dd class="col-7 text-break">{{ $user->email }}</dd>

                        <dt class="col-5 text-muted fw-normal">Nomor HP</dt>
                        <dd class="col-7">{{ $user->phone ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Terdaftar</dt>
                        <dd class="col-7">{{ $user->created_at->translatedFormat('d F Y') }}</dd>

                        <dt class="col-5 text-muted fw-normal">Email terverifikasi</dt>
                        <dd class="col-7">{{ $user->email_verified_at ? 'Ya' : 'Belum' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Test drive</dt>
                        <dd class="col-7">{{ $user->testDrives->count() }}</dd>

                        <dt class="col-5 text-muted fw-normal">Pengajuan</dt>
                        <dd class="col-7 mb-0">{{ $user->purchaseRequests->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header bg-transparent border-0 pt-3 px-3">
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
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                    @if ($testDriveDetailRoute)
                                        <th class="text-end">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->testDrives as $testDrive)
                                    <tr>
                                        <td>{{ $carLabel($testDrive->car) }}</td>
                                        <td class="text-nowrap">{{ $testDrive->preferred_date->translatedFormat('d M Y') }}</td>
                                        <td class="text-nowrap">{{ substr($testDrive->preferred_time, 0, 5) }} WIB</td>
                                        <td><x-status-badge :status="$testDrive->status" /></td>
                                        @if ($testDriveDetailRoute)
                                            <td class="text-end">
                                                <a href="{{ route('admin.test-drives.show', $testDrive) }}" class="btn btn-sm btn-outline-primary">Detail</a>
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
                <div class="card-header bg-transparent border-0 pt-3 px-3">
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
                                    <th>Metode</th>
                                    <th class="text-end">Harga</th>
                                    <th>Status</th>
                                    @if ($purchaseDetailRoute)
                                        <th class="text-end">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->purchaseRequests as $purchase)
                                    <tr>
                                        <td class="text-nowrap">{{ $purchase->created_at->translatedFormat('d M Y') }}</td>
                                        <td>{{ $carLabel($purchase->car) }}</td>
                                        <td>{{ $purchase->isCredit() ? 'Kredit' : 'Cash' }}</td>
                                        <td class="text-end"><x-price :amount="$purchase->car_price" /></td>
                                        <td><x-status-badge :status="$purchase->status" /></td>
                                        @if ($purchaseDetailRoute)
                                            <td class="text-end">
                                                <a href="{{ route('admin.purchase-requests.show', $purchase) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                            </td>
                                        @endif
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
