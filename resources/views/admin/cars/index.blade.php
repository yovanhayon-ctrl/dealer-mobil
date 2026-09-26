@extends('layouts.admin')

@section('title', 'Mobil')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Mobil']]])
@endsection

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.cars.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg"></i>Tambah Mobil
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.cars.index') }}" class="row g-2 align-items-end" role="search">
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
                    <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Nama mobil…">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_merek" class="form-label small mb-1">Merek</label>
                    <select id="filter_merek" name="merek" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected($filters['merek'] === $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_kategori" class="form-label small mb-1">Kategori</label>
                    <select id="filter_kategori" name="kategori" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['kategori'] === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <label for="filter_kondisi" class="form-label small mb-1">Kondisi</label>
                    <select id="filter_kondisi" name="kondisi" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\Car::CONDITIONS as $value => $label)
                            <option value="{{ $value }}" @selected($filters['kondisi'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <label for="filter_status" class="form-label small mb-1">Status</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_urut" class="form-label small mb-1">Urutkan</label>
                    <select id="filter_urut" name="urut" class="form-select form-select-sm">
                        @foreach ([
                            'terbaru' => 'Terbaru',
                            'harga_termurah' => 'Harga termurah',
                            'harga_termahal' => 'Harga termahal',
                            'tahun_terbaru' => 'Tahun terbaru',
                            'tahun_terlama' => 'Tahun terlama',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['urut'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filter_stok" name="stok" value="habis" @checked($filters['stok'] === 'habis')>
                        <label class="form-check-label small" for="filter_stok">Stok habis</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i>Terapkan</button>
                    @if ($hasFilters || $filters['urut'] !== 'terbaru')
                        <a href="{{ route('admin.cars.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if ($cars->isEmpty())
            @if ($hasFilters)
                <x-empty-state icon="bi-search" title="Mobil tidak ditemukan" message="Tidak ada mobil yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-car-front" title="Belum ada mobil" message="Tambahkan mobil pertama agar tampil di katalog.">
                    <a href="{{ route('admin.cars.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i>Tambah Mobil</a>
                </x-empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th style="width: 88px">Gambar</th>
                            <th>Mobil</th>
                            <th>Merek</th>
                            <th>Kategori</th>
                            <th>Kondisi</th>
                            <th class="text-end">Harga</th>
                            <th class="text-center">Stok</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cars as $car)
                            @php($blockers = $car->deletionBlockers())
                            <tr @class(['table-light text-muted' => ! $car->is_active])>
                                <td>
                                    <a href="{{ route('admin.cars.images.index', $car) }}" title="Kelola galeri">
                                        @if ($car->primaryImage)
                                            <img src="{{ $car->primaryImage->url }}" alt="{{ $car->name }} {{ $car->year }}" class="car-thumb" loading="lazy">
                                        @else
                                            <span class="car-thumb car-thumb-empty"><i class="bi bi-car-front"></i></span>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $car->name }}</div>
                                    <div class="small text-muted">{{ $car->year }} · {{ $car->transmission_label }}</div>
                                </td>
                                <td>{{ $car->brand->name }}</td>
                                <td>{{ $car->category->name }}</td>
                                <td>
                                    <span @class([
                                        'badge rounded-pill',
                                        'bg-primary' => $car->isNew(),
                                        'text-bg-light border' => ! $car->isNew(),
                                    ])>{{ $car->condition_label }}</span>
                                </td>
                                <td class="text-end"><x-price :amount="$car->price" /></td>
                                <td class="text-center">
                                    @if ($car->inStock())
                                        {{ $car->stock }}
                                    @else
                                        <x-status-badge status="out_of_stock" />
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <x-status-badge :status="$car->is_active ? 'active' : 'inactive'" class="me-1" />
                                    <form method="POST" action="{{ route('admin.cars.toggle-active', $car) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">
                                            {{ $car->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.cars.images.index', $car) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-images"></i>Galeri
                                    </a>
                                    @include('admin.partials.row-actions', [
                                        'editUrl' => route('admin.cars.edit', $car),
                                        'deleteUrl' => route('admin.cars.destroy', $car),
                                        'name' => $car->name.' '.$car->year,
                                        'disabledReason' => $blockers ? "Sudah memiliki {$blockers}" : null,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $cars])
        @endif
    </div>

    <x-delete-modal entity="mobil" />
@endsection
