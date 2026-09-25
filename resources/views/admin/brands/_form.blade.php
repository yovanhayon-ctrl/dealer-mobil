@csrf

<x-form.input name="name" label="Nama Merek" :value="$brand->name" required maxlength="100" autofocus
              help="Slug URL dibuat otomatis dari nama." />

<div class="mb-3">
    <label for="logo" class="form-label">Logo <span class="text-muted small">(opsional)</span></label>

    <div class="d-flex align-items-start gap-3">
        <img id="logoPreview" src="{{ $brand->logo_url }}" alt="Pratinjau logo"
             @class(['admin-thumb admin-thumb-lg', 'd-none' => ! $brand->logo_url])>

        <div class="flex-grow-1">
            <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                   data-preview-target="#logoPreview"
                   @class(['form-control', 'is-invalid' => $errors->has('logo')])>
            @error('logo')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="form-text">JPG, JPEG, PNG, atau WEBP. Maksimal 1 MB.</div>
            @enderror

            @if ($brand->logo)
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" id="remove_logo" name="remove_logo" @checked(old('remove_logo'))>
                    <label class="form-check-label" for="remove_logo">Hapus logo saat ini</label>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>Simpan
    </button>
    <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
