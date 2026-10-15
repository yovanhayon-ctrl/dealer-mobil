# JAF Dealer v1.14.0

Rilis fitur setelah [v1.13.0](RELEASE-v1.13.0.md): enam peran staf dengan hak akses yang diatur dari satu daftar izin.

## Peran dan fungsinya

| Peran | Untuk siapa | Fungsi utama | Tidak dapat |
|---|---|---|---|
| **Owner** | Pemilik dealer | Memantau seluruh aktivitas dan laporan bisnis; mengawasi penjualan, pembayaran, pengembalian dana, dan servis; mengelola semua akun staf termasuk Super Admin; satu-satunya yang (bersama Super Admin) boleh menghapus data master. | - |
| **Super Admin** | Pengelola sistem | Semua menu operasional; mengelola akun Admin Dealer, Sales, Customer Service, dan Mekanik; membantu menangani masalah sistem; menghapus data master. | Membuat/mengubah akun Owner dan Super Admin (termasuk akunnya sendiri). |
| **Admin Dealer** | Admin operasional | Mengelola mobil, merek, kategori, dan promo; memproses test drive, pengajuan, pembayaran, pengembalian dana, dan servis; moderasi ulasan; melihat & mengekspor laporan. | Mengelola akun staf; menghapus data master. |
| **Sales** | Tenaga penjual | Melayani calon pembeli; mengatur jadwal test drive; memproses status pengajuan pembelian; melihat stok mobil dan promo. | Mengubah data mobil/promo; memverifikasi bukti bayar, mencatat pembayaran showroom, atau pengembalian dana; membuka file bukti bayar. |
| **Customer Service** | Layanan pelanggan | Menindaklanjuti permintaan pelanggan; mengonfirmasi/membatalkan jadwal test drive dan servis serta menugaskan mekanik; melihat status pengajuan untuk memberi informasi; melihat ulasan dan data customer. | Mengubah status pengajuan atau transaksi finansial; moderasi ulasan; melihat akun staf; menghapus data. |
| **Mekanik** | Staf bengkel | Melihat pekerjaan servis yang ditugaskan beserta checklist; memulai, mencentang, mencatat temuan, dan menyelesaikan servis; melihat daftar layanan & checklist bawaannya; melihat rating dari customer. | Membuka panel admin: pembayaran, penjualan, akun pengguna, dan laporan. |

Akun Owner pertama dibuat dengan `php artisan user:make-owner email`; peran lain dibuat Owner/Super Admin di menu **Staf**.

## Hak akses per menu

Kelola = tambah/ubah, Lihat = hanya baca, - = menu tidak tampil (dibuka langsung: 403).

| Menu | Owner | Super Admin | Admin Dealer | Sales | Customer Service | Mekanik |
|---|---|---|---|---|---|---|
| Dashboard | ✅ | ✅ | ✅ | ✅ ringkas | ✅ ringkas | Pekerjaan Saya |
| Notifikasi | ✅ | ✅ | ✅ | pengajuan & test drive | test drive, pengajuan, servis, ulasan | pekerjaan baru |
| Mobil | Kelola | Kelola | Kelola | Lihat | - | - |
| Merek, Kategori | Kelola | Kelola | Kelola | - | - | - |
| Promo | Kelola | Kelola | Kelola | Lihat | - | - |
| Test Drive | Kelola | Kelola | Kelola | Kelola | Kelola | - |
| Pengajuan | Kelola | Kelola | Kelola | Ubah status | Lihat | - |
| Bukti bayar, pembayaran showroom, pengembalian dana | ✅ | ✅ | ✅ | - | - | - |
| Booking Servis | Kelola | Kelola | Kelola | - | Kelola + tugaskan mekanik | yang ditugaskan |
| Layanan Servis | Kelola | Kelola | Kelola | - | - | Lihat |
| Ulasan | Moderasi | Moderasi | Moderasi | - | Lihat | - |
| Pengguna | Semua akun | Semua akun | Semua akun | - | Customer saja | - |
| Laporan & ekspor | ✅ | ✅ | ✅ | - | - | - |
| Staf | Semua peran | Kecuali Owner & Super Admin | - | - | - | - |
| Hapus data master | ✅ | ✅ | - | - | - | - |

## Perubahan dari v1.13.0

- Peran **admin** kini berlabel **Admin Dealer** dan kembali bisa mengelola **Merek**, **Kategori**, serta membuka **Laporan** (sebelumnya khusus owner sejak v1.11.2). Hapus data master tetap khusus Owner (dan kini Super Admin).
- Peran baru **Super Admin**, **Sales**, dan **Customer Service**, dibuat lewat menu **Staf**.
- Mode **lihat saja**: tombol tambah/ubah/hapus disembunyikan dan alamatnya ditolak (403). Sales melihat daftar mobil dengan tombol *lihat di website*.
- **Dashboard** per peran: hanya kartu, grafik, dan daftar sesuai menunya (grafik penjualan untuk yang boleh membuka Laporan).
- **Notifikasi admin** dikirim hanya ke peran yang berhak atas datanya (mis. bukti pembayaran hanya ke Owner, Super Admin, Admin Dealer).
- **Mekanik**: menu **Notifikasi** (pekerjaan servis baru saat booking yang ditugaskan dikonfirmasi atau dialihkan kepadanya) dan **Layanan Servis** (hanya lihat).
- Untuk pengembang: aturan akses ada di satu tempat, `User::PERMISSIONS` (kemampuan => peran), didaftarkan sebagai Gate dan dipakai route (`can:...`), Blade (`@can`), sidebar, dashboard, dan penerima notifikasi.

## Pembaruan dari v1.13.0

```bash
git pull
php artisan migrate --force
php artisan optimize
```

- **1 migration**: menambah nilai peran `super_admin`, `sales`, `customer_service` di tabel `users`. Akun yang sudah ada tidak berubah.
- Setelah update, Owner membuat akun Super Admin/Sales/Customer Service di menu **Staf** bila diperlukan.
- Cadangkan database sebelum update (lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 12).

## Kualitas

- 1028 test otomatis (PHPUnit) lulus di SQLite dan MySQL (31 test baru: akses 19 halaman untuk setiap peran, sidebar per peran, mode lihat saja Sales, hapus data master, pengajuan untuk Sales & CS, booking servis oleh CS, pengguna terbatas, kelola staf oleh Super Admin, penerima notifikasi per peran, notifikasi & layanan mekanik).
- Tampilan diperiksa per peran (dashboard & mobil Sales, detail pengajuan CS, layanan mekanik).
