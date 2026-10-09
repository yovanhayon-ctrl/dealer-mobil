# JAF Dealer v1.4.0

Pembaruan setelah [v1.3.0](RELEASE-v1.3.0.md): cetak bukti pengajuan pembelian dalam bentuk PDF.

## Fitur baru

**Bukti pengajuan (PDF)**
- Tombol **Cetak PDF** di setiap kartu **Pengajuan Saya** (customer) dan di halaman detail pengajuan admin.
- PDF A4 satu halaman, terbuka di tab baru dan bisa disimpan sebagai `bukti-pengajuan-PB-2026-000123.pdf`:
  - kop JAF Dealer (nama, tagline, alamat, telepon, email, jam buka dari `DEALER_*` di `.env`);
  - nomor dokumen `PB-{tahun}-{id 6 digit}`, tanggal pengajuan, dan status saat dicetak;
  - data customer, data mobil, rincian pembayaran (cash: harga & total; kredit: DP, pokok, tenor, bunga, cicilan, total);
  - catatan customer & catatan dealer bila ada;
  - footer: dokumen ini bukti pengajuan, **bukan** bukti pembayaran atau kontrak jual beli.
- Akses: customer hanya pengajuan miliknya (milik orang lain = 404); admin semua pengajuan. Rate limit 20 per menit, tidak di-cache browser.
- Keamanan: semua teks di-escape, dompdf tidak memuat sumber dari internet, template hanya menerima data kop surat dari konfigurasi dealer.

## Dependency baru

- `dompdf/dompdf` ^3.1 (beserta `dompdf/php-font-lib`, `dompdf/php-svg-lib`, `masterminds/html5`, `sabberworm/php-css-parser`).
- Butuh ekstensi PHP `dom` dan `mbstring` (sudah aktif di Laragon; lihat juga [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md)).

## Pembaruan dari v1.3.0

```bash
git pull
composer install   # wajib: memasang dompdf
php artisan optimize:clear
```

Tidak ada migration baru dan tidak perlu seeder baru.

## Kualitas

- 756 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 7 test dari v1.3.0): unduh PDF (`%PDF`, nama file), akses customer/admin/tamu, isi dokumen kredit & cash, escape HTML, tanpa sumber eksternal.
- PDF nyata dirender dari data lokal dan diperiksa tampilannya (1 halaman A4).
