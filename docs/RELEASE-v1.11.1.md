# JAF Dealer v1.11.1

Pembaruan kecil setelah [v1.11.0](RELEASE-v1.11.0.md): JAF Dealer menjadi dealer khusus Nissan, kategori mobil mengikuti lini Nissan, dan info halaman di tabel admin dirapikan.

## Dealer khusus Nissan

- Data awal (seeder) hanya membuat merek **Nissan** dan **14 mobil Nissan** (8 baru, 6 bekas/heritage seperti Skyline GT-R R34 dan Silvia S15). Mobil klasik merek lain dihapus dari data awal.
- Selama mobil aktif hanya dari satu merek, filter **Merek** di katalog dan kartu *Berdasarkan merek* di beranda disembunyikan (kartu kategori melebar penuh). Tautan lama seperti `/mobil?merek=nissan` tetap berfungsi.
- Form **Tambah Mobil** di admin langsung memilih merek tersebut.
- Menu **Merek** di admin tetap ada: bila kelak menambah merek lain, filter dan kartu merek muncul kembali tanpa perubahan kode.
- Teks Tentang Kami dan deskripsi SEO beranda/Tentang Kami menyebut koleksi Nissan heritage, bukan lagi mobil klasik Jepang.

## Kategori Nissan

| Kategori | Contoh mobil |
|---|---|
| SUV | Kicks e-Power, X-Trail e-Power, Magnite, Juke |
| MPV | Serena e-Power, Livina, Grand Livina |
| Hatchback | (belum ada; muncul otomatis saat ada mobilnya) |
| Pickup | Navara |
| Sport | GT-R, Skyline GT-R R34, Silvia S15, 350Z |
| EV *(baru)* | Leaf |

- Kategori **Sedan** dihapus (Nissan tidak punya sedan di katalog). Beranda dan filter katalog hanya menampilkan kategori yang punya mobil aktif.

## Admin

- Baris *"Menampilkan 1 sampai X dari X data"* dan tombol halaman tidak tampil lagi bila semua data muat di satu halaman (semua tabel admin dan Rating Saya mekanik).

## Pembaruan dari v1.11.0

```bash
git pull
php artisan optimize:clear
```

- Tidak ada migration, dependency, atau pengaturan `.env` baru.
- Perubahan data awal hanya berlaku untuk instalasi baru (`db:seed`). Database yang sudah berisi data lama **tidak** diubah otomatis. Untuk menyesuaikannya lewat admin:
  1. **Kategori:** tambah kategori **EV** lalu ubah kategori Leaf ke EV; pindahkan mobil dari **Sedan** (mis. ke Sport) lalu hapus Sedan.
  2. **Mobil merek lain:** hapus yang belum punya test drive/pengajuan/promo; yang sudah punya cukup dinonaktifkan agar riwayatnya tetap utuh.
  3. **Merek lain:** hapus merek yang sudah tidak punya mobil.
  Penghapusan data master hanya bisa dilakukan akun **owner**.

## Kualitas

- 954 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 3 test dari v1.11.0: info halaman satu halaman, filter & kartu merek untuk dealer satu merek, merek otomatis di form Tambah Mobil; test seeder merek, mobil, dan kategori disesuaikan).
- Data lokal dikonversi dengan cadangan lebih dulu (data & foto mobil non-Nissan, kategori); beranda, katalog, dan filter kategori diperiksa langsung.
