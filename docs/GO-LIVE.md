# Daftar Periksa Go-Live JAF Dealer

Urutan kerja dari persiapan sampai minggu pertama website dipakai dealer. Centang setiap baris. Langkah teknis rinci ada di [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md); dokumen ini menunjuk bagian yang relevan.

Versi aplikasi: **v1.19.0** atau lebih baru.

## 1. Bahan dari dealer (kumpulkan dulu)

- [ ] Domain dan paket hosting cPanel yang memenuhi syarat ([DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 1).
- [ ] Akun email untuk website (mis. `noreply@domain`) beserta data SMTP-nya.
- [ ] Data dealer: nama, tagline, alamat showroom, telepon, nomor WhatsApp, email, jam operasional, link peta Google Maps (*Bagikan > Sematkan peta*).
- [ ] **Rekening pembayaran resmi**: nama bank, nomor rekening, atas nama. Cek ulang bersama owner, karena customer mentransfer ke rekening ini.
- [ ] Daftar mobil yang dijual (data, harga, stok) dan **foto asli** tiap mobil.
- [ ] Promo yang sedang berjalan (bila ada) dan bannernya.
- [ ] Daftar layanan servis, harga mulai, durasi, dan checklist pekerjaannya.
- [ ] Gambar atau video hero milik dealer sendiri (hindari materi berhak cipta pihak lain).
- [ ] Daftar staf: nama, email, nomor WhatsApp, dan role (Owner, Super Admin, Admin Dealer, Sales, Customer Service, Mekanik).
- [ ] **Kebijakan Privasi** dan **Syarat & Ketentuan** sudah dibaca dan disetujui dealer (teks saat ini masih umum).

## 2. Siapkan `.env` production

Ikuti [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 6. Nilai yang wajib berbeda dari lokal:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`
- [ ] `LOG_LEVEL=warning`, `LOG_STACK=daily`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Database production (`DB_*`) dari cPanel.
- [ ] SMTP (`MAIL_*`) asli, `MAIL_FROM_ADDRESS` memakai domain dealer, dan `DEALER_MAIL_NOTIFICATIONS=true`.
- [ ] `ADMIN_EMAIL` dan `ADMIN_PASSWORD` untuk akun owner pertama (kata sandi kuat, minimal 12 karakter).
- [ ] `DEALER_*` boleh diisi sebagai nilai bawaan. Data dealer sebaiknya diisi lewat **Admin > Pengaturan** (bagian 4), karena nilainya menggantikan `.env` dan bisa diubah tanpa akses server.
- [ ] `SEED_CUSTOMER_PASSWORD` dikosongkan (tidak dipakai di production).

## 3. Deploy

- [ ] Paket disiapkan di laptop dan diunggah ([DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 2-4). **Jangan** mengekspor database lokal: isinya customer dummy dan data uji.
- [ ] Database dibuat dan `.env` dipasang (bagian 5-6).
- [ ] Perintah awal di Terminal (bagian 7), dengan seed **tanpa mobil dan promo contoh**:

  ```bash
  cd ~/dealer-mobil
  php artisan key:generate --force
  php artisan migrate --force
  php artisan db:seed --class=AdminUserSeeder --force
  php artisan db:seed --class=BrandSeeder --force
  php artisan db:seed --class=CategorySeeder --force
  php artisan db:seed --class=ServiceSeeder --force
  php artisan user:make-owner email-owner@dealer.com
  php artisan storage:link
  php artisan optimize
  ```

  `ServiceSeeder` membuat 6 layanan contoh; sesuaikan atau hapus di *Layanan Servis* setelah login. Lewati bila ingin mengisi layanan sendiri dari awal.
- [ ] Cron `schedule:run` setiap menit dipasang (bagian 7, *Cron untuk pengingat H-1*); `php artisan schedule:list` menampilkan `reminders:send`.
- [ ] HTTPS aktif dan dipaksa (bagian 8).
- [ ] Setelah login pertama berhasil, hapus nilai `ADMIN_PASSWORD` dari `.env` server lalu `php artisan optimize`.

## 4. Isi data asli (login sebagai Owner)

- [ ] **Profil**: ganti kata sandi owner bila perlu, isi nomor WhatsApp.
- [ ] **Pengaturan**:
  - Identitas dan Kontak lengkap; peta tampil di halaman Kontak.
  - Teks *Siapa Kami* sesuai dealer.
  - Hero Beranda: judul, teks, dan latar (unggah gambar banner atau pilih foto mobil unggulan).
  - **Rekening pembayaran** diisi dan dicek ulang huruf per huruf.
- [ ] **Merek** dan **Kategori** sesuai (Nissan; SUV, MPV, Hatchback, Pickup, Sport, EV).
- [ ] **Layanan Servis**: harga, durasi, dan checklist pekerjaan tiap layanan.
- [ ] **Mobil**: semua unit dengan foto asli, harga, stok; tandai mobil unggulan.
- [ ] **Promo** yang berjalan (bila ada).
- [ ] **Staf**: buat akun tiap staf dengan role yang benar; berikan email dan kata sandi lewat jalur pribadi, minta diganti di menu Profil.

## 5. Uji akhir di server (bukan di lokal)

Lakukan sekali di laptop dan sekali di HP. Pakai email customer uji milik sendiri, lalu hapus datanya setelah selesai (lihat akhir bagian ini).

**Customer**
- [ ] Daftar akun baru, menerima email, dan bisa login. *Lupa kata sandi* mengirim email dan tautannya berfungsi.
- [ ] Booking test drive; ajukan pembelian; booking servis.
- [ ] Setelah pengajuan disetujui: rekening yang tampil adalah rekening asli; kirim bukti transfer.
- [ ] Cetak PDF pengajuan: data dan rekening dealer benar.

**Sales**
- [ ] Mengonfirmasi test drive tadi; customer menerima notifikasi (lonceng dan email).
- [ ] Mengubah status pengajuan ke Disetujui; tidak melihat tombol verifikasi pembayaran.

**Admin Dealer**
- [ ] Menerima bukti bayar; status pembayaran berubah dan customer diberi tahu.
- [ ] Tidak bisa membuka Staf dan Pengaturan (403).

**Customer Service**
- [ ] Mengonfirmasi booking servis dan menugaskan mekanik.
- [ ] Melihat test drive, mobil, dan promo tanpa tombol ubah.

**Mekanik**
- [ ] Melihat pekerjaan yang ditugaskan, mengisi checklist dan temuan, menyelesaikan servis.
- [ ] Customer bisa memberi rating mekanik setelah servis selesai.

**Super Admin**
- [ ] Membuat dan menonaktifkan akun staf (kecuali Owner dan Super Admin); membuka Pengaturan.
- [ ] Melihat pengajuan tanpa tombol ubah status atau verifikasi pembayaran.

**Owner**
- [ ] **Staf > Riwayat Aktivitas** memuat login staf, verifikasi pembayaran, perubahan status, dan perubahan Pengaturan dari pengujian di atas.
- [ ] Laporan dan ekspor Excel/CSV terbuka.
- [ ] `php artisan reminders:send --dry-run` di Terminal menampilkan jadwal besok (bila ada), atau tombol **Kirim Pengingat Besok** di dashboard berfungsi.

**Setelah uji**
- [ ] Batalkan atau tolak transaksi uji, lalu hapus akun customer uji dari database (phpMyAdmin) bila tidak ingin muncul di laporan. Catatan di Riwayat Aktivitas memang tetap ada.

## 6. Keamanan dan cadangan

- [ ] `https://domain-anda/.env` tidak bisa dibuka (403/404); halaman error tidak menampilkan detail teknis.
- [ ] Cadangan database otomatis harian aktif (cPanel *Backup* atau cron `mysqldump`, [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 12), termasuk folder `storage/app/public` dan `storage/app/private` (bukti pembayaran).
- [ ] Sekali **uji restore** cadangan ke database terpisah untuk memastikan cadangannya bisa dipakai.
- [ ] Hanya Owner yang memegang akses cPanel dan `.env`.

## 7. Hari pertama dan minggu pertama

- [ ] Daftarkan domain di **Google Search Console** dan kirim `https://domain-anda/sitemap.xml`.
- [ ] Bagikan tautan website ke kanal dealer (WhatsApp, media sosial, Google Business Profile).
- [ ] Cek harian: notifikasi admin, **Riwayat Aktivitas**, dan log `storage/logs` (cari `ERROR`).
- [ ] Pastikan email notifikasi sampai ke customer (tanyakan ke beberapa customer pertama; cek folder Spam).
- [ ] Catat masukan staf dan customer untuk pengembangan berikutnya (mis. persetujuan diskon oleh Owner, pengajuan per Sales, input pengajuan untuk customer yang datang langsung).
