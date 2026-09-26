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
- Phase 6 (CRUD mobil) selesai: filter admin (kata kunci, merek, kategori, kondisi, status, stok habis) + urutan (terbaru, harga, tahun), 10 per halaman; toggle aktif/nonaktif (`PATCH /admin/mobil/{car}/status`, `admin.cars.toggle-active`); label enum Indonesia di konstanta `Car::CONDITIONS/TRANSMISSIONS/FUEL_TYPES`; harga & km boleh memakai titik ribuan; mobil baru selalu 0 km, mobil bekas wajib km ≥ 1; component `x-price`; seeder 15 mobil (firstOrCreate berdasarkan slug).
- **Slug mobil dibuat sekali saat tambah (merek + nama + tahun) dan TIDAK berubah saat edit**, karena menjadi URL publik `/mobil/{slug}` yang tidak boleh rusak setelah dibagikan. (Slug merek & kategori tetap ikut berubah saat nama diubah.) Titik pada nama dijadikan pemisah: "Avanza 1.5 G" → `avanza-1-5-g`.
- Hapus mobil ditolak jika punya test drive, pengajuan, atau promo (`Car::DELETION_BLOCKERS`); admin disarankan menonaktifkan. Jika berhasil dihapus, baris `car_images` ikut terhapus (cascade) dan folder `cars/{car_id}` dihapus dari disk.
- Phase 7 (galeri gambar mobil) selesai: halaman terpisah `/admin/mobil/{car}/gambar` (`admin.cars.images.*`, `CarImageController`), bukan bagian dari form edit, karena form edit sudah satu form PUT (HTML tidak boleh form bersarang) dan error upload tidak bercampur dengan error field mobil.
  - Upload multiple, maks. `CarImage::MAX_PER_CAR` = 10 gambar per mobil (dicek di `CarImageRequest` dan dicek ulang dalam transaksi dengan `lockForUpdate`); jpg/jpeg/png/webp saja (tanpa SVG), maks. 2 MB per file, minimal 600×400 px; tanpa package tambahan.
  - File di disk public `cars/{car_id}/` dengan nama acak (`store()` → `hashName()`); accessor `CarImage::url`; alt gambar = nama + tahun mobil.
  - Gambar pertama otomatis menjadi utama; hanya satu gambar utama per mobil ("Jadikan Utama"). Jika gambar utama dihapus, gambar berikutnya (urutan terkecil) menjadi utama. File dihapus dari disk setelah data berhasil dihapus.
  - Urutan diatur tombol naik/turun (`PATCH …/{image}/naik|turun`); setiap perpindahan menulis ulang `sort_order` menjadi 1..n.
  - Route memakai `scopeBindings()`: gambar milik mobil lain → 404.
  - Daftar mobil menampilkan thumbnail gambar utama (eager load `primaryImage`, tanpa N+1) atau placeholder.
  - Seeder tidak menambah gambar (tidak ada foto berhak cipta di repo); foto diunggah manual lewat admin.
- CRUD promo admin (Phase 14, bagian admin) selesai: `/admin/promo` (`admin.promos.*`, `PromoController`, tanpa show). Halaman publik `/promo` menyusul.
  - Status dihitung otomatis (`Promo::status`, tanggal WIB): Nonaktif (is_active false, selalu menang atas tanggal) → Terjadwal (hari ini < start_date) → Berakhir (hari ini > end_date) → Berjalan. Filter status memakai scope `withStatus`; "Berjalan" = scope `active`.
  - Promo khusus mobil (car_id terisi) **wajib** diskon ≥ Rp 1 dan < harga mobil; promo umum (car_id kosong) **tidak boleh** berdiskon (informasi saja). Diskon boleh memakai titik ribuan (trait `NormalizesDigits`, dipakai juga `CarRequest`).
  - Dropdown mobil hanya mobil aktif ("Merek Nama Tahun"); saat edit, mobil promo tersebut tetap bisa dipilih walau kini nonaktif.
  - Slug promo dibuat sekali saat tambah dan tidak berubah saat edit (URL publik `/promo/{slug}`).
  - Banner opsional: jpg/jpeg/png/webp, maks. 2 MB, min. 1200×400, tanpa SVG, disk public `promos/`; file lama dihapus saat diganti/dihapus atau saat promo dihapus.
  - Harga akhir: `Car::finalPrice()` = harga − diskon promo aktif terbesar (`bestActivePromo()`); memerlukan eager load `activePromos` (tanpa N+1). Diskon yang tidak lagi lebih kecil dari harga mobil (harga diturunkan setelah promo dibuat) diabaikan. Daftar mobil admin menampilkan badge "Promo" + harga coret.
  - `PromoSeeder` (dipanggil `DatabaseSeeder` setelah `CarSeeder`): 5 promo tanpa gambar, firstOrCreate berdasarkan slug, tanggal relatif hari ini — 2 khusus mobil berjalan (Avanza, HR-V), 1 umum berjalan, 1 terjadwal (Xpander), 1 berakhir. Menjalankan ulang tidak menggeser tanggal promo yang sudah ada; promo mobil dilewati jika mobilnya belum ada.
- Daftar pengguna admin selesai: `/admin/pengguna` (`admin.users.index`) dan `/admin/pengguna/{user}` (`admin.users.show`), `UserController`.
  - **Hanya baca** (tanpa tambah/edit/hapus): menghapus user ikut menghapus riwayat test drive & pengajuannya (FK `cascadeOnDelete`).
  - Filter kata kunci (nama/email/HP), role (default **customer**; admin; semua), urutan terbaru/nama, 10 per halaman. Query hanya memilih `id, name, email, phone, role, created_at` + `withCount` test drive & pengajuan; password & remember_token tidak pernah diambil maupun ditampilkan.
  - Detail: profil + riwayat test drive (mobil, tanggal, jam WIB, status) & pengajuan (mobil, Cash/Kredit, harga, status, tanggal), eager load mobil + merek (tanpa N+1). Tombol detail per riwayat disiapkan dengan `Route::has('admin.test-drives.show')` / `Route::has('admin.purchase-requests.show')`.
  - Kartu "Customer" di dashboard → `/admin/pengguna?role=customer`.
  - `CustomerSeeder` (dipanggil `DatabaseSeeder` setelah `AdminUserSeeder`): 8 customer dummy `@example.test`, **hanya** di environment local/testing, kata sandi dari `SEED_CUSTOMER_PASSWORD` (`config('dealer.seed.customer_password')`, key kosong di `.env.example`); kosong = dilewati dengan peringatan. firstOrCreate berdasarkan email: data, role, dan kata sandi user yang sudah ada tidak diubah. Dipakai seeder test drive & pengajuan.
- Kelola test drive & pengajuan (admin) selesai: `/admin/test-drive` (`admin.test-drives.index|show|update-status`, `TestDriveController`) dan `/admin/pengajuan` (`admin.purchase-requests.index|show|update-status`, `PurchaseRequestController`). Admin **tidak** membuat/menghapus data (dibuat customer di halaman publik); hanya melihat dan mengubah status + catatan admin (`PATCH …/{id}/status`).
  - Transisi (konstanta `TRANSITIONS`, method `allowedTransitions()` / `canTransitionTo()` / `isFinal()` di model; selain ini ditolak di server): test drive pending → confirmed/cancelled, confirmed → completed/cancelled; pengajuan pending → processing/rejected/cancelled, processing → approved/rejected/cancelled, approved → completed/rejected/cancelled. Form hanya menampilkan status lanjutan yang diizinkan; "status tetap" = hanya catatan yang diubah (juga untuk status akhir).
  - Catatan admin: wajib (5–1000 karakter) bila status hasilnya rejected/cancelled (alasan untuk customer, tidak bisa dikosongkan kemudian); selain itu opsional, maks. 1000.
  - Test drive: mobil **bekas** stok 0 tidak bisa dikonfirmasi (unit terjual; admin membatalkan dengan catatan), mobil baru stok 0 tetap boleh; confirmed → completed hanya jika tanggal jadwal ≤ hari ini (WIB).
  - Stok pengajuan di `App\Actions\ChangePurchaseRequestStatus`: `DB::transaction` + `lockForUpdate` pada baris pengajuan & mobil, status dibaca ulang setelah dikunci. → approved: stok −1 (stok 0 ditolak: "Stok mobil … habis, pengajuan tidak bisa disetujui."); approved → rejected/cancelled: stok +1; approved → completed: tetap. Persetujuan ganda (dua admin bersamaan) tidak mengurangi stok dua kali.
  - Filter: test drive (status, jadwal dari–sampai, kata kunci nama/email customer atau mobil/merek, urut terbaru/jadwal terdekat); pengajuan (status, metode cash/kredit, kata kunci, urut terbaru/terlama). 10 per halaman, eager load customer + mobil + merek (tanpa N+1). Detail pengajuan menampilkan rincian harga, DP, pokok, tenor, bunga, total bunga, cicilan, total bayar.
  - Kartu dashboard "Test Drive Pending" / "Pengajuan Pending" → daftar `?status=pending`; tombol Detail riwayat di `/admin/pengguna/{user}` kini tertaut.
  - `config/credit.php` + `App\Support\CreditCalculator` (hitungan bilangan bulat; contoh §5 = Rp 7.867.000) dibuat sekarang; dipakai factory & seeder, nanti juga simulasi kredit & pengajuan publik.
  - `TestDriveSeeder` (10 data) & `PurchaseRequestSeeder` (9 data), dipanggil `DatabaseSeeder` setelah `PromoSeeder`: hanya local/testing, memakai customer `@example.test` + mobil CarSeeder (dilewati dengan peringatan bila belum ada), satu data per pasangan customer–mobil (aman dijalankan ulang). Pengajuan: harga setelah promo aktif, kredit dari `CreditCalculator`, status akhir dicapai lewat `ChangePurchaseRequestStatus` langkah demi langkah sehingga stok konsisten; status & stok hanya diproses untuk data baru.
- Pagination admin: teks "Menampilkan X sampai Y dari Z data" + nomor halaman ditulis langsung di `admin/partials/pagination` (tanpa kunci terjemahan global).
- Laporan admin (Phase 15) selesai: `/admin/laporan` (`admin.reports.index`) dan `/admin/laporan/export` (`admin.reports.export`), `ReportController` (hanya baca).
  - Periode: `periode` = `bulan-ini` (default) | `bulan-lalu` | `tahun-ini` | `kustom` (`dari`–`sampai`, Y-m-d, hari penuh WIB). Validasi `ReportRequest`: tanggal valid, sampai ≥ dari, rentang maksimal 1 tahun (mis. 26 Sep 2025 – 25 Sep 2026); gagal → kembali ke laporan bulan ini dengan pesan.
  - **Terjual** = pengajuan `approved` + `completed` (`PurchaseRequest::SOLD_STATUSES`, unit sudah dipotong dari stok); nilai penjualan = SUM `car_price`. Pengajuan difilter menurut **tanggal pengajuan** (`created_at`) karena belum ada kolom waktu perubahan status; test drive menurut **tanggal jadwal** (`preferred_date`).
  - Isi: ringkasan pengajuan (per status, cash vs kredit, unit terjual, nilai), penjualan per merek & per kategori, 5 mobil terlaris (unit lalu nilai), test drive per status + persentase selesai (completed ÷ total di periode), stok menipis (mobil aktif stok ≤ `Car::LOW_STOCK_THRESHOLD` = 1, tidak bergantung periode, link edit).
  - `App\Reports\AdminReport`: agregasi di database (COUNT/SUM/GROUP BY), jumlah query tetap (±6), hasil di-memo; dipakai tampilan & CSV. Belum ada index baru; rencana Phase 18: index `(status, created_at)` di `purchase_requests`.
  - Cetak: tombol `data-print` → `window.print()`; `@media print` menyembunyikan sidebar/topbar/breadcrumb/filter dan menampilkan judul cetak.
  - Export CSV tanpa package (`App\Reports\AdminReportCsv`): UTF-8 + BOM, pemisah `;`, angka tanpa titik ribuan (desimal memakai titik). Anti CSV injection: sel teks yang diawali `=`, `+`, `-`, `@`, tab, atau carriage return diberi awalan `'`; angka tetap angka.
- Urutan berikutnya: seluruh halaman admin (merek & kategori ✓ → mobil ✓ → gambar ✓ → promo ✓ → pengguna ✓ → test drive & pengajuan ✓ → laporan ✓) sudah selesai; berikutnya halaman publik.
- Konvensi nama route admin (menu sidebar muncul otomatis bila route ada): `admin.cars.*`, `admin.brands.*`, `admin.categories.*`, `admin.promos.*`, `admin.test-drives.*`, `admin.purchase-requests.*`, `admin.users.*`, `admin.reports.*`.
