@extends('layouts.app')

@section('title', 'Pengajuan Saya')

@php
    $percent = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%';
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Pengajuan Saya']]])

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h3 mb-0">Pengajuan Saya</h1>
            <a href="{{ route('cars.index') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-car-front"></i>Lihat Mobil
            </a>
        </div>

        @if ($purchaseRequests->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-card-checklist" title="Belum ada pengajuan"
                               message="Pilih mobil yang Anda minati, lalu tekan Ajukan Pembelian.">
                    <a href="{{ route('cars.index') }}" class="btn btn-primary btn-sm">Lihat Mobil</a>
                </x-empty-state>
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($purchaseRequests as $purchase)
                    @php($car = $purchase->car)
                    @php($carTitle = "{$car->brand->name} {$car->name} {$car->year}")
                    <article class="card test-drive-item" id="pengajuan-{{ $purchase->id }}">
                        <div class="card-body p-3 d-flex flex-column flex-md-row gap-3">
                            @if ($car->primaryImage)
                                <img src="{{ $car->primaryImage->url }}" alt="{{ $carTitle }}" class="test-drive-car-thumb" loading="lazy">
                            @else
                                <span class="test-drive-car-thumb car-card-img-empty" aria-hidden="true"><i class="bi bi-car-front"></i></span>
                            @endif

                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h2 class="h6 mb-0">
                                        @if ($car->is_active)
                                            <a href="{{ route('cars.show', $car) }}" class="text-reset">{{ $carTitle }}</a>
                                        @else
                                            {{ $carTitle }}
                                        @endif
                                    </h2>
                                    <x-status-badge :status="$purchase->status" />
                                </div>

                                <p class="small mb-2">
                                    <span class="fw-semibold">{{ $purchase->paymentMethodLabel() }}</span>
                                    · harga <x-price :amount="$purchase->car_price" />
                                    <span class="text-muted ms-2">· diajukan {{ $purchase->created_at->translatedFormat('d M Y H:i') }}</span>
                                </p>

                                @if ($purchase->isCredit())
                                    <p class="small mb-2">
                                        DP <x-price :amount="$purchase->down_payment" />
                                        · {{ $purchase->tenor_months }} bulan (bunga {{ $percent((float) $purchase->interest_rate) }}/tahun)
                                        · cicilan <span class="fw-semibold"><x-price :amount="$purchase->monthly_installment" />/bulan</span>
                                        · total <x-price :amount="$purchase->totalPayment()" />
                                    </p>
                                @endif

                                <p class="small text-muted mb-2"><span class="fw-semibold">Alamat:</span> {{ $purchase->address }}</p>

                                @if ($purchase->notes)
                                    <p class="small text-muted mb-2"><span class="fw-semibold">Catatan Anda:</span> {{ $purchase->notes }}</p>
                                @endif

                                @if ($purchase->admin_note)
                                    <div class="admin-note-box small rounded-3 p-2 mb-2">
                                        <span class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Catatan dealer:</span>
                                        {{ $purchase->admin_note }}
                                    </div>
                                @endif
                            </div>

                            @if ($purchase->canBeCancelledByCustomer())
                                <div class="flex-shrink-0">
                                    <form method="POST" action="{{ route('account.purchase-requests.cancel', $purchase) }}"
                                          data-confirm="Batalkan pengajuan {{ $carTitle }}?"
                                          data-disable-on-submit>
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-x-circle"></i>Batalkan
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($purchaseRequests->hasPages())
                <div class="mt-4">
                    {{ $purchaseRequests->onEachSide(1)->links('partials.pagination-links') }}
                </div>
            @endif
        @endif
    </div>
@endsection
