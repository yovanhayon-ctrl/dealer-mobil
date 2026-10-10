# JAF Dealer v1.6.0

Pembaruan setelah [v1.5.0](RELEASE-v1.5.0.md): perapian halaman admin (daftar, form, detail) dan halaman akun customer.

## Admin

**Halaman daftar** (Mobil, Pengajuan, Test Drive, Booking Servis, Promo, Pengguna)
- Kepala halaman seragam: jumlah data ("21 mobil", "12 pengajuan ditemukan") dan tombol Tambah di kanan.
- Filter seragam dalam satu baris; **di HP dilipat** di balik tombol "Filter" dengan badge jumlah filter aktif. "Stok habis" menjadi dropdown **Stok**.
- Tabel tidak lagi melebar di laptop 1280 px: info digabung (merek · kategori · tahun · transmisi · kondisi di bawah nama mobil; kolom **Pembayaran** = metode + harga), teks panjang dipotong dengan tooltip, kolom "Masuk" hanya di layar sangat lebar.
- Tombol aksi menjadi **ikon** (Galeri, Edit, Hapus, Detail) dengan label untuk pembaca layar; **baris bisa diklik** menuju detail.

**Form** (Mobil, Merek, Kategori, Promo, Layanan)
- Form mobil **dua kolom** di layar lebar (Data Utama & Spesifikasi | Harga & Stok, Status, Galeri).
- Tombol **Simpan / Batal menempel di bawah layar** pada semua form.

**Halaman detail** (Pengajuan, Test Drive, Booking Servis, Pengguna, Galeri Mobil)
- Kepala halaman seragam: **← kembali ke daftar**, judul, keterangan (nomor dokumen, tanggal masuk), status, dan aksi (Cetak PDF, Edit Mobil, Lihat di Katalog).
- Info dalam dua kolom label | isi (bertumpuk di HP); email, telepon, dan **tombol WhatsApp** bisa diklik.
- Panel **Status & Catatan Admin menempel** saat halaman digulir.
- Detail Pengguna: ringkasan angka (test drive, pengajuan, servis) dan tabel riwayat yang bisa diklik.

## Customer — halaman akun

- **Menu akun** di semua halaman akun: kotak menu di kiri (desktop) atau **tab geser** (HP) — Profil, Pengajuan, Test Drive, Servis, Favorit, Notifikasi — lengkap dengan jumlah data.
- Judul seragam: **Pengajuan Saya, Test Drive Saya, Servis Saya, Favorit Saya, Notifikasi, Profil Saya**, dengan tombol utama (Ajukan Pembelian, Booking Test Drive, Booking Servis).
- **Penyaring status** (Semua · Berjalan · Selesai · Dibatalkan, dengan jumlah) di Pengajuan, Test Drive, dan Servis.
- **Kartu riwayat ringkas**: thumbnail kecil, rincian (DP, tenor, alamat, catatan, keluhan) di **"Lihat rincian"**; catatan dealer dan ulasan tetap terlihat. Di HP kartu ±35% lebih pendek.
- Profil: kartu ringkas + Data Diri dan Ganti Kata Sandi berdampingan.

## Pembaruan dari v1.5.0

```bash
git pull
php artisan optimize:clear
```

Tidak ada migration, dependency, atau pengaturan `.env` baru.

## Kualitas

- 777 test otomatis (PHPUnit) lulus di SQLite dan MySQL (bertambah 12 test dari v1.5.0: menu akun, penyaring status, pagination dengan penyaring).
- Diuji di browser (render data lokal) pada 1280 px, 1440 px, dan HP 375 px: tabel admin tidak melebar, filter terlipat di HP, form dua kolom dengan tombol menempel, halaman akun tanpa lebar berlebih.
