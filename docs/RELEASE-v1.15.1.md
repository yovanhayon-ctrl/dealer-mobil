# JAF Dealer v1.15.1

Pembaruan dokumen setelah [v1.15.0](RELEASE-v1.15.0.md): Kebijakan Privasi disesuaikan dengan enam peran staf, dan panduan demo mencakup fitur v1.11 sampai v1.15.

## Kebijakan Privasi

- Bagian **3. Siapa yang bisa melihat data** kini merinci akses per peran (sejak v1.14.0):
  - **Pemilik, super admin, dan admin dealer**: seluruh data untuk memproses permintaan, pembayaran, dan laporan.
  - **Sales**: data test drive dan pengajuan pembelian; tidak membuka file bukti pembayaran.
  - **Customer service**: data kontak, test drive, booking servis, pengajuan, dan ulasan; tidak membuka file bukti pembayaran.
  - **Mekanik**: data kendaraan dan servis yang ditugaskan kepadanya.
- Tanggal berlaku Kebijakan Privasi dan Syarat & Ketentuan menjadi **17 Oktober 2026**.

## Panduan demo ([DEMO.md](DEMO.md))

- Tabel akun demo memuat enam peran; setelah `migrate:fresh --seed` jalankan `php artisan user:make-owner email` agar menu **Staf** tersedia.
- Langkah demo baru 17–22: pembayaran & verifikasi bukti, batas bayar & pengembalian dana, tampilan Sales/Customer Service, alur mekanik & rating, *Tidak datang* & **Jadwalkan Ulang**, dan pengajuan belum ditanggapi.
- Tabel masalah: menu Staf tidak ada, kartu Jadwalkan Ulang / pilihan Tidak datang tidak muncul, serta tabel atau kolom baru yang belum dimigrasi.

## Pembaruan dari v1.15.0

```bash
git pull
php artisan optimize:clear
```

- Tidak ada migration, dependency, atau pengaturan `.env` baru.

## Kualitas

- 1036 test otomatis (PHPUnit) lulus di SQLite dan MySQL.
- Pemeriksaan ulang sebelum rilis: pengembalian dana diuji langsung di aplikasi lokal (catat sebagian, tutup sisanya, notifikasi, file bukti), PDF bukti pengajuan dan ekspor Excel dibuka, tampilan admin di layar ponsel, serta smoke test 204 kombinasi halaman untuk enam peran.
