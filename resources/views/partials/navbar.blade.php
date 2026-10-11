@php
    // Menu hanya tampil jika route-nya sudah dibuat (halaman menyusul di phase berikutnya).
    $menu = [
        ['route' => 'home', 'label' => 'Beranda', 'active' => 'home'],
        ['route' => 'cars.index', 'label' => 'Mobil', 'active' => 'cars.*'],
        ['route' => 'promos.index', 'label' => 'Promo', 'active' => 'promos.*'],
        ['route' => 'credit.index', 'label' => 'Simulasi Kredit', 'active' => 'credit.*'],
        ['route' => 'test-drives.create', 'label' => 'Test Drive', 'active' => 'test-drives.*'],
        ['route' => 'services.index', 'label' => 'Servis', 'active' => ['services.*', 'service-bookings.*']],
        ['route' => 'about', 'label' => 'Tentang Kami', 'active' => 'about'],
        ['route' => 'contact', 'label' => 'Kontak', 'active' => 'contact'],
    ];
@endphp

{{-- 8 menu publik: navbar baru melebar di layar ≥1200px (xl) agar label tidak terpotong. --}}
<nav class="navbar navbar-expand-xl navbar-dark navbar-dealer sticky-top" aria-label="Menu utama" data-navbar>
    <div class="container">
        <a class="navbar-brand d-inline-flex align-items-center gap-2" href="{{ route('home') }}">
            <x-brand-logo :size="36" /> {{ config('dealer.name') }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-xl-0 text-nowrap">
                @foreach ($menu as $item)
                    @continue(! Route::has($item['route']))

                    @php($isActive = request()->routeIs($item['active']))
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => $isActive]) href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>

            {{-- Area akun: di HP memenuhi lebar di bawah menu, di desktop sejajar di kanan. --}}
            <div class="navbar-actions">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-in-right"></i>Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-accent btn-sm">
                        <i class="bi bi-person-plus"></i>Daftar
                    </a>
                @else
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-accent btn-sm" title="Dashboard Admin">
                            <i class="bi bi-speedometer2"></i>Admin
                        </a>
                    @endif
                    @if (! auth()->user()->isAdmin() && Route::has('account.notifications.index'))
                        @php($unreadNotifications = auth()->user()->unreadNotifications()->count())
                        <a href="{{ route('account.notifications.index') }}"
                           @class(['btn btn-outline-light btn-sm notification-bell', 'active' => request()->routeIs('account.notifications.*')])
                           aria-label="Notifikasi{{ $unreadNotifications ? ", {$unreadNotifications} belum dibaca" : '' }}">
                            <i class="bi bi-bell{{ $unreadNotifications ? '-fill' : '' }}"></i>
                            @if ($unreadNotifications)
                                <span class="badge rounded-pill bg-danger" aria-hidden="true">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                            @endif
                        </a>
                    @endif
                    <div class="dropdown navbar-account">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>{{ auth()->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if (auth()->user()->isAdmin())
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
                                    </a>
                                </li>
                            @endif
                            @if (! auth()->user()->isAdmin() && Route::has('account.favorites.index'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.favorites.index') }}">
                                        <i class="bi bi-heart me-2"></i>Favorit Saya
                                    </a>
                                </li>
                            @endif
                            @if (! auth()->user()->isAdmin() && Route::has('account.test-drives.index'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.test-drives.index') }}">
                                        <i class="bi bi-calendar-check me-2"></i>Test Drive Saya
                                    </a>
                                </li>
                            @endif
                            @if (! auth()->user()->isAdmin() && Route::has('account.service-bookings.index'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.service-bookings.index') }}">
                                        <i class="bi bi-wrench-adjustable me-2"></i>Servis Saya
                                    </a>
                                </li>
                            @endif
                            @if (! auth()->user()->isAdmin() && Route::has('account.purchase-requests.index'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.purchase-requests.index') }}">
                                        <i class="bi bi-card-checklist me-2"></i>Pengajuan Saya
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('account.profile'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.profile') }}">
                                        <i class="bi bi-person-gear me-2"></i>Profil
                                    </a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</nav>
