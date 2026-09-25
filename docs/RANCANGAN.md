# Rancangan Final — Website Dealer Mobil

Status: disepakati (25 Sep 2026). Acuan untuk semua anggota tim.

## 1. Teknologi & Konfigurasi
- Laravel 13, PHP 8.5, MySQL 8. Tanpa Laravel Boost. DILARANG folder `.claude/` / file konfigurasi AI di project.
- Aset via CDN (versi dikunci): Bootstrap 5.3.3 (CSS + bundle JS), Bootstrap Icons 1.11.3, Google Fonts Poppins + Inter. File Vite/Tailwind bawaan dibiarkan, tidak dipakai.
- Locale: APP_LOCALE=id, APP_FAKER_LOCALE=id_ID, timezone Asia/Jakarta, pesan validasi `lang/id/validation.php` (+ nama atribut).
- `config/credit.php`: bunga flat/tahun 12→5%, 24→5,5%, 36→6%, 48→6,5%, 60→7%; dp_min 20%, dp_max 90%; cicilan dibulatkan ke atas per Rp 1.000.
- `config/dealer.php` (dari .env, key juga di .env.example): DEALER_NAME, DEALER_TAGLINE, DEALER_ADDRESS, DEALER_PHONE, DEALER_WHATSAPP (62…), DEALER_EMAIL, DEALER_HOURS, DEALER_MAPS_EMBED_URL.

## 2. Identitas Visual
- Warna: navy #0B2545 (utama), merah #D62828 (CTA), latar section #F4F6F9.
- Font: Poppins (judul), Inter (isi). Override Bootstrap via CSS variables di `public/css/app.css`.
- Tombol: navy (umum), merah (CTA), outline (sekunder), ikon di kiri.
- Kartu: tanpa border, shadow tipis, rounded-4, gambar 16:9, efek angkat saat hover.
- Badge: pending kuning · confirmed/processing biru · approved/completed hijau · rejected/cancelled merah · Stok Habis abu gelap.

## 3. Halaman
- Public: Beranda, Katalog, Detail Mobil, Promo (daftar & detail), Test Drive, Simulasi Kredit, Tentang Kami, Kontak (info + WhatsApp + Maps, tanpa form/tabel), Login, Register, Pengajuan Saya, Profil.
- Admin: Dashboard, Mobil (+gambar), Merek, Kategori, Promo, Test Drive, Pengajuan, Pengguna, Laporan (Phase 15).

## 4. Kartu Mobil & Filter
- Kartu: gambar utama; badge Baru/Bekas, Promo, Stok Habis; merek · kategori, nama, tahun; transmisi, BBM, km (bekas); harga coret + harga promo; "Cicilan mulai Rp x/bln"; tombol Detail.
- Stok 0 tetap tampil; tombol Ajukan dinonaktifkan + ditolak di server.
- Filter: kata kunci, merek, kategori, kondisi, harga min–max, tahun min–max, transmisi, BBM, kursi, hanya promo.
- Urutan: terbaru, harga termurah/termahal, tahun, km. 12 per halaman.

## 5. Alur Bisnis
### Test drive
- Wajib login. Tanggal besok s/d +30 hari, slot 09:00–16:00 WIB.
- Status: pending → confirmed/cancelled → completed. Customer batal hanya saat pending.
- Mobil BARU stok 0 tetap boleh test drive (unit display). Mobil BEKAS stok 0 TIDAK boleh (unit sudah terjual).

### Pengajuan pembelian
- Wajib login; mobil aktif & stok > 0.
- `car_price` = harga setelah diskon promo aktif (dihitung server). Jika >1 promo aktif, pakai diskon terbesar. Promo aktif = is_active dan tanggal hari ini di antara start_date–end_date.
- Cash: tanpa DP/tenor/bunga. Kredit: DP ≥ 20%, tenor dari config; bunga & cicilan dihitung ulang di server.
- Status: pending → processing → approved/rejected → completed; cancelled (customer saat pending, atau admin).
- Stok (transaksi DB + lockForUpdate): → approved: stok −1 (tolak jika 0); approved → rejected/cancelled: stok +1; approved → completed: tetap.

### Simulasi kredit (bunga flat)
- Pokok = Harga − DP; Bunga = Pokok × rate% × (tenor/12); Cicilan = (Pokok + Bunga)/tenor, dibulatkan ke atas per Rp 1.000.
- Contoh: Rp 300 jt, DP Rp 60 jt, 36 bln → Rp 7.867.000/bln.
- Satu class `CreditCalculator` (baca config/credit.php) + versi JS untuk hitung live.

## 6. Struktur Blade & Route
- Layouts: app, admin, auth (CDN hanya di layout). Partials: navbar, footer, flash, admin-sidebar, breadcrumb. Components: car-card, status-badge, price, promo-card, filter-sidebar, credit-summary, stat-card, form/*, empty-state.
- Public: /, /mobil, /mobil/{slug}, /promo, /promo/{slug}, /simulasi-kredit, /tentang-kami, /kontak, /login, /register, /logout.
- Customer (auth): /test-drive, /mobil/{slug}/ajukan, /akun/pengajuan, PATCH /akun/test-drive/{id}/batal, PATCH /akun/pengajuan/{id}/batal, /akun/profil.
- Admin (auth + admin, prefix /admin), URL berbahasa Indonesia, controller berbahasa Inggris: dashboard, mobil, merek, kategori, promo, test-drive, pengajuan, pengguna, laporan; hapus gambar & jadikan gambar utama.
- Login diberi rate limit (throttle). Upload gambar di disk public (`php artisan storage:link`).

## 7. Prioritas
- Wajib: auth + role + layout; CRUD merek/kategori/mobil + gambar; katalog + filter dasar; detail; test drive; pengajuan cash/kredit + aturan stok; simulasi kredit; Pengajuan Saya; dashboard admin; seeder; locale/timezone.
- Penting: promo + harga coret; filter lanjutan & urutan; galeri multi-gambar; pembatalan + catatan admin; mobil serupa; tombol WA; Tentang, Kontak, Profil; laporan sederhana.
- Tambahan: reset password (Phase 16, MAIL_MAILER=log), notifikasi email, bandingkan mobil, wishlist, export laporan, grafik, SEO lanjutan.

## 8. Status
- Phase 1 (setup) dan Phase 2 (migration + model, enum `cancelled`, timezone Asia/Jakarta) selesai.
- Phase 3 (auth + role + layout dasar) selesai.
- Phase 4 (layout admin + dashboard) selesai: sidebar offcanvas-lg, breadcrumb, komponen stat-card/status-badge/empty-state, factory model, `preventLazyLoading` di non-production.
- Phase 5 (CRUD merek & kategori) selesai: URL `/tambah` & `/ubah` (Route::resourceVerbs), slug unik otomatis (trait `HasUniqueSlug`, ikut berubah saat nama diubah), nama unik tanpa beda huruf besar/kecil, logo merek jpg/jpeg/png/webp maks 1 MB di disk public (perlu `php artisan storage:link`), hapus ditolak jika masih dipakai mobil, seeder merek & kategori (firstOrCreate).
- Urutan berikutnya: seluruh halaman admin dulu (merek & kategori → mobil → gambar → promo → pengguna → test drive & pengajuan → laporan), baru halaman publik.
- Konvensi nama route admin (menu sidebar muncul otomatis bila route ada): `admin.cars.*`, `admin.brands.*`, `admin.categories.*`, `admin.promos.*`, `admin.test-drives.*`, `admin.purchase-requests.*`, `admin.users.*`, `admin.reports.*`.
