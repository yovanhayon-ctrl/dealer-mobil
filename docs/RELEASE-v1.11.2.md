# JAF Dealer v1.11.2

Pembaruan kecil setelah [v1.11.1](RELEASE-v1.11.1.md): pengelolaan **Merek** dan **Kategori** sekarang khusus owner.

## Hak akses

| Menu | Owner | Admin |
|---|---|---|
| Merek | Lihat, tambah, ubah, hapus | Tidak tampil (403 bila dibuka langsung) |
| Kategori | Lihat, tambah, ubah, hapus | Tidak tampil (403 bila dibuka langsung) |
| Mobil, Promo, Layanan Servis | Lihat, tambah, ubah, hapus | Lihat, tambah, ubah |

- Merek dan kategori jarang berubah, tetapi perubahannya langsung memengaruhi filter katalog dan pintasan di beranda, jadi hanya owner yang mengelolanya.
- Admin tetap memilih merek dan kategori yang sudah ada saat menambah atau mengubah mobil.
- Menu Merek dan Kategori di sidebar admin hanya muncul untuk owner.

## Pembaruan dari v1.11.1

```bash
git pull
php artisan optimize:clear
```

- Tidak ada migration, dependency, atau pengaturan `.env` baru.
- Bila admin perlu merek atau kategori baru, minta owner menambahkannya.

## Kualitas

- 960 test otomatis (PHPUnit) lulus di SQLite dan MySQL, termasuk test baru: admin mendapat 403 saat membuka, menambah, atau mengubah merek dan kategori; menu keduanya hanya tampil untuk owner; form mobil admin tetap menampilkan merek dan kategori yang ada.
- Semua halaman dibuka ulang per role (owner, admin, mekanik, customer, tamu) pada data lokal: 144 kombinasi sesuai.
