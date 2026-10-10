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
    </x-admin.detail-header>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <dl class="admin-detail-list">
                        <dt>Jadwal</dt>
                        <dd>{{ $testDrive->preferred_date->translatedFormat('l, d F Y') }} · {{ $testDrive->timeLabel() }} WIB</dd>

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
                        'noteHelp' => 'Wajib diisi saat membatalkan (alasan untuk customer). Maksimal 1000 karakter.',
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
