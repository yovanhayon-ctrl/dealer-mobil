@csrf

<x-form.input name="name" label="Nama Kategori" :value="$category->name" required maxlength="100" autofocus
              help="Slug URL dibuat otomatis dari nama." />

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>Simpan
    </button>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
