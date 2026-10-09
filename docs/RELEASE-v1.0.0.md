# JAF Dealer v1.0.0

Rilis pertama website dealer mobil **JAF Dealer — Nissan Heritage & Performance** (proyek Pemrograman Web II, Teknik Informatika Universitas Pamulang). Data kendaraan, promo, pengguna, dan transaksi adalah data contoh/fiktif.

## Fitur

**Publik (tanpa login)**
- Beranda, katalog dengan pencarian & filter (merek, kategori, kondisi, harga, tahun, transmisi, BBM, warna, kursi, promo) dan 6 urutan, detail mobil (galeri, spesifikasi, mobil serupa, WhatsApp).
- Promo berjalan & detail promo, simulasi kredit bunga flat (hitung live + perbandingan tenor), JAF Service (daftar layanan), Tentang Kami, Kontak (Google Maps).

**Customer (login)**
- Booking test drive, booking servis, pengajuan pembelian cash/kredit (nilai simulasi terbawa), riwayat & pembatalan masing-masing, profil & ganti kata sandi, lupa kata sandi.

**Admin**
- Dashboard, CRUD merek, kategori, mobil + galeri foto, promo, layanan servis; kelola test drive, booking servis, dan pengajuan (status + catatan, stok otomatis); daftar pengguna; laporan + export CSV.

## Kualitas

- 665 test otomatis (PHPUnit) lulus di SQLite dan MySQL; uji browser per peran (tamu, customer, admin) di desktop, tablet, dan HP.
- Keamanan: CSRF, validasi server, hash kata sandi, hak akses per peran, rate limit, header keamanan (CSP), pencegahan open redirect, upload aman.
- Performa: tanpa N+1, index untuk daftar & laporan, cache aset browser, panduan `php artisan optimize`.

## Kebutuhan

PHP ≥ 8.4.1 (disarankan 8.5), MySQL 8, Composer 2. Lihat [README](../README.md), [DEMO.md](DEMO.md), dan [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md).
