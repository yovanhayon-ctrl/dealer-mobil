@extends('layouts.app')

@section('title', 'Kirim Bukti Pembayaran')

@use('App\Http\Requests\Account\PaymentProofRequest')

@php
    $car = $purchaseRequest->car;
    $carTitle = "{$car->brand->name} {$car->name} {$car->year}";
    $rupiah = fn (int $amount) => 'Rp '.number_format($amount, 0, ',', '.');
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [
            ['label' => 'Pengajuan Saya', 'url' => route('account.purchase-requests.index')],
            ['label' => 'Kirim Bukti Pembayaran'],
        ]])

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <h1 class="h3 mb-1">Kirim Bukti Pembayaran</h1>
                <p class="text-muted mb-4">
                    Pembelian <span class="fw-semibold">{{ $carTitle }}</span> · {{ $purchaseRequest->paymentMethodLabel() }} ·
                    {{ $purchaseRequest->documentNumber() }}
                </p>

                <div class="payment-box rounded-3 p-3 mb-4">
                    <p class="small mb-2">
                        {{ $purchaseRequest->isCredit() ? 'DP yang dibayar ke dealer' : 'Harga mobil' }}:
                        <span class="fw-semibold">{{ $rupiah($purchaseRequest->amountPayableToDealer()) }}</span>
                        · sudah terverifikasi <span class="fw-semibold">{{ $rupiah($purchaseRequest->verifiedPaymentTotal()) }}</span>
                    </p>
                    <x-payment-bank />
                    <p class="small text-muted mb-0">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>{{ \App\Models\PurchaseRequest::SAFE_PAYMENT_NOTE }}
                    </p>
                </div>

                <div class="card">
                    <div class="card-body p-3 p-lg-4">
                        <form method="POST" action="{{ route('account.payment-proofs.store', $purchaseRequest) }}" enctype="multipart/form-data"
                              novalidate data-disable-on-submit>
                            @csrf

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <x-form.select name="type" label="Jenis Pembayaran" :options="$purchaseRequest->paymentProofTypes()"
                                                   placeholder="Pilih jenis" required />
                                </div>
                                <div class="col-sm-6">
                                    <x-form.input name="paid_on" type="date" label="Tanggal Transfer" required
                                                  min="{{ $purchaseRequest->created_at->toDateString() }}" max="{{ today()->toDateString() }}" />
                                </div>
                            </div>

                            <x-form.input name="amount" data-number="thousands" label="Nominal (Rp)" required inputmode="numeric"
                                          placeholder="Contoh: 5.000.000" help="Sesuai nominal di bukti transfer." />

                            <x-form.input name="file" type="file" label="File Bukti" required accept=".jpg,.jpeg,.png,.webp,.pdf"
                                          :help="'Foto/screenshot bukti transfer atau PDF dari m-banking. JPG, PNG, WEBP, atau PDF, maks. '.intdiv(PaymentProofRequest::MAX_KILOBYTES, 1024).' MB.'" />

                            <x-form.textarea name="note" label="Catatan (opsional)" rows="2" maxlength="{{ PaymentProofRequest::MAX_NOTE_LENGTH }}"
                                             help="Mis. nama pemilik rekening pengirim bila berbeda dengan nama akun Anda." />

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-accent">
                                    <i class="bi bi-upload"></i>Kirim Bukti
                                </button>
                                <a href="{{ route('account.purchase-requests.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="small text-muted mt-3 mb-0">
                    Bukti diperiksa admin dengan mutasi rekening dealer. Statusnya bisa dipantau di menu Pengajuan Saya.
                </p>
            </div>
        </div>
    </div>
@endsection
