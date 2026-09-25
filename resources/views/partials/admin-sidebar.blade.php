@php
    // Menu hanya tampil jika route-nya sudah terdaftar (lihat Route::has di bawah).
    $menu = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Mobil', 'icon' => 'bi-car-front', 'route' => 'admin.cars.index', 'active' => 'admin.cars.*'],
        ['label' => 'Merek', 'icon' => 'bi-tags', 'route' => 'admin.brands.index', 'active' => 'admin.brands.*'],
        ['label' => 'Kategori', 'icon' => 'bi-grid', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*'],
        ['label' => 'Promo', 'icon' => 'bi-percent', 'route' => 'admin.promos.index', 'active' => 'admin.promos.*'],
        ['label' => 'Test Drive', 'icon' => 'bi-calendar-check', 'route' => 'admin.test-drives.index', 'active' => 'admin.test-drives.*'],
        ['label' => 'Pengajuan', 'icon' => 'bi-file-earmark-text', 'route' => 'admin.purchase-requests.index', 'active' => 'admin.purchase-requests.*'],
        ['label' => 'Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index', 'active' => 'admin.users.*'],
        ['label' => 'Laporan', 'icon' => 'bi-bar-chart', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*'],
    ];
@endphp

<div class="admin-sidebar">
    {{-- offcanvas-lg: menu tetap di layar besar, jadi offcanvas di bawah breakpoint lg. --}}
    <aside class="offcanvas-lg offcanvas-start admin-sidebar-panel" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
        <div class="offcanvas-header">
            <span class="offcanvas-title text-white fw-bold font-heading fs-5" id="adminSidebarLabel">
                <i class="bi bi-car-front-fill text-accent"></i> {{ config('dealer.name') }}
            </span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Tutup"></button>
        </div>

        <div class="offcanvas-body flex-column p-3">
            <a href="{{ route('admin.dashboard') }}" class="d-none d-lg-block text-white text-decoration-none fw-bold font-heading fs-5 mb-4 px-2">
                <i class="bi bi-car-front-fill text-accent"></i> {{ config('dealer.name') }}
            </a>

            <nav class="nav flex-column gap-1" aria-label="Menu admin">
                @foreach ($menu as $item)
                    @continue(! Route::has($item['route']))

                    @php($isActive = request()->routeIs($item['active']))
                    <a @class(['nav-link', 'active' => $isActive]) href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }}"></i>{{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </aside>
</div>
