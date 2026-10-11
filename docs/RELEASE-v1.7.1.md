# JAF Dealer v1.7.1

Pembaruan kecil setelah [v1.7.0](RELEASE-v1.7.0.md): beberapa mobil unggulan di beranda, dan perapian halaman detail mobil, detail promo, serta kontak.

## Mobil unggulan bisa lebih dari satu

- Semua mobil yang dicentang **Jadikan unggulan di beranda** (aktif dan punya foto) tampil **bergantian** di bagian **Mobil Unggulan**, maksimal **5 mobil**; yang terakhir diubah tampil duluan.
- Pindah mobil lewat tombol ‹ ›, titik penanda, geser jari (HP), atau panah keyboard. Slider **tidak berganti sendiri**; efek bingkai miring lalu tegak saat digulir tetap ada.
- Satu mobil: tampil seperti sebelumnya, tanpa tombol. Belum ada yang dicentang: tetap memakai pilihan otomatis (Nissan berfoto terbaru).
- Form mobil di admin menampilkan jumlah mobil unggulan yang sedang aktif.

## Perapian halaman publik

**Detail mobil**
- **Ajukan Pembelian** dan **Booking Test Drive** berdampingan; Favorit dan Bandingkan menjadi tombol kecil sebaris; WhatsApp menjadi tautan teks.
- Promo yang berlaku tampil sebagai daftar biasa (bukan kotak merah muda); spesifikasi dalam grid 3 kolom (2 kolom di HP) tanpa garis per baris.

**Detail promo**
- Perbaikan: kartu **Mobil promo** dan **Promo lainnya** di kolom kanan tidak lagi memanjang menimpa footer.
- Promo tanpa banner memakai foto mobilnya (utuh, tidak terpotong) atau blok bergaya JAF.
- Dua tombol utama (Ajukan Pembelian, Lihat Detail Mobil); Test Drive, Simulasi Kredit, dan WhatsApp menjadi tautan teks.

**Kontak**
- Tombol hijau WhatsApp yang dobel dihapus (WhatsApp tetap di daftar kontak); pintasan Booking Test Drive / Servis / Simulasi Kredit menjadi tautan.

**Warna**
- Nomor halaman aktif (pagination, termasuk di admin) dan filter aktif di halaman Promo memakai hitam JAF, bukan biru bawaan Bootstrap.

## Pembaruan dari v1.7.0

```bash
git pull
php artisan optimize:clear
```

Tidak ada migration, dependency, atau pengaturan `.env` baru.

## Kualitas

- 806 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 7 test dari v1.7.0: slider mobil unggulan maks. 5 tanpa autoplay, jumlah query tetap, keterangan jumlah unggulan di admin, WhatsApp tanpa tombol hijau, grid spesifikasi, kolom samping promo).
- Slider diuji di browser (tombol, titik, urutan) dan lewat screenshot Edge pada 1280 px dan HP 390 px; detail mobil, detail promo, dan kontak diperiksa di desktop dan HP 375 px.
