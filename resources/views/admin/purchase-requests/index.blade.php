@extends('layouts.admin')

@section('title', 'Pengajuan')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Pengajuan']]])
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.purchase-requests.index') }}" class="row g-2 align-items-end" role="search">
                <div class="col-12 col-md-6 col-xl-4">
                    <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
                    <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Nama/email customer, mobil…">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_status" class="form-label small mb-1">Status</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\PurchaseRequest::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_metode" class="form-label small mb-1">Metode</label>
                    <select id="filter_metode" name="metode" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="cash" @selected($filters['metode'] === 'cash')>Cash</option>
                        <option value="kredit" @selected($filters['metode'] === 'kredit')>Kredit</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_urut" class="form-label small mb-1">Urutkan</label>
                    <select id="filter_urut" name="urut" class="form-select form-select-sm">
                        <option value="terbaru" @selected($filters['urut'] === 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected($filters['urut'] === 'terlama')>Terlama</option>
                    </select>
                </div>
                <div class="col-12 col-xl-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i>Terapkan</button>
                    @if ($hasFilters || $filters['urut'] !== 'terbaru')
                        <a href="{{ route('admin.purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

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
                            <th>Metode</th>
                            <th class="text-end">Harga</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseRequests as $purchase)
                            <tr>
                                <td class="text-nowrap small">{{ $purchase->created_at->translatedFormat('d M Y') }}</td>
                                <td>
                                    <div>{{ $purchase->user->name }}</div>
                                    <div class="small text-muted">{{ $purchase->user->email }}</div>
                                </td>
                                <td>{{ $purchase->car->brand->name }} {{ $purchase->car->name }} {{ $purchase->car->year }}</td>
                                <td>
                                    {{ $purchase->paymentMethodLabel() }}
                                    @if ($purchase->isCredit())
                                        <div class="small text-muted">{{ $purchase->tenor_months }} bln</div>
                                    @endif
                                </td>
                                <td class="text-end"><x-price :amount="$purchase->car_price" /></td>
                                <td><x-status-badge :status="$purchase->status" /></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.purchase-requests.show', $purchase) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>Detail
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
