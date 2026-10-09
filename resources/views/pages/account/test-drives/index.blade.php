@extends('layouts.app')

@section('title', 'Riwayat Test Drive')

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Riwayat Test Drive']]])

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h3 mb-0">Riwayat Test Drive</h1>
            <a href="{{ route('test-drives.create') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-plus-lg"></i>Booking Baru
            </a>
        </div>

        @if ($testDrives->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-calendar-check" title="Belum ada test drive"
                               message="Booking test drive untuk mencoba mobil yang Anda minati.">
                    <a href="{{ route('test-drives.create') }}" class="btn btn-primary btn-sm">Booking Test Drive</a>
                </x-empty-state>
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($testDrives as $testDrive)
                    @php($car = $testDrive->car)
                    @php($carTitle = "{$car->brand->name} {$car->name} {$car->year}")
                    <article class="card test-drive-item" id="test-drive-{{ $testDrive->id }}">
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
                                    <x-status-badge :status="$testDrive->status" />
                                </div>
                                <p class="small mb-2">
                                    <i class="bi bi-calendar-event me-1"></i>{{ $testDrive->preferred_date->translatedFormat('l, d M Y') }}
                                    <i class="bi bi-clock ms-2 me-1"></i>{{ $testDrive->timeLabel() }} WIB
                                    <span class="text-muted ms-2">· dibuat {{ $testDrive->created_at->translatedFormat('d M Y H:i') }}</span>
                                </p>

                                @if ($testDrive->notes)
                                    <p class="small text-muted mb-2"><span class="fw-semibold">Catatan Anda:</span> {{ $testDrive->notes }}</p>
                                @endif

                                @if ($testDrive->admin_note)
                                    <div class="admin-note-box small rounded-3 p-2 mb-2">
                                        <span class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Catatan dealer:</span>
                                        {{ $testDrive->admin_note }}
                                    </div>
                                @endif
                            </div>

                            @if ($testDrive->canBeCancelledByCustomer())
                                <div class="flex-shrink-0">
                                    <form method="POST" action="{{ route('account.test-drives.cancel', $testDrive) }}"
                                          data-confirm="Batalkan test drive {{ $carTitle }} pada {{ $testDrive->preferred_date->translatedFormat('d M Y') }}?"
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

            @if ($testDrives->hasPages())
                <div class="mt-4">
                    {{ $testDrives->onEachSide(1)->links('partials.pagination-links') }}
                </div>
            @endif
        @endif
    </div>
@endsection
