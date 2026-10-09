# JAF Dealer v1.3.0

Pembaruan setelah [v1.2.0](RELEASE-v1.2.0.md): ulasan (testimoni) customer dengan moderasi admin.

## Fitur baru

**Ulasan customer**
- Customer yang **pembeliannya berstatus Selesai** bisa memberi ulasan dari **Pengajuan Saya** (tombol **Beri Ulasan**): rating 1–5 bintang dan komentar 20–1000 karakter. Satu ulasan per pembelian.
- Ulasan menunggu moderasi; selama menunggu atau setelah ditolak customer bisa mengubahnya (kembali menunggu). Ulasan yang sudah disetujui terkunci.
- Semua admin mendapat notifikasi (lonceng + email) "Ulasan Baru / Diperbarui · nama customer".

**Moderasi admin**
- Menu **Ulasan** (`/admin/ulasan`): filter Semua / Menunggu / Disetujui / Ditolak beserta jumlahnya, tombol **Setujui** dan **Tolak** (alasan wajib, dikirim ke customer). Ulasan yang sudah tampil bisa disembunyikan dengan menolaknya.
- Customer mendapat notifikasi "Ulasan Disetujui" atau "Ulasan Ditolak" beserta alasannya.

**Beranda**
- Bagian **Kata Pelanggan**: 3 ulasan terbaru yang disetujui (bintang, kutipan, mobil yang dibeli) dan ringkasan rating, misalnya "4,7 dari 5 · 3 ulasan". Disembunyikan bila belum ada ulasan yang disetujui.
- Privasi: nama disingkat ("Budi Santoso" → "Budi S."); email dan nomor HP tidak pernah ditampilkan.

## Data contoh

- `PurchaseRequestSeeder` kini membuat 11 pengajuan (bertambah 2 pembelian **Selesai**: Budi – Livina, Dewi – Magnite).
- `TestimonialSeeder` (baru, dipanggil `DatabaseSeeder`) membuat 3 ulasan contoh yang sudah disetujui untuk pembelian selesai Nur, Budi, dan Dewi. Hanya local/testing, aman dijalankan ulang.

## Pembaruan dari v1.2.0

```bash
git pull
composer install
php artisan migrate   # tabel baru: testimonials
php artisan optimize:clear
```

Opsional, agar beranda langsung menampilkan contoh ulasan di database lokal yang sudah berisi data (menambah 2 pengajuan selesai dan mengurangi stok Livina & Magnite masing-masing 1):

```bash
php artisan db:seed --class=PurchaseRequestSeeder
php artisan db:seed --class=TestimonialSeeder
```

## Kualitas

- 749 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 18 test dari v1.2.0): kepemilikan, status pembelian, validasi, aturan ubah, moderasi, notifikasi, tampilan beranda (hanya yang disetujui, nama disingkat, escape HTML), dan tanpa N+1.
- Diuji di browser: halaman admin Ulasan (menu, filter, tampilan kosong).
