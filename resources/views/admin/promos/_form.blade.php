@csrf

@php
    $formatNumber = fn ($value) => filled($value) ? number_format((int) $value, 0, ',', '.') : '';
@endphp

<div class="row gx-3">
    <div class="col-12">
        <x-form.select name="car_id" label="Mobil" :options="$carOptions" :value="$promo->car_id"
                       placeholder="Promo Umum (semua mobil)"
                       help="Kosongkan untuk promo umum (informasi saja, tanpa diskon). Hanya mobil aktif yang bisa dipilih." />
    </div>
    <div class="col-12">
        <x-form.input name="title" label="Judul Promo" :value="$promo->title" required maxlength="150"
                      :help="$promo->exists ? 'Slug URL: '.$promo->slug.' (tidak berubah saat judul diubah).' : 'Slug URL dibuat otomatis dari judul.'" />
    </div>
    <div class="col-12">
        <x-form.textarea name="description" label="Deskripsi" :value="$promo->description" rows="4" maxlength="5000" />
    </div>
    <div class="col-md-6">
        <x-form.input name="discount_amount" label="Diskon (Rp)" :value="$formatNumber($promo->discount_amount)"
                      inputmode="numeric" placeholder="Contoh: 15.000.000"
                      help="Wajib untuk promo khusus mobil dan harus lebih kecil dari harga mobil. Kosongkan untuk promo umum." />
    </div>
    <div class="col-md-3 col-6">
        <x-form.input name="start_date" label="Tanggal Mulai" type="date" :value="$promo->start_date?->toDateString()" required />
    </div>
    <div class="col-md-3 col-6">
        <x-form.input name="end_date" label="Tanggal Selesai" type="date" :value="$promo->end_date?->toDateString()" required />
    </div>
</div>

<div class="mb-3">
    <label for="image" class="form-label">Banner <span class="text-muted small">(opsional)</span></label>

    <img id="imagePreview" src="{{ $promo->image_url }}" alt="Pratinjau banner"
         @class(['promo-banner-preview mb-2', 'd-none' => ! $promo->image_url])>

    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
           data-preview-target="#imagePreview"
           @class(['form-control', 'is-invalid' => $errors->has('image')])>
    @error('image')
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        <div class="form-text">JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB, minimal 1200×400 piksel.</div>
    @enderror

    @if ($promo->image)
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="remove_image" name="remove_image" @checked(old('remove_image'))>
            <label class="form-check-label" for="remove_image">Hapus banner saat ini</label>
        </div>
    @endif
</div>

<x-form.checkbox name="is_active" label="Aktif" :checked="$promo->is_active"
                 help="Promo berjalan jika aktif dan hari ini berada di antara tanggal mulai dan selesai." />

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>Simpan
    </button>
    <a href="{{ route('admin.promos.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
