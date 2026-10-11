# JAF Dealer v1.7.2

Pembaruan kecil setelah [v1.7.1](RELEASE-v1.7.1.md): perapian halaman Tentang Kami, Servis, Simulasi Kredit, dan form booking/pengajuan. Dengan ini semua halaman publik sudah dirapikan, kecuali Bandingkan (tabel data, dibiarkan apa adanya).

## Tentang Kami

- Angka **mobil tersedia, merek, layanan servis** tampil sebagai angka besar sebaris (tanpa kartu dan ikon); tetap diambil dari database.
- **Yang Kami Tawarkan**: tiga baris (judul, penjelasan, tautan "Lihat") dipisah garis tipis, bukan tiga kartu sama besar.
- **Dari Menemukan hingga Membeli**: enam langkah dalam satu baris bersambung garis (2 kolom di HP), tanpa nomor 01-06 dan tanpa kartu.
- Penutup halaman memakai **pita bantuan yang sama dengan beranda** (WhatsApp atau Hubungi Kami, jam operasional, nomor telepon), menyambung ke footer. Pita ini kini satu partial bersama: `resources/views/partials/help-band.blade.php`.

## Servis

- Tombol **Konsultasi Servis** di hero menjadi tombol outline putih (bukan hijau).
- Kartu layanan tanpa ikon yang sama berulang; harga lebih menonjol; tombol **Booking Layanan Ini** menjadi outline.
- **Cara Booking Servis** dan **Layanan Pendukung** menjadi dua kolom teks tanpa kartu.

## Simulasi Kredit

- Satu tombol utama **Ajukan dengan Simulasi Ini**; Lihat Detail Mobil, Booking Test Drive, dan Konsultasi via WhatsApp menjadi tautan teks.

## Form

- Booking Servis: judul bagian **Data kendaraan** dan **Jadwal** memakai huruf biasa (bukan label kapital kecil).
- Ajukan Pembelian: kotak **Perkiraan cicilan** (saat memilih Kredit) berlatar netral.

## Pembaruan dari v1.7.1

```bash
git pull
php artisan optimize:clear
```

Tidak ada migration, dependency, atau pengaturan `.env` baru.

## Kualitas

- 808 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 2 test dari v1.7.1; test tombol WhatsApp kini juga mencakup Servis dan Simulasi Kredit).
- Diperiksa lewat screenshot Edge pada 1440 px dan HP 390 px: Tentang Kami, Servis, Simulasi Kredit, serta form Ajukan Pembelian, Booking Test Drive, dan Booking Servis (dirender sebagai customer).
