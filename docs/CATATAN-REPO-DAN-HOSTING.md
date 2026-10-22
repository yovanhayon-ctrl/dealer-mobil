# Catatan Repo dan Hosting dari Awal

Catatan per **22 Oktober 2026**, versi aplikasi **v1.20.1**.

| Bagian | Isi |
|---|---|
| **A** | Repo GitHub, cadangan repo, folder di laptop, file `.env`, dan dari mana kode serta data saat hosting |
| **B** | Langkah deploy ke hosting cPanel dari nol sampai online (B0 sampai B12) |
| **C** | Hal yang perlu diperhatikan |
| **D** | Masalah umum dan cara mengatasinya |
| **E** | Ringkasan urutan |

Rincian teknis tambahan ada di DEPLOY-CPANEL.md dan daftar periksa di GO-LIVE.md (folder `docs`).

---

## A. Repo, folder, dan `.env`

### A1. Repo GitHub

| Repo | Status | Kegunaan |
|---|---|---|
| `yovanhayon-ctrl/dealer-mobil` | **Public**, repo yang dipakai | Semua pekerjaan (push, merge, tag). Riwayat lengkap 193 commit dan 29 tag (v1.0.0 sampai v1.20.1), semua atas nama Yovan Hayon, tanpa atribusi Claude. |
| `yovanhayon-ctrl/dealer-mobil-lama` | **Private**, arsip | Repo lama sebelum riwayat dibersihkan. Isi kodenya identik. Tidak dipakai lagi. |

- Pada 22 Oktober 2026 riwayat commit dibersihkan dari baris `Co-Authored-By: Claude` lalu dipindahkan ke repo baru. Yang berubah hanya kode hash commit; isi kode, penulis, tanggal, judul commit, dan tag tetap sama.
- Pengaman di laptop: file `.git\hooks\commit-msg` di folder `dealer-mobil` otomatis menghapus baris atribusi Claude dari setiap commit dan merge. File ini tidak ikut ke GitHub.

### A2. Cadangan repo

- File: `C:\laragon\www\dealer-mobil-backup-2026-10-22.bundle` (sekitar 4,3 MB), berisi seluruh repo **sebelum** dibersihkan.
- Simpan juga salinannya di luar laptop (Google Drive, flashdisk).
- Cara memakai bila suatu saat perlu:

  ```bash
  git clone C:/laragon/www/dealer-mobil-backup-2026-10-22.bundle dealer-mobil-pulih
  ```

### A3. Kapan repo lama boleh dihapus

Hapus `dealer-mobil-lama` hanya bila semua ini sudah benar:

- [ ] Repo baru `dealer-mobil` menampilkan semua folder, semua tag, dan branch `main` + `testing`.
- [ ] Sudah pernah push dan merge di repo baru tanpa masalah.
- [ ] Website sudah online dari kode repo baru (bagian B).
- [ ] File `.bundle` di A2 sudah disalin ke tempat aman.

Cara menghapus: GitHub > repo `dealer-mobil-lama` > **Settings** > *Danger Zone* > **Delete this repository**. Tidak bisa dibatalkan. Membiarkannya tetap private juga tidak masalah.

### A4. Folder di laptop

| Folder | Isi | Aturan |
|---|---|---|
| `C:\laragon\www\dealer-mobil` | **Folder utama.** Kode (branch `testing`), database `dealer_mobil` berisi data **dummy** | Tempat mengubah kode, test, `git add/commit/push`. Situs: `http://dealer-mobil.test`. |
| `C:\laragon\www\dealer-mobil-release` | Kode versi rilis (saat ini **v1.20.1**), database `dealer_mobil_release` berisi data **asli** | Tempat mengisi data asli sebelum online. **Jangan** commit di sini, **jangan** hapus lewat File Explorer (terhubung ke git sebagai *worktree*). Situs: `http://dealer-mobil-release.test`. |
| `C:\laragon\www\paket-deploy` | Dibuat saat deploy (B2), berisi kode bersih untuk hosting | Boleh dihapus setelah upload selesai. |

Sebelum `git commit` atau `git push`, pastikan terminal berada di `C:\laragon\www\dealer-mobil` (di folder utama tampil `(testing -> origin)` setelah path).

### A5. Tiga file `.env`

| File | Isi | Ikut ke server? |
|---|---|---|
| `dealer-mobil\.env` | Pengaturan laptop untuk pengembangan; `DEALER_*` berisi data **contoh** (rekening "PT JAF Dealer (Demo)") | Tidak. Biarkan apa adanya. |
| `dealer-mobil-release\.env` | Pengaturan laptop untuk data asli; `DEALER_*` **dikosongkan** karena data dealer ada di menu Pengaturan (database) | Tidak. |
| `.env` di server | Dibuat **baru** dari `.env.example` saat hosting (B6) | Ya, hanya di server. |

File `.env` tidak pernah masuk ke git atau GitHub. Jangan dikirim lewat chat dan jangan diunggah dari laptop ke server.

### A6. Yang diunggah ke cPanel

Ada **dua** hal yang dibawa ke hosting, keduanya **bukan** hasil men-zip folder kerja secara langsung:

| Yang diunggah | Berasal dari | Dibuat dengan | Langkah |
|---|---|---|---|
| **`dealer-mobil.zip`** (kode) | Folder **`paket-deploy`**, hasil `git archive` versi **v1.20.1** dari folder utama | `git archive` lalu `composer install --no-dev`, lalu kompres isi folder | B2, B4 |
| **`database.sql`** + folder **`storage/app/public`** dan **`storage/app/private`** (data) | Zip cadangan dari folder **`dealer-mobil-release`** | `php artisan backup:run` | B3, B7 |

**Jangan:**
- men-zip folder `dealer-mobil` atau `dealer-mobil-release` lalu mengunggahnya: ikut membawa `.env` laptop (kata sandi Gmail, `APP_KEY`, `APP_DEBUG=true`), paket test, log, sesi login, cadangan, dan file penghubung git;
- memakai database `dealer_mobil` (folder utama): isinya data **dummy**, sengaja dipertahankan untuk mencoba fitur dan demo.

---

## B. Deploy ke hosting cPanel dari nol

### B0. Daftar yang perlu disiapkan

- [ ] Akun hosting cPanel dan domain (B1).
- [ ] Laragon menyala di laptop (untuk B2 dan B3).
- [ ] Data di `http://dealer-mobil-release.test` sudah benar: Pengaturan, rekening, akun owner, logo.
- [ ] Alamat email untuk website (mis. `noreply@domain-anda.com`), dibuat di cPanel (B6).
- [ ] Waktu sekitar 1 sampai 2 jam tanpa terburu-buru.

### B1. Siapkan hosting dan domain

1. Beli paket hosting **cPanel** (mis. Niagahoster, Hostinger, Rumahweb) yang mendukung:
   - **PHP 8.4 atau lebih baru** (wajib);
   - MySQL 8 / MariaDB 10.6+;
   - **Terminal** (SSH) di cPanel, sangat disarankan;
   - **Cron Jobs** dan **AutoSSL** (HTTPS gratis).
2. Beli atau hubungkan **domain** (mis. `jafdealer.com`) ke hosting tersebut.
3. cPanel > **Select PHP Version**:
   - pilih PHP **8.4** atau **8.5**;
   - centang ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, `xml`, `curl`, `dom`, `zip`;
   - tab *Options*: `upload_max_filesize` = **8M**, `post_max_size` = **80M**.

### B2. Buat paket kode di laptop (`dealer-mobil.zip`)

Semua perintah dijalankan **di laptop** lewat **Terminal Laragon**, bukan di server.

| Urutan | Perintah / tindakan | Dijalankan di | Hasil |
|---|---|---|---|
| 1 | `cd /d C:\laragon\www\dealer-mobil` | Terminal Laragon | Masuk ke folder utama |
| 2 | `git fetch origin --tags` | folder utama `dealer-mobil` | Tag versi terbaru dari GitHub tersedia |
| 3 | `git archive --format=zip --output=../paket-deploy.zip v1.20.1` | folder utama `dealer-mobil` | File `C:\laragon\www\paket-deploy.zip` berisi kode bersih (tanpa `.env`, tanpa data) |
| 4 | Klik kanan `paket-deploy.zip` > **Extract All** ke `C:\laragon\www\paket-deploy` | File Explorer | Folder `paket-deploy` |
| 5 | `cd /d C:\laragon\www\paket-deploy` lalu `composer install --no-dev --optimize-autoloader` | folder **`paket-deploy`** | Folder `vendor` versi produksi |
| 6 | Buka folder `paket-deploy`, pilih **semua isinya** (`app`, `bootstrap`, `config`, `public`, `vendor`, `artisan`, dst.), klik kanan > **Compress to ZIP**, beri nama `dealer-mobil.zip` | File Explorer | File yang diunggah ke hosting (B4) |

- Pada langkah 6 yang dikompres adalah **isi** folder, bukan folder `paket-deploy`-nya, agar setelah diekstrak di server isinya langsung berada di `dealer-mobil`.
- Untuk rilis berikutnya, ganti `v1.20.1` dengan versi terbaru (lihat README atau tag di GitHub).
- `git archive` memakai tag, jadi **tidak perlu** pindah branch.
- **Jangan** menjalankan `composer install --no-dev` di folder `dealer-mobil` atau `dealer-mobil-release`: paket test terhapus dan pengembangan di laptop bisa berhenti berjalan.

### B3. Buat paket data dari folder release

1. Pastikan data di `http://dealer-mobil-release.test` sudah benar. Mobil, layanan, staf, dan promo boleh diisi showroom nanti langsung di server.
2. Di Terminal Laragon:

   ```bash
   cd /d C:\laragon\www\dealer-mobil-release
   php artisan backup:run
   ```

3. Hasilnya: `C:\laragon\www\dealer-mobil-release\storage\app\backups\cadangan-TANGGAL-JAM.zip`.
4. Klik kanan zip itu > **Extract All** ke folder sementara (mis. `C:\laragon\www\data-deploy`). Isinya:
   - `database.sql`;
   - `storage\app\public` (logo, foto, banner);
   - `storage\app\private` (bukti pembayaran; kosong bila belum ada transaksi).

### B4. Upload kode ke hosting

1. cPanel > **File Manager** > buka folder home (mis. `/home/namauser`), **bukan** `public_html`.
2. Klik **+ Folder**, buat folder `dealer-mobil`, lalu masuk ke folder itu.
3. **Upload** `dealer-mobil.zip`, lalu klik kanan > **Extract**. Pastikan `/home/namauser/dealer-mobil` berisi `app`, `public`, `vendor`, `artisan`, dan seterusnya (bukan subfolder lain di dalamnya).
4. Hapus `dealer-mobil.zip` dari server setelah diekstrak.
5. Arahkan domain ke folder `public`:
   - **Disarankan:** cPanel > **Domains** > domain Anda > *Manage* > *Document Root* = `dealer-mobil/public`.
   - Bila domain utama terkunci ke `public_html`: salin isi `dealer-mobil/public` ke `public_html` dan ubah `public_html/index.php` (lihat **Opsi B** di DEPLOY-CPANEL.md bagian 4).

### B5. Buat database

1. cPanel > **MySQL® Database Wizard**:
   - nama database, mis. `dealer` (menjadi `namauser_dealer`);
   - user, mis. `dealer` (menjadi `namauser_dealer`) dengan kata sandi kuat (pakai *Password Generator*);
   - centang **ALL PRIVILEGES**.
2. Catat nama database, user, dan kata sandinya di tempat aman.

### B6. Email dan `.env` di server

**Email website:**
1. cPanel > **Email Accounts** > *Create*: buat `noreply@domain-anda.com` dengan kata sandi kuat.
2. Klik **Connect Devices** pada akun itu, catat *Outgoing Server* (biasanya `mail.domain-anda.com`) dan port SMTP (465 atau 587).
3. cPanel > **Email Deliverability**: pastikan **SPF** dan **DKIM** berstatus *Valid* (klik *Repair* bila belum).

**File `.env`:**
1. File Manager > folder `dealer-mobil` > aktifkan *Settings* > **Show Hidden Files**.
2. Klik kanan `.env.example` > **Copy** dengan nama `.env`, lalu **Edit** `.env`.
3. Ubah baris berikut (sisanya biarkan):

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

4. Bila port SMTP **587**: `MAIL_PORT=587` dan `MAIL_SCHEME=smtp`.
5. **Kosongkan** `DEALER_NAME` sampai `DEALER_BANK_HOLDER` (data dealer sudah ada di database dari Pengaturan), juga `ADMIN_EMAIL`, `ADMIN_PASSWORD`, dan `SEED_CUSTOMER_PASSWORD` (akun owner ikut dari database release).
6. **Save Changes**.

### B7. Impor data dari folder release

**Database:**
1. cPanel > **phpMyAdmin** > klik database `namauser_dealer` di kiri.
2. Tab **Import** > *Choose File* > pilih `database.sql` (dari folder `data-deploy` di B3) > **Import** (atau **Go**).
3. Berhasil bila muncul pesan sukses dan tabel seperti `users`, `settings`, `brands` tampil di kiri.
4. Bila file terlalu besar untuk phpMyAdmin, pakai Terminal (B8): unggah `database.sql` ke folder home lalu jalankan `mysql -u namauser_dealer -p namauser_dealer < ~/database.sql`.

**File (logo, foto, bukti pembayaran):**
1. File Manager > buka `dealer-mobil/storage/app/public`.
2. Upload **isi** folder `data-deploy\storage\app\public` ke sini (paling mudah: zip isinya di laptop, upload, lalu **Extract** di server). Contoh hasil: `dealer-mobil/storage/app/public/brands/...`.
3. Lakukan hal yang sama untuk `storage\app\private` ke `dealer-mobil/storage/app/private` (bila ada isinya).
4. Hapus file zip sementara dari server dan folder `data-deploy` dari laptop.

### B8. Perintah di Terminal cPanel

cPanel > **Terminal**, lalu:

```bash
cd ~/dealer-mobil
php -v
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

- `php -v` harus 8.4 atau lebih baru. Bila tidak, pakai perintah PHP versi hosting (mis. `/opt/cpanel/ea-php85/root/usr/bin/php artisan ...`).
- `key:generate` membuat `APP_KEY` baru khusus server. Simpan salinan `.env` server (termasuk `APP_KEY`) di tempat aman.
- `migrate` hanya menambah tabel yang belum ada; data dari release **tidak** dihapus.
- **Jangan** menjalankan `php artisan db:seed`: data sudah dari release.
- Bila muncul *Permission denied*: `chmod -R 775 storage bootstrap/cache`.
- Tanpa Terminal: lihat DEPLOY-CPANEL.md bagian 9.

### B9. HTTPS

1. cPanel > **SSL/TLS Status** > centang domain > *Run AutoSSL*. Tunggu sampai status hijau.
2. cPanel > **Domains** > aktifkan **Force HTTPS Redirect**.
3. Buka `https://domain-anda.com`: gembok harus tampil di browser.

### B10. Cron (pengingat dan cadangan otomatis)

cPanel > **Cron Jobs** > *Add New Cron Job* > *Common Settings*: **Once Per Minute** (`* * * * *`), *Command*:

```bash
cd ~/dealer-mobil && php artisan schedule:run >> /dev/null 2>&1
```

Cek di Terminal:

```bash
php artisan schedule:list
php artisan backup:run
```

`schedule:list` harus menampilkan `reminders:send` (16.00) dan `backup:run` (02.00); `backup:run` harus menampilkan *Cadangan dibuat*.

### B11. Uji email

Di Terminal (ganti alamat tujuan dengan email Anda):

```bash
php artisan tinker --execute='Mail::raw("Tes email dari JAF Dealer.", fn ($m) => $m->to("email-anda@gmail.com")->subject("Tes email JAF Dealer"));'
```

Email harus masuk (cek juga folder Spam). Bila gagal, periksa `MAIL_*` di `.env`, jalankan `php artisan optimize`, lalu uji lagi.

### B12. Cek setelah online

1. Buka `https://domain-anda.com`: beranda tampil, gembok HTTPS aktif, nama, kontak, dan hero sesuai Pengaturan.
2. Login di `https://domain-anda.com/login` dengan akun owner (`cruzgzz244@gmail.com`, kata sandi sama seperti di situs release). Ganti kata sandi di **Profil** bila perlu.
3. **Pengaturan**: rekening dan kontak benar. Logo Nissan tampil di katalog.
4. `https://domain-anda.com/.env` harus **tidak** bisa dibuka (403/404).
5. Coba **Lupa kata sandi** dengan email owner: email reset harus terkirim.
6. Buat akun staf di menu **Staf**; showroom mengisi **Layanan Servis**, **Mobil** (dengan foto), dan **Promo**.
7. Uji alur lengkap per peran (GO-LIVE.md bagian 5): daftar customer, test drive, pengajuan, bukti bayar, verifikasi, servis, mekanik.
8. Keesokan harinya, dashboard owner menampilkan *Cadangan terakhir* (tanda cron berjalan).

### Setelah online

- **Seminggu sekali:** unduh file cadangan terbaru dari `dealer-mobil/storage/app/backups` ke laptop atau Google Drive.
- **Cek berkala:** notifikasi admin, **Staf > Riwayat Aktivitas**, log `storage/logs`.
- **Memperbarui ke versi baru:** cadangkan dulu (`php artisan backup:run` dan unduh), buat paket kode baru seperti B2 dengan tag versi baru, upload dan ekstrak **tanpa menimpa** `.env`, `storage/app/public`, dan `storage/app/private`, lalu:

  ```bash
  cd ~/dealer-mobil
  php artisan down
  php artisan migrate --force
  php artisan optimize
  php artisan up
  ```

- Setelah website berjalan di server, folder `dealer-mobil-release` di laptop tidak dipakai lagi untuk data (data asli sekarang di server).

---

## C. Yang perlu diperhatikan

**Keamanan**
- `.env` server berisi kata sandi database, email, dan `APP_KEY`: jangan dibagikan; simpan salinannya sendiri (cadangan otomatis **tidak** menyimpan `.env`).
- Di server wajib `APP_ENV=production` dan `APP_DEBUG=false`.
- `SESSION_SECURE_COOKIE=true` hanya setelah HTTPS aktif.
- Pastikan `https://domain-anda.com/.env` tidak bisa dibuka.
- Akun owner memakai kata sandi yang sama seperti di situs release: ganti bila pernah dibagikan.

**Data**
- **Jangan** menjalankan `php artisan db:seed` di server.
- **Jangan** mengimpor database `dealer_mobil` (folder utama): isinya data dummy.
- Cek ulang rekening pembayaran setelah online: customer mentransfer ke rekening itu.
- `storage/app/private` berisi bukti pembayaran customer (data pribadi): jangan dibuat publik.

**Hosting dan pembaruan**
- PHP minimal **8.4**.
- Tanpa **cron**, pengingat H-1 dan cadangan otomatis tidak berjalan (dashboard owner memperingatkan).
- SMTP harus benar agar *Lupa kata sandi* dan notifikasi terkirim; SPF dan DKIM *Valid* agar tidak masuk Spam.
- Saat memperbarui: **jangan menimpa** `.env`, `storage/app/public`, `storage/app/private`.
- Unduh cadangan dari server secara rutin; cadangan di server ikut hilang bila hosting bermasalah.

**Repo dan folder di laptop**
- Commit dan push hanya dari folder utama `dealer-mobil` (branch `testing`, lalu merge ke `main`).
- Jangan menghapus atau memindahkan `dealer-mobil-release` lewat File Explorer; minta dilepas dengan `git worktree remove` bila sudah tidak diperlukan.
- Kode di `dealer-mobil-release` dikunci di versi rilis tertentu (saat ini v1.20.1).
- Simpan `dealer-mobil-backup-2026-10-22.bundle` sampai repo lama dihapus.

---

## D. Masalah umum

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Halaman putih / *500 Server Error* | Izin folder, versi PHP, atau `.env` salah | `chmod -R 775 storage bootstrap/cache`; cek `php -v`; cek `.env`; lihat `storage/logs/laravel-TANGGAL.log` |
| *No application encryption key* | `APP_KEY` kosong | `php artisan key:generate --force` lalu `php artisan optimize` |
| *Could not find driver* / gagal koneksi database | Ekstensi `pdo_mysql` mati atau `DB_*` salah | Centang `pdo_mysql` di Select PHP Version; cek nama database, user, kata sandi (dengan awalan `namauser_`) |
| Situs menampilkan daftar file atau *403* | Document Root belum ke `dealer-mobil/public` | Ulangi B4 langkah 5 |
| Logo/foto tidak tampil | `storage:link` belum dijalankan atau file belum diunggah | `php artisan storage:link`; cek isi `storage/app/public`; pada Opsi B buat tautan manual (DEPLOY-CPANEL.md bagian 7) |
| Tidak bisa login (kembali ke halaman login / *Page Expired*) | `SESSION_SECURE_COOKIE=true` tetapi belum HTTPS, atau `APP_URL` salah | Aktifkan HTTPS (B9) dan samakan `APP_URL` dengan alamat `https://` |
| Email tidak terkirim | `MAIL_*` salah, port/scheme tidak cocok | Cek B6, uji B11, lihat log |
| Dashboard: *Belum ada cadangan otomatis* | Cron belum berjalan atau ekstensi `zip` mati | Cek B10; centang `zip` di Select PHP Version |
| Perubahan `.env` tidak terbaca | Konfigurasi masih ter-cache | `php artisan optimize` |

---

## E. Ringkasan urutan

1. Siapkan hosting, domain, dan versi PHP (B1).
2. Buat paket kode dari tag **v1.20.1** (B2) dan paket data dari folder release (B3).
3. Upload kode, arahkan domain ke `dealer-mobil/public` (B4).
4. Buat database (B5), email, dan `.env` server (B6).
5. Impor `database.sql` dan folder `storage` (B7).
6. Jalankan perintah Terminal (B8).
7. Aktifkan HTTPS (B9) dan cron (B10), uji email (B11).
8. Cek setelah online dan uji alur per peran (B12); showroom mengisi data.
9. Rutin unduh cadangan; hapus repo lama bila semua beres (A3).
