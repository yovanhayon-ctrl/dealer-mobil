@extends('layouts.app')

@php
    $images = $car->images;
    $description = trim((string) $car->description);
    $metaDescription = $description !== ''
        ? \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish(strip_tags($description)), 155)
        : "{$title} ({$car->condition_label}), harga Rp ".number_format($finalPrice, 0, ',', '.').' di '.config('dealer.name').'.';
    $detailUrl = route('cars.show', $car);
    $purchaseRoute = Route::has('purchase-requests.create');
    $testDriveRoute = Route::has('test-drives.create');
    $creditUrl = Route::has('credit.index') ? route('credit.index', ['mobil' => $car->slug]) : null;
@endphp

@section('title', $title)
@section('meta_description', $metaDescription)

@push('meta')
    <link rel="canonical" href="{{ $detailUrl }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $detailUrl }}">
    @if ($images->isNotEmpty())
        <meta property="og:image" content="{{ $images->first()->url }}">
    @endif
@endpush

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [
            ['label' => 'Mobil', 'url' => route('cars.index')],
            ['label' => $title],
        ]])

        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                @if ($images->isEmpty())
                    <div class="car-gallery-empty rounded-4" role="img" aria-label="Belum ada foto {{ $title }}">
                        <i class="bi bi-car-front"></i>
                    </div>
                @else
                    <div id="carGallery" class="carousel slide car-gallery">
                        <div class="position-relative">
                            <div class="carousel-inner rounded-4">
                                @foreach ($images as $image)
                                    <div @class(['carousel-item', 'active' => $loop->first])>
                                        <img src="{{ $image->url }}" alt="{{ $title }} — foto {{ $loop->iteration }} dari {{ $loop->count }}"
                                             class="car-gallery-img" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                                    </div>
                                @endforeach
                            </div>

                            @if ($images->count() > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#carGallery" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Foto sebelumnya</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carGallery" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Foto berikutnya</span>
                                </button>
                            @endif
                        </div>

                        @if ($images->count() > 1)
                            {{-- Thumbnail = indikator carousel: status aktif diatur Bootstrap, tanpa JS tambahan. --}}
                            <div class="carousel-indicators car-gallery-thumbs">
                                @foreach ($images as $image)
                                    <button type="button" data-bs-target="#carGallery" data-bs-slide-to="{{ $loop->index }}"
                                            aria-label="Tampilkan foto {{ $loop->iteration }}"
                                            @if ($loop->first) class="active" aria-current="true" @endif>
                                        <img src="{{ $image->url }}" alt="" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="col-lg-5">
                <div class="car-detail-info">
                    <p class="text-muted mb-1">{{ $car->brand->name }} · {{ $car->category->name }}</p>
                    <h1 class="h2 mb-2">{{ $car->name }} {{ $car->year }}</h1>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span @class(['badge rounded-pill', 'badge-condition-new' => $car->isNew(), 'badge-condition-used border' => ! $car->isNew()])>{{ $car->condition_label }}</span>
                        @if ($car->hasPromoPrice())
                            <span class="badge rounded-pill badge-status-red"><i class="bi bi-percent"></i> Promo</span>
                        @endif
                        @unless ($car->inStock())
                            <x-status-badge status="out_of_stock" />
                        @endunless
                    </div>

                    <div class="mb-2">
                        @if ($car->hasPromoPrice())
                            <del class="price-old d-block"><x-price :amount="$car->price" /></del>
                        @endif
                        <x-price :amount="$finalPrice" class="price-final car-detail-price" />
                    </div>

                    <p class="mb-3">
                        @if ($car->inStock())
                            <i class="bi bi-box-seam me-1"></i>Stok: <span class="fw-semibold">{{ $car->stock }} unit</span>
                        @else
                            <i class="bi bi-x-circle me-1"></i><span class="fw-semibold">Stok habis</span>
                        @endif
                    </p>

                    @if ($car->activePromos->isNotEmpty())
                        <div class="promo-box rounded-3 p-3 mb-3">
                            <p class="fw-semibold small text-uppercase mb-2"><i class="bi bi-percent me-1"></i>Promo berlaku</p>
                            <ul class="list-unstyled small mb-0">
                                @foreach ($car->activePromos as $promo)
                                    <li @class(['mb-2' => ! $loop->last])>
                                        <span class="fw-semibold">{{ $promo->title }}</span>
                                        @if ($bestPromo?->is($promo))
                                            <span class="badge rounded-pill badge-status-red ms-1">Dipakai di harga</span>
                                        @endif
                                        <br>
                                        <span class="text-muted">
                                            {{ $promo->start_date->translatedFormat('d M Y') }} – {{ $promo->end_date->translatedFormat('d M Y') }}
                                            @if ($promo->discount_amount)
                                                · hemat <x-price :amount="$promo->discount_amount" />
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="d-grid gap-2">
                        @if ($purchaseRoute)
                            @if ($car->canBePurchased())
                                <a href="{{ route('purchase-requests.create', $car) }}" class="btn btn-accent btn-lg">
                                    <i class="bi bi-cart-check"></i>Ajukan Pembelian
                                </a>
                            @else
                                <span class="btn btn-accent btn-lg disabled" aria-disabled="true">
                                    <i class="bi bi-cart-x"></i>Ajukan Pembelian
                                </span>
                                <p class="small text-muted mb-0">Stok habis, pengajuan belum bisa dilakukan.</p>
                            @endif
                        @endif

                        @if ($testDriveRoute)
                            @if (auth()->user()?->isAdmin())
                                <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Booking test drive hanya untuk akun customer.</p>
                            @elseif ($car->canBeTestDriven())
                                <a href="{{ route('test-drives.create', ['mobil' => $car->slug]) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-calendar-check"></i>Booking Test Drive
                                </a>
                            @else
                                <span class="btn btn-outline-primary disabled" aria-disabled="true">
                                    <i class="bi bi-calendar-x"></i>Booking Test Drive
                                </span>
                                <p class="small text-muted mb-0">Unit bekas ini sudah terjual, test drive tidak tersedia.</p>
                            @endif
                        @endif

                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success">
                                <i class="bi bi-whatsapp"></i>Tanya via WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-lg-7">
                <section class="card mb-4" aria-labelledby="spec-title">
                    <div class="card-body p-3 p-lg-4">
                        <h2 id="spec-title" class="h5 mb-3">Spesifikasi</h2>
                        <table class="table spec-table mb-0">
                            <tbody>
                                <tr><th scope="row">Merek</th><td>{{ $car->brand->name }}</td></tr>
                                <tr><th scope="row">Kategori</th><td>{{ $car->category->name }}</td></tr>
                                <tr><th scope="row">Kondisi</th><td>{{ $car->condition_label }}</td></tr>
                                <tr><th scope="row">Tahun</th><td>{{ $car->year }}</td></tr>
                                <tr><th scope="row">Transmisi</th><td>{{ $car->transmission_label }}</td></tr>
                                <tr><th scope="row">Bahan bakar</th><td>{{ $car->fuel_type_label }}</td></tr>
                                <tr>
                                    <th scope="row">Kapasitas mesin</th>
                                    <td>{{ $car->engine_cc ? number_format($car->engine_cc, 0, ',', '.').' cc' : '–' }}</td>
                                </tr>
                                <tr><th scope="row">Jumlah kursi</th><td>{{ $car->seats }} kursi</td></tr>
                                <tr><th scope="row">Warna</th><td>{{ $car->color ?: '–' }}</td></tr>
                                @unless ($car->isNew())
                                    <tr><th scope="row">Kilometer</th><td>{{ number_format($car->mileage, 0, ',', '.') }} km</td></tr>
                                @endunless
                                <tr><th scope="row">Stok</th><td>{{ $car->inStock() ? "{$car->stock} unit" : 'Habis' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($description !== '')
                    <section class="card mb-4" aria-labelledby="description-title">
                        <div class="card-body p-3 p-lg-4">
                            <h2 id="description-title" class="h5 mb-3">Deskripsi</h2>
                            {{-- Di-escape dulu, baru baris baru diubah jadi <br>: tidak ada HTML mentah. --}}
                            <p class="mb-0 car-description">{!! nl2br(e($description)) !!}</p>
                        </div>
                    </section>
                @endif
            </div>

            <div class="col-lg-5">
                <section class="card" aria-labelledby="credit-title">
                    <div class="card-body p-3 p-lg-4">
                        <h2 id="credit-title" class="h5 mb-2">Ringkasan Cicilan</h2>
                        <p class="small text-muted mb-3">
                            Harga <x-price :amount="$finalPrice" />, DP minimum {{ config('credit.dp_min') }}%
                            <x-price :amount="$minDownPayment" />.
                        </p>
                        <table class="table table-sm credit-table mb-2">
                            <thead>
                                <tr>
                                    <th scope="col">Tenor</th>
                                    <th scope="col">Bunga flat/thn</th>
                                    <th scope="col" class="text-end">Cicilan/bln</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($installments as $installment)
                                    <tr>
                                        <td>{{ $installment['tenor_months'] }} bln</td>
                                        <td>{{ str_replace('.', ',', (string) $installment['interest_rate']) }}%</td>
                                        <td class="text-end fw-semibold"><x-price :amount="$installment['monthly_installment']" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="small text-muted mb-0">Perkiraan dengan bunga flat; angka final mengikuti persetujuan leasing.</p>
                        @if ($creditUrl)
                            <a href="{{ $creditUrl }}" class="btn btn-outline-primary btn-sm mt-3">
                                <i class="bi bi-calculator"></i>Hitung simulasi sendiri
                            </a>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        @if ($similarCars->isNotEmpty())
            <section class="mt-5" aria-labelledby="similar-title">
                <h2 id="similar-title" class="h4 mb-3">Mobil Serupa</h2>
                <div class="row g-4">
                    @foreach ($similarCars as $similarCar)
                        <div class="col-12 col-sm-6 col-lg-3">
                            <x-car-card :car="$similarCar" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
