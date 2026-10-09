# Deploy ke Shared Hosting (cPanel) — JAF Dealer

Panduan memasang aplikasi di shared hosting berbasis cPanel (mis. Niagahoster, Hostinger, Rumahweb, IDCloudHost). Tidak perlu queue worker maupun cron (aplikasi tidak memakai queue/scheduler).

## 1. Syarat hosting (cek sebelum membeli/memakai)

| Kebutuhan | Keterangan |
|---|---|
| **PHP 8.4 atau lebih baru (disarankan 8.5)** | **Wajib.** Paket yang terkunci di `composer.lock` (Symfony 8) membutuhkan PHP ≥ 8.4.1. Di PHP 8.3 aplikasi **tidak** akan berjalan. Atur di cPanel → *Select PHP Version* / *MultiPHP Manager*. |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` (validasi upload), `ctype`, `tokenizer`, `xml`, `curl`, `dom` (PDF bukti pengajuan, dompdf). |
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

Kompres ulang isi folder tersebut menjadi `dealer-mobil.zip`. Paket **tidak** boleh berisi `.env` (berisi rahasia) — `git archive` sudah tidak menyertakannya.

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

DEALER_NAME="JAF Dealer"
DEALER_TAGLINE="Dream the Legacy. Drive the Future."
DEALER_ADDRESS="…"
DEALER_PHONE="…"
DEALER_WHATSAPP=62…
DEALER_EMAIL=…
DEALER_HOURS="Senin–Sabtu 08.00–17.00 WIB"
DEALER_MAPS_EMBED_URL="https://www.google.com/maps/embed?pb=…"

ADMIN_EMAIL=admin@domain-anda.com
ADMIN_PASSWORD=kata-sandi-admin-yang-kuat
```

- `MAIL_*`: buat akun email di cPanel → **Email Accounts** (dipakai fitur *Lupa kata sandi*).
- `SESSION_SECURE_COOKIE=true` hanya bila situs sudah memakai **HTTPS**.
- `SEED_CUSTOMER_PASSWORD` dibiarkan kosong: customer dummy tidak dibuat di production.
- Peta Google: Google Maps → *Bagikan* → *Sematkan peta* → salin URL di dalam `src="…"`.

## 7. Perintah di Terminal cPanel

cPanel → **Terminal** (pastikan versi PHP CLI ≥ 8.4: `php -v`; beberapa hosting memakai `ea-php85` atau `/opt/cpanel/ea-php85/root/usr/bin/php`):

```bash
cd ~/dealer-mobil
php -v
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

- `db:seed --force` di production membuat: akun admin (dari `ADMIN_EMAIL`/`ADMIN_PASSWORD`), merek, kategori, 21 mobil contoh, 5 promo, dan 6 layanan servis. Data dummy customer/transaksi **tidak** dibuat (hanya local/testing). Lewati perintah ini bila ingin mengisi data sendiri lewat admin (akun admin tetap dibutuhkan: jalankan `php artisan db:seed --class=AdminUserSeeder --force`).
- Opsi B (`public_html`): perintah `artisan` di terminal **tidak** membaca `public_html/index.php`, jadi `storage:link` membuat tautan di `dealer-mobil/public/storage` (tidak dipakai). Buat tautan untuk `public_html` secara manual:
  `ln -s ~/dealer-mobil/storage/app/public ~/public_html/storage`
- Izin folder (bila muncul error "Permission denied"): `chmod -R 775 storage bootstrap/cache`.

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
- [ ] *Lupa kata sandi* mengirim email.
- [ ] Gembok HTTPS aktif; `https://domain-anda.com/.env` **tidak** bisa dibuka (harus 403/404).

## 11. Memperbarui aplikasi (rilis berikutnya)

1. Siapkan paket baru seperti bagian 2 dan upload/ekstrak menimpa folder `dealer-mobil` (**jangan** menimpa `.env` dan `storage/app/public`).
2. Di Terminal: `cd ~/dealer-mobil && php artisan down && php artisan migrate --force && php artisan optimize && php artisan up`.
3. Opsi B: salin ulang isi `dealer-mobil/public` ke `public_html` (kecuali `index.php` yang sudah diedit).

## 12. Masalah umum

| Gejala | Penyebab & solusi |
|---|---|
| Error *"Your Composer dependencies require a PHP version >= 8.4.1"* | Versi PHP hosting terlalu lama → pilih PHP 8.4/8.5 (web **dan** CLI). |
| Halaman putih / *500 Server Error* | Lihat `dealer-mobil/storage/logs/laravel.log`. Biasanya `.env` salah (DB), `APP_KEY` kosong, atau izin folder `storage`. |
| Perubahan `.env` tidak berlaku | Config ter-cache → `php artisan optimize`. |
| Foto tidak tampil | `storage:link` belum ada / salah folder (opsi B: tautan harus `public_html/storage` → `~/dealer-mobil/storage/app/public`, dibuat manual dengan `ln -s`). |
| Peta tidak tampil | `DEALER_MAPS_EMBED_URL` harus URL **embed** Google Maps (https). |
| *419 Sesi Berakhir* terus-menerus | `SESSION_SECURE_COOKIE=true` padahal belum HTTPS, atau `APP_URL` tidak sesuai domain. |
