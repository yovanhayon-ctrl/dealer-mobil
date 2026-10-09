# JAF Dealer v1.2.0

Pembaruan setelah [v1.1.0](RELEASE-v1.1.0.md): notifikasi untuk admin dan notifikasi lewat email.

## Fitur baru

**Notifikasi admin**
- Semua admin mendapat notifikasi saat customer membuat **booking test drive, pengajuan pembelian, atau booking servis baru**, dan saat customer **membatalkan** sendiri salah satunya (contoh: "Test Drive Baru · Budi Santoso").
- Lonceng di topbar admin dan menu **Notifikasi** di sidebar (badge jumlah belum dibaca), halaman `/admin/notifikasi`, tandai semua dibaca. Klik notifikasi membuka halaman detail di admin.
- Tidak dikirim bila booking gagal validasi, pembatalan diklik dua kali, atau saat admin sendiri mengubah status (yang itu tetap menjadi notifikasi customer).

**Notifikasi lewat email**
- Notifikasi customer (perubahan status) dan admin (booking baru / dibatalkan) juga dikirim lewat email: sapaan, judul, rincian, catatan dealer, dan tombol yang membuka notifikasinya (sekaligus menandai terbaca).
- Notifikasi selalu disimpan ke lonceng lebih dulu; bila pengiriman email gagal (SMTP mati / salah setting), proses tetap berhasil dan kesalahannya hanya dicatat di `storage/logs/laravel.log`.
- Dikirim langsung (tanpa queue), jadi tidak perlu menjalankan `php artisan queue:work`.

## Pengaturan email (`.env`)

| Pengaturan | Hasil |
|---|---|
| `MAIL_MAILER=log` (bawaan) | Email tidak terkirim; isinya tercatat di `storage/logs/laravel.log` |
| Mailtrap (lihat di bawah) | Email masuk ke inbox Mailtrap (aman untuk demo, tidak ke penerima asli) |
| `DEALER_MAIL_NOTIFICATIONS=false` | Email dimatikan, hanya lonceng |

Mailtrap (gratis): buat akun di mailtrap.io → **Email Testing → Inboxes → SMTP Settings**, lalu isi `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=(username dari Mailtrap)
MAIL_PASSWORD=(password dari Mailtrap)
MAIL_FROM_ADDRESS="noreply@jafdealer.test"
DEALER_MAIL_NOTIFICATIONS=true
```

Lalu `php artisan optimize:clear`. Jangan commit `.env`.

## Pembaruan dari v1.1.0

```bash
git pull
composer install
php artisan optimize:clear
```

Tidak ada migration baru (memakai tabel `notifications` dari v1.1.0) dan tidak perlu seeder baru.

## Kualitas

- 731 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 20 test dari v1.1.0), termasuk simulasi SMTP gagal dengan dua admin.
- Diuji di browser: lonceng & halaman notifikasi admin; isi email dirender (bahasa Indonesia) dari data lokal.
