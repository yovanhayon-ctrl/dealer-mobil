# Panduan Demo Lokal — JAF Dealer

Panduan menyiapkan dan membawakan demo di laptop (Laragon) untuk presentasi.

## 1. Persiapan (H-1)

Jalankan dari folder project (`C:\laragon\www\dealer-mobil`) di terminal Laragon/Cmder.

```bash
git checkout main
git pull
composer install
php artisan migrate
php artisan storage:link
php artisan optimize:clear
php artisan test
```

- `php artisan test` harus **lulus semua** sebelum demo.
- `php artisan migrate` juga membuat tabel fitur v1.1.0 (`notifications`, `favorites`) bila laptop masih memakai database versi v1.0.0.
- Laptop harus **terhubung internet**: Bootstrap, ikon, font, dan grafik dashboard (Chart.js) dimuat dari CDN.
- Pastikan `.env` berisi: `DEALER_NAME="JAF Dealer"`, `DEALER_TAGLINE`, kontak dealer (`DEALER_*`), `ADMIN_EMAIL`, `ADMIN_PASSWORD`, dan `SEED_CUSTOMER_PASSWORD` (lihat `.env.example`).
- Laragon: **Start All** (Apache + MySQL), lalu buka `http://dealer-mobil.test`.

### Data demo yang bersih (opsional)

Jika data lokal sudah berantakan setelah uji coba, database bisa diisi ulang dengan data contoh.

> **Peringatan:** perintah berikut **menghapus semua data** di database lokal `dealer_mobil` (termasuk foto yang sudah dihubungkan ke mobil). Gunakan hanya bila memang ingin mulai dari awal.

```bash
php artisan migrate:fresh --seed
```

Hasilnya: 1 admin, 8 customer dummy (`…@example.test`), 21 mobil (Nissan + klasik Jepang), 5 promo, 6 layanan servis, serta contoh test drive, pengajuan, dan booking servis dengan berbagai status. Seeder **tidak** membuat notifikasi dan favorit; keduanya muncul saat demo (langkah 4 dan 13 di bawah). Karena semua data contoh dibuat hari ini, grafik dashboard hanya berisi bulan berjalan. Foto mobil diunggah manual lewat **Admin → Mobil → Galeri** (siapkan 5–10 foto sebelum demo agar katalog terlihat menarik).

### Mode "seperti production" saat demo (opsional)

Agar lebih cepat dan tidak ada halaman debug yang muncul di depan penguji:

1. Di `.env`: `APP_DEBUG=false`.
2. `php artisan optimize`

Setelah demo, kembalikan untuk development: `APP_DEBUG=true` lalu `php artisan optimize:clear`. **Wajib** `optimize:clear` sebelum menjalankan test.

## 2. Akun demo

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | nilai `ADMIN_EMAIL` di `.env` | nilai `ADMIN_PASSWORD` di `.env` |
| Customer | `budi.santoso@example.test` (atau 7 customer dummy lain) | nilai `SEED_CUSTOMER_PASSWORD` di `.env` |

Kata sandi tidak ditulis di repo. Login admin dan customer di **dua browser berbeda** (atau satu jendela biasa + satu jendela penyamaran) agar bisa berpindah peran dengan cepat.

## 3. Alur demo (±20 menit)

Mengikuti *customer journey* di proposal: Temukan → Kenali → Pertimbangkan → Coba → Ajukan → Tindak lanjut. Langkah bertanda **(v1.1.0)** adalah fitur tambahan setelah rilis pertama.

| # | Peran | Langkah | Yang ditunjukkan |
|---|---|---|---|
| 1 | Tamu | Beranda → **Mobil** → filter *Kondisi: Bekas*, *Merek: Nissan*, *Warna* | Katalog, filter & urutan, harga promo, cicilan mulai |
| 2 | Tamu | Buka **Skyline GT-R (R34)** | Galeri, spesifikasi, mobil serupa, tombol WhatsApp |
| 3 | Tamu | **(v1.1.0)** Klik **Bandingkan** di R34, lalu di 1–2 mobil lain dari katalog → bar bawah → **Bandingkan** | Tabel spesifikasi berdampingan, badge *Terbaik* (harga, tahun, km), tanpa login |
| 4 | Tamu → Customer | **(v1.1.0)** Klik ikon ❤ di kartu mobil → diarahkan ke login → login customer → klik ❤ lagi → menu akun → **Favorit Saya** | Kembali ke halaman semula setelah login, daftar favorit |
| 5 | Tamu | **Promo** → *Diskon Spesial Kicks e-Power* | Promo berjalan, harga setelah diskon |
| 6 | Tamu | **Simulasi Kredit** → Kicks e-Power, klik *DP 30%*, ubah tenor | Hitung live, tabel perbandingan tenor |
| 7 | Customer | Klik **Ajukan dengan Simulasi Ini** → kirim | Nilai simulasi terbawa ke form pengajuan |
| 8 | Customer | **Test Drive** → booking mobil lain | Validasi jadwal & slot |
| 9 | Customer | **Servis** → booking servis (plat `b1234xyz`) | JAF Service, plat dirapikan otomatis |
| 10 | Customer | Menu akun → **Pengajuan Saya / Test Drive Saya / Servis Saya** | Status *Menunggu*, tombol batal |
| 11 | Admin | **Dashboard** → gulir ke **Tren 6 Bulan Terakhir** → buka *Lihat angka* | Kartu statistik, jadwal terdekat, **(v1.1.0)** grafik aktivitas & penjualan |
| 12 | Admin | **Pengajuan** → buka pengajuan langkah 7 → *Diproses* → *Disetujui* + catatan | Stok mobil berkurang otomatis |
| 13 | Customer | **(v1.1.0)** Muat ulang halaman → lonceng 🔔 di navbar menunjukkan angka → klik → klik notifikasi *Pengajuan Pembelian Disetujui* | Notifikasi status + catatan dealer, kartu pengajuan tersorot |
| 14 | Admin | **Booking Servis** → konfirmasi → (pada tanggalnya) *Dikerjakan* → *Selesai* + catatan | Alur status servis (customer juga mendapat notifikasi) |
| 15 | Admin | **Laporan** → *Export CSV* | Rekap penjualan, test drive, servis, stok menipis |
| 16 | Semua | Tampilkan di HP (F12 → mode perangkat) | Responsif, menu ☰, tabel bandingkan bisa digeser |

Tips: booking test drive/servis hanya bisa untuk **besok s/d 30 hari ke depan**; status *Dikerjakan* (servis) hanya bisa dipilih pada tanggal jadwalnya. Kosongkan daftar bandingkan sebelum demo (tombol **Kosongkan** di bar bawah) agar mulai dari nol.

## 4. Jika terjadi masalah saat demo

| Gejala | Penyebab & solusi |
|---|---|
| Halaman error / tidak bisa terhubung database | MySQL di Laragon mati → **Start All**. |
| Foto mobil tidak muncul | `php artisan storage:link` belum dijalankan. |
| Perubahan kode/`.env` tidak terlihat | Cache aktif → `php artisan optimize:clear`. |
| "Terlalu banyak percobaan…" | Rate limit (keamanan). Tunggu 1 menit. |
| Grafik dashboard kosong, hanya ada tabel *Lihat angka* | Chart.js gagal dimuat dari CDN (tidak ada internet). Angkanya tetap ada di tabel *Lihat angka*. |
| Lonceng notifikasi tidak muncul / tidak bertambah | Lonceng hanya untuk akun **customer**. Notifikasi dikirim hanya bila admin benar-benar **mengubah status** (menyimpan catatan saja tidak). Muat ulang halaman customer. |
| Tabel `notifications` / `favorites` tidak ditemukan | Database masih versi v1.0.0 → `php artisan migrate`. |
| Lupa kata sandi akun demo | Halaman **Lupa kata sandi?** → tautan reset ada di `storage/logs/laravel.log` (`MAIL_MAILER=log`). |
