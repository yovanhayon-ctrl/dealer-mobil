@extends('layouts.app')

@use('App\Http\Requests\PurchaseRequestRequest')
@use('App\Models\PurchaseRequest')

@php
    $title = "{$car->brand->name} {$car->name} {$car->year}";
    $digits = fn (?int $amount) => $amount === null ? '' : number_format($amount, 0, ',', '.');
    $percent = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%';
    $method = old('payment_method', $defaultMethod);
    $creditCalc = app(\App\Support\CreditCalculator::class);
@endphp

@section('title', 'Ajukan Pembelian '.$title)
@section('meta_description', 'Ajukan pembelian '.$title.' di '.config('dealer.name').' secara cash atau kredit.')

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [
            ['label' => 'Mobil', 'url' => route('cars.index')],
            ['label' => $title, 'url' => route('cars.show', $car)],
            ['label' => 'Ajukan Pembelian'],
        ]])

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <h1 class="h3 mb-1">Ajukan Pembelian</h1>
                <p class="text-muted mb-4">Pilih metode pembayaran dan lengkapi data Anda. Tim kami akan menghubungi lewat WhatsApp untuk proses selanjutnya.</p>

                <div class="card test-drive-car-summary mb-4">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        @if ($car->primaryImage)
                            <img src="{{ $car->primaryImage->thumb_url }}" alt="{{ $title }}" class="test-drive-car-thumb">
                        @else
                            <span class="test-drive-car-thumb car-card-img-empty" aria-hidden="true"><i class="bi bi-car-front"></i></span>
                        @endif
                        <div class="min-w-0">
                            <p class="small text-muted mb-0">{{ $car->brand->name }} · {{ $car->category->name }} · {{ $car->condition_label }}</p>
                            <p class="fw-semibold font-heading mb-0">{{ $car->name }} {{ $car->year }}</p>
                            <p class="mb-0">
                                @if ($car->hasPromoPrice())
                                    <del class="price-old small me-1"><x-price :amount="$car->price" /></del>
                                @endif
                                <x-price :amount="$price" class="fw-bold text-accent" />
                            </p>
                        </div>
                    </div>
                </div>

                @if ($isAdmin)
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div>
                            {{ PurchaseRequestRequest::ADMIN_MESSAGE }}
                            <a href="{{ route('admin.purchase-requests.index') }}" class="alert-link">Kelola pengajuan di dashboard admin</a>.
                        </div>
                    </div>
                @elseif ($existing)
                    <div class="alert alert-info d-flex align-items-center" role="status">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <div>
                            Anda sudah punya pengajuan untuk mobil ini ({{ $existing->statusLabel() }}, {{ $existing->created_at->translatedFormat('d M Y') }}).
                            <a href="{{ route('account.purchase-requests.index') }}" class="alert-link">Lihat Pengajuan Saya</a>.
                        </div>
                    </div>
                @elseif (! $car->inStock())
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div>Stok mobil ini habis, pengajuan belum bisa dilakukan. <a href="{{ route('cars.index') }}" class="alert-link">Lihat mobil lain</a>.</div>
                    </div>
                @else
                    @error('car')
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>
                            <div>{{ $message }}</div>
                        </div>
                    @enderror

                    <div class="card">
                        <div class="card-body p-3 p-lg-4">
                            <form method="POST" action="{{ route('purchase-requests.store', $car) }}" novalidate data-disable-on-submit
                                  data-purchase-form
                                  data-price="{{ $price }}"
                                  data-rates='@json($rates)'
                                  data-dp-min="{{ config('credit.dp_min') }}"
                                  data-dp-max="{{ config('credit.dp_max') }}"
                                  data-rounding="{{ config('credit.rounding') }}">
                                @csrf

                                <fieldset class="mb-3">
                                    <legend class="form-label fs-6">Metode Pembayaran<span class="text-danger ms-1" aria-hidden="true">*</span></legend>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach (PurchaseRequest::PAYMENT_METHOD_LABELS as $value => $label)
                                            <div class="form-check">
                                                <input @class(['form-check-input', 'is-invalid' => $errors->has('payment_method')]) type="radio"
                                                       name="payment_method" id="payment_method_{{ $value }}" value="{{ $value }}" @checked($method === $value)>
                                                <label class="form-check-label" for="payment_method_{{ $value }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('payment_method')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </fieldset>

                                <div class="border rounded-3 p-3 mb-3" data-credit-fields>
                                    <p class="small text-muted mb-3">Isi bagian ini bila memilih <strong>Kredit</strong>.</p>
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <x-form.input name="down_payment" label="Uang Muka / DP (Rp)" :value="$digits($defaultDownPayment)"
                                                          inputmode="numeric" data-purchase-dp
                                                          :help="'Minimal '.$digits($creditCalc->minDownPayment($price)).' ('.config('credit.dp_min').'%), maksimal '.config('credit.dp_max').'%.'" />
                                        </div>
                                        <div class="col-sm-6">
                                            <x-form.select name="tenor_months" label="Tenor" :value="$defaultTenor" data-purchase-tenor
                                                           :options="collect($tenors)->mapWithKeys(fn ($months) => [$months => $months.' bulan · bunga '.$percent((float) $rates[$months]).'/tahun'])->all()" />
                                        </div>
                                    </div>

                                    <div class="promo-box rounded-3 p-3 d-none" data-purchase-preview>
                                        <p class="small text-uppercase fw-semibold mb-1">Perkiraan cicilan</p>
                                        <p class="h4 fw-bold text-accent mb-1"><span data-preview="monthly_installment"></span><span class="fs-6 text-muted fw-normal">/bulan</span></p>
                                        <p class="small text-muted mb-0">
                                            selama <span data-preview="tenor_months"></span> bulan · pokok <span data-preview="principal"></span>
                                            · total <span data-preview="total_payment"></span>
                                        </p>
                                    </div>
                                </div>

                                <x-form.input name="phone" type="tel" label="Nomor WhatsApp" required
                                              :value="auth()->user()->phone" inputmode="tel" autocomplete="tel"
                                              help="Format 08xx. Dipakai tim kami untuk menghubungi Anda." />

                                <x-form.textarea name="address" label="Alamat Lengkap" required rows="3"
                                                 maxlength="{{ PurchaseRequestRequest::MAX_ADDRESS_LENGTH }}"
                                                 help="Untuk keperluan dokumen dan pengiriman unit." />

                                <x-form.textarea name="notes" label="Catatan (opsional)" rows="2"
                                                 maxlength="{{ PurchaseRequestRequest::MAX_NOTES_LENGTH }}"
                                                 help="Mis. warna yang diinginkan atau waktu yang nyaman untuk dihubungi." />

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <button type="submit" class="btn btn-accent">
                                        <i class="bi bi-cart-check"></i>Kirim Pengajuan
                                    </button>
                                    <a href="{{ route('cars.show', $car) }}" class="btn btn-outline-secondary">Kembali</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <ul class="small text-muted mt-3 mb-0">
                        <li>Harga dan cicilan dihitung ulang oleh sistem saat pengajuan dikirim, termasuk promo yang berlaku.</li>
                        <li>Simulasi kredit bersifat perkiraan; nilai akhir mengikuti persetujuan leasing.</li>
                        <li>Pengajuan berstatus "Menunggu" bisa Anda batalkan di menu Pengajuan Saya.</li>
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/credit-simulation.js') }}"></script>
    <script src="{{ \App\Support\Asset::url('js/purchase-request.js') }}"></script>
@endpush
