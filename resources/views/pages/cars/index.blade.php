@extends('layouts.app')

@section('title', $filters->title())
@section('meta_description', $filters->title().' di '.config('dealer.name').': cari berdasarkan merek, kategori, harga, tahun, dan promo.')

@php
    $chips = $filters->activeChips();
    $sortQuery = \Illuminate\Support\Arr::except($filters->query(), 'urut');
@endphp

@section('content')
    <div class="container py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h1 class="h3 mb-1">{{ $filters->title() }}</h1>
                <p class="text-muted small mb-0" role="status">
                    <span class="fw-semibold">{{ number_format($cars->total(), 0, ',', '.') }}</span> mobil ditemukan
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-primary btn-sm d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" aria-controls="filterOffcanvas">
                    <i class="bi bi-funnel"></i>Filter
                </button>

                {{-- Pilihan urutan: otomatis terkirim lewat public/js/app.js; tombol Urutkan untuk tanpa JS. --}}
                <form method="GET" action="{{ route('cars.index') }}" class="d-flex align-items-center gap-2">
                    @foreach ($sortQuery as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <label for="sort_urut" class="small text-muted text-nowrap mb-0">Urutkan</label>
                    <select id="sort_urut" name="urut" class="form-select form-select-sm" data-auto-submit>
                        @foreach (\App\Catalog\CarCatalogFilters::SORTS as $value => [$label])
                            <option value="{{ $value }}" @selected($filters->sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Urutkan</button>
                </form>
            </div>
        </div>

        @if ($chips !== [])
            <div class="d-flex flex-wrap align-items-center gap-2 mb-4" aria-label="Filter aktif">
                @foreach ($chips as $chip)
                    <a href="{{ $chip['url'] }}" class="filter-chip">
                        {{ $chip['label'] }} <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">(hapus filter)</span>
                    </a>
                @endforeach
                <a href="{{ route('cars.index') }}" class="small fw-semibold ms-1">Reset</a>
            </div>
        @endif

        <div class="row g-4">
            <aside class="col-lg-3 d-none d-lg-block" aria-label="Filter">
                <div class="card filter-sidebar">
                    <div class="card-body p-3">
                        <x-filter-sidebar :filters="$filters" :brands="$brands" :categories="$categories" :colors="$colors" id-prefix="d_" />
                    </div>
                </div>
            </aside>

            <div class="col-lg-9">
                <h2 class="visually-hidden">Hasil pencarian</h2>

                @if ($cars->isEmpty())
                    <div class="card">
                        <x-empty-state icon="bi-search" title="Mobil tidak ditemukan"
                                       message="Coba ubah kata kunci atau kurangi filter.">
                            @if ($filters->hasAny() || $cars->currentPage() > 1)
                                <a href="{{ route('cars.index') }}" class="btn btn-primary btn-sm">Reset filter</a>
                            @endif
                        </x-empty-state>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($cars as $car)
                            <div class="col-12 col-sm-6 col-lg-4">
                                <x-car-card :car="$car" />
                            </div>
                        @endforeach
                    </div>

                    @if ($cars->hasPages())
                        <div class="mt-4">
                            {{ $cars->onEachSide(1)->links('partials.pagination-links') }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h2 class="offcanvas-title h5" id="filterOffcanvasLabel"><i class="bi bi-funnel"></i> Filter</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <x-filter-sidebar :filters="$filters" :brands="$brands" :categories="$categories" :colors="$colors" id-prefix="m_" />
        </div>
    </div>
@endsection
