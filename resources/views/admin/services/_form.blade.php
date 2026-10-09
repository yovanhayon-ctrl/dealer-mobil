@csrf

<div class="row gx-3">
    <div class="col-12">
        <x-form.input name="name" label="Nama Layanan" :value="$service->name" required maxlength="100" autofocus
                      help="Slug URL dibuat otomatis dari nama." />
    </div>
    <div class="col-12">
        <x-form.textarea name="description" label="Deskripsi" :value="$service->description" rows="4" maxlength="2000"
                         help="Cakupan pekerjaan dan manfaat layanan. Tampil di halaman Servis." />
    </div>
    <div class="col-md-6">
        <x-form.input name="price_from" label="Harga Mulai (Rp)"
                      :value="$service->price_from ? number_format($service->price_from, 0, ',', '.') : ''"
                      inputmode="numeric" placeholder="Contoh: 450.000"
                      help="Estimasi harga terendah. Kosongkan jika harga tergantung kebutuhan (tampil “Hubungi dealer”)." />
    </div>
    <div class="col-md-6">
        <x-form.input name="duration_minutes" label="Estimasi Durasi (menit)" :value="$service->duration_minutes"
                      inputmode="numeric" placeholder="Contoh: 120" help="Opsional, 15–1440 menit." />
    </div>
</div>

<x-form.checkbox name="is_active" label="Aktif" :checked="$service->is_active"
                 help="Layanan nonaktif tidak tampil dan tidak bisa dipilih customer saat booking." />

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>Simpan
    </button>
    <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
