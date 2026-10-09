@extends('layouts.app')

@section('title', 'Bandingkan Mobil')

@php
    $max = \App\Support\CarComparison::MAX_CARS;
    $bestBadge = '<span class="badge rounded-pill badge-status-green ms-1">Terbaik</span>';
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Bandingkan Mobil']]])

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1">Bandingkan Mobil</h1>
                <p class="text-muted small mb-0">Pilih hingga {{ $max }} mobil dari katalog dengan tombol <strong>Bandingkan</strong>.</p>
            </div>
            @if ($rows->isNotEmpty())
                <div class="d-flex gap-2">
                    @if ($rows->count() < $max)
                        <a href="{{ route('cars.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-lg"></i>Tambah Mobil
                        </a>
                    @endif
                    <form method="POST" action="{{ route('compare.clear') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i>Kosongkan
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if ($rows->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-layout-three-columns" title="Belum ada mobil yang dibandingkan"
                               message="Tekan tombol Bandingkan pada kartu mobil untuk memilih 2–{{ $max }} mobil.">
                    <a href="{{ route('cars.index') }}" class="btn btn-primary btn-sm">Lihat Mobil</a>
                </x-empty-state>
            </div>
        @else
            @if ($rows->count() === 1)
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>Pilih minimal satu mobil lagi dari
                    <a href="{{ route('cars.index') }}" class="alert-link">katalog</a> untuk mulai membandingkan.
                </div>
            @endif

            <div class="card">
                <div class="table-responsive">
                    <table class="table compare-table align-middle mb-0">
                        <caption class="visually-hidden">Perbandingan spesifikasi mobil</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="compare-label"><span class="visually-hidden">Spesifikasi</span></th>
                                @foreach ($rows as $row)
                                    @php($car = $row['car'])
                                    <th scope="col" class="compare-col">
                                        @if ($car->primaryImage)
                                            <img src="{{ $car->primaryImage->url }}" alt="{{ $car->brand->name }} {{ $car->name }} {{ $car->year }}" class="compare-img mb-2" loading="lazy">
                                        @else
                                            <span class="compare-img car-card-img-empty mb-2" aria-hidden="true"><i class="bi bi-car-front"></i></span>
                                        @endif
                                        <a href="{{ route('cars.show', $car) }}" class="d-block text-reset fw-semibold">{{ $car->brand->name }} {{ $car->name }} {{ $car->year }}</a>
                                        <form method="POST" action="{{ route('compare.destroy', $car) }}" class="mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0" aria-label="Hapus {{ $car->name }} {{ $car->year }} dari perbandingan">
                                                <i class="bi bi-x-circle"></i>Hapus
                                            </button>
                                        </form>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row" class="compare-label">Harga</th>
                                @foreach ($rows as $row)
                                    <td>
                                        @if ($row['car']->hasPromoPrice())
                                            <del class="price-old small d-block"><x-price :amount="$row['car']->price" /></del>
                                        @endif
                                        <x-price :amount="$row['final_price']" class="fw-semibold" />
                                        @if ($best['price'] === $row['final_price'])
                                            {!! $bestBadge !!}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Cicilan mulai</th>
                                @foreach ($rows as $row)
                                    <td><x-price :amount="$row['installment']" />/bln</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Kondisi</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->condition_label }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Tahun</th>
                                @foreach ($rows as $row)
                                    <td>
                                        {{ $row['car']->year }}
                                        @if ($best['year'] === $row['car']->year)
                                            {!! $bestBadge !!}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Merek</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->brand->name }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Kategori</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->category->name }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Transmisi</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->transmission_label }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Bahan bakar</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->fuel_type_label }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Kapasitas mesin</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->engine_cc ? number_format($row['car']->engine_cc, 0, ',', '.').' cc' : '–' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Jumlah kursi</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->seats }} kursi</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Kilometer</th>
                                @foreach ($rows as $row)
                                    <td>
                                        @if ($row['car']->isNew())
                                            <span class="text-muted">– (baru)</span>
                                        @else
                                            {{ number_format($row['car']->mileage, 0, ',', '.') }} km
                                            @if ($best['mileage'] === $row['car']->mileage)
                                                {!! $bestBadge !!}
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Warna</th>
                                @foreach ($rows as $row)
                                    <td>{{ $row['car']->color ?: '–' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label">Stok</th>
                                @foreach ($rows as $row)
                                    <td>
                                        @if ($row['car']->inStock())
                                            {{ $row['car']->stock }} unit
                                        @else
                                            <x-status-badge status="out_of_stock" />
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="compare-label"><span class="visually-hidden">Aksi</span></th>
                                @foreach ($rows as $row)
                                    @php($car = $row['car'])
                                    <td>
                                        <div class="d-grid gap-2">
                                            @if (Route::has('purchase-requests.create') && $car->canBePurchased())
                                                <a href="{{ route('purchase-requests.create', $car) }}" class="btn btn-accent btn-sm">
                                                    <i class="bi bi-cart-check"></i>Ajukan
                                                </a>
                                            @endif
                                            @if (Route::has('test-drives.create') && $car->canBeTestDriven() && ! auth()->user()?->isAdmin())
                                                <a href="{{ route('test-drives.create', ['mobil' => $car->slug]) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-calendar-check"></i>Test Drive
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
