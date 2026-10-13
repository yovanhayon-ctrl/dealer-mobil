@extends('layouts.admin')

@section('title', $booking->plate_number)

@use('App\Models\ServiceBooking')
@use('App\Models\ServiceBookingTask')

@php
    [$handled, $total] = $booking->taskProgress();
    $working = $booking->status === ServiceBooking::STATUS_IN_PROGRESS;
    $findingsErrors = $errors->getBag('findings');
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => $booking->plate_number]]])
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0">{{ $booking->vehicle_model }}@if ($booking->vehicle_year) ({{ $booking->vehicle_year }})@endif</h2>
        <x-status-badge :status="$booking->status" />
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-2">
                        <h3 class="admin-card-title mb-0">Checklist Pekerjaan</h3>
                        @if ($total > 0)
                            <span class="small text-muted">{{ $handled }} dari {{ $total }} ditangani</span>
                        @endif
                    </div>

                    @if ($booking->status === ServiceBooking::STATUS_CONFIRMED)
                        <div class="alert alert-light border small mb-3">
                            Tekan <span class="fw-semibold">Mulai Kerjakan</span> saat mobil sudah di bengkel. Customer akan diberi tahu.
                        </div>
                    @endif

                    @forelse ($booking->tasks as $task)
                        @php($bag = $errors->getBag("task{$task->id}"))
                        <div @class(['mechanic-task', 'is-done' => $task->isDone(), 'is-skipped' => $task->isSkipped()]) id="tugas-{{ $task->id }}">
                            <div class="mechanic-task-name">
                                <i @class([
                                    'bi me-1',
                                    'bi-check-circle-fill text-success' => $task->isDone(),
                                    'bi-dash-circle' => $task->isSkipped(),
                                    'bi-circle' => $task->isPending(),
                                ]) aria-hidden="true"></i>
                                <span @class(['fw-semibold' => $task->isPending()])>{{ $task->name }}</span>
                                @if ($task->isSkipped())
                                    <div class="small">Dilewati: {{ $task->note }}</div>
                                @elseif ($task->isDone())
                                    <span class="small ms-1">selesai {{ $task->checked_at?->translatedFormat('H:i') }}</span>
                                @endif
                            </div>

                            @if ($working)
                                <div class="mechanic-task-actions d-flex flex-wrap gap-2">
                                    @if ($task->isPending())
                                        <form method="POST" action="{{ route('mechanic.jobs.tasks.update', [$booking, $task]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ ServiceBookingTask::STATUS_DONE }}">
                                            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i>Selesai</button>
                                        </form>
                                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#lewati-{{ $task->id }}" aria-expanded="{{ $bag->any() ? 'true' : 'false' }}"
                                                aria-controls="lewati-{{ $task->id }}">Lewati</button>
                                    @else
                                        <form method="POST" action="{{ route('mechanic.jobs.tasks.update', [$booking, $task]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ ServiceBookingTask::STATUS_PENDING }}">
                                            <button type="submit" class="btn btn-link btn-sm">Batalkan centang</button>
                                        </form>
                                    @endif
                                </div>

                                @if ($task->isPending())
                                    <form method="POST" action="{{ route('mechanic.jobs.tasks.update', [$booking, $task]) }}"
                                          @class(['collapse w-100', 'show' => $bag->any()]) id="lewati-{{ $task->id }}" novalidate>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ ServiceBookingTask::STATUS_SKIPPED }}">
                                        <label for="note-{{ $task->id }}" class="form-label small">Alasan dilewati</label>
                                        <div class="d-flex gap-2">
                                            <input type="text" id="note-{{ $task->id }}" name="note" maxlength="{{ ServiceBookingTask::MAX_NOTE_LENGTH }}"
                                                   value="{{ $bag->any() ? old('note') : '' }}" placeholder="Mis. suku cadang belum tersedia"
                                                   @class(['form-control', 'is-invalid' => $bag->has('note')])>
                                            <button type="submit" class="btn btn-outline-secondary">Simpan</button>
                                        </div>
                                        @if ($bag->has('note'))
                                            <div class="invalid-feedback d-block">{{ $bag->first('note') }}</div>
                                        @endif
                                    </form>
                                @endif
                            @endif
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Checklist kosong. Minta admin menambahkan pekerjaan, atau tulis hasil kerja di Temuan.</p>
                    @endforelse
                </div>
            </div>

            @if (in_array($booking->status, [ServiceBooking::STATUS_IN_PROGRESS, ServiceBooking::STATUS_COMPLETED], true))
                <div class="card" id="temuan">
                    <div class="card-body p-4">
                        <h3 class="admin-card-title">Temuan & Rekomendasi</h3>
                        <form method="POST" action="{{ route('mechanic.jobs.findings', $booking) }}" novalidate>
                            @csrf
                            @method('PATCH')
                            <label for="findings" class="visually-hidden">Temuan & rekomendasi</label>
                            <textarea id="findings" name="findings" rows="3" maxlength="{{ ServiceBooking::MAX_FINDINGS_LENGTH }}"
                                      placeholder="Mis. kampas rem depan tinggal 20%, disarankan ganti bulan depan."
                                      @class(['form-control mb-2', 'is-invalid' => $findingsErrors->has('findings')])>{{ $findingsErrors->any() ? old('findings') : $booking->findings }}</textarea>
                            @if ($findingsErrors->has('findings'))
                                <div class="invalid-feedback d-block mb-2">{{ $findingsErrors->first('findings') }}</div>
                            @endif
                            <div class="form-text mb-2">Dilihat customer di menu Servis Saya.</div>
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-check-lg"></i>Simpan Temuan</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-4">
            <div class="card admin-sticky-panel">
                <div class="card-body p-4">
                    <dl class="admin-detail-list mb-3">
                        <dt>Plat nomor</dt>
                        <dd class="fw-semibold">{{ $booking->plate_number }}</dd>

                        <dt>Layanan</dt>
                        <dd>{{ $booking->service->name }}@if ($booking->service->duration_minutes), sekitar {{ $booking->service->duration_minutes }} menit @endif</dd>

                        <dt>Jadwal</dt>
                        <dd>{{ $booking->preferred_date->translatedFormat('l, d M Y') }} pukul {{ $booking->timeLabel() }}</dd>

                        <dt>Kilometer</dt>
                        <dd>{{ $booking->mileage !== null ? number_format($booking->mileage, 0, ',', '.').' km' : '-' }}</dd>

                        <dt>Customer</dt>
                        <dd>{{ $booking->user->name }}</dd>

                        <dt>Keluhan</dt>
                        <dd>{{ $booking->complaint ?: '-' }}</dd>
                    </dl>

                    @if ($booking->status === ServiceBooking::STATUS_CONFIRMED)
                        <form method="POST" action="{{ route('mechanic.jobs.start', $booking) }}"
                              data-confirm="Mulai kerjakan servis {{ $booking->plate_number }}? Customer akan diberi tahu.">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-accent w-100" @disabled(! $booking->canBeStarted())>
                                <i class="bi bi-play-fill"></i>Mulai Kerjakan
                            </button>
                        </form>
                        @unless ($booking->canBeStarted())
                            <p class="small text-muted mt-2 mb-0">Bisa dimulai pada {{ $booking->preferred_date->translatedFormat('d M Y') }}.</p>
                        @endunless
                    @elseif ($working)
                        <form method="POST" action="{{ route('mechanic.jobs.finish', $booking) }}"
                              data-confirm="Tandai servis {{ $booking->plate_number }} selesai? Customer akan diberi tahu.">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-accent w-100" @disabled($booking->hasPendingTasks())>
                                <i class="bi bi-check2-all"></i>Servis Selesai
                            </button>
                        </form>
                        @if ($booking->hasPendingTasks())
                            <p class="small text-muted mt-2 mb-0">Centang atau lewati semua pekerjaan dulu.</p>
                        @endif
                    @elseif ($booking->status === ServiceBooking::STATUS_COMPLETED)
                        <p class="small mb-0"><i class="bi bi-check-circle text-success me-1"></i>Selesai {{ $booking->finished_at?->translatedFormat('d M Y H:i') }}.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
