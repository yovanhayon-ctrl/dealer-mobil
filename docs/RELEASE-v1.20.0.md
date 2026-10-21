# JAF Dealer v1.20.0

Rilis fitur setelah [v1.19.0](RELEASE-v1.19.0.md): **cadangan otomatis harian** (database, foto, dan bukti pembayaran), serta daftar periksa go-live.

## Cadangan otomatis

- Perintah baru `php artisan backup:run` membuat satu file `cadangan-TANGGAL-JAM.zip` berisi:
  - `database.sql`: seluruh database (struktur + isi), siap diimpor lewat phpMyAdmin atau `mysql`;
  - `storage/app/public`: foto mobil, banner promo, gambar hero, logo merek;
  - `storage/app/private`: bukti pembayaran dan bukti pengembalian dana customer.
- Ekspor database memakai PHP, jadi tetap berjalan di hosting tanpa `mysqldump` dan kata sandi database tidak muncul di perintah.
- File `.env` **tidak** ikut (berisi kata sandi); simpan salinannya sendiri di tempat aman.
- Dijalankan otomatis **setiap hari pukul 02:00** lewat cron `schedule:run` yang sama dengan pengingat H-1; juga bisa manual kapan saja (mis. sebelum memperbarui aplikasi).
- Cadangan yang lebih tua dari **30 hari** dihapus otomatis; cadangan terbaru selalu disimpan.
- Disimpan di `storage/app/backups` (tidak bisa dibuka dari browser, tidak ikut git).
- **Dashboard** Owner dan Super Admin menampilkan *"Cadangan terakhir … (ukuran)"*, dan memberi peringatan bila belum ada cadangan atau cadangan terakhir lebih dari 2 hari (tanda cron tidak berjalan).

Cadangan masih berada di server yang sama: unduh file zip terbaru secara berkala (mis. seminggu sekali) ke komputer atau penyimpanan cloud milik dealer. Cara memulihkan: [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 12.

## Daftar periksa go-live

Dokumen baru [GO-LIVE.md](GO-LIVE.md): bahan yang dikumpulkan dari dealer, `.env` production, deploy tanpa mobil/promo contoh, pengisian data asli, uji akhir per role di server, keamanan dan cadangan, serta minggu pertama. Ditautkan dari README dan [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md).

## Pembaruan dari v1.19.0

```bash
git pull
php artisan optimize
php artisan backup:run
```

- **Tidak ada migration.**
- Pengaturan `.env` baru (opsional, sudah ada nilai bawaan): `DEALER_BACKUP_TIME=02:00`, `DEALER_BACKUP_KEEP_DAYS=30`, `DEALER_BACKUP_PATH=` (kosong = `storage/app/backups`).
- Butuh ekstensi PHP **zip** (cPanel > *Select PHP Version*). Jalankan `php artisan backup:run` sekali untuk memastikan cadangan berhasil, dan pastikan cron `schedule:run` aktif.

## Kualitas

- 1065 test otomatis (PHPUnit) lulus di SQLite dan MySQL. Test baru: isi zip (database, foto, bukti pembayaran), impor ulang database hasil cadangan, penghapusan cadangan lama, status di dashboard per role, dan jadwal harian.
- Uji restore manual di MySQL lokal: cadangan diimpor ke database terpisah, jumlah baris dan **checksum ke-27 tabel identik** dengan database asli.
