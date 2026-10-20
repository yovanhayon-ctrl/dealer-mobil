@extends('layouts.admin')

@section('title', 'Pengaturan')

@php
    // Petunjuk bila nilai saat ini berbeda dari bawaan .env: isian kosong = kembali ke nilai bawaan.
    $default = fn (string $key) => filled($value = \App\Models\Setting::original($key)) && $value !== config("dealer.{$key}")
        ? "Kosongkan untuk kembali ke bawaan: {$value}"
        : null;
@endphp

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <div class="row g-4">
                    <div class="col-xl-6">
                        <section class="admin-form-section">
                            <h2 class="admin-form-section-title">Profil Dealer</h2>
                            <p class="small text-muted">Tampil di navbar, footer, halaman Kontak & Tentang, PDF pengajuan, dan email.</p>
                            <div class="row gx-3">
                                <div class="col-md-6">
                                    <x-form.input name="name" label="Nama Dealer" :value="$dealer['name']" required maxlength="100" />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="tagline" label="Tagline" :value="$dealer['tagline']" maxlength="120" :help="$default('tagline')" />
                                </div>
                                <div class="col-12">
                                    <x-form.input name="address" label="Alamat Showroom" :value="$dealer['address']" maxlength="255" :help="$default('address')" />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="phone" type="tel" label="Telepon" :value="$dealer['phone']" maxlength="30" inputmode="tel" :help="$default('phone')" />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="whatsapp" data-number="phone" type="tel" label="WhatsApp" :value="$dealer['whatsapp']" maxlength="20" inputmode="tel"
                                                  help="Nomor HP untuk tombol WhatsApp, mis. 0812xxxx (disimpan sebagai 62812xxxx)." />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="email" type="email" label="Email" :value="$dealer['email']" maxlength="255" :help="$default('email')" />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="hours" label="Jam Operasional" :value="$dealer['hours']" maxlength="100"
                                                  placeholder="Contoh: Senin-Sabtu 08.00-17.00 WITA" :help="$default('hours')" />
                                </div>
                                <div class="col-12">
                                    <x-form.input name="maps_embed_url" type="url" label="Link Peta (Google Maps)" :value="$dealer['maps_embed_url']" maxlength="2000"
                                                  help='Google Maps → Bagikan → Sematkan peta → salin alamat di dalam src="…" (diawali https://www.google.com/maps/embed?).' />
                                </div>
                            </div>
                        </section>

                        <section class="admin-form-section">
                            <h2 class="admin-form-section-title">Halaman Tentang</h2>
                            <x-form.textarea name="about_text" label="Teks Siapa Kami" :value="$dealer['about_text']" rows="6" maxlength="3000"
                                             help="Pisahkan paragraf dengan baris kosong. Kosongkan untuk memakai teks bawaan." />
                        </section>

                        <section class="admin-form-section" id="rekening">
                            <h2 class="admin-form-section-title">Rekening Pembayaran</h2>
                            <p class="small text-muted">
                                Tampil ke customer yang pengajuannya disetujui, di pengingat batas bayar, dan PDF pengajuan.
                                Setiap perubahan diberitahukan ke Owner.
                            </p>
                            <div class="row gx-3">
                                <div class="col-md-4">
                                    <x-form.input name="bank_name" label="Nama Bank" :value="$dealer['bank']['name']" maxlength="50" placeholder="Contoh: BCA" />
                                </div>
                                <div class="col-md-8">
                                    <x-form.input name="bank_account" data-number="digits" label="Nomor Rekening" :value="$dealer['bank']['account']" maxlength="30" inputmode="numeric" />
                                </div>
                                <div class="col-12">
                                    <x-form.input name="bank_holder" label="Atas Nama" :value="$dealer['bank']['holder']" maxlength="100" />
                                </div>
                            </div>
                            @if ($bankEdit)
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-clock-history"></i>
                                    Terakhir diubah oleh {{ $bankEdit->editor?->name ?? 'akun terhapus' }} pada {{ $bankEdit->updated_at->translatedFormat('d M Y H:i') }}.
                                </p>
                            @endif
                        </section>
                    </div>

                    <div class="col-xl-6">
                        <section class="admin-form-section">
                            <h2 class="admin-form-section-title">Hero Beranda</h2>
                            <x-form.input name="hero_kicker" label="Teks Kecil di Atas Judul" :value="$dealer['hero']['kicker']" maxlength="60"
                                          help="Kosongkan untuk memakai tagline dealer." />
                            <x-form.input name="hero_title" label="Judul" :value="$dealer['hero']['title']" required maxlength="90" />
                            <x-form.textarea name="hero_lead" label="Teks di Bawah Judul" :value="$dealer['hero']['lead']" rows="2" maxlength="200"
                                             help="Kosongkan untuk memakai teks bawaan." />
                            <x-form.select name="hero_background" label="Latar" required :value="$dealer['hero']['background']"
                                           :options="\App\Http\Requests\Admin\SettingRequest::HERO_BACKGROUNDS"
                                           help="Video diputar di layar lebar (di HP memakai foto mobil unggulan). Foto mobil unggulan juga menampilkan kartu mobil di samping judul." />

                            <div class="mb-3">
                                <label for="hero_image" class="form-label">Gambar Banner</label>

                                <img id="heroImagePreview" src="{{ $heroImageUrl }}" alt="Pratinjau gambar banner"
                                     @class(['promo-banner-preview mb-2', 'd-none' => ! $heroImageUrl])>

                                <input type="file" id="hero_image" name="hero_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                       data-preview-target="#heroImagePreview"
                                       @class(['form-control', 'is-invalid' => $errors->has('hero_image')])>
                                @error('hero_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">
                                        Dipakai bila latar "Gambar banner unggahan". JPG, JPEG, PNG, atau WEBP. Maksimal
                                        {{ intdiv(\App\Http\Requests\Admin\SettingRequest::MAX_KILOBYTES, 1024) }} MB, minimal 1200×500 piksel
                                        (disarankan 1920×1080, objek utama di sisi kanan). Disimpan otomatis sebagai WebP.
                                    </div>
                                @enderror

                                @if ($hasStoredHeroImage)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="remove_hero_image" name="remove_hero_image" @checked(old('remove_hero_image'))>
                                        <label class="form-check-label" for="remove_hero_image">Hapus gambar banner saat ini</label>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>Simpan
                    </button>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right"></i>Lihat Beranda
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
