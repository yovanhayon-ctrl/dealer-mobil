@extends('layouts.app')

@section('title', 'Test Drive Saya')

@section('content')
    <x-account.layout title="Test Drive Saya" active="account.test-drives.*"
                      :summary="$statusCounts['semua'].' booking test drive'">
        <x-slot:actions>
            <a href="{{ route('test-drives.create') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-plus-lg"></i>Booking Test Drive
            </a>
        </x-slot:actions>

        @if ($statusCounts['semua'] > 0)
            <x-slot:filters>
                <x-account.status-filter route="account.test-drives.index" :counts="$statusCounts" :active="$statusGroup" />
            </x-slot:filters>
        @endif

        @if ($testDrives->isEmpty())
            <div class="card">
                @if ($statusGroup)
                    <x-empty-state icon="bi-funnel" title="Tidak ada test drive dengan status ini"
                                   message="Pilih status lain atau tampilkan semua test drive.">
                        <a href="{{ route('account.test-drives.index') }}" class="btn btn-primary btn-sm">Tampilkan Semua</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="bi-calendar-check" title="Belum ada test drive"
                                   message="Booking test drive untuk mencoba mobil yang Anda minati.">
                        <a href="{{ route('test-drives.create') }}" class="btn btn-primary btn-sm">Booking Test Drive</a>
                    </x-empty-state>
                @endif
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($testDrives as $testDrive)
                    @php($car = $testDrive->car)
                    @php($carTitle = "{$car->brand->name} {$car->name} {$car->year}")
                    <article class="card test-drive-item account-item" id="test-drive-{{ $testDrive->id }}">
                        <div class="card-body p-3">
                            <div class="account-item-head">
                                @if ($car->primaryImage)
                                    <img src="{{ $car->primaryImage->thumb_url }}" alt="{{ $carTitle }}" class="test-drive-car-thumb" loading="lazy">
                                @else
                                    <span class="test-drive-car-thumb car-card-img-empty" aria-hidden="true"><i class="bi bi-car-front"></i></span>
                                @endif

                                <div class="min-w-0 flex-grow-1">
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
                                    <p class="small mb-0">
                                        <i class="bi bi-calendar-event me-1"></i>{{ $testDrive->preferred_date->translatedFormat('l, d M Y') }}
                                        <i class="bi bi-clock ms-2 me-1"></i>{{ $testDrive->timeLabel() }} WIB
                                    </p>
                                    <p class="small text-muted mb-0">Dibuat {{ $testDrive->created_at->translatedFormat('d M Y H:i') }}</p>
                                </div>
                            </div>

                            @if ($testDrive->admin_note)
                                <div class="admin-note-box small rounded-3 p-2 mt-3">
                                    <span class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Catatan dealer:</span>
                                    {{ $testDrive->admin_note }}
                                </div>
                            @endif

                            @if ($testDrive->notes)
                                <details class="account-item-details">
                                    <summary>Lihat rincian</summary>
                                    <p class="small text-muted pt-2 mb-0"><span class="fw-semibold">Catatan Anda:</span> {{ $testDrive->notes }}</p>
                                </details>
                            @endif

                            @if ($testDrive->canBeCancelledByCustomer())
                                <div class="account-item-actions">
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
    </x-account.layout>
@endsection
