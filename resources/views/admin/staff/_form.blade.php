@use('App\Models\User')

@csrf

<div class="row g-3">
    <div class="col-md-6">
        <x-form.input name="name" label="Nama" :value="$staff->name" required maxlength="100" autocomplete="off" />
    </div>
    <div class="col-md-6">
        <x-form.select name="role" label="Role" required :value="old('role', $staff->role)"
                       :options="collect(User::ASSIGNABLE_STAFF_ROLES)->mapWithKeys(fn ($role) => [$role => User::ROLE_LABELS[$role]])->all()"
                       help="Admin: semua menu admin kecuali Staf. Mekanik: hanya panel Pekerjaan Saya." />
    </div>
    <div class="col-md-6">
        <x-form.input name="email" type="email" label="Email (untuk login)" :value="$staff->email" required maxlength="255" autocomplete="off" />
    </div>
    <div class="col-md-6">
        <x-form.input name="phone" data-number="phone" type="tel" label="Nomor WhatsApp" :value="$staff->phone" inputmode="tel"
                      help="Opsional. Format 08xx." />
    </div>
    <div class="col-md-6">
        <x-form.input name="password" type="password" label="{{ $staff->exists ? 'Kata Sandi Baru' : 'Kata Sandi' }}"
                      :required="! $staff->exists" autocomplete="new-password"
                      :help="$staff->exists ? 'Kosongkan bila tidak diganti. Minimal 8 karakter.' : 'Minimal 8 karakter. Berikan ke staf, lalu minta diganti di menu Profil.'" />
    </div>
    <div class="col-md-6">
        <x-form.input name="password_confirmation" type="password" label="Ulangi Kata Sandi" :required="! $staff->exists" autocomplete="new-password" />
    </div>
</div>

@if ($staff->exists)
    <x-form.checkbox name="is_active" label="Akun aktif" :checked="$staff->isActive()"
                     help="Matikan bila staf berhenti bekerja. Akun nonaktif tidak bisa login, riwayatnya tetap tersimpan." />
@endif

<div class="admin-form-actions">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>Simpan
    </button>
    <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
