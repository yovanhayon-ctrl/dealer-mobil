@extends('layouts.admin')

@section('title', 'Pengajuan')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Pengajuan']]])
@endsection

@php
    $activeFilters = collect($filters)->except('urut')->filter(fn ($value) => $value !== null && $value !== '')->count();
@endphp

@section('content')
    <x-admin.list-header :summary="number_format($purchaseRequests->total(), 0, ',', '.').' pengajuan'.($hasFilters ? ' ditemukan' : '')"></x-admin.list-header>

    <x-admin.filters :action="route('admin.purchase-requests.index')" :reset-url="route('admin.purchase-requests.index')"
                     :active="$activeFilters" :show-reset="$hasFilters || $filters['urut'] !== 'terbaru'">
        <div class="col-12 col-lg">
            <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
            <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Nama/email customer, mobil…">
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_status" class="form-label small mb-1">Status</label>
            <select id="filter_status" name="status" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach (\App\Models\PurchaseRequest::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_metode" class="form-label small mb-1">Metode</label>
            <select id="filter_metode" name="metode" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="cash" @selected($filters['metode'] === 'cash')>Cash</option>
                <option value="kredit" @selected($filters['metode'] === 'kredit')>Kredit</option>
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_urut" class="form-label small mb-1">Urutkan</label>
            <select id="filter_urut" name="urut" class="form-select form-select-sm">
                <option value="terbaru" @selected($filters['urut'] === 'terbaru')>Terbaru</option>
                <option value="terlama" @selected($filters['urut'] === 'terlama')>Terlama</option>
            </select>
        </div>
    </x-admin.filters>

    <div class="card">
        @if ($purchaseRequests->isEmpty())
            @if ($hasFilters)
                <x-empty-state icon="bi-search" title="Pengajuan tidak ditemukan" message="Tidak ada pengajuan yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-file-earmark-text" title="Belum ada pengajuan" message="Pengajuan pembelian dari customer akan muncul di sini." />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Mobil</th>
                            <th class="text-end">Pembayaran</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseRequests as $purchase)
                            @php($detailUrl = route('admin.purchase-requests.show', $purchase))
                            <tr data-row-link="{{ $detailUrl }}">
                                <td class="text-nowrap">
                                    <a href="{{ $detailUrl }}" class="text-reset text-decoration-none fw-medium">{{ $purchase->created_at->translatedFormat('d M Y') }}</a>
                                </td>
                                <td>
                                    <div class="text-nowrap">{{ $purchase->user->name }}</div>
                                    <div class="small text-muted admin-truncate">{{ $purchase->user->email }}</div>
                                </td>
                                <td><span class="admin-truncate" title="{{ $purchase->car->brand->name }} {{ $purchase->car->name }} {{ $purchase->car->year }}">{{ $purchase->car->brand->name }} {{ $purchase->car->name }} {{ $purchase->car->year }}</span></td>
                                <td class="text-end text-nowrap">
                                    <div class="small text-muted">
                                        {{ $purchase->paymentMethodLabel() }}@if ($purchase->isCredit()) · {{ $purchase->tenor_months }} bln @endif
                                    </div>
                                    <x-price :amount="$purchase->car_price" />
                                </td>
                                <td><x-status-badge :status="$purchase->status" /></td>
                                <td class="text-end">
                                    <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary btn-icon" title="Detail" aria-label="Detail pengajuan {{ $purchase->user->name }}">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $purchaseRequests])
        @endif
    </div>
@endsection
