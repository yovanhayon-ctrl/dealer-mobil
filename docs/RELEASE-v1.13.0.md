# JAF Dealer v1.13.0

Rilis fitur setelah [v1.12.1](RELEASE-v1.12.1.md): batas waktu pembayaran pertama, penanda jadwal yang sudah lewat, dan pencatatan pengembalian dana untuk pengajuan yang batal.

## Batas pembayaran pertama

Stok mobil berkurang saat pengajuan disetujui, sehingga pengajuan yang tidak pernah dibayar menahan unit. Sekarang:

- Setelah **disetujui**, customer punya **3 hari** (`DEALER_PAYMENT_DUE_DAYS`) untuk pembayaran pertama: tanda jadi, DP, atau lunas.
- **Customer** melihat *"Lakukan pembayaran pertama paling lambat …"* di Pengajuan Saya; setelah lewat muncul peringatan agar menghubungi dealer.
- **Admin** melihat badge **Lewat tenggat**, filter *Pembayaran: Lewat tenggat bayar*, dan peringatan di detail pengajuan. Saat memilih **Dibatalkan**, catatan terisi *"Pengajuan dibatalkan karena melewati batas pembayaran; unit dilepas kembali."* dan stok kembali seperti biasa.
- Tenggat berhenti bila sudah ada pembayaran diterima atau bukti yang sedang diverifikasi. Pengajuan kredit tanpa DP tidak pernah lewat tenggat.
- **Tidak ada pembatalan otomatis**: admin tetap yang memutuskan (tidak butuh cron di hosting).

## Jadwal lewat

- Test drive dan booking servis berstatus *Menunggu* atau *Dikonfirmasi* yang tanggalnya sudah lewat (hari ini belum termasuk) diberi badge **Lewat jadwal** di daftar & detail admin, dengan filter *Jadwal: Lewat jadwal*.
- Customer melihat *"Jadwal sudah lewat"*: untuk status Menunggu disarankan batal lalu booking ulang; untuk Dikonfirmasi dealer yang menghubungi.
- Mekanik melihat badge yang sama di **Pekerjaan Saya**.

## Pengembalian dana

Untuk pengajuan **Dibatalkan** atau **Ditolak** yang sempat menerima pembayaran:

- **Status dana** otomatis: *Dana belum dikembalikan*, *Dikembalikan sebagian*, *Dikembalikan*, atau *Tidak dikembalikan*. Status ini menggantikan badge *Lunas/Dibayar sebagian* di admin, notifikasi admin, Pengajuan Saya, dan PDF.
- Sebelum membatalkan pengajuan yang sudah menerima dana, panel status menampilkan peringatan.
- Di kartu Bukti Pembayaran: **Catat Pengembalian Dana** (nominal maksimal dana yang belum dikembalikan, boleh bertahap, metode transfer/tunai, bukti opsional di penyimpanan privat) atau **Tutup tanpa pengembalian** dengan alasan wajib (mis. tanda jadi hangus).
- Customer melihat rincian pengembalian atau alasannya, dan menerima notifikasi **Dana Dikembalikan** / **Dana Tidak Dikembalikan**.
- PDF bukti pengajuan memuat tabel *Pengembalian Dana*.
- Kebijakan pengembalian (mis. apakah tanda jadi hangus) tetap ditentukan dealer; sistem hanya mencatat.

## Dashboard admin

Strip **Perlu ditindaklanjuti** di atas ringkasan, hanya tampil bila ada: pengajuan lewat tenggat bayar, test drive/booking servis lewat jadwal, dan pengajuan batal yang dananya belum dikembalikan. Setiap angka membuka daftar yang sudah tersaring.

## Pembaruan dari v1.12.1

```bash
git pull
php artisan migrate --force
php artisan optimize
```

- **2 migration:** kolom `approved_at` di `purchase_requests` (pengajuan lama yang sudah disetujui/selesai diisi dari `updated_at`, sehingga sebagian bisa langsung tampil *Lewat tenggat*; batalkan atau biarkan sesuai kondisi sebenarnya), serta tabel `payment_refunds` dan kolom penutupan pengembalian dana.
- Opsional: `DEALER_PAYMENT_DUE_DAYS=3` di `.env` (bawaan 3 hari).
- Cadangkan database dan folder `storage` sebelum update (lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 12).

## Kualitas

- 997 test otomatis (PHPUnit) lulus di SQLite dan MySQL (24 test baru: tenggat bayar dan batasnya, jadwal lewat, pengembalian dana bertahap/ditutup, batas nominal, akses file bukti pengembalian, notifikasi, filter, dan dashboard).
- Diuji manual: pengajuan disetujui → tenggat tampil → bukti menghentikan tenggat → lewat tenggat → dibatalkan dan stok kembali. Tampilan diperiksa lewat screenshot.
