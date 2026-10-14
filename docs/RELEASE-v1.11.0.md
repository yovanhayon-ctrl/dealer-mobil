# JAF Dealer v1.11.0

Pembaruan setelah [v1.10.0](RELEASE-v1.10.0.md): pembayaran pengajuan (bukti transfer, status lunas, bayar di showroom), role staf (owner, admin, mekanik), alur kerja mekanik dengan checklist, dan rating mekanik.

## Pembayaran pengajuan pembelian

- **Langkah setelah pengajuan:** form pengajuan menampilkan 4 langkah (dihubungi tim, cek unit, pembayaran, serah terima) dan peringatan agar transfer hanya ke rekening atas nama dealer. Pengajuan Saya dan PDF menampilkan *Langkah berikutnya* sesuai status; tombol *Tanya via WhatsApp* berisi nomor dokumen pengajuan.
- **Bukti pembayaran:** setelah pengajuan *Disetujui*, customer melihat rekening dealer dan mengirim bukti (tanda jadi, DP, atau pelunasan; JPG/PNG/WEBP/PDF maks. 8 MB). Admin menerima atau menolak (wajib alasan) dengan pratinjau file. File disimpan di folder privat `storage/app/private` dan hanya bisa dibuka pemilik pengajuan atau admin.
- **Status pembayaran otomatis:** *Belum dibayar*, *Dibayar sebagian* / *DP sebagian*, *Lunas* / *DP lunas*, dihitung dari bukti yang diterima (cash: harga mobil; kredit: DP). Tampil di daftar dan detail admin, Pengajuan Saya, notifikasi admin, dan PDF (tabel *Pembayaran Diterima*). Total tidak bisa melebihi tagihan; setelah lunas customer tidak bisa mengirim bukti lagi. Status pengajuan tetap diubah manual (Selesai = mobil sudah diserahkan).
- **Bayar di showroom:** admin mencatat pembayaran tunai, debit/kartu kredit (EDC), atau transfer di tempat, dengan foto kwitansi opsional; langsung diterima dan ikut terhitung lunas.
- **Catatan admin otomatis:** memilih status di detail pengajuan mengisi catatan sesuai status. Untuk Ditolak/Dibatalkan, tanda "…" wajib diganti alasan.
- **Notifikasi admin** menampilkan status terkini datanya (termasuk status pembayaran), bukan status saat notifikasi dibuat.

## Role staf

| Menu / aksi | Owner | Admin | Mekanik |
|---|:-:|:-:|:-:|
| Mobil, merek, kategori, promo, layanan (tambah & ubah) | ✓ | ✓ | - |
| Hapus mobil, merek, kategori, promo, layanan | ✓ | - | - |
| Pengajuan, pembayaran, test drive, booking servis, ulasan, pengguna | ✓ | ✓ | - |
| Laporan & export Excel/CSV | ✓ | - | - |
| Staf (tambah admin/mekanik, ubah, nonaktifkan) | ✓ | - | - |
| Pekerjaan Saya & Rating Saya | - | - | ✓ |

- Akun staf dibuat owner di **Admin > Staf**, tidak lewat halaman Daftar. Staf nonaktif tidak bisa login dan sesinya langsung diakhiri; riwayatnya tetap tersimpan.
- Mekanik login lewat halaman Masuk yang sama dan langsung ke panel **Pekerjaan Saya**.
- Akun staf tidak bisa booking, mengajukan pembelian, atau menyimpan favorit.
- Owner pertama dibuat dengan perintah `php artisan user:make-owner email@...` (akun harus sudah ada).

## Bengkel: mekanik, checklist, rating

- **Daftar pekerjaan per layanan** (Admin > Layanan Servis): satu pekerjaan per baris, maks. 30.
- **Penugasan:** admin memilih mekanik di detail booking; checklist layanan tersalin ke booking, admin bisa menambah atau menghapus pekerjaan yang belum dikerjakan. Customer juga bisa **memilih mekanik saat booking** (opsional); satu mekanik hanya satu booking per jam.
- **Panel mekanik:** *Mulai Kerjakan* (pada tanggal jadwal), centang *Selesai* atau *Lewati* dengan alasan per pekerjaan, tulis *Temuan & Rekomendasi*, lalu *Servis Selesai* setelah semua pekerjaan ditangani. Customer otomatis diberi tahu saat mulai dan selesai.
- **Customer** melihat nama mekanik, progres pekerjaan, dan temuan di Akun > Servis.
- **Rating mekanik:** setelah servis selesai, customer memberi 1-5 bintang dan komentar (satu kali per booking). Mekanik melihat rating dan komentarnya (nama customer disingkat); owner dan admin melihat rata-rata di menu Staf dan detail booking. Rating tidak tampil di halaman publik.

## Lain-lain

- Menu hamburger di HP tertutup saat area luar diketuk, halaman digulir, atau Esc ditekan; menu yang lebih tinggi dari layar bisa digulir di dalamnya.
- `CLAUDE.md` dan `AGENTS.md` tidak lagi dilacak git.

## Pembaruan dari v1.10.0

```bash
git pull
php artisan migrate
php artisan user:make-owner email-admin-anda@contoh.com
php artisan optimize:clear
```

- **Wajib `php artisan migrate`:** 6 migration baru (bukti pembayaran, cara bayar, role staf + status aktif, mekanik & checklist servis, rating mekanik, mekanik pilihan customer). Tidak ada data yang dihapus.
- **Wajib membuat owner:** tanpa owner, menu Laporan dan Staf tidak bisa dibuka siapa pun. Akun admin yang sudah ada bisa dijadikan owner dengan perintah di atas.
- **`.env` baru (opsional tapi disarankan):** `DEALER_BANK_NAME`, `DEALER_BANK_ACCOUNT`, `DEALER_BANK_HOLDER` (rekening resmi dealer, tampil untuk customer setelah pengajuan disetujui).
- Isi *Daftar Pekerjaan Mekanik* di setiap layanan dan buat akun mekanik di Admin > Staf.
- Folder `storage/app/private` (bukti pembayaran) ikut dicadangkan bersama `storage/app/public`.
- Tidak ada dependency baru.

## Kualitas

- 951 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 109 test dari v1.10.0: bukti & status pembayaran, pembayaran showroom, catatan otomatis, notifikasi, hak akses tiap role, staf nonaktif, alur mekanik & checklist, rating mekanik, pilih mekanik saat booking).
- Halaman baru diperiksa lewat screenshot (desktop & HP, mode terang & gelap) dengan data contoh yang di-rollback; checklist, catatan otomatis, dan pengetikan angka diuji di browser.
