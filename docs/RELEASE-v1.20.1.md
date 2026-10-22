# JAF Dealer v1.20.1

Rilis perbaikan setelah [v1.20.0](RELEASE-v1.20.0.md): teks halaman legal disamakan dengan cara kerja aplikasi, ditambah dokumen persiapan go-live.

## Halaman legal (berlaku 22 Oktober 2026)

Dealer menyetujui isi **Kebijakan Privasi** dan **Syarat & Ketentuan**. Ditambahkan hal yang sudah berjalan di aplikasi tetapi belum tertulis:

- **Syarat & Ketentuan, bagian 5 (Pembayaran):**
  - pembayaran pertama paling lambat **3 hari** setelah pengajuan disetujui; lewat batas, dealer dapat membatalkan pengajuan dan unit ditawarkan kembali;
  - bila pengajuan dibatalkan atau ditolak setelah ada pembayaran, pengembalian dana diproses sesuai kesepakatan dengan dealer dan tercatat di **Pengajuan Saya**.
- **Kebijakan Privasi:**
  - bagian 1: ulasan tampil dengan nama depan dan inisial nama belakang (mis. "Budi S.");
  - bagian 5: cadangan data harian disimpan paling lama **30 hari** lalu dihapus otomatis.
- Angka 3 hari dan 30 hari dibaca dari `DEALER_PAYMENT_DUE_DAYS` dan `DEALER_BACKUP_KEEP_DAYS`, sehingga teks ikut berubah bila pengaturan diubah.
- Tanggal berlaku kedua halaman menjadi **22 Oktober 2026**.

## Dokumen

- [LEGAL-REVIEW.md](LEGAL-REVIEW.md): ringkasan isi halaman legal, aturan aplikasi yang sebelumnya belum tertulis, dan pertanyaan lanjutan untuk dealer (opsional).
- [CATATAN-REPO-DAN-HOSTING.md](CATATAN-REPO-DAN-HOSTING.md): repo GitHub dan arsip, cadangan repo, folder development dan release, tiga file `.env`, langkah hosting dari nol memakai data dari folder release, dan hal yang perlu diperhatikan.
- [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md): folder paket kode diganti menjadi `paket-deploy` agar tidak menimpa folder data `dealer-mobil-release`.
- [GO-LIVE.md](GO-LIVE.md): persetujuan dealer atas halaman legal ditandai selesai.

## Pembaruan dari v1.20.0

```bash
git pull
php artisan optimize
```

- Tidak ada migration dan tidak ada pengaturan `.env` baru.

## Kualitas

- 1065 test otomatis (PHPUnit) lulus di SQLite dan MySQL; test halaman legal kini memeriksa keempat kalimat baru dan tanggal berlakunya.
