# JAF Dealer v1.8.0

Pembaruan setelah [v1.7.3](RELEASE-v1.7.3.md): banner promo kini diolah otomatis seperti foto mobil.

## Banner promo lebih ringan

- Batas upload banner naik dari 2 MB ke **8 MB** (ukuran 1200×400 sampai 8000×8000 piksel), jadi banner dari HP atau file desain bisa langsung diunggah.
- Setiap banner yang diunggah otomatis **diperkecil ke lebar maksimal 1600 px** (tinggi menyesuaikan, banner kecil tidak diperbesar) dan disimpan sebagai **WebP** dengan nama file acak. Orientasi foto HP diperbaiki, data EXIF/GPS ikut terbuang, latar transparan tetap terjaga. File asli tidak disimpan.
- Banner yang rusak atau tidak bisa dibaca ditolak dengan pesan jelas di form, dan promo tidak tersimpan.
- Form promo di admin menampilkan batas dan aturan yang berlaku ("Maksimal 8 MB … Disimpan otomatis sebagai WebP (lebar maks. 1600 px)").
- Pengolahan memakai kode yang sama dengan foto mobil (`App\Support\CarImageProcessor::storeSingle()`); tampilan promo di website tidak berubah.

Banner yang sudah tersimpan sebelum versi ini tetap tampil apa adanya; banner baru atau yang diganti akan diolah otomatis.

## Pembaruan dari v1.7.3

```bash
git pull
php artisan optimize:clear
```

- Tidak ada migration, dependency, atau pengaturan `.env` baru.
- **Hosting:** pastikan `upload_max_filesize` ≥ 8M dan `post_max_size` ≥ 80M (sama seperti foto mobil, lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md)).

## Kualitas

- 818 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 4 test dari v1.7.3: banner besar menjadi WebP 1600 px, banner kecil tidak diperbesar, banner rusak ditolak, petunjuk 8 MB di form).
- Isi `storage/app/public` dipastikan tidak berubah selama seluruh test dijalankan.
