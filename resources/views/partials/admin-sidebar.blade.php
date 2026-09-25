<aside class="admin-sidebar p-3">
    <a href="{{ route('admin.dashboard') }}" class="d-block text-white text-decoration-none fw-bold font-heading fs-5 mb-4 px-2">
        <i class="bi bi-car-front-fill text-accent"></i> {{ config('dealer.name') }}
    </a>

    <nav class="nav flex-column gap-1">
        <a class="nav-link @if (request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2"></i>Dashboard
        </a>
        {{-- Menu mobil, merek, kategori, promo, test drive, pengajuan, pengguna, laporan menyusul. --}}

        <hr class="border-secondary">

        <a class="nav-link" href="{{ route('home') }}" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i>Lihat Website
        </a>
    </nav>
</aside>
