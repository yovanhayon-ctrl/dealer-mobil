# JAF Dealer v1.18.0

Rilis fitur setelah [v1.17.0](RELEASE-v1.17.0.md): menu **Pengaturan** untuk mengubah profil dealer dan hero beranda dari admin, serta pembagian tugas Super Admin dan Customer Service yang lebih tegas.

## Menu Pengaturan

Menu baru **Pengaturan** (`/admin/pengaturan`), khusus **Owner** dan **Super Admin**. Isinya dibagi per bagian:

- **Identitas**: nama dealer, tagline, alamat showroom.
- **Kontak**: telepon, WhatsApp (diketik 0812… disimpan 62812…), email, jam operasional, link peta Google Maps (hanya link "Sematkan peta").
- **Halaman Tentang**: teks bagian *Siapa Kami*. Paragraf dipisah baris kosong; kosong = teks bawaan.
- **Hero Beranda**: teks kecil di atas judul (kosong = tagline), judul, teks di bawah judul, dan latar:
  - **Video**: video `DEALER_HERO_VIDEOS` di layar lebar, foto mobil unggulan di HP (seperti sebelumnya).
  - **Gambar banner**: gambar unggahan sendiri di semua ukuran layar. JPG/PNG/WEBP, maks. 8 MB, min. 1200×500 px; disimpan otomatis sebagai WebP lebar 1920 px. Gambar lama terhapus saat diganti.
  - **Foto mobil unggulan**: foto dan kartu mobil unggulan, tanpa video.
- **Rekening Pembayaran**: nama bank, nomor rekening, atas nama (isi ketiganya atau kosongkan semuanya).

Cara kerja nilai:

- Nilai dari Pengaturan **menggantikan** nilai `.env` (`DEALER_*`) di seluruh situs: navbar, footer, Kontak, Tentang, PDF pengajuan, pengingat batas bayar, notifikasi, dan email. File `.env` tidak diubah.
- Isian yang **dikosongkan** kembali ke nilai `.env`. Petunjuk *"Kosongkan untuk kembali ke bawaan: …"* muncul bila nilai saat ini berbeda dari `.env`.

Perubahan rekening:

- Owner aktif lain menerima notifikasi **Rekening Pembayaran Diubah** (lonceng, dan email bila `DEALER_MAIL_NOTIFICATIONS=true`) berisi rekening baru dan sebelumnya.
- Perubahan juga dicatat di log aplikasi.
- Halaman Pengaturan menampilkan *"Terakhir diubah oleh … pada …"*.

## Pembagian tugas role (fase 1)

Mengikuti pembagian tugas enam role yang disepakati:

| Role | Perubahan |
|---|---|
| **Super Admin** | Pengelola sistem: kelola Staf, Pengaturan, hapus data master, dan tetap bisa melihat semua menu. **Tidak lagi** mengubah/menghapus promo, mengubah status pengajuan, memverifikasi pembayaran, membuka file bukti bayar, atau mencatat pengembalian dana (hak Owner & Admin Dealer). Tidak lagi menerima notifikasi bukti pembayaran. |
| **Customer Service** | Test drive **hanya lihat** (status & catatan tampil, tanpa tombol ubah/jadwal ulang; diurus Sales/Admin Dealer). Menu **Mobil** dan **Promo** kini bisa dibuka (hanya lihat) untuk menjawab pertanyaan customer. Booking servis tetap dikelola penuh. |
| Owner, Admin Dealer, Sales, Mekanik | Tidak berubah. |

Semua batasan diperiksa di server (route), bukan hanya menyembunyikan tombol: akses lewat URL langsung ditolak (403). Tabel *Peran Staf* di README diperbarui.

**Kebijakan Privasi** bagian 3 menyesuaikan (Super Admin dipisah dari Pemilik & Admin Dealer, tidak membuka file bukti pembayaran); tanggal berlaku menjadi **20 Oktober 2026**.

## Pembaruan dari v1.17.0

```bash
git pull
php artisan migrate --force
php artisan optimize
```

- **1 migration**: tabel `settings`.
- Gambar banner disimpan di disk `public` (folder `storage/app/public/settings`), jadi `php artisan storage:link` harus sudah pernah dijalankan (sama seperti foto mobil).
- Tidak ada pengaturan `.env` baru; nilai `DEALER_*` tetap menjadi bawaan.

## Kualitas

- 1057 test otomatis (PHPUnit) lulus di SQLite dan MySQL. Test baru:
  - Pengaturan: menggantikan `.env` di halaman publik dan notifikasi, kembali ke `.env` saat dikosongkan, validasi, unggah/ganti/hapus banner, notifikasi perubahan rekening.
  - Role: Super Admin tanpa keputusan bisnis, Customer Service hanya melihat test drive, mobil, dan promo.
- Tampilan Pengaturan, beranda (latar gambar banner & mobil unggulan), dan halaman Tentang dicek di layar lebar, HP, dan mode gelap.
