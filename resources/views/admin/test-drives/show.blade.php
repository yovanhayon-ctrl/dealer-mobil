@extends('layouts.admin')

@section('title', 'Detail Test Drive')

@php
    $car = $testDrive->car;
    $carLabel = "{$car->brand->name} {$car->name} {$car->year}";
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Test Drive', 'url' => route('admin.test-drives.index')],
        ['label' => $testDrive->user->name.' · '.$testDrive->preferred_date->translatedFormat('d M Y')],
    ]])
@endsection

@section('content')
    <x-admin.detail-header :title="'Test Drive #'.$testDrive->id" :subtitle="'Masuk '.$testDrive->created_at->translatedFormat('d F Y H:i').' WIB'"
                           :back-url="route('admin.test-drives.index')" back-label="Test Drive">
        <x-status-badge :status="$testDrive->status" />
        @if ($testDrive->isOverdue())
            <x-status-badge status="rejected" label="Lewat jadwal" />
        @endif
    </x-admin.detail-header>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <dl class="admin-detail-list">
                        <dt>Jadwal</dt>
                        <dd>
                            {{ $testDrive->preferred_date->translatedFormat('l, d F Y') }} · {{ $testDrive->timeLabel() }} WIB
                            @if ($testDrive->reminded_at)
                                <div class="small text-muted">Pengingat H-1 terkirim {{ $testDrive->reminded_at->translatedFormat('d M Y H:i') }}</div>
                            @endif
                            @if (in_array($testDrive->status, $testDrive::openScheduleStatuses(), true) && ! $testDrive->preferred_date->isPast() && ($waUrl = $testDrive->whatsappReminderUrl()))
                                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success py-0 mt-1">
                                    <i class="bi bi-whatsapp"></i>Ingatkan via WhatsApp
                                </a>
                            @endif
                        </dd>

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
                            <a href="{{ route('admin.users.show', $testDrive->user) }}">{{ $testDrive->user->name }}</a>
                            <span class="text-muted">· <a href="mailto:{{ $testDrive->user->email }}" class="text-muted">{{ $testDrive->user->email }}</a></span>
                        </dd>

                        <dt>Nomor HP</dt>
                        <dd>
                            <a href="tel:{{ $testDrive->phone }}">{{ $testDrive->phone }}</a>
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $testDrive->phone)) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-success py-0 ms-2"><i class="bi bi-whatsapp"></i>WhatsApp</a>
                        </dd>

                        <dt>Catatan customer</dt>
                        <dd>{{ $testDrive->notes ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($testDrive->isPending() && ! $car->isNew() && ! $car->inStock())
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Unit mobil bekas ini sudah terjual (stok 0). Test drive tidak bisa dikonfirmasi; batalkan dengan catatan untuk customer.
                </div>
            @endif
        </div>

        <div class="col-xl-5">
            <div class="card admin-sticky-panel">
                <div class="card-body p-4">
                    <h3 class="admin-card-title">Status & Catatan Admin</h3>
                    @include('admin.partials.status-form', [
                        'action' => route('admin.test-drives.update-status', $testDrive),
                        'currentLabel' => $testDrive->statusLabel(),
                        'allowed' => collect($testDrive->allowedTransitions())
                            ->mapWithKeys(fn ($status) => [$status => \App\Models\TestDrive::STATUS_LABELS[$status]])
                            ->all(),
                        'adminNote' => $testDrive->admin_note,
                        // Tidak datang: catatan untuk customer terisi otomatis.
                        'noteTemplates' => [\App\Models\TestDrive::STATUS_NO_SHOW => $testDrive->noShowNote()],
                        'noteHelp' => 'Wajib diisi saat membatalkan (alasan untuk customer). Maksimal 1000 karakter.',
                    ])
                </div>
            </div>
            @if ($testDrive->canBeRescheduled())
                @php($rescheduleErrors = $errors->getBag('reschedule'))
                <div class="card mt-4" id="jadwal-ulang">
                    <div class="card-body p-4">
                        <h3 class="admin-card-title">Jadwalkan Ulang</h3>
                        <p class="small text-muted">Untuk jadwal yang disepakati ulang dengan customer. Status menjadi <span class="fw-semibold">Dikonfirmasi</span> dan customer diberi tahu.</p>
                        <form method="POST" action="{{ route('admin.test-drives.reschedule', $testDrive) }}" novalidate data-disable-on-submit>
                            @csrf
                            @method('PATCH')
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label for="rs-date" class="form-label small">Tanggal baru</label>
                                    <input type="date" id="rs-date" name="preferred_date" required min="{{ \App\Http\Requests\TestDriveRequest::firstDate() }}" max="{{ \App\Http\Requests\TestDriveRequest::lastDate() }}"
                                           value="{{ $rescheduleErrors->any() ? old('preferred_date') : '' }}"
                                           @class(['form-control form-control-sm', 'is-invalid' => $rescheduleErrors->has('preferred_date')])>
                                    @if ($rescheduleErrors->has('preferred_date'))<div class="invalid-feedback">{{ $rescheduleErrors->first('preferred_date') }}</div>@endif
                                </div>
                                <div class="col-sm-6">
                                    <label for="rs-time" class="form-label small">Jam baru (WIB)</label>
                                    <select id="rs-time" name="preferred_time" required @class(['form-select form-select-sm', 'is-invalid' => $rescheduleErrors->has('preferred_time')])>
                                        @foreach (\App\Models\TestDrive::TIME_SLOTS as $slot)
                                            <option value="{{ $slot }}" @selected(old('preferred_time', $testDrive->timeLabel()) === $slot)>{{ $slot }}</option>
                                        @endforeach
                                    </select>
                                    @if ($rescheduleErrors->has('preferred_time'))<div class="invalid-feedback">{{ $rescheduleErrors->first('preferred_time') }}</div>@endif
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm mt-3"><i class="bi bi-calendar2-week"></i>Simpan Jadwal Baru</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
