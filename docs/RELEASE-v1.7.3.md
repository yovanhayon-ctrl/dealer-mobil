# JAF Dealer v1.7.3

Pembaruan setelah [v1.7.2](RELEASE-v1.7.2.md): dua perbaikan bug dan perapian halaman akun customer serta halaman login/daftar. Dengan ini semua halaman publik dan halaman akun sudah dirapikan (kecuali Bandingkan, tabel data).

## Perbaikan bug

- **Test otomatis tidak lagi menghapus foto asli.** Sebelumnya test hapus mobil memakai disk penyimpanan asli; karena id mobil di database test mulai dari 1, menjalankan test ikut menghapus folder `storage/app/public/cars/1` di komputer lokal (foto mobil ber-id 1). Kini disk `public` selalu palsu di semua test (`tests/TestCase.php`), dijaga oleh `TestIsolationTest`. Bila ada foto yang hilang karena masalah ini, unggah ulang lewat **Admin > Mobil > Galeri**.
- **Petunjuk upload di Galeri Mobil** kini menulis batas yang benar, **8 MB per file** (sebelumnya masih tertulis 2 MB). Angka diambil langsung dari aturan validasi.

## Halaman akun customer

- **Catatan dealer/bengkel** dan **Ulasan Anda** tampil di kotak abu netral (bukan biru/kuning bergaris warna).
- **Notifikasi** belum dibaca ditandai judul tebal dan label **Baru**, tanpa latar merah muda (berlaku juga di notifikasi admin).
- **Servis Saya** tanpa ikon kunci pas yang sama di setiap kartu.
- **Profil** menampilkan **inisial nama** (mis. "BS") dalam lingkaran, bukan ikon orang generik.
- **Foto rusak/hilang** di kartu (akun, katalog, bandingkan, hero, mobil unggulan, promo) tampil sebagai kotak abu; teks alt tidak lagi tumpah ke kartu.

## Login & daftar

- Tombol utama **Masuk**, **Daftar**, **Kirim Tautan Reset**, dan **Simpan Kata Sandi Baru** seragam berwarna merah.
- Label **Nomor HP** di form daftar menjadi **Nomor WhatsApp**, sama dengan form lain.

## Pembaruan dari v1.7.2

```bash
git pull
php artisan optimize:clear
```

Tidak ada migration, dependency, atau pengaturan `.env` baru.

## Kualitas

- 814 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 6 test dari v1.7.2: isolasi disk test, petunjuk 8 MB, inisial profil, label "Baru", Servis Saya tanpa ikon, tombol & label login/daftar).
- Dibuktikan bahwa isi `storage/app/public` sama persis sebelum dan sesudah seluruh test dijalankan (SQLite dan MySQL).
- Diperiksa lewat screenshot Edge: Profil, Pengajuan Saya, Notifikasi (dirender sebagai customer), contoh kartu dengan foto rusak, dan halaman login.
