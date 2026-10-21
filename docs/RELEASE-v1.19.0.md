# JAF Dealer v1.19.0

Rilis fitur setelah [v1.18.0](RELEASE-v1.18.0.md): **Riwayat Aktivitas**, catatan tindakan sensitif staf (pembagian tugas role, fase 2).

## Yang dicatat

| Kelompok | Aktivitas |
|---|---|
| **Akun & Sistem** | Staf ditambah; role diubah (lama → baru); diaktifkan / dinonaktifkan; nama, email, atau WhatsApp diubah; kata sandi direset; Pengaturan diubah (nama bagian yang berubah). |
| **Keuangan** | Bukti bayar diterima / ditolak (dengan alasan), pembayaran di showroom dicatat, pengembalian dana dicatat / ditutup, rekening pembayaran diubah (lama → baru). |
| **Pengajuan** | Status pengajuan diubah, mis. *"PB-2026-000031: Diproses → Disetujui"*. |
| **Hapus Data** | Mobil, merek, kategori, promo, atau layanan servis dihapus. |
| **Login** | Staf berhasil masuk (login customer tidak dicatat). |

Status test drive dan servis, ubah data mobil biasa, dan aktivitas customer tidak dicatat (sudah punya riwayat/notifikasi sendiri).

Setiap catatan menyimpan waktu, nama dan role pelaku saat itu (tetap terbaca walau akunnya dihapus atau rolenya berubah), nilai lama/baru, dan alamat IP.

- Aksi yang gagal validasi atau ditolak (403) tidak dicatat; menyimpan akun staf tanpa perubahan juga tidak.
- Bila pencatatan gagal, aksi utamanya tetap berhasil (error dicatat di log aplikasi).
- Perubahan rekening yang sebelumnya hanya ditulis ke log aplikasi kini masuk ke Riwayat Aktivitas; notifikasi ke owner lain tetap dikirim.

## Halaman Riwayat Aktivitas

- `/admin/staf/aktivitas`, dari tombol **Riwayat Aktivitas** di halaman **Staf** (tanpa menu baru).
- Hanya **Owner** dan **Super Admin** (hak `activity.view`); role lain 403.
- Filter kelompok, staf, dan rentang tanggal; 25 per halaman, terbaru di atas.
- Teks aktivitas menautkan ke pengajuan, akun, atau halaman Pengaturan terkait bila datanya masih ada.
- Di HP, nama staf dan kelompok tampil di dalam kolom aktivitas.
- **Tidak bisa diubah atau dihapus** dari aplikasi, termasuk oleh Owner. Catatan disimpan tanpa batas waktu.

## Pembaruan dari v1.18.0

```bash
git pull
php artisan migrate --force
php artisan optimize
```

- **1 migration**: tabel `activity_logs`.
- Tidak ada pengaturan `.env` baru.

## Kualitas

- 1061 test otomatis (PHPUnit) lulus di SQLite dan MySQL. Test baru: akun staf & login staf (customer tidak), keuangan & status pengajuan, hapus data (yang gagal atau ditolak tidak tercatat), halaman & filter Riwayat Aktivitas per role, serta pencatatan Pengaturan dan rekening.
- Halaman Riwayat Aktivitas dan halaman Staf dicek di layar lebar dan HP.
