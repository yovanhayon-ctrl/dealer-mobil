{{--
    Kartu filter daftar admin (GET). Isi slot = kolom filter (div.col…).
    Di HP filter dilipat di balik tombol "Filter (n aktif)"; di layar lebar selalu terbuka.
--}}
@props([
    'action',
    'resetUrl',
    'active' => 0,       // jumlah filter yang sedang aktif (badge di tombol HP)
    'showReset' => false,
])

<div {{ $attributes->class(['card mb-3 admin-filters']) }}>
    <div class="card-body p-3">
        <button class="btn btn-outline-secondary btn-sm w-100 d-lg-none admin-filters-toggle" type="button"
                data-bs-toggle="collapse" data-bs-target="#adminFilters"
                aria-expanded="{{ $active ? 'true' : 'false' }}" aria-controls="adminFilters">
            <i class="bi bi-funnel"></i>Filter
            @if ($active)
                <span class="badge rounded-pill bg-danger ms-1">{{ $active }} aktif</span>
            @endif
        </button>

        <form id="adminFilters" method="GET" action="{{ $action }}" role="search"
              @class(['row g-2 align-items-end admin-filters-form collapse', 'show' => $active])>
            {{ $slot }}
            <div class="col-12 col-lg-auto d-flex gap-2 admin-filters-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i>Terapkan</button>
                @if ($showReset)
                    <a href="{{ $resetUrl }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>
