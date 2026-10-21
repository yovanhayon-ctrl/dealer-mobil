@php
    // Menu hanya tampil jika route-nya terdaftar dan user punya hak aksesnya ('can' = User::PERMISSIONS, sama dengan route).
    // Mekanik: panel sendiri (Pekerjaan Saya).
    $panelUser = auth()->user();
    $mechanicMenu = [
        ['label' => 'Pekerjaan Saya', 'icon' => 'bi-wrench-adjustable', 'route' => 'mechanic.jobs.index', 'active' => 'mechanic.jobs.*'],
        ['label' => 'Notifikasi', 'icon' => 'bi-bell', 'route' => 'mechanic.notifications.index', 'active' => 'mechanic.notifications.*', 'badge' => $adminUnreadNotifications ?? 0],
        ['label' => 'Layanan Servis', 'icon' => 'bi-tools', 'route' => 'mechanic.services.index', 'active' => 'mechanic.services.*'],
        ['label' => 'Rating Saya', 'icon' => 'bi-star', 'route' => 'mechanic.ratings.index', 'active' => 'mechanic.ratings.*'],
        ['label' => 'Profil', 'icon' => 'bi-person', 'route' => 'account.profile', 'active' => 'account.profile*'],
    ];
    $menu = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Notifikasi', 'icon' => 'bi-bell', 'route' => 'admin.notifications.index', 'active' => 'admin.notifications.*', 'badge' => $adminUnreadNotifications ?? 0],
        ['label' => 'Mobil', 'icon' => 'bi-car-front', 'route' => 'admin.cars.index', 'active' => 'admin.cars.*', 'can' => 'cars.view'],
        ['label' => 'Merek', 'icon' => 'bi-tags', 'route' => 'admin.brands.index', 'active' => 'admin.brands.*', 'can' => 'catalog.manage'],
        ['label' => 'Kategori', 'icon' => 'bi-grid', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'can' => 'catalog.manage'],
        ['label' => 'Promo', 'icon' => 'bi-percent', 'route' => 'admin.promos.index', 'active' => 'admin.promos.*', 'can' => 'promos.view'],
        ['label' => 'Test Drive', 'icon' => 'bi-calendar-check', 'route' => 'admin.test-drives.index', 'active' => 'admin.test-drives.*', 'can' => 'test-drives.view'],
        ['label' => 'Pengajuan', 'icon' => 'bi-file-earmark-text', 'route' => 'admin.purchase-requests.index', 'active' => 'admin.purchase-requests.*', 'can' => 'purchases.view'],
        ['label' => 'Booking Servis', 'icon' => 'bi-wrench-adjustable', 'route' => 'admin.service-bookings.index', 'active' => 'admin.service-bookings.*', 'can' => 'service-bookings.manage'],
        ['label' => 'Layanan Servis', 'icon' => 'bi-tools', 'route' => 'admin.services.index', 'active' => 'admin.services.*', 'can' => 'services.manage'],
        ['label' => 'Ulasan', 'icon' => 'bi-star', 'route' => 'admin.testimonials.index', 'active' => 'admin.testimonials.*', 'can' => 'testimonials.view'],
        ['label' => 'Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'can' => 'users.view'],
        ['label' => 'Laporan', 'icon' => 'bi-bar-chart', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'can' => 'reports.view'],
        ['label' => 'Staf', 'icon' => 'bi-person-badge', 'route' => 'admin.staff.index', 'active' => 'admin.staff.*', 'can' => 'staff.manage'],
        ['label' => 'Pengaturan', 'icon' => 'bi-gear', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'can' => 'settings.manage'],
    ];

    $menu = $panelUser->isMechanic()
        ? $mechanicMenu
        : array_values(array_filter($menu, fn ($item) => ! isset($item['can']) || $panelUser->can($item['can'])));
@endphp

<div class="admin-sidebar">
    {{-- offcanvas-lg: menu tetap di layar besar, jadi offcanvas di bawah breakpoint lg. --}}
    <aside class="offcanvas-lg offcanvas-start admin-sidebar-panel" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
        <div class="offcanvas-header">
            <span class="offcanvas-title text-white fw-bold font-heading fs-5 d-inline-flex align-items-center gap-2" id="adminSidebarLabel">
                <x-brand-logo :size="32" /> {{ config('dealer.name') }}
            </span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Tutup"></button>
        </div>

        <div class="offcanvas-body flex-column p-3">
            <a href="{{ $panelUser->homeRoute() }}" class="d-none d-lg-flex align-items-center gap-2 text-white text-decoration-none fw-bold font-heading fs-5 mb-4 px-2">
                <x-brand-logo :size="36" /> {{ config('dealer.name') }}
            </a>

            <nav class="nav flex-column gap-1" aria-label="{{ $panelUser->isMechanic() ? 'Menu mekanik' : 'Menu admin' }}">
                @foreach ($menu as $item)
                    @continue(! Route::has($item['route']))

                    @php($isActive = request()->routeIs($item['active']))
                    <a @class(['nav-link', 'active' => $isActive]) href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }}"></i>{{ $item['label'] }}
                        @if (! empty($item['badge']))
                            <span class="badge rounded-pill bg-danger ms-auto">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}<span class="visually-hidden"> belum dibaca</span></span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
    </aside>
</div>
