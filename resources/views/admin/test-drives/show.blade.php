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
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="h5 mb-0">Test Drive #{{ $testDrive->id }}</h2>
                        <x-status-badge :status="$testDrive->status" />
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Jadwal</dt>
                        <dd class="col-sm-8">{{ $testDrive->preferred_date->translatedFormat('l, d F Y') }} · {{ $testDrive->timeLabel() }} WIB</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Mobil</dt>
                        <dd class="col-sm-8">
                            {{ $carLabel }}
                            <span class="text-muted">· {{ $car->condition_label }} · stok {{ $car->stock }}</span>
                            @unless ($car->is_active)
                                <x-status-badge status="inactive" class="ms-1" />
                            @endunless
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Customer</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('admin.users.show', $testDrive->user) }}">{{ $testDrive->user->name }}</a>
                            <span class="text-muted">· {{ $testDrive->user->email }}</span>
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Nomor HP</dt>
                        <dd class="col-sm-8">{{ $testDrive->phone }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Catatan customer</dt>
                        <dd class="col-sm-8">{{ $testDrive->notes ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Masuk</dt>
                        <dd class="col-sm-8 mb-0">{{ $testDrive->created_at->translatedFormat('d M Y H:i') }} WIB</dd>
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
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="h6 mb-3">Status & Catatan Admin</h3>
                    @include('admin.partials.status-form', [
                        'action' => route('admin.test-drives.update-status', $testDrive),
                        'currentLabel' => $testDrive->statusLabel(),
                        'allowed' => collect($testDrive->allowedTransitions())
                            ->mapWithKeys(fn ($status) => [$status => \App\Models\TestDrive::STATUS_LABELS[$status]])
                            ->all(),
                        'adminNote' => $testDrive->admin_note,
                        'noteHelp' => 'Wajib diisi saat membatalkan (alasan untuk customer). Maksimal 1000 karakter.',
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
