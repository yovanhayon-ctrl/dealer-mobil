@csrf

@php
    $condition = old('vehicle_condition', $car->vehicle_condition ?? \App\Models\Car::CONDITION_NEW);
    $formatNumber = fn ($value) => filled($value) ? number_format((int) $value, 0, ',', '.') : '';
@endphp

<h2 class="h6 text-uppercase text-muted mb-3">Data Utama</h2>
<div class="row gx-3">
    <div class="col-md-6">
        <x-form.select name="brand_id" label="Merek" :options="$brands->pluck('name', 'id')" :value="$car->brand_id"
                       placeholder="Pilih merek" required />
    </div>
    <div class="col-md-6">
        <x-form.select name="category_id" label="Kategori" :options="$categories->pluck('name', 'id')" :value="$car->category_id"
                       placeholder="Pilih kategori" required />
    </div>
    <div class="col-12">
        <x-form.input name="name" label="Nama Mobil" :value="$car->name" required maxlength="150"
                      placeholder="Contoh: Avanza 1.5 G CVT"
                      :help="$car->exists ? 'Slug URL: '.$car->slug.' (tidak berubah saat diedit).' : 'Slug URL dibuat otomatis dari merek, nama, dan tahun.'" />
    </div>
</div>

<div class="row gx-3">
    <div class="col-md-4 mb-3">
        <span class="form-label d-block">Kondisi<span class="text-danger ms-1" aria-hidden="true">*</span></span>
        @foreach (\App\Models\Car::CONDITIONS as $value => $label)
            <div class="form-check form-check-inline">
                <input class="form-check-input @error('vehicle_condition') is-invalid @enderror" type="radio"
                       name="vehicle_condition" id="condition_{{ $value }}" value="{{ $value }}"
                       data-condition-toggle @checked($condition === $value)>
                <label class="form-check-label" for="condition_{{ $value }}">{{ $label }}</label>
            </div>
        @endforeach
        @error('vehicle_condition')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <x-form.input name="year" label="Tahun" type="number" :value="$car->year" required
                      min="1990" :max="now()->year + 1" />
    </div>
    <div @class(['col-md-4', 'd-none' => $condition !== \App\Models\Car::CONDITION_USED]) data-mileage-field>
        <x-form.input name="mileage" label="Kilometer" :value="$formatNumber($car->mileage ?: null)"
                      inputmode="numeric" placeholder="Contoh: 45.000" help="Wajib untuk mobil bekas." />
    </div>
</div>

<div class="row gx-3">
    <div class="col-md-6 mb-3">
        <label for="price" class="form-label">Harga<span class="text-danger ms-1" aria-hidden="true">*</span></label>
        <div class="input-group has-validation">
            <span class="input-group-text">Rp</span>
            <input type="text" id="price" name="price" inputmode="numeric" required
                   value="{{ old('price', $formatNumber($car->price)) }}" placeholder="285.000.000"
                   @class(['form-control', 'is-invalid' => $errors->has('price')])>
            @error('price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-text">Boleh memakai titik pemisah ribuan.</div>
    </div>
    <div class="col-md-6">
        <x-form.input name="stock" label="Stok" type="number" :value="$car->stock" required min="0" />
    </div>
</div>

<h2 class="h6 text-uppercase text-muted mt-2 mb-3">Spesifikasi</h2>
<div class="row gx-3">
    <div class="col-md-4">
        <x-form.select name="transmission" label="Transmisi" :options="\App\Models\Car::TRANSMISSIONS" :value="$car->transmission"
                       placeholder="Pilih transmisi" required />
    </div>
    <div class="col-md-4">
        <x-form.select name="fuel_type" label="Bahan Bakar" :options="\App\Models\Car::FUEL_TYPES" :value="$car->fuel_type"
                       placeholder="Pilih bahan bakar" required />
    </div>
    <div class="col-md-4">
        <x-form.input name="seats" label="Jumlah Kursi" type="number" :value="$car->seats" required min="2" max="9" />
    </div>
    <div class="col-md-6">
        <x-form.input name="engine_cc" label="Kapasitas Mesin (cc)" type="number" :value="$car->engine_cc" min="500" max="10000"
                      help="Opsional. Kosongkan untuk mobil listrik." />
    </div>
    <div class="col-md-6">
        <x-form.input name="color" label="Warna" :value="$car->color" maxlength="50" placeholder="Opsional" />
    </div>
    <div class="col-12">
        <x-form.textarea name="description" label="Deskripsi" :value="$car->description" rows="5" maxlength="5000" />
    </div>
</div>

<x-form.checkbox name="is_active" label="Aktif (tampil di katalog)" :checked="$car->is_active" />

<div class="alert alert-light border small mb-0">
    <i class="bi bi-images me-1"></i>Foto mobil dapat diunggah setelah fitur galeri tersedia (Phase 7).
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>Simpan
    </button>
    <a href="{{ route('admin.cars.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
