@extends('layouts.app')

@section('title', 'Pengajuan Saya')

@php
    $percent = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%';
@endphp

@section('content')
    <x-account.layout title="Pengajuan Saya" active="account.purchase-requests.*"
                      :summary="$statusCounts['semua'].' pengajuan pembelian'">
        <x-slot:actions>
            <a href="{{ route('cars.index') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-plus-lg"></i>Ajukan Pembelian
            </a>
        </x-slot:actions>

        @if ($statusCounts['semua'] > 0)
            <x-slot:filters>
                <x-account.status-filter route="account.purchase-requests.index" :counts="$statusCounts" :active="$statusGroup" />
            </x-slot:filters>
        @endif

        @if ($purchaseRequests->isEmpty())
            <div class="card">
                @if ($statusGroup)
                    <x-empty-state icon="bi-funnel" title="Tidak ada pengajuan dengan status ini"
                                   message="Pilih status lain atau tampilkan semua pengajuan.">
                        <a href="{{ route('account.purchase-requests.index') }}" class="btn btn-primary btn-sm">Tampilkan Semua</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="bi-card-checklist" title="Belum ada pengajuan"
                                   message="Pilih mobil yang Anda minati, lalu tekan Ajukan Pembelian.">
                        <a href="{{ route('cars.index') }}" class="btn btn-primary btn-sm">Lihat Mobil</a>
                    </x-empty-state>
                @endif
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($purchaseRequests as $purchase)
                    @php($car = $purchase->car)
                    @php($carTitle = "{$car->brand->name} {$car->name} {$car->year}")
                    <article class="card test-drive-item account-item" id="pengajuan-{{ $purchase->id }}">
                        <div class="card-body p-3">
                            <div class="account-item-head">
                                @if ($car->primaryImage)
                                    <img src="{{ $car->primaryImage->url }}" alt="{{ $carTitle }}" class="test-drive-car-thumb" loading="lazy">
                                @else
                                    <span class="test-drive-car-thumb car-card-img-empty" aria-hidden="true"><i class="bi bi-car-front"></i></span>
                                @endif

                                <div class="min-w-0 flex-grow-1">
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
                                    <p class="small mb-0">
                                        <span class="fw-semibold">{{ $purchase->paymentMethodLabel() }}</span>
                                        · harga <x-price :amount="$purchase->car_price" />
                                        @if ($purchase->isCredit())
                                            · cicilan <span class="fw-semibold"><x-price :amount="$purchase->monthly_installment" />/bulan</span>
                                        @endif
                                    </p>
                                    <p class="small text-muted mb-0">Diajukan {{ $purchase->created_at->translatedFormat('d M Y H:i') }}</p>
                                </div>
                            </div>

                            @if ($purchase->admin_note)
                                <div class="admin-note-box small rounded-3 p-2 mt-3">
                                    <span class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Catatan dealer:</span>
                                    {{ $purchase->admin_note }}
                                </div>
                            @endif

                            <details class="account-item-details">
                                <summary>Lihat rincian</summary>
                                <div class="small pt-2">
                                    @if ($purchase->isCredit())
                                        <p class="mb-2">
                                            DP <x-price :amount="$purchase->down_payment" />
                                            · {{ $purchase->tenor_months }} bulan (bunga {{ $percent((float) $purchase->interest_rate) }}/tahun)
                                            · cicilan <span class="fw-semibold"><x-price :amount="$purchase->monthly_installment" />/bulan</span>
                                            · total <x-price :amount="$purchase->totalPayment()" />
                                        </p>
                                    @endif
                                    <p class="text-muted mb-2"><span class="fw-semibold">Alamat:</span> {{ $purchase->address }}</p>
                                    @if ($purchase->notes)
                                        <p class="text-muted mb-0"><span class="fw-semibold">Catatan Anda:</span> {{ $purchase->notes }}</p>
                                    @endif
                                </div>
                            </details>

                            {{-- Ulasan: hanya untuk pembelian yang sudah selesai. --}}
                            @if ($purchase->canBeReviewed() && Route::has('account.testimonials.edit'))
                                @php($review = $purchase->testimonial)
                                <div class="testimonial-box small rounded-3 p-2 mt-3">
                                    @if (! $review)
                                        <span class="me-2"><i class="bi bi-star me-1"></i>Bagaimana pengalaman Anda membeli mobil ini?</span>
                                        <a href="{{ route('account.testimonials.edit', $purchase) }}" class="btn btn-accent btn-sm mt-1 mt-sm-0">
                                            <i class="bi bi-pencil-square"></i>Beri Ulasan
                                        </a>
                                    @else
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="fw-semibold">Ulasan Anda</span>
                                            <x-rating-stars :rating="$review->rating" />
                                            <x-status-badge :status="$review->status" />
                                        </div>
                                        <p class="mb-1 text-break">{{ $review->comment }}</p>
                                        @if ($review->status === \App\Models\Testimonial::STATUS_PENDING)
                                            <p class="text-muted mb-1">Menunggu persetujuan admin sebelum tampil di beranda.</p>
                                        @elseif ($review->status === \App\Models\Testimonial::STATUS_REJECTED)
                                            <p class="text-danger mb-1"><span class="fw-semibold">Ditolak:</span> {{ $review->admin_note }}</p>
                                        @else
                                            <p class="text-muted mb-0">Tampil di beranda. Terima kasih atas ulasan Anda!</p>
                                        @endif
                                        @if ($review->canBeEditedByCustomer())
                                            <a href="{{ route('account.testimonials.edit', $purchase) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-pencil"></i>{{ $review->status === \App\Models\Testimonial::STATUS_REJECTED ? 'Perbaiki Ulasan' : 'Ubah Ulasan' }}
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            <div class="account-item-actions">
                                @if (Route::has('account.purchase-requests.pdf'))
                                    <a href="{{ route('account.purchase-requests.pdf', $purchase) }}" target="_blank" rel="noopener"
                                       class="btn btn-outline-secondary btn-sm" aria-label="Cetak PDF bukti pengajuan {{ $carTitle }}">
                                        <i class="bi bi-file-earmark-pdf"></i>Cetak PDF
                                    </a>
                                @endif
                                @if ($purchase->canBeCancelledByCustomer())
                                    <form method="POST" action="{{ route('account.purchase-requests.cancel', $purchase) }}"
                                          data-confirm="Batalkan pengajuan {{ $carTitle }}?"
                                          data-disable-on-submit>
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-x-circle"></i>Batalkan
                                        </button>
                                    </form>
                                @endif
                            </div>
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
    </x-account.layout>
@endsection
