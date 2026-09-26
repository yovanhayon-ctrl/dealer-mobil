@extends('layouts.admin')

@section('title', 'Galeri Mobil')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Mobil', 'url' => route('admin.cars.index')],
        ['label' => $car->name.' '.$car->year, 'url' => route('admin.cars.edit', $car)],
        ['label' => 'Galeri'],
    ]])
@endsection

@php
    $altText = $car->name.' '.$car->year;
    $isFull = $car->images->count() >= $max;
    $uploadErrors = collect($errors->get('images'))->merge(collect($errors->get('images.*'))->flatten())->unique();
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="fw-semibold">{{ $altText }}</div>
            <div class="small text-muted">{{ $car->images->count() }} dari {{ $max }} gambar</div>
        </div>
        <a href="{{ route('admin.cars.edit', $car) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>Kembali ke Edit Mobil
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('admin.cars.images.store', $car) }}" enctype="multipart/form-data" novalidate>
                @csrf

                <label for="images" class="form-label">Unggah Gambar</label>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <input type="file" id="images" name="images[]" multiple
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                           @disabled($isFull)
                           @class(['form-control', 'is-invalid' => $uploadErrors->isNotEmpty()])>
                    <button type="submit" class="btn btn-primary text-nowrap" @disabled($isFull)>
                        <i class="bi bi-upload"></i>Unggah
                    </button>
                </div>

                @if ($uploadErrors->isNotEmpty())
                    <div class="invalid-feedback d-block">
                        @foreach ($uploadErrors as $message)
                            <div>{{ $message }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="form-text">
                    @if ($isFull)
                        Galeri sudah penuh. Hapus gambar lain untuk mengunggah yang baru.
                    @else
                        JPG, JPEG, PNG, atau WEBP · maks. 2 MB per file · minimal 600×400 piksel ·
                        bisa pilih beberapa file (sisa {{ $max - $car->images->count() }} slot).
                        Gambar pertama otomatis menjadi gambar utama.
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if ($car->images->isEmpty())
            <x-empty-state icon="bi-images" title="Belum ada gambar"
                           message="Unggah foto mobil agar tampil menarik di katalog." />
        @else
            <div class="card-body p-3 p-md-4">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
                    @foreach ($car->images as $image)
                        <div class="col">
                            <div @class(['card h-100 car-image-card', 'border-primary' => $image->is_primary])>
                                <div class="position-relative">
                                    {{-- Badge di luar .ratio karena setiap anak .ratio dibuat memenuhi kotak. --}}
                                    <div class="ratio ratio-16x9">
                                        <img src="{{ $image->url }}" alt="{{ $altText }}" loading="lazy"
                                             class="object-fit-cover rounded-top">
                                    </div>
                                    @if ($image->is_primary)
                                        <span class="badge bg-primary car-image-badge"><i class="bi bi-star-fill me-1"></i>Utama</span>
                                    @endif
                                </div>
                                <div class="card-body p-2 d-flex flex-wrap align-items-center gap-1">
                                    <span class="small text-muted me-auto">#{{ $loop->iteration }}</span>

                                    <form method="POST" action="{{ route('admin.cars.images.move', [$car, $image, 'naik']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Naikkan urutan"
                                                aria-label="Naikkan urutan gambar #{{ $loop->iteration }}" @disabled($loop->first)>
                                            <i class="bi bi-arrow-up m-0"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.cars.images.move', [$car, $image, 'turun']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Turunkan urutan"
                                                aria-label="Turunkan urutan gambar #{{ $loop->iteration }}" @disabled($loop->last)>
                                            <i class="bi bi-arrow-down m-0"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus gambar"
                                            aria-label="Hapus gambar #{{ $loop->iteration }}"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-delete-url="{{ route('admin.cars.images.destroy', [$car, $image]) }}"
                                            data-delete-name="#{{ $loop->iteration }}{{ $image->is_primary ? ' (gambar utama)' : '' }}">
                                        <i class="bi bi-trash m-0"></i>
                                    </button>

                                    @unless ($image->is_primary)
                                        <form method="POST" action="{{ route('admin.cars.images.primary', [$car, $image]) }}" class="w-100">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="bi bi-star"></i>Jadikan Utama
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <x-delete-modal entity="gambar" />
@endsection
