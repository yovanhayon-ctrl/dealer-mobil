@extends('layouts.admin')

@section('title', 'Notifikasi')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Notifikasi']]])
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">Test drive, pengajuan, dan booking servis baru atau yang dibatalkan customer.</p>
        @if ($hasUnread)
            <form method="POST" action="{{ route('admin.notifications.read-all') }}" data-disable-on-submit>
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
                           message="Notifikasi muncul saat customer membuat atau membatalkan test drive, pengajuan pembelian, atau booking servis." />
        </div>
    @else
        <div class="list-group">
            @foreach ($notifications as $notification)
                @php($data = $notification->data)
                <a href="{{ route('admin.notifications.open', $notification->id) }}"
                   @class(['list-group-item list-group-item-action notification-item p-3', 'is-unread' => $notification->unread()])>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span @class(['fw-semibold' => $notification->unread()])>{{ $data['title'] ?? 'Notifikasi' }}</span>
                        @isset($data['status'])
                            <x-status-badge :status="$data['status']" />
                        @endisset
                        @if ($notification->unread())
                            <span class="visually-hidden">(belum dibaca)</span>
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
