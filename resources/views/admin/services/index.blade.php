@extends('layouts.admin')

@section('title', 'Layanan Servis')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Layanan Servis']]])
@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between mb-3">
        <form method="GET" action="{{ route('admin.services.index') }}" class="d-flex gap-2 admin-search" role="search">
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama layanan…" aria-label="Cari nama layanan">
            <select name="status" class="form-select w-auto" aria-label="Filter status">
                <option value="">Semua status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i><span class="d-none d-md-inline">Cari</span></button>
            @if ($hasFilters)
                <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.services.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg"></i>Tambah Layanan
        </a>
    </div>

    <div class="card">
        @if ($services->isEmpty())
            @if ($hasFilters)
                <x-empty-state icon="bi-search" title="Layanan tidak ditemukan" message="Tidak ada layanan yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-tools" title="Belum ada layanan" message="Tambahkan layanan seperti Servis Berkala atau Inspeksi Kendaraan.">
                    <a href="{{ route('admin.services.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i>Tambah Layanan</a>
                </x-empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px">No</th>
                            <th>Nama</th>
                            <th class="text-end">Harga Mulai</th>
                            <th class="text-center">Durasi</th>
                            <th class="text-center">Jumlah Booking</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td class="text-center text-muted">{{ $services->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $service->name }}</div>
                                    <div class="small text-muted">{{ $service->slug }}</div>
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($service->price_from)
                                        Rp {{ number_format($service->price_from, 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">Hubungi dealer</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">{{ $service->duration_minutes ? $service->duration_minutes.' menit' : '—' }}</td>
                                <td class="text-center">{{ $service->bookings_count }}</td>
                                <td><x-status-badge :status="$service->is_active ? 'active' : 'inactive'" /></td>
                                <td class="text-end text-nowrap">
                                    @include('admin.partials.row-actions', [
                                        'editUrl' => route('admin.services.edit', $service),
                                        'deleteUrl' => route('admin.services.destroy', $service),
                                        'name' => $service->name,
                                        'disabledReason' => $service->bookings_count > 0 ? "Sudah dipakai {$service->bookings_count} booking. Nonaktifkan saja." : null,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $services])
        @endif
    </div>

    <x-delete-modal entity="layanan" />
@endsection
