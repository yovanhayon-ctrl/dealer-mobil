@extends('layouts.admin')

@section('title', 'Mobil')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Mobil']]])
@endsection

@php
    $activeFilters = collect($filters)->except('urut')->filter(fn ($value) => $value !== null && $value !== '')->count();
@endphp

@section('content')
    <x-admin.list-header :summary="number_format($cars->total(), 0, ',', '.').' mobil'.($hasFilters ? ' ditemukan' : '')">
        <a href="{{ route('admin.cars.create') }}" class="btn btn-accent btn-sm">
            <i class="bi bi-plus-lg"></i>Tambah Mobil
        </a>
    </x-admin.list-header>

    <x-admin.filters :action="route('admin.cars.index')" :reset-url="route('admin.cars.index')"
                     :active="$activeFilters" :show-reset="$hasFilters || $filters['urut'] !== 'terbaru'">
        <div class="col-12 col-lg">
            <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
            <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Nama mobil…">
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_merek" class="form-label small mb-1">Merek</label>
            <select id="filter_merek" name="merek" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected($filters['merek'] === $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_kategori" class="form-label small mb-1">Kategori</label>
            <select id="filter_kategori" name="kategori" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['kategori'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_kondisi" class="form-label small mb-1">Kondisi</label>
            <select id="filter_kondisi" name="kondisi" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach (\App\Models\Car::CONDITIONS as $value => $label)
                    <option value="{{ $value }}" @selected($filters['kondisi'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_status" class="form-label small mb-1">Status</label>
            <select id="filter_status" name="status" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_stok" class="form-label small mb-1">Stok</label>
            <select id="filter_stok" name="stok" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="habis" @selected($filters['stok'] === 'habis')>Habis</option>
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
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
    </x-admin.filters>

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
                            <th class="text-end">Harga</th>
                            <th class="text-center">Stok</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cars as $car)
                            @php($blockers = $car->deletionBlockers())
                            @php($carTitle = "{$car->name} {$car->year}")
                            <tr @class(['table-light text-muted' => ! $car->is_active])>
                                <td>
                                    <a href="{{ route('admin.cars.images.index', $car) }}" title="Kelola galeri">
                                        @if ($car->primaryImage)
                                            <img src="{{ $car->primaryImage->url }}" alt="{{ $carTitle }}" class="car-thumb" loading="lazy">
                                        @else
                                            <span class="car-thumb car-thumb-empty"><i class="bi bi-car-front"></i></span>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold d-flex flex-wrap align-items-center gap-1">
                                        <a href="{{ route('admin.cars.edit', $car) }}" class="text-reset text-decoration-none">{{ $car->name }}</a>
                                        @if ($car->is_featured)
                                            <span class="badge rounded-pill badge-status-dark" title="Unggulan di beranda"><i class="bi bi-star-fill me-1"></i>Unggulan</span>
                                        @endif
                                        @if ($car->hasPromoPrice())
                                            <span class="badge rounded-pill badge-status-red">Promo</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        {{ $car->brand->name }} · {{ $car->category->name }} · {{ $car->year }} · {{ $car->transmission_label }}
                                        <span @class([
                                            'badge ms-1',
                                            'bg-primary' => $car->isNew(),
                                            'text-bg-light border' => ! $car->isNew(),
                                        ])>{{ $car->condition_label }}</span>
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($car->hasPromoPrice())
                                        <del class="d-block small text-muted"><x-price :amount="$car->price" /></del>
                                        <x-price :amount="$car->finalPrice()" class="fw-semibold text-accent" />
                                    @else
                                        <x-price :amount="$car->price" />
                                    @endif
                                </td>
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
                                    <div class="admin-row-actions">
                                        <a href="{{ route('admin.cars.images.index', $car) }}" class="btn btn-sm btn-outline-secondary btn-icon"
                                           title="Galeri" aria-label="Galeri {{ $carTitle }}">
                                            <i class="bi bi-images"></i>
                                        </a>
                                        @include('admin.partials.row-actions', [
                                            'editUrl' => route('admin.cars.edit', $car),
                                            'deleteUrl' => route('admin.cars.destroy', $car),
                                            'name' => $carTitle,
                                            'disabledReason' => $blockers ? "Sudah memiliki {$blockers}" : null,
                                        ])
                                    </div>
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
