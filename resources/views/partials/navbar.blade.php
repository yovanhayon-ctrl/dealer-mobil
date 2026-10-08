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
<nav class="navbar navbar-expand-xl navbar-dark navbar-dealer sticky-top" aria-label="Menu utama">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <i class="bi bi-car-front-fill"></i> {{ config('dealer.name') }}
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
                @if (auth()->user()?->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard Admin
                        </a>
                    </li>
                @endif
            </ul>

            <div class="d-flex align-items-xl-center gap-2">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-in-right"></i>Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-accent btn-sm">
                        <i class="bi bi-person-plus"></i>Daftar
                    </a>
                @else
                    <div class="dropdown">
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
