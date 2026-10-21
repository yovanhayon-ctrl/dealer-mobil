# Deploy ke Shared Hosting (cPanel) — JAF Dealer

Panduan memasang aplikasi di shared hosting berbasis cPanel (mis. Niagahoster, Hostinger, Rumahweb, IDCloudHost). Tidak perlu queue worker: notifikasi (lonceng + email) dikirim langsung. **Cron disarankan** (satu baris, bagian 7) untuk pengingat H-1 test drive & servis; tanpa cron, pengingat dikirim lewat tombol *Kirim Pengingat Besok* di dashboard.

Berlaku untuk rilis **v1.4.0** (notifikasi customer & admin, email, favorit, bandingkan mobil, grafik dashboard, ulasan, bukti pengajuan PDF). Untuk dipakai klien, gunakan subdomain atau domain tambahan dengan document root ke folder `public` (bagian 4, opsi A).

Untuk go-live pertama, ikuti juga [GO-LIVE.md](GO-LIVE.md) (urutan kerja dan daftar periksa lengkap).

## 1. Syarat hosting (cek sebelum membeli/memakai)

| Kebutuhan | Keterangan |
|---|---|
| **PHP 8.4 atau lebih baru (disarankan 8.5)** | **Wajib.** Paket yang terkunci di `composer.lock` (Symfony 8) membutuhkan PHP ≥ 8.4.1. Di PHP 8.3 aplikasi **tidak** akan berjalan. Atur di cPanel → *Select PHP Version* / *MultiPHP Manager*. |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` (validasi upload), `ctype`, `tokenizer`, `xml`, `curl`, `dom` (PDF bukti pengajuan, dompdf), `zip` (export laporan Excel). |
| Batas upload PHP | `upload_max_filesize` ≥ **8M** dan `post_max_size` ≥ **80M** (galeri bisa mengunggah beberapa foto HP sekaligus, maks. 8 MB per foto; banner promo juga maks. 8 MB). Atur di cPanel → *Select PHP Version* → *Options* / *MultiPHP INI Editor*. |
| Database | MySQL 8 (atau MariaDB 10.6+). |
| Terminal / SSH di cPanel | **Sangat disarankan** (untuk `php artisan`). Tanpa Terminal lihat bagian 9. |
| SSL | AutoSSL / Let's Encrypt (biasanya gratis di cPanel). |

## 2. Siapkan paket di laptop

Gunakan **folder terpisah** agar folder development (yang berisi PHPUnit, Pint, dll.) tidak berubah. Dari Git Bash/Cmder:

```bash
cd C:/laragon/www/dealer-mobil
git checkout main
git pull
git archive --format=zip --output=../dealer-mobil-release.zip HEAD
```

Ekstrak `dealer-mobil-release.zip` ke folder baru (mis. `C:/laragon/www/dealer-mobil-release`), lalu di folder itu:

```bash
composer install --no-dev --optimize-autoloader
```

Perintah ini juga memasang `dompdf/dompdf` (fitur **Cetak PDF**) dan `phpoffice/phpspreadsheet` (fitur **Export Excel** laporan), jadi folder `vendor` di paket sudah lengkap. Kompres ulang isi folder tersebut menjadi `dealer-mobil.zip`. Paket **tidak** boleh berisi `.env` (berisi rahasia) — `git archive` sudah tidak menyertakannya.

## 3. Upload

1. cPanel → **File Manager** → buka folder home (mis. `/home/namauser`), **bukan** `public_html`.
2. Upload `dealer-mobil.zip`, lalu **Extract** sehingga terbentuk `/home/namauser/dealer-mobil` (berisi `app`, `public`, `vendor`, …).

Kode aplikasi sengaja diletakkan di luar `public_html` agar file seperti `.env` dan `storage` tidak bisa diakses dari internet.

## 4. Arahkan domain ke folder `public`

**Opsi A — disarankan (domain tambahan / subdomain):**
cPanel → **Domains** → *Create A New Domain* (atau ubah domain yang ada) → *Document Root* = `dealer-mobil/public`.

**Opsi B — domain utama yang terkunci ke `public_html`:**

1. Salin **isi** folder `dealer-mobil/public` (termasuk `.htaccess`) ke `public_html`.
2. Edit `public_html/index.php`, ubah path menjadi folder aplikasi dan beri tahu Laravel lokasi folder publik baru:

```php
if (file_exists($maintenance = __DIR__.'/../dealer-mobil/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../dealer-mobil/vendor/autoload.php';

$app = require_once __DIR__.'/../dealer-mobil/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
```

`usePublicPath(__DIR__)` **wajib** pada opsi B: tanpa itu versi CSS/JS (`?v=`) dan tautan foto `storage` menunjuk ke folder yang salah. Setiap kali memperbarui file di `dealer-mobil/public` (CSS/JS), salin ulang ke `public_html`.

## 5. Database

1. cPanel → **MySQL® Database Wizard**: buat database (mis. `namauser_dealer`), user (mis. `namauser_dealer`) dengan kata sandi kuat, beri **ALL PRIVILEGES**.
2. Catat nama database, user, dan kata sandi (cPanel menambahkan awalan `namauser_`).

## 6. Berkas `.env` production

Di File Manager, salin `dealer-mobil/.env.example` menjadi `dealer-mobil/.env`, lalu ubah minimal:

```env
APP_NAME="JAF Dealer"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com
LOG_LEVEL=warning
LOG_STACK=daily
LOG_DAILY_DAYS=14

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
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

DEALER_NAME="JAF Dealer"
DEALER_TAGLINE="Dream the Legacy. Drive the Future."
DEALER_ADDRESS="…"
DEALER_PHONE="…"
DEALER_WHATSAPP=62…
DEALER_EMAIL=…
DEALER_HOURS="Senin-Sabtu 08.00-17.00 WIB"
# Rekening resmi dealer (tampil untuk customer setelah pengajuan disetujui)
DEALER_BANK_NAME="BCA"
DEALER_BANK_ACCOUNT="…"
DEALER_BANK_HOLDER="PT …"
# Batas pembayaran pertama (hari) setelah pengajuan disetujui
DEALER_PAYMENT_DUE_DAYS=3
# Jam (WIB) pengingat H-1 test drive & servis
DEALER_REMINDER_TIME=16:00
DEALER_MAPS_EMBED_URL="https://www.google.com/maps/embed?pb=…"

ADMIN_EMAIL=admin@domain-anda.com
ADMIN_PASSWORD=kata-sandi-admin-yang-kuat
```

- `MAIL_*`: **wajib** diisi SMTP sungguhan, karena fitur *Lupa kata sandi* hanya bisa dipakai bila email benar-benar terkirim (tidak bergantung pada `DEALER_MAIL_NOTIFICATIONS`). Langkahnya ada di bagian *Email* di bawah. Pastikan `ADMIN_EMAIL` adalah alamat yang benar-benar dibaca.
- `DEALER_MAIL_NOTIFICATIONS` mengatur **email notifikasi** (customer: status test drive/pengajuan/pembayaran/servis/ulasan berubah; admin: booking, pengajuan, bukti pembayaran, pembatalan, atau ulasan baru). `false` = hanya lonceng 🔔. Kegagalan SMTP tidak menggagalkan proses, tetapi setting yang salah membuat aksi terasa lambat; cek log di `storage/logs/`.
- `LOG_STACK=daily`: log ditulis per hari (`storage/logs/laravel-2026-10-15.log`, dst.) dan file yang lebih tua dari `LOG_DAILY_DAYS` hari dihapus otomatis, sehingga tidak memenuhi kuota hosting.
- `DEALER_*` diisi **data klien** (nama dealer, alamat, telepon, WhatsApp, email, jam buka, peta): tampil di navbar, footer, Kontak, tombol WhatsApp, email, kop **PDF bukti pengajuan**, dan bagian kontak **Kebijakan Privasi** / **Syarat & Ketentuan**.
- Isi `/kebijakan-privasi` dan `/syarat-ketentuan` adalah templat yang disesuaikan dengan fitur situs, **bukan nasihat hukum**: minta pemilik dealer (atau penasihat hukumnya) membacanya sebelum online. Teksnya ada di `resources/views/pages/legal/`; bila diubah, perbarui tanggal berlaku di `PageController::LEGAL_EFFECTIVE_DATE`.
- `SESSION_SECURE_COOKIE=true` hanya bila situs sudah memakai **HTTPS**.
- `SEED_CUSTOMER_PASSWORD` dibiarkan kosong: customer dummy tidak dibuat di production.
- Peta Google: Google Maps → *Bagikan* → *Sematkan peta* → salin URL di dalam `src="…"`.

### Email (SMTP dari cPanel)

1. cPanel → **Email Accounts** → *Create*: buat mis. `noreply@domain-anda.com` dengan kata sandi kuat.
2. Klik **Connect Devices** pada akun itu, catat *Outgoing Server* (biasanya `mail.domain-anda.com`) dan port SMTP.
3. Isi `.env`:
   - Port **465** (SSL): `MAIL_PORT=465` dan `MAIL_SCHEME=smtps`.
   - Port **587** (STARTTLS): `MAIL_PORT=587` dan `MAIL_SCHEME=smtp`.
   - `MAIL_USERNAME` dan `MAIL_FROM_ADDRESS` = alamat email lengkap tadi (harus sama, banyak hosting menolak pengirim yang berbeda).
4. cPanel → **Email Deliverability**: pastikan **SPF** dan **DKIM** berstatus *Valid* (klik *Repair* bila belum). Tanpa ini email reset kata sandi mudah masuk folder Spam.
5. Setelah bagian 7 selesai, uji kirim (ganti alamat tujuan dengan email Anda):

   ```bash
   php artisan tinker --execute='Mail::raw("Tes email dari JAF Dealer.", fn ($m) => $m->to("email-anda@gmail.com")->subject("Tes email JAF Dealer"));'
   ```

   Tidak ada pesan error dan email masuk (cek juga folder Spam) = SMTP benar. Bila error, perbaiki `MAIL_*`, jalankan `php artisan optimize`, lalu uji lagi. Tanpa Terminal: uji lewat halaman **Lupa kata sandi** dan lihat log di `storage/logs/`.

## 7. Perintah di Terminal cPanel

cPanel → **Terminal** (pastikan versi PHP CLI ≥ 8.4: `php -v`; beberapa hosting memakai `ea-php85` atau `/opt/cpanel/ea-php85/root/usr/bin/php`):

```bash
cd ~/dealer-mobil
php -v
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan user:make-owner email-admin@dealer.com
php artisan storage:link
php artisan optimize
```

- `db:seed --force` di production membuat: akun admin (dari `ADMIN_EMAIL`/`ADMIN_PASSWORD`), merek Nissan, kategori, 14 mobil Nissan contoh, 5 promo, dan 6 layanan servis. Data dummy customer, transaksi, dan ulasan contoh **tidak** dibuat (hanya local/testing), sehingga bagian *Kata Pelanggan* di beranda baru muncul setelah ada ulasan customer sungguhan yang disetujui admin. Lewati perintah ini bila ingin mengisi data sendiri lewat admin (akun admin tetap dibutuhkan: jalankan `php artisan db:seed --class=AdminUserSeeder --force`).
- Opsi B (`public_html`): perintah `artisan` di terminal **tidak** membaca `public_html/index.php`, jadi `storage:link` membuat tautan di `dealer-mobil/public/storage` (tidak dipakai). Buat tautan untuk `public_html` secara manual:
  `ln -s ~/dealer-mobil/storage/app/public ~/public_html/storage`
- `user:make-owner` menjadikan akun admin dari `ADMIN_EMAIL` sebagai **owner** (pemilik dealer). Owner (dan Super Admin) membuat akun staf lain di menu **Staf**: Super Admin, Admin Dealer, Sales, Customer Service, dan Mekanik. Hak tiap peran: README bagian *Peran Staf*.
- Izin folder (bila muncul error "Permission denied"): `chmod -R 775 storage bootstrap/cache`.

### Cron untuk pengingat H-1 (disarankan)

Pengingat H-1 dikirim otomatis setiap hari pukul `DEALER_REMINDER_TIME` (bawaan 16:00 WIB) ke customer yang jadwal test drive/servisnya **dikonfirmasi** untuk besok (dan ke mekanik yang ditugaskan), serta ke customer yang **batas pembayaran pertamanya** jatuh besok.

1. cPanel → **Cron Jobs** → *Add New Cron Job* → *Common Settings*: **Once Per Minute** (`* * * * *`).
2. Command (sesuaikan path PHP bila hosting memakai `ea-php85`):

   ```bash
   cd ~/dealer-mobil && php artisan schedule:run >> /dev/null 2>&1
   ```

3. Cek: `php artisan schedule:list` menampilkan `reminders:send`; `php artisan reminders:send --dry-run` menampilkan jadwal besok yang akan diingatkan tanpa mengirim.

Tanpa cron: Owner/Super Admin/Admin Dealer menekan **Kirim Pengingat Besok** di dashboard (aman ditekan berulang; jadwal yang sudah diingatkan dilewati). Pengingat juga bisa dikirim manual per customer lewat tombol **Ingatkan via WhatsApp** di detail test drive/servis.

## 8. HTTPS

1. cPanel → **SSL/TLS Status** → *Run AutoSSL*.
2. Pastikan `APP_URL` memakai `https://` dan `SESSION_SECURE_COOKIE=true`, lalu `php artisan optimize`.
3. Paksa HTTPS (bila hosting belum otomatis): cPanel → **Domains** → aktifkan *Force HTTPS Redirect*.

Header keamanan (CSP, HSTS saat HTTPS, dll.) sudah dipasang aplikasi; aturan cache & gzip ada di `public/.htaccess` dan aktif otomatis bila modul Apache tersedia.

## 9. Jika hosting tidak menyediakan Terminal

- **APP_KEY:** di laptop jalankan `php artisan key:generate --show`, salin hasilnya (`base64:…`) ke `APP_KEY=` di `.env` server.
- **Database:** di laptop, siapkan database bersih lalu ekspor dan impor via **phpMyAdmin**:
  1. Buat database lokal kosong (mis. `dealer_mobil_release`), arahkan `.env` sementara ke database itu dengan `APP_ENV=production`, isi `ADMIN_EMAIL`/`ADMIN_PASSWORD` milik production.
  2. `php artisan migrate --seed --force`
  3. Ekspor database itu (HeidiSQL/phpMyAdmin Laragon) → impor di phpMyAdmin hosting.
  4. Kembalikan `.env` lokal seperti semula.
  Jangan mengekspor database development `dealer_mobil` (berisi customer dummy & data uji).
- **storage:link & optimize:** minta bantuan support hosting, atau gunakan menu *Cron Jobs* untuk menjalankan sekali `cd ~/dealer-mobil && php artisan storage:link && php artisan optimize` lalu hapus cron tersebut.

## 10. Cek setelah online

- [ ] Beranda, katalog, detail mobil, promo, simulasi, servis, kontak tampil normal (tidak ada halaman debug).
- [ ] Login admin berhasil; tambah foto mobil dan foto tampil di katalog.
- [ ] Daftar akun customer baru → booking test drive → muncul di admin.
- [ ] *Lupa kata sandi* mengirim email (cek juga folder Spam) dan tautan reset bisa dipakai untuk masuk.
- [ ] Footer memuat **Kebijakan Privasi** dan **Syarat & Ketentuan** dengan kontak dealer yang benar.
- [ ] Admin mengubah status booking test drive tadi → customer melihat lonceng 🔔 dan menerima email; admin menerima notifikasi booking baru.
- [ ] **Pengajuan Saya** → **Cetak PDF** membuka bukti pengajuan dengan kop data klien.
- [ ] Favorit (❤), **Bandingkan**, dan grafik **Tren 6 Bulan** di dashboard admin tampil (grafik butuh akses ke cdn.jsdelivr.net).
- [ ] Gembok HTTPS aktif; `https://domain-anda.com/.env` **tidak** bisa dibuka (harus 403/404).

## 11. Memperbarui aplikasi (rilis berikutnya)

1. **Cadangkan dulu** database dan folder `storage` (bagian 12).
2. Siapkan paket baru seperti bagian 2 dan upload/ekstrak menimpa folder `dealer-mobil` (**jangan** menimpa `.env`, `storage/app/public`, dan `storage/app/private` yang berisi bukti pembayaran customer). Folder `vendor` ikut diganti, sehingga dependency baru (mis. dompdf di v1.4.0) ikut terpasang. Bila memakai Terminal dengan `composer`, boleh juga `composer install --no-dev --optimize-autoloader` di server.
3. Di Terminal: `cd ~/dealer-mobil && php artisan down && php artisan migrate --force && php artisan optimize && php artisan up`.
4. Opsi B: salin ulang isi `dealer-mobil/public` ke `public_html` (kecuali `index.php` yang sudah diedit).
5. Ikuti langkah khusus di catatan rilis versi tersebut. Contoh v1.11.0: `php artisan user:make-owner email-admin@dealer.com` dan isi `DEALER_BANK_*` di `.env` (lihat [RELEASE-v1.11.0.md](RELEASE-v1.11.0.md)).

## 12. Cadangan data

Yang **wajib** dicadangkan (folder lain bisa dibuat ulang dari paket aplikasi):

| Data | Lokasi | Isi |
|---|---|---|
| Database | MySQL `namauser_dealer` | Akun, mobil, pengajuan, pembayaran, servis, notifikasi |
| Bukti pembayaran | `dealer-mobil/storage/app/private` | Bukti transfer & kwitansi customer (data pribadi) |
| Foto | `dealer-mobil/storage/app/public` | Foto mobil, banner promo, logo merek |
| Pengaturan | `dealer-mobil/.env` | Kunci aplikasi (`APP_KEY`), database, email, data dealer |

**Cadangan otomatis (sejak v1.20.0):** bila cron `schedule:run` (bagian 7) aktif, aplikasi membuat cadangan setiap hari pukul `DEALER_BACKUP_TIME` (bawaan 02:00) dengan `php artisan backup:run`:

- Satu file `cadangan-TANGGAL-JAM.zip` di `dealer-mobil/storage/app/backups` (ubah lewat `DEALER_BACKUP_PATH`), berisi `database.sql`, `storage/app/public` (foto), dan `storage/app/private` (bukti pembayaran). File `.env` **tidak** ikut karena berisi kata sandi: simpan salinannya sendiri.
- Cadangan yang lebih tua dari `DEALER_BACKUP_KEEP_DAYS` hari (bawaan 30) dihapus; cadangan terbaru selalu disimpan.
- Dashboard Owner & Super Admin menampilkan *Cadangan terakhir …* dan memberi peringatan bila belum ada cadangan atau sudah lebih dari 2 hari.
- Jalankan manual kapan saja (mis. sebelum memperbarui aplikasi): `php artisan backup:run`.
- Butuh ekstensi PHP **zip** (cPanel → *Select PHP Version* → centang `zip`). Ekspor database memakai PHP, jadi tidak membutuhkan `mysqldump`.
- Cadangan masih berada di server yang sama: **unduh** file zip terbaru secara berkala (mis. seminggu sekali) lewat File Manager ke komputer atau penyimpanan cloud milik dealer.

**Kapan (manual):** minimal **sekali seminggu** bila cron tidak tersedia, dan **selalu** sebelum memperbarui aplikasi (bagian 11). Banyak hosting juga membuat cadangan otomatis harian; cek di cPanel → **Backup** / **JetBackup**, tetapi tetap simpan salinan sendiri.

**Cara A, lewat cPanel (tanpa Terminal):**

1. cPanel → **Backup** → *Download a MySQL Database Backup* → klik nama database.
2. cPanel → **File Manager** → klik kanan folder `dealer-mobil/storage/app` → *Compress* (Zip) → unduh file zip-nya, lalu hapus zip dari server.
3. Unduh juga `dealer-mobil/.env`.

**Cara B, lewat Terminal:**

```bash
mkdir -p ~/backups
mysqldump -u namauser_dealer -p namauser_dealer > ~/backups/db-$(date +%F).sql
tar -czf ~/backups/storage-$(date +%F).tar.gz -C ~/dealer-mobil storage/app/public storage/app/private .env
find ~/backups -type f -mtime +30 -delete   # hapus cadangan yang lebih tua dari 30 hari
```

`mysqldump` menanyakan kata sandi database. Folder `~/backups` berada di luar `public_html` sehingga tidak bisa diunduh orang lain; tetap unduh salinannya ke komputer atau penyimpanan cloud milik dealer.

**Memulihkan:**

Dari cadangan otomatis: ekstrak zip, impor `database.sql` (langkah 1), lalu salin folder `storage/app/public` dan `storage/app/private` dari zip ke `dealer-mobil/storage/app` (langkah 2-3).

1. Database: phpMyAdmin → pilih database → *Import* file `.sql` (atau `mysql -u namauser_dealer -p namauser_dealer < db-TANGGAL.sql`).
2. File: ekstrak cadangan ke `dealer-mobil` sehingga `storage/app/public`, `storage/app/private`, dan `.env` kembali ke tempatnya (`tar -xzf storage-TANGGAL.tar.gz -C ~/dealer-mobil`).
3. `php artisan storage:link` (opsi B: buat ulang tautan `public_html/storage`, bagian 7) lalu `php artisan optimize`.

Cadangan berisi data pribadi customer dan bukti pembayaran: simpan di tempat yang aman dan jangan dibagikan (lihat Kebijakan Privasi).

## 13. Masalah umum

| Gejala | Penyebab & solusi |
|---|---|
| Error *"Your Composer dependencies require a PHP version >= 8.4.1"* | Versi PHP hosting terlalu lama → pilih PHP 8.4/8.5 (web **dan** CLI). |
| Halaman putih / *500 Server Error* | Lihat log terbaru di `dealer-mobil/storage/logs/` (`laravel-TANGGAL.log`; `laravel.log` bila `LOG_STACK=single`). Biasanya `.env` salah (DB), `APP_KEY` kosong, atau izin folder `storage`. |
| Perubahan `.env` tidak berlaku | Config ter-cache → `php artisan optimize`. |
| Foto tidak tampil | `storage:link` belum ada / salah folder (opsi B: tautan harus `public_html/storage` → `~/dealer-mobil/storage/app/public`, dibuat manual dengan `ln -s`). |
| Peta tidak tampil | `DEALER_MAPS_EMBED_URL` harus URL **embed** Google Maps (https). |
| **Cetak PDF** error *Class "Dompdf\Dompdf" not found* | Folder `vendor` lama → upload paket baru (bagian 2) atau `composer install --no-dev`. |
| **Export Excel** error *Class "PhpOffice\PhpSpreadsheet\..." not found* atau file gagal dibuat | Folder `vendor` lama → upload paket baru / `composer install --no-dev`; pastikan ekstensi PHP `zip` dan `xml` aktif. |
| Isian harga/kilometer tidak otomatis bertitik ribuan, huruf masih bisa diketik | File `public/js/number-input.js` belum terunggah → upload paket terbaru (server tetap menolak isian berhuruf). |
| Pengingat H-1 tidak terkirim | Cron belum dipasang / path PHP salah → bagian 7 *Cron untuk pengingat H-1*; sementara pakai tombol **Kirim Pengingat Besok** di dashboard. Hanya jadwal **Dikonfirmasi** untuk besok yang diingatkan. |
| Menu **Staf** tidak ada, semua akun staf mendapat 403 | Belum ada owner → `php artisan user:make-owner email-admin@dealer.com`. |
| Bukti pembayaran gagal diunggah / tidak bisa dibuka | Folder `storage/app/private` harus bisa ditulis (`chmod -R 775 storage`) dan ikut dicadangkan; batas upload sama dengan foto (≥ 8M). |
| Upload foto gagal / halaman error saat mengunggah | Batas `upload_max_filesize` / `post_max_size` hosting terlalu kecil → naikkan (lihat bagian 1). Foto lama: jalankan `php artisan cars:optimize-images` sekali. |
| **Cetak PDF** error terkait `DOMDocument` | Ekstensi PHP `dom` belum aktif → cPanel → *Select PHP Version* → *Extensions* → centang `dom`. |
| Email notifikasi tidak terkirim / aksi lambat | `MAIL_*` salah atau port SMTP diblokir hosting → cek log di `storage/logs/`, perbaiki lalu `php artisan optimize`, atau sementara `DEALER_MAIL_NOTIFICATIONS=false`. |
| *419 Sesi Berakhir* terus-menerus | `SESSION_SECURE_COOKIE=true` padahal belum HTTPS, atau `APP_URL` tidak sesuai domain. |
