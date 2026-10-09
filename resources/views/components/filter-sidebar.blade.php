{{--
    Form filter katalog (GET /mobil). Dipakai dua kali (sidebar desktop + offcanvas HP),
    jadi id input diberi awalan $idPrefix agar pasangan label/id tidak bentrok.
    <x-filter-sidebar :filters="$filters" :brands="$brands" :categories="$categories" :colors="$colors" id-prefix="d_" />
--}}
@props(['filters', 'brands', 'categories', 'colors' => collect(), 'idPrefix' => ''])

@php
    $id = fn (string $name) => "{$idPrefix}filter_{$name}";
    $digits = fn (?int $amount) => $amount === null ? '' : number_format($amount, 0, ',', '.');
    $selects = [
        'merek' => ['Merek', $brands->pluck('name', 'slug')->all(), $filters->brand?->slug],
        'kategori' => ['Kategori', $categories->pluck('name', 'slug')->all(), $filters->category?->slug],
        'kondisi' => ['Kondisi', \App\Models\Car::CONDITIONS, $filters->condition],
        'transmisi' => ['Transmisi', \App\Models\Car::TRANSMISSIONS, $filters->transmission],
        'bbm' => ['Bahan bakar', \App\Models\Car::FUEL_TYPES, $filters->fuelType],
        'warna' => ['Warna', $colors->mapWithKeys(fn (string $color) => [$color => $color])->all(), $filters->color],
        'kursi' => [
            'Jumlah kursi',
            collect(\App\Catalog\CarCatalogFilters::SEAT_OPTIONS)->mapWithKeys(fn (int $seats) => [$seats => "{$seats} kursi"])->all(),
            $filters->seats,
        ],
    ];
@endphp

<form method="GET" action="{{ route('cars.index') }}" {{ $attributes->class(['filter-form']) }} role="search" aria-label="Filter mobil">
    @if ($filters->sort !== \App\Catalog\CarCatalogFilters::DEFAULT_SORT)
        <input type="hidden" name="urut" value="{{ $filters->sort }}">
    @endif

    <div class="mb-3">
        <label for="{{ $id('q') }}" class="form-label small fw-semibold mb-1">Kata kunci</label>
        <input type="search" id="{{ $id('q') }}" name="q" value="{{ $filters->keyword }}" maxlength="{{ \App\Catalog\CarCatalogFilters::MAX_KEYWORD_LENGTH }}"
               class="form-control form-control-sm" placeholder="Nama mobil atau merek…">
    </div>

    @foreach ($selects as $name => [$label, $options, $selected])
        <div class="mb-3">
            <label for="{{ $id($name) }}" class="form-label small fw-semibold mb-1">{{ $label }}</label>
            <select id="{{ $id($name) }}" name="{{ $name }}" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach ($options as $value => $optionLabel)
                    <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        </div>
    @endforeach

    <fieldset class="mb-3">
        <legend class="form-label small fw-semibold mb-1">Harga (Rp)</legend>
        <div class="row g-2">
            <div class="col-6">
                <label for="{{ $id('harga_min') }}" class="visually-hidden">Harga minimum</label>
                <input type="text" inputmode="numeric" id="{{ $id('harga_min') }}" name="harga_min" value="{{ $digits($filters->minPrice) }}"
                       class="form-control form-control-sm" placeholder="Min">
            </div>
            <div class="col-6">
                <label for="{{ $id('harga_max') }}" class="visually-hidden">Harga maksimum</label>
                <input type="text" inputmode="numeric" id="{{ $id('harga_max') }}" name="harga_max" value="{{ $digits($filters->maxPrice) }}"
                       class="form-control form-control-sm" placeholder="Maks">
            </div>
        </div>
    </fieldset>

    <fieldset class="mb-3">
        <legend class="form-label small fw-semibold mb-1">Tahun</legend>
        <div class="row g-2">
            <div class="col-6">
                <label for="{{ $id('tahun_min') }}" class="visually-hidden">Tahun minimum</label>
                <input type="number" id="{{ $id('tahun_min') }}" name="tahun_min" value="{{ $filters->minYear }}"
                       min="{{ \App\Catalog\CarCatalogFilters::MIN_YEAR }}" max="{{ now()->year + 1 }}" class="form-control form-control-sm" placeholder="Dari">
            </div>
            <div class="col-6">
                <label for="{{ $id('tahun_max') }}" class="visually-hidden">Tahun maksimum</label>
                <input type="number" id="{{ $id('tahun_max') }}" name="tahun_max" value="{{ $filters->maxYear }}"
                       min="{{ \App\Catalog\CarCatalogFilters::MIN_YEAR }}" max="{{ now()->year + 1 }}" class="form-control form-control-sm" placeholder="Sampai">
            </div>
        </div>
    </fieldset>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="{{ $id('promo') }}" name="promo" value="1" @checked($filters->promoOnly)>
        <label class="form-check-label small" for="{{ $id('promo') }}">Hanya mobil promo</label>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i>Terapkan</button>
        <a href="{{ route('cars.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>
</form>
