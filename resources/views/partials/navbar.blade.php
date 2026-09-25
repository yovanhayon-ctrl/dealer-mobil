@php
    // Menu hanya tampil jika route-nya sudah dibuat (halaman menyusul di phase berikutnya).
    $menu = [
        ['route' => 'home', 'label' => 'Beranda'],
        ['route' => 'cars.index', 'label' => 'Katalog'],
        ['route' => 'promos.index', 'label' => 'Promo'],
        ['route' => 'credit.index', 'label' => 'Simulasi Kredit'],
        ['route' => 'test-drives.create', 'label' => 'Test Drive'],
        ['route' => 'about', 'label' => 'Tentang Kami'],
        ['route' => 'contact', 'label' => 'Kontak'],
    ];
@endphp

<nav class="navbar navbar-expand-lg navbar-dark navbar-dealer sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <i class="bi bi-car-front-fill"></i> {{ config('dealer.name') }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @foreach ($menu as $item)
                    @if (Route::has($item['route']))
                        <li class="nav-item">
                            <a class="nav-link @if (request()->routeIs($item['route'])) active @endif"
                               href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <div class="d-flex align-items-lg-center gap-2">
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
                            @if (Route::has('account.requests'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.requests') }}">
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
