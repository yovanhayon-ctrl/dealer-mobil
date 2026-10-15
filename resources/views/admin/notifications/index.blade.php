@extends('layouts.admin')

@section('title', 'Notifikasi')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Notifikasi']]])
@endsection

{{-- Dipakai panel admin & mekanik: $routePrefix menentukan route buka / tandai dibaca. --}}
@php($routePrefix ??= 'admin.notifications')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">{{ $intro ?? 'Test drive, pengajuan, pembayaran, booking servis, dan ulasan dari customer sesuai menu Anda.' }}</p>
        @if ($hasUnread)
            <form method="POST" action="{{ route($routePrefix.'.read-all') }}" data-disable-on-submit>
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check2-all"></i>Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-bell" title="Belum ada notifikasi"
                           :message="$emptyMessage ?? 'Notifikasi muncul saat customer membuat atau membatalkan test drive, pengajuan pembelian, atau booking servis.'" />
        </div>
    @else
        <div class="list-group">
            @foreach ($notifications as $notification)
                @php($data = $notification->data)
                <a href="{{ route($routePrefix.'.open', $notification->id) }}"
                   @class(['list-group-item list-group-item-action notification-item p-3', 'is-unread' => $notification->unread()])>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span @class(['fw-semibold' => $notification->unread()])>{{ $data['title'] ?? 'Notifikasi' }}</span>
                        {{-- Status terkini datanya (lihat NotificationController::statusBadges). --}}
                        @foreach ($badges[$notification->id] ?? [] as $badge)
                            <x-status-badge :status="$badge['status']" :label="$badge['label']" />
                        @endforeach
                        @if ($notification->unread())
                            <span class="notification-new">Baru<span class="visually-hidden"> (belum dibaca)</span></span>
                        @endif
                        <small class="text-muted ms-auto" title="{{ $notification->created_at->translatedFormat('d M Y H:i') }}">
                            {{ $notification->created_at->diffForHumans() }}
                        </small>
                    </div>
                    @if (! empty($data['message']))
                        <p class="small mb-0">{{ $data['message'] }}</p>
                    @endif
                </a>
            @endforeach
        </div>

        @if ($notifications->hasPages())
            <div class="mt-4">
                {{ $notifications->onEachSide(1)->links('partials.pagination-links') }}
            </div>
        @endif
    @endif
@endsection
