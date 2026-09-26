<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Dealer Mobil

Website sistem informasi Dealer Mobil berbasis **Laravel 13**. Customer bisa melihat katalog, booking test drive, simulasi kredit, dan mengajukan pembelian. Admin bisa mengelola mobil, brand, kategori, promo, test drive, dan pengajuan.

---

## Daftar Isi

1. [Tech Stack](#tech-stack)
2. [Kebutuhan Software](#kebutuhan-software)
3. [Instalasi Laragon & PHP 8.5](#1-instalasi-laragon--php-85)
4. [Perbaikan Apache (Wajib)](#2-perbaikan-apache-wajib)
5. [Update Composer](#3-update-composer)
6. [Setting Git](#4-setting-git)
7. [Clone Project](#5-clone-project)
8. [Install Dependency](#6-install-dependency)
9. [Setting File .env](#7-setting-file-env)
10. [Membuat Database](#8-membuat-database)
11. [Menjalankan Migration](#9-menjalankan-migration)
12. [Menjalankan Project](#10-menjalankan-project)
13. [Alur Kerja Git (Kerja Tim)](#alur-kerja-git-kerja-tim)
14. [Troubleshooting](#troubleshooting)
15. [Progress Project](#progress-project)

---

## Tech Stack

| Komponen         | Versi                          |
| ---------------- | ------------------------------ |
| PHP              | 8.5.x                          |
| Laravel          | 13.x                           |
| MySQL            | 8.0.x                          |
| Composer         | 2.10.x atau lebih baru         |
| Web server lokal | Laragon (Apache)               |
| Frontend         | Blade + Bootstrap + JavaScript |
| Version control  | Git + GitHub                   |

---

## Kebutuhan Software

Install terlebih dahulu:

- **Laragon** (sudah termasuk Apache, MySQL, Composer, dan Terminal Cmder): https://laragon.org/download
- **Git for Windows**: https://git-scm.com/download/win
- **VS Code**: https://code.visualstudio.com
- **Visual C++ Redistributable 2015–2022 x64**: https://aka.ms/vs/17/release/vc_redist.x64.exe

---

## 1. Instalasi Laragon & PHP 8.5

Laravel 13 membutuhkan **PHP 8.3 atau lebih baru**. PHP bawaan Laragon biasanya masih versi lama, jadi harus ditambahkan PHP 8.5.

### 1.1 Download PHP

1. Buka https://windows.php.net/download
2. Cari bagian **PHP 8.5**, lalu pilih kotak **VS17 x64 Thread Safe**.
3. Klik **Zip**.

> **Penting:** pilih **Thread Safe**, bukan _Non Thread Safe_. Laragon menjalankan PHP sebagai modul Apache, dan itu membutuhkan versi Thread Safe.

### 1.2 Extract ke Laragon

1. Buat folder baru di:
    ```text
    C:\laragon\bin\php\php-8.5.11-Win32-vs17-x64
    ```
    Sesuaikan angka versinya dengan file yang di-download.
2. Extract **isi** zip ke dalam folder tersebut.

Pastikan `php.exe` berada **langsung** di dalam folder itu:

```text
C:\laragon\bin\php\
├── php-8.1.x-Win32-vs16-x64\     (versi lama, biarkan)
└── php-8.5.11-Win32-vs17-x64\
    ├── ext\
    ├── php.exe
    ├── php8apache2_4.dll
    └── ...
```

> Jangan extract langsung ke `C:\laragon\bin\php\` karena file-file PHP akan tercampur.

### 1.3 Aktifkan PHP 8.5 di Laragon

1. Klik kanan jendela Laragon → **PHP → Version** → pilih **php-8.5.11-Win32-vs17-x64**.
2. Klik kanan → **PHP → Extensions**, lalu pastikan ekstensi berikut tercentang:
    - `curl`
    - `fileinfo`
    - `gd`
    - `mbstring`
    - `openssl`
    - `pdo_mysql`
    - `zip`

---

## 2. Perbaikan Apache (Wajib)

Apache bawaan Laragon versi lama (misalnya `httpd-2.4.54`) punya file `nghttp2.dll` yang tidak cocok dengan PHP 8.5. Akibatnya muncul error saat Start:

```text
httpd.exe - Entry Point Not Found
nghttp2_option_set_no_rfc9113_leading_and_trailing_ws_validation ...
php_curl.dll
```

**Cara memperbaiki:**

1. Di Laragon, klik **Stop**.
2. Buka folder Apache, misalnya:
    ```text
    C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin
    ```
3. Rename `nghttp2.dll` menjadi `nghttp2.dll.bak` (sebagai backup).
4. Copy `nghttp2.dll` dari folder PHP:
    ```text
    C:\laragon\bin\php\php-8.5.11-Win32-vs17-x64\nghttp2.dll
    ```
5. Paste ke folder `bin` Apache pada langkah 2.
6. Klik **Start All**.

Pastikan di jendela Laragon muncul **Apache ... started** dan **MySQL ... started** tanpa pop-up error.

---

## 3. Update Composer

Tutup semua Terminal Laragon, lalu buka Terminal baru (tombol **Terminal** di Laragon).

```bash
php -v
composer self-update
composer -V
```

Hasil yang benar:

```text
PHP 8.5.x ...
Composer version 2.10.x ... PHP version 8.5.x
```

> Kalau muncul banyak pesan `Deprecation Notice` saat menjalankan `composer`, berarti Composer masih versi lama. Jalankan `composer self-update`.

---

## 4. Setting Git

Cukup dilakukan sekali di setiap laptop:

```bash
git config --global user.name "Nama Lengkap"
git config --global user.email "email-github@contoh.com"
git config --global init.defaultBranch main
```

> Gunakan email yang **sama dengan akun GitHub**, supaya commit tercatat atas nama kamu.

Saat pertama kali push, akan muncul jendela **CredentialHelperSelector**:

1. Pilih **manager**.
2. Centang **Always use this from now on**.
3. Klik **Select**.
4. Pilih **Sign in with your browser**, lalu login ke GitHub dan klik **Authorize**.

---

## 5. Clone Project

Pastikan sudah menerima dan menerima undangan collaborator di GitHub.

```bash
cd C:\laragon\www
git clone https://github.com/yovanhayon-ctrl/dealer-mobil.git
cd dealer-mobil
```

> Nama folder harus tetap `dealer-mobil`, supaya Laragon otomatis membuat alamat `http://dealer-mobil.test`.

---

## 6. Install Dependency

```bash
composer install
```

Perintah ini membuat folder `vendor/`. Folder ini **tidak** disimpan di GitHub, jadi setiap orang harus menjalankan `composer install` sendiri.

---

## 7. Setting File .env

File `.env` berisi konfigurasi lokal dan **tidak** disimpan di GitHub. Buat dari file contoh:

```bash
copy .env.example .env
php artisan key:generate
```

Buka `.env` di VS Code, lalu sesuaikan bagian berikut.

**Bagian aplikasi (paling atas):**

```env
APP_NAME="Dealer Mobil"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://dealer-mobil.test

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID
```

**Bagian database.** Bawaan Laravel 13 adalah SQLite, jadi ubah ke MySQL. Hapus tanda `#` di depan setiap baris:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dealer_mobil
DB_USERNAME=root
DB_PASSWORD=
```

**Bagian akun awal & data dummy (paling bawah):**

```env
ADMIN_EMAIL=admin@dealer.test
ADMIN_PASSWORD=isi-kata-sandi-admin

SEED_CUSTOMER_PASSWORD=isi-kata-sandi-customer-dummy
```

- `ADMIN_EMAIL` & `ADMIN_PASSWORD` wajib diisi sebelum `php artisan db:seed` (akun admin pertama).
- `SEED_CUSTOMER_PASSWORD` opsional, hanya untuk laptop masing-masing (local). Dipakai sebagai kata sandi 8 customer dummy `@example.test`. Jika kosong, customer dummy (dan test drive/pengajuan dummy) tidak dibuat. Jangan pernah diisi di server production (di production seeder ini selalu dilewati).

> - Jangan ada spasi setelah tanda `=`.
> - Jangan ada nama variabel yang dobel (misalnya dua baris `APP_URL`).
> - Jangan mengubah `APP_KEY` secara manual.

Simpan (**Ctrl+S**), lalu bersihkan cache konfigurasi:

```bash
php artisan config:clear
```

---

## 8. Membuat Database

Pastikan MySQL di Laragon sudah menyala, lalu jalankan:

```bash
mysql -u root -e "CREATE DATABASE dealer_mobil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Cara alternatif: Laragon → **Database** (HeidiSQL) → klik kanan → **Create new → Database** → beri nama `dealer_mobil`, dengan collation `utf8mb4_unicode_ci`.

---

## 9. Menjalankan Migration

```bash
php artisan migrate
php artisan migrate:status
```

Semua migration harus berstatus **Ran**.

> **Peringatan:** jangan menjalankan `php artisan migrate:fresh` tanpa berdiskusi dulu. Perintah itu **menghapus semua tabel dan data** di database lokal.

Setelah migration, jalankan juga:

```bash
php artisan storage:link
php artisan db:seed
```

- `storage:link` membuat folder `public/storage` yang terhubung ke `storage/app/public`, supaya logo merek dan foto mobil yang di-upload bisa tampil di browser. Cukup sekali di setiap laptop. Foto mobil disimpan di `storage/app/public/cars/{id_mobil}/`.
- Upload galeri mobil bisa sampai 10 file × 2 MB sekaligus. Jika muncul error *Content Too Large* / *POST Content-Length exceeds the limit*, naikkan `post_max_size` (misalnya `25M`) dan `upload_max_filesize` (minimal `2M`) di `php.ini`, lalu restart Apache.
- `db:seed` membuat akun admin (dari `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env`, wajib diisi dulu) serta data merek (Toyota, Honda, Daihatsu, Mitsubishi, Suzuki, Hyundai, Wuling), kategori (SUV, MPV, Sedan, Hatchback, Pickup, LCGC), 15 mobil contoh, dan 5 promo contoh (tanggal relatif terhadap hari seeder dijalankan). Jika `SEED_CUSTOMER_PASSWORD` diisi dan environment `local`, juga dibuat 8 customer dummy (misalnya `budi.santoso@example.test`) beserta 10 test drive dan 9 pengajuan dummy dengan berbagai status; stok mobil ikut disesuaikan untuk pengajuan yang disetujui/selesai. Aman dijalankan ulang: data yang sudah ada tidak digandakan dan tidak ditimpa.

---

## 10. Menjalankan Project

**Opsi A — lewat Laragon (disarankan)**

1. Laragon → **Menu → Apache → Reload**.
2. Buka **http://dealer-mobil.test** di browser.

**Opsi B — server bawaan Laravel**

```bash
php artisan serve
```

Lalu buka **http://127.0.0.1:8000**.

Kalau halaman welcome Laravel 13 muncul dan judul tab browser **"Dealer Mobil"**, berarti instalasi berhasil.

> Label **"Tidak aman"** di browser itu normal untuk localhost, karena belum memakai HTTPS.

---

## Alur Kerja Git (Kerja Tim)

### Sebelum mulai coding

Selalu ambil perubahan terbaru dari teman:

```bash
git checkout main
git pull
```

Kalau ada migration baru dari teman, jalankan:

```bash
composer install
php artisan migrate
```

### Saat mengerjakan fitur

Buat branch baru untuk setiap fitur:

```bash
git checkout -b feature/nama-fitur
```

Contoh nama branch:

- `feature/crud-brand`
- `feature/katalog-mobil`
- `fix/validasi-test-drive`

Simpan perubahan secara berkala:

```bash
git add .
git commit -m "feat: add brand crud"
git push -u origin feature/nama-fitur
```

Setelah itu buka GitHub, lalu buat **Pull Request** ke branch `main`. Minta teman mengecek sebelum di-merge.

### Format pesan commit

| Awalan      | Dipakai untuk                        |
| ----------- | ------------------------------------ |
| `feat:`     | Fitur baru                           |
| `fix:`      | Perbaikan bug                        |
| `style:`    | Perubahan tampilan/CSS               |
| `refactor:` | Merapikan kode tanpa mengubah fungsi |
| `docs:`     | Dokumentasi (README, dan sebagainya) |
| `chore:`    | Setup, konfigurasi, dependency       |

### Aturan penting

- **Jangan** `git push --force` ke branch `main`.
- **Jangan** commit file atau folder berikut (sudah diatur di `.gitignore`):
    - `.env`
    - `vendor/`
    - `node_modules/`
    - `.claude/`
- **Jangan** mengedit file migration yang sudah di-push dan dijalankan teman. Kalau perlu mengubah struktur tabel, buat migration baru.
- Sepakati pembagian fitur supaya tidak mengedit file yang sama bersamaan.

---

## Troubleshooting

| Masalah                                                          | Penyebab                                                          | Solusi                                                                |
| ---------------------------------------------------------------- | ----------------------------------------------------------------- | --------------------------------------------------------------------- |
| `httpd.exe - Entry Point Not Found ... nghttp2 ... php_curl.dll` | `nghttp2.dll` Apache terlalu lama                                 | Lihat [langkah 2](#2-perbaikan-apache-wajib).                         |
| `could not find driver (Connection: sqlite ...)`                 | `.env` masih memakai SQLite                                       | Ubah `DB_CONNECTION=mysql`, lalu jalankan `php artisan config:clear`. |
| `SQLSTATE[HY000] [1049] Unknown database 'dealer_mobil'`         | Database belum dibuat                                             | Lihat [langkah 8](#8-membuat-database).                               |
| `SQLSTATE[HY000] [2002] No connection could be made`             | MySQL belum menyala                                               | Laragon → **Start All**.                                              |
| `php artisan --version` masih menampilkan versi lama             | Terminal belum di-restart atau versi PHP di Laragon belum diganti | Tutup semua terminal, cek **PHP → Version**, lalu buka terminal baru. |
| Banyak `Deprecation Notice` saat menjalankan `composer`          | Composer versi lama                                               | `composer self-update`                                                |
| `curl error 28 ... Connection timed out`                         | Koneksi ke Packagist lambat                                       | Cek internet, lalu ulangi perintah.                                   |
| `error: src refspec main does not match any`                     | Belum ada commit, atau branch masih `master`                      | `git branch -m main`, lalu `git add .`, `git commit`, dan `git push`. |
| `http://dealer-mobil.test` tidak terbuka                         | Virtual host belum dibuat                                         | Laragon → **Menu → Apache → Reload**, atau pakai `php artisan serve`. |
| Error `No application encryption key has been specified`         | `APP_KEY` kosong                                                  | `php artisan key:generate`                                            |

---

## Progress Project

| Phase | Tahap                            | Status          |
| ----- | -------------------------------- | --------------- |
| 1     | Project setup                    | ✅ Selesai      |
| 2     | Database & ERD                   | ✅ Selesai      |
| 3     | Authentication & role            | ✅ Selesai      |
| 4     | Layout admin & dashboard         | ✅ Selesai      |
| 5     | CRUD merek & kategori            | ✅ Selesai      |
| 6     | CRUD mobil                       | ✅ Selesai      |
| 7     | Upload & galeri mobil            | ✅ Selesai      |
| 8     | Katalog publik        | ⏳                   |
| 9     | Search & filter       | ⏳                   |
| 10    | Detail mobil          | ⏳                   |
| 11    | Test drive            | 🟡 Admin selesai (publik menyusul) |
| 12    | Pengajuan pembelian   | 🟡 Admin selesai (publik menyusul) |
| 13    | Simulasi kredit       | ⏳                   |
| 14    | Promo                 | ✅ Admin (publik menyusul) |
| 15    | Dashboard & laporan   | ✅ Admin selesai (dashboard, daftar pengguna, laporan) |
| 16    | Security              | ⏳                   |
| 17    | Testing               | ⏳                   |
| 18    | Optimization          | ⏳                   |
| 19    | Deployment            | ⏳                   |

> **Urutan kerja:** semua halaman **admin** dikerjakan dulu (sudah selesai), lalu halaman **publik** (katalog, detail, test drive, pengajuan, simulasi kredit, dan lainnya). Jadi nomor phase di tabel tidak dikerjakan berurutan.

### Keputusan desain

- Customer **wajib login** untuk booking test drive dan mengajukan pembelian.
- Dealer menjual mobil **baru dan bekas**.
- Simulasi kredit hanya berupa perhitungan (tanpa tabel). Hasilnya disimpan ke pengajuan pembelian.
- Kontak dealer melalui WhatsApp dan halaman kontak.

### Rancangan tabel

| Tabel               | Fungsi                                            |
| ------------------- | ------------------------------------------------- |
| `users`             | Admin & customer (kolom `role`, `phone`)          |
| `brands`            | Merek mobil                                       |
| `categories`        | Jenis mobil (SUV, MPV, Sedan, dan lain-lain)      |
| `cars`              | Data mobil (baru/bekas, harga, spesifikasi, stok) |
| `car_images`        | Galeri foto mobil                                 |
| `promos`            | Promo umum atau per mobil                         |
| `test_drives`       | Booking test drive                                |
| `purchase_requests` | Pengajuan pembelian cash/kredit                   |

## Alur Kerja Git (Kerja Tim)

Repository ini memakai **2 branch**:

| Branch    | Fungsi                                      | Siapa yang boleh push          |
| --------- | ------------------------------------------- | ------------------------------ |
| `main`    | Versi **stabil** yang sudah dicek dan dites | **Hanya admin (pemilik repo)** |
| `testing` | Tempat **semua anggota tim bekerja**        | Semua anggota tim              |

```text
anggota tim ──push──▶ testing ──(dicek & dites admin)──▶ main
```

> ⚠️ Branch `main` dikunci dengan GitHub Ruleset. Push ke `main` oleh selain admin akan **ditolak otomatis**.

### 1. Pertama kali (setelah menerima undangan collaborator)

```bat
cd C:\laragon\www
git clone https://github.com/yovanhayon-ctrl/dealer-mobil.git
cd dealer-mobil
git checkout testing
```

Lanjutkan langkah instalasi di bagian atas README (composer install, `.env`, database, migrate).

> Default branch repo ini adalah `main`. **Setelah clone, wajib pindah ke `testing`.**

### 2. Setiap kali mulai coding

```bat
git checkout testing
git pull origin testing
composer install
php artisan migrate
```

- `git pull` mengambil perubahan terbaru dari anggota tim lain.
- `composer install` dan `php artisan migrate` diperlukan kalau ada package atau migration baru.

### 3. Setelah selesai coding

```bat
git status
git add .
git commit -m "feat: deskripsi singkat perubahan"
git push origin testing
```

Sebelum commit, pastikan:

- prompt terminal menunjukkan **`(testing)`**, bukan `(main)`,
- `.env`, `vendor/`, `node_modules/`, dan `.claude/` **tidak** muncul di `git status`,
- `php artisan test` lulus.

### 4. Kalau push ditolak karena ada perubahan baru dari teman

Pesan error: `rejected ... (fetch first)` atau `non-fast-forward`.

```bat
git pull origin testing
```

- Kalau tidak ada konflik, lanjutkan dengan `git push origin testing`.
- Kalau ada **konflik**: buka file yang ditandai di VS Code, pilih kode yang benar, simpan, lalu:

```bat
git add .
git commit -m "fix: resolve merge conflict"
git push origin testing
```

Kalau ragu cara menyelesaikan konflik, **tanyakan dulu ke admin** sebelum commit.

### 5. Kalau terlanjur commit di `main`

Push ke `main` akan ditolak. Pindahkan commit ke `testing`:

```bat
git checkout testing
git merge main
git push origin testing
git checkout main
git reset --hard origin/main
git checkout testing
```

> ⚠️ `git reset --hard origin/main` mengembalikan `main` lokal agar sama dengan GitHub. Jalankan **hanya setelah** commit sudah berhasil di-push ke `testing`.

### 6. Khusus admin: memindahkan `testing` ke `main`

Setelah perubahan di `testing` dicek:

```bat
git checkout testing
git pull origin testing
composer install
php artisan migrate
php artisan test
```

Cek juga secara manual di browser. Kalau semua lancar:

```bat
git checkout main
git pull origin main
git merge testing
git push origin main
git checkout testing
```

### Format pesan commit

| Awalan      | Dipakai untuk                        |
| ----------- | ------------------------------------ |
| `feat:`     | Fitur baru                           |
| `fix:`      | Perbaikan bug                        |
| `style:`    | Perubahan tampilan/CSS               |
| `refactor:` | Merapikan kode tanpa mengubah fungsi |
| `docs:`     | Dokumentasi                          |
| `test:`     | Menambah atau mengubah test          |
| `chore:`    | Setup, konfigurasi, dependency       |

### Aturan penting

- **Jangan** push ke `main`. Semua pekerjaan masuk ke `testing`.
- **Jangan** menjalankan `git push --force`.
- **Jangan** mengedit migration yang sudah di-push. Buat migration baru kalau perlu mengubah struktur tabel.
- **Jangan** menjalankan `php artisan migrate:fresh` di database orang lain, dan tanyakan dulu sebelum menjalankannya di laptop sendiri.
- Selalu `git pull` **sebelum** mulai coding.
- Sepakati pembagian fitur supaya tidak mengedit file yang sama bersamaan.
- Kalau memakai Claude Code, tambahkan instruksi: _"Kita bekerja di branch testing. Jangan pindah branch, jangan merge, dan jangan push ke main."_
