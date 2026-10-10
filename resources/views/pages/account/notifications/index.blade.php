@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <x-account.layout title="Notifikasi" active="account.notifications.*"
                      :summary="$notifications->total().' notifikasi'">
        @if ($hasUnread)
            <x-slot:actions>
                <form method="POST" action="{{ route('account.notifications.read-all') }}" data-disable-on-submit>
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-check2-all"></i>Tandai semua dibaca
                    </button>
                </form>
            </x-slot:actions>
        @endif

        @if ($notifications->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-bell" title="Belum ada notifikasi"
                               message="Kabar perubahan status test drive, pengajuan pembelian, dan booking servis Anda akan muncul di sini." />
            </div>
        @else
            <div class="list-group">
                @foreach ($notifications as $notification)
                    @php($data = $notification->data)
                    <a href="{{ route('account.notifications.open', $notification->id) }}"
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
                            <p class="small mb-1">{{ $data['message'] }}</p>
                        @endif
                        @if (! empty($data['admin_note']))
                            <p class="small text-muted mb-0"><span class="fw-semibold">Catatan dealer:</span> {{ $data['admin_note'] }}</p>
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
    </x-account.layout>
@endsection
