# JAF Dealer v1.7.0

Pembaruan setelah [v1.6.0](RELEASE-v1.6.0.md): optimasi foto mobil, SEO dan pratinjau tautan, logo JAF, serta tampilan publik yang dirapikan (navbar, footer, beranda).

## Foto mobil lebih ringan

- Foto yang diunggah admin otomatis **dikecilkan** (sisi terpanjang maks. 1600 px) dan disimpan sebagai **WebP**, ditambah **thumbnail 480 px** untuk kartu dan daftar. Foto dari HP diputar sesuai orientasinya; data lokasi (EXIF/GPS) ikut terbuang.
- Batas upload naik dari 2 MB ke **8 MB** per foto (ukuran 600×400 sampai 8000×8000 piksel), jadi foto langsung dari HP bisa diunggah.
- Kartu katalog, beranda, favorit, bandingkan, dan halaman akun memakai thumbnail; galeri detail tetap memakai foto besar.
- Perintah baru **`php artisan cars:optimize-images`** mengolah foto lama yang belum punya thumbnail. File JPG/PNG lama **diganti** WebP; aman dijalankan ulang.

## SEO & berbagi tautan

- **`/sitemap.xml`** otomatis: halaman publik, mobil aktif, dan promo yang sedang berjalan.
- **`/robots.txt`** otomatis: menutup `/admin`, `/akun`, login/daftar, dan bandingkan, serta menyebut alamat sitemap. File `public/robots.txt` dihapus agar route ini yang dipakai.
- **Pratinjau tautan** (WhatsApp, Facebook, X) di semua halaman publik: judul, deskripsi, gambar. Detail mobil memakai foto mobil, detail promo memakai banner atau foto mobilnya, halaman lain memakai gambar bawaan `public/images/og-default.jpg`.
- **Canonical URL** (katalog yang difilter tidak dianggap halaman ganda) dan **`noindex`** di halaman admin, akun, dan login.
- **Data terstruktur (JSON-LD)** di detail mobil: merek, model, tahun, kondisi, harga setelah promo, ketersediaan stok.
- Semua URL mengikuti `APP_URL`, jadi isi `APP_URL` dengan domain yang benar saat dipasang online.

## Logo & ikon

- **Logo JAF** (dipotong bulat, latar transparan) menggantikan ikon mobil di navbar, footer, halaman login/daftar, sidebar admin, dan halaman error; juga menjadi **favicon**. File sumber: `public/images/LOGO.png`; versi siap pakai `logo-128.webp`, `logo-512.png`, `favicon-64.png`.
- Tombol **Bandingkan** memakai ikon dua panah.

## Tampilan publik

**Navbar**
- Menu desktop dalam satu "pil" dengan **lampu merah** di menu aktif; lampu meluncur mengikuti kursor lalu kembali ke menu aktif. Di HP tetap menu hamburger.

**Footer**
- Susunan baru: logo + tagline + alamat di kiri, kolom **Jelajahi / Layanan / Perusahaan / Hubungi**, lalu hak cipta dan "Kembali ke atas". Catatan "Data kendaraan bersifat contoh." dihapus.

**Beranda**
- **Hero baru**: lebih tinggi, judul "Nissan pilihan, dari klasik sampai terbaru.", satu kalimat penjelas, tombol Lihat Mobil dan Simulasi Kredit. Di HP latarnya foto mobil unggulan.
- Kotak **Cari Mobil dihapus** (pencarian tetap di halaman Mobil). **Mobil Terbaru** menampilkan 4 mobil.
- **Mobil Unggulan** (saat hero memakai video): bingkai foto besar yang miring lalu tegak saat digulir (CSS murni). Pilihan otomatis mendahulukan Nissan berfoto.
- **Kata Pelanggan**: ringkasan rating besar di kiri, ulasan berupa daftar bergaris tipis di kanan (kutipan maks. 3 baris).
- **Butuh bantuan memilih mobil?** menjadi pita gelap yang menyambung ke footer: jam operasional, satu tombol **Chat WhatsApp** (atau **Hubungi Kami** bila nomor kosong), dan nomor telepon.

**Perapian "anti-slop"** (seluruh halaman publik)
- Tanpa tanda pisah panjang (— / –): judul tab menjadi "Beranda | JAF Dealer", halaman error menampilkan "Error 404" di atas judul.
- Satu aturan sudut (kartu 1rem, tombol/input .5rem, chip pill), satu nama per tujuan tombol, titik tengah (·) maks. satu per baris.
- Menghormati pengaturan **"kurangi gerakan"** di perangkat; tombol sedikit mengecil saat ditekan.
- Celah putih di bawah navbar setelah pesan (mis. "Anda telah keluar.") ditutup sudah hilang.

## Pembaruan dari v1.6.0

```bash
git pull
php artisan migrate
php artisan optimize:clear
```

- **Migration baru:** kolom `car_images.thumb_path`.
- **Opsional (disarankan):** `php artisan cars:optimize-images` untuk foto lama. File asli diganti WebP, jadi cadangkan `storage/app/public/cars` dulu bila perlu.
- **Hosting:** naikkan `upload_max_filesize` ≥ 8M dan `post_max_size` ≥ 80M (lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md)).
- `DEALER_HOURS` sebaiknya memakai tanda hubung biasa, mis. `"Senin-Sabtu 08.00-17.00 WIB"`.
- Tidak ada dependency baru.

## Kualitas

- 799 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 22 test dari v1.6.0: optimasi foto, sitemap/robots/Open Graph/JSON-LD, teks publik tanpa tanda pisah panjang, logo, navbar, beranda).
- Diuji di browser dan screenshot Edge pada 1200 px, 1280 px, 1400 px, 1440 px, 1600 px, dan HP 375/390 px: navbar satu baris, hero dan beranda tanpa lebar berlebih, footer rapi di desktop dan HP.
