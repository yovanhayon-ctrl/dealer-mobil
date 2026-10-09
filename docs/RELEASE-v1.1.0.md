# JAF Dealer v1.1.0

Pembaruan setelah [v1.0.0](RELEASE-v1.0.0.md): empat fitur tambahan untuk customer dan admin.

## Fitur baru

**Notifikasi status (customer)**
- Customer mendapat notifikasi saat admin mengubah status test drive, pengajuan pembelian, atau booking servis (hanya bila status benar-benar berubah; catatan saja atau pembatalan oleh customer sendiri tidak dikirim).
- Lonceng di navbar dengan jumlah belum dibaca, halaman `/akun/notifikasi`, tandai semua dibaca; klik notifikasi membuka riwayat terkait dan menyorot kartunya.

**Mobil favorit (customer)**
- Tombol hati di kartu mobil dan halaman detail; halaman `/akun/favorit` (maks. 50 mobil).
- Tamu diarahkan ke login; admin tidak memakai fitur ini; mobil nonaktif tidak bisa ditambahkan dan tampil "Tidak tersedia" di daftar favorit.

**Bandingkan mobil (publik)**
- Pilih 2–3 mobil dengan tombol Bandingkan (disimpan di session, tamu juga bisa), bar di bawah layar, halaman `/bandingkan` berisi tabel spesifikasi berdampingan dengan badge "Terbaik" (harga termurah, tahun termuda, km terendah).

**Grafik dashboard admin**
- Bagian "Tren 6 Bulan Terakhir": aktivitas customer per bulan (pengajuan, test drive, booking servis) dan penjualan per bulan (unit terjual & nilai), dengan tabel "Lihat angka" sebagai cadangan.
- Chart.js 4.4.4 dari jsDelivr dengan SRI, hanya di dashboard; CSP tidak berubah, tanpa script inline.

## Pembaruan dari v1.0.0

```bash
git pull
composer install
php artisan migrate   # tabel baru: notifications, favorites
php artisan optimize:clear
```

Tidak ada perubahan pada migration lama dan tidak perlu seeder baru.

## Kualitas

- 711 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 46 test dari v1.0.0).
- Diuji di browser: tombol favorit & bandingkan (tamu), halaman bandingkan (desktop & HP), grafik dashboard admin (desktop & HP, tanpa error console).
