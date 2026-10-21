# Catatan Repo dan Hosting dari Awal

Catatan per **22 Oktober 2026**. Bagian A menjelaskan kondisi repo dan folder saat ini; bagian B langkah memasang website di hosting dari nol sampai online. Rincian teknis tambahan ada di [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) dan daftar periksa di [GO-LIVE.md](GO-LIVE.md).

---

## A. Repo dan folder

### A1. Repo GitHub

| Repo | Status | Kegunaan |
|---|---|---|
| `yovanhayon-ctrl/dealer-mobil` | **Public**, repo yang dipakai | Semua pekerjaan berikutnya (push, merge, tag). Riwayat 188 commit dan 28 tag (v1.0.0 sampai v1.20.0) lengkap, tanpa atribusi Claude. |
| `yovanhayon-ctrl/dealer-mobil-lama` | **Private**, arsip | Repo lama sebelum riwayat dibersihkan. Isi kodenya identik dengan repo baru. Tidak dipakai lagi. |

Pada 22 Oktober 2026 riwayat commit dibersihkan dari baris `Co-Authored-By: Claude`, lalu dipindahkan ke repo baru. Yang berubah hanya kode hash commit; isi kode, penulis, tanggal, judul commit, dan tag tetap sama. Mulai sekarang commit dibuat **tanpa** atribusi Claude.

### A2. Cadangan repo

- File: `C:\laragon\www\dealer-mobil-backup-2026-10-22.bundle` (sekitar 4,3 MB), berisi seluruh repo **sebelum** dibersihkan (semua branch dan tag).
- Simpan juga salinannya di luar laptop (Google Drive, flashdisk).
- Cara memakai bila suatu saat perlu (membuat salinan repo dari cadangan):

  ```bash
  git clone C:/laragon/www/dealer-mobil-backup-2026-10-22.bundle dealer-mobil-pulih
  ```

### A3. Kapan repo lama boleh dihapus

Hapus `dealer-mobil-lama` hanya bila semua ini sudah benar:

- [ ] Repo baru `dealer-mobil` menampilkan semua folder, 28 tag, dan branch `main` + `testing`.
- [ ] Sudah pernah push dan merge di repo baru tanpa masalah.
- [ ] Website sudah online dari kode repo baru (bagian B).
- [ ] File `.bundle` di A2 sudah disalin ke tempat aman.

Cara menghapus: GitHub > repo `dealer-mobil-lama` > **Settings** > *Danger Zone* > **Delete this repository**. Tindakan ini **tidak bisa dibatalkan**. Membiarkannya tetap private juga tidak masalah.

### A4. Folder di laptop

| Folder | Isi | Aturan |
|---|---|---|
| `C:\laragon\www\dealer-mobil` | Kode (branch `testing`), database `dealer_mobil` berisi data **dummy** | Tempat mengubah kode, menjalankan test, `git add/commit/push`. Situs: `http://dealer-mobil.test`. |
| `C:\laragon\www\dealer-mobil-release` | Kode versi rilis (saat ini v1.20.0), database `dealer_mobil_release` berisi data **asli** | Tempat mengisi data asli sebelum online. **Jangan** commit di sini dan **jangan** hapus lewat File Explorer (terhubung ke git sebagai *worktree*). Situs: `http://dealer-mobil-release.test`. |

Sebelum `git commit` atau `git push`, pastikan terminal berada di `C:\laragon\www\dealer-mobil` (lihat path di atas tanda `λ`).

---

## B. Hosting dari awal sampai online

Gambaran: **kode** diambil dari rilis terbaru di repo, **data** (pengaturan dealer, rekening, akun owner, logo, foto) diambil dari cadangan folder `dealer-mobil-release`. Database development yang berisi data dummy **tidak** dipakai.

### B1. Siapkan hosting dan domain

1. Beli paket hosting **cPanel** (mis. Niagahoster, Hostinger, Rumahweb) yang mendukung:
   - **PHP 8.4 atau lebih baru** (wajib);
   - MySQL 8 / MariaDB 10.6+;
   - **Terminal** (SSH) di cPanel, sangat disarankan;
   - **Cron Jobs** dan **AutoSSL** (HTTPS gratis).
2. Beli atau hubungkan **domain** (mis. `jafdealer.com`) ke hosting tersebut.
3. Di cPanel > **Select PHP Version**: pilih PHP **8.4/8.5**, centang ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, `xml`, `curl`, `dom`, `zip`. Di *Options*: `upload_max_filesize` = **8M**, `post_max_size` = **80M**.

### B2. Siapkan paket kode di laptop

Gunakan folder **`paket-deploy`** (jangan memakai nama `dealer-mobil-release`, sudah dipakai untuk data). Di terminal Laragon:

```bash
cd /d C:\laragon\www\dealer-mobil
git checkout main
git pull
git archive --format=zip --output=../paket-deploy.zip v1.20.0
```

Ganti `v1.20.0` dengan versi rilis terbaru saat itu. Lalu:

1. Ekstrak `C:\laragon\www\paket-deploy.zip` ke folder baru `C:\laragon\www\paket-deploy`.
2. Di folder itu jalankan:

   ```bash
   cd /d C:\laragon\www\paket-deploy
   composer install --no-dev --optimize-autoloader
   ```

3. Kompres **isi** folder `paket-deploy` menjadi `dealer-mobil.zip`. Paket ini tidak berisi `.env` (aman).
4. Kembali ke branch kerja: `cd /d C:\laragon\www\dealer-mobil` lalu `git checkout testing`.

### B3. Siapkan paket data dari folder release

1. Pastikan data di `http://dealer-mobil-release.test` sudah benar (Pengaturan, rekening, akun owner, logo). Data mobil, layanan, dan staf boleh diisi showroom nanti langsung di server.
2. Buat cadangan:

   ```bash
   cd /d C:\laragon\www\dealer-mobil-release
   php artisan backup:run
   ```

3. Ambil file zip terbaru di `C:\laragon\www\dealer-mobil-release\storage\app\backups\cadangan-....zip`. Isinya `database.sql`, `storage/app/public` (logo, foto), dan `storage/app/private`.

### B4. Upload kode ke hosting

1. cPanel > **File Manager** > buka folder home (mis. `/home/namauser`), **bukan** `public_html`.
2. Upload `dealer-mobil.zip`, lalu **Extract** sehingga terbentuk `/home/namauser/dealer-mobil`.
3. Arahkan domain ke folder `public`:
   - **Disarankan:** cPanel > **Domains** > domain Anda > *Document Root* = `dealer-mobil/public`.
   - Bila domain utama terkunci ke `public_html`: ikuti **Opsi B** di [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 4.

### B5. Buat database

1. cPanel > **MySQL® Database Wizard**: buat database (mis. `namauser_dealer`) dan user dengan kata sandi kuat, beri **ALL PRIVILEGES**.
2. Catat nama database, user, dan kata sandinya.

### B6. Buat `.env` di server

1. File Manager: salin `dealer-mobil/.env.example` menjadi `dealer-mobil/.env`.
2. Ubah baris berikut (sisanya biarkan):

   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda.com
   LOG_LEVEL=warning
   LOG_STACK=daily

   DB_HOST=localhost
   DB_DATABASE=namauser_dealer
   DB_USERNAME=namauser_dealer
   DB_PASSWORD=kata-sandi-database

   SESSION_SECURE_COOKIE=true

   MAIL_MAILER=smtp
   MAIL_HOST=mail.domain-anda.com
   MAIL_PORT=465
   MAIL_SCHEME=smtps
   MAIL_USERNAME=noreply@domain-anda.com
   MAIL_PASSWORD=kata-sandi-email
   MAIL_FROM_ADDRESS=noreply@domain-anda.com
   DEALER_MAIL_NOTIFICATIONS=true
   ```

3. Kosongkan semua `DEALER_NAME` sampai `DEALER_BANK_HOLDER` (data dealer sudah ada di database dari Pengaturan). `ADMIN_EMAIL`, `ADMIN_PASSWORD`, dan `SEED_CUSTOMER_PASSWORD` juga dikosongkan: akun owner ikut dari database release.
4. Email SMTP: buat akun email di cPanel > **Email Accounts** (mis. `noreply@domain-anda.com`), lalu cek **Email Deliverability** (SPF dan DKIM *Valid*). Rincian di [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 6.

### B7. Impor data dari folder release

1. Di laptop, ekstrak zip cadangan dari B3.
2. cPanel > **phpMyAdmin** > pilih database `namauser_dealer` > tab **Import** > pilih `database.sql` > **Go**.
3. File Manager: upload isi folder `storage/app/public` dan `storage/app/private` dari zip ke `dealer-mobil/storage/app/public` dan `dealer-mobil/storage/app/private` di server.

### B8. Perintah di Terminal cPanel

```bash
cd ~/dealer-mobil
php -v
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

- `php -v` harus 8.4 atau lebih baru. Bila tidak, pakai perintah PHP versi hosting (mis. `/opt/cpanel/ea-php85/root/usr/bin/php`).
- `migrate` hanya menambah tabel yang belum ada (aman, data tidak dihapus).
- **Jangan** menjalankan `db:seed`: data sudah dari release.
- Bila muncul *Permission denied*: `chmod -R 775 storage bootstrap/cache`.
- Simpan salinan `.env` server (termasuk `APP_KEY`) di tempat aman.

### B9. Cron, HTTPS, dan uji email

1. **Cron** (pengingat H-1 dan cadangan otomatis): cPanel > **Cron Jobs** > *Once Per Minute* (`* * * * *`):

   ```bash
   cd ~/dealer-mobil && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **HTTPS:** cPanel > **SSL/TLS Status** > *Run AutoSSL*, lalu aktifkan *Force HTTPS Redirect* di **Domains**.
3. **Uji email** (ganti alamat tujuan):

   ```bash
   php artisan tinker --execute='Mail::raw("Tes email dari JAF Dealer.", fn ($m) => $m->to("email-anda@gmail.com")->subject("Tes email JAF Dealer"));'
   ```

4. **Uji cadangan:** `php artisan backup:run` harus menampilkan *Cadangan dibuat*.

### B10. Cek setelah online

1. Buka `https://domain-anda.com`: beranda tampil, gembok HTTPS aktif, nama dan kontak dealer benar.
2. Login di `https://domain-anda.com/login` dengan akun owner (`cruzgzz244@gmail.com`, kata sandi sama seperti di situs release). Ganti kata sandi di **Profil** bila perlu.
3. **Pengaturan**: rekening dan kontak sesuai. Logo Nissan tampil di katalog.
4. `https://domain-anda.com/.env` harus **tidak** bisa dibuka (403/404).
5. Buat akun staf di menu **Staf**, lalu showroom mengisi **Layanan Servis**, **Mobil** (dengan foto), dan **Promo**.
6. Jalankan uji akhir per role di [GO-LIVE.md](GO-LIVE.md) bagian 5.
7. Keesokan harinya, dashboard owner harus menampilkan *Cadangan terakhir* (tanda cron berjalan).

### B11. Setelah online

- **Seminggu sekali:** unduh file cadangan terbaru dari `dealer-mobil/storage/app/backups` di server ke laptop atau Google Drive.
- **Cek berkala:** notifikasi admin, **Staf > Riwayat Aktivitas**, dan log `storage/logs`.
- **Memperbarui website** ke versi baru: ikuti [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 11 (cadangkan dulu, upload kode baru tanpa menimpa `.env` dan `storage/app`, lalu `php artisan migrate --force` dan `php artisan optimize`).
- Setelah website berjalan di server, folder `dealer-mobil-release` di laptop tidak dipakai lagi untuk data (data asli sekarang di server).

---

## C. Ringkasan urutan

1. Hosting + domain siap (B1).
2. Paket kode dari rilis terbaru (B2) dan paket data dari folder release (B3).
3. Upload kode, arahkan domain, buat database, buat `.env` (B4 sampai B6).
4. Impor `database.sql` dan folder `storage` (B7).
5. Perintah Terminal, cron, HTTPS, uji email dan cadangan (B8, B9).
6. Cek dan uji akhir, lalu showroom mengisi data (B10).
7. Repo lama dihapus bila semua beres (A3).
