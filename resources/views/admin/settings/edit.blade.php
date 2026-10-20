@extends('layouts.admin')

@section('title', 'Pengaturan')

@php
    // Petunjuk bila nilai saat ini berbeda dari bawaan .env: isian kosong = kembali ke nilai bawaan.
    $default = fn (string $key) => filled($value = \App\Models\Setting::original($key)) && $value !== config("dealer.{$key}")
        ? "Kosongkan untuk kembali ke bawaan: {$value}"
        : null;
    $heroBackground = (string) old('hero_background', $dealer['hero']['background']);
    $backgroundOptions = [
        'video' => ['bi-camera-reels', 'Video latar di layar lebar, foto mobil unggulan di HP.'],
        'image' => ['bi-image', 'Gambar unggahan sendiri, di semua ukuran layar.'],
        'car' => ['bi-car-front', 'Foto mobil unggulan beserta kartu harganya.'],
    ];
@endphp

@section('content')
    <div class="card admin-form-card-xl settings-form">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                {{-- Tiap bagian: judul & penjelasan di kiri, isian di kanan (layar lebar); bertumpuk di HP. --}}
                <section class="admin-form-section">
                    <div class="row g-3 g-lg-5">
                        <div class="col-lg-4">
                            <h2 class="settings-title">Identitas</h2>
                            <p class="small text-muted mb-0">Tampil di navbar, footer, PDF pengajuan, dan email.</p>
                        </div>
                        <div class="col-lg-8">
                            <div class="row gx-3">
                                <div class="col-md-6">
                                    <x-form.input name="name" label="Nama Dealer" :value="$dealer['name']" required maxlength="100" />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="tagline" label="Tagline" :value="$dealer['tagline']" maxlength="120" :help="$default('tagline')" />
                                </div>
                            </div>
                            <x-form.input name="address" label="Alamat Showroom" :value="$dealer['address']" maxlength="255" :help="$default('address')" />
                        </div>
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="row g-3 g-lg-5">
                        <div class="col-lg-4">
                            <h2 class="settings-title">Kontak</h2>
                            <p class="small text-muted mb-0">Dipakai di halaman Kontak, tombol WhatsApp, dan footer.</p>
                        </div>
                        <div class="col-lg-8">
                            <div class="row gx-3">
                                <div class="col-md-6">
                                    <x-form.input name="phone" type="tel" label="Telepon" :value="$dealer['phone']" maxlength="30" inputmode="tel" :help="$default('phone')" />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="whatsapp" data-number="phone" type="tel" label="WhatsApp" :value="$dealer['whatsapp']" maxlength="20" inputmode="tel"
                                                  help="Nomor HP, mis. 0812xxxx." />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="email" type="email" label="Email" :value="$dealer['email']" maxlength="255" :help="$default('email')" />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="hours" label="Jam Operasional" :value="$dealer['hours']" maxlength="100"
                                                  placeholder="Contoh: Senin-Sabtu 08.00-17.00 WITA" :help="$default('hours')" />
                                </div>
                            </div>
                            <x-form.input name="maps_embed_url" type="url" label="Link Peta Google Maps" :value="$dealer['maps_embed_url']" maxlength="2000"
                                          help='Google Maps, Bagikan, Sematkan peta, lalu salin alamat di dalam src="...".' />
                        </div>
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="row g-3 g-lg-5">
                        <div class="col-lg-4">
                            <h2 class="settings-title">Halaman Tentang</h2>
                            <p class="small text-muted mb-0">Teks bagian Siapa Kami.</p>
                        </div>
                        <div class="col-lg-8">
                            <x-form.textarea name="about_text" label="Teks Siapa Kami" :value="$dealer['about_text']" rows="5" maxlength="3000"
                                             help="Pisahkan paragraf dengan baris kosong. Kosongkan untuk teks bawaan." />
                        </div>
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="row g-3 g-lg-5">
                        <div class="col-lg-4">
                            <h2 class="settings-title">Hero Beranda</h2>
                            <p class="small text-muted mb-0">Bagian paling atas halaman beranda.</p>
                        </div>
                        <div class="col-lg-8">
                            <x-form.input name="hero_kicker" label="Teks Kecil di Atas Judul" :value="$dealer['hero']['kicker']" maxlength="60"
                                          help="Kosongkan untuk memakai tagline." />
                            <x-form.input name="hero_title" label="Judul" :value="$dealer['hero']['title']" required maxlength="90" />
                            <x-form.textarea name="hero_lead" label="Teks di Bawah Judul" :value="$dealer['hero']['lead']" rows="2" maxlength="200"
                                             help="Kosongkan untuk teks bawaan." />

                            <fieldset class="mb-3">
                                <legend class="form-label fs-6 mb-2">Latar<span class="text-danger ms-1" aria-hidden="true">*</span></legend>
                                <div class="settings-choices">
                                    @foreach (\App\Http\Requests\Admin\SettingRequest::HERO_BACKGROUNDS as $value => $label)
                                        @php([$icon, $description] = $backgroundOptions[$value])
                                        <div>
                                            <input type="radio" class="btn-check" name="hero_background" id="hero_background_{{ $value }}" value="{{ $value }}"
                                                   autocomplete="off" @checked($heroBackground === $value)>
                                            <label class="settings-choice" for="hero_background_{{ $value }}">
                                                <i class="bi {{ $icon }}" aria-hidden="true"></i>
                                                <span class="fw-semibold">{{ $label }}</span>
                                                <span class="small text-muted">{{ $description }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('hero_background')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </fieldset>

                            {{-- Hanya untuk latar "Gambar banner" (admin.js menampilkan/menyembunyikan saat pilihan berubah). --}}
                            <div id="heroImageField" @class(['mb-3', 'd-none' => $heroBackground !== 'image' && ! $errors->has('hero_image')])>
                                <label for="hero_image" class="form-label">Gambar Banner</label>

                                <img id="heroImagePreview" src="{{ $heroImageUrl }}" alt="Pratinjau gambar banner"
                                     @class(['settings-hero-preview mb-2', 'd-none' => ! $heroImageUrl])>

                                <input type="file" id="hero_image" name="hero_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                       data-preview-target="#heroImagePreview"
                                       @class(['form-control', 'is-invalid' => $errors->has('hero_image')])>
                                @error('hero_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">
                                        JPG, PNG, atau WEBP, maks. {{ intdiv(\App\Http\Requests\Admin\SettingRequest::MAX_KILOBYTES, 1024) }} MB, min. 1200×500 px.
                                        Disarankan 1920×1080 dengan objek utama di sisi kanan.
                                    </div>
                                @enderror

                                @if ($hasStoredHeroImage)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="remove_hero_image" name="remove_hero_image" @checked(old('remove_hero_image'))>
                                        <label class="form-check-label" for="remove_hero_image">Hapus gambar banner saat ini</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="admin-form-section" id="rekening">
                    <div class="row g-3 g-lg-5">
                        <div class="col-lg-4">
                            <h2 class="settings-title">Rekening Pembayaran</h2>
                            <p class="small text-muted mb-0">Tujuan transfer customer setelah pengajuan disetujui.</p>
                        </div>
                        <div class="col-lg-8">
                            <div class="alert alert-warning small d-flex gap-2 py-2" role="note">
                                <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
                                <span>Rekening ini muncul di form bukti bayar, pengingat batas bayar, dan PDF pengajuan. Setiap perubahan diberitahukan ke Owner.</span>
                            </div>
                            <div class="row gx-3">
                                <div class="col-md-4">
                                    <x-form.input name="bank_name" label="Nama Bank" :value="$dealer['bank']['name']" maxlength="50" placeholder="Contoh: BCA" />
                                </div>
                                <div class="col-md-8">
                                    <x-form.input name="bank_account" data-number="digits" label="Nomor Rekening" :value="$dealer['bank']['account']" maxlength="30" inputmode="numeric" />
                                </div>
                            </div>
                            <x-form.input name="bank_holder" label="Atas Nama" :value="$dealer['bank']['holder']" maxlength="100" />
                            @if ($bankEdit)
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                    Terakhir diubah oleh {{ $bankEdit->editor?->name ?? 'akun terhapus' }} pada {{ $bankEdit->updated_at->translatedFormat('d M Y H:i') }}.
                                </p>
                            @endif
                        </div>
                    </div>
                </section>

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
