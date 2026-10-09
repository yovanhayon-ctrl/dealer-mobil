@extends('layouts.admin')

@section('title', 'Ulasan')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Ulasan']]])
@endsection

@php
    $filters = [null => 'Semua'] + \App\Models\Testimonial::STATUS_LABELS;
@endphp

@section('content')
    <p class="text-muted small mb-3">Ulasan customer untuk pembelian yang sudah selesai. Hanya ulasan <span class="fw-semibold">Disetujui</span> yang tampil di beranda.</p>

    <ul class="nav nav-pills gap-1 mb-3" aria-label="Filter status ulasan">
        @foreach ($filters as $value => $label)
            @php($isActive = ($status ?? '') === (string) $value)
            <li class="nav-item">
                <a @class(['nav-link py-1 px-3', 'active' => $isActive]) href="{{ route('admin.testimonials.index', $value ? ['status' => $value] : []) }}"
                   @if ($isActive) aria-current="page" @endif>
                    {{ $label }}
                    <span class="badge rounded-pill {{ $isActive ? 'bg-light text-dark' : 'badge-status-muted' }} ms-1">{{ $value ? (int) ($counts[$value] ?? 0) : (int) $counts->sum() }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @if ($testimonials->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-star" title="Belum ada ulasan" message="Ulasan muncul setelah customer menilai pembelian yang sudah selesai." />
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($testimonials as $testimonial)
                @php($car = $testimonial->purchaseRequest->car)
                @php($bag = $errors->getBag("reject{$testimonial->id}"))
                <article class="card" id="ulasan-{{ $testimonial->id }}">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="fw-semibold">{{ $testimonial->user->name }}</span>
                            <span class="text-muted small">{{ $testimonial->user->email }}</span>
                            <x-status-badge :status="$testimonial->status" />
                            <small class="text-muted ms-auto">{{ $testimonial->updated_at->translatedFormat('d M Y H:i') }}</small>
                        </div>
                        <p class="small mb-2">
                            <x-rating-stars :rating="$testimonial->rating" class="me-2" />
                            {{ $car->brand->name }} {{ $car->name }} {{ $car->year }}
                            @if (Route::has('admin.purchase-requests.show'))
                                · <a href="{{ route('admin.purchase-requests.show', $testimonial->purchase_request_id) }}">Lihat pengajuan</a>
                            @endif
                        </p>
                        <p class="mb-2 text-break">{{ $testimonial->comment }}</p>

                        @if ($testimonial->status === \App\Models\Testimonial::STATUS_REJECTED && $testimonial->admin_note)
                            <p class="small text-danger mb-2"><span class="fw-semibold">Alasan ditolak:</span> {{ $testimonial->admin_note }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            @unless ($testimonial->isApproved())
                                <form method="POST" action="{{ route('admin.testimonials.approve', $testimonial) }}" data-disable-on-submit>
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i>Setujui</button>
                                </form>
                            @endunless
                            @if ($testimonial->status !== \App\Models\Testimonial::STATUS_REJECTED)
                                <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#reject-{{ $testimonial->id }}" aria-expanded="{{ $bag->any() ? 'true' : 'false' }}"
                                        aria-controls="reject-{{ $testimonial->id }}">
                                    <i class="bi bi-x-lg"></i>{{ $testimonial->isApproved() ? 'Sembunyikan (Tolak)' : 'Tolak' }}
                                </button>
                            @endif
                        </div>

                        @if ($testimonial->status !== \App\Models\Testimonial::STATUS_REJECTED)
                            <form method="POST" action="{{ route('admin.testimonials.reject', $testimonial) }}"
                                  @class(['collapse mt-2', 'show' => $bag->any()]) id="reject-{{ $testimonial->id }}" data-disable-on-submit>
                                @csrf
                                @method('PATCH')
                                <label for="admin_note-{{ $testimonial->id }}" class="form-label small">Alasan penolakan (dikirim ke customer)</label>
                                <textarea id="admin_note-{{ $testimonial->id }}" name="admin_note" rows="2" maxlength="500" required
                                          @class(['form-control form-control-sm', 'is-invalid' => $bag->has('admin_note')])
                                          placeholder="Contoh: Ulasan berisi nomor HP. Mohon hapus data pribadi.">{{ $bag->any() ? old('admin_note') : '' }}</textarea>
                                @if ($bag->has('admin_note'))
                                    <div class="invalid-feedback">{{ $bag->first('admin_note') }}</div>
                                @endif
                                <button type="submit" class="btn btn-danger btn-sm mt-2">Tolak Ulasan</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if ($testimonials->hasPages())
            <div class="mt-4">
                {{ $testimonials->onEachSide(1)->links('partials.pagination-links') }}
            </div>
        @endif
    @endif
@endsection
