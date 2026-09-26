@extends('layouts.admin')

@section('title', 'Promo')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Promo']]])
@endsection

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.promos.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg"></i>Tambah Promo
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.promos.index') }}" class="row g-2 align-items-end" role="search">
                <div class="col-12 col-md-5">
                    <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
                    <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Judul promo…">
                </div>
                <div class="col-6 col-md-3">
                    <label for="filter_status" class="form-label small mb-1">Status</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\Promo::STATUS_FILTERS as $value => [, $label])
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter_jenis" class="form-label small mb-1">Jenis</label>
                    <select id="filter_jenis" name="jenis" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="mobil" @selected($filters['jenis'] === 'mobil')>Khusus mobil</option>
                        <option value="umum" @selected($filters['jenis'] === 'umum')>Umum</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i>Terapkan</button>
                    @if ($hasFilters)
                        <a href="{{ route('admin.promos.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if ($promos->isEmpty())
            @if ($hasFilters)
                <x-empty-state icon="bi-search" title="Promo tidak ditemukan" message="Tidak ada promo yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-percent" title="Belum ada promo" message="Tambahkan promo untuk menarik minat pembeli.">
                    <a href="{{ route('admin.promos.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i>Tambah Promo</a>
                </x-empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th style="width: 112px">Banner</th>
                            <th>Judul</th>
                            <th>Mobil</th>
                            <th class="text-end">Diskon</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promos as $promo)
                            <tr @class(['text-muted' => $promo->status !== \App\Models\Promo::STATUS_RUNNING])>
                                <td>
                                    @if ($promo->image_url)
                                        <img src="{{ $promo->image_url }}" alt="{{ $promo->title }}" class="promo-thumb" loading="lazy">
                                    @else
                                        <span class="promo-thumb car-thumb-empty"><i class="bi bi-image"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $promo->title }}</div>
                                    <div class="small text-muted">{{ $promo->slug }}</div>
                                </td>
                                <td>
                                    @if ($promo->car)
                                        {{ $promo->car->brand->name }} {{ $promo->car->name }} {{ $promo->car->year }}
                                    @else
                                        <span class="badge rounded-pill text-bg-light border">Promo Umum</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($promo->discount_amount)
                                        <x-price :amount="$promo->discount_amount" />
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap small">
                                    {{ $promo->start_date->translatedFormat('d M Y') }} – {{ $promo->end_date->translatedFormat('d M Y') }}
                                </td>
                                <td><x-status-badge :status="$promo->status" /></td>
                                <td class="text-end text-nowrap">
                                    @include('admin.partials.row-actions', [
                                        'editUrl' => route('admin.promos.edit', $promo),
                                        'deleteUrl' => route('admin.promos.destroy', $promo),
                                        'name' => $promo->title,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $promos])
        @endif
    </div>

    <x-delete-modal entity="promo" />
@endsection
