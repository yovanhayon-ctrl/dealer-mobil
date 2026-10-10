# JAF Dealer v1.5.0

Pembaruan setelah [v1.4.0](RELEASE-v1.4.0.md): banner beranda baru, video latar, mobil unggulan pilihan admin, dan perapian tampilan.

## Fitur baru

**Banner beranda (hero)**
- **Video latar** di layar lebar (≥ 992 px): diputar tanpa suara dan berulang, dengan lapisan gelap agar teks tetap terbaca. Tidak diunduh sama sekali di HP/tablet, saat pengguna memilih *reduce motion*, atau mode hemat data; di situ tampil banner dengan garis diagonal ala livery balap Nissan.
- Video diatur lewat `.env` → `DEALER_HERO_VIDEOS=videos/video2.mp4` (MP4 di folder `public`, boleh beberapa dipisah koma). Kosongkan untuk banner tanpa video.
- **Kartu mobil unggulan** di kanan banner (mode tanpa video): foto, harga, tombol **Lihat Detail**.
- **"Jadikan unggulan di beranda"** (baru): checkbox di form mobil admin dan badge **Unggulan** di daftar mobil. Mobil aktif yang punya foto dan dicentang tampil di kartu banner (bila beberapa, yang terakhir diubah). Tanpa pilihan admin, otomatis Nissan terbaru yang punya foto, lalu mobil terbaru lain yang punya foto.

**Perapian tampilan**
- **Beranda**: celah putih di bawah navbar hilang, judul bagian dan "Lihat semua" sejajar, harga kartu mobil sejajar, kartu promo tanpa banner menampilkan "Hemat Rp 10 jt", latar bagian selang-seling, Jelajahi Mobil dalam dua kartu, dan **Mobil Terbaru di HP bisa digeser ke samping** (Promo tidak lagi tertimbun di bawah).
- **Navbar**: penanda menu aktif berupa garis merah, tombol **Admin** di area akun (bukan di deretan menu), menu HP dengan pemisah dan tombol akun selebar layar, bayangan tipis saat halaman digulir.
- **Footer**: garis merah di atas, kolom Jelajahi / Layanan / Hubungi Kami, telepon bisa diklik, tautan **Kembali ke atas**; catatan "Data kendaraan bersifat contoh" hanya di luar production.
- **Dashboard admin**: tombol aksi cepat (Tambah Mobil, Tambah Promo, Laporan), kartu statistik dengan label utuh dan titik merah untuk yang pending, judul bagian seragam, tabel aktivitas yang tidak lagi melebar dan baris yang bisa diklik.

## Pembaruan dari v1.4.0

```bash
git pull
php artisan migrate   # kolom baru: cars.is_featured
php artisan optimize:clear
```

Opsional di `.env`: `DEALER_HERO_VIDEOS=videos/video2.mp4` (sudah menjadi nilai bawaan bila tidak diisi). Tidak ada dependency baru.

## Kualitas

- 765 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 9 test dari v1.4.0): kartu unggulan (pilihan admin, Nissan, merek lain, tanpa foto), video valid/tidak valid, menu & footer, mobil terbaru di HP.
- Diuji di browser pada 1440 px, 1280 px, 1024 px, dan HP 375 px: tidak ada halaman atau tabel yang melebar; video tidak diunduh di HP.
