# JAF Dealer v1.10.0

Pembaruan setelah [v1.9.0](RELEASE-v1.9.0.md): isian angka (harga, kilometer, tahun, nomor WhatsApp, dan lainnya) hanya menerima angka, di halaman publik maupun admin.

## Saat mengetik (browser)

- **Harga, DP, diskon promo, harga layanan, kilometer:** hanya angka yang masuk, titik ribuan muncul otomatis (`25o0x000` → `250.000`).
- **Tahun, stok, jumlah kursi, kapasitas mesin, durasi layanan:** hanya angka.
- **Nomor WhatsApp:** hanya angka, boleh diawali `+`. Nomor yang ditempel dengan spasi, tanda hubung, atau kurung langsung dirapikan (`+62 (812) 3456-7890` → `+6281234567890`).
- Berlaku di: form mobil, promo, dan layanan (admin); simulasi kredit, pengajuan pembelian, booking servis, booking test drive, profil, daftar akun, dan filter harga/tahun katalog.
- Batas stok di browser kini sama dengan server (0–9999).
- Teknis: satu skrip `public/js/number-input.js` dimuat di semua layout; isian ditandai atribut `data-number="thousands|digits|phone"`. Tanpa skrip inline (CSP tetap ketat).

## Saat dikirim (server)

- Isian berhuruf seperti `45rb`, `abc`, `1,2 M`, atau `60jt` **ditolak** dengan pesan jelas, misalnya "Kilometer hanya boleh berisi angka." Sebelumnya `45rb` diam-diam tersimpan sebagai 45.
- Format yang wajar tetap diterima: `285.000.000`, `Rp 285.000.000`, `45 000`.
- Simulasi kredit menampilkan "Harga hanya boleh berisi angka." / "Uang muka hanya boleh berisi angka." alih-alih menghitung dari angka yang salah.
- Nomor WhatsApp yang mengandung huruf ditolak di kelima form (daftar, profil, test drive, booking servis, pengajuan pembelian).
- Plat nomor, nama, alamat, dan catatan tidak berubah. Filter katalog tetap mengabaikan isian tidak valid tanpa pesan error.
- Teknis: aturan format ada di `App\Support\NumberInput`, dipakai oleh `NormalizesDigits` (form request) dan `CreditSimulationController`.

## Pembaruan dari v1.9.0

```bash
git pull
php artisan optimize:clear
```

- Tidak ada dependency, migration, atau pengaturan `.env` baru.
- Di hosting, pastikan file baru `public/js/number-input.js` ikut terunggah.

## Kualitas

- 842 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 18 test dari v1.9.0: format yang diterima/ditolak, pesan error tiap form publik & admin, nomor WhatsApp berhuruf, simulasi kredit, dan atribut isian di setiap form).
- Pengetikan dan tempel diuji langsung di browser (simulasi kredit dan daftar akun), termasuk nomor WhatsApp panjang yang ditempel.
