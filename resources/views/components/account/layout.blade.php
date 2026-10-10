{{--
    Tata letak halaman akun customer: menu akun (samping di desktop, tab geser di HP),
    kepala halaman (judul, ringkasan, tombol utama), slot filter, lalu isi.
    <x-account.layout title="Pengajuan Saya" active="account.purchase-requests.*" summary="3 pengajuan">
        <x-slot:actions>…</x-slot:actions>
        <x-slot:filters>…</x-slot:filters>
        … isi …
    </x-account.layout>
--}}
@props(['title', 'active', 'summary' => null])

@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();

    if (! $isAdmin) {
        // Satu query (subselect) untuk semua angka menu.
        $user->loadCount(['testDrives', 'purchaseRequests', 'serviceBookings', 'favoriteCars']);
    }

    $menu = array_filter([
        ['account.profile', 'account.profile*', 'bi-person', 'Profil', null],
        $isAdmin ? null : ['account.purchase-requests.index', 'account.purchase-requests.*|account.testimonials.*', 'bi-card-checklist', 'Pengajuan', $user->purchase_requests_count],
        $isAdmin ? null : ['account.test-drives.index', 'account.test-drives.*', 'bi-calendar-check', 'Test Drive', $user->test_drives_count],
        $isAdmin ? null : ['account.service-bookings.index', 'account.service-bookings.*', 'bi-wrench-adjustable', 'Servis', $user->service_bookings_count],
        $isAdmin ? null : ['account.favorites.index', 'account.favorites.*', 'bi-heart', 'Favorit', $user->favorite_cars_count],
        $isAdmin ? null : ['account.notifications.index', 'account.notifications.*', 'bi-bell', 'Notifikasi', null],
    ]);
@endphp

<div class="container py-4">
    @include('partials.public-breadcrumb', ['items' => [['label' => $title]]])

    <div class="row g-4">
        <aside class="col-lg-3" aria-label="Menu akun">
            <nav class="account-nav">
                @foreach ($menu as [$route, $pattern, $icon, $label, $count])
                    @continue(! Route::has($route))
                    @php($isActive = request()->routeIs(...explode('|', $pattern)))
                    <a href="{{ route($route) }}" @class(['account-nav-link', 'active' => $isActive]) @if ($isActive) aria-current="page" @endif>
                        <i class="bi {{ $icon }}"></i>
                        <span class="flex-grow-1">{{ $label }}</span>
                        @if ($count !== null)
                            <span class="account-nav-count">{{ $count }}</span>
                        @endif
                    </a>
                @endforeach
                @if ($isAdmin && Route::has('admin.dashboard'))
                    <a href="{{ route('admin.dashboard') }}" class="account-nav-link">
                        <i class="bi bi-speedometer2"></i><span class="flex-grow-1">Dashboard Admin</span>
                    </a>
                @endif
            </nav>
        </aside>

        <div class="col-lg-9 min-w-0">
            <div class="account-page-head">
                <div>
                    <h1 class="h3 mb-0">{{ $title }}</h1>
                    @if ($summary)
                        <p class="small text-muted mb-0">{{ $summary }}</p>
                    @endif
                </div>
                @isset($actions)
                    <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
                @endisset
            </div>

            @isset($filters)
                {{ $filters }}
            @endisset

            {{ $slot }}
        </div>
    </div>
</div>
