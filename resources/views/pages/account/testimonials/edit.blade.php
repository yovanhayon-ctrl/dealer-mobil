@extends('layouts.app')

@section('title', $testimonial ? 'Ubah Ulasan' : 'Beri Ulasan')

@php
    $car = $purchaseRequest->car;
    $carTitle = "{$car->brand->name} {$car->name} {$car->year}";
    $currentRating = (int) old('rating', $testimonial?->rating);
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [
            ['label' => 'Pengajuan Saya', 'url' => route('account.purchase-requests.index')],
            ['label' => $testimonial ? 'Ubah Ulasan' : 'Beri Ulasan'],
        ]])

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <h1 class="h3 mb-1">{{ $testimonial ? 'Ubah Ulasan' : 'Beri Ulasan' }}</h1>
                <p class="text-muted mb-4">Pembelian <span class="fw-semibold">{{ $carTitle }}</span> · {{ $purchaseRequest->paymentMethodLabel() }}</p>

                @if ($testimonial?->status === \App\Models\Testimonial::STATUS_REJECTED && $testimonial->admin_note)
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i><span class="fw-semibold">Ulasan sebelumnya ditolak:</span> {{ $testimonial->admin_note }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body p-3 p-lg-4">
                        <form method="POST" action="{{ route('account.testimonials.update', $purchaseRequest) }}" novalidate data-disable-on-submit>
                            @csrf
                            @method('PUT')

                            <fieldset class="mb-3">
                                <legend class="form-label fs-6 mb-2">Rating<span class="text-danger ms-1" aria-hidden="true">*</span></legend>
                                <div class="d-flex flex-wrap gap-2 rating-input">
                                    @for ($i = \App\Models\Testimonial::MAX_RATING; $i >= \App\Models\Testimonial::MIN_RATING; $i--)
                                        <input type="radio" class="btn-check" name="rating" id="rating-{{ $i }}" value="{{ $i }}"
                                               @checked($currentRating === $i) required>
                                        <label @class(['btn btn-outline-warning', 'is-invalid' => $errors->has('rating')]) for="rating-{{ $i }}">
                                            {{ $i }} <i class="bi bi-star-fill ms-1 me-0"></i>
                                        </label>
                                    @endfor
                                </div>
                                @error('rating')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="form-text">5 = sangat puas, 1 = tidak puas.</div>
                                @enderror
                            </fieldset>

                            <x-form.textarea name="comment" label="Ulasan" :value="$testimonial?->comment" rows="5" required maxlength="1000"
                                             help="20–1000 karakter. Ceritakan pelayanan, proses pembelian, atau kondisi mobil. Jangan menulis nomor HP atau data pribadi." />

                            <p class="small text-muted">
                                <i class="bi bi-shield-check me-1"></i>Ulasan tampil di beranda setelah disetujui admin, dengan nama disingkat
                                (<span class="fw-semibold">{{ \App\Models\Testimonial::publicName(auth()->user()->name) }}</span>).
                            </p>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-accent">
                                    <i class="bi bi-send"></i>Kirim Ulasan
                                </button>
                                <a href="{{ route('account.purchase-requests.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
