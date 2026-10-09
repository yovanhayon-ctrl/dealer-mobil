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

                                {{-- Ulasan: hanya untuk pembelian yang sudah selesai. --}}
                                @if ($purchase->canBeReviewed() && Route::has('account.testimonials.edit'))
                                    @php($review = $purchase->testimonial)
                                    <div class="testimonial-box small rounded-3 p-2 mb-2">
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
