@extends('layouts.admin')

@section('title', 'Detail Pengajuan')

@use('App\Models\PurchaseRequest')

@php
    $car = $purchaseRequest->car;
    $carLabel = "{$car->brand->name} {$car->name} {$car->year}";
    $awaitingDecision = in_array($purchaseRequest->status, [PurchaseRequest::STATUS_PENDING, PurchaseRequest::STATUS_PROCESSING], true);
    // Verifikasi bukti, pembayaran showroom, file bukti, dan pengembalian dana: hanya payments.manage (bukan Sales/CS).
    $canPay = auth()->user()->can('payments.manage');
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Pengajuan', 'url' => route('admin.purchase-requests.index')],
        ['label' => $purchaseRequest->user->name.' · '.$carLabel],
    ]])
@endsection

@section('content')
    <x-admin.detail-header :title="'Pengajuan #'.$purchaseRequest->id" :subtitle="$purchaseRequest->documentNumber().' · diajukan '.$purchaseRequest->created_at->translatedFormat('d F Y H:i').' WIB'"
                           :back-url="route('admin.purchase-requests.index')" back-label="Pengajuan">
        <x-status-badge :status="$purchaseRequest->status" />
        @if ($purchaseRequest->hasPaymentSection())
            <x-status-badge :status="$purchaseRequest->paymentStatusBadge()" :label="$purchaseRequest->paymentStatusLabel()" />
        @endif
        @if ($purchaseRequest->isPaymentOverdue())
            <x-status-badge status="rejected" label="Lewat tenggat" />
        @endif
        @if ($purchaseRequest->isAwaitingResponse())
            <x-status-badge status="rejected" label="Belum ditanggapi" />
        @endif
        @if (Route::has('admin.purchase-requests.pdf'))
            <a href="{{ route('admin.purchase-requests.pdf', $purchaseRequest) }}" target="_blank" rel="noopener"
               class="btn btn-outline-secondary btn-sm" title="Bukti {{ $purchaseRequest->documentNumber() }}">
                <i class="bi bi-file-earmark-pdf"></i>Cetak PDF
            </a>
        @endif
    </x-admin.detail-header>

    @if ($purchaseRequest->isPaymentOverdue())
        <div class="alert alert-warning d-flex gap-2 mb-4" role="status">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <strong>Lewat batas pembayaran pertama.</strong>
                Batasnya {{ $purchaseRequest->paymentDueAt()->translatedFormat('l, d M Y') }}
                ({{ $purchaseRequest->paymentOverdueDays() }} hari lalu) dan belum ada pembayaran maupun bukti yang masuk.
                Hubungi customer, atau ubah status ke <strong>Dibatalkan</strong> agar unit kembali ke stok.
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <dl class="admin-detail-list">

                        <dt>Mobil</dt>
                        <dd>
                            {{ $carLabel }}
                            <span class="text-muted">· {{ $car->condition_label }} · stok {{ $car->stock }}</span>
                            @unless ($car->is_active)
                                <x-status-badge status="inactive" class="ms-1" />
                            @endunless
                        </dd>

                        <dt>Customer</dt>
                        <dd>
                            <a href="{{ route('admin.users.show', $purchaseRequest->user) }}">{{ $purchaseRequest->user->name }}</a>
                            <span class="text-muted">· <a href="mailto:{{ $purchaseRequest->user->email }}" class="text-muted">{{ $purchaseRequest->user->email }}</a></span>
                        </dd>

                        <dt>Nomor HP</dt>
                        <dd>
                            <a href="tel:{{ $purchaseRequest->phone }}">{{ $purchaseRequest->phone }}</a>
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $purchaseRequest->phone)) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-success py-0 ms-2"><i class="bi bi-whatsapp"></i>WhatsApp</a>
                        </dd>

                        <dt>Alamat</dt>
                        <dd>{{ $purchaseRequest->address }}</dd>

                        <dt>Catatan customer</dt>
                        <dd>{{ $purchaseRequest->notes ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body p-4">
                    <h3 class="admin-card-title">Rincian Harga · {{ $purchaseRequest->paymentMethodLabel() }}</h3>

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

            @if ($purchaseRequest->hasPaymentSection())
                <div class="card mb-4" id="bukti-pembayaran">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="admin-card-title mb-0">Bukti Pembayaran</h3>
                                <x-status-badge :status="$purchaseRequest->paymentStatusBadge()" :label="$purchaseRequest->paymentStatusLabel()" />
                            </div>
                            <span class="small text-muted">
                                Diterima <span class="fw-semibold text-body"><x-price :amount="$purchaseRequest->verifiedPaymentTotal()" /></span>
                                dari <x-price :amount="$purchaseRequest->amountPayableToDealer()" />{{ $purchaseRequest->isCredit() ? ' (DP)' : '' }}
                            </span>
                        </div>

                        @if ($purchaseRequest->isPaidOff() && $purchaseRequest->status === PurchaseRequest::STATUS_APPROVED)
                            <div class="alert alert-success small py-2">
                                <i class="bi bi-check-circle me-1"></i>Pembayaran {{ mb_strtolower($purchaseRequest->paymentStatusLabel()) }}.
                                Ubah status ke <span class="fw-semibold">Selesai</span> setelah mobil diserahkan.
                            </div>
                        @elseif ($purchaseRequest->awaitsFirstPayment() && $purchaseRequest->paymentDueAt())
                            <div class="small mb-3">
                                Batas pembayaran pertama <span class="fw-semibold">{{ $purchaseRequest->paymentDueAt()->translatedFormat('l, d M Y') }}</span>
                                @if ($purchaseRequest->payment_reminded_at)
                                    <span class="text-muted">· pengingat terkirim {{ $purchaseRequest->payment_reminded_at->translatedFormat('d M Y H:i') }}</span>
                                @endif
                                @if (auth()->user()->can('purchases.status') && ($waUrl = $purchaseRequest->whatsappPaymentReminderUrl()))
                                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success py-0 ms-1">
                                        <i class="bi bi-whatsapp"></i>Ingatkan via WhatsApp
                                    </a>
                                @endif
                            </div>
                        @elseif ($purchaseRequest->verifiedPaymentTotal() > 0 && ! $purchaseRequest->isClosed())
                            <p class="small text-muted mb-3">Sisa <span class="fw-semibold text-body"><x-price :amount="$purchaseRequest->remainingPayment()" /></span></p>
                        @endif

                        @forelse ($purchaseRequest->paymentProofs as $proof)
                            @php($bag = $errors->getBag("rejectProof{$proof->id}"))
                            @php($fileUrl = route('admin.payment-proofs.file', [$purchaseRequest, $proof]))
                            <div class="admin-proof d-flex gap-3">
                                @if ($canPay && $proof->hasFile())
                                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="admin-proof-thumb" aria-label="Buka file bukti {{ $proof->typeLabel() }}">
                                        @if ($proof->isImage())
                                            <img src="{{ $fileUrl }}" alt="" loading="lazy">
                                        @else
                                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                        @endif
                                    </a>
                                @else
                                    <span class="admin-proof-thumb" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                                @endif
                                <div class="min-w-0 flex-grow-1 small">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="fw-semibold">{{ $proof->typeLabel() }} <x-price :amount="$proof->amount" /></span>
                                        <x-status-badge :status="$proof->status" :label="$proof->statusLabel()" />
                                    </div>
                                    <p class="text-muted mb-1">
                                        @if ($proof->isShowroomPayment())
                                            {{ $proof->methodLabel() }} · {{ $proof->paid_on->translatedFormat('d M Y') }}
                                            · dicatat {{ $proof->recorder?->name ?? 'admin' }} {{ $proof->created_at->translatedFormat('d M Y H:i') }}
                                        @else
                                            Transfer {{ $proof->paid_on->translatedFormat('d M Y') }} · dikirim {{ $proof->created_at->translatedFormat('d M Y H:i') }}
                                        @endif
                                        @if ($canPay && $proof->hasFile())
                                            · <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="text-break">{{ $proof->file_name }}</a>
                                        @endif
                                    </p>
                                    @if ($proof->note)
                                        <p class="mb-1"><span class="fw-semibold">{{ $proof->isShowroomPayment() ? 'Catatan' : 'Catatan customer' }}:</span> {{ $proof->note }}</p>
                                    @endif
                                    @if ($proof->status === \App\Models\PaymentProof::STATUS_REJECTED && $proof->admin_note)
                                        <p class="text-danger mb-1"><span class="fw-semibold">Alasan ditolak:</span> {{ $proof->admin_note }}</p>
                                    @endif

                                    @if ($canPay && $proof->isPending())
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <form method="POST" action="{{ route('admin.payment-proofs.approve', [$purchaseRequest, $proof]) }}"
                                                  data-confirm="Terima bukti {{ $proof->typeLabel() }} Rp {{ number_format($proof->amount, 0, ',', '.') }}? Pastikan dana sudah masuk ke rekening dealer."
                                                  data-disable-on-submit>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i>Terima</button>
                                            </form>
                                            <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#reject-proof-{{ $proof->id }}" aria-expanded="{{ $bag->any() ? 'true' : 'false' }}"
                                                    aria-controls="reject-proof-{{ $proof->id }}">
                                                <i class="bi bi-x-lg"></i>Tolak
                                            </button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.payment-proofs.reject', [$purchaseRequest, $proof]) }}"
                                              @class(['collapse mt-2', 'show' => $bag->any()]) id="reject-proof-{{ $proof->id }}" data-disable-on-submit>
                                            @csrf
                                            @method('PATCH')
                                            <label for="proof_note-{{ $proof->id }}" class="form-label small">Alasan penolakan (dilihat customer)</label>
                                            <textarea id="proof_note-{{ $proof->id }}" name="admin_note" rows="2" maxlength="500" required
                                                      @class(['form-control form-control-sm', 'is-invalid' => $bag->has('admin_note')])
                                                      placeholder="Contoh: Dana belum masuk ke rekening dealer. Mohon cek kembali.">{{ $bag->any() ? old('admin_note') : '' }}</textarea>
                                            @if ($bag->has('admin_note'))
                                                <div class="invalid-feedback">{{ $bag->first('admin_note') }}</div>
                                            @endif
                                            <button type="submit" class="btn btn-danger btn-sm mt-2">Tolak Bukti</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="small text-muted mb-0">Belum ada bukti pembayaran dari customer.</p>
                        @endforelse

                        {{-- Pembayaran langsung di showroom: dicatat admin, langsung diterima. --}}
                        @if ($canPay && $purchaseRequest->status === PurchaseRequest::STATUS_APPROVED && ! $purchaseRequest->isPaidOff())
                            @php($showroomErrors = $errors->getBag('showroomPayment'))
                            <div class="mt-3 pt-3 border-top">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#showroom-payment"
                                        aria-expanded="{{ $showroomErrors->any() ? 'true' : 'false' }}" aria-controls="showroom-payment">
                                    <i class="bi bi-cash-coin"></i>Catat Pembayaran di Showroom
                                </button>
                                <form method="POST" action="{{ route('admin.payment-proofs.store', $purchaseRequest) }}" enctype="multipart/form-data"
                                      @class(['collapse mt-3', 'show' => $showroomErrors->any()]) id="showroom-payment" novalidate
                                      data-confirm="Catat pembayaran ini sebagai diterima? Pastikan uang sudah diterima kasir.">
                                    @csrf
                                    @php($fieldError = fn (string $field) => $showroomErrors->first($field))
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label for="sp-type" class="form-label small">Jenis pembayaran</label>
                                            <select id="sp-type" name="type" @class(['form-select form-select-sm', 'is-invalid' => $fieldError('type')]) required>
                                                @foreach ($purchaseRequest->paymentProofTypes() as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @if ($fieldError('type'))<div class="invalid-feedback">{{ $fieldError('type') }}</div>@endif
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="sp-method" class="form-label small">Cara bayar</label>
                                            <select id="sp-method" name="method" @class(['form-select form-select-sm', 'is-invalid' => $fieldError('method')]) required>
                                                @foreach (\App\Models\PaymentProof::SHOWROOM_METHODS as $method)
                                                    <option value="{{ $method }}" @selected(old('method') === $method)>{{ \App\Models\PaymentProof::METHOD_LABELS[$method] }}</option>
                                                @endforeach
                                            </select>
                                            @if ($fieldError('method'))<div class="invalid-feedback">{{ $fieldError('method') }}</div>@endif
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="sp-amount" class="form-label small">Nominal (Rp)</label>
                                            <input type="text" id="sp-amount" name="amount" data-number="thousands" inputmode="numeric" required
                                                   value="{{ old('amount', number_format($purchaseRequest->remainingPayment(), 0, ',', '.')) }}"
                                                   @class(['form-control form-control-sm', 'is-invalid' => $fieldError('amount')])>
                                            @if ($fieldError('amount'))
                                                <div class="invalid-feedback">{{ $fieldError('amount') }}</div>
                                            @else
                                                <div class="form-text">Sisa tagihan <x-price :amount="$purchaseRequest->remainingPayment()" /></div>
                                            @endif
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="sp-date" class="form-label small">Tanggal bayar</label>
                                            <input type="date" id="sp-date" name="paid_on" required value="{{ old('paid_on', today()->toDateString()) }}"
                                                   min="{{ $purchaseRequest->created_at->toDateString() }}" max="{{ today()->toDateString() }}"
                                                   @class(['form-control form-control-sm', 'is-invalid' => $fieldError('paid_on')])>
                                            @if ($fieldError('paid_on'))<div class="invalid-feedback">{{ $fieldError('paid_on') }}</div>@endif
                                        </div>
                                        <div class="col-12">
                                            <label for="sp-file" class="form-label small">Foto kwitansi / struk EDC (opsional)</label>
                                            <input type="file" id="sp-file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                   @class(['form-control form-control-sm', 'is-invalid' => $fieldError('file')])>
                                            @if ($fieldError('file'))<div class="invalid-feedback">{{ $fieldError('file') }}</div>@endif
                                        </div>
                                        <div class="col-12">
                                            <label for="sp-note" class="form-label small">Catatan (opsional)</label>
                                            <input type="text" id="sp-note" name="note" maxlength="500" value="{{ old('note') }}" placeholder="Contoh: No. kwitansi 0123, diterima kasir Rina"
                                                   @class(['form-control form-control-sm', 'is-invalid' => $fieldError('note')])>
                                            @if ($fieldError('note'))<div class="invalid-feedback">{{ $fieldError('note') }}</div>@endif
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm mt-3">
                                        <i class="bi bi-check-lg"></i>Simpan Pembayaran
                                    </button>
                                </form>
                            </div>
                        @endif

                        {{-- Pengembalian dana: pengajuan batal yang sempat menerima pembayaran. --}}
                        @if ($purchaseRequest->refundStatus())
                            @php($refundErrors = $errors->getBag('refund'))
                            @php($closeErrors = $errors->getBag('refundClose'))
                            <div class="mt-3 pt-3 border-top" id="pengembalian-dana">
                                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
                                    <h4 class="h6 mb-0">Pengembalian Dana</h4>
                                    <span class="small text-muted">
                                        Dikembalikan <span class="fw-semibold text-body"><x-price :amount="$purchaseRequest->refundedTotal()" /></span>
                                        dari <x-price :amount="$purchaseRequest->verifiedPaymentTotal()" /> diterima
                                    </span>
                                </div>

                                @if ($purchaseRequest->needsRefundAction())
                                    <div class="alert alert-warning small py-2">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Pengajuan sudah {{ mb_strtolower($purchaseRequest->statusLabel()) }}, tetapi dana
                                        <span class="fw-semibold"><x-price :amount="$purchaseRequest->refundableAmount()" /></span> belum dikembalikan ke customer.
                                        Catat pengembaliannya, atau tutup tanpa pengembalian bila sesuai kesepakatan (mis. tanda jadi hangus).
                                    </div>
                                @endif

                                @foreach ($purchaseRequest->paymentRefunds as $refund)
                                    @php($refundUrl = route('admin.payment-refunds.file', [$purchaseRequest, $refund]))
                                    <div class="admin-proof d-flex gap-3">
                                        @if ($canPay && $refund->hasFile())
                                            <a href="{{ $refundUrl }}" target="_blank" rel="noopener" class="admin-proof-thumb" aria-label="Buka bukti pengembalian">
                                                @if ($refund->isImage())
                                                    <img src="{{ $refundUrl }}" alt="" loading="lazy">
                                                @else
                                                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                                @endif
                                            </a>
                                        @else
                                            <span class="admin-proof-thumb" aria-hidden="true"><i class="bi bi-arrow-return-left"></i></span>
                                        @endif
                                        <div class="min-w-0 flex-grow-1 small">
                                            <span class="fw-semibold">Dikembalikan <x-price :amount="$refund->amount" /></span>
                                            <p class="text-muted mb-1">
                                                {{ $refund->methodLabel() }} · {{ $refund->refunded_on->translatedFormat('d M Y') }}
                                                · dicatat {{ $refund->recorder?->name ?? 'admin' }} {{ $refund->created_at->translatedFormat('d M Y H:i') }}
                                                @if ($canPay && $refund->hasFile())
                                                    · <a href="{{ $refundUrl }}" target="_blank" rel="noopener" class="text-break">{{ $refund->file_name }}</a>
                                                @endif
                                            </p>
                                            @if ($refund->note)
                                                <p class="mb-1"><span class="fw-semibold">Catatan:</span> {{ $refund->note }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @if ($purchaseRequest->refund_closed_at)
                                    <p class="small mb-0">
                                        <span class="fw-semibold">Ditutup tanpa {{ $purchaseRequest->refundedTotal() > 0 ? 'sisa ' : '' }}pengembalian</span>
                                        {{ $purchaseRequest->refund_closed_at->translatedFormat('d M Y H:i') }}: {{ $purchaseRequest->refund_closed_note }}
                                    </p>
                                @endif

                                @if ($canPay && $purchaseRequest->needsRefundAction())
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#refund-form"
                                                aria-expanded="{{ $refundErrors->any() ? 'true' : 'false' }}" aria-controls="refund-form">
                                            <i class="bi bi-arrow-return-left"></i>Catat Pengembalian Dana
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#refund-close"
                                                aria-expanded="{{ $closeErrors->any() ? 'true' : 'false' }}" aria-controls="refund-close">
                                            Tutup tanpa pengembalian
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route('admin.payment-refunds.store', $purchaseRequest) }}" enctype="multipart/form-data"
                                          @class(['collapse mt-3', 'show' => $refundErrors->any()]) id="refund-form" novalidate
                                          data-confirm="Catat pengembalian dana ini? Pastikan uang sudah dikembalikan ke customer.">
                                        @csrf
                                        @php($refundError = fn (string $field) => $refundErrors->first($field))
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <label for="rf-amount" class="form-label small">Nominal dikembalikan (Rp)</label>
                                                <input type="text" id="rf-amount" name="amount" data-number="thousands" inputmode="numeric" required
                                                       value="{{ old('amount', number_format($purchaseRequest->refundableAmount(), 0, ',', '.')) }}"
                                                       @class(['form-control form-control-sm', 'is-invalid' => $refundError('amount')])>
                                                @if ($refundError('amount'))
                                                    <div class="invalid-feedback">{{ $refundError('amount') }}</div>
                                                @else
                                                    <div class="form-text">Belum dikembalikan <x-price :amount="$purchaseRequest->refundableAmount()" /></div>
                                                @endif
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="rf-method" class="form-label small">Cara pengembalian</label>
                                                <select id="rf-method" name="method" @class(['form-select form-select-sm', 'is-invalid' => $refundError('method')]) required>
                                                    @foreach (\App\Models\PaymentRefund::METHOD_LABELS as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($refundError('method'))<div class="invalid-feedback">{{ $refundError('method') }}</div>@endif
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="rf-date" class="form-label small">Tanggal pengembalian</label>
                                                <input type="date" id="rf-date" name="refunded_on" required value="{{ old('refunded_on', today()->toDateString()) }}"
                                                       min="{{ $purchaseRequest->created_at->toDateString() }}" max="{{ today()->toDateString() }}"
                                                       @class(['form-control form-control-sm', 'is-invalid' => $refundError('refunded_on')])>
                                                @if ($refundError('refunded_on'))<div class="invalid-feedback">{{ $refundError('refunded_on') }}</div>@endif
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="rf-file" class="form-label small">Bukti transfer / kwitansi (opsional)</label>
                                                <input type="file" id="rf-file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                       @class(['form-control form-control-sm', 'is-invalid' => $refundError('file')])>
                                                @if ($refundError('file'))<div class="invalid-feedback">{{ $refundError('file') }}</div>@endif
                                            </div>
                                            <div class="col-12">
                                                <label for="rf-note" class="form-label small">Catatan untuk customer (opsional)</label>
                                                <input type="text" id="rf-note" name="note" maxlength="500" value="{{ old('note') }}" placeholder="Contoh: Ditransfer ke BCA a.n. customer"
                                                       @class(['form-control form-control-sm', 'is-invalid' => $refundError('note')])>
                                                @if ($refundError('note'))<div class="invalid-feedback">{{ $refundError('note') }}</div>@endif
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm mt-3">
                                            <i class="bi bi-check-lg"></i>Simpan Pengembalian
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.payment-refunds.close', $purchaseRequest) }}"
                                          @class(['collapse mt-3', 'show' => $closeErrors->any()]) id="refund-close"
                                          data-confirm="Tutup tanpa mengembalikan dana Rp {{ number_format($purchaseRequest->refundableAmount(), 0, ',', '.') }}? Customer akan melihat alasannya.">
                                        @csrf
                                        @method('PATCH')
                                        <label for="rf-close-note" class="form-label small">Alasan dana tidak dikembalikan (dilihat customer)</label>
                                        <textarea id="rf-close-note" name="refund_closed_note" rows="2" maxlength="500" required
                                                  @class(['form-control form-control-sm', 'is-invalid' => $closeErrors->has('refund_closed_note')])
                                                  placeholder="Contoh: Tanda jadi hangus sesuai kesepakatan pada saat pemesanan.">{{ $closeErrors->any() ? old('refund_closed_note') : '' }}</textarea>
                                        @if ($closeErrors->has('refund_closed_note'))
                                            <div class="invalid-feedback">{{ $closeErrors->first('refund_closed_note') }}</div>
                                        @endif
                                        <button type="submit" class="btn btn-outline-danger btn-sm mt-2">Tutup tanpa Pengembalian</button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        <p class="small text-muted mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>Cocokkan dengan mutasi rekening sebelum menerima. Status pengajuan tetap diubah manual.
                        </p>
                    </div>
                </div>
            @endif

            @if ($awaitingDecision && ! $car->inStock())
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Stok mobil ini 0. Pengajuan tidak bisa disetujui sampai stok tersedia.
                </div>
            @endif
        </div>

        <div class="col-xl-5">
            <div class="card admin-sticky-panel">
                <div class="card-body p-4">
                    <h3 class="admin-card-title">Status & Catatan Admin</h3>
                    @can('purchases.status')
                        @include('admin.partials.status-form', [
                            'action' => route('admin.purchase-requests.update-status', $purchaseRequest),
                            'currentLabel' => $purchaseRequest->statusLabel(),
                            'allowed' => collect($purchaseRequest->allowedTransitions())
                                ->mapWithKeys(fn ($status) => [$status => PurchaseRequest::STATUS_LABELS[$status]])
                                ->all(),
                            'adminNote' => $purchaseRequest->admin_note,
                            'noteHelp' => 'Terisi otomatis sesuai status yang dipilih dan boleh diubah. Saat menolak/membatalkan, ganti tanda … dengan alasan untuk customer. Maksimal 1000 karakter.',
                            'noteTemplates' => $purchaseRequest->noteTemplates(),
                        ])
                    @else
                        {{-- Customer Service: hanya melihat. --}}
                        <p class="small mb-2">Status: <x-status-badge :status="$purchaseRequest->status" /></p>
                        <p class="small text-muted mb-1">Catatan admin</p>
                        <p class="small mb-0">{{ $purchaseRequest->admin_note ?: '-' }}</p>
                    @endcan

                    @if ($purchaseRequest->status === PurchaseRequest::STATUS_APPROVED && $purchaseRequest->verifiedPaymentTotal() > 0)
                        <div class="alert alert-warning small py-2 mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>Pengajuan ini sudah menerima
                            <span class="fw-semibold"><x-price :amount="$purchaseRequest->verifiedPaymentTotal()" /></span>.
                            Bila dibatalkan atau ditolak, kembalikan dananya lalu catat di bagian Bukti Pembayaran.
                        </div>
                    @endif

                    <div class="small text-muted mt-3">
                        <i class="bi bi-info-circle me-1"></i>Disetujui: stok −1 · Disetujui → Ditolak/Dibatalkan: stok +1 · Disetujui → Selesai: stok tetap.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
