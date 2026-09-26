@extends('layouts.admin')

@section('title', 'Detail Pengajuan')

@use('App\Models\PurchaseRequest')

@php
    $car = $purchaseRequest->car;
    $carLabel = "{$car->brand->name} {$car->name} {$car->year}";
    $awaitingDecision = in_array($purchaseRequest->status, [PurchaseRequest::STATUS_PENDING, PurchaseRequest::STATUS_PROCESSING], true);
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Pengajuan', 'url' => route('admin.purchase-requests.index')],
        ['label' => $purchaseRequest->user->name.' · '.$carLabel],
    ]])
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="h5 mb-0">Pengajuan #{{ $purchaseRequest->id }}</h2>
                        <x-status-badge :status="$purchaseRequest->status" />
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Tanggal pengajuan</dt>
                        <dd class="col-sm-8">{{ $purchaseRequest->created_at->translatedFormat('d F Y H:i') }} WIB</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Mobil</dt>
                        <dd class="col-sm-8">
                            {{ $carLabel }}
                            <span class="text-muted">· {{ $car->condition_label }} · stok {{ $car->stock }}</span>
                            @unless ($car->is_active)
                                <x-status-badge status="inactive" class="ms-1" />
                            @endunless
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Customer</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('admin.users.show', $purchaseRequest->user) }}">{{ $purchaseRequest->user->name }}</a>
                            <span class="text-muted">· {{ $purchaseRequest->user->email }}</span>
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Nomor HP</dt>
                        <dd class="col-sm-8">{{ $purchaseRequest->phone }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Alamat</dt>
                        <dd class="col-sm-8">{{ $purchaseRequest->address }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Catatan customer</dt>
                        <dd class="col-sm-8 mb-0">{{ $purchaseRequest->notes ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 mb-3">Rincian Harga · {{ $purchaseRequest->paymentMethodLabel() }}</h3>

                    <dl class="row small mb-0">
                        <dt class="col-7 text-muted fw-normal">Harga mobil (saat pengajuan)</dt>
                        <dd class="col-5 text-end"><x-price :amount="$purchaseRequest->car_price" /></dd>

                        @if ($purchaseRequest->isCredit())
                            <dt class="col-7 text-muted fw-normal">
                                Uang muka (DP {{ number_format($purchaseRequest->down_payment / $purchaseRequest->car_price * 100, 1, ',', '.') }}%)
                            </dt>
                            <dd class="col-5 text-end"><x-price :amount="$purchaseRequest->down_payment" /></dd>

                            <dt class="col-7 text-muted fw-normal">Pokok pinjaman</dt>
                            <dd class="col-5 text-end"><x-price :amount="$purchaseRequest->principal()" /></dd>

                            <dt class="col-7 text-muted fw-normal">Tenor</dt>
                            <dd class="col-5 text-end">{{ $purchaseRequest->tenor_months }} bulan</dd>

                            <dt class="col-7 text-muted fw-normal">Bunga flat</dt>
                            <dd class="col-5 text-end">{{ rtrim(rtrim(number_format((float) $purchaseRequest->interest_rate, 2, ',', '.'), '0'), ',') }}% / tahun</dd>

                            <dt class="col-7 text-muted fw-normal">Total bunga</dt>
                            <dd class="col-5 text-end"><x-price :amount="$purchaseRequest->interestTotal()" /></dd>

                            <dt class="col-7 fw-semibold">Cicilan per bulan</dt>
                            <dd class="col-5 text-end fw-semibold"><x-price :amount="$purchaseRequest->monthly_installment" /></dd>

                            <dt class="col-7 text-muted fw-normal border-top pt-2 mt-1 mb-0">Total pembayaran</dt>
                            <dd class="col-5 text-end border-top pt-2 mt-1 mb-0"><x-price :amount="$purchaseRequest->totalPayment()" /></dd>
                        @else
                            <dt class="col-7 fw-semibold border-top pt-2 mt-1 mb-0">Total pembayaran (cash)</dt>
                            <dd class="col-5 text-end fw-semibold border-top pt-2 mt-1 mb-0"><x-price :amount="$purchaseRequest->totalPayment()" /></dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if ($awaitingDecision && ! $car->inStock())
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Stok mobil ini 0. Pengajuan tidak bisa disetujui sampai stok tersedia.
                </div>
            @endif
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="h6 mb-3">Status & Catatan Admin</h3>
                    @include('admin.partials.status-form', [
                        'action' => route('admin.purchase-requests.update-status', $purchaseRequest),
                        'currentLabel' => $purchaseRequest->statusLabel(),
                        'allowed' => collect($purchaseRequest->allowedTransitions())
                            ->mapWithKeys(fn ($status) => [$status => PurchaseRequest::STATUS_LABELS[$status]])
                            ->all(),
                        'adminNote' => $purchaseRequest->admin_note,
                        'noteHelp' => 'Wajib diisi saat menolak atau membatalkan (alasan untuk customer). Maksimal 1000 karakter.',
                    ])

                    <div class="small text-muted mt-3">
                        <i class="bi bi-info-circle me-1"></i>Disetujui: stok −1 · Disetujui → Ditolak/Dibatalkan: stok +1 · Disetujui → Selesai: stok tetap.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
