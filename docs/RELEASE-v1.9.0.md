# JAF Dealer v1.9.0

Pembaruan setelah [v1.8.0](RELEASE-v1.8.0.md): mode terang/gelap untuk pengunjung dan export laporan admin ke Excel yang sudah rapi.

## Mode terang & gelap

- Tombol **☾ / ☀** di navbar (di HP berlabel "Mode gelap" / "Mode terang" di bawah menu); di halaman login/daftar ada di pojok kanan atas.
- Saat pertama dibuka mengikuti **pengaturan perangkat**; pilihan pengunjung diingat di browser.
- Berlaku untuk halaman publik, halaman akun customer, login/daftar, dan halaman error. **Halaman admin selalu terang.**
- Mode gelap: latar hitam lembut, kartu sedikit lebih terang bergaris tipis, teks putih lembut; tombol merah tetap, teks merah (harga, tautan) dibuat lebih terang agar tetap terbaca. Tombol hitam JAF, pagination, menu akun aktif, dan inisial profil dibalik menjadi terang.
- Mode terang tidak berubah. Foto mobil, PDF bukti pengajuan, email, dan peta Google tetap terang.
- Teknis: skrip kecil `public/js/theme.js` dimuat di `<head>` sebelum CSS (tidak berkedip putih, tanpa skrip inline sehingga CSP tetap ketat); warna di `public/css/app.css` memakai token (`--dm-ink`, `--dm-surface`, `--dm-line`, `--dm-tint`, `--dm-muted`, `--dm-accent-text`) yang berganti nilai di mode gelap.

## Export laporan ke Excel

- Tombol **Export Excel** di **Admin > Laporan** menghasilkan `laporan-…xlsx` untuk periode yang dipilih. Tautan kecil **atau CSV** tetap tersedia untuk data mentah (isi CSV tidak berubah).
- File langsung rapi saat dibuka: judul laporan, baris Periode/Dibuat/Catatan, judul tiap bagian berlatar hitam JAF, kepala kolom tebal berlatar abu, baris total tebal, lebar kolom tetap.
- Angka tetap berupa angka (bisa dijumlah/difilter) dengan format **ribuan**, **Rp**, dan **%**; teks selalu ditulis sebagai teks sehingga tidak pernah dijalankan sebagai formula.
- Siap cetak: A4 tegak, lebar dipas satu halaman, judul diulang di tiap halaman, nomor halaman di footer.
- Teknis: memakai `phpoffice/phpspreadsheet`; isi laporan disusun sekali di `App\Reports\AdminReportSections` dan dipakai bersama oleh export CSV dan Excel.

## Pembaruan dari v1.8.0

```bash
git pull
composer install
php artisan optimize:clear
```

- **Wajib `composer install`** karena ada dependency baru (`phpoffice/phpspreadsheet`). Di hosting, upload paket dengan folder `vendor` baru atau jalankan `composer install --no-dev --optimize-autoloader` (lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md)).
- Ekstensi PHP `zip` dan `xml` harus aktif (untuk file Excel).
- Tidak ada migration atau pengaturan `.env` baru.

## Kualitas

- 824 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 6 test dari v1.8.0: skrip & tombol mode, admin tetap terang, halaman error ikut mode, palet gelap, export Excel).
- Mode gelap diperiksa lewat screenshot Edge di beranda, katalog, detail mobil, promo, tentang kami, kontak, pengajuan saya, dan login; tombol diuji di browser (berganti, tersimpan, label HP).
- File Excel dibuat dari data lokal lalu diperiksa isi, format angka, dan tampilannya; isi CSV dipastikan identik dengan versi sebelumnya.
