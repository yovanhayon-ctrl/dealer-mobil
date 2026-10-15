# JAF Dealer v1.12.1

Pembaruan persiapan online setelah [v1.12.0](RELEASE-v1.12.0.md): halaman Kebijakan Privasi dan Syarat & Ketentuan, serta panduan email, cadangan data, dan log untuk hosting.

## Kebijakan Privasi & Syarat Ketentuan

- Halaman baru **`/kebijakan-privasi`** (8 bagian): data yang dikumpulkan per fitur (akun, test drive, pengajuan, bukti pembayaran, servis, ulasan, data teknis), untuk apa dipakai, siapa yang bisa melihat, layanan pihak ketiga, lama penyimpanan, hak pengguna, keamanan, dan perubahan kebijakan. Mengacu pada UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.
- Halaman baru **`/syarat-ketentuan`** (9 bagian): akun, katalog dan harga, simulasi kredit, pengajuan pembelian, pembayaran (hanya ke rekening resmi dealer), test drive dan servis, ulasan, batasan tanggung jawab, dan hukum yang berlaku.
- Isi mengikuti perilaku aplikasi, mis. pembatalan oleh customer hanya untuk status *Menunggu* dan lama sesi login dibaca dari konfigurasi.
- Setiap halaman punya daftar isi, tanggal berlaku, dan kontak dealer dari `.env` (`DEALER_EMAIL`, `DEALER_WHATSAPP`, `DEALER_ADDRESS`; yang kosong tidak ditampilkan).
- Tautan di **footer** semua halaman publik, dan kalimat persetujuan di atas tombol **Daftar** (tautan terbuka di tab baru agar isian form tidak hilang). Kedua halaman masuk `sitemap.xml`.

> Isi kedua halaman adalah templat yang disesuaikan dengan fitur situs, **bukan nasihat hukum**. Minta pemilik dealer (atau penasihat hukumnya) membacanya sebelum situs online. Teks ada di `resources/views/pages/legal/`; bila diubah, perbarui `PageController::LEGAL_EFFECTIVE_DATE`.

## Panduan hosting ([DEPLOY-CPANEL.md](DEPLOY-CPANEL.md))

- **Email (SMTP dari cPanel):** membuat akun `noreply@domain`, pengaturan port 465/587 dan `MAIL_SCHEME`, SPF/DKIM di *Email Deliverability* agar tidak masuk Spam, dan perintah uji kirim email satu baris. Ditegaskan bahwa *Lupa kata sandi* tetap butuh SMTP walaupun `DEALER_MAIL_NOTIFICATIONS=false`.
- **Cadangan data (bagian 12, baru):** apa yang wajib dicadangkan (database, `storage/app/private`, `storage/app/public`, `.env`), jadwal (minimal mingguan dan selalu sebelum update), cara lewat cPanel atau Terminal, dan cara memulihkan. Langkah update aplikasi kini diawali pencadangan. *Masalah umum* menjadi bagian 13.
- **Log harian:** `LOG_STACK=daily` dan `LOG_DAILY_DAYS=14` di `.env` production, agar log tidak memenuhi kuota hosting.

## Pembaruan dari v1.12.0

```bash
git pull
php artisan optimize:clear
```

- Tidak ada migration atau dependency baru.
- Hosting yang sudah berjalan: tambahkan `LOG_STACK=daily` dan `LOG_DAILY_DAYS=14` ke `.env`, lalu `php artisan optimize`. Log baru ditulis ke `storage/logs/laravel-TANGGAL.log`.
- Pastikan `DEALER_EMAIL`, `DEALER_WHATSAPP`, dan `DEALER_ADDRESS` terisi agar bagian kontak di kedua halaman lengkap.

## Kualitas

- 973 test otomatis (PHPUnit) lulus di SQLite dan MySQL (7 test baru: kedua halaman terbuka untuk tamu dengan kontak dari konfigurasi, kontak kosong disembunyikan, daftar isi menunjuk ke setiap bagian, tautan di footer dan form daftar, masuk sitemap).
- Tampilan diperiksa di desktop mode terang dan ponsel mode gelap; perintah uji kirim email dan log harian dicoba di lokal.
